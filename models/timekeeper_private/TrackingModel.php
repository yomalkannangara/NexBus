<?php
namespace App\models\timekeeper_private;

use App\models\common\BaseModel;

class TrackingModel extends BaseModel {

    public function tripLogs(int $depotId, string $from, string $to): array {
        // Direct depot_id
        try {
            $sql="SELECT tm.*, r.route_no
                  FROM tracking_monitoring tm
                  LEFT JOIN routes r ON r.route_id=tm.route_id
                  WHERE tm.depot_id=? AND tm.snapshot_at BETWEEN ? AND ?
                  ORDER BY tm.snapshot_at DESC";
            $st=$this->pdo->prepare($sql);
            $st->execute([$depotId, $from.' 00:00:00', $to.' 23:59:59']);
            return $st->fetchAll();
        } catch (\Throwable $e) {}

        // Join private_buses
        try {
            $sql="SELECT tm.*, r.route_no
                  FROM tracking_monitoring tm
                  JOIN private_buses pb ON pb.reg_no=tm.bus_reg_no AND pb.depot_id=?
                  LEFT JOIN routes r ON r.route_id=tm.route_id
                  WHERE tm.snapshot_at BETWEEN ? AND ?
                  ORDER BY tm.snapshot_at DESC";
            $st=$this->pdo->prepare($sql);
            $st->execute([$depotId, $from.' 00:00:00', $to.' 23:59:59']);
            return $st->fetchAll();
        } catch (\Throwable $e) {}

        // Fallback sltb_buses
        $sql="SELECT tm.*, r.route_no
              FROM tracking_monitoring tm
              JOIN sltb_buses b ON b.reg_no=tm.bus_reg_no AND b.sltb_depot_id=?
              LEFT JOIN routes r ON r.route_id=tm.route_id
              WHERE tm.snapshot_at BETWEEN ? AND ?
              ORDER BY tm.snapshot_at DESC";
        $st=$this->pdo->prepare($sql);
        $st->execute([$depotId, $from.' 00:00:00', $to.' 23:59:59']);
        return $st->fetchAll();
    }
}
