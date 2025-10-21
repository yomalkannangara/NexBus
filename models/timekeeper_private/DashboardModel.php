<?php
namespace App\models\timekeeper_private;

class DashboardModel extends BaseModel
{
    public function stats(): array
    {
        $today = date('Y-m-d');

        $assignedToday = (int)$this->scalar(
            "SELECT COUNT(DISTINCT bus_reg_no)
               FROM private_assignments
              WHERE private_operator_id=:op AND assigned_date=:d",
            [':op'=>$this->opId, ':d'=>$today]
        );
        $assignedYesterday = (int)$this->scalar(
            "SELECT COUNT(DISTINCT bus_reg_no)
               FROM private_assignments
              WHERE private_operator_id=:op AND assigned_date=:d",
            [':op'=>$this->opId, ':d'=>date('Y-m-d', strtotime('-1 day'))]
        );

        $driversOnDuty = (int)$this->scalar(
            "SELECT COUNT(DISTINCT private_driver_id)
               FROM private_assignments
              WHERE private_operator_id=:op AND assigned_date=:d",
            [':op'=>$this->opId, ':d'=>$today]
        );
        $conductorsOnDuty = (int)$this->scalar(
            "SELECT COUNT(DISTINCT private_conductor_id)
               FROM private_assignments
              WHERE private_operator_id=:op AND assigned_date=:d",
            [':op'=>$this->opId, ':d'=>$today]
        );

        $activeRoutes = (int)$this->scalar(
            "SELECT COUNT(DISTINCT tt.route_id)
               FROM timetables tt
               JOIN private_buses pb ON pb.reg_no=tt.bus_reg_no AND pb.private_operator_id=:op",
            [':op'=>$this->opId]
        );

        $totalBuses = (int)$this->scalar(
            "SELECT COUNT(*) FROM private_buses WHERE private_operator_id=:op",
            [':op'=>$this->opId]
        ) ?: 1;

        $updatedLastHour = (int)$this->scalar(
            "SELECT COUNT(DISTINCT tm.bus_reg_no)
               FROM tracking_monitoring tm
               JOIN private_buses pb ON pb.reg_no=tm.bus_reg_no AND pb.private_operator_id=:op
              WHERE tm.snapshot_at >= NOW() - INTERVAL 1 HOUR",
            [':op'=>$this->opId]
        );
        $locationPct = (int)round(($updatedLastHour / $totalBuses) * 100);

        // revenue table in final DB is `earnings`
        $revenueToday = (int)$this->scalar(
            "SELECT COALESCE(SUM(e.amount),0)
               FROM earnings e
               JOIN private_buses pb ON pb.reg_no=e.bus_reg_no AND pb.private_operator_id=:op
              WHERE e.date=:d",
            [':op'=>$this->opId, ':d'=>$today]
        );

        return [
            'assigned_today'     => $assignedToday,
            'assigned_delta'     => $assignedToday - $assignedYesterday,
            'drivers_on_duty'    => $driversOnDuty,
            'conductors_on_duty' => $conductorsOnDuty,
            'active_routes'      => $activeRoutes,
            'location_pct'       => $locationPct,
            'revenue_today'      => $revenueToday,
        ];
    }

    private function scalar(string $sql, array $p) {
        $st = $this->pdo->prepare($sql); $st->execute($p); return $st->fetchColumn();
    }
}
