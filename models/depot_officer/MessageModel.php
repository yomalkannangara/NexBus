<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;
use PDO;

class MessageModel extends BaseModel
{
    private function normalizeRecipientGroup(?string $recipientGroup): ?string
    {
        $recipientGroup = trim((string)$recipientGroup);
        return in_array($recipientGroup, ['SLTBTimekeeper', 'PrivateTimekeeper'], true)
            ? $recipientGroup
            : null;
    }

    private function depotRouteIds(int $depotId): array
    {
        if ($depotId <= 0) return [];

        try {
            $st = $this->pdo->prepare(
                "SELECT DISTINCT t.route_id
                 FROM timetables t
                 JOIN sltb_buses sb ON sb.reg_no = t.bus_reg_no
                 WHERE t.operator_type = 'SLTB'
                   AND sb.sltb_depot_id = ?
                   AND t.route_id IS NOT NULL"
            );
            $st->execute([$depotId]);
            return array_values(array_unique(array_filter(array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)), fn($id) => $id > 0)));
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function sltbDepotIdsForRoutes(array $routeIds): array
    {
        $routeIds = array_values(array_unique(array_filter(array_map('intval', $routeIds), fn($id) => $id > 0)));
        if (empty($routeIds)) return [];

        try {
            $ph = implode(',', array_fill(0, count($routeIds), '?'));
            $st = $this->pdo->prepare(
                "SELECT DISTINCT sb.sltb_depot_id
                 FROM timetables t
                 JOIN sltb_buses sb ON sb.reg_no = t.bus_reg_no
                 WHERE t.operator_type = 'SLTB'
                   AND t.route_id IN ({$ph})
                   AND sb.sltb_depot_id > 0"
            );
            $st->execute($routeIds);
            return array_values(array_unique(array_filter(array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)), fn($id) => $id > 0)));
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function privateOperatorIdsForRoutes(array $routeIds): array
    {
        $routeIds = array_values(array_unique(array_filter(array_map('intval', $routeIds), fn($id) => $id > 0)));
        if (empty($routeIds)) return [];

        try {
            $ph = implode(',', array_fill(0, count($routeIds), '?'));
            $st = $this->pdo->prepare(
                "SELECT DISTINCT pb.private_operator_id
                 FROM timetables t
                 JOIN private_buses pb ON pb.reg_no = t.bus_reg_no
                 WHERE t.route_id IN ({$ph})
                   AND pb.private_operator_id > 0"
            );
            $st->execute($routeIds);
            return array_values(array_unique(array_filter(array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)), fn($id) => $id > 0)));
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function locationScopedPrivateTimekeeperIdsForRoutes(array $routeIds): array
    {
        $routeRows = $this->routeStopRows($routeIds);
        if (empty($routeRows)) {
            return [];
        }

        try {
            $rows = $this->pdo->query(
                "SELECT user_id, timekeeper_location
                 FROM users
                 WHERE role = 'PrivateTimekeeper'
                   AND COALESCE(private_operator_id, 0) = 0"
            )->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            $userId = (int)($row['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            foreach ($routeRows as $routeRow) {
                if ($this->routeContainsLocationValue((string)($routeRow['stops_raw'] ?? '[]'), (string)($row['timekeeper_location'] ?? ''))) {
                    $ids[] = $userId;
                    break;
                }
            }
        }

        return array_values(array_unique(array_filter($ids, fn($id) => $id > 0)));
    }

    private function recipientIdsForRoutes(array $routeIds, ?string $recipientGroup = null): array
    {
        $routeIds = array_values(array_unique(array_filter(array_map('intval', $routeIds), fn($id) => $id > 0)));
        if (empty($routeIds)) return [];

        $recipientGroup = $this->normalizeRecipientGroup($recipientGroup);
        $ids = [];

        if ($recipientGroup !== 'PrivateTimekeeper') {
            $depotIds = $this->sltbDepotIdsForRoutes($routeIds);
            if (!empty($depotIds)) {
                $ph = implode(',', array_fill(0, count($depotIds), '?'));
                $st = $this->pdo->prepare(
                    "SELECT user_id FROM users
                     WHERE role = 'SLTBTimekeeper' AND sltb_depot_id IN ({$ph})"
                );
                $st->execute($depotIds);
                $ids = array_merge($ids, array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)));
            }
        }

