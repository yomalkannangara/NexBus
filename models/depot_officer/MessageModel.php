<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;
use PDO;

class MessageModel extends BaseModel
{
    /**
     * Expand recipient IDs based on scope.
     * scope='individual': use provided userIds as-is.
     * scope='role': userIds are role names (DepotOfficer, Driver, Conductor, etc); expand to all users with that role in depot.
     * scope='route': userIds are route_ids; expand to all drivers/conductors assigned to those routes.
     * scope='bus': userIds are bus_ids; expand to all drivers/conductors assigned to those buses.
     */
    public function expandRecipients(int $depotId, array $userIds, string $scope='individual'): array {
        if (empty($userIds)) return [];

        $okIds = [];

        if ($scope === 'individual') {
            // Validate user IDs belong to this depot
            $in = implode(',', array_fill(0, count($userIds), '?'));
            $st = $this->pdo->prepare("SELECT user_id FROM users WHERE sltb_depot_id=? AND user_id IN ($in)");
            $st->execute(array_merge([$depotId], array_map('intval',$userIds)));
            $okIds = $st->fetchAll(PDO::FETCH_COLUMN);

        } elseif ($scope === 'role') {
            // userIds are role names; expand to all users with that role in depot
            $in = implode(',', array_fill(0, count($userIds), '?'));
            $st = $this->pdo->prepare("SELECT user_id FROM users WHERE sltb_depot_id=? AND role IN ($in)");
            $st->execute(array_merge([$depotId], $userIds));
            $okIds = $st->fetchAll(PDO::FETCH_COLUMN);

        } elseif ($scope === 'route') {
            // userIds are route_ids; expand to drivers/conductors assigned to buses serving those routes
            $routeIds = array_map('intval', $userIds);
            $in = implode(',', array_fill(0, count($routeIds), '?'));
            $st = $this->pdo->prepare(
                "SELECT DISTINCT u.user_id FROM users u
                 JOIN sltb_assignments a ON (a.sltb_driver_id=u.user_id OR a.sltb_conductor_id=u.user_id)
                 JOIN timetables t ON t.bus_reg_no=a.bus_reg_no AND t.operator_type='SLTB'
                 WHERE u.sltb_depot_id=?
                   AND a.sltb_depot_id=?
                   AND a.assigned_date=CURDATE()
                   AND t.route_id IN ($in)"
            );
            $st->execute(array_merge([$depotId, $depotId], $routeIds));
            $okIds = $st->fetchAll(PDO::FETCH_COLUMN);

        } elseif ($scope === 'bus') {
            // userIds are bus registration numbers
            $busRegs = array_values(array_filter(array_map(static fn($v) => trim((string)$v), $userIds)));
            if (!$busRegs) return [];
            $in = implode(',', array_fill(0, count($busRegs), '?'));
            $st = $this->pdo->prepare(
                "SELECT DISTINCT u.user_id FROM users u
                 JOIN sltb_assignments a ON (a.sltb_driver_id=u.user_id OR a.sltb_conductor_id=u.user_id)
                 WHERE u.sltb_depot_id=?
                   AND a.sltb_depot_id=?
                   AND a.assigned_date=CURDATE()
                   AND a.bus_reg_no IN ($in)"
            );
            $st->execute(array_merge([$depotId, $depotId], $busRegs));
            $okIds = $st->fetchAll(PDO::FETCH_COLUMN);
        }

        return array_unique(array_map('intval', $okIds));
    }

    public function send(
        int $depotId,
        array $userIds,
        string $text,
        string $priority='normal',
        string $scope='individual',
        bool $allDepot=false,
        ?int $senderUserId=null,
        ?string $senderRole=null
    ): bool {
        $text = trim($text);
        if (!$text) return false;

        // Expand recipients based on scope
        if ($allDepot) {
            $st = $this->pdo->prepare("SELECT user_id FROM users WHERE sltb_depot_id=?");
            $st->execute([$depotId]);
            $okIds = $st->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $okIds = $this->expandRecipients($depotId, $userIds, $scope);
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
            'source' => 'depot_message',
            'source_user_id' => $senderUserId ? (int)$senderUserId : null,
            'source_role' => $senderRole,
            'source_name' => $senderName,
            'scope' => $scope,
        ];
        $metadataJson = json_encode($metadata, JSON_UNESCAPED_UNICODE);

        $ins = $this->pdo->prepare(
            "INSERT INTO notifications(user_id,type,message,is_seen,priority,metadata,created_at)
             VALUES(?, 'Message', ?, 0, ?, ?, NOW())"
        );

        try {
            $this->pdo->beginTransaction();
            foreach ($okIds as $uid) {
                $ins->execute([$uid, $text, $priority, $metadataJson]);
            }
            return $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return false;
        }
    }

