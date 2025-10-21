<?php
namespace App\models\timekeeper_private;

use App\models\common\BaseModel;

class DashboardModel extends BaseModel {

    public function depot(int $depotId): ?array {
        if (!$depotId) return null;

        // Try private_depots
        try {
            $st = $this->pdo->prepare("SELECT depot_id, name, code FROM private_depots WHERE depot_id=?");
            $st->execute([$depotId]);
            if ($r = $st->fetch()) return $r;
        } catch (\Throwable $e) {}

        // Fallback: depots
        try {
            $st = $this->pdo->prepare("SELECT depot_id, name, code FROM depots WHERE depot_id=?");
            $st->execute([$depotId]);
            if ($r = $st->fetch()) return $r;
        } catch (\Throwable $e) {}

        // Fallback: sltb_depots
        try {
            $st = $this->pdo->prepare("SELECT depot_id, name, code FROM sltb_depots WHERE depot_id=?");
            $st->execute([$depotId]);
            if ($r = $st->fetch()) return $r;
        } catch (\Throwable $e) {}

        return null;
    }

    public function todayStats(int $depotId): array {
        $delayed = 0; $breaks = 0;

        // Prefer direct depot_id column
        try {
            $delayed = (int)$this->pdo->query("SELECT COUNT(*) FROM tracking_monitoring WHERE depot_id={$depotId} AND operational_status='Delayed' AND DATE(snapshot_at)=CURDATE()")->fetchColumn();
            $breaks  = (int)$this->pdo->query("SELECT COUNT(*) FROM tracking_monitoring WHERE depot_id={$depotId} AND operational_status='Breakdown' AND DATE(snapshot_at)=CURDATE()")->fetchColumn();
            return compact('delayed','breaks');
        } catch (\Throwable $e) {}

        // Join private_buses by reg_no
        try {
            $sql = "SELECT
                        SUM(tm.operational_status='Delayed') AS d,
                        SUM(tm.operational_status='Breakdown') AS b
                    FROM tracking_monitoring tm
                    JOIN private_buses pb ON pb.reg_no=tm.bus_reg_no AND pb.depot_id=?";
            $st = $this->pdo->prepare($sql);
            $st->execute([$depotId]);
            $r = $st->fetch() ?: [];
            return ['delayed'=>(int)($r['d']??0),'breaks'=>(int)($r['b']??0)];
        } catch (\Throwable $e) {}

        // Last fallback: join sltb_buses
        $sql = "SELECT
                    SUM(tm.operational_status='Delayed') AS d,
                    SUM(tm.operational_status='Breakdown') AS b
                FROM tracking_monitoring tm
                JOIN sltb_buses b ON b.reg_no=tm.bus_reg_no AND b.sltb_depot_id=?";
        $st = $this->pdo->prepare($sql);
        $st->execute([$depotId]);
        $r = $st->fetch() ?: [];
        return ['delayed'=>(int)($r['d']??0),'breaks'=>(int)($r['b']??0)];
    }

    public function delayedToday(int $depotId): array {
        // Direct filter
        try {
            $st=$this->pdo->prepare("SELECT * FROM tracking_monitoring WHERE depot_id=? AND operational_status='Delayed' AND DATE(snapshot_at)=CURDATE() ORDER BY snapshot_at DESC");
            $st->execute([$depotId]); return $st->fetchAll();
        } catch (\Throwable $e) {}

        // Join private_buses
        try {
            $sql="SELECT tm.* FROM tracking_monitoring tm
                  JOIN private_buses pb ON pb.reg_no=tm.bus_reg_no AND pb.depot_id=?
                  WHERE tm.operational_status='Delayed' AND DATE(tm.snapshot_at)=CURDATE()
                  ORDER BY tm.snapshot_at DESC";
            $st=$this->pdo->prepare($sql); $st->execute([$depotId]); return $st->fetchAll();
        } catch (\Throwable $e) {}

        // Fallback sltb_buses
        $sql="SELECT tm.* FROM tracking_monitoring tm
              JOIN sltb_buses b ON b.reg_no=tm.bus_reg_no AND b.sltb_depot_id=?
              WHERE tm.operational_status='Delayed' AND DATE(tm.snapshot_at)=CURDATE()
              ORDER BY tm.snapshot_at DESC";
        $st=$this->pdo->prepare($sql); $st->execute([$depotId]); return $st->fetchAll();
    }
}
