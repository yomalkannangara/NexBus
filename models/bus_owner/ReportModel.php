<?php
namespace App\models\bus_owner;

use PDO;
use App\models\common\BaseModel; // same as used in other bus_owner models

class ReportModel extends BaseModel
{
    /** 
     * Resolve the current operator (private bus owner) from session 
     */
    private function operatorId(): ?int
    {
        $u = $_SESSION['user'] ?? null;
        return isset($u['private_operator_id']) ? (int)$u['private_operator_id'] : null;
    }

    /** 
     * Check if the current user has a private operator ID 
     */
    private function hasOperator(): bool
    {
        return (bool)$this->operatorId();
    }

    private function parseReportDate(?string $rawDate): ?string
    {
        if (!$rawDate) {
            return null;
        }
        $dt = \DateTime::createFromFormat('Y-m-d', trim($rawDate));
        if (!$dt || $dt->format('Y-m-d') !== trim($rawDate)) {
            return null;
        }
        return $dt->format('Y-m-d');
    }

    private function resolveTrackingDate(array $filters = []): string
    {
        $requested = $this->parseReportDate($filters['date'] ?? null);
        if ($requested) {
            return $requested;
        }

        $params = [];
        $opClause = '';
        $routeClause = '';
        $busClause = '';

        if ($this->hasOperator()) {
            $opClause = ' AND b.private_operator_id = :op';
            $params[':op'] = $this->operatorId();
        }
        if (!empty($filters['route_no'])) {
            $routeClause = " AND EXISTS (SELECT 1 FROM routes r WHERE r.route_id = tm.route_id AND r.route_no = :route_no)";
            $params[':route_no'] = $filters['route_no'];
        }
        if (!empty($filters['bus_reg'])) {
            $busClause = ' AND tm.bus_reg_no = :bus_reg';
            $params[':bus_reg'] = $filters['bus_reg'];
        }

        $sql = "SELECT MAX(DATE(tm.snapshot_at))
                FROM tracking_monitoring tm
                JOIN private_buses b ON b.reg_no = tm.bus_reg_no
                WHERE tm.operator_type='Private' $opClause $routeClause $busClause";

        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $date = $st->fetchColumn();
            if (!empty($date)) {
                return (string)$date;
            }
        } catch (\Throwable $e) {
            // Fall through to current date.
        }

