<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class DashboardModel extends BaseModel
{
    // Inherits parent::__construct() which sets $this->pdo

    /** Cards: delayed / breakdowns today (from tracking_monitoring) */
    public function counts(int $depotId): array
    {
        try {
            $sql = "SELECT
                        SUM(CASE WHEN tm.operational_status='Delayed'   THEN 1 ELSE 0 END) AS delayed,
                        SUM(CASE WHEN tm.operational_status='Breakdown' THEN 1 ELSE 0 END) AS breaks
                    FROM tracking_monitoring tm
                    JOIN sltb_buses b ON b.reg_no = tm.bus_reg_no AND b.sltb_depot_id = ?
                    WHERE tm.track_id IN (
                        SELECT MAX(t2.track_id)
                        FROM tracking_monitoring t2
                        JOIN sltb_buses b2 ON b2.reg_no = t2.bus_reg_no AND b2.sltb_depot_id = ?
                        GROUP BY t2.bus_reg_no
                    )";
            $st = $this->pdo->prepare($sql);
            $st->execute([$depotId, $depotId]);
            $row = $st->fetch();
            return [
                'delayed' => (int)($row['delayed'] ?? 0),
                'breaks'  => (int)($row['breaks']  ?? 0),
            ];
        } catch (\Throwable $e) {
            return ['delayed' => 0, 'breaks' => 0];
        }
    }

    /** Latest delayed buses (latest snapshot per bus, limit 20) */
    public function delayedToday(int $depotId): array
    {
        try {
            $sql = "SELECT tm.*, r.route_no
                    FROM tracking_monitoring tm
                    JOIN sltb_buses b ON b.reg_no = tm.bus_reg_no AND b.sltb_depot_id = ?
                    LEFT JOIN routes r ON r.route_id = tm.route_id
                    WHERE tm.operational_status = 'Delayed'
                      AND tm.track_id IN (
                          SELECT MAX(t2.track_id)
                          FROM tracking_monitoring t2
                          JOIN sltb_buses b2 ON b2.reg_no = t2.bus_reg_no AND b2.sltb_depot_id = ?
                          GROUP BY t2.bus_reg_no
                      )
                    ORDER BY tm.snapshot_at DESC
                    LIMIT 20";
            $st = $this->pdo->prepare($sql);
            $st->execute([$depotId, $depotId]);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Six KPI stats for the dashboard cards */
    public function stats(int $depotId): array
    {
        $zero = [
            'activeBuses'      => 0,
            'maintBuses'       => 0,
            'driversOnDuty'    => 0,
            'conductorsOnDuty' => 0,
            'tripsCompleted'   => 0,
            'delayedTrips'     => 0,
        ];
        try {
            $cnt = function (string $sql, array $p = []): int {
                $st = $this->pdo->prepare($sql);
                $st->execute($p);
                return (int)($st->fetchColumn() ?? 0);
            };
            $d = $depotId;
            return [
                // Buses assigned today (by depot officer) — meaningful even before timekeepers act
                'activeBuses'      => $cnt("SELECT COUNT(DISTINCT bus_reg_no) FROM sltb_assignments WHERE sltb_depot_id=? AND assigned_date=CURDATE()", [$d]),
                'maintBuses'       => $cnt("SELECT COUNT(*) FROM sltb_buses WHERE sltb_depot_id=? AND status='Maintenance'", [$d]),
                'driversOnDuty'    => $cnt("SELECT COUNT(DISTINCT sltb_driver_id) FROM sltb_assignments WHERE sltb_depot_id=? AND assigned_date=CURDATE()", [$d]),
                'conductorsOnDuty' => $cnt("SELECT COUNT(DISTINCT sltb_conductor_id) FROM sltb_assignments WHERE sltb_depot_id=? AND assigned_date=CURDATE()", [$d]),
                'tripsCompleted'   => $cnt("SELECT COUNT(*) FROM sltb_trips WHERE sltb_depot_id=? AND trip_date=CURDATE() AND status='Completed'", [$d]),
                'delayedTrips'     => $cnt("SELECT COUNT(DISTINCT tm.bus_reg_no) FROM tracking_monitoring tm JOIN sltb_buses b ON tm.bus_reg_no=b.reg_no AND b.sltb_depot_id=? WHERE tm.operational_status='Delayed' AND tm.track_id IN (SELECT MAX(t2.track_id) FROM tracking_monitoring t2 JOIN sltb_buses b2 ON b2.reg_no=t2.bus_reg_no AND b2.sltb_depot_id=? GROUP BY t2.bus_reg_no)", [$d, $d]),
            ];
        } catch (\Throwable $e) {
            return $zero;
        }
    }
}


    /** Cards: delayed / breakdowns today */
    public function counts(int $depotId): array
    {
        $today = date('Y-m-d');

        $tracking = array_filter($this->seedTracking(), function ($r) use ($depotId, $today) {
            return (int)$r['sltb_depot_id'] === (int)$depotId
                && substr($r['snapshot_at'], 0, 10) === $today;
        });

        $delayed = 0;
        $breaks  = 0;
        foreach ($tracking as $r) {
            if (($r['operational_status'] ?? '') === 'Delayed')   $delayed++;
            if (($r['operational_status'] ?? '') === 'Breakdown') $breaks++;
        }

        return compact('delayed','breaks');
    }

    /** Latest delayed records today (limit 20), includes route_no like your JOIN did */
    public function delayedToday(int $depotId): array
    {
        $today = date('Y-m-d');

        $rows = array_values(array_filter($this->seedTracking(), function ($r) use ($depotId, $today) {
            return (int)$r['sltb_depot_id'] === (int)$depotId
                && substr($r['snapshot_at'], 0, 10) === $today
                && ($r['operational_status'] ?? '') === 'Delayed';
        }));

        usort($rows, function ($a, $b) {
            return strcmp($b['snapshot_at'], $a['snapshot_at']); // desc
        });

        return array_slice($rows, 0, 20);
    }

    /**
     * Six real-time KPI stats for the dashboard cards.
     * Uses $GLOBALS['db'] directly because the constructor is overridden (dummy-data mode).
     */
    public function stats(int $depotId): array
    {
        $zero = [
            'activeBuses'      => 0,
            'maintBuses'       => 0,
            'driversOnDuty'    => 0,
            'conductorsOnDuty' => 0,
            'tripsCompleted'   => 0,
            'delayedTrips'     => 0,
        ];
        if (!isset($GLOBALS['db'])) return $zero;
        try {
            $pdo = $GLOBALS['db'];
            $cnt = function (string $sql, array $p = []) use ($pdo): int {
                $st = $pdo->prepare($sql);
                $st->execute($p);
                return (int) ($st->fetchColumn() ?? 0);
            };
            $d = $depotId;
            return [
                'activeBuses'      => $cnt("SELECT COUNT(DISTINCT bus_reg_no) FROM sltb_trips WHERE sltb_depot_id=? AND trip_date=CURDATE() AND status IN ('InProgress','Completed')", [$d]),
                'maintBuses'       => $cnt("SELECT COUNT(*) FROM sltb_buses WHERE sltb_depot_id=? AND status='Maintenance'", [$d]),
                'driversOnDuty'    => $cnt("SELECT COUNT(DISTINCT sltb_driver_id) FROM sltb_assignments WHERE sltb_depot_id=? AND assigned_date=CURDATE()", [$d]),
                'conductorsOnDuty' => $cnt("SELECT COUNT(DISTINCT sltb_conductor_id) FROM sltb_assignments WHERE sltb_depot_id=? AND assigned_date=CURDATE()", [$d]),
                'tripsCompleted'   => $cnt("SELECT COUNT(*) FROM sltb_trips WHERE sltb_depot_id=? AND trip_date=CURDATE() AND status='Completed'", [$d]),
                'delayedTrips'     => $cnt("SELECT COUNT(DISTINCT tm.bus_reg_no) FROM tracking_monitoring tm JOIN sltb_buses b ON tm.bus_reg_no=b.reg_no WHERE b.sltb_depot_id=? AND tm.operational_status='Delayed' AND DATE(tm.snapshot_at)=CURDATE()", [$d]),
            ];
        } catch (\Throwable $e) {
            return $zero;
        }
    }

    /** Fake live tracking feed (what tracking_monitoring JOIN routes would have returned) */
    private function seedTracking(): array
    {
        $today = date('Y-m-d');
        $yday  = date('Y-m-d', strtotime('-1 day'));

        return [
            // Depot 1 - today
            ['snapshot_at'=>"$today 08:10:05",'bus_reg_no'=>'NA-1234','sltb_depot_id'=>1,'route_no'=>'138','route_id'=>1,'operational_status'=>'Delayed',   'avg_delay_min'=>5,'speed'=>32,'heading'=>110],
            ['snapshot_at'=>"$today 08:08:12",'bus_reg_no'=>'NC-4567','sltb_depot_id'=>1,'route_no'=>'100','route_id'=>2,'operational_status'=>'OnTime',    'avg_delay_min'=>0,'speed'=>41,'heading'=>90],
            ['snapshot_at'=>"$today 07:55:30",'bus_reg_no'=>'NB-2222','sltb_depot_id'=>1,'route_no'=>'199','route_id'=>3,'operational_status'=>'Breakdown','avg_delay_min'=>0,'speed'=>0, 'heading'=>0],
            ['snapshot_at'=>"$today 09:02:44",'bus_reg_no'=>'ND-8901','sltb_depot_id'=>1,'route_no'=>'100','route_id'=>2,'operational_status'=>'Delayed',   'avg_delay_min'=>3,'speed'=>27,'heading'=>140],
            // Depot 1 - yesterday
            ['snapshot_at'=>"$yday  10:12:00",'bus_reg_no'=>'NA-1234','sltb_depot_id'=>1,'route_no'=>'138','route_id'=>1,'operational_status'=>'OnTime',   'avg_delay_min'=>0,'speed'=>36,'heading'=>70],
            // Depot 2 - today
            ['snapshot_at'=>"$today 07:40:00",'bus_reg_no'=>'QE-1111','sltb_depot_id'=>2,'route_no'=>'17', 'route_id'=>4,'operational_status'=>'Delayed',   'avg_delay_min'=>2,'speed'=>30,'heading'=>200],
            ['snapshot_at'=>"$today 08:25:19",'bus_reg_no'=>'QF-2222','sltb_depot_id'=>2,'route_no'=>'17', 'route_id'=>4,'operational_status'=>'OnTime',    'avg_delay_min'=>0,'speed'=>38,'heading'=>215],
        ];
    }
}

