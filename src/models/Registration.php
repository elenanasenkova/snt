<?php
namespace models;
use services\DatabaseConnection;

class Registration {
    public static function create(string $email, string $fullName, string $phone, string $address, string $message): int {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("INSERT INTO registrations (email, full_name, phone, address, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $email, $fullName, $phone, $address, $message);
        $stmt->execute();
        $id = $db->insert_id;
        $stmt->close();
        return $id;
    }

    public static function pending(): array {
        $db = DatabaseConnection::get();
        $result = $db->query("SELECT * FROM registrations WHERE status = 'pending' ORDER BY created_at DESC");
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function approve(int $id, int $adminId): bool {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("UPDATE registrations SET status='approved', reviewed_by=?, reviewed_at=NOW() WHERE id=?");
        $stmt->bind_param('ii', $adminId, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function reject(int $id, int $adminId): bool {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("UPDATE registrations SET status='rejected', reviewed_by=?, reviewed_at=NOW() WHERE id=?");
        $stmt->bind_param('ii', $adminId, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function emailExists(string $email): bool {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE email=? AND status='pending'");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        if ($count > 0) return true;
        $stmt2 = $db->prepare("SELECT COUNT(*) FROM users WHERE email=?");
        $stmt2->bind_param('s', $email);
        $stmt2->execute();
        $stmt2->bind_result($count2);
        $stmt2->fetch();
        $stmt2->close();
        return $count2 > 0;
    }
}