        return date('Y-m-d');
    }

    /**
     * Compute key performance metrics using tracking_monitoring
     */
    public function getPerformanceMetrics(array $filters = []): array
    {
        $metrics = [
            'delayed_buses'    => 0,
            'average_rating'   => null,
            'speed_violations' => 0,
            'long_wait_rate'   => 0,
            'total_complaints' => 0,
        ];

        $params = [];
        $opClause = '';
        $reportDate = $this->resolveTrackingDate($filters);
        if ($this->hasOperator()) {
            $opClause = " AND b.private_operator_id = :op";
            $params[':op'] = $this->operatorId();
        }

        // Optional route filter (join routes table via route_id)
        $routeClause = '';
        if (!empty($filters['route_no'])) {
            $routeClause = " AND EXISTS (SELECT 1 FROM routes r WHERE r.route_id = tm.route_id AND r.route_no = :route_no)";
            $params[':route_no'] = $filters['route_no'];
        }

        // Optional bus filter
        $busClause = '';
        if (!empty($filters['bus_reg'])) {
            $busClause = " AND tm.bus_reg_no = :bus_reg";
            $params[':bus_reg'] = $filters['bus_reg'];
        }
        $params[':report_date'] = $reportDate;

         // 1. Delayed buses — based on latest snapshot per bus
         $sql = "SELECT COUNT(*) FROM (
                  SELECT tm.bus_reg_no,
                      tm.operational_status,
                      ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                  FROM tracking_monitoring tm
                  JOIN private_buses b ON b.reg_no = tm.bus_reg_no AND b.status = 'Active'
                  WHERE tm.operator_type='Private'
                              AND DATE(tm.snapshot_at)=:report_date
                 $opClause $routeClause $busClause
              ) latest
              WHERE latest.rn = 1 AND latest.operational_status = 'Delayed'";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $metrics['delayed_buses'] = (int)$st->fetchColumn();

        // 2. Speed violations
        $sql = "SELECT COALESCE(SUM(tm.speed_violations),0)
                FROM tracking_monitoring tm
                JOIN private_buses b ON b.reg_no = tm.bus_reg_no AND b.status = 'Active'
                WHERE tm.operator_type='Private'
                                    AND DATE(tm.snapshot_at)=:report_date $opClause $routeClause $busClause";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $metrics['speed_violations'] = (int)$st->fetchColumn();

        // 3. Average reliability index
        $sql = "SELECT AVG(tm.reliability_index)
                FROM tracking_monitoring tm
                JOIN private_buses b ON b.reg_no = tm.bus_reg_no AND b.status = 'Active'
                WHERE tm.operator_type='Private'
                                    AND DATE(tm.snapshot_at)=:report_date $opClause $routeClause $busClause";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $avg = $st->fetchColumn();
        $metrics['average_rating'] = $avg !== null ? round((float)$avg, 1) : null;

        // 4. Long wait rate
        $sql = "SELECT 
                    SUM(CASE WHEN tm.avg_delay_min>=10 THEN 1 ELSE 0 END) AS long_wait,
                    COUNT(*) AS total
                FROM tracking_monitoring tm
                JOIN private_buses b ON b.reg_no = tm.bus_reg_no AND b.status = 'Active'
                WHERE tm.operator_type='Private'
                                    AND DATE(tm.snapshot_at)=:report_date $opClause $routeClause $busClause";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['long_wait' => 0, 'total' => 0];
        $metrics['long_wait_rate'] = ($row['total'] > 0)
            ? round(($row['long_wait'] / $row['total']) * 100)
            : 0;

        // 5. Total complaints (best-effort across common table names)
        $metrics['total_complaints'] = $this->countComplaints();

        return $metrics;
    }

    /**
     * Try to count complaints for current operator across likely feedback tables.
     * Adjust table/column names if your schema differs.
     */
    private function countComplaints(): int
    {
        $op = $this->operatorId();
        // Primary source in this schema
        try {
            $sql = "SELECT COUNT(*)
                    FROM complaints c
                    JOIN private_buses pb ON pb.reg_no = c.bus_reg_no
                    WHERE c.operator_type='Private'
                      AND LOWER(COALESCE(c.category, ''))='complaint'";
            $params = [];
            if ($op) {
                $sql .= " AND pb.private_operator_id = :op";
                $params[':op'] = $op;
            }
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            // fall through to legacy candidates
        }

        $candidates = [
            // table => operator column candidates (first found used)
            'passenger_feedback' => ['private_operator_id', 'operator_id'],
            'feedback'           => ['private_operator_id', 'operator_id'],
        ];

        foreach ($candidates as $table => $opCols) {
            try {
                $params = [];
                $where = "LOWER(type) = 'complaint'";
                if ($op) {
                    // Prefer first matching operator column that exists
                    foreach ($opCols as $col) {
                        // Try query with this operator column; fall back if it fails
                        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where} AND {$col} = :op";
                        $st = $this->pdo->prepare($sql);
                        $st->execute([':op' => $op]);
                        return (int)$st->fetchColumn();
                    }
                }
                // No operator filter
                $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
                $st = $this->pdo->prepare($sql);
                $st->execute($params);
                return (int)$st->fetchColumn();
            } catch (\Throwable $e) {
                // Try next candidate table
            }
        }

        return 0;
    }

    /** 
     * List top drivers for current operator 
     */
    public function topDrivers(int $limit = 5): array
    {
        $sql = "SELECT private_driver_id, full_name, status
                FROM private_drivers";
        $params = [];
        if ($this->hasOperator()) {
            $sql .= " WHERE private_operator_id = :op";
            $params[':op'] = $this->operatorId();
        }
        $sql .= " ORDER BY full_name ASC LIMIT " . max(1, (int)$limit);
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Distinct routes that have tracking data for this operator's buses
     */
    public function getOperatorRoutes(): array
    {
        $params = [];
        $opClause = '';
        if ($this->hasOperator()) {
            $opClause = " AND pb.private_operator_id = :op";
            $params[':op'] = $this->operatorId();
        }
        $sql = "SELECT DISTINCT r.route_no
                FROM routes r
                JOIN tracking_monitoring tm ON tm.route_id = r.route_id
                JOIN private_buses pb ON pb.reg_no = tm.bus_reg_no
                WHERE tm.operator_type = 'Private' $opClause
                ORDER BY CAST(r.route_no AS UNSIGNED), r.route_no";
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * All buses belonging to this operator
     */
    public function getOperatorBuses(): array
    {
        $params = [];
        $where = "WHERE 1=1";
        if ($this->hasOperator()) {
            $where .= " AND private_operator_id = :op";
            $params[':op'] = $this->operatorId();
        }
        $sql = "SELECT reg_no FROM private_buses $where ORDER BY reg_no ASC";
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Bus Status distribution — reads directly from private_buses (always accurate). */
    public function getBusStatusData(array $filters = []): array
    {
        try {
            $params    = [];
            $opClause  = '';
            $busClause = '';
            if ($this->hasOperator()) {
                $opClause = ' AND b.private_operator_id = :op';
                $params[':op'] = $this->operatorId();
            }
            if (!empty($filters['bus_reg'])) {
                $busClause = ' AND b.reg_no = :bus_reg';
                $params[':bus_reg'] = $filters['bus_reg'];
            }
            $sql = "SELECT b.status, COUNT(*) AS total
                    FROM private_buses b
                    WHERE 1=1 $opClause $busClause
                    GROUP BY b.status
                    ORDER BY b.status";
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            return array_map(fn($r) => [
                'label'  => ucfirst(strtolower($r['status'] ?? 'Unknown')),
                'value'  => (int)$r['total'],
                'status' => $r['status'],
            ], $st->fetchAll(\PDO::FETCH_ASSOC) ?: []);
        } catch (\Throwable $e) { return []; }
    }

    /** Delayed vs total buses per route. */
    public function getDelayedByRouteData(array $filters = []): array
    {
        try {
            $params    = [];
            $opClause  = '';
            $busClause = '';
            $reportDate = $this->resolveTrackingDate($filters);
            if ($this->hasOperator()) {
                $opClause = ' AND b.private_operator_id = :op';
                $params[':op'] = $this->operatorId();
            }
            if (!empty($filters['bus_reg'])) {
                $busClause = ' AND tm.bus_reg_no = :bus_reg';
                $params[':bus_reg'] = $filters['bus_reg'];
            }
            $params[':report_date'] = $reportDate;
            $sql = "SELECT r.route_no,
                           SUM(CASE WHEN tm.operational_status='Delayed' THEN 1 ELSE 0 END) AS delayed,
                           COUNT(*) AS total
                    FROM tracking_monitoring tm
                    JOIN private_buses b ON b.reg_no = tm.bus_reg_no
                    LEFT JOIN routes r ON r.route_id = tm.route_id
                    WHERE tm.operator_type='Private'
                      AND DATE(tm.snapshot_at)=:report_date $opClause $busClause
                    GROUP BY r.route_id, r.route_no
                    ORDER BY total DESC LIMIT 8";
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $labels = $delayed = $total = [];
            foreach ($rows as $r) {
                $labels[]  = $r['route_no'] ?? 'Unknown';
                $delayed[] = (int)$r['delayed'];
                $total[]   = (int)$r['total'];
            }
            return ['labels' => $labels, 'delayed' => $delayed, 'total' => $total];
        } catch (\Throwable $e) { return ['labels' => [], 'delayed' => [], 'total' => []]; }
    }

    /** Speed violations per bus. */
    public function getSpeedByBusData(array $filters = []): array
    {
        try {
            $params   = [];
            $opClause = '';
            $reportDate = $this->resolveTrackingDate($filters);
            if ($this->hasOperator()) {
                $opClause = ' AND b.private_operator_id = :op';
                $params[':op'] = $this->operatorId();
            }
            if (!empty($filters['route_no'])) {
                $opClause .= " AND EXISTS (SELECT 1 FROM routes r WHERE r.route_id = tm.route_id AND r.route_no = :route_no)";
                $params[':route_no'] = $filters['route_no'];
            }
            if (!empty($filters['bus_reg'])) {
                $opClause .= ' AND tm.bus_reg_no = :bus_reg';
                $params[':bus_reg'] = $filters['bus_reg'];
            }
            $params[':report_date'] = $reportDate;
            $sql = "SELECT tm.bus_reg_no,
                           SUM(COALESCE(tm.speed_violations, 0)) AS violations
                    FROM tracking_monitoring tm
                    JOIN private_buses b ON b.reg_no = tm.bus_reg_no
                    WHERE tm.operator_type='Private'
                      AND DATE(tm.snapshot_at)=:report_date $opClause
                    GROUP BY tm.bus_reg_no
                    ORDER BY violations DESC LIMIT 9";
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows   = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $labels = $values = [];
            foreach ($rows as $r) {
                $labels[] = $r['bus_reg_no'] ?? 'Unknown';
                $values[] = (int)$r['violations'];
            }
            return ['labels' => $labels, 'values' => $values];
        } catch (\Throwable $e) { return ['labels' => [], 'values' => []]; }
    }

    /** Monthly revenue trend (in millions). */
    public function getRevenueData(array $filters = []): array
    {
        try {
            $params   = [];
            $opClause = '';
            $routeClause = '';
            $busClause = '';
            if ($this->hasOperator()) {
                $opClause = ' AND pb.private_operator_id = :op';
                $params[':op'] = $this->operatorId();
            }
            if (!empty($filters['route_no'])) {
                $routeClause = " AND EXISTS (
                    SELECT 1 FROM routes r2 WHERE r2.route_id = t.route_id AND r2.route_no = :route_no
                )";
                $params[':route_no'] = $filters['route_no'];
            }
            if (!empty($filters['bus_reg'])) {
                $busClause = ' AND e.bus_reg_no = :bus_reg';
                $params[':bus_reg'] = $filters['bus_reg'];
            }
            $sql = "SELECT DATE_FORMAT(tm.snapshot_at, '%b') AS month,
                           SUM(COALESCE(e.amount, 0)) AS revenue,
                           MONTH(e.date) AS month_num
                    FROM earnings e
                    JOIN private_buses pb ON pb.reg_no = e.bus_reg_no
                    LEFT JOIN timetables t ON t.bus_reg_no = e.bus_reg_no AND t.operator_type='Private'
                    WHERE e.operator_type='Private'
                      AND YEAR(e.date)=YEAR(CURDATE()) $opClause $routeClause $busClause
                    GROUP BY MONTH(e.date), DATE_FORMAT(e.date,'%b')
                    ORDER BY month_num";
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows   = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $labels = $values = [];
            foreach ($rows as $r) {
                $labels[] = $r['month'] ?? '';
                $values[] = round((float)($r['revenue'] ?? 0) / 1000000, 1);
            }
            return ['labels' => $labels, 'values' => $values];
        } catch (\Throwable $e) { return ['labels' => [], 'values' => []]; }
    }

    /** Wait-time bucket distribution. */
    public function getWaitTimeData(array $filters = []): array
    {
        try {
            $params    = [];
            $opClause  = '';
            $busClause = '';
            $rtClause  = '';
            $reportDate = $this->resolveTrackingDate($filters);
            if ($this->hasOperator()) {
                $opClause = ' AND b.private_operator_id = :op';
                $params[':op'] = $this->operatorId();
            }
            if (!empty($filters['route_no'])) {
                $rtClause = " AND EXISTS (SELECT 1 FROM routes r WHERE r.route_id = tm.route_id AND r.route_no = :route_no)";
                $params[':route_no'] = $filters['route_no'];
            }
            if (!empty($filters['bus_reg'])) {
                $busClause = ' AND tm.bus_reg_no = :bus_reg';
                $params[':bus_reg'] = $filters['bus_reg'];
            }
            $params[':report_date'] = $reportDate;
            $sql = "SELECT
                        SUM(CASE WHEN tm.avg_delay_min < 5  THEN 1 ELSE 0 END) AS under_5,
                        SUM(CASE WHEN tm.avg_delay_min >= 5  AND tm.avg_delay_min < 10 THEN 1 ELSE 0 END) AS b5_10,
                        SUM(CASE WHEN tm.avg_delay_min >= 10 AND tm.avg_delay_min < 15 THEN 1 ELSE 0 END) AS b10_15,
                        SUM(CASE WHEN tm.avg_delay_min >= 15 THEN 1 ELSE 0 END) AS over_15
                    FROM tracking_monitoring tm
                    JOIN private_buses b ON b.reg_no = tm.bus_reg_no
                    WHERE tm.operator_type='Private'
                      AND DATE(tm.snapshot_at)=:report_date $opClause $rtClause $busClause";
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $row = $st->fetch(\PDO::FETCH_ASSOC) ?: [];
            return [
                ['label' => 'Under 5 min', 'value' => (int)($row['under_5']  ?? 0), 'color' => '#16a34a'],
                ['label' => '5–10 min',    'value' => (int)($row['b5_10']    ?? 0), 'color' => '#84cc16'],
                ['label' => '10–15 min',   'value' => (int)($row['b10_15']   ?? 0), 'color' => '#f3b944'],
                ['label' => 'Over 15 min', 'value' => (int)($row['over_15']  ?? 0), 'color' => '#b91c1c'],
            ];
        } catch (\Throwable $e) { return []; }
    }

    /** Complaints grouped by route. */
    public function getComplaintsByRouteData(array $filters = []): array
    {
        try {
            $params    = [];
            $opClause  = '';
            $busClause = '';
            $routeClause = '';
            if ($this->hasOperator()) {
                $opClause = ' AND pb.private_operator_id = :op';
                $params[':op'] = $this->operatorId();
            }
            if (!empty($filters['bus_reg'])) {
                $busClause = ' AND c.bus_reg_no = :bus_reg';
                $params[':bus_reg'] = $filters['bus_reg'];
            }
            if (!empty($filters['route_no'])) {
                $routeClause = ' AND r.route_no = :route_no';
                $params[':route_no'] = $filters['route_no'];
            }
            $sql = "SELECT r.route_no, COUNT(*) AS cnt
                    FROM complaints c
                    JOIN private_buses pb ON pb.reg_no = c.bus_reg_no
                    LEFT JOIN routes r ON r.route_id = c.route_id
                    WHERE c.operator_type='Private'
                      AND LOWER(COALESCE(c.category, ''))='complaint'
                      AND c.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                      $opClause $busClause $routeClause
                    GROUP BY r.route_id, r.route_no
                    ORDER BY cnt DESC LIMIT 8";
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows   = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $labels = $values = [];
            foreach ($rows as $r) {
                $labels[] = $r['route_no'] ?? 'Unknown';
                $values[] = (int)$r['cnt'];
            }
            return ['labels' => $labels, 'values' => $values];
        } catch (\Throwable $e) { return ['labels' => [], 'values' => []]; }
    }

    /**
     * Detailed delayed-buses-today data for the KPI popup modal.
     * Returns:
     *   routeSummary  — per-route: route_no, total_buses, delayed_buses, avg_speed
     *   delayedBuses  — one row per delayed bus: bus_reg_no, owner_name, route_no,
     *                   operational_status, speed, avg_delay_min, snapshot_at
     */
    public function getDelayedTodayDetail(array $filters = []): array
    {
        $params   = [];
        $opClause = '';
        $routeClause = '';
        $busClause = '';
        $reportDate = $this->resolveTrackingDate($filters);
        if ($this->hasOperator()) {
            $opClause = ' AND b.private_operator_id = :op';
            $params[':op'] = $this->operatorId();
        }
        if (!empty($filters['route_no'])) {
            $routeClause = " AND EXISTS (SELECT 1 FROM routes rr WHERE rr.route_id = tm.route_id AND rr.route_no = :route_no)";
            $params[':route_no'] = $filters['route_no'];
        }
        if (!empty($filters['bus_reg'])) {
            $busClause = ' AND tm.bus_reg_no = :bus_reg';
            $params[':bus_reg'] = $filters['bus_reg'];
        }
        $params[':report_date'] = $reportDate;

        // ── Route-level summary ───────────────────────────────────────────
        $sqlSummary = "SELECT
                COALESCE(r.route_no, 'Unassigned') AS route_no,
                COUNT(DISTINCT x.bus_reg_no) AS total_buses,
                SUM(CASE WHEN x.operational_status = 'Delayed' THEN 1 ELSE 0 END) AS delayed_buses,
                ROUND(AVG(COALESCE(x.speed, 0)), 1) AS avg_speed
            FROM (
                SELECT tm.*,
                       ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                FROM tracking_monitoring tm
                WHERE tm.operator_type = 'Private'
                                    AND DATE(tm.snapshot_at) = :report_date
                                    $routeClause
                                    $busClause
            ) x
            JOIN private_buses b ON b.reg_no = x.bus_reg_no AND b.status = 'Active'
            LEFT JOIN routes r   ON r.route_id = x.route_id
            WHERE x.rn = 1 $opClause
            GROUP BY r.route_id, r.route_no
            ORDER BY delayed_buses DESC, r.route_no ASC";

        // ── Individual delayed bus detail (latest snapshot is Delayed) ─────
        $sqlDetail = "SELECT
                x.bus_reg_no,
                COALESCE(pbo.name, 'Your Fleet') AS owner_name,
                COALESCE(r.route_no, '-') AS route_no,
                COALESCE(x.operational_status, 'Unknown') AS operational_status,
                ROUND(COALESCE(x.speed, 0), 1) AS speed,
                ROUND(COALESCE(x.avg_delay_min, 0), 1) AS avg_delay_min,
                DATE_FORMAT(x.snapshot_at, '%Y-%m-%d %H:%i') AS snapshot_at
            FROM (
                SELECT tm.*,
                       ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                FROM tracking_monitoring tm
                WHERE tm.operator_type = 'Private'
                                    AND DATE(tm.snapshot_at) = :report_date
                                    $routeClause
                                    $busClause
            ) x
            JOIN private_buses b   ON b.reg_no = x.bus_reg_no AND b.status = 'Active'
            LEFT JOIN private_bus_owners pbo ON pbo.private_operator_id = b.private_operator_id
            LEFT JOIN routes r     ON r.route_id = x.route_id
                 WHERE x.rn = 1 AND x.operational_status = 'Delayed' $opClause
            ORDER BY x.avg_delay_min DESC, x.snapshot_at DESC
            LIMIT 200";

        try {
            $st = $this->pdo->prepare($sqlSummary);
            $st->execute($params);
            $routeSummary = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $st = $this->pdo->prepare($sqlDetail);
            $st->execute($params);
            $delayedBuses = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return [
                'routeSummary' => $routeSummary,
                'delayedBuses' => $delayedBuses,
                'reportDate' => $reportDate,
            ];
        } catch (\Throwable $e) {
            return ['routeSummary' => [], 'delayedBuses' => [], 'reportDate' => $reportDate];
        }
    }

    /**
     * Detail data for the Avg Driver Rating KPI popup.
     * Returns per-bus reliability index and driver info for today's snapshots.
     */
    public function getRatingDetail(array $filters = []): array
    {
        $params      = [];
        $opClause    = '';
        $routeClause = '';
        $busClause   = '';
        $reportDate  = $this->resolveTrackingDate($filters);

        if ($this->hasOperator()) {
            $opClause = ' AND b.private_operator_id = :op';
            $params[':op'] = $this->operatorId();
        }
        if (!empty($filters['route_no'])) {
            $routeClause = " AND EXISTS (SELECT 1 FROM routes rr WHERE rr.route_id = x.route_id AND rr.route_no = :route_no)";
            $params[':route_no'] = $filters['route_no'];
        }
        if (!empty($filters['bus_reg'])) {
            $busClause = ' AND x.bus_reg_no = :bus_reg';
            $params[':bus_reg'] = $filters['bus_reg'];
        }
        $params[':report_date'] = $reportDate;

        // --- Query 1: fleet-wide summary (unique buses) ---
        $sqlSummary = "SELECT
                ROUND(AVG(COALESCE(x.reliability_index,0)),1) AS fleet_avg,
                MAX(COALESCE(x.reliability_index,0))           AS best,
                MIN(COALESCE(x.reliability_index,0))           AS worst,
                COUNT(DISTINCT x.bus_reg_no)                   AS bus_count
            FROM tracking_monitoring x
            JOIN private_buses b ON b.reg_no = x.bus_reg_no AND b.status = 'Active'
            WHERE x.operator_type = 'Private'
                AND DATE(x.snapshot_at) = :report_date $opClause $routeClause $busClause";

        // --- Query 2: per-bus day aggregates (one row per unique bus) ---
        $sqlAgg = "SELECT
                x.bus_reg_no,
                ROUND(AVG(COALESCE(x.reliability_index,0)),1)    AS avg_rating,
                ROUND(AVG(COALESCE(x.speed,0)),1)                AS avg_speed,
                COUNT(*)                                          AS snapshots,
                DATE_FORMAT(MAX(x.snapshot_at),'%Y-%m-%d %H:%i') AS last_snapshot
            FROM tracking_monitoring x
            JOIN private_buses b ON b.reg_no = x.bus_reg_no AND b.status = 'Active'
            WHERE x.operator_type = 'Private'
                AND DATE(x.snapshot_at) = :report_date $opClause $routeClause $busClause
            GROUP BY x.bus_reg_no
            ORDER BY avg_rating DESC
            LIMIT 100";

        // --- Query 3: route + driver from each bus's latest snapshot ---
        // Build clauses using 'tm' alias (the inner tracking_monitoring table)
        $opClauseLx    = $this->hasOperator()         ? ' AND pb.private_operator_id = :op' : '';
        $routeClauseLx = !empty($filters['route_no']) ? " AND EXISTS (SELECT 1 FROM routes rr WHERE rr.route_id = tm.route_id AND rr.route_no = :route_no)" : '';
        $busClauseLx   = !empty($filters['bus_reg'])  ? ' AND tm.bus_reg_no = :bus_reg'     : '';
        $paramsLx = [];
        if ($this->hasOperator())         $paramsLx[':op']          = $this->operatorId();
        $paramsLx[':report_date'] = $reportDate;
        if (!empty($filters['route_no'])) $paramsLx[':route_no']    = $filters['route_no'];
        if (!empty($filters['bus_reg']))  $paramsLx[':bus_reg']     = $filters['bus_reg'];

        $sqlLatest = "SELECT x.bus_reg_no,
                COALESCE(r.route_no,'-')    AS route_no,
                COALESCE(d.full_name,'-')   AS driver_name
            FROM (
                SELECT tm.*,
                       ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                FROM tracking_monitoring tm
                JOIN private_buses pb ON pb.reg_no = tm.bus_reg_no AND pb.status = 'Active'
                WHERE tm.operator_type = 'Private'
                    AND DATE(tm.snapshot_at) = :report_date $opClauseLx $routeClauseLx $busClauseLx
            ) x
            JOIN private_buses pb2 ON pb2.reg_no = x.bus_reg_no AND pb2.status = 'Active'
            LEFT JOIN routes r     ON r.route_id  = x.route_id
            LEFT JOIN private_drivers d ON d.private_driver_id = pb2.driver_id
            WHERE x.rn = 1";

        try {
            $st = $this->pdo->prepare($sqlSummary);
            $st->execute($params);
            $summary = $st->fetch(PDO::FETCH_ASSOC) ?: [];

            $st = $this->pdo->prepare($sqlAgg);
            $st->execute($params);
            $aggRows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $st = $this->pdo->prepare($sqlLatest);
            $st->execute($paramsLx);
            $latestRows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Index latest by bus_reg_no for O(1) lookup
            $latestMap = [];
            foreach ($latestRows as $lr) {
                $latestMap[$lr['bus_reg_no']] = $lr;
            }

            // Merge aggregates with latest-snapshot context
            $buses = array_map(function($row) use ($latestMap) {
                $lx = $latestMap[$row['bus_reg_no']] ?? [];
                return [
                    'bus_reg_no'    => $row['bus_reg_no'],
                    'route_no'      => $lx['route_no']    ?? '-',
                    'driver_name'   => $lx['driver_name'] ?? '-',
                    'avg_rating'    => $row['avg_rating'],
                    'avg_speed'     => $row['avg_speed'],
                    'snapshots'     => $row['snapshots'],
                    'last_snapshot' => $row['last_snapshot'],
                ];
            }, $aggRows);

            return ['summary' => $summary, 'buses' => $buses, 'reportDate' => $reportDate];
        } catch (\Throwable $e) {
            return ['summary' => [], 'buses' => [], 'reportDate' => $reportDate];
        }
    }

    /**
     * Detail data for the Speed Violations KPI popup.
     * Returns per-bus speed violation counts and high-speed snapshot details.
     */
    public function getSpeedViolationsDetail(array $filters = []): array
    {
        $params      = [];
        $opClause    = '';
        $routeClause = '';
        $busClause   = '';
        $reportDate  = $this->resolveTrackingDate($filters);

        if ($this->hasOperator()) {
            $opClause = ' AND b.private_operator_id = :op';
            $params[':op'] = $this->operatorId();
        }
        if (!empty($filters['route_no'])) {
            $routeClause = " AND EXISTS (SELECT 1 FROM routes rr WHERE rr.route_id = x.route_id AND rr.route_no = :route_no)";
            $params[':route_no'] = $filters['route_no'];
        }
        if (!empty($filters['bus_reg'])) {
            $busClause = ' AND x.bus_reg_no = :bus_reg';
            $params[':bus_reg'] = $filters['bus_reg'];
        }
        $params[':report_date'] = $reportDate;

        // --- Query 1: fleet summary ---
        $sqlSummary = "SELECT
                SUM(COALESCE(x.speed_violations, 0))   AS total_violations,
                COUNT(DISTINCT x.bus_reg_no)            AS bus_count,
                SUM(CASE WHEN COALESCE(x.speed_violations,0) > 0 THEN 1 ELSE 0 END) AS snapshots_with_viol,
                ROUND(MAX(COALESCE(x.speed,0)),1)       AS fleet_max_speed
            FROM tracking_monitoring x
            JOIN private_buses b ON b.reg_no = x.bus_reg_no AND b.status = 'Active'
            WHERE x.operator_type = 'Private'
                AND DATE(x.snapshot_at) = :report_date $opClause $routeClause $busClause";

        // --- Query 2: per-bus day aggregates (violations grouped by bus only) ---
        $sqlAgg = "SELECT
                x.bus_reg_no,
                SUM(COALESCE(x.speed_violations, 0))              AS total_violations,
                ROUND(MAX(COALESCE(x.speed, 0)), 1)               AS max_speed,
                ROUND(AVG(COALESCE(x.speed, 0)), 1)               AS avg_speed,
                COUNT(*)                                           AS snapshots,
                DATE_FORMAT(MAX(x.snapshot_at),'%Y-%m-%d %H:%i')  AS last_snapshot
            FROM tracking_monitoring x
            JOIN private_buses b ON b.reg_no = x.bus_reg_no AND b.status = 'Active'
            WHERE x.operator_type = 'Private'
                AND DATE(x.snapshot_at) = :report_date $opClause $routeClause $busClause
            GROUP BY x.bus_reg_no
            HAVING total_violations > 0
            ORDER BY total_violations DESC
            LIMIT 100";

        // --- Query 3: route + driver from each bus's latest snapshot ---
        // Build clauses using 'tm' alias (the inner tracking_monitoring table)
        $opClauseLx    = $this->hasOperator()         ? ' AND pb.private_operator_id = :op' : '';
        $routeClauseLx = !empty($filters['route_no']) ? " AND EXISTS (SELECT 1 FROM routes rr WHERE rr.route_id = tm.route_id AND rr.route_no = :route_no)" : '';
        $busClauseLx   = !empty($filters['bus_reg'])  ? ' AND tm.bus_reg_no = :bus_reg'     : '';
        $paramsLx = [];
        if ($this->hasOperator())         $paramsLx[':op']       = $this->operatorId();
        $paramsLx[':report_date'] = $reportDate;
        if (!empty($filters['route_no'])) $paramsLx[':route_no'] = $filters['route_no'];
        if (!empty($filters['bus_reg']))  $paramsLx[':bus_reg']  = $filters['bus_reg'];

        $sqlLatest = "SELECT x.bus_reg_no,
                COALESCE(r.route_no,'-')    AS route_no,
                COALESCE(d.full_name,'-')   AS driver_name
            FROM (
                SELECT tm.*,
                       ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                FROM tracking_monitoring tm
                JOIN private_buses pb ON pb.reg_no = tm.bus_reg_no AND pb.status = 'Active'
                WHERE tm.operator_type = 'Private'
                    AND DATE(tm.snapshot_at) = :report_date $opClauseLx $routeClauseLx $busClauseLx
            ) x
            JOIN private_buses pb2 ON pb2.reg_no = x.bus_reg_no AND pb2.status = 'Active'
            LEFT JOIN routes r     ON r.route_id  = x.route_id
            LEFT JOIN private_drivers d ON d.private_driver_id = pb2.driver_id
            WHERE x.rn = 1";

        try {
            $st = $this->pdo->prepare($sqlSummary);
            $st->execute($params);
            $summary = $st->fetch(PDO::FETCH_ASSOC) ?: [];

            $st = $this->pdo->prepare($sqlAgg);
            $st->execute($params);
            $aggRows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $st = $this->pdo->prepare($sqlLatest);
            $st->execute($paramsLx);
            $latestRows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Index latest by bus_reg_no for O(1) lookup
            $latestMap = [];
            foreach ($latestRows as $lr) {
                $latestMap[$lr['bus_reg_no']] = $lr;
            }

            // Merge aggregates with latest-snapshot context
            $buses = array_map(function($row) use ($latestMap) {
                $lx = $latestMap[$row['bus_reg_no']] ?? [];
                return [
                    'bus_reg_no'       => $row['bus_reg_no'],
                    'route_no'         => $lx['route_no']    ?? '-',
                    'driver_name'      => $lx['driver_name'] ?? '-',
                    'total_violations' => $row['total_violations'],
                    'max_speed'        => $row['max_speed'],
                    'avg_speed'        => $row['avg_speed'],
                    'snapshots'        => $row['snapshots'],
                    'last_snapshot'    => $row['last_snapshot'],
                ];
            }, $aggRows);

            return ['summary' => $summary, 'buses' => $buses, 'reportDate' => $reportDate];
        } catch (\Throwable $e) {
            return ['summary' => [], 'buses' => [], 'reportDate' => $reportDate];
        }
    }

    /**
     * Detail data for the Long Wait Times KPI popup.
     * Returns snapshot-level delay info and per-bus breakdown.
     */
    public function getLongWaitDetail(array $filters = []): array
    {
        $params   = [];
        $opClause = '';
        $routeClauseX = '';
        $busClauseX = '';
        $routeClauseTm = '';
        $busClauseTm = '';
        $reportDate = $this->resolveTrackingDate($filters);
        if ($this->hasOperator()) {
            $opClause = ' AND b.private_operator_id = :op';
            $params[':op'] = $this->operatorId();
        }
        if (!empty($filters['route_no'])) {
            $routeClauseX = " AND EXISTS (SELECT 1 FROM routes rr WHERE rr.route_id = x.route_id AND rr.route_no = :route_no)";
            $routeClauseTm = " AND EXISTS (SELECT 1 FROM routes rr WHERE rr.route_id = tm.route_id AND rr.route_no = :route_no)";
            $params[':route_no'] = $filters['route_no'];
        }
        if (!empty($filters['bus_reg'])) {
            $busClauseX = ' AND x.bus_reg_no = :bus_reg';
            $busClauseTm = ' AND tm.bus_reg_no = :bus_reg';
            $params[':bus_reg'] = $filters['bus_reg'];
        }
        $params[':report_date'] = $reportDate;

        // Bucket counts
        $sqlBuckets = "SELECT
                SUM(CASE WHEN x.avg_delay_min <  5                          THEN 1 ELSE 0 END) AS under_5,
                SUM(CASE WHEN x.avg_delay_min >= 5  AND x.avg_delay_min < 10 THEN 1 ELSE 0 END) AS b5_10,
                SUM(CASE WHEN x.avg_delay_min >= 10 AND x.avg_delay_min < 15 THEN 1 ELSE 0 END) AS b10_15,
                SUM(CASE WHEN x.avg_delay_min >= 15                          THEN 1 ELSE 0 END) AS over_15,
                COUNT(*)                                                      AS total,
                ROUND(AVG(COALESCE(x.avg_delay_min,0)),1)                    AS avg_delay
            FROM tracking_monitoring x
            JOIN private_buses b ON b.reg_no = x.bus_reg_no AND b.status = 'Active'
            WHERE x.operator_type = 'Private'
                            AND DATE(x.snapshot_at) = :report_date $opClause $routeClauseX $busClauseX";

        // Per-bus worst delay (latest snapshot)
        $sqlBuses = "SELECT
                lx.bus_reg_no,
                COALESCE(r.route_no, '-')                           AS route_no,
                COALESCE(d.full_name, '-')                          AS driver_name,
                ROUND(COALESCE(lx.avg_delay_min, 0), 1)            AS avg_delay_min,
                COALESCE(lx.operational_status, 'Unknown')          AS operational_status,
                ROUND(COALESCE(lx.speed, 0), 1)                     AS speed,
                DATE_FORMAT(lx.snapshot_at, '%Y-%m-%d %H:%i')      AS snapshot_at
            FROM (
                SELECT tm.*,
                       ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                FROM tracking_monitoring tm
                WHERE tm.operator_type = 'Private'
                    AND DATE(tm.snapshot_at) = :report_date
                  AND tm.avg_delay_min >= 10
                    $routeClauseTm
                    $busClauseTm
            ) lx
            JOIN private_buses b ON b.reg_no = lx.bus_reg_no AND b.status = 'Active'
            LEFT JOIN routes r   ON r.route_id = lx.route_id
            LEFT JOIN private_drivers d ON d.private_driver_id = b.driver_id
                WHERE lx.rn = 1 $opClause
            ORDER BY lx.avg_delay_min DESC
            LIMIT 100";

        try {
            $st = $this->pdo->prepare($sqlBuckets);
            $st->execute($params);
            $buckets = $st->fetch(PDO::FETCH_ASSOC) ?: [];

            $st = $this->pdo->prepare($sqlBuses);
            $st->execute($params);
            $buses = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return ['buckets' => $buckets, 'buses' => $buses, 'reportDate' => $reportDate];
        } catch (\Throwable $e) {
            return ['buckets' => [], 'buses' => [], 'reportDate' => $reportDate];
        }
    }
}
