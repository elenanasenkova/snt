<?php
namespace models;
use services\DatabaseConnection;

class Vote {
    public static function active(): array {
        $db = DatabaseConnection::get();
        $result = $db->query("SELECT v.*, (SELECT COUNT(*) FROM votes_participants vp WHERE vp.vote_id = v.id) AS total_votes FROM votes v WHERE v.status = 'active' ORDER BY v.created_at DESC");
        $votes = [];
        while ($row = $result->fetch_assoc()) {
            $voteId = (int)$row['id'];
            $stmt = $db->prepare("SELECT * FROM votes_options WHERE vote_id = ?");
            $stmt->bind_param('i', $voteId);
            $stmt->execute();
            $opts = $stmt->get_result();
            $row['options'] = [];
            while ($opt = $opts->fetch_assoc()) $row['options'][] = $opt;
            $stmt->close();
            $votes[] = $row;
        }
        return $votes;
    }

    public static function all(): array {
        $db = DatabaseConnection::get();
        $result = $db->query("SELECT v.*, (SELECT COUNT(*) FROM votes_participants vp WHERE vp.vote_id = v.id) AS total_votes FROM votes v ORDER BY v.created_at DESC");
        $votes = [];
        while ($row = $result->fetch_assoc()) {
            $voteId = (int)$row['id'];
            $stmt = $db->prepare("SELECT * FROM votes_options WHERE vote_id = ?");
            $stmt->bind_param('i', $voteId);
            $stmt->execute();
            $opts = $stmt->get_result();
            $row['options'] = [];
            while ($opt = $opts->fetch_assoc()) $row['options'][] = $opt;
            $stmt->close();
            $votes[] = $row;
        }
        return $votes;
    }

    public static function find(int $voteId): ?array {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("SELECT * FROM votes WHERE id=? LIMIT 1");
        $stmt->bind_param('i', $voteId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /** Поимённые итоги голосования: кто, какой участок, что выбрал и когда. */
    public static function results(int $voteId): array {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare(
            "SELECT u.full_name, u.address, o.option_text, vp.voted_at
             FROM votes_participants vp
             JOIN users u ON u.id = vp.user_id
             JOIN votes_options o ON o.id = vp.option_id
             WHERE vp.vote_id = ?
             ORDER BY vp.voted_at"
        );
        $stmt->bind_param('i', $voteId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $stmt->close();
        return $rows;
    }

    public static function userVoted(int $voteId, int $userId): bool {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("SELECT id FROM votes_participants WHERE vote_id=? AND user_id=? LIMIT 1");
        $stmt->bind_param('ii', $voteId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public static function castVote(int $voteId, int $userId, int $optionId): bool {
        $db = DatabaseConnection::get();
        if (self::userVoted($voteId, $userId)) return false;
        $check = $db->prepare("SELECT id FROM votes_options WHERE id = ? AND vote_id = ?");
        $check->bind_param('ii', $optionId, $voteId);
        $check->execute();
        if ($check->get_result()->num_rows === 0) { $check->close(); return false; }
        $check->close();
        $stmt = $db->prepare("INSERT INTO votes_participants (vote_id, user_id, option_id) VALUES (?, ?, ?)");
        $stmt->bind_param('iii', $voteId, $userId, $optionId);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) {
            $stmt2 = $db->prepare("UPDATE votes_options SET vote_count = vote_count + 1 WHERE id=?");
            $stmt2->bind_param('i', $optionId);
            $stmt2->execute();
            $stmt2->close();
        }
        return $ok;
    }

    public static function create(string $title, string $description, int $createdBy, array $options): int {
        $db = DatabaseConnection::get();
        $stmt = $db->prepare("INSERT INTO votes (title, description, created_by, status, started_at) VALUES (?, ?, ?, 'active', NOW())");
        $stmt->bind_param('ssi', $title, $description, $createdBy);
        $stmt->execute();
        $id = $db->insert_id;
        $stmt->close();
        foreach ($options as $opt) {
            $stmt2 = $db->prepare("INSERT INTO votes_options (vote_id, option_text) VALUES (?, ?)");
            $stmt2->bind_param('is', $id, $opt);
            $stmt2->execute();
            $stmt2->close();
        }
        return $id;
    }
}
