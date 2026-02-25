<?php
namespace App\models\ntc_admin;

use PDO;

class AnalyticsModel extends BaseModel
{
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

        $sql = "SELECT DATE_FORMAT(e.date,'%b %Y') AS mon,
                       YEAR(e.date) AS yr, MONTH(e.date) AS mo,
                       ROUND(SUM(e.amount)/1000000, 2) AS total_m
                FROM earnings e $joinSql
                $whereSql
                GROUP BY yr, mo
                ORDER BY yr, mo";
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
            $col = $this->pdo->prepare("SHOW COLUMNS FROM complaints LIKE 'rating'");
            $col->execute();
            if (!$col->fetch(PDO::FETCH_ASSOC)) {
                return 0.0;
            }

            $wheres = ["rating IS NOT NULL"];
            $params = [];
            if (!empty($f['route_no'])) {
                $wheres[]          = "route_id = (SELECT route_id FROM routes WHERE CAST(route_no AS UNSIGNED) = CAST(:ft_rno AS UNSIGNED) LIMIT 1)";
                $params[':ft_rno'] = $f['route_no'];
            }
            $whereSql = 'WHERE ' . implode(' AND ', $wheres);
            $stmt = $this->pdo->prepare(
                "SELECT ROUND(AVG(rating) * 2, 1) AS avg_r FROM complaints $whereSql"
            );
            $stmt->execute($params);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            return $r && $r['avg_r'] !== null ? (float)$r['avg_r'] : 0.0;
        } catch (\PDOException $e) {
            return 0.0;
        }
    }

    /* ─── KPI: delayed today ─────────────────────────────────────── */
    public function delayedToday(array $f = []): int
    {
        [$joins, $wheres, $params] = $this->tmFilters($f);
        $wheres[] = "t.operational_status = 'Delayed'";
        $wheres[] = "DATE(t.snapshot_at) = CURDATE()";
        $joinSql  = implode(' ', array_map(fn($j) => str_replace(' t.', ' t.', $j), $joins));
        $whereSql = 'WHERE ' . implode(' AND ', $wheres);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT t.bus_reg_no) c FROM tracking_monitoring t $joinSql $whereSql"
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
