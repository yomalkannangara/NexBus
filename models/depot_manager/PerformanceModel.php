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

    public function depotName(): string
    {
        $depotId = $this->depotId();
        if (!$depotId) {
            return 'SLTB Depot';
        }
        try {
            $st = $this->pdo->prepare('SELECT name FROM sltb_depots WHERE sltb_depot_id = ?');
            $st->execute([$depotId]);
            return (string)($st->fetchColumn() ?: ('Depot #' . $depotId));
        } catch (PDOException $e) {
            return 'Depot #' . $depotId;
        }
    }

    private function reportDate(array $filters = []): string
    {
        $date = trim((string)($filters['date'] ?? ''));
        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }
        return date('Y-m-d');
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

    public function getFleetStatusComparisonData(array $filters = []): array
    {
        try {
            $depotId = $this->depotId();
            if ($depotId === null) {
                return [];
            }

            $params = [':depot_id' => $depotId];
            $where = ['sb.sltb_depot_id = :depot_id'];

            if (!empty($filters['bus_reg'])) {
                $where[] = 'sb.reg_no = :bus_reg';
                $params[':bus_reg'] = $filters['bus_reg'];
            }

            if (!empty($filters['route_no'])) {
                $where[] = "EXISTS (
                    SELECT 1
                    FROM tracking_monitoring tm
                    LEFT JOIN routes r ON r.route_id = tm.route_id
                    WHERE tm.operator_type = 'SLTB'
                      AND tm.bus_reg_no = sb.reg_no
                      AND DATE(tm.snapshot_at) = CURDATE()
                      AND r.route_no = :route_no
                )";
                $params[':route_no'] = $filters['route_no'];
            }

            $sql = "SELECT
                        SUM(CASE WHEN LOWER(COALESCE(sb.status, 'active')) = 'active' THEN 1 ELSE 0 END) AS active_count,
                        SUM(CASE WHEN LOWER(COALESCE(sb.status, '')) LIKE '%maint%' THEN 1 ELSE 0 END) AS maintenance_count,
                        SUM(CASE
                                WHEN LOWER(COALESCE(sb.status, '')) IN ('inactive', 'in-active', 'deactive', 'disabled') THEN 1
                                WHEN LOWER(COALESCE(sb.status, '')) NOT IN ('active')
                                     AND LOWER(COALESCE(sb.status, '')) NOT LIKE '%maint%'
                                THEN 1
                                ELSE 0
                            END) AS inactive_count
                    FROM sltb_buses sb
                    WHERE " . implode(' AND ', $where);

            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

            return [
                ['label' => 'Active', 'value' => (int)($row['active_count'] ?? 0), 'status' => 'Active'],
                ['label' => 'Maintenance', 'value' => (int)($row['maintenance_count'] ?? 0), 'status' => 'Maintenance'],
                ['label' => 'Inactive', 'value' => (int)($row['inactive_count'] ?? 0), 'status' => 'Inactive'],
            ];
        } catch (PDOException $e) {
            return [];
        }
    }

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
            if ($depotId === null) {
                return ['labels' => [], 'delayed' => [], 'total' => []];
            }

            $requestedDate = trim((string)($filters['date'] ?? ''));
            $reportDate = '';
            if ($requestedDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate)) {
                $reportDate = $requestedDate;
            }

            if ($reportDate === '') {
                $probeParams = [':depot_id' => $depotId];
                $probeWhere = [
                    "tm.operator_type='SLTB'",
                    'EXISTS (SELECT 1 FROM sltb_buses sb WHERE sb.reg_no = tm.bus_reg_no AND sb.sltb_depot_id = :depot_id)',
                ];

                if (!empty($filters['route_no'])) {
                    $probeWhere[] = 'EXISTS (SELECT 1 FROM routes rr WHERE rr.route_id = tm.route_id AND rr.route_no = :route_no)';
                    $probeParams[':route_no'] = $filters['route_no'];
                }
                if (!empty($filters['bus_reg'])) {
                    $probeWhere[] = 'tm.bus_reg_no = :bus_reg';
                    $probeParams[':bus_reg'] = $filters['bus_reg'];
                }

                $probeSql = 'SELECT MAX(DATE(tm.snapshot_at)) FROM tracking_monitoring tm WHERE ' . implode(' AND ', $probeWhere);
                $probeSt = $this->pdo->prepare($probeSql);
                $probeSt->execute($probeParams);
                $reportDate = (string)($probeSt->fetchColumn() ?: date('Y-m-d'));
            }

            $params = [
                ':depot_id' => $depotId,
                ':report_date' => $reportDate,
            ];
            $routeClause = '';
            $busClause = '';

            if (!empty($filters['route_no'])) {
                $routeClause = ' AND r.route_no = :route_no';
                $params[':route_no'] = $filters['route_no'];
            }
            if (!empty($filters['bus_reg'])) {
                $busClause = ' AND x.bus_reg_no = :bus_reg';
                $params[':bus_reg'] = $filters['bus_reg'];
            }

            $sql = "SELECT
                        COALESCE(r.route_no, 'Unknown') AS route_no,
                        SUM(CASE WHEN x.operational_status='Delayed' THEN 1 ELSE 0 END) AS delayed_count,
                        COUNT(*) AS total_count
                    FROM (
                        SELECT tm.bus_reg_no,
                               tm.route_id,
                               tm.operational_status,
                               ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                        FROM tracking_monitoring tm
                        WHERE tm.operator_type='SLTB'
                          AND DATE(tm.snapshot_at)=:report_date
                          AND EXISTS (
                              SELECT 1
                              FROM sltb_buses sb
                              WHERE sb.reg_no = tm.bus_reg_no
                                AND sb.sltb_depot_id = :depot_id
                          )
                    ) x
                    LEFT JOIN routes r ON r.route_id = x.route_id
                    WHERE x.rn = 1 $routeClause $busClause
                    GROUP BY COALESCE(r.route_no, 'Unknown')
                    ORDER BY total_count DESC
                    LIMIT 8";

            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            $labels = [];
            $delayed = [];
            $total = [];
            
            foreach ($rows as $r) {
                $labels[] = $r['route_no'] ?? 'Unknown';
                $delayed[] = (int)($r['delayed_count'] ?? 0);
                $total[] = (int)($r['total_count'] ?? 0);
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
            if ($depotId === null) {
                return ['labels' => [], 'values' => []];
            }

            $params = [':depot_id' => $depotId];
            $routeClause = '';
            $busClause = '';

            if (!empty($filters['route_no'])) {
                $routeClause = " AND EXISTS (
                    SELECT 1
                    FROM timetables t2
                    JOIN routes r2 ON r2.route_id = t2.route_id
                    WHERE t2.bus_reg_no = e.bus_reg_no
                      AND t2.operator_type = 'SLTB'
                      AND r2.route_no = :route_no
                )";
                $params[':route_no'] = $filters['route_no'];
            }

            if (!empty($filters['bus_reg'])) {
                $busClause = ' AND e.bus_reg_no = :bus_reg';
                $params[':bus_reg'] = $filters['bus_reg'];
            }

            $sql = "SELECT
                        DATE_FORMAT(e.date, '%b') AS month,
                        SUM(COALESCE(e.amount, 0)) AS revenue,
                        MONTH(e.date) AS month_num
                    FROM earnings e
                    JOIN sltb_buses sb ON sb.reg_no = e.bus_reg_no AND sb.sltb_depot_id = :depot_id
                    WHERE e.operator_type='SLTB'
                      AND YEAR(e.date)=YEAR(CURDATE())
                      $routeClause
                      $busClause
                    GROUP BY MONTH(e.date), DATE_FORMAT(e.date, '%b')
                    ORDER BY month_num";

            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            $labels = [];
            $values = [];
            
            foreach ($rows as $r) {
                $labels[] = $r['month'] ?? '';
                $values[] = round((float)($r['revenue'] ?? 0) / 1000000, 1);
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
            if ($depotId === null) {
                return ['labels' => [], 'values' => []];
            }

            $params = [':depot_id' => $depotId];
            $busClause = '';
            $routeClause = '';

            if (!empty($filters['bus_reg'])) {
                $busClause = ' AND c.bus_reg_no = :bus_reg';
                $params[':bus_reg'] = $filters['bus_reg'];
            }
            if (!empty($filters['route_no'])) {
                $routeClause = ' AND r.route_no = :route_no';
                $params[':route_no'] = $filters['route_no'];
            }

            $sqlPrimary = "SELECT c.bus_reg_no, COUNT(*) AS cnt
                    FROM complaints c
                    JOIN sltb_buses sb ON sb.reg_no = c.bus_reg_no AND sb.sltb_depot_id = :depot_id
                    LEFT JOIN routes r ON r.route_id = c.route_id
                    WHERE c.operator_type='SLTB'
                      AND LOWER(COALESCE(c.category, ''))='complaint'
                                            AND NULLIF(NULLIF(TRIM(COALESCE(c.bus_reg_no, '')), ''), 'undefined') IS NOT NULL
                      $busClause $routeClause
                    GROUP BY c.bus_reg_no
                    ORDER BY cnt DESC, c.bus_reg_no ASC
                    LIMIT 12";

            $rows = [];
            try {
                $st = $this->pdo->prepare($sqlPrimary);
                $st->execute($params);
                $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (PDOException $e) {
                $sqlFallback = "SELECT c.bus_reg_no, COUNT(*) AS cnt
                        FROM passenger_feedback c
                        JOIN sltb_buses sb ON sb.reg_no = c.bus_reg_no AND sb.sltb_depot_id = :depot_id
                        LEFT JOIN routes r ON r.route_id = c.route_id
                        WHERE c.operator_type='SLTB'
                          AND LOWER(COALESCE(c.type, ''))='complaint'
                                                    AND NULLIF(NULLIF(TRIM(COALESCE(c.bus_reg_no, '')), ''), 'undefined') IS NOT NULL
                          $busClause $routeClause
                        GROUP BY c.bus_reg_no
                        ORDER BY cnt DESC, c.bus_reg_no ASC
                        LIMIT 12";
                $st = $this->pdo->prepare($sqlFallback);
                $st->execute($params);
                $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
            
            $labels = [];
            $values = [];
            
            foreach ($rows as $r) {
                $labels[] = $r['bus_reg_no'] ?? 'Unknown';
                $values[] = (int)($r['cnt'] ?? 0);
            }
            
            return ['labels' => $labels, 'values' => $values];
        } catch (PDOException $e) {
            return ['labels' => [], 'values' => []];
        }
    }

    public function getDelayedTodayDetail(array $filters = []): array
    {
        $reportDate = $this->reportDate($filters);
        $depotId = $this->depotId();
        if (!$depotId) {
            return ['routeSummary' => [], 'delayedBuses' => [], 'reportDate' => $reportDate];
        }

        $params = [':report_date' => $reportDate, ':depot_id' => $depotId];
        $routeClause = '';
        $busClause = '';
        if (!empty($filters['route_no'])) {
            $routeClause = " AND EXISTS (SELECT 1 FROM routes rr WHERE rr.route_id = tm.route_id AND rr.route_no = :route_no)";
            $params[':route_no'] = $filters['route_no'];
        }
        if (!empty($filters['bus_reg'])) {
            $busClause = ' AND tm.bus_reg_no = :bus_reg';
            $params[':bus_reg'] = $filters['bus_reg'];
        }

        $sqlSummary = "SELECT
                COALESCE(r.route_no, 'Unassigned') AS route_no,
                COUNT(DISTINCT x.bus_reg_no) AS total_buses,
                SUM(CASE WHEN x.operational_status = 'Delayed' THEN 1 ELSE 0 END) AS delayed_buses,
                ROUND(AVG(COALESCE(x.speed, 0)), 1) AS avg_speed
            FROM (
                SELECT tm.*,
                       ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                FROM tracking_monitoring tm
                JOIN sltb_buses sb ON sb.reg_no = tm.bus_reg_no AND sb.sltb_depot_id = :depot_id
                WHERE tm.operator_type = 'SLTB'
                  AND DATE(tm.snapshot_at) = :report_date
                  $routeClause
                  $busClause
            ) x
            LEFT JOIN routes r ON r.route_id = x.route_id
            WHERE x.rn = 1
            GROUP BY r.route_id, r.route_no
            ORDER BY delayed_buses DESC, r.route_no ASC";

        $sqlDetail = "SELECT
                x.bus_reg_no,
                :depot_name AS owner_name,
                COALESCE(r.route_no, '-') AS route_no,
                COALESCE(x.operational_status, 'Unknown') AS operational_status,
                ROUND(COALESCE(x.speed, 0), 1) AS speed,
                ROUND(COALESCE(x.avg_delay_min, 0), 1) AS avg_delay_min,
                DATE_FORMAT(x.snapshot_at, '%Y-%m-%d %H:%i') AS snapshot_at
            FROM (
                SELECT tm.*,
                       ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                FROM tracking_monitoring tm
                JOIN sltb_buses sb ON sb.reg_no = tm.bus_reg_no AND sb.sltb_depot_id = :depot_id
                WHERE tm.operator_type = 'SLTB'
                  AND DATE(tm.snapshot_at) = :report_date
                  $routeClause
                  $busClause
            ) x
            LEFT JOIN routes r ON r.route_id = x.route_id
            WHERE x.rn = 1 AND x.operational_status = 'Delayed'
            ORDER BY x.avg_delay_min DESC, x.snapshot_at DESC
            LIMIT 200";

        try {
            $st = $this->pdo->prepare($sqlSummary);
            $st->execute($params);
            $routeSummary = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $params[':depot_name'] = $this->depotName();
            $st = $this->pdo->prepare($sqlDetail);
            $st->execute($params);
            $delayedBuses = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return ['routeSummary' => $routeSummary, 'delayedBuses' => $delayedBuses, 'reportDate' => $reportDate];
        } catch (PDOException $e) {
            return ['routeSummary' => [], 'delayedBuses' => [], 'reportDate' => $reportDate];
        }
    }

    public function getRatingDetail(array $filters = []): array
    {
        $reportDate = $this->reportDate($filters);
        $depotId = $this->depotId();
        if (!$depotId) {
            return ['summary' => [], 'buses' => [], 'reportDate' => $reportDate];
        }

        $params = [':report_date' => $reportDate, ':depot_id' => $depotId];
        $routeClause = '';
        $busClause = '';
        if (!empty($filters['route_no'])) {
            $routeClause = " AND EXISTS (SELECT 1 FROM routes rr WHERE rr.route_id = x.route_id AND rr.route_no = :route_no)";
            $params[':route_no'] = $filters['route_no'];
        }
        if (!empty($filters['bus_reg'])) {
            $busClause = ' AND x.bus_reg_no = :bus_reg';
            $params[':bus_reg'] = $filters['bus_reg'];
        }

        $ratingExpr = "COALESCE(x.reliability_index, CASE WHEN x.operational_status='Delayed' THEN 5 ELSE 8 END)";

        $sqlSummary = "SELECT
                ROUND(AVG(z.bus_avg),1) AS fleet_avg,
                ROUND(MAX(z.bus_avg),1) AS best,
                ROUND(MIN(z.bus_avg),1) AS worst,
                COUNT(*) AS bus_count
            FROM (
                SELECT x.bus_reg_no, AVG($ratingExpr) AS bus_avg
                FROM tracking_monitoring x
                JOIN sltb_buses sb ON sb.reg_no = x.bus_reg_no AND sb.sltb_depot_id = :depot_id
                WHERE x.operator_type = 'SLTB'
                  AND DATE(x.snapshot_at) = :report_date
                  $routeClause
                  $busClause
                GROUP BY x.bus_reg_no
            ) z";

        $sqlAgg = "SELECT
                x.bus_reg_no,
                ROUND(AVG($ratingExpr),1) AS avg_rating,
                ROUND(AVG(COALESCE(x.speed,0)),1) AS avg_speed,
                COUNT(*) AS snapshots,
                DATE_FORMAT(MAX(x.snapshot_at),'%Y-%m-%d %H:%i') AS last_snapshot
            FROM tracking_monitoring x
            JOIN sltb_buses sb ON sb.reg_no = x.bus_reg_no AND sb.sltb_depot_id = :depot_id
            WHERE x.operator_type = 'SLTB'
              AND DATE(x.snapshot_at) = :report_date
              $routeClause
              $busClause
            GROUP BY x.bus_reg_no
            ORDER BY avg_rating DESC
            LIMIT 100";

        $sqlLatest = "SELECT x.bus_reg_no,
                COALESCE(r.route_no,'-') AS route_no,
                COALESCE(d.full_name,'-') AS driver_name
            FROM (
                SELECT tm.*,
                       ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                FROM tracking_monitoring tm
                JOIN sltb_buses pb ON pb.reg_no = tm.bus_reg_no AND pb.sltb_depot_id = :depot_id
                WHERE tm.operator_type = 'SLTB'
                  AND DATE(tm.snapshot_at) = :report_date
                  $routeClause
                  $busClause
            ) x
            LEFT JOIN routes r ON r.route_id = x.route_id
            LEFT JOIN sltb_drivers d ON d.sltb_driver_id = x.driver_id
            WHERE x.rn = 1";

        try {
            $st = $this->pdo->prepare($sqlSummary);
            $st->execute($params);
            $summary = $st->fetch(PDO::FETCH_ASSOC) ?: [];

            $st = $this->pdo->prepare($sqlAgg);
            $st->execute($params);
            $aggRows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $st = $this->pdo->prepare($sqlLatest);
            $st->execute($params);
            $latestRows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $latestMap = [];
            foreach ($latestRows as $lr) {
                $latestMap[$lr['bus_reg_no']] = $lr;
            }

            $buses = array_map(function ($row) use ($latestMap) {
                $lx = $latestMap[$row['bus_reg_no']] ?? [];
                return [
                    'bus_reg_no' => $row['bus_reg_no'],
                    'route_no' => $lx['route_no'] ?? '-',
                    'driver_name' => $lx['driver_name'] ?? '-',
                    'avg_rating' => $row['avg_rating'],
                    'avg_speed' => $row['avg_speed'],
                    'snapshots' => $row['snapshots'],
                    'last_snapshot' => $row['last_snapshot'],
                ];
            }, $aggRows);

            return ['summary' => $summary, 'buses' => $buses, 'reportDate' => $reportDate];
        } catch (PDOException $e) {
            return ['summary' => [], 'buses' => [], 'reportDate' => $reportDate];
        }
    }

    public function getSpeedViolationsDetail(array $filters = []): array
    {
        $reportDate = $this->reportDate($filters);
        $depotId = $this->depotId();
        if (!$depotId) {
            return ['summary' => [], 'buses' => [], 'reportDate' => $reportDate];
        }

        $params = [':report_date' => $reportDate, ':depot_id' => $depotId];
        if (!empty($filters['route_no'])) {
            $params[':route_no'] = $filters['route_no'];
        }
        if (!empty($filters['bus_reg'])) {
            $params[':bus_reg'] = $filters['bus_reg'];
        }

        $routeClause = !empty($filters['route_no']) ? " AND EXISTS (SELECT 1 FROM routes rr WHERE rr.route_id = x.route_id AND rr.route_no = :route_no)" : '';
        $busClause = !empty($filters['bus_reg']) ? ' AND x.bus_reg_no = :bus_reg' : '';

        $sqlSummary = "SELECT
                SUM(COALESCE(x.speed_violations, 0)) AS total_violations,
                COUNT(DISTINCT x.bus_reg_no) AS bus_count,
                SUM(CASE WHEN COALESCE(x.speed_violations,0) > 0 THEN 1 ELSE 0 END) AS snapshots_with_viol,
                ROUND(MAX(COALESCE(x.speed,0)),1) AS fleet_max_speed
            FROM tracking_monitoring x
            JOIN sltb_buses sb ON sb.reg_no = x.bus_reg_no AND sb.sltb_depot_id = :depot_id
            WHERE x.operator_type='SLTB'
              AND DATE(x.snapshot_at) = :report_date
              $routeClause
              $busClause";

        $sqlAgg = "SELECT
                x.bus_reg_no,
                SUM(COALESCE(x.speed_violations, 0)) AS total_violations,
                ROUND(MAX(COALESCE(x.speed, 0)), 1) AS max_speed,
                ROUND(AVG(COALESCE(x.speed, 0)), 1) AS avg_speed,
                COUNT(*) AS snapshots,
                DATE_FORMAT(MAX(x.snapshot_at),'%Y-%m-%d %H:%i') AS last_snapshot
            FROM tracking_monitoring x
            JOIN sltb_buses sb ON sb.reg_no = x.bus_reg_no AND sb.sltb_depot_id = :depot_id
            WHERE x.operator_type='SLTB'
              AND DATE(x.snapshot_at) = :report_date
              $routeClause
              $busClause
            GROUP BY x.bus_reg_no
            HAVING total_violations > 0
            ORDER BY total_violations DESC
            LIMIT 100";

        try {
            $st = $this->pdo->prepare($sqlSummary);
            $st->execute($params);
            $summary = $st->fetch(PDO::FETCH_ASSOC) ?: [];

            $st = $this->pdo->prepare($sqlAgg);
            $st->execute($params);
            $buses = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return ['summary' => $summary, 'buses' => $buses, 'reportDate' => $reportDate];
        } catch (PDOException $e) {
            return ['summary' => [], 'buses' => [], 'reportDate' => $reportDate];
        }
    }

    public function getLongWaitDetail(array $filters = []): array
    {
        $reportDate = $this->reportDate($filters);
        $depotId = $this->depotId();
        if (!$depotId) {
            return ['buckets' => [], 'buses' => [], 'reportDate' => $reportDate];
        }

        $params = [':report_date' => $reportDate, ':depot_id' => $depotId];
        $routeClause = '';
        $busClause = '';
        if (!empty($filters['route_no'])) {
            $routeClause = " AND EXISTS (SELECT 1 FROM routes rr WHERE rr.route_id = x.route_id AND rr.route_no = :route_no)";
            $params[':route_no'] = $filters['route_no'];
        }
        if (!empty($filters['bus_reg'])) {
            $busClause = ' AND x.bus_reg_no = :bus_reg';
            $params[':bus_reg'] = $filters['bus_reg'];
        }

        $sqlBuckets = "SELECT
                SUM(CASE WHEN x.avg_delay_min < 5 THEN 1 ELSE 0 END) AS under_5,
                SUM(CASE WHEN x.avg_delay_min >= 5 AND x.avg_delay_min < 10 THEN 1 ELSE 0 END) AS b5_10,
                SUM(CASE WHEN x.avg_delay_min >= 10 AND x.avg_delay_min < 15 THEN 1 ELSE 0 END) AS b10_15,
                SUM(CASE WHEN x.avg_delay_min >= 15 THEN 1 ELSE 0 END) AS over_15,
                COUNT(*) AS total,
                ROUND(AVG(COALESCE(x.avg_delay_min,0)),1) AS avg_delay
            FROM tracking_monitoring x
            JOIN sltb_buses sb ON sb.reg_no = x.bus_reg_no AND sb.sltb_depot_id = :depot_id
            WHERE x.operator_type='SLTB'
              AND DATE(x.snapshot_at) = :report_date
              $routeClause
              $busClause";

        $sqlBuses = "SELECT
                x.bus_reg_no,
                COALESCE(r.route_no, '-') AS route_no,
                COALESCE(d.full_name, '-') AS driver_name,
                ROUND(COALESCE(x.avg_delay_min, 0), 1) AS avg_delay_min,
                COALESCE(x.operational_status, 'Unknown') AS operational_status,
                ROUND(COALESCE(x.speed, 0), 1) AS speed,
                DATE_FORMAT(x.snapshot_at, '%Y-%m-%d %H:%i') AS snapshot_at
            FROM (
                SELECT tm.*,
                       ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                FROM tracking_monitoring tm
                JOIN sltb_buses sb ON sb.reg_no = tm.bus_reg_no AND sb.sltb_depot_id = :depot_id
                WHERE tm.operator_type = 'SLTB'
                  AND DATE(tm.snapshot_at) = :report_date
                  AND tm.avg_delay_min >= 10
                  $routeClause
                  $busClause
            ) x
            LEFT JOIN routes r ON r.route_id = x.route_id
            LEFT JOIN sltb_drivers d ON d.sltb_driver_id = x.driver_id
            WHERE x.rn = 1
            ORDER BY x.avg_delay_min DESC
            LIMIT 100";

        try {
            $st = $this->pdo->prepare($sqlBuckets);
            $st->execute($params);
            $buckets = $st->fetch(PDO::FETCH_ASSOC) ?: [];

            $st = $this->pdo->prepare($sqlBuses);
            $st->execute($params);
            $buses = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return ['buckets' => $buckets, 'buses' => $buses, 'reportDate' => $reportDate];
        } catch (PDOException $e) {
            return ['buckets' => [], 'buses' => [], 'reportDate' => $reportDate];
        }
    }
}

