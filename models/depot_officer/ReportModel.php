<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class ReportModel extends BaseModel
{
    public function kpis(int $depotId, string $from, string $to): array {
        $kpis = ['delayed'=>0,'breakdowns'=>0,'avgDelayMin'=>0.0,'speedViolations'=>0,'trips'=>0];
        $sql = "SELECT 
                    SUM(CASE WHEN operational_status='Delayed' THEN 1 ELSE 0 END) AS delayed,
                    SUM(CASE WHEN operational_status='Breakdown' THEN 1 ELSE 0 END) AS breakdowns,
                    AVG(avg_delay_min) AS avgDelayMin,
                    SUM(speed_violations) AS speedViolations,
                    COUNT(*) AS trips
                FROM tracking_monitoring tm
                JOIN sltb_buses b ON b.reg_no=tm.bus_reg_no AND b.sltb_depot_id=?
                WHERE tm.snapshot_at BETWEEN CONCAT(?, ' 00:00:00') AND CONCAT(?, ' 23:59:59')";
        $st=$this->pdo->prepare($sql);
        $st->execute([$depotId,$from,$to]);
        $row=$st->fetch();
        if ($row) {
            $kpis = [
                'delayed' => (int)($row['delayed'] ?? 0),
                'breakdowns' => (int)($row['breakdowns'] ?? 0),
                'avgDelayMin' => (float)($row['avgDelayMin'] ?? 0),
                'speedViolations' => (int)($row['speedViolations'] ?? 0),
                'trips' => (int)($row['trips'] ?? 0),
            ];
        }
        return $kpis;
    }

    public function csv(int $depotId, string $from, string $to): string {
        $sql = "SELECT tm.*, r.route_no FROM tracking_monitoring tm
                JOIN sltb_buses b ON b.reg_no=tm.bus_reg_no AND b.sltb_depot_id=?
                LEFT JOIN routes r ON r.route_id=tm.route_id
                WHERE tm.snapshot_at BETWEEN CONCAT(?, ' 00:00:00') AND CONCAT(?, ' 23:59:59')
                ORDER BY tm.snapshot_at DESC";
        $st=$this->pdo->prepare($sql);
        $st->execute([$depotId,$from,$to]);
        $rows=$st->fetchAll();

        $out = fopen('php://temp','r+');
        fputcsv($out, ['snapshot_at','bus_reg_no','route_no','status','avg_delay_min','speed','heading']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['snapshot_at'], $r['bus_reg_no'], $r['route_no'] ?? '',
                $r['operational_status'], $r['avg_delay_min'], $r['speed'], $r['heading']
            ]);
        }
        rewind($out);
        return stream_get_contents($out);
    }
}
