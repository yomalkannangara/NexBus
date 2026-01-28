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
class EarningsModel extends BaseModel
{
    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return \"$first - $last\";
    }

    /* ==================== Top summary (daily, highest, lowest) ==================== */
    public function topSummary(): array
    {
        try {
            // Today vs yesterday
            $today = $this->scalar("SELECT COALESCE(SUM(amount),0) v FROM earnings WHERE DATE(date)=CURDATE()");
            $yday  = $this->scalar("SELECT COALESCE(SUM(amount),0) v FROM earnings WHERE DATE(date)=DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
            $trend = $this->pctDelta($today, $yday);

            // Highest / Lowest daily totals in the current year (ignore zeros for lowest)
            $hi = $this->row("
                SELECT DATE(date) d, SUM(amount) t
                  FROM earnings
                 WHERE YEAR(date)=YEAR(CURDATE())
              GROUP BY DATE(date)
              ORDER BY t DESC
                 LIMIT 1
            ");
            $lo = $this->row("
                SELECT DATE(date) d, SUM(amount) t
                  FROM earnings
                 WHERE YEAR(date)=YEAR(CURDATE())
              GROUP BY DATE(date)
                HAVING t>0
              ORDER BY t ASC
                 LIMIT 1
            ");

            return [
                ['value' => $this->rupees($today),               'label' => 'Daily Income',   'trend' => $trend,                             'color' => 'maroon'],
                ['value' => $this->rupees((float)($hi['t']??0)), 'label' => 'Highest Income','sub'   => $this->fmtLongDate($hi['d']??null), 'color' => 'green'],
                ['value' => $this->rupees((float)($lo['t']??0)), 'label' => 'Lowest Income', 'sub'   => $this->fmtLongDate($lo['d']??null), 'color' => 'red'],
            ];
        } catch (PDOException $e) {
            return [
                ['value' => $this->rupees(0), 'label' => 'Daily Income',   'trend' => '+0.0%', 'color' => 'maroon'],
                ['value' => $this->rupees(0), 'label' => 'Highest Income', 'sub'   => '—',     'color' => 'green'],
                ['value' => $this->rupees(0), 'label' => 'Lowest Income',  'sub'   => '—',     'color' => 'red'],
            ];
        }
    }
    private function fmtLongDate(?string $date): string
{
    if (!$date) return '—';
    $ts = strtotime($date);
    return $ts ? date('F j, Y', $ts) : '—';
}

    /* ==================== Income per bus (today + 7d) ==================== */
    public function busIncomeDetail(): array
    {
        try {
            $weeklyStart = (new \DateTime('today'))->modify('-6 days')->format('Y-m-d');

            $sql = "
                SELECT
                    b.id,
                    b.reg_no,
                    r.name AS route,
                    SUM(CASE WHEN DATE(e.date)=CURDATE() THEN e.amount ELSE 0 END) AS daily,
                    SUM(CASE WHEN DATE(e.date) >= :wstart THEN e.amount ELSE 0 END) AS weekly
                FROM buses b
                LEFT JOIN earnings e ON e.bus_id=b.id
                LEFT JOIN routes r   ON r.route_id=b.route_id
                GROUP BY b.id, b.reg_no, r.name
                ORDER BY weekly DESC
                LIMIT 200
            ";
            $st = $this->pdo->prepare($sql);
            $st->execute([':wstart' => $weeklyStart]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Compute efficiency vs max weekly
            $maxWeekly = 0.0;
            foreach ($rows as $r) $maxWeekly = max($maxWeekly, (float)$r['weekly']);

            $out = [];
            foreach ($rows as $r) {
                $weekly = (float)$r['weekly'];
                $daily  = (float)$r['daily'];
                $eff    = $maxWeekly > 0 ? (100.0 * $weekly / $maxWeekly) : 0.0;
                $out[] = [
                    'number' => (string)($r['reg_no'] ?? ''),
                    'route'  => (string)($r['route']  ?? '—'),
                    'daily'  => $this->rupees($daily),
                    'weekly' => $this->rupees($weekly),
                    'eff'    => number_format($eff, 0) . '%',
                ];
            }
            return $out;
        } catch (PDOException $e) {
            return [];
        }
    }

    /* ==================== Monthly overview (current vs previous) ==================== */
    public function monthlyOverview(): array
    {
        try {
            // Current month bounds
            $firstThis = (new \DateTime('first day of this month 00:00:00'))->format('Y-m-d');
            $firstNext = (new \DateTime('first day of next month 00:00:00'))->format('Y-m-d');

            // Previous month bounds
            $firstPrev = (new \DateTime('first day of last month 00:00:00'))->format('Y-m-d');
            $firstThisAgain = $firstThis;

            $curr = $this->scalar("
                SELECT COALESCE(SUM(amount),0) v
                  FROM earnings
                 WHERE date >= :start AND date < :end
            ", [':start'=>$firstThis, ':end'=>$firstNext]);

            $prev = $this->scalar("
                SELECT COALESCE(SUM(amount),0) v
                  FROM earnings
                 WHERE date >= :start AND date < :end
            ", [':start'=>$firstPrev, ':end'=>$firstThisAgain]);

            return [
                'current'  => $this->rupeesCompact($curr),
                'previous' => $this->rupeesCompact($prev),
                'growth'   => $this->pctDelta($curr, $prev),
            ];
        } catch (PDOException $e) {
            return ['current'=>'Rs. 0','previous'=>'Rs. 0','growth'=>'+0.0%'];
        }
    }

    /* ==================== Existing helpers (keep your add/delete/import if used) ==================== */
    public function add(array $d): bool
    {
        try {
            $sql = "INSERT INTO earnings (bus_id, date, amount, source, created_at)
                    VALUES (:bus_id, :date, :amount, :source, NOW())";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([
                ':bus_id' => (int)($d['bus_id'] ?? 0),
                ':date'   => $d['date'] ?? date('Y-m-d'),
                ':amount' => (float)($d['amount'] ?? 0),
                ':source' => $d['source'] ?? 'Ticketing',
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $st = $this->pdo->prepare("DELETE FROM earnings WHERE id=?");
            return $st->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function importCsv(?array $file): bool
    {
        if (!$file || !is_uploaded_file($file['tmp_name'] ?? '')) return false;

        $okAll = true;
        if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
            $first = true;
            while (($row = fgetcsv($handle)) !== false) {
                if ($first && $this->looksLikeHeader($row)) { $first = false; continue; }
                $first = false;
                $ok = $this->add([
                    'bus_id' => (int)($row[0] ?? 0),
                    'date'   => $row[1] ?? date('Y-m-d'),
                    'amount' => (float)($row[2] ?? 0),
                    'source' => $row[3] ?? 'Ticketing',
                ]);
                if (!$ok) $okAll = false;
            }
            fclose($handle);
        }
        return $okAll;
    }

    /* ==================== Private utils ==================== */
    private function scalar(string $sql, array $p = []): float
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($p);
        return (float)($st->fetch(PDO::FETCH_ASSOC)['v'] ?? 0.0);
    }

    private function row(string $sql, array $p = []): array
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($p);
        return $st->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function pctDelta(float $now, float $prev): string
    {
        if ($prev <= 0) return $now > 0 ? '+100.0%' : '+0.0%';
        $pct = 100.0 * ($now - $prev) / $prev;
        $sign = $pct >= 0 ? '+' : '';
        return $sign . number_format($pct, 1) . '%';
    }

    private function rupees(float $n): string
    {
        return 'Rs. ' . number_format($n, 0, '.', ',');
    }

    private function rupeesCompact(float $n): string
    {
        if ($n >= 1_000_000_000) return 'Rs. ' . number_format($n/1_000_000_000, 1) . 'B';
        if ($n >= 1_000_000)     return 'Rs. ' . number_format($n/1_000_000, 1) . 'M';
        if ($n >= 1_000)         return 'Rs. ' . number_format($n/1_000, 1) . 'K';
        return $this->rupees($n);
    }

    private function looksLikeHeader(array $row): bool
    {
        $joined = strtolower(implode(',', $row));
        return str_contains($joined, 'bus') || str_contains($joined, 'amount');
    }
}
