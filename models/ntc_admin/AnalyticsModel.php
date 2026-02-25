<?php
namespace App\models\ntc_admin;

use PDO;
abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];
    }
}

class AnalyticsModel extends BaseModel {

    /** Buses grouped by status (Active / Maintenance / Inactive) */
    public function busStatus(): array {
        $sql = "SELECT status, COUNT(*) AS total FROM (
                    SELECT status FROM private_buses
                    UNION ALL
                    SELECT status FROM sltb_buses
                ) t GROUP BY status ORDER BY status";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Revenue by month for the last 12 months.
     * Returns {labels:[], values:[]} shaped for the chart.
     */
    public function revenueTrends(): array {
        $sql = "SELECT DATE_FORMAT(date,'%b %Y') AS mon,
                       YEAR(date) AS yr, MONTH(date) AS mo,
                       ROUND(SUM(amount)/1000000, 2) AS total_m
                FROM earnings
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY yr, mo
                ORDER BY yr, mo";
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $labels = array_column($rows, 'mon');
        $values = array_map('floatval', array_column($rows, 'total_m'));
        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Top buses by cumulative speed violations (from tracking_monitoring).
     * Falls back to average speed > 60 per bus if speed_violations is null.
     */
    public function speedByBus(): array {
        $sql = "SELECT bus_reg_no,
                       COALESCE(SUM(speed_violations), 0) AS viols
                FROM tracking_monitoring
                GROUP BY bus_reg_no
                HAVING viols > 0
                ORDER BY viols DESC
                LIMIT 10";
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            // fallback: average speed recorded above 60
            $sql2 = "SELECT bus_reg_no,
                            COUNT(*) AS viols
                     FROM tracking_monitoring
                     WHERE speed > 60
                     GROUP BY bus_reg_no
                     ORDER BY viols DESC
                     LIMIT 10";
            $rows = $this->pdo->query($sql2)->fetchAll(PDO::FETCH_ASSOC);
        }
        return [
            'labels' => array_column($rows, 'bus_reg_no'),
            'values' => array_map('intval', array_column($rows, 'viols')),
        ];
    }

    /**
     * Delayed vs total bus-trips per route (last 30 days).
     */
    public function delayedByRoute(): array {
        $sql = "SELECT r.route_no,
                       COUNT(*) AS total,
                       SUM(CASE WHEN t.operational_status='Delayed' THEN 1 ELSE 0 END) AS `delayed_count`
                FROM tracking_monitoring t
                JOIN routes r ON r.route_id = t.route_id
                WHERE t.snapshot_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY r.route_no
                ORDER BY `delayed_count` DESC
                LIMIT 10";
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return [
            'labels'  => array_column($rows, 'route_no'),
            'delayed' => array_map('intval', array_column($rows, 'delayed_count')),
            'total'   => array_map('intval', array_column($rows, 'total')),
        ];
    }

    /**
     * Wait-time / delay distribution based on avg_delay_min buckets.
     */
    public function waitTimeDistribution(): array {
        $sql = "SELECT
                  SUM(CASE WHEN avg_delay_min IS NULL OR avg_delay_min < 5  THEN 1 ELSE 0 END) AS under5,
                  SUM(CASE WHEN avg_delay_min >= 5  AND avg_delay_min < 10  THEN 1 ELSE 0 END) AS five10,
                  SUM(CASE WHEN avg_delay_min >= 10 AND avg_delay_min < 15  THEN 1 ELSE 0 END) AS ten15,
                  SUM(CASE WHEN avg_delay_min >= 15 AND avg_delay_min < 20  THEN 1 ELSE 0 END) AS fif20,
                  SUM(CASE WHEN avg_delay_min >= 20                         THEN 1 ELSE 0 END) AS over20,
                  COUNT(*) AS grand
                FROM tracking_monitoring
                WHERE snapshot_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $r   = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
        $g   = max(1, (int)$r['grand']);
        return [
            ['label' => 'Under 5 min',  'value' => round($r['under5'] / $g * 100), 'color' => '#16a34a'],
            ['label' => '5–10 min',     'value' => round($r['five10'] / $g * 100), 'color' => '#84cc16'],
            ['label' => '10–15 min',    'value' => round($r['ten15']  / $g * 100), 'color' => '#f3b944'],
            ['label' => '15–20 min',    'value' => round($r['fif20']  / $g * 100), 'color' => '#f59e0b'],
            ['label' => 'Over 20 min',  'value' => round($r['over20'] / $g * 100), 'color' => '#b91c1c'],
        ];
    }

    /**
     * Complaints (category = 'complaint') per route.
     */
    public function complaintsByRoute(): array {
        $sql = "SELECT r.route_no, COUNT(*) AS cnt
                FROM complaints c
                JOIN routes r ON r.route_id = c.route_id
                WHERE c.category = 'complaint'
                GROUP BY r.route_no
                ORDER BY cnt DESC
                LIMIT 10";
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return [
            'labels' => array_column($rows, 'route_no'),
            'values' => array_map('intval', array_column($rows, 'cnt')),
        ];
    }

    /**
     * KPI: average passenger rating (1-5 → scaled to /10).
     */
    public function avgRating(): float {
        $r = $this->pdo->query(
            "SELECT ROUND(AVG(rating) * 2, 1) AS avg_r FROM complaints WHERE rating IS NOT NULL"
        )->fetch(PDO::FETCH_ASSOC);
        return $r && $r['avg_r'] !== null ? (float)$r['avg_r'] : 0.0;
    }

    /**
     * KPI: today's delayed buses (distinct).
     */
    public function delayedToday(): int {
        $r = $this->pdo->query(
            "SELECT COUNT(DISTINCT bus_reg_no) c FROM tracking_monitoring
             WHERE operational_status='Delayed' AND DATE(snapshot_at)=CURDATE()"
        )->fetch(PDO::FETCH_ASSOC);
        return (int)($r['c'] ?? 0);
    }

    /**
     * KPI: total speed violations recorded today.
     */
    public function speedViolationsToday(): int {
        $r = $this->pdo->query(
            "SELECT COALESCE(SUM(speed_violations),0) c FROM tracking_monitoring
             WHERE DATE(snapshot_at)=CURDATE()"
        )->fetch(PDO::FETCH_ASSOC);
        if (!$r['c']) {
            // fallback count
            $r = $this->pdo->query(
                "SELECT COUNT(*) c FROM tracking_monitoring WHERE speed > 60 AND DATE(snapshot_at)=CURDATE()"
            )->fetch(PDO::FETCH_ASSOC);
        }
        return (int)($r['c'] ?? 0);
    }

    /**
     * KPI: % of snapshots today with avg_delay_min > 10.
     */
    public function longWaitPct(): int {
        $r = $this->pdo->query(
            "SELECT COUNT(*) total,
                    SUM(CASE WHEN avg_delay_min > 10 THEN 1 ELSE 0 END) over10
             FROM tracking_monitoring WHERE DATE(snapshot_at)=CURDATE()"
        )->fetch(PDO::FETCH_ASSOC);
        if (!$r || !$r['total']) return 0;
        return (int)round($r['over10'] / $r['total'] * 100);
    }
}
