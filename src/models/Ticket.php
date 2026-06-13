<?php
namespace models;
use services\DatabaseConnection;

/**
 * Обращения членов СНТ в правление (тикеты) и переписка по ним.
 * Статусы: new (новое), in_progress (в работе), answered (отвечено), closed (закрыто).
 */
class Ticket
{
    public const STATUSES = ['new', 'in_progress', 'answered', 'closed'];

    public const STATUS_LABELS = [
        'new'         => 'Новое',
        'in_progress' => 'В работе',
        'answered'    => 'Отвечено',
        'closed'      => 'Закрыто',
    ];

    /** Создаёт таблицы, если их ещё нет (ленивая инициализация). */
    public static function ensureTable(): void
    {
        $db = DatabaseConnection::get();
        $db->query("CREATE TABLE IF NOT EXISTS tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            subject VARCHAR(200) NOT NULL,
            body TEXT NOT NULL,
            status ENUM('new','in_progress','answered','closed') NOT NULL DEFAULT 'new',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS ticket_replies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            user_id INT NOT NULL,
            body TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ticket (ticket_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /** Создать обращение. Возвращает id нового тикета. */
    public static function create(int $userId, string $subject, string $body): int
    {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("INSERT INTO tickets (user_id, subject, body) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $userId, $subject, $body);
        $stmt->execute();
        $id = $db->insert_id;
        $stmt->close();
        return $id;
    }

    /** Список обращений одного пользователя (свежие сверху). */
    public static function forUser(int $userId): array
    {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY updated_at DESC, id DESC");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $stmt->close();
        return $rows;
    }

    /** Все обращения с именем автора (для правления). Опциональный фильтр по статусу. */
    public static function all(string $status = ''): array
    {
        $db = DatabaseConnection::get();
        $sql = "SELECT t.*, u.full_name AS author_name
                FROM tickets t
                LEFT JOIN users u ON u.id = t.user_id";
        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $sql .= " WHERE t.status = ?";
        }
        $sql .= " ORDER BY t.updated_at DESC, t.id DESC";
        $stmt = $db->prepare($sql);
        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $stmt->bind_param('s', $status);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $stmt->close();
        return $rows;
    }

    /** Один тикет с именем автора, либо null. */
    public static function find(int $id): ?array
    {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("SELECT t.*, u.full_name AS author_name
                              FROM tickets t
                              LEFT JOIN users u ON u.id = t.user_id
                              WHERE t.id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /** Переписка по тикету (старые сверху), с именами авторов. */
    public static function replies(int $ticketId): array
    {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("SELECT r.*, u.full_name AS author_name, u.role_id AS author_role
                              FROM ticket_replies r
                              LEFT JOIN users u ON u.id = r.user_id
                              WHERE r.ticket_id = ?
                              ORDER BY r.created_at ASC, r.id ASC");
        $stmt->bind_param('i', $ticketId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $stmt->close();
        return $rows;
    }

    /** Добавить ответ. $touchStatus — новый статус тикета (или '' — не менять). */
    public static function addReply(int $ticketId, int $userId, string $body, string $touchStatus = ''): void
    {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("INSERT INTO ticket_replies (ticket_id, user_id, body) VALUES (?, ?, ?)");
        $stmt->bind_param('iis', $ticketId, $userId, $body);
        $stmt->execute();
        $stmt->close();

        if ($touchStatus !== '' && in_array($touchStatus, self::STATUSES, true)) {
            self::setStatus($ticketId, $touchStatus);
        } else {
            // Просто обновим updated_at
            $db->query("UPDATE tickets SET updated_at = CURRENT_TIMESTAMP WHERE id = " . (int)$ticketId);
        }
    }

    /** Сменить статус тикета. */
    public static function setStatus(int $ticketId, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) return;
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("UPDATE tickets SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $ticketId);
        $stmt->execute();
        $stmt->close();
    }

    /** Число открытых обращений (для бейджа правления). Безопасно при отсутствии таблицы. */
    public static function openCount(): int
    {
        try {
            $db = DatabaseConnection::get();
            $res = $db->query("SELECT COUNT(*) AS n FROM tickets WHERE status IN ('new','in_progress')");
            return (int)($res->fetch_assoc()['n'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