    public function recent(int $depotId, int $userId, int $limit=20, string $filter='all'): array {
                $sql = "SELECT n.*,
                                             CONCAT(ru.first_name, ' ', COALESCE(ru.last_name, '')) AS recipient_name,
                                             COALESCE(
                                                     NULLIF(JSON_UNQUOTE(JSON_EXTRACT(n.metadata, '$.source_name')), ''),
                                                     NULLIF(CONCAT(su.first_name, ' ', COALESCE(su.last_name, '')), ''),
                                                     CASE WHEN n.type='Message' THEN 'Depot Messaging' ELSE 'System Alert' END
                                             ) AS full_name,
                                             JSON_UNQUOTE(JSON_EXTRACT(n.metadata, '$.source_role')) AS source_role
                                FROM notifications n
                                                                JOIN users ru ON ru.user_id=n.user_id AND ru.sltb_depot_id=?
                                LEFT JOIN users su
                                    ON su.user_id = CAST(JSON_UNQUOTE(JSON_EXTRACT(n.metadata, '$.source_user_id')) AS UNSIGNED)
                                WHERE n.type IN ('Message','Delay','Timetable','Alert','Breakdown')
                                    AND n.user_id = ?
                                    AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(n.metadata, '$.archived')), 'false') <> 'true'";
        
        if ($filter === 'unread') {
            $sql .= " AND n.is_seen=0";
        } elseif ($filter === 'alert') {
            $sql .= " AND n.type IN ('Delay','Timetable','Alert','Breakdown')";
        } elseif ($filter === 'message') {
            $sql .= " AND n.type='Message'";
        }
        
        $sql .= " ORDER BY n.created_at DESC LIMIT {$limit}";
        $st=$this->pdo->prepare($sql);
        $st->execute([$depotId, $userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markRead(int $messageId, int $userId): void {
        $st = $this->pdo->prepare("UPDATE notifications SET is_seen=1 WHERE id=? AND user_id=?");
        $st->execute([$messageId, $userId]);
    }

    public function acknowledge(int $messageId, int $userId): void {
        // Store acknowledgement in a custom metadata field or a separate table
        // For now, mark as read + add a note
        $st = $this->pdo->prepare(
            "UPDATE notifications SET is_seen=1, metadata=JSON_SET(COALESCE(metadata,'{}'), '$.acknowledged_by', ?, '$.acknowledged_at', ?) 
             WHERE id=? AND user_id=?"
        );
        $st->execute([$userId, date('Y-m-d H:i:s'), $messageId, $userId]);
    }

    public function escalate(int $messageId, int $userId): void {
        // Mark as escalated in metadata
        $st = $this->pdo->prepare(
            "UPDATE notifications SET metadata=JSON_SET(COALESCE(metadata,'{}'), '$.escalated', true, '$.escalated_by', ?, '$.escalated_at', ?) 
             WHERE id=? AND user_id=?"
        );
        $st->execute([$userId, date('Y-m-d H:i:s'), $messageId, $userId]);
    }

    public function archive(int $messageId, int $userId): void {
        // Mark as archived in metadata
        $st = $this->pdo->prepare(
            "UPDATE notifications SET metadata=JSON_SET(COALESCE(metadata,'{}'), '$.archived', true, '$.archived_by', ?, '$.archived_at', ?) 
             WHERE id=? AND user_id=?"
        );
        $st->execute([$userId, date('Y-m-d H:i:s'), $messageId, $userId]);
    }
}