        if ($recipientGroup !== 'SLTBTimekeeper') {
            $operatorIds = $this->privateOperatorIdsForRoutes($routeIds);
            if (!empty($operatorIds)) {
                $ph = implode(',', array_fill(0, count($operatorIds), '?'));
                $st = $this->pdo->prepare(
                    "SELECT user_id FROM users
                     WHERE role = 'PrivateTimekeeper' AND private_operator_id IN ({$ph})"
                );
                $st->execute($operatorIds);
                $ids = array_merge($ids, array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)));
            }

            $ids = array_merge($ids, $this->locationScopedPrivateTimekeeperIdsForRoutes($routeIds));
        }

        return array_values(array_unique(array_filter($ids, fn($id) => $id > 0)));
    }

    private function recipientIdsForBuses(array $busIds, ?string $recipientGroup = null): array
    {
        $busIds = array_values(array_unique(array_filter(array_map(static fn($v) => trim((string)$v), $busIds), fn($v) => $v !== '')));
        if (empty($busIds)) return [];

        try {
            $ph = implode(',', array_fill(0, count($busIds), '?'));
            $st = $this->pdo->prepare(
                "SELECT DISTINCT route_id
                 FROM timetables
                 WHERE bus_reg_no IN ({$ph})
                   AND route_id IS NOT NULL"
            );
            $st->execute($busIds);
            $routeIds = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        } catch (\Throwable $e) {
            $routeIds = [];
        }

        return $this->recipientIdsForRoutes($routeIds, $recipientGroup);
    }

    private function allRelevantRecipientIds(int $depotId, ?string $recipientGroup = null): array
    {
        return $this->recipientIdsForRoutes($this->depotRouteIds($depotId), $recipientGroup);
    }

    public function messagingRecipients(int $depotId, ?string $recipientGroup = null): array
    {
        $recipientIds = $this->allRelevantRecipientIds($depotId, $recipientGroup);
        if (empty($recipientIds)) return [];

        $ph = implode(',', array_fill(0, count($recipientIds), '?'));
        $st = $this->pdo->prepare(
            "SELECT user_id,
                    CONCAT(first_name, ' ', COALESCE(last_name, '')) AS full_name,
                    role,
                    email,
                    phone
             FROM users
             WHERE user_id IN ({$ph})
             ORDER BY role, full_name"
        );
        $st->execute($recipientIds);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function availableRolesForMessaging(int $depotId): array
    {
        $roles = array_column($this->messagingRecipients($depotId), 'role');
        $roles = array_values(array_unique(array_filter(array_map('strval', $roles))));
        sort($roles);
        return $roles;
    }

    /**
     * Expand recipient IDs based on scope.
     * scope='individual': use provided userIds as-is.
     * scope='role': userIds are role names (DepotOfficer, Driver, Conductor, etc); expand to all users with that role in depot.
     * scope='route': userIds are route_ids; expands to all SLTBTimekeepers at the depot.
     * scope='bus': userIds are bus_ids; expands to all SLTBTimekeepers at the depot.
     */
    public function expandRecipients(int $depotId, array $userIds, string $scope='individual', ?string $recipientGroup = null): array {
        if (empty($userIds)) return [];

        $okIds = [];
        $recipientGroup = $this->normalizeRecipientGroup($recipientGroup);

        if ($scope === 'individual') {
            $allowedIds = array_map('intval', array_column($this->messagingRecipients($depotId, $recipientGroup), 'user_id'));
            $requestedIds = array_map('intval', $userIds);
            $okIds = array_values(array_intersect($requestedIds, $allowedIds));

        } elseif ($scope === 'role') {
            $allowedRoles = ['SLTBTimekeeper', 'PrivateTimekeeper'];
            $roles = array_values(array_intersect($allowedRoles, array_map(static fn($v) => trim((string)$v), $userIds)));
            if (!empty($roles)) {
                $matching = array_filter(
                    $this->messagingRecipients($depotId),
                    fn($row) => in_array((string)($row['role'] ?? ''), $roles, true)
                );
                $okIds = array_column($matching, 'user_id');
            }

        } elseif ($scope === 'route' || $scope === 'bus') {
            $okIds = $scope === 'route'
                ? $this->recipientIdsForRoutes($userIds, $recipientGroup)
                : $this->recipientIdsForBuses($userIds, $recipientGroup);
        }

        return array_unique(array_map('intval', $okIds));
    }

    private function notificationTypeForSend(string $text, string $scope = 'individual', bool $allDepot = false, ?string $category = null): string
    {
        $text = trim($text);
        $category = trim((string)$category);

        if ($text !== '' && str_starts_with($text, 'OPERATION UPDATE:')) {
            return 'Timetable';
        }

        if (in_array($category, ['assignment_update', 'assignment_schedule', 'schedule_change', 'poya_schedule'], true)) {
            return 'Timetable';
        }

        if ($category === 'breakdown_alert' || stripos($text, 'BREAKDOWN:') === 0) {
            return 'Breakdown';
        }

        if ($category !== '' || $allDepot || in_array($scope, ['role', 'depot', 'bus', 'route'], true)) {
            return 'Alert';
        }

        return 'Message';
    }

    public function send(
        int $depotId,
        array $userIds,
        string $text,
        string $priority='normal',
        string $scope='individual',
        bool $allDepot=false,
        ?int $senderUserId=null,
        ?string $senderRole=null,
        ?string $category=null,
        ?string $recipientGroup=null
    ): bool {
        $text = trim($text);
        if (!$text) return false;

        $notificationType = $this->notificationTypeForSend($text, $scope, $allDepot, $category);

        // Expand recipients based on scope
        if ($allDepot) {
            $okIds = $this->allRelevantRecipientIds($depotId, $recipientGroup);
        } else {
            $okIds = $this->expandRecipients($depotId, $userIds, $scope, $recipientGroup);
        }
        
        if (!$okIds) return false;

        $senderName = null;
        if (!empty($senderUserId)) {
            $st = $this->pdo->prepare("SELECT CONCAT(first_name, ' ', COALESCE(last_name, '')) AS full_name FROM users WHERE user_id=? LIMIT 1");
            $st->execute([(int)$senderUserId]);
            $senderName = trim((string)$st->fetchColumn());
            if ($senderName === '') $senderName = null;
        }

        $metadata = [
            'source'         => 'depot_message',
            'source_user_id' => $senderUserId ? (int)$senderUserId : null,
            'source_role'    => $senderRole,
            'source_name'    => $senderName,
            'scope'          => $scope,
            'all_depot'      => $allDepot,
            'category'       => $category,
            'recipient_group'=> $this->normalizeRecipientGroup($recipientGroup),
        ];
        $metadataJson = json_encode($metadata, JSON_UNESCAPED_UNICODE);

        $hasPriority = $this->columnExists('notifications', 'priority');
        $hasMetadata = $this->columnExists('notifications', 'metadata');
        $hasCategory = $this->columnExists('notifications', 'category');

        $columns = ['user_id', 'type', 'message', 'is_seen'];
        $values  = ['?', '?', '?', '0'];
        if ($hasPriority) { $columns[] = 'priority';  $values[] = '?'; }
        if ($hasMetadata) { $columns[] = 'metadata';  $values[] = '?'; }
        if ($hasCategory) { $columns[] = 'category';  $values[] = '?'; }
        $columns[] = 'created_at';
        $values[]  = 'NOW()';

        $ins = $this->pdo->prepare(
            'INSERT INTO notifications(' . implode(',', $columns) . ') VALUES(' . implode(',', $values) . ')'
        );

        try {
            $this->pdo->beginTransaction();
            foreach ($okIds as $uid) {
                $params = [$uid, $notificationType, $text];
                if ($hasPriority) $params[] = $priority;
                if ($hasMetadata) $params[] = $metadataJson;
                if ($hasCategory) $params[] = $category;
                $ins->execute($params);
            }
            return $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return false;
        }
    }

    public function recent(int $depotId, int $userId, int $limit=20, string $filter='all'): array {
        $limit = max(1, (int)$limit);
        $hasMetadata = $this->columnExists('notifications', 'metadata');

        $hasCategory = $this->columnExists('notifications', 'category');
        $categorySelect = $hasCategory ? 'n.category' : "JSON_UNQUOTE(JSON_EXTRACT(n.metadata, '$.category')) AS category";

        if ($hasMetadata) {
            $sql = "SELECT n.*,
                           {$categorySelect},
                           CONCAT(ru.first_name, ' ', COALESCE(ru.last_name, '')) AS recipient_name,
                           COALESCE(
                               NULLIF(JSON_UNQUOTE(JSON_EXTRACT(n.metadata, '$.source_name')), ''),
                               NULLIF(CONCAT(su.first_name, ' ', COALESCE(su.last_name, '')), ''),
                               CASE WHEN n.type='Message' THEN 'Depot Messaging' ELSE 'System Alert' END
                           ) AS full_name,
                           JSON_UNQUOTE(JSON_EXTRACT(n.metadata, '$.source_role')) AS source_role
                    FROM notifications n
                    JOIN users ru ON ru.user_id = n.user_id
                    LEFT JOIN users su
                        ON su.user_id = CAST(JSON_UNQUOTE(JSON_EXTRACT(n.metadata, '$.source_user_id')) AS UNSIGNED)
                    WHERE n.type IN ('Message','Delay','Timetable','Alert','Breakdown')
                      AND n.user_id = ?
                      AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(n.metadata, '$.archived')), 'false') <> 'true'";
        } else {
            $sql = "SELECT n.*,
                           NULL AS category,
                           CONCAT(ru.first_name, ' ', COALESCE(ru.last_name, '')) AS recipient_name,
                           CASE WHEN n.type='Message' THEN 'Depot Messaging' ELSE 'System Alert' END AS full_name,
                           NULL AS source_role
                    FROM notifications n
                    JOIN users ru ON ru.user_id = n.user_id
                    WHERE n.type IN ('Message','Delay','Timetable','Alert','Breakdown')
                      AND n.user_id = ?";
        }

        if ($filter === 'unread') {
            $sql .= " AND n.is_seen=0";
        } elseif ($filter === 'alert') {
            $sql .= " AND n.type IN ('Delay','Timetable','Alert','Breakdown')";
        } elseif ($filter === 'message') {
            $sql .= " AND n.type='Message'";
        }

        $sql .= " ORDER BY n.id DESC LIMIT {$limit}";
        $st = $this->pdo->prepare($sql);
        $st->execute([$userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markRead(int $messageId, int $userId): void {
        $st = $this->pdo->prepare("UPDATE notifications SET is_seen=1 WHERE id=? AND user_id=?");
        $st->execute([$messageId, $userId]);
    }

    public function acknowledge(int $messageId, int $userId): void {
        if (!$this->columnExists('notifications', 'metadata')) {
            $this->markRead($messageId, $userId);
            return;
        }

        // Store acknowledgement in a custom metadata field or a separate table
        // For now, mark as read + add a note
        $st = $this->pdo->prepare(
            "UPDATE notifications SET is_seen=1, metadata=JSON_SET(COALESCE(metadata,'{}'), '$.acknowledged_by', ?, '$.acknowledged_at', ?) 
             WHERE id=? AND user_id=?"
        );
        $st->execute([$userId, date('Y-m-d H:i:s'), $messageId, $userId]);
    }

    public function escalate(int $messageId, int $userId): void {
        if (!$this->columnExists('notifications', 'metadata')) {
            $this->markRead($messageId, $userId);
            return;
        }

        // Mark as escalated in metadata
        $st = $this->pdo->prepare(
            "UPDATE notifications SET metadata=JSON_SET(COALESCE(metadata,'{}'), '$.escalated', true, '$.escalated_by', ?, '$.escalated_at', ?) 
             WHERE id=? AND user_id=?"
        );
        $st->execute([$userId, date('Y-m-d H:i:s'), $messageId, $userId]);
    }

    public function archive(int $messageId, int $userId): void {
        if (!$this->columnExists('notifications', 'metadata')) {
            $this->markRead($messageId, $userId);
            return;
        }

        // Mark as archived in metadata
        $st = $this->pdo->prepare(
            "UPDATE notifications SET metadata=JSON_SET(COALESCE(metadata,'{}'), '$.archived', true, '$.archived_by', ?, '$.archived_at', ?) 
             WHERE id=? AND user_id=?"
        );
        $st->execute([$userId, date('Y-m-d H:i:s'), $messageId, $userId]);
    }

    /** Public proxy so DepotOfficerModel can check optional columns without duplicating logic */
    public function columnExistsPublic(string $table, string $column): bool
    {
        return $this->columnExists($table, $column);
    }
}
