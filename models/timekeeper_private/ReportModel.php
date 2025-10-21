<?php
namespace App\models\timekeeper_private;

use App\models\common\BaseModel;

class ReportModel extends BaseModel
{
    /**
     * KPI summary for a depot and date range.
     * Safe against schema variants (depot_id vs sltb_depot_id).
     */
    public function kpis(int $depotId, string $from, string $to): array
    {
        if ($depotId <= 0) {
            return [
                'delayed'         => 0,
                'breakdowns'      => 0,
                'avgDelayMin'     => 0.0,
                'speedViolations' => 0,
                'trips'           => 0,
            ];
        }

        // One query with CASE-based aggregates (portable across MariaDB/MySQL)
        $sql = "
        SELECT
            SUM(CASE WHEN tm.operational_status = 'Delayed'   THEN 1 ELSE 0 END) AS delayed,
            SUM(CASE WHEN tm.operational_status = 'Breakdown' THEN 1 ELSE 0 END) AS breakdowns,
            AVG(tm.avg_delay_min)                                                AS avgDelayMin,
            SUM(COALESCE(tm.speed_violations,0))                                 AS speedViolations,
            COUNT(*)                                                             AS trips
        FROM tracking_monitoring tm
        JOIN sltb_buses b
            ON b.reg_no = tm.bus_reg_no
           AND (b.depot_id = :dep OR b.sltb_depot_id = :dep)   -- tolerate both column names
        WHERE tm.snapshot_at BETWEEN CONCAT(:from, ' 00:00:00') AND CONCAT(:to, ' 23:59:59')
        ";

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':dep'  => $depotId,
            ':from' => $from,
            ':to'   => $to,
        ]);
        $row = $st->fetch() ?: [];

        return [
            'delayed'         => (int)($row['delayed'] ?? 0),
            'breakdowns'      => (int)($row['breakdowns'] ?? 0),
            'avgDelayMin'     => (float)($row['avgDelayMin'] ?? 0),
            'speedViolations' => (int)($row['speedViolations'] ?? 0),
            'trips'           => (int)($row['trips'] ?? 0),
        ];
    }

    /**
     * CSV export for the same range.
     */
    public function csv(int $depotId, string $from, string $to): string
    {
        if ($depotId <= 0) {
            // return just the header if depot is unknown
            $out = fopen('php://temp', 'r+');
            fputcsv($out, ['snapshot_at','bus_reg_no','route_no','status','avg_delay_min','speed','heading']);
            rewind($out);
            return stream_get_contents($out);
        }

        $sql = "
        SELECT tm.snapshot_at, tm.bus_reg_no, r.route_no, tm.operational_status,
               tm.avg_delay_min, tm.speed, tm.heading
        FROM tracking_monitoring tm
        JOIN sltb_buses b
            ON b.reg_no = tm.bus_reg_no
           AND (b.depot_id = :dep OR b.sltb_depot_id = :dep)
        LEFT JOIN routes r ON r.route_id = tm.route_id
        WHERE tm.snapshot_at BETWEEN CONCAT(:from, ' 00:00:00') AND CONCAT(:to, ' 23:59:59')
        ORDER BY tm.snapshot_at DESC
        ";

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':dep'  => $depotId,
            ':from' => $from,
            ':to'   => $to,
        ]);

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['snapshot_at','bus_reg_no','route_no','status','avg_delay_min','speed','heading']);
        while ($r = $st->fetch()) {
            fputcsv($out, [
                $r['snapshot_at'] ?? '',
                $r['bus_reg_no'] ?? '',
                $r['route_no'] ?? '',
                $r['operational_status'] ?? '',
                $r['avg_delay_min'] ?? '',
                $r['speed'] ?? '',
                $r['heading'] ?? '',
            ]);
        }
        rewind($out);
        return stream_get_contents($out);
    }
}
