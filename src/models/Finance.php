<?php
namespace models;
use services\DatabaseConnection;

class Finance {
    public static function expenses(int $year): array {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("
            SELECT f.*, ft.name AS fee_type_name
            FROM finances f
            LEFT JOIN fee_types ft ON ft.id = f.fee_type_id
            WHERE f.type='expense' AND YEAR(f.created_at)=?
            ORDER BY f.created_at DESC
        ");
        $stmt->bind_param('i', $year);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        $stmt->close();
        return $rows;
    }

    public static function summary(int $year): array {
        $db = DatabaseConnection::get();

        // Totals from finances table
        $stmt = $db->prepare("SELECT COALESCE(SUM(CASE WHEN type IN ('income','fee','payment') THEN amount ELSE 0 END),0) AS total_collected, COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END),0) AS total_expenses FROM finances WHERE YEAR(created_at)=?");
        $stmt->bind_param('i', $year);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Sum only paid fee_payments for the year (status='paid' only)
        $stmt2 = $db->prepare("SELECT COALESCE(SUM(fp.paid_amount),0) AS fee_collected FROM fee_payments fp JOIN fee_types ft ON fp.fee_type_id = ft.id WHERE ft.year = ? AND fp.status = 'paid'");
        $stmt2->bind_param('i', $year);
        $stmt2->execute();
        $row2 = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        $row['fee_collected'] = (float)($row2['fee_collected'] ?? 0);

        // Balance = collected fees - expenses
        $totalCollected = $row['fee_collected'];
        $totalExpenses  = (float)($row['total_expenses'] ?? 0);
        $row['balance'] = $totalCollected - $totalExpenses;

        // Expenses breakdown for the template (fields: category, expense_date, amount, description)
        $stmt3 = $db->prepare("SELECT description AS category, DATE(created_at) AS expense_date, amount, '' AS description FROM finances WHERE type = 'expense' AND YEAR(created_at) = ? ORDER BY created_at DESC");
        $stmt3->bind_param('i', $year);
        $stmt3->execute();
        $result3 = $stmt3->get_result();
        $breakdown = [];
        while ($exp = $result3->fetch_assoc()) $breakdown[] = $exp;
        $stmt3->close();
        $row['expenses_breakdown'] = $breakdown;

        return $row;
    }

    /** Расходы, сгруппированные по fee_type_id для вывода суммы трат из каждой категории */
    public static function expensesByFeeType(int $year): array {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("
            SELECT fee_type_id, COALESCE(SUM(amount), 0) AS total_spent
            FROM finances
            WHERE type = 'expense' AND YEAR(created_at) = ?
            GROUP BY fee_type_id
        ");
        $stmt->bind_param('i', $year);
        $stmt->execute();
        $result = $stmt->get_result();
        $map = [];
        while ($row = $result->fetch_assoc()) {
            $key = $row['fee_type_id'] === null ? 'null' : (int)$row['fee_type_id'];
            $map[$key] = (float)$row['total_spent'];
        }
        $stmt->close();
        return $map;
    }
}
