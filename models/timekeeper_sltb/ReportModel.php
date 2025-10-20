<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;

class ReportModel extends BaseModel
{
    private function colExists(string $t, string $c): bool {
        try {
            $db = (string)$this->pdo->query("SELECT DATABASE()")->fetchColumn();
            $st = $this->pdo->prepare(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?"
            );
            $st->execute([$db,$t,$c]);
            return (int)$st->fetchColumn() > 0;
        } catch (\Throwable $e) { return false; }
    }

    private function depotColumn(): string {
        static $col=null; if ($col!==null) return $col;
        if ($this->colExists('sltb_buses','sltb_depot_id')) return $col='sltb_depot_id';
        if ($this->colExists('sltb_buses','depot_id'))      return $col='depot_id';
        return $col='';
    }

    private function busesJoin(int $depotId): array {
        $join   = "JOIN sltb_buses b ON b.reg_no = tm.bus_reg_no";
        $params = [];
        $col = $this->depotColumn();
        if ($col !== '' && $depotId > 0) { $join .= " AND b.$col = :dep"; $params[':dep'] = $depotId; }
        return [$join,$params];
    }

    public function kpis(int $depotId, string $from, string $to): array
    {
        if ($depotId <= 0) {
            return ['delayed'=>0,'breakdowns'=>0,'avgDelayMin'=>0.0,'speedViolations'=>0,'trips'=>0];
        }

        // ✅ SELECT spelled correctly; safe aliases
        $sql = "
        SELECT
            SUM(CASE WHEN tm.operational_status='Delayed'   THEN 1 ELSE 0 END) AS delayed_cnt,
            SUM(CASE WHEN tm.operational_status='Breakdown' THEN 1 ELSE 0 END) AS breakdown_cnt,
            AVG(tm.avg_delay_min)                                                AS avgDelayMin,
            SUM(COALESCE(tm.speed_violations,0))                                 AS speedViolations,
            COUNT(*)                                                             AS trips
        FROM tracking_monitoring tm
        %JOIN%
        WHERE tm.snapshot_at BETWEEN CONCAT(:from,' 00:00:00') AND CONCAT(:to,' 23:59:59')";
        [$join,$params] = $this->busesJoin($depotId);
        $sql = str_replace('%JOIN%', $join, $sql);

        $st = $this->pdo->prepare($sql);
        $st->execute($params + [':from'=>$from, ':to'=>$to]);
        $row = $st->fetch() ?: [];

        return [
            'delayed'         => (int)($row['delayed_cnt'] ?? 0),
            'breakdowns'      => (int)($row['breakdown_cnt'] ?? 0),
            'avgDelayMin'     => (float)($row['avgDelayMin'] ?? 0),
            'speedViolations' => (int)($row['speedViolations'] ?? 0),
            'trips'           => (int)($row['trips'] ?? 0),
        ];
    }

    public function csv(int $depotId, string $from, string $to): string
    {
        $out = fopen('php://temp','r+');
        fputcsv($out, ['snapshot_at','bus_reg_no','route_no','status','avg_delay_min','speed','heading']);

        if ($depotId > 0) {
            $sql = "
            SELECT tm.snapshot_at, tm.bus_reg_no, r.route_no, tm.operational_status, tm.avg_delay_min, tm.speed, tm.heading
            FROM tracking_monitoring tm
            %JOIN%
            LEFT JOIN routes r ON r.route_id=tm.route_id
            WHERE tm.snapshot_at BETWEEN CONCAT(:from,' 00:00:00') AND CONCAT(:to,' 23:59:59')
            ORDER BY tm.snapshot_at DESC";
            [$join,$params] = $this->busesJoin($depotId);
            $sql = str_replace('%JOIN%', $join, $sql);

            $st = $this->pdo->prepare($sql);
            $st->execute($params + [':from'=>$from, ':to'=>$to]);
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
        }
        rewind($out);
        return stream_get_contents($out);
    }
}
