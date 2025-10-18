<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class TrackingModel extends BaseModel
{
    public function logs(int $depotId, string $from, string $to): array {
        $sql = "SELECT tm.*, r.route_no FROM tracking_monitoring tm
                JOIN sltb_buses b ON b.reg_no=tm.bus_reg_no AND b.sltb_depot_id=?
                LEFT JOIN routes r ON r.route_id=tm.route_id
                WHERE tm.snapshot_at BETWEEN CONCAT(?, ' 00:00:00') AND CONCAT(?, ' 23:59:59')
                ORDER BY tm.snapshot_at DESC";
        $st=$this->pdo->prepare($sql);
        $st->execute([$depotId,$from,$to]);
        return $st->fetchAll();
    }
}
