<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;

class DashboardModel extends BaseModel
{
    /** Return the depot record (tries sltb_depots first, falls back to depots) */
    public function depot(int $depotId): ?array
    {
        if (!$depotId) return null;

        try {
            $st = $this->pdo->prepare(
                "SELECT sltb_depot_id AS id, name, code
                 FROM sltb_depots
                 WHERE sltb_depot_id=?"
            );
            $st->execute([$depotId]);
            if ($row = $st->fetch()) return $row;
        } catch (\Throwable $e) {}

        try {
            $st = $this->pdo->prepare(
                "SELECT depot_id AS id, name, code
                 FROM depots
                 WHERE depot_id=?"
            );
            $st->execute([$depotId]);
            if ($row = $st->fetch()) return $row;
        } catch (\Throwable $e) {}

        return null;
    }

    /** Small helper: does a table have a given column? */
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
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Which depot column should we use on sltb_buses? (cached) */
    private function depotColumn(): string
    {
        static $col = null;
        if ($col !== null) return $col;

        if ($this->colExists('sltb_buses', 'sltb_depot_id')) { $col = 'sltb_depot_id'; return $col; }
        if ($this->colExists('sltb_buses', 'depot_id'))      { $col = 'depot_id';      return $col; }
        $col = ''; // none found
        return $col;
    }

    /** Build the JOIN fragment with the correct depot filter (or none) */
    private function busesJoin(int $depotId): array
    {
        $join   = "JOIN sltb_buses b ON b.reg_no = tm.bus_reg_no";
        $params = [];

        $col = $this->depotColumn();
        if ($col !== '' && $depotId > 0) {
            $join   .= " AND b.$col = :dep";
            $params[':dep'] = $depotId;
        }

        return [$join, $params];
    }

    public function todayStats(int $depotId): array
    {
        // SELECT (spelled correctly) and portable CASE expressions
        $sql = "
        SELECT
            SUM(CASE WHEN tm.operational_status = 'Delayed'   THEN 1 ELSE 0 END) AS delayed_cnt,
            SUM(CASE WHEN tm.operational_status = 'Breakdown' THEN 1 ELSE 0 END) AS breakdown_cnt
        FROM tracking_monitoring tm
        %JOIN%
        WHERE DATE(tm.snapshot_at) = CURDATE()
        ";

        [$join, $params] = $this->busesJoin($depotId);
        $sql = str_replace('%JOIN%', $join, $sql);

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch() ?: [];

        return [
            'delayed' => (int)($row['delayed_cnt'] ?? 0),
            'breaks'  => (int)($row['breakdown_cnt'] ?? 0),
        ];
    }

    public function delayedToday(int $depotId): array
    {
        $sql = "
        SELECT tm.*, r.route_no
        FROM tracking_monitoring tm
        %JOIN%
        LEFT JOIN routes r ON r.route_id = tm.route_id
        WHERE DATE(tm.snapshot_at) = CURDATE()
          AND tm.operational_status = 'Delayed'
        ORDER BY tm.snapshot_at DESC
        LIMIT 50
        ";

        [$join, $params] = $this->busesJoin($depotId);
        $sql = str_replace('%JOIN%', $join, $sql);

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }
}
