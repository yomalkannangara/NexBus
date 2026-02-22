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
            // userIds are route_ids; expand to drivers/conductors assigned to those routes
            $routeIds = array_map('intval', $userIds);
            $in = implode(',', array_fill(0, count($routeIds), '?'));
            $st = $this->pdo->prepare(
                "SELECT DISTINCT u.user_id FROM users u
                 JOIN sltb_assignments a ON (a.sltb_driver_id=u.user_id OR a.sltb_conductor_id=u.user_id)
                 WHERE u.sltb_depot_id=? AND a.route_id IN ($in)"
            );
            $st->execute(array_merge([$depotId], $routeIds));
            $okIds = $st->fetchAll(PDO::FETCH_COLUMN);

        } elseif ($scope === 'bus') {
            // userIds are bus_ids; expand to drivers/conductors assigned to those buses
            $busIds = array_map('intval', $userIds);
            $in = implode(',', array_fill(0, count($busIds), '?'));
            $st = $this->pdo->prepare(
                "SELECT DISTINCT u.user_id FROM users u
                 JOIN sltb_assignments a ON (a.sltb_driver_id=u.user_id OR a.sltb_conductor_id=u.user_id)
                 WHERE u.sltb_depot_id=? AND a.bus_id IN ($in)"
            );
            $st->execute(array_merge([$depotId], $busIds));
            $okIds = $st->fetchAll(PDO::FETCH_COLUMN);
        }

        return array_unique(array_map('intval', $okIds));
    }

    public function send(int $depotId, array $userIds, string $text, string $priority='normal', string $scope='individual', bool $allDepot=false): bool {
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

        $ins = $this->pdo->prepare(
            "INSERT INTO notifications(user_id,type,message,is_seen,created_at)
             VALUES(?, 'Message', ?, 0, NOW())"
        );
        $this->pdo->beginTransaction();
        foreach ($okIds as $uid) $ins->execute([$uid,$text]);
        return $this->pdo->commit();
    }

    public function recent(int $depotId, int $limit=20, string $filter='all'): array {
        $sql = "SELECT n.*, 
                       CONCAT(u.first_name, ' ', COALESCE(u.last_name, '')) AS full_name 
                FROM notifications n
                JOIN users u ON u.user_id=n.user_id AND u.sltb_depot_id=?
                WHERE n.type IN ('Message','Delay','Timetable')";
        
        if ($filter === 'unread') {
            $sql .= " AND n.is_seen=0";
        } elseif ($filter === 'alert') {
            $sql .= " AND n.type IN ('Delay','Timetable')";
        } elseif ($filter === 'message') {
            $sql .= " AND n.type='Message'";
        }
        
        $sql .= " ORDER BY n.created_at DESC LIMIT {$limit}";
        $st=$this->pdo->prepare($sql);
        $st->execute([$depotId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markRead(int $messageId, int $userId): void {
        $st = $this->pdo->prepare("UPDATE notifications SET is_seen=1 WHERE id=? AND user_id=?");
        $st->execute([$messageId, $userId]);
    }
}
