<?php
namespace models;
use services\DatabaseConnection;

class User {
    public static function findByEmail(string $email): ?array {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ? AND u.status != 'inactive' LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public static function findById(int $id): ?array {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public static function all(): array {
        $db = DatabaseConnection::get();
        $result = $db->query("SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.full_name");
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function updateRole(int $id, int $roleId): bool {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("UPDATE users SET role_id=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param('ii', $roleId, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function updateStatus(int $id, string $status): bool {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("UPDATE users SET status=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param('si', $status, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function update(int $id, string $fullName, string $phone, string $email, string $address): bool {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("UPDATE users SET full_name=?, phone=?, email=?, address=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param('ssssi', $fullName, $phone, $email, $address, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function updateNamePhone(int $id, string $fullName, string $phone): bool {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("UPDATE users SET full_name=?, phone=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param('ssi', $fullName, $phone, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    public static function roles(): array {
        $db = DatabaseConnection::get();
        $result = $db->query("SELECT * FROM roles ORDER BY id");
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }
}
