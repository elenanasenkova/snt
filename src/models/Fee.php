<?php
namespace models;
use services\DatabaseConnection;

class Fee {
    public static function typesByYear(int $year): array {
        $db = DatabaseConnection::get();
        // For fixed-amount fee types (amount > 0): required_total = amount * PLOT_COUNT (61)
        // For electricity (amount = 0): required_total = SUM(required_amount) from actual records
        // paid_total = SUM(paid_amount) WHERE status = 'paid'
        $stmt = $db->prepare("
            SELECT ft.*,
                (SELECT COUNT(*) FROM fee_payments fp  WHERE fp.fee_type_id = ft.id AND fp.status = 'paid') AS paid_count,
                (SELECT COUNT(*) FROM fee_payments fp2 WHERE fp2.fee_type_id = ft.id) AS total_count,
                CASE
                    WHEN ft.amount > 0 AND ft.plot_scope = 'selected'
                        THEN COALESCE((SELECT SUM(fps.required_amount) FROM fee_payments fps WHERE fps.fee_type_id = ft.id), 0)
                    WHEN ft.amount > 0 THEN ft.amount * 61
                    ELSE COALESCE((SELECT SUM(fp3.required_amount) FROM fee_payments fp3 WHERE fp3.fee_type_id = ft.id), 0)
                END AS required_total,
                COALESCE((SELECT SUM(fp4.paid_amount) FROM fee_payments fp4 WHERE fp4.fee_type_id = ft.id AND fp4.status = 'paid'), 0) AS paid_total
            FROM fee_types ft
            WHERE ft.year = ?
            ORDER BY ft.created_at
        ");
        $stmt->bind_param('i', $year);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        $stmt->close();
        return $rows;
    }

    public static function paymentsByPlot(string $plotNumber): array {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("SELECT fp.*, ft.name AS fee_name, ft.year FROM fee_payments fp JOIN fee_types ft ON fp.fee_type_id = ft.id WHERE fp.plot_number = ? ORDER BY ft.year DESC, ft.created_at");
        $stmt->bind_param('s', $plotNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        $stmt->close();
        return $rows;
    }

    /** Создать вид взноса.
     *  $plotScope: 'all' — начисляется всем участкам (записи fee_payments создаются при оплате),
     *              'selected' — только участкам из $plots (сразу создаём записи со статусом 'unpaid').
     */
    public static function createType(
        string $name, int $year, float $amount, string $description, int $createdBy,
        string $plotScope = 'all', array $plots = []
    ): int {
        $db    = DatabaseConnection::get();
        $scope = $plotScope === 'selected' ? 'selected' : 'all';

        $stmt = $db->prepare("INSERT INTO fee_types (name, year, amount, description, created_by, plot_scope) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sidsis', $name, $year, $amount, $description, $createdBy, $scope);
        $stmt->execute();
        $id = $db->insert_id;
        $stmt->close();

        // Адресный взнос — посев записей «начислено, не оплачено» по выбранным участкам
        if ($scope === 'selected') {
            $seed = $db->prepare(
                "INSERT IGNORE INTO fee_payments (fee_type_id, plot_number, required_amount, paid_amount, status)
                 VALUES (?, ?, ?, 0, 'unpaid')"
            );
            foreach ($plots as $pn) {
                $pn = trim((string)$pn);
                if ($pn === '') continue;
                $seed->bind_param('isd', $id, $pn, $amount);
                $seed->execute();
            }
            $seed->close();
        }

        return $id;
    }

    public static function availableYears(): array {
        $db = DatabaseConnection::get();
        $result = $db->query("SELECT DISTINCT year FROM fee_types ORDER BY year DESC");
        $years = [];
        while ($row = $result->fetch_row()) $years[] = (int)$row[0];
        return $years ?: [date('Y')];
    }

    /** Задолженность по участкам за год (из реальных записей fee_payments).
     *  Возвращает список участков с долгом > 0, по каждому — суммарный долг и разбивка по взносам.
     *  Формат: [ ['plot' => '12', 'total' => 1500.0, 'items' => [['name'=>..,'debt'=>..], ...]], ... ]
     */
    public static function debtByPlot(int $year): array {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare(
            "SELECT fp.plot_number,
                    ft.name AS fee_name,
                    (fp.required_amount - fp.paid_amount) AS debt
               FROM fee_payments fp
               JOIN fee_types ft ON fp.fee_type_id = ft.id
              WHERE ft.year = ?
                AND fp.status <> 'paid'
                AND (fp.required_amount - fp.paid_amount) > 0
              ORDER BY fp.plot_number + 0, ft.created_at"
        );
        $stmt->bind_param('i', $year);
        $stmt->execute();
        $result = $stmt->get_result();
        $plots = [];
        while ($row = $result->fetch_assoc()) {
            $plot = (string)$row['plot_number'];
            if (!isset($plots[$plot])) {
                $plots[$plot] = ['plot' => $plot, 'total' => 0.0, 'items' => []];
            }
            $debt = (float)$row['debt'];
            $plots[$plot]['total'] += $debt;
            $plots[$plot]['items'][] = ['name' => $row['fee_name'], 'debt' => $debt];
        }
        $stmt->close();
        return array_values($plots);
    }

    /** Все платежи за год (JOIN fee_types) */
    public static function paymentsByYear(int $year): array {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare(
            "SELECT fp.*, ft.name AS fee_name, ft.amount AS fee_amount
               FROM fee_payments fp
               JOIN fee_types ft ON fp.fee_type_id = ft.id
              WHERE ft.year = ?
              ORDER BY fp.plot_number + 0, ft.created_at"
        );
        $stmt->bind_param('i', $year);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        $stmt->close();
        return $rows;
    }

    /** Установить/обновить оплату участка.
     *  Для электричества (ft.amount=0): required_amount берётся из существующей записи fee_payments.
     *  Для целевых взносов: required_amount = ft.amount.
     */
    public static function upsertPayment(int $feeTypeId, string $plotNumber, string $status, float $paidAmount, string $notes = ''): void {
        $db = DatabaseConnection::get();

        // 1. Читаем базовую сумму из fee_types
        $stmt = $db->prepare("SELECT amount FROM fee_types WHERE id = ?");
        $stmt->bind_param('i', $feeTypeId);
        $stmt->execute();
        $reqRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $ftAmount = (float)($reqRow['amount'] ?? 0);

        // 2. Электричество (amount=0): required_amount индивидуален — читаем из fee_payments
        if ($ftAmount == 0) {
            $s2 = $db->prepare("SELECT required_amount FROM fee_payments WHERE fee_type_id=? AND plot_number=?");
            $s2->bind_param('is', $feeTypeId, $plotNumber);
            $s2->execute();
            $existRow = $s2->get_result()->fetch_assoc();
            $s2->close();
            $requiredAmount = $existRow ? (float)$existRow['required_amount'] : 0;
        } else {
            $requiredAmount = $ftAmount;
        }

        // 3. Если платим полностью и сумма не указана — paidAmount = requiredAmount
        if ($status === 'paid' && $paidAmount == 0) {
            $paidAmount = $requiredAmount;
        }

        // 4. paid_at ставим при любой оплате (полной или частичной), сбрасываем при unpaid
        $paidAt = in_array($status, ['paid', 'partial'], true) ? date('Y-m-d H:i:s') : null;

        $stmt3 = $db->prepare(
            "INSERT INTO fee_payments (fee_type_id, plot_number, required_amount, paid_amount, status, notes, paid_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               status       = VALUES(status),
               paid_amount  = VALUES(paid_amount),
               notes        = VALUES(notes),
               paid_at      = VALUES(paid_at)"
        );
        $stmt3->bind_param('isddsss', $feeTypeId, $plotNumber, $requiredAmount, $paidAmount, $status, $notes, $paidAt);
        $stmt3->execute();
        $stmt3->close();
    }

    /** Задать индивидуальную сумму участка для счёта за свет.
     *  Создаёт запись fee_payments с required_amount и status='unpaid'.
     *  Если amount=0 — участок без потребления, сразу paid.
     */
    public static function setElecAmount(int $feeTypeId, string $plotNumber, float $amount): void {
        $db  = DatabaseConnection::get();
        $status  = ($amount == 0) ? 'paid' : 'unpaid';
        $paidAt  = ($amount == 0) ? date('Y-m-d H:i:s') : null;
        $paidAmt = 0.0;

        $stmt = $db->prepare(
            "INSERT INTO fee_payments (fee_type_id, plot_number, required_amount, paid_amount, status, paid_at)
             VALUES (?, ?, ?, 0, ?, ?)
             ON DUPLICATE KEY UPDATE
               required_amount = VALUES(required_amount),
               status          = VALUES(status),
               paid_amount     = IF(VALUES(status)='paid', 0, paid_amount),
               paid_at         = VALUES(paid_at)"
        );
        $stmt->bind_param('isdss', $feeTypeId, $plotNumber, $amount, $status, $paidAt);
        $stmt->execute();
        $stmt->close();
    }

    /** Импорт счёта за свет: создаёт fee_type с amount=0, затем записи по участкам.
     *  $plots = [['plot_number'=>'1', 'amount'=>500.50], ...]
     *  Участки не в списке получают required_amount=0 (нет потребления → paid).
     */
    public static function importElectricity(string $name, int $year, array $plots, int $createdBy): int {
        $db = DatabaseConnection::get();

        // Создать вид взноса с amount=0
        $stmt = $db->prepare(
            "INSERT INTO fee_types (name, year, amount, description, created_by) VALUES (?, ?, 0, 'Счёт за электроэнергию', ?)"
        );
        $stmt->bind_param('sii', $name, $year, $createdBy);
        $stmt->execute();
        $feeTypeId = $db->insert_id;
        $stmt->close();

        // Записываем индивидуальные суммы
        foreach ($plots as $row) {
            $plotNumber = trim((string)($row['plot_number'] ?? ''));
            $amount     = (float)($row['amount'] ?? 0);
            if ($plotNumber === '') continue;
            self::setElecAmount($feeTypeId, $plotNumber, $amount);
        }

        return $feeTypeId;
    }

    /** Удалить вид взноса (каскадно удаляет платежи) */
    public static function deleteType(int $id): void {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("DELETE FROM fee_payments WHERE fee_type_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $stmt2 = $db->prepare("DELETE FROM fee_types WHERE id = ?");
        $stmt2->bind_param('i', $id);
        $stmt2->execute();
        $stmt2->close();
    }
}
