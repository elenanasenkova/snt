<?php
namespace models;
use services\DatabaseConnection;

class Announcement {

    public static function all(int $page = 1, int $perPage = 20): array {
        $db = DatabaseConnection::get();
        $offset = ($page - 1) * $perPage;
        $stmt = $db->prepare(
            "SELECT a.*, u.full_name AS author_name, u.address AS author_address
               FROM announcements a
               JOIN users u ON a.author_id = u.id
              WHERE a.status = 'published'
           ORDER BY a.is_pinned DESC, a.created_at DESC
              LIMIT ? OFFSET ?"
        );
        $stmt->bind_param('ii', $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        $stmt->close();
        return $rows;
    }

    public static function allByCategory(string $category, int $limit = 20): array {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare(
            "SELECT a.*, u.full_name AS author_name, u.address AS author_address
               FROM announcements a
               JOIN users u ON a.author_id = u.id
              WHERE a.status = 'published' AND a.category = ?
           ORDER BY a.created_at DESC
              LIMIT ?"
        );
        $stmt->bind_param('si', $category, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        $stmt->close();
        return $rows;
    }

    public static function count(): int {
        $db = DatabaseConnection::get();
        $result = $db->query("SELECT COUNT(*) FROM announcements WHERE status='published'");
        return (int)$result->fetch_row()[0];
    }

    public static function create(int $authorId, string $title, string $content, string $category): int {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare(
            "INSERT INTO announcements (author_id, title, content, category, status)
             VALUES (?, ?, ?, ?, 'published')"
        );
        $stmt->bind_param('isss', $authorId, $title, $content, $category);
        $stmt->execute();
        $id = $db->insert_id;
        $stmt->close();
        return $id;
    }

    public static function delete(int $id): bool {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("DELETE FROM announcements WHERE id=?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /** Инкремент счётчика просмотров */
    public static function incrementViews(int $id): void {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("UPDATE announcements SET views_count = views_count + 1 WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
}
