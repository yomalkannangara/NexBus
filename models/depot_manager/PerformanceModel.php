<?php
namespace App\models\depot_manager;

use PDO;
use PDOException;
abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}
class PerformanceModel extends BaseModel
{
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

        // Dummy fallback when everything is zero (no data)
        $useDummy = ($delayedRate === 0.0 && $avgRating === 0.0 && (int)$speedViolations === 0 && $longWaitRate === 0.0);
        if ($useDummy) {
            return [
                ['title' => 'Delayed Buses Today',   'value' => '7.8%', 'sub' => 'Share of delayed trips today', 'color' => 'red'],
                ['title' => 'Average Driver Rating', 'value' => '4.3',  'sub' => '7-day average score',          'color' => 'green'],
                ['title' => 'Speed Violations',      'value' => '12',   'sub' => 'Today total',                   'color' => 'yellow'],
                ['title' => 'Long Wait Times',       'value' => '9.4%', 'sub' => '> 10 minutes (today)',          'color' => 'maroon'],
            ];
        }

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

            // Dummy fallback when no rows
            if (!$out) {
                $out = [
                    ['rank' => 1, 'name' => 'J. Perera',     'route' => '138 — Colombo - Kandy',    'delay' => '3.1%', 'rating' => '4.7', 'speed' => '1', 'wait' => '2.4%'],
                    ['rank' => 2, 'name' => 'A. Silva',      'route' => '101 — Pettah - Kadawatha', 'delay' => '5.0%', 'rating' => '4.5', 'speed' => '0', 'wait' => '3.0%'],
                    ['rank' => 3, 'name' => 'K. Fernando',   'route' => '255 — Moratuwa - Nugegoda','delay' => '4.2%', 'rating' => '4.4', 'speed' => '2', 'wait' => '4.6%'],
                ];
            }

            return $out;
        } catch (PDOException $e) {
            // Dummy fallback on error
            return [
                ['rank' => 1, 'name' => 'J. Perera',     'route' => '138 — Colombo - Kandy',    'delay' => '3.1%', 'rating' => '4.7', 'speed' => '1', 'wait' => '2.4%'],
                ['rank' => 2, 'name' => 'A. Silva',      'route' => '101 — Pettah - Kadawatha', 'delay' => '5.0%', 'rating' => '4.5', 'speed' => '0', 'wait' => '3.0%'],
                ['rank' => 3, 'name' => 'K. Fernando',   'route' => '255 — Moratuwa - Nugegoda','delay' => '4.2%', 'rating' => '4.4', 'speed' => '2', 'wait' => '4.6%'],
            ];
        }
    }

    /* ---------- Helpers ---------- */

    private function bestRouteForDriver(int $driverId): string
    {
        try {
            $sql = "
                SELECT r.route_no, r.name, COUNT(*) c
                FROM trip_performance p
                JOIN routes r ON r.route_id = p.route_id
                WHERE p.driver_id = :id
                GROUP BY r.route_id, r.route_no, r.name
                ORDER BY c DESC
                LIMIT 1
            ";
            $st = $this->pdo->prepare($sql);
            $st->execute([':id' => $driverId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $no = trim((string)($row['route_no'] ?? ''));
                $nm = trim((string)($row['name'] ?? ''));
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
}
