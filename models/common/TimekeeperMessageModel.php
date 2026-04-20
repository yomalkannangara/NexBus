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

    private function metadataCategoryExpr(bool $hasCategory, bool $hasMetadata, string $alias = 'n'): string
    {
        if ($hasCategory) {
            return "COALESCE(NULLIF({$alias}.category, ''), '')";
        }
        if ($hasMetadata) {
            return "COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT({$alias}.metadata, '$.category')), ''), '')";
        }
        return "''";
    }

    private function depotNoticeBaseExpr(bool $hasCategory, bool $hasMetadata, string $alias = 'n'): string
    {
        if (!$hasMetadata) {
            return "({$alias}.message LIKE 'OPERATION UPDATE:%')";
        }

        $sourceExpr = "COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT({$alias}.metadata, '$.source')), ''), '')";
        $scopeExpr = "COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT({$alias}.metadata, '$.scope')), ''), '')";
        $allDepotExpr = "COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$alias}.metadata, '$.all_depot')), 'false') = 'true'";
        $categoryExpr = $this->metadataCategoryExpr($hasCategory, $hasMetadata, $alias);

        return "(
            {$sourceExpr} = 'depot_message'
            AND (
                {$scopeExpr} IN ('role','depot','bus','route')
                OR {$allDepotExpr}
                OR {$categoryExpr} <> ''
                OR {$alias}.message LIKE 'OPERATION UPDATE:%'
            )
        )";
    }

    private function displayTypeExpr(bool $hasCategory, bool $hasMetadata, string $alias = 'n'): string
    {
        $categoryExpr = $this->metadataCategoryExpr($hasCategory, $hasMetadata, $alias);
        $depotNoticeExpr = $this->depotNoticeBaseExpr($hasCategory, $hasMetadata, $alias);

        return "CASE
                    WHEN {$alias}.type <> 'Message' THEN {$alias}.type
                    WHEN NOT {$depotNoticeExpr} THEN {$alias}.type
                    WHEN {$alias}.message LIKE 'OPERATION UPDATE:%'
                         OR {$categoryExpr} IN ('assignment_update', 'assignment_schedule', 'schedule_change', 'poya_schedule') THEN 'Timetable'
                    WHEN {$categoryExpr} = 'breakdown_alert'
                         OR {$alias}.message LIKE 'BREAKDOWN:%' THEN 'Breakdown'
                    ELSE 'Alert'
                END";
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
        $typeExpr = $this->displayTypeExpr($hasCategory, $hasMetadata);

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
                    {$typeExpr} AS type,
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
            $sql .= " AND ({$typeExpr}) = 'Message'";
        } elseif ($filter === 'alert') {
            $sql .= " AND ({$typeExpr}) IN ('Delay','Timetable','Alert','Breakdown')";
        }

        $sql .= " ORDER BY n.created_at DESC LIMIT {$limit}";

        try {
            $st = $this->pdo->prepare($sql);
            $st->execute([':uid' => $userId]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }

        // Deduplicate: same type + message + timestamp can appear multiple times
        // when a broadcast was inserted more than once. Keep the row where
        // is_seen=0 (so unread status is preserved), otherwise keep the latest id.
        $seen = [];
        $deduped = [];
        foreach ($rows as $row) {
            $key = $row['type'] . '|' . $row['message'] . '|' . $row['created_at'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $deduped[] = $row;
            } elseif ((int)($row['is_seen'] ?? 1) === 0) {
                // Replace already-stored duplicate with unread version
                foreach ($deduped as &$d) {
                    $dk = $d['type'] . '|' . $d['message'] . '|' . $d['created_at'];
                    if ($dk === $key && (int)($d['is_seen'] ?? 1) !== 0) {
                        $d = $row;
                        break;
                    }
                }
                unset($d);
            }
        }
        return $deduped;
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

        $sql = "SELECT COUNT(DISTINCT CONCAT(n.type,'|',n.message,'|',n.created_at))
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
                $meta = [];
                $sel = $this->pdo->prepare("SELECT metadata FROM notifications WHERE {$idCol} = ? AND user_id = ? LIMIT 1");
                $sel->execute([$messageId, $userId]);
                $currentMetadata = $sel->fetchColumn();

                if (is_string($currentMetadata) && trim($currentMetadata) !== '') {
                    $decoded = json_decode($currentMetadata, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $meta = $decoded;
                    }
                }

                $meta['acknowledged_by'] = $userId;
                $meta['acknowledged_at'] = date('Y-m-d H:i:s');

                $st = $this->pdo->prepare(
                    "UPDATE notifications
                     SET is_seen = 1,
                         metadata = ?
                     WHERE {$idCol} = ? AND user_id = ?"
                );
                return $st->execute([
                    json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    $messageId,
                    $userId,
                ]);
            } catch (\Throwable $e) {
                return false;
            }
        }
        return $this->markRead($messageId, $userId);
    }

    /**
     * Send a manual message from a timekeeper to relevant DepotOfficer users.
     * SLTB recipients are resolved from the sender's active depot routes.
     * Private recipients are resolved from the sender operator's active routes.
     */
    public function sendToDepotOfficers(int $senderUserId, string $text, string $priority = 'normal'): bool
    {
        $text = trim($text);
        if (!$text || $senderUserId <= 0) return false;

        // Resolve sender info + their depot
        try {
            $st = $this->pdo->prepare(
                "SELECT user_id, first_name, last_name, role, sltb_depot_id, private_operator_id, timekeeper_location
                 FROM users WHERE user_id = ? LIMIT 1"
            );
            $st->execute([$senderUserId]);
            $sender = $st->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return false; }

        if (!$sender) return false;

        $senderName = trim(($sender['first_name'] ?? '') . ' ' . ($sender['last_name'] ?? ''));
        if ($senderName === '') $senderName = 'Timekeeper';
        $senderRole = (string)($sender['role'] ?? 'SLTBTimekeeper');

        $depotId = (int)($sender['sltb_depot_id'] ?? 0);
        $operatorId = (int)($sender['private_operator_id'] ?? 0);

        $routeIds = [];
        try {
            if ($senderRole === 'SLTBTimekeeper' && $depotId > 0) {
                $stRoutes = $this->pdo->prepare(
                    "SELECT DISTINCT t.route_id
                     FROM timetables t
                     JOIN sltb_buses sb ON sb.reg_no = t.bus_reg_no
                     WHERE t.operator_type = 'SLTB'
                       AND sb.sltb_depot_id = ?
                       AND t.route_id IS NOT NULL"
                );
                $stRoutes->execute([$depotId]);
                $routeIds = array_map('intval', $stRoutes->fetchAll(PDO::FETCH_COLUMN));
            } elseif ($senderRole === 'PrivateTimekeeper') {
                $routeIds = $this->privateTimekeeperVisibleRouteIds(
                    (string)($sender['timekeeper_location'] ?? ''),
                    $operatorId
                );
            }
        } catch (\Throwable $e) {
            $routeIds = [];
        }

        $dm = new DirectMessageModel();
        $recipientIds = [];
        foreach ($dm->routeDepotOptions($routeIds, $depotId) as $option) {
            $recipientIds = array_merge($recipientIds, $option['officer_ids'] ?? []);
        }
        $recipientIds = array_values(array_unique(array_filter(array_map('intval', $recipientIds), fn($id) => $id > 0)));

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