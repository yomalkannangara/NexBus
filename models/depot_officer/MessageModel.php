<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;
use PDO;

class MessageModel extends BaseModel
{
    public function send(int $depotId, array $userIds, string $text, string $priority='normal', string $scope='individual', bool $allDepot=false): bool {
        $text = trim($text);
        if (!$text) return false;

        // If allDepot: get all users in this depot; else use provided userIds
        if ($allDepot) {
            $st = $this->pdo->prepare("SELECT user_id FROM users WHERE sltb_depot_id=?");
            $st->execute([$depotId]);
            $okIds = $st->fetchAll(PDO::FETCH_COLUMN);
        } else {
            if (empty($userIds)) return false;
            $in = implode(',', array_fill(0, count($userIds), '?'));
            $chk = $this->pdo->prepare("SELECT user_id FROM users WHERE sltb_depot_id=? AND user_id IN ($in)");
            $chk->execute(array_merge([$depotId], array_map('intval',$userIds)));
            $okIds = $chk->fetchAll(PDO::FETCH_COLUMN);
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
