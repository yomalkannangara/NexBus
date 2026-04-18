<?php
namespace App\models\common;

use PDO;

class TimekeeperMessageModel extends BaseModel
{
    private function idColumn(): string
    {
        if ($this->columnExists('notifications', 'id')) {
            return 'id';
        }
        if ($this->columnExists('notifications', 'notification_id')) {
            return 'notification_id';
        }
        return 'id';
    }

    private function baseTypeFilter(): string
    {
        return "n.type IN ('Message','Delay','Timetable','Alert','Breakdown')";
    }

    public function recentForUser(int $userId, int $limit = 50, string $filter = 'all'): array
    {
        if ($userId <= 0) {
            return [];
        }

        $limit = max(1, min(200, (int)$limit));
        $idCol = $this->idColumn();
        $hasPriority = $this->columnExists('notifications', 'priority');
        $hasMetadata = $this->columnExists('notifications', 'metadata');
        $hasCategory = $this->columnExists('notifications', 'category');

        $prioritySelect  = $hasPriority ? 'n.priority' : "'normal' AS priority";
        $categorySelect  = $hasCategory ? 'n.category'
            : ($hasMetadata ? "JSON_UNQUOTE(JSON_EXTRACT(n.metadata, '$.category')) AS category" : "NULL AS category");
        $sourceNameExpr  = "CASE WHEN n.type='Message' THEN 'Depot Messaging' ELSE 'System Alert' END";
        $sourceRoleExpr  = 'NULL';
        $archiveClause   = '';

        if ($hasMetadata) {
            $sourceNameExpr = "COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(n.metadata, '$.source_name')), ''), CASE WHEN n.type='Message' THEN 'Depot Messaging' ELSE 'System Alert' END)";
            $sourceRoleExpr = "NULLIF(JSON_UNQUOTE(JSON_EXTRACT(n.metadata, '$.source_role')), '')";
            $archiveClause  = " AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(n.metadata, '$.archived')), 'false') <> 'true'";
        }

        $sql = "SELECT
                    n.{$idCol} AS id,
                    n.user_id,
                    n.type,
                    n.message,
                    n.is_seen,
                    n.created_at,
                    {$prioritySelect},
                    {$categorySelect},
                    {$sourceNameExpr} AS source_name,
                    {$sourceRoleExpr} AS source_role
                FROM notifications n
                WHERE n.user_id = :uid
                  AND {$this->baseTypeFilter()}{$archiveClause}";

        if ($filter === 'unread') {
            $sql .= ' AND n.is_seen = 0';
        } elseif ($filter === 'message') {
            $sql .= " AND n.type = 'Message'";
        } elseif ($filter === 'alert') {
            $sql .= " AND n.type IN ('Delay','Timetable','Alert','Breakdown')";
        }

        $sql .= " ORDER BY n.created_at DESC LIMIT {$limit}";

        try {
            $st = $this->pdo->prepare($sql);
            $st->execute([':uid' => $userId]);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function unreadCount(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $hasMetadata = $this->columnExists('notifications', 'metadata');
        $archiveClause = '';
        if ($hasMetadata) {
            $archiveClause = " AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(n.metadata, '$.archived')), 'false') <> 'true'";
        }

        $sql = "SELECT COUNT(*)
                FROM notifications n
                WHERE n.user_id = :uid
                  AND n.is_seen = 0
                  AND {$this->baseTypeFilter()}{$archiveClause}";

        try {
            $st = $this->pdo->prepare($sql);
            $st->execute([':uid' => $userId]);
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function markRead(int $messageId, int $userId): bool
    {
        if ($messageId <= 0 || $userId <= 0) {
            return false;
        }

        $idCol = $this->idColumn();

        try {
            $st = $this->pdo->prepare("UPDATE notifications SET is_seen = 1 WHERE {$idCol} = ? AND user_id = ?");
            return $st->execute([$messageId, $userId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function markAllRead(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $hasMetadata = $this->columnExists('notifications', 'metadata');
        $archiveClause = '';
        if ($hasMetadata) {
            $archiveClause = " AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.archived')), 'false') <> 'true'";
        }

        $sql = "UPDATE notifications
                SET is_seen = 1
                WHERE user_id = ?
                  AND is_seen = 0
                  AND type IN ('Message','Delay','Timetable','Alert','Breakdown'){$archiveClause}";

        try {
            $st = $this->pdo->prepare($sql);
            return $st->execute([$userId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function acknowledge(int $messageId, int $userId): bool
    {
        if ($messageId <= 0 || $userId <= 0) return false;
        $idCol = $this->idColumn();
        $hasMetadata = $this->columnExists('notifications', 'metadata');

        if ($hasMetadata) {
            try {
                $st = $this->pdo->prepare(
                    "UPDATE notifications SET is_seen=1,
                        metadata=JSON_SET(COALESCE(metadata,'{}'), '$.acknowledged_by', ?, '$.acknowledged_at', ?)
                     WHERE {$idCol}=? AND user_id=?"
                );
                return $st->execute([$userId, date('Y-m-d H:i:s'), $messageId, $userId]);
            } catch (\Throwable $e) {
                return false;
            }
        }
        return $this->markRead($messageId, $userId);
    }

    /**
     * Send a manual message from a timekeeper to all DepotOfficer users at the same depot.
     * Supports both SLTB timekeeper (sltb_depot_id) and private timekeeper (private_operator_id).
     */
    public function sendToDepotOfficers(int $senderUserId, string $text, string $priority = 'normal'): bool
    {
        $text = trim($text);
        if (!$text || $senderUserId <= 0) return false;

        // Resolve sender info + their depot
        try {
            $st = $this->pdo->prepare(
                "SELECT user_id, first_name, last_name, role, sltb_depot_id, private_operator_id
                 FROM users WHERE user_id = ? LIMIT 1"
            );
            $st->execute([$senderUserId]);
            $sender = $st->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return false; }

        if (!$sender) return false;

        $senderName = trim(($sender['first_name'] ?? '') . ' ' . ($sender['last_name'] ?? ''));
        if ($senderName === '') $senderName = 'Timekeeper';
        $senderRole = (string)($sender['role'] ?? 'SLTBTimekeeper');

        // Find recipient DepotOfficers: by sltb_depot_id for SLTB TK
        $depotId = (int)($sender['sltb_depot_id'] ?? 0);
        if ($depotId <= 0) return false;

        try {
            $st = $this->pdo->prepare(
                "SELECT user_id FROM users
                 WHERE sltb_depot_id = ? AND role IN ('DepotOfficer','DepotManager')"
            );
            $st->execute([$depotId]);
            $recipientIds = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        } catch (\Throwable $e) { return false; }

        if (!$recipientIds) return false;

        $hasPriority = $this->columnExists('notifications', 'priority');
        $hasMetadata = $this->columnExists('notifications', 'metadata');
        $hasCategory = $this->columnExists('notifications', 'category');

        $metadata = json_encode([
            'source'         => 'timekeeper_message',
            'source_user_id' => $senderUserId,
            'source_role'    => $senderRole,
            'source_name'    => $senderName,
            'scope'          => 'individual',
            'category'       => null,
        ], JSON_UNESCAPED_UNICODE);

        $columns = ['user_id', 'type', 'message', 'is_seen'];
        $values  = ['?', '?', '?', '0'];
        if ($hasPriority) { $columns[] = 'priority'; $values[] = '?'; }
        if ($hasMetadata) { $columns[] = 'metadata'; $values[] = '?'; }
        if ($hasCategory) { $columns[] = 'category'; $values[] = '?'; }
        $columns[] = 'created_at';
        $values[]  = 'NOW()';

        try {
            $ins = $this->pdo->prepare(
                'INSERT INTO notifications(' . implode(',', $columns) . ') VALUES(' . implode(',', $values) . ')'
            );
            $this->pdo->beginTransaction();
            foreach ($recipientIds as $uid) {
                $params = [$uid, 'Message', $text];
                if ($hasPriority) $params[] = $priority;
                if ($hasMetadata) $params[] = $metadata;
                if ($hasCategory) $params[] = null;
                $ins->execute($params);
            }
            return $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return false;
        }
    }
}