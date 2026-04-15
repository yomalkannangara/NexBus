<?php
namespace App\models\depot_manager;

use PDO;
use PDOException;
use App\models\common\BaseModel;

class PerformanceModel extends BaseModel
{
    private function depotId(): ?int
    {
        $u = $_SESSION['user'] ?? [];
        if (isset($u['sltb_depot_id']) && $u['sltb_depot_id'] !== '') {
            return (int)$u['sltb_depot_id'];
        }
        if (isset($u['depot_id']) && $u['depot_id'] !== '') {
            return (int)$u['depot_id'];
        }
        return null;
    }

    private function depotJoin(): array
    {
        $depotId = $this->depotId();
        if ($depotId === null) {
            return ['join' => '', 'params' => []];
        }
        return [
            'join' => ' JOIN sltb_buses sb ON sb.reg_no = tm.bus_reg_no AND sb.sltb_depot_id = :depot_id',
            'params' => [':depot_id' => $depotId],
        ];
    }

    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }
    
    /* ---------- KPI cards ---------- */
    public function cards(): array
    {
        // Try from trip_performance (preferred). Fallback to zeros if table/cols are absent.
        $delayedRate = $this->percentSafe("
            SELECT 100 * SUM(CASE WHEN delayed=1 THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0) AS p
            FROM trip_performance
            WHERE DATE(trip_date)=CURDATE()
        ");

        $avgRating = $this->avgSafe("
            SELECT AVG(overall_score) a
            FROM trip_performance
            WHERE DATE(trip_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");

        $speedViolations = $this->sumSafe("
            SELECT SUM(violations) s
            FROM trip_performance
            WHERE DATE(trip_date)=CURDATE()
        ");

        $longWaitRate = $this->percentSafe("
            SELECT 100 * SUM(CASE WHEN (long_wait=1 OR wait_time_min>10) THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0) AS p
            FROM trip_performance
            WHERE DATE(trip_date)=CURDATE()
        ");

        // Dummy data has been removed; return zeros when the trip_performance table has no data.
        return [
            ['title' => 'Delayed Buses Today',     'value' => number_format($delayedRate, 1).'%', 'sub' => 'Share of delayed trips today', 'color' => 'red'],
            ['title' => 'Average Driver Rating',   'value' => number_format($avgRating, 1),       'sub' => '7-day average score',          'color' => 'green'],
            ['title' => 'Speed Violations',        'value' => (string) (int)$speedViolations,     'sub' => 'Today total',                   'color' => 'yellow'],
            ['title' => 'Long Wait Times',         'value' => number_format($longWaitRate, 1).'%', 'sub' => '> 10 minutes (today)',         'color' => 'maroon'],
        ];
    }

    /* ---------- Top drivers rows (last 30d) ---------- */
    public function topDrivers(): array
    {
        try {
            // Aggregate per driver for the last 30 days
            $sql = "
                SELECT d.id AS driver_id,
                       d.name AS driver_name,
                       AVG(p.overall_score)        AS avg_rating,
                       SUM(COALESCE(p.violations,0)) AS speed_violations,
                       SUM(CASE WHEN p.delayed=1 THEN 1 ELSE 0 END) AS delayed_trips,
                       SUM(CASE WHEN (p.long_wait=1 OR p.wait_time_min>10) THEN 1 ELSE 0 END) AS long_waits,
                       COUNT(*) AS trips
                FROM trip_performance p
                JOIN drivers d ON d.id = p.driver_id
                WHERE p.trip_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY d.id, d.name
                HAVING trips >= 3
                ORDER BY avg_rating DESC
                LIMIT 20
            ";
            $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Build output structure (rank + resolved route label)
            $out = [];
            $rank = 1;
            foreach ($rows as $r) {
                $route = $this->bestRouteForDriver((int)$r['driver_id']);

                $delayPct = $this->safePct((int)$r['delayed_trips'], (int)$r['trips']);
                $waitPct  = $this->safePct((int)$r['long_waits'],   (int)$r['trips']);

                $out[] = [
                    'rank'   => $rank++,
                    'name'   => $r['driver_name'],
                    'route'  => $route, // e.g. "138 — Colombo - Kandy" or "—"
                    'delay'  => number_format($delayPct, 1) . '%',
                    'rating' => number_format((float)$r['avg_rating'], 1),
                    'speed'  => (string) (int)$r['speed_violations'],
                    'wait'   => number_format($waitPct, 1) . '%',
                ];
            }

            return $out;
        } catch (PDOException $e) {
            return [];
        }
    }

    /* ---------- Helpers ---------- */

    private function bestRouteForDriver(int $driverId): string
    {
        try {
            $sql = "
                SELECT r.route_no, r.stops_json, COUNT(*) c
                FROM trip_performance p
                JOIN routes r ON r.route_id = p.route_id
                WHERE p.driver_id = :id
                GROUP BY r.route_id, r.route_no, r.stops_json
                ORDER BY c DESC
                LIMIT 1
            ";
            $st = $this->pdo->prepare($sql);
            $st->execute([':id' => $driverId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $no = trim((string)($row['route_no'] ?? ''));
                $nm = $this->getRouteDisplayName($row['stops_json'] ?? '[]');
                return $no && $nm ? "{$no} — {$nm}" : ($no ?: ($nm ?: '—'));
            }
        } catch (PDOException $e) { /* ignore */ }
        return '—';
    }

    private function percentSafe(string $sql): float
    {
        try {
            $v = (float)($this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC)['p'] ?? 0.0);
            if (!is_finite($v)) return 0.0;
            return max(0.0, min(100.0, $v));
        } catch (PDOException $e) {
            return 0.0;
        }
    }

    private function avgSafe(string $sql): float
    {
        try {
            return (float)($this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC)['a'] ?? 0.0);
        } catch (PDOException $e) {
            return 0.0;
        }
    }

    private function sumSafe(string $sql): int
    {
        try {
            return (int)($this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC)['s'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    private function safePct(int $num, int $den): float
    {
        return $den > 0 ? (100.0 * $num / $den) : 0.0;
    }

    /* ---------- SLTB Performance Metrics (for analytics page) ---------- */
    
    public function getPerformanceMetricsForSLTB(array $filters = []): array
    {
        $metrics = [
            'delayed_buses'    => 0,
            'average_rating'   => null,
            'speed_violations' => 0,
            'long_wait_rate'   => 0,
            'total_complaints' => 0,
        ];

        $params = [];
        $routeClause = '';
        $busClause = '';
        $depotClause = '';

        $scope = $this->depotJoin();
        if (!empty($scope['params'])) {
            $depotClause = ' AND EXISTS (SELECT 1 FROM sltb_buses sb WHERE sb.reg_no = tm.bus_reg_no AND sb.sltb_depot_id = :depot_id)';
            $params += $scope['params'];
        }

        // Optional route filter
        if (!empty($filters['route_no'])) {
            $routeClause = " AND EXISTS (SELECT 1 FROM sltb_routes r WHERE r.route_id = tm.route_id AND r.route_no = :route_no)";
            $params[':route_no'] = $filters['route_no'];
        }

        // Optional bus filter
        if (!empty($filters['bus_reg'])) {
            $busClause = " AND tm.bus_reg_no = :bus_reg";
            $params[':bus_reg'] = $filters['bus_reg'];
        }

        // 1. Delayed buses (SLTB only) — latest snapshot per bus
        $sql = "SELECT COUNT(*) FROM (
                    SELECT tm.bus_reg_no,
                           ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                    FROM tracking_monitoring tm
                    WHERE tm.operator_type='SLTB'
                      AND DATE(tm.snapshot_at)=CURDATE()
                                            $depotClause
                      AND tm.operational_status='Delayed' $routeClause $busClause
                ) latest WHERE latest.rn = 1";
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $metrics['delayed_buses'] = (int)$st->fetchColumn();
        } catch (PDOException $e) { }

        // 2. Speed violations (SLTB only)
        $sql = "SELECT COALESCE(SUM(tm.speed_violations),0)
                FROM tracking_monitoring tm
                WHERE tm.operator_type='SLTB'
                                    AND DATE(tm.snapshot_at)=CURDATE() $depotClause $routeClause $busClause";
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $metrics['speed_violations'] = (int)$st->fetchColumn();
        } catch (PDOException $e) { }

        // 3. Average reliability index (SLTB only)
        $sql = "SELECT AVG(tm.reliability_index)
                FROM tracking_monitoring tm
                WHERE tm.operator_type='SLTB'
                                    AND DATE(tm.snapshot_at)=CURDATE() $depotClause $routeClause $busClause";
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $avg = $st->fetchColumn();
            $metrics['average_rating'] = $avg !== null ? round((float)$avg, 1) : null;
        } catch (PDOException $e) { }

        // 4. Long wait rate (SLTB only)
        $sql = "SELECT 
                    SUM(CASE WHEN tm.avg_delay_min>=10 THEN 1 ELSE 0 END) AS long_wait,
                    COUNT(*) AS total
                FROM tracking_monitoring tm
                WHERE tm.operator_type='SLTB'
                                    AND DATE(tm.snapshot_at)=CURDATE() $depotClause $routeClause $busClause";
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row && (int)$row['total'] > 0) {
                $metrics['long_wait_rate'] = round(100.0 * (int)$row['long_wait'] / (int)$row['total'], 1);
            } else {
                $metrics['long_wait_rate'] = 0;
            }
        } catch (PDOException $e) { }

        return $metrics;
    }

    public function getSLTBRoutes(): array
    {
        try {
            $scope = $this->depotJoin();
            // Get distinct routes that have SLTB tracking data
            $sql = "SELECT DISTINCT r.route_no, r.route_id, r.route_name as name
                    FROM routes r
                    JOIN tracking_monitoring tm ON tm.route_id = r.route_id
                    {$scope['join']}
                    WHERE tm.operator_type = 'SLTB'
                      AND DATE(tm.snapshot_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    ORDER BY CAST(r.route_no AS UNSIGNED), r.route_no";
            $st = $this->pdo->prepare($sql);
            $st->execute($scope['params']);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getSLTBBuses(): array
    {
        try {
            $depotId = $this->depotId();
            if ($depotId === null) return [];
            $sql = "SELECT reg_no FROM sltb_buses WHERE sltb_depot_id = :depot_id ORDER BY reg_no";
            $st = $this->pdo->prepare($sql);
            $st->execute([':depot_id' => $depotId]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /* ---------- Chart Data Methods ---------- */

    public function getBusStatusData(array $filters = []): array
    {
        try {
            $depotId = $this->depotId();

            $params      = [];
            $depotClause = '';
            $routeClause = '';
            $busClause   = '';

            if ($depotId) {
                $depotClause = ' AND EXISTS (SELECT 1 FROM sltb_buses sb WHERE sb.reg_no = tm.bus_reg_no AND sb.sltb_depot_id = :depot_id)';
                $params[':depot_id'] = (int)$depotId;
            }
            if (!empty($filters['route_no'])) {
                $routeClause = " AND EXISTS (SELECT 1 FROM routes r WHERE r.route_id = tm.route_id AND r.route_no = :route_no)";
                $params[':route_no'] = $filters['route_no'];
            }
            if (!empty($filters['bus_reg'])) {
                $busClause = ' AND tm.bus_reg_no = :bus_reg';
                $params[':bus_reg'] = $filters['bus_reg'];
            }

            // Count distinct buses by their LATEST operational status today
            $sql = "SELECT latest.operational_status AS status, COUNT(*) AS total
                    FROM (
                        SELECT tm.bus_reg_no,
                               tm.operational_status,
                               ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                        FROM tracking_monitoring tm
                        WHERE tm.operator_type = 'SLTB'
                          AND DATE(tm.snapshot_at) = CURDATE()
                          $depotClause $routeClause $busClause
                    ) AS latest
                    WHERE latest.rn = 1
                    GROUP BY latest.operational_status
                    ORDER BY total DESC";

            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return array_map(fn($r) => [
                'label'  => (string)($r['status'] ?? 'Unknown'),
                'value'  => (int)$r['total'],
                'status' => $r['status'],
            ], $rows);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getDelayedByRouteData(array $filters = []): array
    {
        try {
            $depotId = $this->depotId();
            $params = [];
            $busClause = '';
            $depotClause = '';
            if ($depotId !== null) {
                $depotClause = ' AND EXISTS (SELECT 1 FROM sltb_buses sb WHERE sb.reg_no = tm.bus_reg_no AND sb.sltb_depot_id = :depot_id)';
                $params[':depot_id'] = $depotId;
            }
            if (!empty($filters['bus_reg'])) {
                $busClause = " AND tm.bus_reg_no = :bus_reg";
                $params[':bus_reg'] = $filters['bus_reg'];
            }

            $sql = "SELECT r.route_no,
                           SUM(CASE WHEN tm.operational_status='Delayed' THEN 1 ELSE 0 END) AS delayed,
                           COUNT(*) AS total
                    FROM tracking_monitoring tm
                    LEFT JOIN routes r ON r.route_id = tm.route_id
                    WHERE tm.operator_type='SLTB' 
                                            AND DATE(tm.snapshot_at)=CURDATE() $depotClause $busClause
                    GROUP BY r.route_id, r.route_no
                    ORDER BY total DESC
                    LIMIT 8";
            
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            $labels = [];
            $delayed = [];
            $total = [];
            
            foreach ($rows as $r) {
                $labels[] = $r['route_no'] ?? 'Unknown';
                $delayed[] = (int)$r['delayed'];
                $total[] = (int)$r['total'];
            }
            
            return ['labels' => $labels, 'delayed' => $delayed, 'total' => $total];
        } catch (PDOException $e) {
            return ['labels' => [], 'delayed' => [], 'total' => []];
        }
    }

    public function getSpeedByBusData(array $filters = []): array
    {
        try {
            $depotId = $this->depotId();
            $params = [];
            $routeClause = '';
            $depotClause = '';
            if ($depotId !== null) {
                $depotClause = ' AND EXISTS (SELECT 1 FROM sltb_buses sb WHERE sb.reg_no = tm.bus_reg_no AND sb.sltb_depot_id = :depot_id)';
                $params[':depot_id'] = $depotId;
            }
            if (!empty($filters['route_no'])) {
                $routeClause = " AND EXISTS (SELECT 1 FROM routes r WHERE r.route_id = tm.route_id AND r.route_no = :route_no)";
                $params[':route_no'] = $filters['route_no'];
            }

            $sql = "SELECT tm.bus_reg_no,
                           SUM(COALESCE(tm.speed_violations, 0)) AS violations
                    FROM tracking_monitoring tm
                    WHERE tm.operator_type='SLTB' 
                                            AND DATE(tm.snapshot_at)=CURDATE() $depotClause $routeClause
                    GROUP BY tm.bus_reg_no
                    ORDER BY violations DESC
                    LIMIT 9";
            
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            $labels = [];
            $values = [];
            
            foreach ($rows as $r) {
                $labels[] = $r['bus_reg_no'] ?? 'Unknown';
                $values[] = (int)$r['violations'];
            }
            
            return ['labels' => $labels, 'values' => $values];
        } catch (PDOException $e) {
            return ['labels' => [], 'values' => []];
        }
    }

    public function getRevenueData(array $filters = []): array
    {
        try {
            $depotId = $this->depotId();
            $depotClause = '';
            $params = [];
            if ($depotId !== null) {
                $depotClause = ' AND EXISTS (SELECT 1 FROM sltb_buses sb WHERE sb.reg_no = tm.bus_reg_no AND sb.sltb_depot_id = :depot_id)';
                $params[':depot_id'] = $depotId;
            }
            $sql = "SELECT 
                        DATE_FORMAT(tm.snapshot_at, '%b') AS month,
                        SUM(COALESCE(tm.revenue, 0)) AS revenue
                    FROM tracking_monitoring tm
                    WHERE tm.operator_type='SLTB'
                      AND YEAR(tm.snapshot_at) = YEAR(CURDATE())
                      $depotClause
                    GROUP BY MONTH(tm.snapshot_at), DATE_FORMAT(tm.snapshot_at, '%b')
                    ORDER BY MONTH(tm.snapshot_at)";
            
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            $labels = [];
            $values = [];
            
            foreach ($rows as $r) {
                $labels[] = $r['month'] ?? '';
                $values[] = round((float)($r['revenue'] ?? 0) / 1000000, 1); // Convert to millions
            }
            
            return ['labels' => $labels, 'values' => $values];
        } catch (PDOException $e) {
            return ['labels' => [], 'values' => []];
        }
    }

    public function getWaitTimeData(array $filters = []): array
    {
        try {
            $depotId = $this->depotId();
            $params = [];
            $routeClause = '';
            $busClause = '';
            $depotClause = '';

            if ($depotId !== null) {
                $depotClause = ' AND EXISTS (SELECT 1 FROM sltb_buses sb WHERE sb.reg_no = tm.bus_reg_no AND sb.sltb_depot_id = :depot_id)';
                $params[':depot_id'] = $depotId;
            }

            if (!empty($filters['route_no'])) {
                    $routeClause = " AND EXISTS (SELECT 1 FROM routes r WHERE r.route_id = tm.route_id AND r.route_no = :route_no)";
                    $params[':route_no'] = $filters['route_no'];
                }
                if (!empty($filters['bus_reg'])) {
                    $busClause = " AND tm.bus_reg_no = :bus_reg";
                    $params[':bus_reg'] = $filters['bus_reg'];
                }

                $sql = "SELECT 
                            SUM(CASE WHEN tm.avg_delay_min < 5 THEN 1 ELSE 0 END) AS under_5,
                            SUM(CASE WHEN tm.avg_delay_min >= 5 AND tm.avg_delay_min < 10 THEN 1 ELSE 0 END) AS between_5_10,
                            SUM(CASE WHEN tm.avg_delay_min >= 10 AND tm.avg_delay_min < 15 THEN 1 ELSE 0 END) AS between_10_15,
                            SUM(CASE WHEN tm.avg_delay_min >= 15 THEN 1 ELSE 0 END) AS over_15
                        FROM tracking_monitoring tm
                        WHERE tm.operator_type='SLTB' 
                                                    AND DATE(tm.snapshot_at)=CURDATE() $depotClause $routeClause $busClause";
                
                $st = $this->pdo->prepare($sql);
                $st->execute($params);
                $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
                
                return [
                    ['label' => 'Under 5 min', 'value' => (int)($row['under_5'] ?? 0), 'color' => '#16a34a'],
                    ['label' => '5–10 min', 'value' => (int)($row['between_5_10'] ?? 0), 'color' => '#f3b944'],
                    ['label' => '10–15 min', 'value' => (int)($row['between_10_15'] ?? 0), 'color' => '#f59e0b'],
                    ['label' => 'Over 15 min', 'value' => (int)($row['over_15'] ?? 0), 'color' => '#b91c1c']
                ];
            } catch (PDOException $e) {
                return [];
            }
        }

    public function getComplaintsByRouteData(array $filters = []): array
    {
        try {
            $depotId = $this->depotId();
            $params = [];
            $busClause = '';
            $depotClause = '';
            if ($depotId !== null) {
                $depotClause = ' AND EXISTS (SELECT 1 FROM sltb_buses sb WHERE sb.reg_no = c.bus_reg_no AND sb.sltb_depot_id = :depot_id)';
                $params[':depot_id'] = $depotId;
            }
            if (!empty($filters['bus_reg'])) {
                $busClause = " AND c.bus_reg_no = :bus_reg";
                $params[':bus_reg'] = $filters['bus_reg'];
            }

            $sql = "SELECT r.route_no, COUNT(*) AS count
                    FROM passenger_feedback c
                    LEFT JOIN routes r ON r.route_id = c.route_id
                    WHERE c.operator_type='SLTB'
                      AND LOWER(c.type) = 'complaint'
                                            AND DATE(c.created_at)=CURDATE() $depotClause $busClause
                    GROUP BY r.route_id, r.route_no
                    ORDER BY count DESC
                    LIMIT 8";
            
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            $labels = [];
            $values = [];
            
            foreach ($rows as $r) {
                $labels[] = $r['route_no'] ?? 'Unknown';
                $values[] = (int)$r['count'];
            }
            
            return ['labels' => $labels, 'values' => $values];
        } catch (PDOException $e) {
            return ['labels' => [], 'values' => []];
        }
    }
}

