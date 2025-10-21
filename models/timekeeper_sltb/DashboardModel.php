<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;

class DashboardModel extends BaseModel
{
    /** Get depot id from session (strict) */
    private function myDepotId(): int
    {
        return (int)($_SESSION['user']['sltb_depot_id'] ?? 0);
    }

    /** Depot details (uses session depot id if not provided) */
    public function depot(?int $depotId = null): ?array
    {
        $dep = $depotId ?? $this->myDepotId();
        if ($dep <= 0) return null;

        $st = $this->pdo->prepare(
            "SELECT sltb_depot_id AS id, name, city, phone
             FROM sltb_depots
             WHERE sltb_depot_id = ?"
        );
        $st->execute([$dep]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Today’s cards (Delayed/Breakdown) scoped to session depot */
    public function todayStats(?int $depotId = null): array
    {
        $dep = $depotId ?? $this->myDepotId();
        if ($dep <= 0) return ['delayed'=>0,'breaks'=>0];

        $sql = "
            SELECT
              SUM(CASE WHEN tm.operational_status='Delayed'   THEN 1 ELSE 0 END) AS delayed_cnt,
              SUM(CASE WHEN tm.operational_status='Breakdown' THEN 1 ELSE 0 END) AS breakdown_cnt
            FROM tracking_monitoring tm
            JOIN sltb_buses b
              ON b.reg_no = tm.bus_reg_no
             AND b.sltb_depot_id = :dep
            WHERE tm.operator_type='SLTB'
              AND tm.snapshot_at >= CURDATE()
              AND tm.snapshot_at <  CURDATE() + INTERVAL 1 DAY
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([':dep' => $dep]);
        $r = $st->fetch() ?: [];

        return [
            'delayed' => (int)($r['delayed_cnt'] ?? 0),
            'breaks'  => (int)($r['breakdown_cnt'] ?? 0),
        ];
    }

    /** Delayed list for today scoped to session depot */
    public function delayedToday(?int $depotId = null): array
    {
        $dep = $depotId ?? $this->myDepotId();
        if ($dep <= 0) return [];

        $sql = "
            SELECT tm.*, r.route_no
            FROM tracking_monitoring tm
            JOIN sltb_buses b
              ON b.reg_no = tm.bus_reg_no
             AND b.sltb_depot_id = :dep
            LEFT JOIN routes r ON r.route_id = tm.route_id
            WHERE tm.operator_type='SLTB'
              AND tm.snapshot_at >= CURDATE()
              AND tm.snapshot_at <  CURDATE() + INTERVAL 1 DAY
              AND tm.operational_status='Delayed'
            ORDER BY tm.snapshot_at DESC
            LIMIT 50
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([':dep' => $dep]);
        return $st->fetchAll();
    }
}
