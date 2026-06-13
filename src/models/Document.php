<?php
namespace models;
use services\DatabaseConnection;

class Document {
    public static function byCategory(string $category = ''): array {
        $db = DatabaseConnection::get();
        if ($category !== '') {
            $stmt = $db->prepare("SELECT d.*, u.full_name AS uploader_name FROM documents d JOIN users u ON d.uploaded_by = u.id WHERE d.category=? ORDER BY d.created_at DESC");
            $stmt->bind_param('s', $category);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $db->query("SELECT d.*, u.full_name AS uploader_name FROM documents d JOIN users u ON d.uploaded_by = u.id ORDER BY d.created_at DESC");
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    /** Все документы (для админ-архива). */
    public static function all(): array
    {
        return self::byCategory('');
    }

    /** Только публичные документы (для членов). */
    public static function allPublic(): array
    {
        $db = DatabaseConnection::get();
        $result = $db->query("SELECT d.*, u.full_name AS uploader_name FROM documents d JOIN users u ON d.uploaded_by = u.id WHERE d.is_public = 1 ORDER BY d.created_at DESC");
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public static function find(int $id): ?array
    {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public static function create(string $title, string $description, string $category, string $filePath, int $fileSize, string $mimeType, bool $isPublic, int $userId): int
    {
        $db = DatabaseConnection::get();
        $pub = $isPublic ? 1 : 0;
        $stmt = $db->prepare("INSERT INTO documents (title, description, category, file_path, file_size, mime_type, is_public, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssisii', $title, $description, $category, $filePath, $fileSize, $mimeType, $pub, $userId);
        $stmt->execute();
        $id = $db->insert_id;
        $stmt->close();
        return $id;
    }

    public static function delete(int $id): void
    {
        $doc = self::find($id);
        if ($doc && !empty($doc['file_path'])) {
            $fp = BASE_PATH . '/public/uploads/documents/' . basename($doc['file_path']);
            if (is_file($fp)) @unlink($fp);
        }
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("DELETE FROM documents WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
}
