<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;

class TrackingModel extends BaseModel
{
    /** Does table.column exist? */
    private function colExists(string $table, string $column): bool
    {
        try {
            $db = (string)$this->pdo->query("SELECT DATABASE()")->fetchColumn();
            $st = $this->pdo->prepare(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?"
            );
            $st->execute([$db, $table, $column]);
            return (int)$st->fetchColumn() > 0;
        } catch (\Throwable $e) { return false; }
    }

    /** Which depot column on sltb_buses should we use? */
    private function depotColumn(): string
    {
        static $col = null;
        if ($col !== null) return $col;
        if ($this->colExists('sltb_buses', 'sltb_depot_id')) { $col = 'sltb_depot_id'; return $col; }
        if ($this->colExists('sltb_buses', 'depot_id'))      { $col = 'depot_id';      return $col; }
        return $col = ''; // none
    }

    /** Build JOIN … AND b.<col>=:dep only if that column exists */
    private function busesJoin(int $depotId): array
    {
        $join   = "JOIN sltb_buses b ON b.reg_no = tm.bus_reg_no";
        $params = [];
        $col = $this->depotColumn();
        if ($col !== '' && $depotId > 0) {
            $join .= " AND b.$col = :dep";
            $params[':dep'] = $depotId;
        }
        return [$join, $params];
    }

    public function logs(int $depotId, string $from, string $to): array
    {
        $sql = "
        SELECT tm.snapshot_at, tm.bus_reg_no, r.route_no, tm.operational_status, tm.avg_delay_min, tm.speed, tm.heading
        FROM tracking_monitoring tm
        %JOIN%
        LEFT JOIN routes r ON r.route_id = tm.route_id
        WHERE tm.snapshot_at BETWEEN CONCAT(:from,' 00:00:00') AND CONCAT(:to,' 23:59:59')
        ORDER BY tm.snapshot_at DESC";
        [$join, $params] = $this->busesJoin($depotId);
        $sql = str_replace('%JOIN%', $join, $sql);

        $st = $this->pdo->prepare($sql);
        $st->execute($params + [':from'=>$from, ':to'=>$to]);
        return $st->fetchAll();
    }
}
