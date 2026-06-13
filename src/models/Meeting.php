<?php
namespace models;
use services\DatabaseConnection;

class Meeting {
    public static function upcoming(): array {
        $db = DatabaseConnection::get();
        $result = $db->query("SELECT * FROM meetings WHERE meeting_date >= CURDATE() ORDER BY meeting_date ASC LIMIT 5");
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function all(): array {
        $db = DatabaseConnection::get();
        $result = $db->query("SELECT * FROM meetings ORDER BY meeting_date DESC");
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function create(string $date, string $topic, string $location): int {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("INSERT INTO meetings (meeting_date, topic, location) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $date, $topic, $location);
        $stmt->execute();
        $id = $db->insert_id;
        $stmt->close();
        return $id;
    }

    public static function delete(int $id): bool {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("DELETE FROM meetings WHERE id=?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
