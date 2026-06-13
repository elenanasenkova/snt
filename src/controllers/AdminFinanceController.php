<?php

require_once BASE_PATH . '/src/helpers/csrf.php';

use models\User;
use models\Fee;
use models\Finance;

/**
 * Финансы админ-панели: взносы, оплаты, расходы, импорт электроэнергии,
 * напоминания об оплате и выдача вложений. Выделено из AdminController.
 */
class AdminFinanceController
{
    public static function finances(): void
    {
        requireRole([1, 2, 3]);

        $year = (int)($_GET['year'] ?? date('Y'));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                flash('Ошибка безопасности', 'error');
                redirect('/admin/finances?year=' . $year);
                return;
            }
            $action = $_POST['action'] ?? '';

            if ($action === 'create_fee_type') {
                $amount = (float)$_POST['amount'];
                if ($amount <= 0) {
                    flash('Сумма должна быть больше нуля', 'error');
                    redirect('/admin/finances?year=' . $year . '&tab=fees');
                    return;
                }

                // Выбор участков: 'all' — всем, 'selected' — только отмеченным
                $scope     = ($_POST['plot_scope'] ?? 'all') === 'selected' ? 'selected' : 'all';
                $selPlots  = [];
                if ($scope === 'selected') {
                    $raw = $_POST['plots'] ?? [];
                    if (is_array($raw)) {
                        foreach ($raw as $p) {
                            $p = trim((string)$p);
                            if ($p !== '') $selPlots[] = $p;
                        }
                    }
                    if (empty($selPlots)) {
                        flash('Выберите хотя бы один участок или режим «Все участки»', 'error');
                        redirect('/admin/finances?year=' . $year . '&tab=fees');
                        return;
                    }
                }

                Fee::createType(
                    trim($_POST['name']),
                    $year,
                    $amount,
                    trim($_POST['description'] ?? ''),
                    getCurrentUser()['id'],
                    $scope,
                    $selPlots
                );
                flash(
                    $scope === 'selected'
                        ? 'Вид взноса создан для ' . count($selPlots) . ' участков'
                        : 'Вид взноса создан',
                    'success'
                );
                redirect('/admin/finances?year=' . $year . '&tab=fees');
                return;
            }

