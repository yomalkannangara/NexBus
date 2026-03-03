<?php
namespace App\models\depot_manager;

use PDO;
use PDOException;
use App\models\common\BaseModel;

class EarningsModel extends BaseModel
{
    /** Get current depot ID from session */
    private function depotId(): ?int
    {
        $u = $_SESSION['user'] ?? null;
        return isset($u['sltb_depot_id']) ? (int)$u['sltb_depot_id'] : null;
    }

    /** WHERE clause + params to scope queries to this depot's SLTB buses */
    private function depotScope(): array
    {
        $depotId = $this->depotId();
        if ($depotId) {
            return [
                'join'   => "JOIN sltb_buses sb ON sb.reg_no = e.bus_reg_no AND sb.sltb_depot_id = :depot_id",
                'params' => [':depot_id' => $depotId],
            ];
        }
        return [
            'join'   => "JOIN sltb_buses sb ON sb.reg_no = e.bus_reg_no",
            'params' => [],
        ];
    }

    private function routeForBus(string $busReg): string
    {
        try {
            $sql = "SELECT r.route_no, r.stops_json
                    FROM tracking_monitoring tm
                    JOIN routes r ON r.route_id = tm.route_id
                    WHERE tm.bus_reg_no = :reg
                    ORDER BY tm.snapshot_at DESC
                    LIMIT 1";
            $st = $this->pdo->prepare($sql);
            $st->execute([':reg' => $busReg]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) return '—';
            $no = trim($row['route_no'] ?? '');
            $stops = json_decode($row['stops_json'] ?? '[]', true) ?: [];
            if ($stops) {
                $first = is_array($stops[0])
                    ? ($stops[0]['name'] ?? $stops[0]['stop'] ?? $stops[0]['code'] ?? 'Start')
                    : (string)$stops[0];
                $last  = is_array($stops[count($stops)-1])
                    ? ($stops[count($stops)-1]['name'] ?? $stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['code'] ?? 'End')
                    : (string)$stops[count($stops)-1];
                return $no ? "$no — $first → $last" : "$first → $last";
            }
            return $no ?: '—';
        } catch (PDOException $e) {
            return '—';
        }
    }
    
