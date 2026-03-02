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
        if (isset($u['sltb_depot_id']) && $u['sltb_depot_id'] !== '') {
            return (int)$u['sltb_depot_id'];
        }
        return null;
    }
    /**
     * Users without an assigned depot will see the full fleet rather than
     * nothing; this makes development easier and prevents silent failures.
     */
    private function hasDepot(): bool { return $this->depotId() !== null; }

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

    /** helper used by both list() and summaryCards() */
    private function buildFilterWhere(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['route'])) {
            // route number comes from joined `routes` table alias r
            $clauses[] = "r.route_no = :route";
            $params[':route'] = $filters['route'];
        }
        if (!empty($filters['status'])) {
            $clauses[] = "sb.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['capacity'])) {
            if ($filters['capacity'] === 'small') {
                $clauses[] = "sb.capacity < 30";
            } elseif ($filters['capacity'] === 'medium') {
                $clauses[] = "sb.capacity BETWEEN 30 AND 50";
            } elseif ($filters['capacity'] === 'large') {
                $clauses[] = "sb.capacity > 50";
            }
        }
        if (!empty($filters['search'])) {
            $clauses[] = "(
                sb.reg_no LIKE :search OR 
                r.route_no LIKE :search
            )";
            $params[':search'] = '%'.$filters['search'].'%';
        }

        // Assignment filter: check if bus has assignments with driver AND conductor
        if (!empty($filters['assignment'])) {
            if ($filters['assignment'] === 'full') {
                // Fully assigned: has assignment with both driver_id and conductor_id
                $clauses[] = "(
                    EXISTS (
                        SELECT 1 FROM sltb_assignments sa
                        WHERE sa.bus_reg_no = sb.reg_no
                        AND sa.sltb_driver_id IS NOT NULL
                        AND sa.sltb_conductor_id IS NOT NULL
                        AND DATE(sa.assigned_date) = CURDATE()
                    )
                )";
            } elseif ($filters['assignment'] === 'incomplete') {
                // Incomplete: has assignment but missing driver or conductor
                $clauses[] = "(
                    EXISTS (
                        SELECT 1 FROM sltb_assignments sa
                        WHERE sa.bus_reg_no = sb.reg_no
                        AND (sa.sltb_driver_id IS NULL OR sa.sltb_conductor_id IS NULL)
                        AND DATE(sa.assigned_date) = CURDATE()
                    )
                )";
            } elseif ($filters['assignment'] === 'unassigned') {
                // Unassigned: no assignment for today
                $clauses[] = "(
                    NOT EXISTS (
                        SELECT 1 FROM sltb_assignments sa
                        WHERE sa.bus_reg_no = sb.reg_no
                        AND DATE(sa.assigned_date) = CURDATE()
                    )
                )";
            }
        }

        // Maintenance filter: for now, based on status column
        if (!empty($filters['maintenance'])) {
            if ($filters['maintenance'] === 'overdue' || $filters['maintenance'] === 'due-soon') {
                // Both map to buses currently in Maintenance status
                $clauses[] = "sb.status = 'Maintenance'";
            } elseif ($filters['maintenance'] === 'scheduled') {
                // Scheduled = Active (normal operation)
                $clauses[] = "sb.status = 'Active'";
            }
        }

        $where = '';
        if (!empty($clauses)) {
            $where = 'WHERE sb.sltb_depot_id=:d AND ' . implode(' AND ', $clauses);
        } else {
            $where = 'WHERE sb.sltb_depot_id=:d';
        }
        return [$where, $params];
    }

    /**
     * Summary cards may optionally be filtered. Accepts same keys as list().
     * When filters are provided the counts respect them (useful for showing
     * breakdown after user applies a filter set).
     *
     * @param array $filters
     * @return array
     */
    public function summaryCards(array $filters = []): array
    {
        // Build base filters (excluding status so we can count each status separately)
        $baseFilters = [
            'search'      => $filters['search'] ?? '',
            'route'       => $filters['route'] ?? '',
            'capacity'    => $filters['capacity'] ?? '',
            'assignment'  => $filters['assignment'] ?? '',
            'maintenance' => $filters['maintenance'] ?? '',
            // deliberately NOT including status
        ];

        // Count TOTAL using all filters except status
        $totalCount  = $this->countBusesWithFilters($baseFilters);

        // Count by each specific status (add status filter, then count)
        $activeCount = $this->countBusesWithFilters(array_merge($baseFilters, ['status' => 'Active']));
        $mainCount   = $this->countBusesWithFilters(array_merge($baseFilters, ['status' => 'Maintenance']));
        $inactiveCount = $this->countBusesWithFilters(array_merge($baseFilters, ['status' => 'Inactive']));

        return [
            ['label'=>'Total Buses','value'=>(string)$totalCount,'class'=>'primary'],
            ['label'=>'Active Buses','value'=>(string)$activeCount,'class'=>'green'],
            ['label'=>'In Maintenance','value'=>(string)$mainCount,'class'=>'yellow'],
            ['label'=>'Out of Service','value'=>(string)$inactiveCount,'class'=>'red'],
        ];
    }

    /** Count buses matching the given filter set */
    private function countBusesWithFilters(array $filters): int
    {
        try {
            list($where, $params) = $this->buildFilterWhere($filters);
            $did = $this->depotId();
            if ($did !== null) {
                if (!isset($params[':d'])) {
                    $params[':d'] = $did;
                }
            } else {
                // if no depot assigned, just remove the depot constraint
                $where = preg_replace('/WHERE\s+sb\.sltb_depot_id=:d\s+AND\s+/', 'WHERE ', $where);
                $where = preg_replace('/WHERE\s+sb\.sltb_depot_id=:d\b/', 'WHERE 1=1 ', $where);
            }

            $sql = "SELECT COUNT(DISTINCT sb.reg_no) as c FROM sltb_buses sb
                    LEFT JOIN (
                        SELECT bus_reg_no, MAX(timetable_id) AS max_tt
                        FROM timetables WHERE operator_type='SLTB'
                        GROUP BY bus_reg_no
                    ) s1 ON s1.bus_reg_no = sb.reg_no
                    LEFT JOIN timetables tt ON tt.timetable_id = s1.max_tt
                    LEFT JOIN routes r ON r.route_id = tt.route_id
                    $where";

            $st = $this->pdo->prepare($sql);
            foreach ($params as $key => $val) {
                if ($key === ':d') {
                    $st->bindValue($key, $val, PDO::PARAM_INT);
                } else {
                    $st->bindValue($key, $val);
                }
            }
            $st->execute();
            $result = $st->fetch(PDO::FETCH_ASSOC);
            return (int)($result['c'] ?? 0);
        } catch (PDOException $e) {
            error_log('FleetModel::countBusesWithFilters error: '.$e->getMessage());
            return 0;
        }
    }

    public function list(array $filters = []): array
    {
        try {
            // build filter where clause along with params
            // build filter where clause along with params; add depot constraint if available
            list($where, $params) = $this->buildFilterWhere($filters);
            $did = $this->depotId();
            if ($did !== null) {
                // prepend depot clause if not already present
                if (strpos($where, 'sltb_depot_id') === false) {
                    if (trim($where) === '') {
                        $where = 'WHERE sb.sltb_depot_id = :d';
                    } else {
                        $where = preg_replace('/WHERE\s+/i', "WHERE sb.sltb_depot_id = :d AND ", $where, 1);
                    }
                }
                $params[':d'] = $did;
            }

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
                $where
                ORDER BY sb.reg_no DESC
                LIMIT 200";

            $st = $this->pdo->prepare($sql);
            foreach ($params as $key => $val) {
                $st->bindValue($key, $val);
            }
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
            $rows = $this->pdo->query("SELECT route_id, route_no, stops_json FROM routes ORDER BY route_no+0, route_no")
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
