<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class DashboardModel extends BaseModel
{
    public function counts(int $depotId): array {
        $delayed = (int)$this->pdo->query(
            "SELECT COUNT(*) FROM tracking_monitoring tm
             JOIN sltb_buses b ON b.reg_no=tm.bus_reg_no AND b.sltb_depot_id={$depotId}
             WHERE DATE(tm.snapshot_at)=CURDATE() AND tm.operational_status='Delayed'"
        )->fetchColumn();

        $breaks = (int)$this->pdo->query(
            "SELECT COUNT(*) FROM tracking_monitoring tm
             JOIN sltb_buses b ON b.reg_no=tm.bus_reg_no AND b.sltb_depot_id={$depotId}
             WHERE DATE(tm.snapshot_at)=CURDATE() AND tm.operational_status='Breakdown'"
        )->fetchColumn();

        $compl  = (int)$this->pdo->query(
            "SELECT COUNT(*) FROM complaints c
             JOIN sltb_buses b ON b.reg_no=c.bus_reg_no AND b.sltb_depot_id={$depotId}
             WHERE c.status IN ('Open','In Progress')"
        )->fetchColumn();

        return compact('delayed','breaks','compl');
    }

    public function delayedToday(int $depotId): array {
        $sql = "SELECT tm.*, r.route_no FROM tracking_monitoring tm
                JOIN sltb_buses b ON b.reg_no=tm.bus_reg_no AND b.sltb_depot_id=?
                LEFT JOIN routes r ON r.route_id=tm.route_id
                WHERE DATE(tm.snapshot_at)=CURDATE() AND tm.operational_status='Delayed'
                ORDER BY tm.snapshot_at DESC LIMIT 20";
        $st = $this->pdo->prepare($sql);
        $st->execute([$depotId]);
        return $st->fetchAll();
    }

    public function openComplaints(int $depotId, int $limit=5): array {
        $sql = "SELECT c.* FROM complaints c
                JOIN sltb_buses b ON b.reg_no=c.bus_reg_no AND b.sltb_depot_id=?
                WHERE c.status='Open' ORDER BY c.created_at DESC LIMIT {$limit}";
        $st = $this->pdo->prepare($sql);
        $st->execute([$depotId]);
        return $st->fetchAll();
    }
}
?>