            if ($action === 'delete_fee_type') {
                Fee::deleteType((int)$_POST['fee_type_id']);
                flash('Вид взноса удалён', 'success');
                redirect('/admin/finances?year=' . $year . '&tab=' . ($_POST['tab'] ?? 'fees'));
                return;
            }
        }

        $feeTypes         = Fee::typesByYear($year);
        $allPayments      = Fee::paymentsByYear($year);
        $summary          = Finance::summary($year);
        $expenses         = Finance::expenses($year);
        $expensesByFeeType = Finance::expensesByFeeType($year);
        $years            = Fee::availableYears();
        $users       = User::all();

        // Карта участок → имя
        $plotNames = [];
        foreach ($users as $u) {
            $plot = plotFromAddress($u['address'] ?? '');
            if ($plot !== 0) $plotNames[(string)$plot] = $u['full_name'];
        }

        // Платежи сгруппированные по [feeTypeId][plotNumber]
        $payMap = [];
        foreach ($allPayments as $p) {
            $payMap[$p['fee_type_id']][$p['plot_number']] = $p;
        }

        renderTemplate('admin/finances', compact(
            'feeTypes', 'allPayments', 'summary', 'expenses', 'expensesByFeeType',
            'year', 'years', 'plotNames', 'payMap'
        ) + ['flash' => getFlash()]);
    }

    /** AJAX: отметить/снять оплату участка */
    public static function financesPay(): void
    {
        requireRole([1, 2, 3]);
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (!verifyCsrfToken($data['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'CSRF error']);
            return;
        }

        $feeTypeId  = (int)($data['fee_type_id'] ?? 0);
        $plotNumber = trim($data['plot_number'] ?? '');
        $status     = $data['status'] ?? 'paid';   // 'paid' | 'partial' | 'unpaid'
        $paidAmount = (float)($data['paid_amount'] ?? 0);
        $notes      = trim($data['notes'] ?? '');

        if (!$feeTypeId || $plotNumber === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Неверные параметры']);
            return;
        }

        if (!in_array($status, ['paid', 'partial', 'unpaid'], true)) {
            $status = 'paid';
        }

        if ($status === 'unpaid') {
            Fee::upsertPayment($feeTypeId, $plotNumber, 'unpaid', 0, '');
        } else {
            Fee::upsertPayment($feeTypeId, $plotNumber, $status, $paidAmount, $notes);
        }

        echo json_encode(['ok' => true]);
    }

    /** AJAX: задать индивидуальные суммы по участкам для счёта за свет */
    public static function financesElecAmount(): void
    {
        requireRole([1, 2, 3]);
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (!verifyCsrfToken($data['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'CSRF error']);
            return;
        }

        $feeTypeId = (int)($data['fee_type_id'] ?? 0);
        $amounts   = $data['amounts'] ?? [];   // ['1' => 500.5, '2' => 350, ...]

        if (!$feeTypeId || !is_array($amounts)) {
            http_response_code(400);
            echo json_encode(['error' => 'Неверные параметры']);
            return;
        }

        $saved = 0;
        foreach ($amounts as $plotNumber => $amount) {
            $plotNumber = trim((string)$plotNumber);
            $amount     = (float)$amount;
            if ($plotNumber === '' || $amount < 0) continue;
            Fee::setElecAmount($feeTypeId, $plotNumber, $amount);
            $saved++;
        }

        echo json_encode(['ok' => true, 'saved' => $saved]);
    }

    /** POST multipart: создать расход (с необязательным вложением) */
    public static function financesExpense(): void
    {
        requireRole([1, 2, 3]);
        header('Content-Type: application/json; charset=utf-8');

        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'CSRF error']);
            return;
        }

        $description = trim($_POST['description'] ?? '');
        $amount      = (float)($_POST['amount'] ?? 0);
        $feeTypeId   = !empty($_POST['fee_type_id']) ? (int)$_POST['fee_type_id'] : null;

        if ($description === '' || $amount <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Укажите описание и сумму']);
            return;
        }

        // Обработка прикреплённого файла
        $attachPath = null;
        $attachName = null;
        if (!empty($_FILES['attachment']['tmp_name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = BASE_PATH . '/public/uploads/expenses/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $origName = $_FILES['attachment']['name'];
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            // Разрешённые расширения
            $allowed = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','gif','webp','heic','bmp','tiff','odt','ods'];
            if (!in_array($ext, $allowed, true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Недопустимый формат файла']);
                return;
            }

            $maxSize = 20 * 1024 * 1024; // 20 МБ
            if ($_FILES['attachment']['size'] > $maxSize) {
                http_response_code(400);
                echo json_encode(['error' => 'Файл слишком большой (максимум 20 МБ)']);
                return;
            }

            $safeName = preg_replace('/[^a-z0-9_.-]/i', '_', pathinfo($origName, PATHINFO_FILENAME));
            $fileName = date('Ymd_His') . '_' . $safeName . '.' . $ext;
            $destPath = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $destPath)) {
                http_response_code(500);
                echo json_encode(['error' => 'Не удалось сохранить файл']);
                return;
            }

            $attachPath = 'expenses/' . $fileName;
            $attachName = $origName;
        }

        $db     = \services\DatabaseConnection::get();
        $userId = getCurrentUser()['id'];
        $stmt   = $db->prepare("INSERT INTO finances (user_id, type, fee_type_id, amount, description, attachment_path, attachment_name, status) VALUES (?, 'expense', ?, ?, ?, ?, ?, 'paid')");
        $stmt->bind_param('iidsss', $userId, $feeTypeId, $amount, $description, $attachPath, $attachName);
        $stmt->execute();
        $id = $db->insert_id;
        $stmt->close();

        // Получаем название категории для ответа
        $feeTypeName = null;
        if ($feeTypeId) {
            $s2 = $db->prepare("SELECT name FROM fee_types WHERE id=?");
            $s2->bind_param('i', $feeTypeId);
            $s2->execute();
            $r2 = $s2->get_result()->fetch_assoc();
            $s2->close();
            $feeTypeName = $r2['name'] ?? null;
        }

        echo json_encode([
            'ok'              => true,
            'id'              => $id,
            'fee_type_name'   => $feeTypeName,
            'attachment_path' => $attachPath,
            'attachment_name' => $attachName,
        ]);
    }

    /** POST multipart: импорт электроэнергии из xlsx */
    public static function financesElecImport(): void
    {
        requireRole([1, 2, 3]);

        $year = (int)($_POST['year'] ?? date('Y'));

        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            flash('Ошибка безопасности', 'error');
            redirect('/admin/finances?year=' . $year . '&tab=electricity');
            return;
        }

        if (empty($_FILES['xlsx_file']['tmp_name'])) {
            flash('Файл не загружен', 'error');
            redirect('/admin/finances?year=' . $year . '&tab=electricity');
            return;
        }

        $tmpPath = $_FILES['xlsx_file']['tmp_name'];
        $rows    = self::parseXlsx($tmpPath);

        if (empty($rows)) {
            flash('Не удалось прочитать файл Excel — убедитесь, что формат .xlsx', 'error');
            redirect('/admin/finances?year=' . $year . '&tab=electricity');
            return;
        }

        // Название из строки 1 (индекс 0), ячейка A (индекс 0)
        $periodCell = $rows[0][0] ?? '';
        if (preg_match('/период\s+(.+)/iu', $periodCell, $m)) {
            $name = 'Свет ' . trim($m[1]);
        } else {
            $name = 'Свет ' . date('m.Y');
        }

        // Год из названия
        if (preg_match('/(\d{4})/', $name, $ym)) {
            $year = (int)$ym[1];
        }

        // Данные по участкам: строки с индекса 5 (Excel строка 6)
        // Столбец B (индекс 1) = Участок, Столбец N (индекс 13) = Итого к оплате
        $plots = [];
        foreach ($rows as $i => $row) {
            if ($i < 5) continue;
            $plotRaw   = trim((string)($row[1] ?? ''));
            $amountRaw = trim((string)($row[13] ?? ''));
            if (!preg_match('/^\d+$/', $plotRaw)) continue;

            // Русский формат: "17 795,00" или "17\xc2\xa0795,00" → 17795.0
            $amountStr = str_replace([' ', "\xc2\xa0", "\xa0", ','], ['', '', '', '.'], $amountRaw);
            $amount    = (float)$amountStr;

            $plots[] = ['plot_number' => $plotRaw, 'amount' => $amount];
        }

        if (empty($plots)) {
            flash('В файле не найдены данные по участкам (ожидается столбец B — номер участка)', 'error');
            redirect('/admin/finances?year=' . $year . '&tab=electricity');
            return;
        }

        $userId = getCurrentUser()['id'];
        Fee::importElectricity($name, $year, $plots, $userId);

        flash('Импортировано «' . $name . '» — ' . count($plots) . ' участков', 'success');
        redirect('/admin/finances?year=' . $year . '&tab=electricity');
    }

    /**
     * Парсит xlsx-файл через ZipArchive, без внешних зависимостей.
     * Возвращает $rows[rowIdx][colIdx] = value (оба индекса 0-based).
     */
    private static function parseXlsx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) return [];

        // Shared strings (строки хранятся по индексу)
        $sharedStrings = [];
        $ssRaw = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssRaw) {
            $ssRaw = preg_replace('/xmlns(?::[a-z0-9]+)?="[^"]*"/', '', $ssRaw);
            $ss = @simplexml_load_string($ssRaw);
            if ($ss) {
                foreach ($ss->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } else {
                        $text = '';
                        foreach ($si->r as $r) { $text .= (string)$r->t; }
                        $sharedStrings[] = $text;
                    }
                }
            }
        }

        // Лист sheet1
        $sheetRaw = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if (!$sheetRaw) return [];

        $sheetRaw = preg_replace('/xmlns(?::[a-z0-9]+)?="[^"]*"/', '', $sheetRaw);
        $sheet = @simplexml_load_string($sheetRaw);
        if (!$sheet) return [];

        $rows = [];
        foreach ($sheet->sheetData->row as $xmlRow) {
            foreach ($xmlRow->c as $cell) {
                $ref = (string)($cell['r'] ?? '');
                if (!preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) continue;

                // Столбец: A=0, B=1, ..., N=13
                $colStr = $m[1];
                $colIdx = 0;
                for ($ci = 0; $ci < strlen($colStr); $ci++) {
                    $colIdx = $colIdx * 26 + (ord($colStr[$ci]) - 64);
                }
                $colIdx--;                  // 0-based
                $rowIdx = (int)$m[2] - 1;   // 0-based

                $cellType  = (string)($cell['t'] ?? '');
                $cellValue = (string)($cell->v ?? '');

                if ($cellType === 's') {
                    // Shared string
                    $value = $sharedStrings[(int)$cellValue] ?? '';
                } elseif ($cellType === 'inlineStr') {
                    // Inline string: значение в <is><t>
                    $value = (string)($cell->is->t ?? '');
                } elseif ($cellType === 'b') {
                    $value = $cellValue ? 'TRUE' : 'FALSE';
                } else {
                    // Числовые ячейки (t="" или t="n") — raw float-строка
                    $value = $cellValue;
                }

                $rows[$rowIdx][$colIdx] = $value;
            }
        }

        return $rows;
    }

    /** Разослать напоминание об оплате взносов всем активным членам. */
    public static function remindFees(): void
    {
        requireRole([1, 2, 3]);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            flash('Ошибка безопасности', 'error');
            redirect('/admin');
            return;
        }
        $year = (int)date('Y');
        $n = \models\Notification::broadcastAll(
            'reminder',
            'Напоминание об оплате взносов',
            'Пожалуйста, проверьте задолженность по взносам за ' . $year . ' год в разделе «Взносы».',
            '/finances',
            getCurrentUser()['id']
        );
        flash("Напоминание отправлено: $n чел.", 'success');
        redirect('/admin');
    }

    /** GET: скачать вложение расхода (требует авторизации, роли 1, 2 или 3) */
    public static function downloadExpense(): void
    {
        requireRole([1, 2, 3]);

        $filename = basename($_GET['file'] ?? '');

        if ($filename === '') {
            http_response_code(400);
            echo 'Имя файла не указано.';
            return;
        }

        $uploadsDir = realpath(BASE_PATH . '/public/uploads/expenses');
        $path       = realpath($uploadsDir . DIRECTORY_SEPARATOR . $filename);

        // Защита от path traversal: путь должен начинаться с папки uploads/expenses
        if ($path === false || strpos($path, $uploadsDir) !== 0) {
            http_response_code(403);
            echo 'Доступ запрещён.';
            return;
        }

        if (!is_file($path)) {
            http_response_code(404);
            echo 'Файл не найден.';
            return;
        }

        $ext      = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeMap  = [
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'odt'  => 'application/vnd.oasis.opendocument.text',
            'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'heic' => 'image/heic',
            'bmp'  => 'image/bmp',
            'tiff' => 'image/tiff',
        ];
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, no-store');
        readfile($path);
    }
}
