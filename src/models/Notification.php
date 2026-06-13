<?php
namespace models;
use services\DatabaseConnection;

/**
 * Уведомления членам СНТ: события (собрание, голосование) и напоминания (долг).
 */
class Notification
{
    public static function ensureTable(): void
    {
        $db = DatabaseConnection::get();
        $db->query("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(40) NOT NULL DEFAULT 'info',
            title VARCHAR(200) NOT NULL,
            body TEXT,
            link VARCHAR(255) DEFAULT '',
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_read (user_id, is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /** Создать уведомление одному пользователю. */
    public static function create(int $userId, string $type, string $title, string $body = '', string $link = ''): void
    {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, body, link) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('issss', $userId, $type, $title, $body, $link);
        $stmt->execute();
        $stmt->close();
    }

    /** Разослать уведомление всем активным членам (кроме указанного автора). */
    public static function broadcastAll(string $type, string $title, string $body = '', string $link = '', int $exceptUserId = 0): int
    {
        self::ensureTable();
        $db = DatabaseConnection::get();
        $res = $db->query("SELECT id FROM users WHERE status = 'active'");
        $count = 0;
        $ids = [];
        while ($row = $res->fetch_assoc()) {
            if ((int)$row['id'] === $exceptUserId) continue;
            $ids[] = (int)$row['id'];
        }
        $res->free();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, body, link) VALUES (?, ?, ?, ?, ?)");
        foreach ($ids as $uid) {
            $stmt->bind_param('issss', $uid, $type, $title, $body, $link);
            $stmt->execute();
            $count++;
        }
        $stmt->close();
        return $count;
    }

    /** Список уведомлений пользователя (свежие сверху). */
    public static function forUser(int $userId, int $limit = 50): array
    {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ?");
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $stmt->close();
        return $rows;
    }

    /** Число непрочитанных (для бейджа). Безопасно при отсутствии таблицы. */
    public static function unreadCount(int $userId): int
    {
        try {
            $db = DatabaseConnection::get();
            $stmt = $db->prepare("SELECT COUNT(*) AS n FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $n = (int)($stmt->get_result()->fetch_assoc()['n'] ?? 0);
            $stmt->close();
            return $n;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function markAllRead(int $userId): void
    {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }
}
