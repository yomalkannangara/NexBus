<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class ComplaintModel extends BaseModel
{
    public function listByStatus(int $depotId, string $status): array {
        $sql = "SELECT c.* FROM complaints c
                JOIN sltb_buses b ON b.reg_no=c.bus_reg_no AND b.sltb_depot_id=?
                WHERE c.status=? ORDER BY c.created_at DESC";
        $st=$this->pdo->prepare($sql);
        $st->execute([$depotId,$status]);
        return $st->fetchAll();
    }

    public function listAssignedTo(int $depotId, int $userId): array {
        $sql = "SELECT c.* FROM complaints c
                JOIN sltb_buses b ON b.reg_no=c.bus_reg_no AND b.sltb_depot_id=?
                WHERE c.assigned_to_user_id=? ORDER BY c.created_at DESC";
        $st=$this->pdo->prepare($sql);
        $st->execute([$depotId,$userId]);
        return $st->fetchAll();
    }

    public function take(int $depotId, int $complaintId, int $userId): void {
        $sql = "UPDATE complaints c
                JOIN sltb_buses b ON b.reg_no=c.bus_reg_no AND b.sltb_depot_id=?
                SET c.assigned_to_user_id=? , c.status='In Progress'
                WHERE c.complaint_id=?";
        $st=$this->pdo->prepare($sql);
        $st->execute([$depotId,$userId,$complaintId]);
    }

    public function reply(int $depotId, int $complaintId, string $reply, string $status): void {
        $sql = "UPDATE complaints c
                JOIN sltb_buses b ON b.reg_no=c.bus_reg_no AND b.sltb_depot_id=?
                SET c.reply_text=?, c.status=?,
                    c.resolved_at = CASE WHEN ? IN ('Resolved','Closed') THEN NOW() ELSE c.resolved_at END
                WHERE c.complaint_id=?";
        $st=$this->pdo->prepare($sql);
        $st->execute([$depotId, $reply, $status, $status, $complaintId]);
    }
}
