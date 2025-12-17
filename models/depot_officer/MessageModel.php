<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;
use PDO;

class MessageModel extends BaseModel
{
    public function send(int $depotId, array $userIds, string $text): bool {
        $text = trim($text);
        if (!$text || empty($userIds)) return false;

        $in = implode(',', array_fill(0, count($userIds), '?'));
        $chk = $this->pdo->prepare("SELECT user_id FROM users WHERE sltb_depot_id=? AND user_id IN ($in)");
        $chk->execute(array_merge([$depotId], array_map('intval',$userIds)));
        $okIds = $chk->fetchAll(PDO::FETCH_COLUMN);
        if (!$okIds) return false;

        $ins = $this->pdo->prepare(
            "INSERT INTO notifications(user_id,type,message,is_seen,created_at)
             VALUES(?, 'Message', ?, 0, NOW())"
        );
        $this->pdo->beginTransaction();
        foreach ($okIds as $uid) $ins->execute([$uid,$text]);
        return $this->pdo->commit();
    }

    public function recent(int $depotId, int $limit=20): array {
        // FIXED: Combine first_name + last_name for display
        $sql = "SELECT n.*, 
                       CONCAT(u.first_name, ' ', COALESCE(u.last_name, '')) AS full_name 
                FROM notifications n
                JOIN users u ON u.user_id=n.user_id AND u.sltb_depot_id=?
                WHERE n.type IN ('Message','Delay','Timetable')
                ORDER BY n.created_at DESC
                LIMIT {$limit}";
        $st=$this->pdo->prepare($sql);
        $st->execute([$depotId]);
        return $st->fetchAll();
    }
}