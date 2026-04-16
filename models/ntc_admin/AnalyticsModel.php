<?php
namespace App\models\ntc_admin;

use PDO;

class AnalyticsModel extends BaseModel
{
    private ?bool $complaintsHasRating = null;

    /* ────────────────────────────────────────────────────────────────
     * Filter helpers
     * Filters accepted: route_no (string), depot_id (int), owner_id (int)
     * depot_id  → SLTB buses in that SLTB depot
     * owner_id  → Private buses owned by that operator
     * ──────────────────────────────────────────────────────────────── */

    /** Returns [JOIN clause, WHERE clauses array, params array] for tracking_monitoring alias 't' */
    private function tmFilters(array $f): array
    {
        $joins   = [];
        $wheres  = [];
        $params  = [];

        if (!empty($f['depot_id'])) {
            $joins[]             = "JOIN sltb_buses _sb ON _sb.reg_no = t.bus_reg_no";
            $wheres[]            = "_sb.sltb_depot_id = :depot_id";
            $params[':depot_id'] = (int)$f['depot_id'];
        } elseif (!empty($f['owner_id'])) {
            $joins[]             = "JOIN private_buses _pb ON _pb.reg_no = t.bus_reg_no";
            $wheres[]            = "_pb.private_operator_id = :owner_id";
            $params[':owner_id'] = (int)$f['owner_id'];
        }

        if (!empty($f['route_no'])) {
            $joins[]           = "JOIN routes r ON r.route_id = t.route_id";
            $wheres[]          = "CAST(r.route_no AS UNSIGNED) = CAST(:ft_rno AS UNSIGNED)";
            $params[':ft_rno'] = $f['route_no'];
        }
        return [$joins, $wheres, $params];
    }

    /** Same but for earnings alias 'e' */
    private function earningFilters(array $f): array
    {
        $joins  = [];
        $wheres = [];
        $params = [];

        if (!empty($f['depot_id'])) {
            $joins[]             = "JOIN sltb_buses _sb ON _sb.reg_no = e.bus_reg_no";
            $wheres[]            = "_sb.sltb_depot_id = :depot_id";
            $params[':depot_id'] = (int)$f['depot_id'];
        } elseif (!empty($f['owner_id'])) {
            $joins[]             = "JOIN private_buses _pb ON _pb.reg_no = e.bus_reg_no";
            $wheres[]            = "_pb.private_operator_id = :owner_id";
            $params[':owner_id'] = (int)$f['owner_id'];
        }
        return [$joins, $wheres, $params];
    }

    /** Same but for complaints alias 'c' */
    private function complaintFilters(array $f): array
    {
        $joins  = [];
        $wheres = [];
        $params = [];

        if (!empty($f['depot_id'])) {
            $joins[]             = "JOIN sltb_buses _sb ON _sb.reg_no = c.bus_reg_no";
            $wheres[]            = "_sb.sltb_depot_id = :depot_id";
            $params[':depot_id'] = (int)$f['depot_id'];
        } elseif (!empty($f['owner_id'])) {
            $joins[]             = "JOIN private_buses _pb ON _pb.reg_no = c.bus_reg_no";
            $wheres[]            = "_pb.private_operator_id = :owner_id";
            $params[':owner_id'] = (int)$f['owner_id'];
        }

        if (!empty($f['route_no'])) {
            $wheres[]          = "CAST(r.route_no AS UNSIGNED) = CAST(:ft_rno AS UNSIGNED)";
            $params[':ft_rno'] = $f['route_no'];
        }
        return [$joins, $wheres, $params];
    }

    private function complaintsHasRatingColumn(): bool
    {
        if ($this->complaintsHasRating !== null) {
            return $this->complaintsHasRating;
        }

        try {
            $st = $this->pdo->prepare(
                "SELECT COUNT(*) c
                   FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'complaints'
                    AND COLUMN_NAME = 'rating'"
            );
            $st->execute();
            $this->complaintsHasRating = ((int)($st->fetch(PDO::FETCH_ASSOC)['c'] ?? 0)) > 0;
        } catch (\Throwable $e) {
            $this->complaintsHasRating = false;
        }

        return $this->complaintsHasRating;
    }

    /* ─── Bus Status ─────────────────────────────────────────────── */
    public function busStatus(array $f = []): array
    {
        $hasDepot = !empty($f['depot_id']);
        $hasOwner = !empty($f['owner_id']);

        if ($hasDepot) {
            $sql  = "SELECT status, COUNT(*) AS total FROM sltb_buses
                     WHERE sltb_depot_id = :depot_id GROUP BY status ORDER BY status";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':depot_id' => (int)$f['depot_id']]);
        } elseif ($hasOwner) {
            $sql  = "SELECT status, COUNT(*) AS total FROM private_buses
                     WHERE private_operator_id = :owner_id GROUP BY status ORDER BY status";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':owner_id' => (int)$f['owner_id']]);
        } else {
            $sql  = "SELECT status, COUNT(*) AS total
                     FROM (SELECT status FROM private_buses
                           UNION ALL SELECT status FROM sltb_buses) t
                     GROUP BY status ORDER BY status";
            $stmt = $this->pdo->query($sql);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ─── Revenue Trends (last 12 months) ───────────────────────── */
    public function revenueTrends(array $f = []): array
    {
        [$joins, $wheres, $params] = $this->earningFilters($f);
        $wheres[] = "e.date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        $joinSql  = implode(' ', $joins);
        $whereSql = 'WHERE ' . implode(' AND ', $wheres);

                $sql = "SELECT
                                        DATE_FORMAT(
                                            STR_TO_DATE(CONCAT(t.yr,'-',LPAD(t.mo,2,'0'),'-01'), '%Y-%m-%d'),
                                            '%b %Y'
                                        ) AS mon,
                                        t.yr,
                                        t.mo,
                                        t.total_m
                                FROM (
                                        SELECT YEAR(e.date) AS yr,
                                                     MONTH(e.date) AS mo,
                                                     ROUND(SUM(e.amount)/1000000, 2) AS total_m
                                        FROM earnings e $joinSql
                                        $whereSql
                                        GROUP BY YEAR(e.date), MONTH(e.date)
                                ) t
                                ORDER BY t.yr, t.mo";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return [
            'labels' => array_column($rows, 'mon'),
            'values' => array_map('floatval', array_column($rows, 'total_m')),
        ];
    }

    /* ─── Speed Violations by Bus ───────────────────────────────── */
    public function speedByBus(array $f = []): array
    {
        [$joins, $wheres, $params] = $this->tmFilters($f);
        // routes JOIN already added by tmFilters when route_no is set
        $wheres[] = "t.snapshot_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $joinSql  = implode(' ', $joins);
        $whereSql = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';

        $sql = "SELECT t.bus_reg_no, COALESCE(SUM(t.speed_violations),0) AS viols
                FROM tracking_monitoring t $joinSql
                $whereSql
                GROUP BY t.bus_reg_no HAVING viols > 0
                ORDER BY viols DESC LIMIT 10";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            // fallback: raw speed > 60
            $sql2 = "SELECT t.bus_reg_no, COUNT(*) AS viols
                     FROM tracking_monitoring t $joinSql
                     $whereSql AND t.speed > 60
                     GROUP BY t.bus_reg_no ORDER BY viols DESC LIMIT 10";
            $stmt2 = $this->pdo->prepare($sql2);
            $stmt2->execute($params);
            $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
        return [
            'labels' => array_column($rows, 'bus_reg_no'),
            'values' => array_map('intval', array_column($rows, 'viols')),
        ];
    }

    /* ─── Delayed by Route ───────────────────────────────────────── */
    public function delayedByRoute(array $f = []): array
    {
        [$joins, $wheres, $params] = $this->tmFilters($f);
        // routes JOIN is always needed for GROUP BY; tmFilters adds it when route_no is set
        if (empty($f['route_no'])) {
            array_unshift($joins, "JOIN routes r ON r.route_id = t.route_id");
        }
        $wheres[] = "t.snapshot_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $joinSql  = implode(' ', $joins);
        $whereSql = 'WHERE ' . implode(' AND ', $wheres);

        $sql = "SELECT r.route_no,
                       COUNT(*) AS total,
                       SUM(CASE WHEN t.operational_status='Delayed' THEN 1 ELSE 0 END) AS `delayed_count`
                FROM tracking_monitoring t $joinSql
                $whereSql
                GROUP BY r.route_no
                ORDER BY `delayed_count` DESC LIMIT 10";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return [
            'labels'  => array_column($rows, 'route_no'),
            'delayed' => array_map('intval', array_column($rows, 'delayed_count')),
            'total'   => array_map('intval', array_column($rows, 'total')),
        ];
    }

    /* ─── Wait Time Distribution ─────────────────────────────────── */
    public function waitTimeDistribution(array $f = []): array
    {
        [$joins, $wheres, $params] = $this->tmFilters($f);
        // routes JOIN already added by tmFilters when route_no is set
        $wheres[] = "t.snapshot_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $joinSql  = implode(' ', $joins);
        $whereSql = 'WHERE ' . implode(' AND ', $wheres);

        $sql = "SELECT
                  SUM(CASE WHEN t.avg_delay_min IS NULL OR t.avg_delay_min < 5  THEN 1 ELSE 0 END) AS under5,
                  SUM(CASE WHEN t.avg_delay_min >= 5  AND t.avg_delay_min < 10  THEN 1 ELSE 0 END) AS five10,
                  SUM(CASE WHEN t.avg_delay_min >= 10 AND t.avg_delay_min < 15  THEN 1 ELSE 0 END) AS ten15,
                  SUM(CASE WHEN t.avg_delay_min >= 15 AND t.avg_delay_min < 20  THEN 1 ELSE 0 END) AS fif20,
                  SUM(CASE WHEN t.avg_delay_min >= 20                           THEN 1 ELSE 0 END) AS over20,
                  COUNT(*) AS grand
                FROM tracking_monitoring t $joinSql
                $whereSql";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        $g = max(1, (int)($r['grand'] ?? 1));
        return [
            ['label' => 'Under 5 min', 'value' => round(($r['under5'] ?? 0) / $g * 100), 'color' => '#16a34a'],
            ['label' => '5–10 min',    'value' => round(($r['five10'] ?? 0) / $g * 100), 'color' => '#84cc16'],
            ['label' => '10–15 min',   'value' => round(($r['ten15']  ?? 0) / $g * 100), 'color' => '#f3b944'],
            ['label' => '15–20 min',   'value' => round(($r['fif20']  ?? 0) / $g * 100), 'color' => '#f59e0b'],
            ['label' => 'Over 20 min', 'value' => round(($r['over20'] ?? 0) / $g * 100), 'color' => '#b91c1c'],
        ];
    }

