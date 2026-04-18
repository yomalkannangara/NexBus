<?php
namespace App\models\common;

use PDO;

/**
 * DirectMessageModel — bidirectional 1:1 chat between timekeepers and depot officers.
 * Auto-creates the direct_messages table on first use.
 */
class DirectMessageModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS direct_messages (
                    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    from_user_id INT UNSIGNED NOT NULL,
                    to_user_id   INT UNSIGNED NOT NULL,
                    message      TEXT         NOT NULL,
                    is_read      TINYINT(1)   NOT NULL DEFAULT 0,
                    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_pair_fwd  (from_user_id, to_user_id, created_at),
                    INDEX idx_pair_rev  (to_user_id, from_user_id, created_at),
                    INDEX idx_to_unread (to_user_id, is_read)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\Throwable $e) {
            // Ignore — table may already exist
        }
    }

    /** Return DepotOfficer/DepotManager user IDs for an SLTB depot. */
    public function depotOfficerIds(int $depotId): array
    {
        if ($depotId <= 0) return [];
        try {
            $st = $this->pdo->prepare(
                "SELECT user_id FROM users
                 WHERE sltb_depot_id = ? AND role IN ('DepotOfficer','DepotManager')
                 ORDER BY role = 'DepotOfficer' DESC, user_id ASC"
            );
            $st->execute([$depotId]);
            return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        } catch (\Throwable $e) { return []; }
    }

    /**
     * Send a direct message. Returns the new message id or null on failure.
     */
    public function send(int $fromId, int $toId, string $message): ?int
    {
        $message = trim($message);
        if (!$message || $fromId <= 0 || $toId <= 0 || $fromId === $toId) return null;
        try {
            $st = $this->pdo->prepare(
                "INSERT INTO direct_messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)"
            );
            $st->execute([$fromId, $toId, $message]);
            $id = (int)$this->pdo->lastInsertId();
            return $id > 0 ? $id : null;
        } catch (\Throwable $e) { return null; }
    }

    /**
     * Send one message from TK to all depot officers (one row per DO).
     * Returns array of inserted IDs.
     */
    public function sendToMultiple(int $fromId, array $toIds, string $message): array
    {
        $message = trim($message);
        if (!$message || $fromId <= 0 || empty($toIds)) return [];
        $ids = [];
        try {
            $st = $this->pdo->prepare(
                "INSERT INTO direct_messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)"
            );
            foreach ($toIds as $toId) {
                $toId = (int)$toId;
                if ($toId <= 0 || $toId === $fromId) continue;
                $st->execute([$fromId, $toId, $message]);
                $id = (int)$this->pdo->lastInsertId();
                if ($id > 0) $ids[] = $id;
            }
        } catch (\Throwable $e) {}
        return $ids;
    }

    /**
     * Get all messages in a thread between two users, oldest first.
     * $sinceId > 0: only messages with id > sinceId.
     */
    public function threadBetween(int $userA, int $userB, int $limit = 150, int $sinceId = 0): array
    {
        if ($userA <= 0 || $userB <= 0) return [];
        $params = [$userA, $userB, $userB, $userA];
        $sql = "
            SELECT dm.id, dm.from_user_id, dm.to_user_id, dm.message, dm.is_read, dm.created_at,
                   u.first_name, u.last_name, u.role
            FROM direct_messages dm
            JOIN users u ON u.user_id = dm.from_user_id
            WHERE (dm.from_user_id = ? AND dm.to_user_id = ?)
               OR (dm.from_user_id = ? AND dm.to_user_id = ?)
        ";
        if ($sinceId > 0) { $sql .= " AND dm.id > ?"; $params[] = $sinceId; }
        $sql .= " ORDER BY dm.created_at ASC, dm.id ASC LIMIT " . min(200, max(1, $limit));
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return []; }
    }

    /**
     * Get all messages between a TK and any of the depot officer user IDs.
     * De-duplicates TK→DO messages that were broadcast (same text, same second).
     */
    public function threadWithDepot(int $tkId, array $doIds, int $limit = 150, int $sinceId = 0): array
    {
        $doIds = array_values(array_unique(array_filter(array_map('intval', $doIds), fn($id) => $id > 0)));
        if ($tkId <= 0 || empty($doIds)) return [];
        $ph = implode(',', array_fill(0, count($doIds), '?'));
        $params = array_merge([$tkId], $doIds, [$tkId], $doIds);
        $sql = "
            SELECT dm.id, dm.from_user_id, dm.to_user_id, dm.message, dm.is_read, dm.created_at,
                   u.first_name, u.last_name, u.role
            FROM direct_messages dm
            JOIN users u ON u.user_id = dm.from_user_id
            WHERE (dm.from_user_id = ? AND dm.to_user_id IN ({$ph}))
               OR (dm.to_user_id = ? AND dm.from_user_id IN ({$ph}))
        ";
        if ($sinceId > 0) { $sql .= " AND dm.id > ?"; $params[] = $sinceId; }
        $sql .= " ORDER BY dm.created_at ASC, dm.id ASC LIMIT " . min(200, max(1, $limit));
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return []; }

        // De-duplicate: TK's broadcast messages (same text, same second) → keep only first row
        $seen = [];
        $deduped = [];
        foreach ($rows as $row) {
            if ((int)$row['from_user_id'] === $tkId) {
                $key = $row['message'] . '|' . $row['created_at'];
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
            }
            $deduped[] = $row;
        }
        return $deduped;
    }

    /**
     * Get all conversation partners for $userId, with last message preview & unread count.
     * Optionally restrict partners to certain roles.
     */
    public function conversationsForUser(int $userId, array $partnerRoles = []): array
    {
        if ($userId <= 0) return [];
        try {
            // Distinct partners
            $st = $this->pdo->prepare("
                SELECT DISTINCT
                    CASE WHEN dm.from_user_id = ? THEN dm.to_user_id ELSE dm.from_user_id END AS partner_id
                FROM direct_messages dm
                WHERE dm.from_user_id = ? OR dm.to_user_id = ?
            ");
            $st->execute([$userId, $userId, $userId]);
            $partnerIds = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
            if (empty($partnerIds)) return [];

            $results = [];
            foreach ($partnerIds as $pid) {
                $stU = $this->pdo->prepare(
                    "SELECT user_id, first_name, last_name, role FROM users WHERE user_id = ? LIMIT 1"
                );
                $stU->execute([$pid]);
                $u = $stU->fetch(PDO::FETCH_ASSOC);
                if (!$u) continue;
                if (!empty($partnerRoles) && !in_array($u['role'] ?? '', $partnerRoles, true)) continue;

                $stL = $this->pdo->prepare("
                    SELECT message, created_at FROM direct_messages
                    WHERE (from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?)
                    ORDER BY created_at DESC, id DESC LIMIT 1
                ");
                $stL->execute([$userId, $pid, $pid, $userId]);
                $last = $stL->fetch(PDO::FETCH_ASSOC);

                $stR = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM direct_messages WHERE from_user_id = ? AND to_user_id = ? AND is_read = 0"
                );
                $stR->execute([$pid, $userId]);
                $unread = (int)$stR->fetchColumn();

                $results[] = [
                    'partner_id'   => $pid,
                    'first_name'   => $u['first_name'] ?? '',
                    'last_name'    => $u['last_name']  ?? '',
                    'role'         => $u['role']        ?? '',
                    'last_message' => $last ? mb_substr((string)$last['message'], 0, 80) : '',
                    'last_time'    => $last ? $last['created_at'] : null,
                    'unread_count' => $unread,
                ];
            }
            usort($results, fn($a, $b) => strcmp((string)($b['last_time'] ?? ''), (string)($a['last_time'] ?? '')));
            return $results;
        } catch (\Throwable $e) { return []; }
    }

    /** Mark all messages sent by $partnerId to $myId as read. */
    public function markReadFrom(int $myId, int $partnerId): void
    {
        if ($myId <= 0 || $partnerId <= 0) return;
        try {
            $st = $this->pdo->prepare(
                "UPDATE direct_messages SET is_read = 1
                 WHERE from_user_id = ? AND to_user_id = ? AND is_read = 0"
            );
            $st->execute([$partnerId, $myId]);
        } catch (\Throwable $e) {}
    }

    /** Mark all messages from any of $partnerIds to $myId as read. */
    public function markReadFromMultiple(int $myId, array $partnerIds): void
    {
        $partnerIds = array_filter(array_map('intval', $partnerIds), fn($id) => $id > 0);
        if ($myId <= 0 || empty($partnerIds)) return;
        try {
            $ph = implode(',', array_fill(0, count($partnerIds), '?'));
            $st = $this->pdo->prepare(
                "UPDATE direct_messages SET is_read = 1
                 WHERE from_user_id IN ({$ph}) AND to_user_id = ? AND is_read = 0"
            );
            $st->execute(array_merge($partnerIds, [$myId]));
        } catch (\Throwable $e) {}
    }

    /** Get unread direct message count for a user. */
    public function unreadCount(int $userId): int
    {
        if ($userId <= 0) return 0;
        try {
            $st = $this->pdo->prepare(
                "SELECT COUNT(*) FROM direct_messages WHERE to_user_id = ? AND is_read = 0"
            );
            $st->execute([$userId]);
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) { return 0; }
    }
}
