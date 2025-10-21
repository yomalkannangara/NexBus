<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;
use PDO;

class DashboardModel extends BaseModel
{
    private function depotId(): int {
        $u = $_SESSION['user'] ?? [];
        return (int)($u['sltb_depot_id'] ?? 0);
    }

    private function depotName(): string {
        $id = $this->depotId();
        if ($id <= 0) return 'My Depot';
        try {
            $st = $this->pdo->prepare("SELECT name FROM sltb_depots WHERE sltb_depot_id=:d");
            $st->execute([':d'=>$id]);
            return (string)($st->fetchColumn() ?: 'My Depot');
        } catch (\Throwable $e) { return 'My Depot'; }
    }

    private function tableExists(string $t): bool {
        try {
            $db = (string)$this->pdo->query("SELECT DATABASE()")->fetchColumn();
            $st = $this->pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema=? AND table_name=?");
            $st->execute([$db, $t]);
            return (bool)$st->fetchColumn();
        } catch (\Throwable $e) { return false; }
    }

    public function stats(): array
    {
        $d = $this->depotId();

        // Total buses assigned today (+ delta vs yesterday)
        $qAssign = "SELECT COUNT(DISTINCT bus_reg_no) FROM sltb_assignments WHERE sltb_depot_id=:d AND assigned_date=:dt";
        $stT = $this->pdo->prepare($qAssign); $stT->execute([':d'=>$d, ':dt'=>date('Y-m-d')]);
        $assignedToday = (int)$stT->fetchColumn();

        $stY = $this->pdo->prepare($qAssign); $stY->execute([':d'=>$d, ':dt'=>date('Y-m-d', strtotime('-1 day'))]);
        $assignedYesterday = (int)$stY->fetchColumn();
        $assignedDelta = $assignedToday - $assignedYesterday;

        // Drivers on duty (today’s assignments)
        $st = $this->pdo->prepare("SELECT COUNT(DISTINCT sltb_driver_id) FROM sltb_assignments WHERE sltb_depot_id=:d AND assigned_date=CURDATE()");
        $st->execute([':d'=>$d]); $driversOnDuty = (int)$st->fetchColumn();

        // Conductors on duty
        $st = $this->pdo->prepare("SELECT COUNT(DISTINCT sltb_conductor_id) FROM sltb_assignments WHERE sltb_depot_id=:d AND assigned_date=CURDATE()");
        $st->execute([':d'=>$d]); $conductorsOnDuty = (int)$st->fetchColumn();

        // Active routes (any timetable for this depot)
        $st = $this->pdo->prepare("
            SELECT COUNT(DISTINCT t.route_id)
            FROM timetables t JOIN sltb_buses b ON b.reg_no=t.bus_reg_no
            WHERE t.operator_type='SLTB' AND b.sltb_depot_id=:d
        ");
        $st->execute([':d'=>$d]); $activeRoutes = (int)$st->fetchColumn();

        // Location updates last hour
        $st = $this->pdo->prepare("SELECT COUNT(*) FROM sltb_buses WHERE sltb_depot_id=:d");
        $st->execute([':d'=>$d]); $totalDepotBuses = max(1, (int)$st->fetchColumn()); // avoid /0

        $st = $this->pdo->prepare("
            SELECT COUNT(DISTINCT tm.bus_reg_no)
            FROM tracking_monitoring tm
            JOIN sltb_buses b ON b.reg_no=tm.bus_reg_no
            WHERE b.sltb_depot_id=:d AND tm.snapshot_at >= NOW() - INTERVAL 1 HOUR
        ");
        $st->execute([':d'=>$d]); $updatedLastHour = (int)$st->fetchColumn();
        $locationPct = round(($updatedLastHour / $totalDepotBuses) * 100);

        // Revenue today (optional table; fallback 0)
        $revenue = 0;
        if ($this->tableExists('sltb_revenue')) {
            $st = $this->pdo->prepare("
                SELECT COALESCE(SUM(amount),0) FROM sltb_revenue r
                JOIN sltb_buses b ON b.reg_no=r.bus_reg_no
                WHERE b.sltb_depot_id=:d AND r.txn_date=CURDATE()
            ");
            $st->execute([':d'=>$d]); $revenue = (int)$st->fetchColumn();
        }

        return [
            'depot_name'          => $this->depotName(),
            'assigned_today'      => $assignedToday,
            'assigned_delta'      => $assignedDelta,
            'drivers_on_duty'     => $driversOnDuty,
            'conductors_on_duty'  => $conductorsOnDuty,
            'active_routes'       => $activeRoutes,
            'location_pct'        => $locationPct,
            'revenue_today'       => $revenue,
        ];
    }
}