    /* ─── Complaints by Route ────────────────────────────────────── */
    public function complaintsByRoute(array $f = []): array
    {
        [$joins, $wheres, $params] = $this->complaintFilters($f);
        array_unshift($joins, "JOIN routes r ON r.route_id = c.route_id");
        $wheres[] = "c.category = 'complaint'";
        $joinSql  = implode(' ', $joins);
        $whereSql = 'WHERE ' . implode(' AND ', $wheres);

        $sql = "SELECT r.route_no, COUNT(*) AS cnt
                FROM complaints c $joinSql
                $whereSql
                GROUP BY r.route_no ORDER BY cnt DESC LIMIT 10";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return [
            'labels' => array_column($rows, 'route_no'),
            'values' => array_map('intval', array_column($rows, 'cnt')),
        ];
    }

    /* ─── KPI: average rating (/10) ─────────────────────────────── */
    public function avgRating(array $f = []): float
    {
        try {
            [$joins, $wheres, $params] = $this->complaintFilters($f);
            if (!empty($f['route_no'])) {
                array_unshift($joins, "JOIN routes r ON r.route_id = c.route_id");
            }

            $joinSql = implode(' ', $joins);

            // Primary source: explicit passenger ratings when the column exists.
            if ($this->complaintsHasRatingColumn()) {
                $ratingWheres = array_merge(
                    $wheres,
                    [
                        "c.rating IS NOT NULL",
                        "c.rating BETWEEN 1 AND 5",
                        "LOWER(COALESCE(c.category,'')) IN ('feedback','complaint')",
                    ]
                );

                $whereSql = $ratingWheres ? ('WHERE ' . implode(' AND ', $ratingWheres)) : '';
                $st = $this->pdo->prepare(
                    "SELECT ROUND(AVG(c.rating) * 2, 1) AS avg_r
                     FROM complaints c $joinSql
                     $whereSql"
                );
                $st->execute($params);
                $r = $st->fetch(PDO::FETCH_ASSOC);
                if ($r && $r['avg_r'] !== null) {
                    return (float)$r['avg_r'];
                }
            }

            // Fallback when explicit ratings are unavailable: sentiment mix from complaint/feedback entries.
            $mixWheres = array_merge($wheres, ["LOWER(COALESCE(c.category,'')) IN ('feedback','complaint')"]);
            $mixWhereSql = $mixWheres ? ('WHERE ' . implode(' AND ', $mixWheres)) : '';
            $mixStmt = $this->pdo->prepare(
                "SELECT
                    SUM(CASE WHEN LOWER(COALESCE(c.category,''))='feedback' THEN 1 ELSE 0 END) AS feedback_count,
                    SUM(CASE WHEN LOWER(COALESCE(c.category,''))='complaint' THEN 1 ELSE 0 END) AS complaint_count
                 FROM complaints c $joinSql
                 $mixWhereSql"
            );
            $mixStmt->execute($params);
            $mix = $mixStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $feedbackCount = (int)($mix['feedback_count'] ?? 0);
            $complaintCount = (int)($mix['complaint_count'] ?? 0);
            $total = $feedbackCount + $complaintCount;

            if ($total <= 0) {
                return 0.0;
            }

            return round(($feedbackCount / $total) * 10, 1);
        } catch (\PDOException $e) {
            return 0.0;
        }
    }

    /* ─── KPI: delayed today ─────────────────────────────────────── */
    public function delayedToday(array $f = []): int
    {
        [$joins, $wheres, $params] = $this->tmFilters($f);
        $wheres[] = "DATE(t.snapshot_at) = CURDATE()";
        $joinSql  = implode(' ', array_map(fn($j) => str_replace(' t.', ' t.', $j), $joins));
        $whereSql = 'WHERE ' . implode(' AND ', $wheres);
        // Count buses by their LATEST snapshot status only (not every row)
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) c
             FROM (
                 SELECT t.bus_reg_no,
                        t.operational_status,
                        ROW_NUMBER() OVER (PARTITION BY t.bus_reg_no ORDER BY t.snapshot_at DESC) AS rn
                 FROM tracking_monitoring t $joinSql $whereSql
             ) latest
             WHERE latest.rn = 1 AND latest.operational_status = 'Delayed'"
        );
        $stmt->execute($params);
        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    }

    /* ─── KPI: speed violations today ───────────────────────────── */
    public function speedViolationsToday(array $f = []): int
    {
        [$joins, $wheres, $params] = $this->tmFilters($f);
        $wheres[] = "DATE(t.snapshot_at) = CURDATE()";
        $joinSql  = implode(' ', $joins);
        $whereSql = 'WHERE ' . implode(' AND ', $wheres);
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(t.speed_violations),0) c FROM tracking_monitoring t $joinSql $whereSql"
        );
        $stmt->execute($params);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r['c']) {
            $stmt2 = $this->pdo->prepare(
                "SELECT COUNT(*) c FROM tracking_monitoring t $joinSql $whereSql AND t.speed > 60"
            );
            $stmt2->execute($params);
            $r = $stmt2->fetch(PDO::FETCH_ASSOC);
        }
        return (int)($r['c'] ?? 0);
    }

    /* ─── KPI: long wait % ───────────────────────────────────────── */
    public function longWaitPct(array $f = []): int
    {
        [$joins, $wheres, $params] = $this->tmFilters($f);
        $wheres[] = "DATE(t.snapshot_at) = CURDATE()";
        $joinSql  = implode(' ', $joins);
        $whereSql = 'WHERE ' . implode(' AND ', $wheres);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) total,
                    SUM(CASE WHEN t.avg_delay_min > 10 THEN 1 ELSE 0 END) over10
             FROM tracking_monitoring t $joinSql $whereSql"
        );
        $stmt->execute($params);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r || !$r['total']) return 0;
        return (int)round($r['over10'] / $r['total'] * 100);
    }

    /* ─── Admin Drilldown Builder ───────────────────────────────── */
    public function sanitizeDetailFilters(array $raw = []): array
    {
        $routeNo = trim((string)($raw['route_no'] ?? ''));
        $depotId = max(0, (int)($raw['depot_id'] ?? 0));
        $ownerId = max(0, (int)($raw['owner_id'] ?? 0));
        if ($depotId > 0) {
            $ownerId = 0;
        }

        $busReg = trim((string)($raw['bus_reg'] ?? ''));
        $status = trim((string)($raw['status'] ?? ''));

        $to = $this->normalizeDetailDate((string)($raw['to'] ?? '')) ?: date('Y-m-d');
        $from = $this->normalizeDetailDate((string)($raw['from'] ?? ''));
        if ($from === null) {
            $from = date('Y-m-d', strtotime($to . ' -29 days'));
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $maxSpanDays = 180;
        $span = (int)floor((strtotime($to) - strtotime($from)) / 86400);
        if ($span > $maxSpanDays) {
            $from = date('Y-m-d', strtotime($to . " -{$maxSpanDays} days"));
        }

        return [
            'route_no' => $routeNo,
            'depot_id' => $depotId,
            'owner_id' => $ownerId,
            'bus_reg' => $busReg,
            'status' => $status,
            'from' => $from,
            'to' => $to,
        ];
    }

    public function buildAdminDetailPayload(string $chart, array $rawFilters = []): array
    {
        $f = $this->sanitizeDetailFilters($rawFilters);

        $normalizedChart = match ($chart) {
            'live_status', 'live_speed' => 'live_speed',
            'bus_status' => 'bus_status',
            'delayed_by_route' => 'delayed',
            'speed_by_bus' => 'speed_violations',
            'revenue' => 'revenue',
            'wait_time' => 'wait_time',
            'complaints_by_route' => 'complaints',
            default => 'bus_status',
        };

        $payload = match ($normalizedChart) {
            'live_speed' => $this->buildLiveSpeedPayload($f),
            'bus_status' => $this->buildBusStatusPayload($f),
            'delayed' => $this->buildDelayedPayload($f),
            'speed_violations' => $this->buildSpeedViolationPayload($f),
            'revenue' => $this->buildRevenuePayload($f),
            'wait_time' => $this->buildWaitTimePayload($f),
            'complaints' => $this->buildComplaintsPayload($f),
            default => $this->buildBusStatusPayload($f),
        };

        $payload['chart'] = $normalizedChart;
        $payload['filters'] = $f;

        return $payload;
    }

    private function normalizeDetailDate(string $value): ?string
    {
        $v = trim($value);
        if ($v === '') {
            return null;
        }
        $dt = \DateTime::createFromFormat('Y-m-d', $v);
        if (!$dt || $dt->format('Y-m-d') !== $v) {
            return null;
        }
        return $v;
    }

    private function trackingScope(array $f, string $alias = 'tm', bool $includeStatus = true): array
    {
        $joins = [
            "LEFT JOIN routes r ON r.route_id = {$alias}.route_id",
            "LEFT JOIN sltb_buses sb ON sb.reg_no = {$alias}.bus_reg_no",
            "LEFT JOIN sltb_depots d ON d.sltb_depot_id = sb.sltb_depot_id",
            "LEFT JOIN private_buses pb ON pb.reg_no = {$alias}.bus_reg_no",
            "LEFT JOIN private_bus_owners pbo ON pbo.private_operator_id = pb.private_operator_id",
        ];

        $where = ["DATE({$alias}.snapshot_at) BETWEEN :from AND :to"];
        $params = [
            ':from' => $f['from'],
            ':to' => $f['to'],
        ];

        if ($f['route_no'] !== '') {
            $where[] = "(r.route_no = :route_no OR CAST(COALESCE(r.route_no,'0') AS UNSIGNED) = CAST(:route_no AS UNSIGNED))";
            $params[':route_no'] = $f['route_no'];
        }
        if (!empty($f['depot_id'])) {
            $where[] = 'sb.sltb_depot_id = :depot_id';
            $params[':depot_id'] = (int)$f['depot_id'];
        }
        if (!empty($f['owner_id'])) {
            $where[] = 'pb.private_operator_id = :owner_id';
            $params[':owner_id'] = (int)$f['owner_id'];
        }
        if ($f['bus_reg'] !== '') {
            $where[] = "{$alias}.bus_reg_no LIKE :bus_reg";
            $params[':bus_reg'] = '%' . $f['bus_reg'] . '%';
        }
        if ($includeStatus && $f['status'] !== '') {
            $where[] = "{$alias}.operational_status = :op_status";
            $params[':op_status'] = $f['status'];
        }

        return [$joins, $where, $params];
    }

    private function latestSnapshotRows(array $f): array
    {
        [$joins, $where, $params] = $this->trackingScope($f, 'x', true);
        $dateWindowClause = 'DATE(x.snapshot_at) BETWEEN :from AND :to';
        $where = array_values(array_filter(
            $where,
            static fn(string $clause): bool => $clause !== $dateWindowClause
        ));
        $where[] = 'x.rn = 1';

        $sql = "SELECT
                    x.bus_reg_no,
                    COALESCE(r.route_no, '-') AS route_no,
                    COALESCE(x.operator_type, CASE WHEN sb.reg_no IS NOT NULL THEN 'SLTB' ELSE 'Private' END) AS operator_type,
                    COALESCE(x.operational_status, 'Unknown') AS operational_status,
                    ROUND(COALESCE(x.speed, 0), 1) AS speed,
                    ROUND(COALESCE(x.avg_delay_min, 0), 1) AS avg_delay_min,
                    x.snapshot_at,
                    COALESCE(d.name, '') AS depot_name,
                    COALESCE(pbo.name, '') AS owner_name,
                    COALESCE(NULLIF(d.name, ''), NULLIF(pbo.name, ''), 'Unassigned') AS entity_name
                FROM (
                    SELECT tm.*,
                           ROW_NUMBER() OVER (PARTITION BY tm.bus_reg_no ORDER BY tm.snapshot_at DESC) AS rn
                    FROM tracking_monitoring tm
                    WHERE DATE(tm.snapshot_at) BETWEEN :from AND :to
                ) x
                " . implode(' ', $joins) . "
                WHERE " . implode(' AND ', $where) . "
                ORDER BY x.snapshot_at DESC";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function runTrackingAggregate(
        array $f,
        string $selectSql,
        string $groupBySql = '',
        string $orderBySql = '',
        bool $includeStatus = true,
        string $extraWhere = '',
        int $limit = 0
    ): array {
        [$joins, $where, $params] = $this->trackingScope($f, 'tm', $includeStatus);
        if ($extraWhere !== '') {
            $where[] = $extraWhere;
        }

        $sql = "SELECT {$selectSql}
                FROM tracking_monitoring tm
                " . implode(' ', $joins) . "
                WHERE " . implode(' AND ', $where);

        if ($groupBySql !== '') {
            $sql .= " GROUP BY {$groupBySql}";
        }
        if ($orderBySql !== '') {
            $sql .= " ORDER BY {$orderBySql}";
        }
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function revenueRows(array $f): array
    {
        $where = ['e.date BETWEEN :from AND :to'];
        $params = [
            ':from' => $f['from'],
            ':to' => $f['to'],
        ];

        if ($f['route_no'] !== '') {
            $where[] = "(r.route_no = :route_no OR CAST(COALESCE(r.route_no,'0') AS UNSIGNED) = CAST(:route_no AS UNSIGNED))";
            $params[':route_no'] = $f['route_no'];
        }
        if (!empty($f['depot_id'])) {
            $where[] = 'sb.sltb_depot_id = :depot_id';
            $params[':depot_id'] = (int)$f['depot_id'];
        }
        if (!empty($f['owner_id'])) {
            $where[] = 'pb.private_operator_id = :owner_id';
            $params[':owner_id'] = (int)$f['owner_id'];
        }
        if ($f['bus_reg'] !== '') {
            $where[] = 'e.bus_reg_no LIKE :bus_reg';
            $params[':bus_reg'] = '%' . $f['bus_reg'] . '%';
        }

        $sql = "SELECT
                    e.date AS day,
                    e.bus_reg_no,
                    COALESCE(r.route_no, '-') AS route_no,
                    COALESCE(d.name, '') AS depot_name,
                    COALESCE(pbo.name, '') AS owner_name,
                    CAST(e.amount AS DECIMAL(14,2)) AS amount
                FROM earnings e
                LEFT JOIN (
                    SELECT t1.bus_reg_no, MAX(t1.timetable_id) AS timetable_id
                    FROM timetables t1
                    GROUP BY t1.bus_reg_no
                ) tmx ON tmx.bus_reg_no = e.bus_reg_no
                LEFT JOIN timetables tt ON tt.timetable_id = tmx.timetable_id
                LEFT JOIN routes r ON r.route_id = tt.route_id
                LEFT JOIN sltb_buses sb ON sb.reg_no = e.bus_reg_no
                LEFT JOIN sltb_depots d ON d.sltb_depot_id = sb.sltb_depot_id
                LEFT JOIN private_buses pb ON pb.reg_no = e.bus_reg_no
                LEFT JOIN private_bus_owners pbo ON pbo.private_operator_id = pb.private_operator_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY e.date ASC";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function complaintRows(array $f, bool $excludeFeedback = true): array
    {
        $where = ['DATE(c.created_at) BETWEEN :from AND :to'];
        $params = [
            ':from' => $f['from'],
            ':to' => $f['to'],
        ];

        if ($excludeFeedback) {
            $where[] = "LOWER(COALESCE(c.category,'')) <> 'feedback'";
        }
        if ($f['route_no'] !== '') {
            $where[] = "(r.route_no = :route_no OR CAST(COALESCE(r.route_no,'0') AS UNSIGNED) = CAST(:route_no AS UNSIGNED))";
            $params[':route_no'] = $f['route_no'];
        }
        if (!empty($f['depot_id'])) {
            $where[] = 'sb.sltb_depot_id = :depot_id';
            $params[':depot_id'] = (int)$f['depot_id'];
        }
        if (!empty($f['owner_id'])) {
            $where[] = 'pb.private_operator_id = :owner_id';
            $params[':owner_id'] = (int)$f['owner_id'];
        }
        if ($f['bus_reg'] !== '') {
            $where[] = 'c.bus_reg_no LIKE :bus_reg';
            $params[':bus_reg'] = '%' . $f['bus_reg'] . '%';
        }

        $sql = "SELECT
                    DATE(c.created_at) AS day,
                    COALESCE(r.route_no, '-') AS route_no,
                    COALESCE(d.name, '') AS depot_name,
                    COALESCE(pbo.name, '') AS owner_name,
                    COALESCE(TRIM(LOWER(c.category)), 'uncategorized') AS category,
                    COALESCE(c.status, 'Open') AS status,
                    COALESCE(c.bus_reg_no, '') AS bus_reg_no
                FROM complaints c
                LEFT JOIN routes r ON r.route_id = c.route_id
                LEFT JOIN sltb_buses sb ON sb.reg_no = c.bus_reg_no
                LEFT JOIN sltb_depots d ON d.sltb_depot_id = sb.sltb_depot_id
                LEFT JOIN private_buses pb ON pb.reg_no = c.bus_reg_no
                LEFT JOIN private_bus_owners pbo ON pbo.private_operator_id = pb.private_operator_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.created_at ASC";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function serviceBucket(string $status): string
    {
        $s = strtolower(trim($status));
        if (in_array($s, ['ontime', 'on time', 'delayed', 'active'], true)) {
            return 'Active';
        }
        if (in_array($s, ['breakdown', 'maintenance'], true)) {
            return 'Maintenance';
        }
        return 'Out of Service';
    }

    private function mapPairs(array $map, int $limit = 0, bool $asc = false): array
    {
        if ($asc) {
            asort($map, SORT_NUMERIC);
        } else {
            arsort($map, SORT_NUMERIC);
        }

        if ($limit > 0) {
            $map = array_slice($map, 0, $limit, true);
        }

        $out = [];
        foreach ($map as $label => $value) {
            $out[] = [
                'label' => (string)$label,
                'value' => round((float)$value, 2),
            ];
        }
        return $out;
    }

    private function topLabel(array $map): ?array
    {
        if (empty($map)) {
            return null;
        }
        arsort($map, SORT_NUMERIC);
        $label = array_key_first($map);
        if ($label === null) {
            return null;
        }
        return ['label' => (string)$label, 'value' => (float)$map[$label]];
    }

    private function buildRanking(string $title, array $items, string $tone = 'neutral', string $valueSuffix = ''): array
    {
        return [
            'title' => $title,
            'tone' => $tone,
            'valueSuffix' => $valueSuffix,
            'items' => $items,
        ];
    }

    private function formatShortDateLabel(string $date): string
    {
        $ts = strtotime($date);
        if ($ts === false) {
            return $date;
        }
        return date('M d', $ts);
    }

    private function rangeDays(string $from, string $to): int
    {
        $span = (int)floor((strtotime($to) - strtotime($from)) / 86400);
        return max(1, $span + 1);
    }

    private function buildExportRows(array $rankings, array $kpis): array
    {
        $rows = [];
        foreach ($kpis as $kpi) {
            $rows[] = [
                'section' => 'KPI',
                'label' => (string)($kpi['title'] ?? ''),
                'value' => (string)($kpi['value'] ?? ''),
            ];
        }
        foreach ($rankings as $ranking) {
            foreach (($ranking['items'] ?? []) as $item) {
                $rows[] = [
                    'section' => (string)($ranking['title'] ?? 'Ranking'),
                    'label' => (string)($item['label'] ?? ''),
                    'value' => (string)($item['value'] ?? ''),
                ];
            }
        }
        return $rows;
    }

    private function buildLiveSpeedPayload(array $f): array
    {
        $snapshots = $this->latestSnapshotRows($f);

        $totalBuses = count($snapshots);
        $speedingBuses = 0;
        $sumSpeed = 0.0;

        $routeStats = [];
        $routeViolationMap = [];
        $depotViolationMap = [];
        $companyViolationMap = [];
        $entityViolationMap = [];

        foreach ($snapshots as $row) {
            $route = trim((string)($row['route_no'] ?? ''));
            if ($route === '') {
                $route = '-';
            }

            if (!isset($routeStats[$route])) {
                $routeStats[$route] = ['speeding' => 0, 'normal' => 0];
                $routeViolationMap[$route] = 0;
            }

            $speed = (float)($row['speed'] ?? 0);
            $sumSpeed += $speed;
            $isSpeeding = $speed > 60.0;
            if ($isSpeeding) {
                $routeStats[$route]['speeding']++;
                $routeViolationMap[$route]++;
                $speedingBuses++;
            } else {
                $routeStats[$route]['normal']++;
            }

            $depot = trim((string)($row['depot_name'] ?? ''));
            if ($depot !== '') {
                if (!isset($depotViolationMap[$depot])) {
                    $depotViolationMap[$depot] = 0;
                }
                if ($isSpeeding) {
                    $depotViolationMap[$depot]++;
                }
                if (!isset($entityViolationMap['Depot: ' . $depot])) {
                    $entityViolationMap['Depot: ' . $depot] = 0;
                }
                if ($isSpeeding) {
                    $entityViolationMap['Depot: ' . $depot]++;
                }
            }

            $owner = trim((string)($row['owner_name'] ?? ''));
            if ($owner !== '') {
                if (!isset($companyViolationMap[$owner])) {
                    $companyViolationMap[$owner] = 0;
                }
                if ($isSpeeding) {
                    $companyViolationMap[$owner]++;
                }
                if (!isset($entityViolationMap['Company: ' . $owner])) {
                    $entityViolationMap['Company: ' . $owner] = 0;
                }
                if ($isSpeeding) {
                    $entityViolationMap['Company: ' . $owner]++;
                }
            }
        }

        uasort($routeStats, static function ($a, $b) {
            $cmp = ($b['speeding'] <=> $a['speeding']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return ($b['normal'] <=> $a['normal']);
        });
        $routeStats = array_slice($routeStats, 0, 10, true);

        $routeLabels = array_keys($routeStats);
        $routeSpeeding = [];
        $routeNormal = [];
        foreach ($routeStats as $stat) {
            $routeSpeeding[] = (int)($stat['speeding'] ?? 0);
            $routeNormal[] = (int)($stat['normal'] ?? 0);
        }

        $topDepotPairs = $this->mapPairs($depotViolationMap, 10, false);
        $topCompanyPairs = $this->mapPairs($companyViolationMap, 10, false);

        $trendRows = $this->runTrackingAggregate(
            $f,
            "DATE(tm.snapshot_at) AS bucket, ROUND(AVG(COALESCE(tm.speed, 0)), 1) AS avg_speed",
            'DATE(tm.snapshot_at)',
            'DATE(tm.snapshot_at)',
            false
        );
        $trendLabels = array_map(fn($r) => $this->formatShortDateLabel((string)($r['bucket'] ?? '')), $trendRows);
        $trendValues = array_map('floatval', array_column($trendRows, 'avg_speed'));

        $heatRows = $this->runTrackingAggregate(
            $f,
            "DAYOFWEEK(tm.snapshot_at) AS dow,
             HOUR(tm.snapshot_at) AS hr,
             SUM(
                CASE
                    WHEN COALESCE(tm.speed_violations, 0) > 0 THEN COALESCE(tm.speed_violations, 0)
                    WHEN tm.speed > 60 THEN 1
                    ELSE 0
                END
             ) AS viols",
            'DAYOFWEEK(tm.snapshot_at), HOUR(tm.snapshot_at)',
            'DAYOFWEEK(tm.snapshot_at), HOUR(tm.snapshot_at)',
            false
        );

        $xLabels = [];
        for ($h = 0; $h < 24; $h++) {
            $xLabels[] = str_pad((string)$h, 2, '0', STR_PAD_LEFT);
        }
        $yLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $dayMap = [2 => 0, 3 => 1, 4 => 2, 5 => 3, 6 => 4, 7 => 5, 1 => 6];
        $matrix = array_fill(0, count($yLabels), array_fill(0, count($xLabels), 0));
        foreach ($heatRows as $row) {
            $dow = (int)($row['dow'] ?? 0);
            $hr = (int)($row['hr'] ?? -1);
            if (!isset($dayMap[$dow]) || $hr < 0 || $hr > 23) {
                continue;
            }
            $matrix[$dayMap[$dow]][$hr] = (int)($row['viols'] ?? 0);
        }

        $avgSpeed = $totalBuses > 0 ? round($sumSpeed / $totalBuses, 1) : 0.0;
        $speedRate = $totalBuses > 0 ? round(($speedingBuses / $totalBuses) * 100, 1) : 0.0;

        $topRoute = $this->topLabel($routeViolationMap);
        $topDepot = $this->topLabel($depotViolationMap);
        $topCompany = $this->topLabel($companyViolationMap);

        $insights = [];
        if ($topRoute) {
            $insights[] = "Route {$topRoute['label']} has the highest speeding buses ({$topRoute['value']}).";
        }
        if ($topDepot) {
            $insights[] = "Depot {$topDepot['label']} currently leads speed violations ({$topDepot['value']}).";
        }
        if ($topCompany) {
            $insights[] = "Private company {$topCompany['label']} has the most speeding events ({$topCompany['value']}).";
        }
        if (empty($insights)) {
            $insights[] = 'No critical speeding pattern detected in the selected range.';
        }

        $rankings = [
            $this->buildRanking('Top 5 Depots by Violations', $this->mapPairs($depotViolationMap, 5, false), 'bad'),
            $this->buildRanking('Top 5 Companies by Violations', $this->mapPairs($companyViolationMap, 5, false), 'bad'),
            $this->buildRanking('Bottom 5 (Least Violations)', $this->mapPairs($entityViolationMap, 5, true), 'good'),
        ];

        $kpis = [
            ['title' => 'Live Buses', 'value' => (string)$totalBuses, 'hint' => 'Latest snapshots', 'tone' => 'neutral'],
            ['title' => 'Speeding Buses', 'value' => (string)$speedingBuses, 'hint' => 'Speed > 60 km/h', 'tone' => 'bad'],
            ['title' => 'Speeding Rate', 'value' => $speedRate . '%', 'hint' => 'Across filtered fleet', 'tone' => 'warn'],
            ['title' => 'Average Speed', 'value' => $avgSpeed . ' km/h', 'hint' => 'All filtered buses', 'tone' => 'good'],
        ];

        return [
            'pageTitle' => 'Live Status Analytics (Speed Monitoring)',
            'pageSubtitle' => 'Real-time speed compliance and operator hotspots',
            'kpis' => $kpis,
            'insights' => $insights,
            'charts' => [
                [
                    'id' => 'speed_by_route',
                    'title' => 'Speeding vs Normal Buses by Route',
                    'type' => 'stackedBar',
                    'labels' => $routeLabels,
                    'datasets' => [
                        ['label' => 'Speeding', 'data' => $routeSpeeding, 'color' => '#dc2626'],
                        ['label' => 'Normal', 'data' => $routeNormal, 'color' => '#16a34a'],
                    ],
                    'yLabel' => 'Buses',
                ],
                [
                    'id' => 'speed_by_depot',
                    'title' => 'Speeding Buses by Depot',
                    'type' => 'bar',
                    'labels' => array_column($topDepotPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Violations', 'data' => array_column($topDepotPairs, 'value'), 'color' => '#ef4444'],
                    ],
                    'yLabel' => 'Violations',
                ],
                [
                    'id' => 'speed_by_company',
                    'title' => 'Speeding Buses by Private Company',
                    'type' => 'horizontalBar',
                    'labels' => array_column($topCompanyPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Violations', 'data' => array_column($topCompanyPairs, 'value'), 'color' => '#f97316'],
                    ],
                    'xLabel' => 'Violations',
                ],
                [
                    'id' => 'speed_trend',
                    'title' => 'Speed Trend Over Time',
                    'type' => 'line',
                    'labels' => $trendLabels,
                    'datasets' => [
                        ['label' => 'Avg Speed (km/h)', 'data' => $trendValues, 'color' => '#2563eb'],
                    ],
                    'yLabel' => 'km/h',
                ],
                [
                    'id' => 'speed_heatmap',
                    'title' => 'Heatmap: Time vs Speed Violations',
                    'type' => 'heatmap',
                    'xLabels' => $xLabels,
                    'yLabels' => $yLabels,
                    'matrix' => $matrix,
                ],
            ],
            'rankings' => $rankings,
            'exportRows' => $this->buildExportRows($rankings, $kpis),
        ];
    }

    private function buildBusStatusPayload(array $f): array
    {
        $snapshots = $this->latestSnapshotRows($f);

        $routeStatus = [];
        $depotStatus = [];
        $companyStatus = [];
        $routeTotals = [];
        $depotTotals = [];
        $companyTotals = [];
        $routeDelayed = [];

        foreach ($snapshots as $row) {
            $statusBucket = $this->serviceBucket((string)($row['operational_status'] ?? ''));

            $route = trim((string)($row['route_no'] ?? ''));
            if ($route === '') {
                $route = '-';
            }
            if (!isset($routeStatus[$route])) {
                $routeStatus[$route] = ['Active' => 0, 'Maintenance' => 0, 'Out of Service' => 0];
                $routeTotals[$route] = 0;
                $routeDelayed[$route] = 0;
            }
            $routeStatus[$route][$statusBucket]++;
            $routeTotals[$route]++;
            if (strcasecmp((string)($row['operational_status'] ?? ''), 'Delayed') === 0) {
                $routeDelayed[$route]++;
            }

            $depot = trim((string)($row['depot_name'] ?? ''));
            if ($depot !== '') {
                if (!isset($depotStatus[$depot])) {
                    $depotStatus[$depot] = ['Active' => 0, 'Maintenance' => 0, 'Out of Service' => 0];
                    $depotTotals[$depot] = 0;
                }
                $depotStatus[$depot][$statusBucket]++;
                $depotTotals[$depot]++;
            }

            $owner = trim((string)($row['owner_name'] ?? ''));
            if ($owner !== '') {
                if (!isset($companyStatus[$owner])) {
                    $companyStatus[$owner] = ['Active' => 0, 'Maintenance' => 0, 'Out of Service' => 0];
                    $companyTotals[$owner] = 0;
                }
                $companyStatus[$owner][$statusBucket]++;
                $companyTotals[$owner]++;
            }
        }

        $complaintRows = $this->complaintRows($f, true);
        $complaintsByRoute = [];
        foreach ($complaintRows as $cRow) {
            $route = trim((string)($cRow['route_no'] ?? '-'));
            if ($route === '') {
                $route = '-';
            }
            if (!isset($complaintsByRoute[$route])) {
                $complaintsByRoute[$route] = 0;
            }
            $complaintsByRoute[$route]++;
        }

        $suggestions = [];
        $utilization = [];
        foreach ($routeStatus as $route => $counts) {
            $active = (int)($counts['Active'] ?? 0);
            $total = max(1, (int)($routeTotals[$route] ?? 0));
            $utilization[$route] = round(($active / $total) * 100, 1);

            $demandSignal = ((int)($routeDelayed[$route] ?? 0) * 2) + (int)($complaintsByRoute[$route] ?? 0);
            $suggestions[$route] = max(0, $demandSignal - $active);
        }

        arsort($routeTotals, SORT_NUMERIC);
        $topRoutes = array_slice($routeTotals, 0, 10, true);
        $routeLabels = array_keys($topRoutes);

        $routeActive = [];
        $routeMaintenance = [];
        $routeOut = [];
        foreach ($routeLabels as $label) {
            $routeActive[] = (int)($routeStatus[$label]['Active'] ?? 0);
            $routeMaintenance[] = (int)($routeStatus[$label]['Maintenance'] ?? 0);
            $routeOut[] = (int)($routeStatus[$label]['Out of Service'] ?? 0);
        }

        arsort($depotTotals, SORT_NUMERIC);
        $topDepotLabels = array_keys(array_slice($depotTotals, 0, 10, true));
        $depotActive = [];
        $depotMaintenance = [];
        $depotOut = [];
        foreach ($topDepotLabels as $label) {
            $depotActive[] = (int)($depotStatus[$label]['Active'] ?? 0);
            $depotMaintenance[] = (int)($depotStatus[$label]['Maintenance'] ?? 0);
            $depotOut[] = (int)($depotStatus[$label]['Out of Service'] ?? 0);
        }

        arsort($companyTotals, SORT_NUMERIC);
        $topCompanyLabels = array_keys(array_slice($companyTotals, 0, 10, true));
        $companyActive = [];
        $companyMaintenance = [];
        $companyOut = [];
        foreach ($topCompanyLabels as $label) {
            $companyActive[] = (int)($companyStatus[$label]['Active'] ?? 0);
            $companyMaintenance[] = (int)($companyStatus[$label]['Maintenance'] ?? 0);
            $companyOut[] = (int)($companyStatus[$label]['Out of Service'] ?? 0);
        }

        arsort($suggestions, SORT_NUMERIC);
        $topSuggestionPairs = $this->mapPairs($suggestions, 10, false);

        arsort($utilization, SORT_NUMERIC);
        $topUtilizationPairs = $this->mapPairs($utilization, 10, false);

        $totalActive = 0;
        $totalMaintenance = 0;
        $totalOut = 0;
        foreach ($routeStatus as $counts) {
            $totalActive += (int)($counts['Active'] ?? 0);
            $totalMaintenance += (int)($counts['Maintenance'] ?? 0);
            $totalOut += (int)($counts['Out of Service'] ?? 0);
        }
        $avgUtilization = !empty($utilization) ? round(array_sum($utilization) / count($utilization), 1) : 0.0;

        $worstRoute = $this->topLabel($suggestions);
        if ($worstRoute && $worstRoute['value'] <= 0) {
            $worstRoute = null;
        }
        $lowestUtilRoute = null;
        if (!empty($utilization)) {
            $asc = $utilization;
            asort($asc, SORT_NUMERIC);
            $rk = array_key_first($asc);
            if ($rk !== null) {
                $lowestUtilRoute = ['label' => (string)$rk, 'value' => (float)$asc[$rk]];
            }
        }

        $insights = [];
        if ($worstRoute) {
            $insights[] = "Route {$worstRoute['label']} needs additional buses (gap score {$worstRoute['value']}).";
        }
        if ($lowestUtilRoute) {
            $insights[] = "Route {$lowestUtilRoute['label']} is underutilized at {$lowestUtilRoute['value']}% utilization.";
        }
        if (empty($insights)) {
            $insights[] = 'Fleet distribution is balanced for the selected filters.';
        }

        $rankings = [
            $this->buildRanking('Top 5 Busiest Routes', $this->mapPairs($routeTotals, 5, false), 'warn'),
            $this->buildRanking('Top 5 Busiest Depots', $this->mapPairs($depotTotals, 5, false), 'warn'),
            $this->buildRanking('Bottom 5 Underutilized Routes', $this->mapPairs($utilization, 5, true), 'good', '%'),
        ];

        $kpis = [
            ['title' => 'Active Buses', 'value' => (string)$totalActive, 'hint' => 'On duty now', 'tone' => 'good'],
            ['title' => 'Maintenance', 'value' => (string)$totalMaintenance, 'hint' => 'Needs attention', 'tone' => 'warn'],
            ['title' => 'Out of Service', 'value' => (string)$totalOut, 'hint' => 'Unavailable fleet', 'tone' => 'bad'],
            ['title' => 'Average Utilization', 'value' => $avgUtilization . '%', 'hint' => 'Active vs total buses', 'tone' => 'neutral'],
        ];

        return [
            'pageTitle' => 'Bus Status Analytics',
            'pageSubtitle' => 'Operational availability by route, depot and company',
            'kpis' => $kpis,
            'insights' => $insights,
            'charts' => [
                [
                    'id' => 'status_route',
                    'title' => 'Bus Status by Route',
                    'type' => 'stackedBar',
                    'labels' => $routeLabels,
                    'datasets' => [
                        ['label' => 'Active', 'data' => $routeActive, 'color' => '#16a34a'],
                        ['label' => 'Maintenance', 'data' => $routeMaintenance, 'color' => '#f59e0b'],
                        ['label' => 'Out of Service', 'data' => $routeOut, 'color' => '#dc2626'],
                    ],
                    'yLabel' => 'Buses',
                ],
                [
                    'id' => 'status_depot',
                    'title' => 'Bus Status by Depot',
                    'type' => 'stackedBar',
                    'labels' => $topDepotLabels,
                    'datasets' => [
                        ['label' => 'Active', 'data' => $depotActive, 'color' => '#22c55e'],
                        ['label' => 'Maintenance', 'data' => $depotMaintenance, 'color' => '#f59e0b'],
                        ['label' => 'Out of Service', 'data' => $depotOut, 'color' => '#ef4444'],
                    ],
                    'yLabel' => 'Buses',
                ],
                [
                    'id' => 'status_company',
                    'title' => 'Bus Status by Private Company',
                    'type' => 'stackedBar',
                    'labels' => $topCompanyLabels,
                    'datasets' => [
                        ['label' => 'Active', 'data' => $companyActive, 'color' => '#22c55e'],
                        ['label' => 'Maintenance', 'data' => $companyMaintenance, 'color' => '#f59e0b'],
                        ['label' => 'Out of Service', 'data' => $companyOut, 'color' => '#ef4444'],
                    ],
                    'yLabel' => 'Buses',
                ],
                [
                    'id' => 'route_gap',
                    'title' => 'Suggested Routes Needing More Buses',
                    'type' => 'bar',
                    'labels' => array_column($topSuggestionPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Gap Score', 'data' => array_column($topSuggestionPairs, 'value'), 'color' => '#dc2626'],
                    ],
                    'yLabel' => 'Gap score',
                ],
                [
                    'id' => 'utilization',
                    'title' => 'Utilization by Route',
                    'type' => 'line',
                    'labels' => array_column($topUtilizationPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Utilization %', 'data' => array_column($topUtilizationPairs, 'value'), 'color' => '#2563eb'],
                    ],
                    'yLabel' => '%',
                ],
            ],
            'rankings' => $rankings,
            'exportRows' => $this->buildExportRows($rankings, $kpis),
        ];
    }

    private function buildDelayedPayload(array $f): array
    {
        $snapshots = $this->latestSnapshotRows($f);

        $routeDelayed = [];
        $routeTotals = [];
        $depotDelayed = [];
        $depotTotals = [];
        $companyDelayed = [];
        $companyTotals = [];

        $delayedNow = 0;
        $delayMinutes = [];

        foreach ($snapshots as $row) {
            $route = trim((string)($row['route_no'] ?? ''));
            if ($route === '') {
                $route = '-';
            }
            if (!isset($routeDelayed[$route])) {
                $routeDelayed[$route] = 0;
                $routeTotals[$route] = 0;
            }
            $routeTotals[$route]++;

            $isDelayed = strcasecmp((string)($row['operational_status'] ?? ''), 'Delayed') === 0;
            if ($isDelayed) {
                $routeDelayed[$route]++;
                $delayedNow++;
                $delayMinutes[] = (float)($row['avg_delay_min'] ?? 0);
            }

            $depot = trim((string)($row['depot_name'] ?? ''));
            if ($depot !== '') {
                if (!isset($depotDelayed[$depot])) {
                    $depotDelayed[$depot] = 0;
                    $depotTotals[$depot] = 0;
                }
                $depotTotals[$depot]++;
                if ($isDelayed) {
                    $depotDelayed[$depot]++;
                }
            }

            $owner = trim((string)($row['owner_name'] ?? ''));
            if ($owner !== '') {
                if (!isset($companyDelayed[$owner])) {
                    $companyDelayed[$owner] = 0;
                    $companyTotals[$owner] = 0;
                }
                $companyTotals[$owner]++;
                if ($isDelayed) {
                    $companyDelayed[$owner]++;
                }
            }
        }

        $trendRows = $this->runTrackingAggregate(
            $f,
            "DATE(tm.snapshot_at) AS bucket,
             SUM(CASE WHEN tm.operational_status = 'Delayed' THEN 1 ELSE 0 END) AS delayed_count",
            'DATE(tm.snapshot_at)',
            'DATE(tm.snapshot_at)'
        );
        $trendLabels = array_map(fn($r) => $this->formatShortDateLabel((string)($r['bucket'] ?? '')), $trendRows);
        $trendValues = array_map('intval', array_column($trendRows, 'delayed_count'));

        $hourRows = $this->runTrackingAggregate(
            $f,
            "HOUR(tm.snapshot_at) AS hr,
             SUM(CASE WHEN tm.operational_status = 'Delayed' THEN 1 ELSE 0 END) AS delayed_count",
            'HOUR(tm.snapshot_at)',
            'HOUR(tm.snapshot_at)'
        );
        $hourMap = [];
        foreach ($hourRows as $hrRow) {
            $hour = (int)($hrRow['hr'] ?? 0);
            $hourMap[str_pad((string)$hour, 2, '0', STR_PAD_LEFT)] = (int)($hrRow['delayed_count'] ?? 0);
        }

        $totalBuses = count($snapshots);
        $delayRate = $totalBuses > 0 ? round(($delayedNow / $totalBuses) * 100, 1) : 0.0;
        $avgDelay = !empty($delayMinutes) ? round(array_sum($delayMinutes) / count($delayMinutes), 1) : 0.0;

        $peakHour = $this->topLabel($hourMap);
        $topRoute = $this->topLabel($routeDelayed);

        $insights = [];
        if ($topRoute) {
            $insights[] = "Route {$topRoute['label']} records the highest delayed bus count ({$topRoute['value']}).";
        }
        if ($peakHour) {
            $insights[] = "Peak delay hour is {$peakHour['label']}:00 with {$peakHour['value']} delayed snapshots.";
        }
        if (empty($insights)) {
            $insights[] = 'No major delay hotspot detected in the selected range.';
        }

        $rankings = [
            $this->buildRanking('Top 5 Most Delayed Routes', $this->mapPairs($routeDelayed, 5, false), 'bad'),
            $this->buildRanking('Top 5 Most Delayed Depots', $this->mapPairs($depotDelayed, 5, false), 'bad'),
            $this->buildRanking('Top 5 Most Delayed Companies', $this->mapPairs($companyDelayed, 5, false), 'bad'),
            $this->buildRanking('Bottom 5 Least Delayed Routes', $this->mapPairs($routeDelayed, 5, true), 'good'),
        ];

        $routePairs = $this->mapPairs($routeDelayed, 10, false);
        $depotPairs = $this->mapPairs($depotDelayed, 10, false);
        $companyPairs = $this->mapPairs($companyDelayed, 10, false);
        $hourPairs = $this->mapPairs($hourMap, 24, false);

        $kpis = [
            ['title' => 'Delayed Buses', 'value' => (string)$delayedNow, 'hint' => 'Latest snapshot state', 'tone' => 'bad'],
            ['title' => 'Delay Rate', 'value' => $delayRate . '%', 'hint' => 'Delayed / total buses', 'tone' => 'warn'],
            ['title' => 'Average Delay', 'value' => $avgDelay . ' min', 'hint' => 'Delayed buses only', 'tone' => 'neutral'],
            ['title' => 'Peak Delay Hour', 'value' => ($peakHour['label'] ?? '--') . ':00', 'hint' => 'Highest delayed volume', 'tone' => 'warn'],
        ];

        return [
            'pageTitle' => 'Delayed Buses Analytics',
            'pageSubtitle' => 'Delay hotspots by route, depot and private company',
            'kpis' => $kpis,
            'insights' => $insights,
            'charts' => [
                [
                    'id' => 'delay_route',
                    'title' => 'Delays by Route',
                    'type' => 'bar',
                    'labels' => array_column($routePairs, 'label'),
                    'datasets' => [
                        ['label' => 'Delayed Buses', 'data' => array_column($routePairs, 'value'), 'color' => '#dc2626'],
                    ],
                    'yLabel' => 'Delayed buses',
                ],
                [
                    'id' => 'delay_depot',
                    'title' => 'Delays by Depot',
                    'type' => 'bar',
                    'labels' => array_column($depotPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Delayed Buses', 'data' => array_column($depotPairs, 'value'), 'color' => '#f59e0b'],
                    ],
                    'yLabel' => 'Delayed buses',
                ],
                [
                    'id' => 'delay_company',
                    'title' => 'Delays by Private Company',
                    'type' => 'horizontalBar',
                    'labels' => array_column($companyPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Delayed Buses', 'data' => array_column($companyPairs, 'value'), 'color' => '#fb7185'],
                    ],
                    'xLabel' => 'Delayed buses',
                ],
                [
                    'id' => 'delay_trend',
                    'title' => 'Delay Trend Over Time',
                    'type' => 'line',
                    'labels' => $trendLabels,
                    'datasets' => [
                        ['label' => 'Delayed Snapshots', 'data' => $trendValues, 'color' => '#2563eb'],
                    ],
                    'yLabel' => 'Delayed snapshots',
                ],
                [
                    'id' => 'peak_hours',
                    'title' => 'Peak Delay Hours',
                    'type' => 'bar',
                    'labels' => array_column($hourPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Delays', 'data' => array_column($hourPairs, 'value'), 'color' => '#7c3aed'],
                    ],
                    'yLabel' => 'Delayed snapshots',
                ],
            ],
            'rankings' => $rankings,
            'exportRows' => $this->buildExportRows($rankings, $kpis),
        ];
    }

    private function buildSpeedViolationPayload(array $f): array
    {
        $violationExpr = "SUM(
            CASE
                WHEN COALESCE(tm.speed_violations, 0) > 0 THEN COALESCE(tm.speed_violations, 0)
                WHEN tm.speed > 60 THEN 1
                ELSE 0
            END
        )";

        $routeRows = $this->runTrackingAggregate(
            $f,
            "COALESCE(r.route_no, '-') AS label, {$violationExpr} AS viols",
            "COALESCE(r.route_no, '-')",
            'viols DESC',
            false
        );

        $depotRows = $this->runTrackingAggregate(
            $f,
            "COALESCE(NULLIF(d.name, ''), 'Unassigned') AS label, {$violationExpr} AS viols",
            "COALESCE(NULLIF(d.name, ''), 'Unassigned')",
            'viols DESC',
            false,
            'sb.sltb_depot_id IS NOT NULL'
        );

        $companyRows = $this->runTrackingAggregate(
            $f,
            "COALESCE(NULLIF(pbo.name, ''), 'Unassigned') AS label, {$violationExpr} AS viols",
            "COALESCE(NULLIF(pbo.name, ''), 'Unassigned')",
            'viols DESC',
            false,
            'pb.private_operator_id IS NOT NULL'
        );

        $busRows = $this->runTrackingAggregate(
            $f,
            "COALESCE(tm.bus_reg_no, 'Unknown') AS label, {$violationExpr} AS viols",
            "COALESCE(tm.bus_reg_no, 'Unknown')",
            'viols DESC',
            false,
            '',
            12
        );

        [$joins, $where, $params] = $this->trackingScope($f, 'tm', false);
        $sqlSeverity = "SELECT
                SUM(CASE WHEN tm.speed > 60 AND tm.speed <= 80 THEN 1 ELSE 0 END) AS mild_count,
                SUM(CASE WHEN tm.speed > 80 THEN 1 ELSE 0 END) AS severe_count
            FROM tracking_monitoring tm
            " . implode(' ', $joins) . "
            WHERE " . implode(' AND ', $where);
        $stSeverity = $this->pdo->prepare($sqlSeverity);
        $stSeverity->execute($params);
        $severity = $stSeverity->fetch(PDO::FETCH_ASSOC) ?: [];

        $routeMap = [];
        foreach ($routeRows as $r) {
            $routeMap[(string)($r['label'] ?? '-')] = (float)($r['viols'] ?? 0);
        }
        $depotMap = [];
        foreach ($depotRows as $r) {
            $depotMap[(string)($r['label'] ?? 'Unassigned')] = (float)($r['viols'] ?? 0);
        }
        $companyMap = [];
        foreach ($companyRows as $r) {
            $companyMap[(string)($r['label'] ?? 'Unassigned')] = (float)($r['viols'] ?? 0);
        }
        $busMap = [];
        foreach ($busRows as $r) {
            $busMap[(string)($r['label'] ?? 'Unknown')] = (float)($r['viols'] ?? 0);
        }

        $totalViolations = array_sum($routeMap);
        $mild = (int)($severity['mild_count'] ?? 0);
        $severe = (int)($severity['severe_count'] ?? 0);
        $severeShare = ($mild + $severe) > 0 ? round(($severe / ($mild + $severe)) * 100, 1) : 0.0;

        $repeatOffenders = 0;
        foreach ($busMap as $v) {
            if ($v >= 3) {
                $repeatOffenders++;
            }
        }
        $avgViolPerBus = !empty($busMap) ? round($totalViolations / count($busMap), 1) : 0.0;

        $topRoute = $this->topLabel($routeMap);
        $insights = [];
        if ($topRoute) {
            $insights[] = "Route {$topRoute['label']} has the highest speed violations ({$topRoute['value']}).";
        }
        $insights[] = "Severe speeding contributes {$severeShare}% of all speed incidents.";

        $rankings = [
            $this->buildRanking('Top 5 Highest Violations (Routes)', $this->mapPairs($routeMap, 5, false), 'bad'),
            $this->buildRanking('Top 5 Highest Violations (Depots)', $this->mapPairs($depotMap, 5, false), 'bad'),
            $this->buildRanking('Top 5 Highest Violations (Companies)', $this->mapPairs($companyMap, 5, false), 'bad'),
            $this->buildRanking('Bottom 5 Lowest Violations', $this->mapPairs($routeMap, 5, true), 'good'),
        ];

        $routePairs = $this->mapPairs($routeMap, 10, false);
        $depotPairs = $this->mapPairs($depotMap, 10, false);
        $companyPairs = $this->mapPairs($companyMap, 10, false);
        $busPairs = $this->mapPairs($busMap, 10, false);

        $kpis = [
            ['title' => 'Total Violations', 'value' => (string)round($totalViolations), 'hint' => 'Selected range', 'tone' => 'bad'],
            ['title' => 'Severe Share', 'value' => $severeShare . '%', 'hint' => 'Speed > 80 km/h', 'tone' => 'warn'],
            ['title' => 'Repeat Offenders', 'value' => (string)$repeatOffenders, 'hint' => 'Buses with >= 3 violations', 'tone' => 'bad'],
            ['title' => 'Avg Violations / Bus', 'value' => (string)$avgViolPerBus, 'hint' => 'Across violating buses', 'tone' => 'neutral'],
        ];

        return [
            'pageTitle' => 'High-Speed Violations Analytics',
            'pageSubtitle' => 'Severity and repeat-offender monitoring across the network',
            'kpis' => $kpis,
            'insights' => $insights,
            'charts' => [
                [
                    'id' => 'viol_route',
                    'title' => 'Violations by Route',
                    'type' => 'bar',
                    'labels' => array_column($routePairs, 'label'),
                    'datasets' => [
                        ['label' => 'Violations', 'data' => array_column($routePairs, 'value'), 'color' => '#dc2626'],
                    ],
                    'yLabel' => 'Violations',
                ],
                [
                    'id' => 'viol_depot',
                    'title' => 'Violations by Depot',
                    'type' => 'bar',
                    'labels' => array_column($depotPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Violations', 'data' => array_column($depotPairs, 'value'), 'color' => '#f97316'],
                    ],
                    'yLabel' => 'Violations',
                ],
                [
                    'id' => 'viol_company',
                    'title' => 'Violations by Company',
                    'type' => 'horizontalBar',
                    'labels' => array_column($companyPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Violations', 'data' => array_column($companyPairs, 'value'), 'color' => '#fb7185'],
                    ],
                    'xLabel' => 'Violations',
                ],
                [
                    'id' => 'viol_severity',
                    'title' => 'Severity Distribution (Mild vs Severe)',
                    'type' => 'bar',
                    'labels' => ['Mild (60-80)', 'Severe (>80)'],
                    'datasets' => [
                        ['label' => 'Incidents', 'data' => [$mild, $severe], 'color' => '#7c3aed'],
                    ],
                    'yLabel' => 'Incidents',
                ],
                [
                    'id' => 'viol_repeat',
                    'title' => 'Repeat Offender Buses',
                    'type' => 'bar',
                    'labels' => array_column($busPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Violations', 'data' => array_column($busPairs, 'value'), 'color' => '#2563eb'],
                    ],
                    'yLabel' => 'Violations',
                ],
            ],
            'rankings' => $rankings,
            'exportRows' => $this->buildExportRows($rankings, $kpis),
        ];
    }

    private function buildRevenuePayload(array $f): array
    {
        $rows = $this->revenueRows($f);

        $routeMap = [];
        $depotMap = [];
        $companyMap = [];
        $trendMap = [];

        $monthly = $this->rangeDays($f['from'], $f['to']) > 60;

        foreach ($rows as $row) {
            $amount = (float)($row['amount'] ?? 0);
            $route = trim((string)($row['route_no'] ?? '-'));
            if ($route === '') {
                $route = '-';
            }
            if (!isset($routeMap[$route])) {
                $routeMap[$route] = 0.0;
            }
            $routeMap[$route] += $amount;

            $depot = trim((string)($row['depot_name'] ?? ''));
            if ($depot !== '') {
                if (!isset($depotMap[$depot])) {
                    $depotMap[$depot] = 0.0;
                }
                $depotMap[$depot] += $amount;
            }

            $company = trim((string)($row['owner_name'] ?? ''));
            if ($company !== '') {
                if (!isset($companyMap[$company])) {
                    $companyMap[$company] = 0.0;
                }
                $companyMap[$company] += $amount;
            }

            $day = (string)($row['day'] ?? '');
            if ($day !== '') {
                $bucket = $monthly ? substr($day, 0, 7) : $day;
                if (!isset($trendMap[$bucket])) {
                    $trendMap[$bucket] = 0.0;
                }
                $trendMap[$bucket] += $amount;
            }
        }

        ksort($trendMap);

        $trendLabels = [];
        $trendRevenue = [];
        $trendCost = [];
        $trendProfit = [];
        foreach ($trendMap as $bucket => $value) {
            $label = $bucket;
            if ($monthly) {
                $label = date('M Y', strtotime($bucket . '-01'));
            } else {
                $label = $this->formatShortDateLabel($bucket);
            }
            $trendLabels[] = $label;
            $revM = round($value / 1000000, 2);
            $costM = round(($value * 0.68) / 1000000, 2);
            $profitM = round(($value * 0.32) / 1000000, 2);
            $trendRevenue[] = $revM;
            $trendCost[] = $costM;
            $trendProfit[] = $profitM;
        }

        $totalRevenue = array_sum($routeMap);
        $estimatedProfit = $totalRevenue * 0.32;
        $avgDailyRevenue = $totalRevenue / max(1, $this->rangeDays($f['from'], $f['to']));

        $topRoute = $this->topLabel($routeMap);
        $lowRoute = null;
        if (!empty($routeMap)) {
            $asc = $routeMap;
            asort($asc, SORT_NUMERIC);
            $rk = array_key_first($asc);
            if ($rk !== null) {
                $lowRoute = ['label' => (string)$rk, 'value' => (float)$asc[$rk]];
            }
        }

        $insights = [];
        if ($topRoute) {
            $insights[] = 'Route ' . $topRoute['label'] . ' is the top revenue generator in the selected period.';
        }
        if ($lowRoute) {
            $insights[] = 'Route ' . $lowRoute['label'] . ' is the lowest revenue performer and may need service optimization.';
        }
        if (empty($insights)) {
            $insights[] = 'No revenue records found for the selected filters.';
        }

        $routePairs = $this->mapPairs($routeMap, 10, false);
        $depotPairs = $this->mapPairs($depotMap, 10, false);
        $companyPairs = $this->mapPairs($companyMap, 10, false);

        $routeRevM = array_map(static fn($v) => round(((float)$v) / 1000000, 2), array_column($routePairs, 'value'));
        $depotRevM = array_map(static fn($v) => round(((float)$v) / 1000000, 2), array_column($depotPairs, 'value'));
        $companyRevM = array_map(static fn($v) => round(((float)$v) / 1000000, 2), array_column($companyPairs, 'value'));

        $rankings = [
            $this->buildRanking('Top 5 Revenue Routes', $this->mapPairs($routeMap, 5, false), 'good'),
            $this->buildRanking('Top 5 Revenue Companies', $this->mapPairs($companyMap, 5, false), 'good'),
            $this->buildRanking('Bottom 5 Lowest Revenue Routes', $this->mapPairs($routeMap, 5, true), 'warn'),
        ];

        $kpis = [
            ['title' => 'Total Revenue', 'value' => 'LKR ' . number_format($totalRevenue / 1000000, 2) . ' Mn', 'hint' => 'Filtered date range', 'tone' => 'good'],
            ['title' => 'Estimated Profit', 'value' => 'LKR ' . number_format($estimatedProfit / 1000000, 2) . ' Mn', 'hint' => 'Using 32% margin estimate', 'tone' => 'good'],
            ['title' => 'Average Daily Revenue', 'value' => 'LKR ' . number_format($avgDailyRevenue, 0), 'hint' => 'Per selected day', 'tone' => 'neutral'],
            ['title' => 'Top Route', 'value' => $topRoute['label'] ?? '--', 'hint' => 'Highest revenue route', 'tone' => 'good'],
        ];

        return [
            'pageTitle' => 'Revenue Analytics',
            'pageSubtitle' => 'Revenue and profit estimation by route, depot and company',
            'kpis' => $kpis,
            'insights' => $insights,
            'charts' => [
                [
                    'id' => 'rev_route',
                    'title' => 'Revenue by Route',
                    'type' => 'bar',
                    'labels' => array_column($routePairs, 'label'),
                    'datasets' => [
                        ['label' => 'Revenue (LKR Mn)', 'data' => $routeRevM, 'color' => '#16a34a'],
                    ],
                    'yLabel' => 'LKR Mn',
                ],
                [
                    'id' => 'rev_depot',
                    'title' => 'Revenue by Depot',
                    'type' => 'bar',
                    'labels' => array_column($depotPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Revenue (LKR Mn)', 'data' => $depotRevM, 'color' => '#2563eb'],
                    ],
                    'yLabel' => 'LKR Mn',
                ],
                [
                    'id' => 'rev_company',
                    'title' => 'Revenue by Company',
                    'type' => 'horizontalBar',
                    'labels' => array_column($companyPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Revenue (LKR Mn)', 'data' => $companyRevM, 'color' => '#f59e0b'],
                    ],
                    'xLabel' => 'LKR Mn',
                ],
                [
                    'id' => 'rev_trend',
                    'title' => 'Revenue Trend Over Time',
                    'type' => 'line',
                    'labels' => $trendLabels,
                    'datasets' => [
                        ['label' => 'Revenue (LKR Mn)', 'data' => $trendRevenue, 'color' => '#16a34a'],
                    ],
                    'yLabel' => 'LKR Mn',
                ],
                [
                    'id' => 'profit_est',
                    'title' => 'Profit Estimation (Revenue vs Cost)',
                    'type' => 'line',
                    'labels' => $trendLabels,
                    'datasets' => [
                        ['label' => 'Revenue', 'data' => $trendRevenue, 'color' => '#1d4ed8'],
                        ['label' => 'Estimated Cost', 'data' => $trendCost, 'color' => '#dc2626'],
                        ['label' => 'Estimated Profit', 'data' => $trendProfit, 'color' => '#16a34a'],
                    ],
                    'yLabel' => 'LKR Mn',
                ],
            ],
            'rankings' => $rankings,
            'exportRows' => $this->buildExportRows($rankings, $kpis),
        ];
    }

    private function buildWaitTimePayload(array $f): array
    {
        $snapshots = $this->latestSnapshotRows($f);

        $routeSum = [];
        $routeCount = [];
        $depotSum = [];
        $depotCount = [];
        $companySum = [];
        $companyCount = [];

        $totalDelay = 0.0;
        $totalRows = 0;
        $longWait = 0;

        foreach ($snapshots as $row) {
            $delay = (float)($row['avg_delay_min'] ?? 0);

            $route = trim((string)($row['route_no'] ?? ''));
            if ($route === '') {
                $route = '-';
            }
            if (!isset($routeSum[$route])) {
                $routeSum[$route] = 0.0;
                $routeCount[$route] = 0;
            }
            $routeSum[$route] += $delay;
            $routeCount[$route]++;

            $depot = trim((string)($row['depot_name'] ?? ''));
            if ($depot !== '') {
                if (!isset($depotSum[$depot])) {
                    $depotSum[$depot] = 0.0;
                    $depotCount[$depot] = 0;
                }
                $depotSum[$depot] += $delay;
                $depotCount[$depot]++;
            }

            $company = trim((string)($row['owner_name'] ?? ''));
            if ($company !== '') {
                if (!isset($companySum[$company])) {
                    $companySum[$company] = 0.0;
                    $companyCount[$company] = 0;
                }
                $companySum[$company] += $delay;
                $companyCount[$company]++;
            }

            $totalDelay += $delay;
            $totalRows++;
            if ($delay > 10) {
                $longWait++;
            }
        }

        $routeAvg = [];
        foreach ($routeSum as $k => $sum) {
            $routeAvg[$k] = $routeCount[$k] > 0 ? round($sum / $routeCount[$k], 1) : 0.0;
        }
        $depotAvg = [];
        foreach ($depotSum as $k => $sum) {
            $depotAvg[$k] = $depotCount[$k] > 0 ? round($sum / $depotCount[$k], 1) : 0.0;
        }
        $companyAvg = [];
        foreach ($companySum as $k => $sum) {
            $companyAvg[$k] = $companyCount[$k] > 0 ? round($sum / $companyCount[$k], 1) : 0.0;
        }

        $peakRows = $this->runTrackingAggregate(
            $f,
            "CASE
                WHEN HOUR(tm.snapshot_at) BETWEEN 7 AND 10 OR HOUR(tm.snapshot_at) BETWEEN 17 AND 20 THEN 'Peak'
                ELSE 'Off-Peak'
             END AS bucket,
             ROUND(AVG(COALESCE(tm.avg_delay_min, 0)), 1) AS avg_wait",
            "CASE
                WHEN HOUR(tm.snapshot_at) BETWEEN 7 AND 10 OR HOUR(tm.snapshot_at) BETWEEN 17 AND 20 THEN 'Peak'
                ELSE 'Off-Peak'
             END",
            'bucket DESC'
        );

        $peakMap = ['Peak' => 0.0, 'Off-Peak' => 0.0];
        foreach ($peakRows as $pr) {
            $bucket = (string)($pr['bucket'] ?? 'Off-Peak');
            $peakMap[$bucket] = (float)($pr['avg_wait'] ?? 0);
        }

        $avgWait = $totalRows > 0 ? round($totalDelay / $totalRows, 1) : 0.0;
        $longWaitPct = $totalRows > 0 ? round(($longWait / $totalRows) * 100, 1) : 0.0;

        $worstRoute = $this->topLabel($routeAvg);
        $bestRoute = null;
        if (!empty($routeAvg)) {
            $asc = $routeAvg;
            asort($asc, SORT_NUMERIC);
            $rk = array_key_first($asc);
            if ($rk !== null) {
                $bestRoute = ['label' => (string)$rk, 'value' => (float)$asc[$rk]];
            }
        }

        $insights = [];
        if ($worstRoute) {
            $insights[] = "Route {$worstRoute['label']} has the highest average wait time ({$worstRoute['value']} min).";
        }
        $insights[] = "Peak periods currently average {$peakMap['Peak']} min wait vs {$peakMap['Off-Peak']} min off-peak.";

        $routePairs = $this->mapPairs($routeAvg, 10, false);
        $depotPairs = $this->mapPairs($depotAvg, 10, false);
        $companyPairs = $this->mapPairs($companyAvg, 10, false);

        $rankings = [
            $this->buildRanking('Top 5 Highest Wait Times (Routes)', $this->mapPairs($routeAvg, 5, false), 'bad', ' min'),
            $this->buildRanking('Top 5 Highest Wait Times (Depots)', $this->mapPairs($depotAvg, 5, false), 'bad', ' min'),
            $this->buildRanking('Bottom 5 Lowest Wait Times (Routes)', $this->mapPairs($routeAvg, 5, true), 'good', ' min'),
        ];

        $kpis = [
            ['title' => 'Average Wait', 'value' => $avgWait . ' min', 'hint' => 'Latest snapshots', 'tone' => 'warn'],
            ['title' => 'Long Wait Share', 'value' => $longWaitPct . '%', 'hint' => 'Wait > 10 min', 'tone' => 'bad'],
            ['title' => 'Worst Route', 'value' => $worstRoute['label'] ?? '--', 'hint' => 'Highest average wait', 'tone' => 'bad'],
            ['title' => 'Best Route', 'value' => $bestRoute['label'] ?? '--', 'hint' => 'Lowest average wait', 'tone' => 'good'],
        ];

        return [
            'pageTitle' => 'Wait Time Distribution Analytics',
            'pageSubtitle' => 'Route and operator wait-time patterns with peak/off-peak split',
            'kpis' => $kpis,
            'insights' => $insights,
            'charts' => [
                [
                    'id' => 'wait_route',
                    'title' => 'Wait Time by Route',
                    'type' => 'bar',
                    'labels' => array_column($routePairs, 'label'),
                    'datasets' => [
                        ['label' => 'Avg Wait (min)', 'data' => array_column($routePairs, 'value'), 'color' => '#dc2626'],
                    ],
                    'yLabel' => 'Minutes',
                ],
                [
                    'id' => 'wait_depot',
                    'title' => 'Wait Time by Depot',
                    'type' => 'bar',
                    'labels' => array_column($depotPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Avg Wait (min)', 'data' => array_column($depotPairs, 'value'), 'color' => '#f59e0b'],
                    ],
                    'yLabel' => 'Minutes',
                ],
                [
                    'id' => 'wait_company',
                    'title' => 'Wait Time by Company',
                    'type' => 'horizontalBar',
                    'labels' => array_column($companyPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Avg Wait (min)', 'data' => array_column($companyPairs, 'value'), 'color' => '#2563eb'],
                    ],
                    'xLabel' => 'Minutes',
                ],
                [
                    'id' => 'wait_peak_split',
                    'title' => 'Peak vs Off-Peak Wait Time',
                    'type' => 'bar',
                    'labels' => ['Peak', 'Off-Peak'],
                    'datasets' => [
                        ['label' => 'Avg Wait (min)', 'data' => [$peakMap['Peak'], $peakMap['Off-Peak']], 'color' => '#7c3aed'],
                    ],
                    'yLabel' => 'Minutes',
                ],
            ],
            'rankings' => $rankings,
            'exportRows' => $this->buildExportRows($rankings, $kpis),
        ];
    }

    private function buildComplaintsPayload(array $f): array
    {
        $rows = $this->complaintRows($f, true);

        $routeMap = [];
        $depotMap = [];
        $companyMap = [];
        $categoryMap = [];
        $trendMap = [];
        $resolved = 0;
        $uniqueBuses = [];

        $monthly = $this->rangeDays($f['from'], $f['to']) > 60;

        foreach ($rows as $row) {
            $route = trim((string)($row['route_no'] ?? '-'));
            if ($route === '') {
                $route = '-';
            }
            if (!isset($routeMap[$route])) {
                $routeMap[$route] = 0;
            }
            $routeMap[$route]++;

            $depot = trim((string)($row['depot_name'] ?? ''));
            if ($depot !== '') {
                if (!isset($depotMap[$depot])) {
                    $depotMap[$depot] = 0;
                }
                $depotMap[$depot]++;
            }

            $company = trim((string)($row['owner_name'] ?? ''));
            if ($company !== '') {
                if (!isset($companyMap[$company])) {
                    $companyMap[$company] = 0;
                }
                $companyMap[$company]++;
            }

            $categoryRaw = trim((string)($row['category'] ?? 'uncategorized'));
            if ($categoryRaw === '') {
                $categoryRaw = 'uncategorized';
            }
            $category = ucwords(str_replace(['_', '-'], ' ', $categoryRaw));
            if (!isset($categoryMap[$category])) {
                $categoryMap[$category] = 0;
            }
            $categoryMap[$category]++;

            $day = (string)($row['day'] ?? '');
            if ($day !== '') {
                $bucket = $monthly ? substr($day, 0, 7) : $day;
                if (!isset($trendMap[$bucket])) {
                    $trendMap[$bucket] = 0;
                }
                $trendMap[$bucket]++;
            }

            $status = strtolower(trim((string)($row['status'] ?? '')));
            if (in_array($status, ['resolved', 'closed'], true)) {
                $resolved++;
            }

            $bus = trim((string)($row['bus_reg_no'] ?? ''));
            if ($bus !== '' && strtolower($bus) !== 'undefined') {
                $uniqueBuses[$bus] = true;
            }
        }

        ksort($trendMap);
        $trendLabels = [];
        $trendValues = [];
        foreach ($trendMap as $bucket => $value) {
            $trendLabels[] = $monthly ? date('M Y', strtotime($bucket . '-01')) : $this->formatShortDateLabel($bucket);
            $trendValues[] = (int)$value;
        }

        $totalComplaints = count($rows);
        $resolvedRate = $totalComplaints > 0 ? round(($resolved / $totalComplaints) * 100, 1) : 0.0;
        $avgPerDay = round($totalComplaints / max(1, $this->rangeDays($f['from'], $f['to'])), 2);

        $topRoute = $this->topLabel($routeMap);
        $topCategory = $this->topLabel($categoryMap);

        $insights = [];
        if ($topRoute) {
            $insights[] = "Route {$topRoute['label']} receives the most complaints ({$topRoute['value']}).";
        }
        if ($topCategory) {
            $insights[] = "Most frequent complaint category is {$topCategory['label']} ({$topCategory['value']} cases).";
        }
        if (empty($insights)) {
            $insights[] = 'No complaint trend detected for the selected filters.';
        }

        $routePairs = $this->mapPairs($routeMap, 10, false);
        $depotPairs = $this->mapPairs($depotMap, 10, false);
        $companyPairs = $this->mapPairs($companyMap, 10, false);
        $categoryPairs = $this->mapPairs($categoryMap, 10, false);

        $rankings = [
            $this->buildRanking('Top 5 Most Complaints (Routes)', $this->mapPairs($routeMap, 5, false), 'bad'),
            $this->buildRanking('Top 5 Most Complaints (Depots)', $this->mapPairs($depotMap, 5, false), 'bad'),
            $this->buildRanking('Bottom 5 Least Complaints (Routes)', $this->mapPairs($routeMap, 5, true), 'good'),
        ];

        $kpis = [
            ['title' => 'Total Complaints', 'value' => (string)$totalComplaints, 'hint' => 'Filtered date range', 'tone' => 'bad'],
            ['title' => 'Resolved Rate', 'value' => $resolvedRate . '%', 'hint' => 'Resolved + closed', 'tone' => 'good'],
            ['title' => 'Affected Buses', 'value' => (string)count($uniqueBuses), 'hint' => 'Unique bus registrations', 'tone' => 'warn'],
            ['title' => 'Average per Day', 'value' => (string)$avgPerDay, 'hint' => 'Complaint frequency', 'tone' => 'neutral'],
        ];

        return [
            'pageTitle' => 'Complaints Analytics',
            'pageSubtitle' => 'Complaint distribution and trend intelligence',
            'kpis' => $kpis,
            'insights' => $insights,
            'charts' => [
                [
                    'id' => 'compl_route',
                    'title' => 'Complaints by Route',
                    'type' => 'bar',
                    'labels' => array_column($routePairs, 'label'),
                    'datasets' => [
                        ['label' => 'Complaints', 'data' => array_column($routePairs, 'value'), 'color' => '#dc2626'],
                    ],
                    'yLabel' => 'Complaints',
                ],
                [
                    'id' => 'compl_depot',
                    'title' => 'Complaints by Depot',
                    'type' => 'bar',
                    'labels' => array_column($depotPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Complaints', 'data' => array_column($depotPairs, 'value'), 'color' => '#f59e0b'],
                    ],
                    'yLabel' => 'Complaints',
                ],
                [
                    'id' => 'compl_company',
                    'title' => 'Complaints by Company',
                    'type' => 'horizontalBar',
                    'labels' => array_column($companyPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Complaints', 'data' => array_column($companyPairs, 'value'), 'color' => '#2563eb'],
                    ],
                    'xLabel' => 'Complaints',
                ],
                [
                    'id' => 'compl_category',
                    'title' => 'Complaint Categories',
                    'type' => 'bar',
                    'labels' => array_column($categoryPairs, 'label'),
                    'datasets' => [
                        ['label' => 'Count', 'data' => array_column($categoryPairs, 'value'), 'color' => '#7c3aed'],
                    ],
                    'yLabel' => 'Complaints',
                ],
                [
                    'id' => 'compl_trend',
                    'title' => 'Complaint Trend Over Time',
                    'type' => 'line',
                    'labels' => $trendLabels,
                    'datasets' => [
                        ['label' => 'Complaints', 'data' => $trendValues, 'color' => '#ef4444'],
                    ],
                    'yLabel' => 'Complaints',
                ],
            ],
            'rankings' => $rankings,
            'exportRows' => $this->buildExportRows($rankings, $kpis),
        ];
    }

    /* ─── Dropdown data ──────────────────────────────────────────── */
    public function depots(): array
    {
        return $this->pdo->query(
            "SELECT sltb_depot_id AS id, name FROM sltb_depots ORDER BY name"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function owners(): array
    {
        return $this->pdo->query(
            "SELECT private_operator_id AS id, name FROM private_bus_owners ORDER BY name"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
