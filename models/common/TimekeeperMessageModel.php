<?php
namespace App\models\common;

use PDO;

class TimekeeperMessageModel extends BaseModel
{
    private array $columnCache = [];

    private function columnExists(string $table, string $column): bool
    {
        $key = $table . ':' . $column;
        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }

        try {
            $st = $this->pdo->prepare(
                "SELECT COUNT(*)
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND COLUMN_NAME = ?"
            );
            $st->execute([$table, $column]);
            $exists = ((int)$st->fetchColumn() > 0);
            $this->columnCache[$key] = $exists;
            return $exists;
        } catch (\Throwable $e) {
            $this->columnCache[$key] = false;
            return false;
        }
    }

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
}