    /* ==================== Top summary (daily, highest, lowest) ==================== */
    public function topSummary(): array
    {
        try {
            // Most recent date that has SLTB earnings
            $latestRow     = $this->row("SELECT MAX(date) d FROM earnings WHERE operator_type='SLTB'");
            $latestDateStr = $latestRow['d'] ?? null;

            // Most recent day's income
            $today = $latestDateStr
                ? $this->scalar(
                    "SELECT COALESCE(SUM(amount),0) v FROM earnings
                      WHERE operator_type='SLTB' AND date=:today",
                    [':today' => $latestDateStr])
                : 0.0;

            // Previous day's income
            $prevDate = $latestDateStr
                ? $this->row(
                    "SELECT MAX(date) d FROM earnings
                      WHERE operator_type='SLTB' AND date < :ld",
                    [':ld' => $latestDateStr])
                : [];
            $prevDateStr = $prevDate['d'] ?? null;
            $yday = $prevDateStr
                ? $this->scalar(
                    "SELECT COALESCE(SUM(amount),0) v FROM earnings
                      WHERE operator_type='SLTB' AND date=:yd",
                    [':yd' => $prevDateStr])
                : 0.0;
            $trend = $this->pctDelta($today, $yday);

            // Total all-time SLTB revenue
            $totalAll = $this->scalar(
                "SELECT COALESCE(SUM(amount),0) v FROM earnings WHERE operator_type='SLTB'");

            // Highest single-day total (all time)
            $hi = $this->row(
                "SELECT DATE(date) d, SUM(amount) t FROM earnings
                  WHERE operator_type='SLTB'
                  GROUP BY DATE(date) ORDER BY t DESC LIMIT 1");

            // Lowest single-day total (all time, ignore zeros)
            $lo = $this->row(
                "SELECT DATE(date) d, SUM(amount) t FROM earnings
                  WHERE operator_type='SLTB'
                  GROUP BY DATE(date) HAVING t>0 ORDER BY t ASC LIMIT 1");

            $latestLabel = $latestDateStr ? date('M j, Y', strtotime($latestDateStr)) : '—';

            return [
                ['value' => $this->rupees($today),               'label' => 'Latest Day Income',  'trend' => $trend,                             'sub'  => $latestLabel,      'color' => 'maroon'],
                ['value' => $this->rupeesCompact($totalAll),     'label' => 'Total Revenue',       'sub'   => 'All-time SLTB fleet',              'color' => 'green'],
                ['value' => $this->rupees((float)($hi['t']??0)), 'label' => 'Highest Day',         'sub'   => $this->fmtLongDate($hi['d']??null), 'color' => 'green'],
                ['value' => $this->rupees((float)($lo['t']??0)), 'label' => 'Lowest Day',          'sub'   => $this->fmtLongDate($lo['d']??null), 'color' => 'red'],
            ];
        } catch (PDOException $e) {
            return [
                ['value' => $this->rupees(0), 'label' => 'Latest Day Income', 'trend' => '+0.0%', 'sub' => '—', 'color' => 'maroon'],
                ['value' => $this->rupees(0), 'label' => 'Total Revenue',     'sub'   => '—',     'color' => 'green'],
                ['value' => $this->rupees(0), 'label' => 'Highest Day',       'sub'   => '—',     'color' => 'green'],
                ['value' => $this->rupees(0), 'label' => 'Lowest Day',        'sub'   => '—',     'color' => 'red'],
            ];
        }
    }
    private function fmtLongDate(?string $date): string
    {
        if (!$date) return '—';
        $ts = strtotime($date);
        return $ts ? date('F j, Y', $ts) : '—';
    }

    /* ==================== Income per bus (total + most-recent day) ==================== */
    public function busIncomeDetail(): array
    {
        try {
            // Find the latest date with SLTB earnings
            $latestRow  = $this->row(
                "SELECT MAX(date) d FROM earnings WHERE operator_type='SLTB'"
            );
            $latestDate    = $latestRow['d'] ?? null;
            $weeklyStart   = (new \DateTime($latestDate ?? 'today'))->modify('-6 days')->format('Y-m-d');
            $latestDateSql = $latestDate ? "'$latestDate'" : 'CURDATE()';

            // All distinct buses present in the SLTB earnings table
            $sql = "
                SELECT
                    e.bus_reg_no AS reg_no,
                    COALESCE(sb.status, 'Active') AS bus_status,
                    SUM(CASE WHEN DATE(e.date) = $latestDateSql THEN e.amount ELSE 0 END) AS daily,
                    SUM(CASE WHEN e.date >= '$weeklyStart'       THEN e.amount ELSE 0 END) AS weekly,
                    SUM(e.amount) AS total_all
                FROM earnings e
                LEFT JOIN sltb_buses sb ON sb.reg_no = e.bus_reg_no
                WHERE e.operator_type = 'SLTB'
                GROUP BY e.bus_reg_no, sb.status
                ORDER BY total_all DESC
            ";

            $st   = $this->pdo->query($sql);
            $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];

            $maxTotal = 0.0;
            foreach ($rows as $r) $maxTotal = max($maxTotal, (float)$r['total_all']);

            $out = [];
            foreach ($rows as $r) {
                $total  = (float)$r['total_all'];
                $daily  = (float)$r['daily'];
                $weekly = (float)$r['weekly'];
                $eff    = $maxTotal > 0 ? (100.0 * $total / $maxTotal) : 0.0;
                $out[] = [
                    'number' => $r['reg_no'],
                    'route'  => $this->routeForBus($r['reg_no']),
                    'daily'  => $this->rupees($daily),
                    'weekly' => $this->rupees($weekly),
                    'total'  => $this->rupeesCompact($total),
                    'eff'    => number_format($eff, 0) . '%',
                    'status' => $r['bus_status'] ?? 'Active',
                ];
            }
            return $out;
        } catch (PDOException $e) {
            return [];
        }
    }

    /* ==================== Monthly overview (latest vs previous month) ==================== */
    public function monthlyOverview(): array
    {
        try {
            // Find the most recent month with SLTB earnings
            $latestRow  = $this->row("SELECT MAX(date) d FROM earnings WHERE operator_type='SLTB'");
            $latestDate = $latestRow['d'] ?? null;

            if (!$latestDate) {
                return ['current' => 'Rs. 0', 'previous' => 'Rs. 0', 'growth' => '+0.0%'];
            }

            $dt        = new \DateTime($latestDate);
            $firstThis = (clone $dt)->modify('first day of this month')->format('Y-m-d');
            $firstNext = (clone $dt)->modify('first day of next month')->format('Y-m-d');
            $firstPrev = (clone $dt)->modify('first day of last month')->format('Y-m-d');

            $curr = $this->scalar(
                "SELECT COALESCE(SUM(amount),0) v FROM earnings
                  WHERE operator_type='SLTB' AND date >= :start AND date < :end",
                [':start' => $firstThis, ':end' => $firstNext]
            );

            $prev = $this->scalar(
                "SELECT COALESCE(SUM(amount),0) v FROM earnings
                  WHERE operator_type='SLTB' AND date >= :start AND date < :end",
                [':start' => $firstPrev, ':end' => $firstThis]
            );

            return [
                'current'  => $this->rupeesCompact($curr),
                'previous' => $this->rupeesCompact($prev),
                'growth'   => $this->pctDelta($curr, $prev),
            ];
        } catch (PDOException $e) {
            return ['current' => 'Rs. 0', 'previous' => 'Rs. 0', 'growth' => '+0.0%'];
        }
    }

    /* ==================== Existing helpers ==================== */
    public function add(array $d): bool
    {
        try {
            $sql = "INSERT INTO earnings (operator_type, bus_reg_no, date, amount, source)
                    VALUES ('SLTB', :bus_reg_no, :date, :amount, :source)";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([
                ':bus_reg_no' => trim($d['bus_reg_no'] ?? ''),
                ':date'       => $d['date'] ?? date('Y-m-d'),
                ':amount'     => (float)($d['amount'] ?? 0),
                ':source'     => $d['source'] ?? 'Cash',
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $st = $this->pdo->prepare("DELETE FROM earnings WHERE earning_id=?");
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
                    'bus_reg_no' => trim($row[0] ?? ''),
                    'date'       => $row[1] ?? date('Y-m-d'),
                    'amount'     => (float)($row[2] ?? 0),
                    'source'     => $row[3] ?? 'Cash',
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
