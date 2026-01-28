<?php
namespace App\models\depot_manager;

use PDO;
use PDOException;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() { $this->pdo = $GLOBALS['db']; }
}

class FleetModel extends BaseModel
{
    private function depotId(): ?int {
        $u = $_SESSION['user'] ?? [];
        $cand = $u['sltb_depot_id'] ??  null;
        return $cand ? (int)$cand : null;
    }
    private function hasDepot(): bool { return (bool)$this->depotId(); }

    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }

    private function statusOrDefault(?string $s): string {
        $s = trim((string)$s);
        return in_array($s, ['Active','Maintenance','Inactive'], true) ? $s : 'Active';
    }

    private function countSafe(string $sql, array $params): int {
        try { $st = $this->pdo->prepare($sql); $st->execute($params);
              return (int)($st->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        } catch (PDOException $e) { return 0; }
    }

    public function summaryCards(): array
    {
        if (!$this->hasDepot()) {
            return [
                ['label'=>'Total Buses','value'=>'0','class'=>'primary'],
                ['label'=>'Active Buses','value'=>'0','class'=>'green'],
                ['label'=>'In Maintenance','value'=>'0','class'=>'yellow'],
                ['label'=>'Out of Service','value'=>'0','class'=>'red'],
            ];
        }
        $p = [':d' => $this->depotId()];
        $total       = $this->countSafe("SELECT COUNT(*) c FROM sltb_buses WHERE sltb_depot_id=:d", $p);
        $active      = $this->countSafe("SELECT COUNT(*) c FROM sltb_buses WHERE sltb_depot_id=:d AND status='Active'", $p);
        $maintenance = $this->countSafe("SELECT COUNT(*) c FROM sltb_buses WHERE sltb_depot_id=:d AND status='Maintenance'", $p);
        $inactive    = $this->countSafe("SELECT COUNT(*) c FROM sltb_buses WHERE sltb_depot_id=:d AND status='Inactive'", $p);

        return [
            ['label'=>'Total Buses','value'=>(string)$total,'class'=>'primary'],
            ['label'=>'Active Buses','value'=>(string)$active,'class'=>'green'],
            ['label'=>'In Maintenance','value'=>(string)$maintenance,'class'=>'yellow'],
            ['label'=>'Out of Service','value'=>(string)$inactive,'class'=>'red'],
        ];
    }

    public function list(): array
    {
        if (!$this->hasDepot()) return [];
        try {
            $sql = "
                SELECT
                    sb.reg_no, sb.status, sb.capacity, sb.chassis_no,
                    r.route_no, r.stops_json,
                    CONCAT(tm.lat, ',', tm.lng) AS current_location,
                    NULL AS last_maintenance, NULL AS next_service
                FROM sltb_buses sb
                LEFT JOIN (
                    SELECT bus_reg_no, MAX(timetable_id) AS max_tt
                    FROM timetables WHERE operator_type='SLTB'
                    GROUP BY bus_reg_no
                ) s1 ON s1.bus_reg_no = sb.reg_no
                LEFT JOIN timetables tt ON tt.timetable_id = s1.max_tt
                LEFT JOIN routes r ON r.route_id = tt.route_id
                LEFT JOIN (
                    SELECT x.bus_reg_no, MAX(x.snapshot_at) AS maxsnap
                    FROM tracking_monitoring x
                    WHERE x.operator_type='SLTB'
                    GROUP BY x.bus_reg_no
                ) lg ON lg.bus_reg_no = sb.reg_no
                LEFT JOIN tracking_monitoring tm
                  ON tm.bus_reg_no = sb.reg_no
                 AND tm.operator_type='SLTB'
                 AND tm.snapshot_at = lg.maxsnap
                WHERE sb.sltb_depot_id = :d
                ORDER BY sb.reg_no DESC
                LIMIT 200";
            $st = $this->pdo->prepare($sql);
            $st->bindValue(':d', $this->depotId(), PDO::PARAM_INT);
            $st->execute();
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as &$r) {
                $r['route'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
            }
            return $rows;
        } catch (PDOException $e) { return []; }
    }

    public function routes(): array
    {
        try {
            $rows = $this->pdo->query(\"SELECT route_id, route_no, stops_json FROM routes ORDER BY route_no+0, route_no\")
                             ->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as &$r) {
                $r['name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
            }
            return $rows;
        } catch (PDOException $e) { return []; }
    }

    public function buses(): array
    {
        if (!$this->hasDepot()) return [];
        try {
            $st = $this->pdo->prepare("SELECT reg_no FROM sltb_buses WHERE sltb_depot_id=:d ORDER BY reg_no");
            $st->bindValue(':d', $this->depotId(), PDO::PARAM_INT);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) { return []; }
    }

    public function createBus(array $d): bool
    {
        if (!$this->hasDepot()) return false;
        try {
            $sql = "INSERT INTO sltb_buses (reg_no, sltb_depot_id, chassis_no, capacity, status)
                    VALUES (:reg_no, :depot, :chassis_no, :capacity, :status)";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([
                ':reg_no'     => trim((string)($d['reg_no'] ?? '')),
                ':depot'      => $this->depotId(),
                ':chassis_no' => $d['chassis_no'] ?? null,
                ':capacity'   => isset($d['capacity']) ? (int)$d['capacity'] : null,
                ':status'     => $this->statusOrDefault($d['status'] ?? null),
            ]);
        } catch (PDOException $e) { return false; }
    }

    public function updateBus(array $d): bool
    {
        if (!$this->hasDepot()) return false;
        try {
            $reg = trim((string)($d['reg_no'] ?? ''));
            if ($reg === '') return false;
            $sql = "UPDATE sltb_buses
                       SET chassis_no=:chassis_no, capacity=:capacity, status=:status
                     WHERE reg_no=:reg_no AND sltb_depot_id=:depot";
            $st = $this->pdo->prepare($sql);
            $st->bindValue(':depot', $this->depotId(), PDO::PARAM_INT);
            $st->bindValue(':reg_no', $reg);
            $st->bindValue(':chassis_no', $d['chassis_no'] ?? null);
            $st->bindValue(':capacity', isset($d['capacity']) ? (int)$d['capacity'] : null, PDO::PARAM_INT);
            $st->bindValue(':status', $this->statusOrDefault($d['status'] ?? null));
            return $st->execute();
        } catch (PDOException $e) { return false; }
    }

    public function deleteBus($regOrId): bool
    {
        if (!$this->hasDepot()) return false;
        try {
            $st = $this->pdo->prepare("DELETE FROM sltb_buses WHERE reg_no=:reg AND sltb_depot_id=:depot");
            $st->bindValue(':reg', (string)$regOrId);
            $st->bindValue(':depot', $this->depotId(), PDO::PARAM_INT);
            return $st->execute();
        } catch (PDOException $e) { return false; }
    }
}
