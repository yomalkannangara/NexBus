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
                'join'   => "JOIN sltb_buses sb ON sb.reg_no = e.bus_reg_no AND sb.sltb_depot_id = " . (int)$depotId . " AND sb.reg_no NOT IN ('PA-1001', 'PB-1002')",
                'params' => [],
            ];
        }
        return [
            'join'   => "JOIN sltb_buses sb ON sb.reg_no = e.bus_reg_no AND sb.reg_no NOT IN ('PA-1001', 'PB-1002')",
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
            $scope = $this->depotScope();
            $join = $scope['join'];
            $params = $scope['params'];

            // Most recent date that has SLTB earnings
            $latestRow     = $this->row("SELECT MAX(e.date) d FROM earnings e {$join} WHERE e.operator_type='SLTB'", $params);
            $latestDateStr = $latestRow['d'] ?? null;

            // Most recent day's income
            $today = $latestDateStr
                                ? $this->scalar(
                                        "SELECT COALESCE(SUM(e.amount),0) v FROM earnings e {$join}
                                            WHERE e.operator_type='SLTB' AND e.date=:today",
                                        [':today' => $latestDateStr])
                : 0.0;

            // Previous day's income
            $prevDate = $latestDateStr
                                ? $this->row(
                                        "SELECT MAX(e.date) d FROM earnings e {$join}
                                            WHERE e.operator_type='SLTB' AND e.date < :ld",
                                        [':ld' => $latestDateStr])
                : [];
            $prevDateStr = $prevDate['d'] ?? null;
            $yday = $prevDateStr
                                ? $this->scalar(
                                        "SELECT COALESCE(SUM(e.amount),0) v FROM earnings e {$join}
                                            WHERE e.operator_type='SLTB' AND e.date=:yd",
                                        [':yd' => $prevDateStr])
                : 0.0;
            $trend = $this->pctDelta($today, $yday);

            // Total all-time SLTB revenue
            $totalAll = $this->scalar(
                "SELECT COALESCE(SUM(e.amount),0) v FROM earnings e {$join} WHERE e.operator_type='SLTB'");

            // Highest single-day total (all time)
                        $hi = $this->row(
                                "SELECT DATE(e.date) d, SUM(e.amount) t FROM earnings e {$join}
                                    WHERE e.operator_type='SLTB'
                                    GROUP BY DATE(e.date) ORDER BY t DESC LIMIT 1");

            // Lowest single-day total (all time, ignore zeros)
                        $lo = $this->row(
                                "SELECT DATE(e.date) d, SUM(e.amount) t FROM earnings e {$join}
                                    WHERE e.operator_type='SLTB'
                                    GROUP BY DATE(e.date) HAVING t>0 ORDER BY t ASC LIMIT 1");

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
            $scope = $this->depotScope();
            $join = $scope['join'];
            $params = $scope['params'];

            // Find the latest date with SLTB earnings
            $latestRow  = $this->row(
                "SELECT MAX(e.date) d FROM earnings e {$join} WHERE e.operator_type='SLTB'",
                $params
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
                {$join}
                WHERE e.operator_type = 'SLTB'
                GROUP BY e.bus_reg_no, sb.status
                ORDER BY total_all DESC
            ";

            $st   = $this->pdo->prepare($sql);
            $st->execute($params);
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
            $scope = $this->depotScope();
            $join = $scope['join'];
            $params = $scope['params'];

            // Find the most recent month with SLTB earnings
            $latestRow  = $this->row("SELECT MAX(e.date) d FROM earnings e {$join} WHERE e.operator_type='SLTB'");
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
                                [':start' => $firstThis, ':end' => $firstNext] + $params
            );

            $prev = $this->scalar(
                "SELECT COALESCE(SUM(amount),0) v FROM earnings
                                    WHERE operator_type='SLTB' AND date >= :start AND date < :end",
                                [':start' => $firstPrev, ':end' => $firstThis] + $params
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

    /* ==================== Revenue Trend Chart (Last 7 days) ==================== */
    public function revenueTrendChart(): array
    {
        try {
            $scope = $this->depotScope();
            $join = $scope['join'];
            $params = $scope['params'];

            $sql = "
                SELECT DATE(e.date) AS day, SUM(e.amount) AS total
                FROM earnings e {$join}
                WHERE e.operator_type='SLTB' AND e.date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY DATE(e.date)
                ORDER BY day ASC
            ";
            
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
            
            $labels = [];
            $values = [];
            
            foreach ($rows as $r) {
                $labels[] = date('M d', strtotime($r['day']));
                $values[] = (float)($r['total'] ?? 0);
            }
            
            return [
                'labels' => $labels,
                'values' => $values,
                'count' => count($rows),
            ];
        } catch (PDOException $e) {
            return ['labels' => [], 'values' => [], 'count' => 0];
        }
    }

    /* ==================== Bus Performance Ranking ==================== */
    public function busPerformanceRanking(): array
    {
        try {
            $scope = $this->depotScope();
            $join = $scope['join'];
            $params = $scope['params'];

            $sql = "
                SELECT
                    e.bus_reg_no,
                    SUM(e.amount) AS total_revenue,
                    COUNT(e.earning_id) AS transaction_count,
                    AVG(e.amount) AS avg_per_transaction
                FROM earnings e {$join}
                WHERE e.operator_type='SLTB'
                GROUP BY e.bus_reg_no
                ORDER BY total_revenue DESC
            ";
            
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
            
            $result = [
                'top' => [],
                'bottom' => [],
            ];
            
            if (!empty($rows)) {
                // Top 5
                $top = array_slice($rows, 0, 5);
                foreach ($top as $r) {
                    $result['top'][] = [
                        'bus' => $r['bus_reg_no'],
                        'revenue' => (float)($r['total_revenue'] ?? 0),
                        'transactions' => (int)($r['transaction_count'] ?? 0),
                        'avg' => (float)($r['avg_per_transaction'] ?? 0),
                    ];
                }
                
                // Bottom 5
                $bottom = array_slice(array_reverse($rows), 0, 5);
                foreach ($bottom as $r) {
                    $result['bottom'][] = [
                        'bus' => $r['bus_reg_no'],
                        'revenue' => (float)($r['total_revenue'] ?? 0),
                        'transactions' => (int)($r['transaction_count'] ?? 0),
                        'avg' => (float)($r['avg_per_transaction'] ?? 0),
                    ];
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            return ['top' => [], 'bottom' => []];
        }
    }

    /* ==================== Daily Income Distribution ==================== */
    public function dailyIncomeDistribution(): array
    {
        try {
            $scope = $this->depotScope();
            $join = $scope['join'];
            $params = $scope['params'];

            $sql = "
                SELECT DATE(e.date) AS day, SUM(e.amount) AS total_income
                FROM earnings e {$join}
                WHERE e.operator_type='SLTB' AND e.date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
                GROUP BY DATE(e.date)
                ORDER BY day ASC
            ";
            
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
            
            $labels = [];
            $values = [];
            
            foreach ($rows as $r) {
                $labels[] = date('d M', strtotime($r['day']));
                $values[] = (float)($r['total_income'] ?? 0);
            }
            
            return [
                'labels' => $labels,
                'values' => $values,
            ];
        } catch (PDOException $e) {
            return ['labels' => [], 'values' => []];
        }
    }

    /* ==================== Bus Performance Heatmap Data ==================== */
    public function busPerformanceMetrics(): array
    {
        try {
            $scope = $this->depotScope();
            $join = $scope['join'];
            $params = $scope['params'];

            $latestRow  = $this->row("SELECT MAX(e.date) d FROM earnings e {$join} WHERE e.operator_type='SLTB'");
            $latestDate = $latestRow['d'] ?? null;
            $weekStart  = (new \DateTime($latestDate ?? 'today'))->modify('-6 days')->format('Y-m-d');
            
            $sql = "
                SELECT
                    e.bus_reg_no,
                    SUM(e.amount) AS weekly_revenue,
                    COUNT(e.earning_id) AS trips,
                    AVG(e.amount) AS avg_revenue
                FROM earnings e {$join}
                WHERE e.operator_type='SLTB' AND e.date >= :week_start
                GROUP BY e.bus_reg_no
                ORDER BY weekly_revenue DESC
            ";
            
            $st = $this->pdo->prepare($sql);
            $st->execute([':week_start' => $weekStart] + $params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            
            $maxRevenue = 0;
            foreach ($rows as $r) {
                $maxRevenue = max($maxRevenue, (float)($r['weekly_revenue'] ?? 0));
            }
            
            $result = [];
            foreach ($rows as $r) {
                $revenue = (float)($r['weekly_revenue'] ?? 0);
                $efficiency = $maxRevenue > 0 ? (100.0 * $revenue / $maxRevenue) : 0;
                
                $result[] = [
                    'bus' => $r['bus_reg_no'],
                    'revenue' => $revenue,
                    'trips' => (int)($r['trips'] ?? 0),
                    'avg' => (float)($r['avg_revenue'] ?? 0),
                    'efficiency' => $efficiency,
                ];
            }
            
            return $result;
        } catch (PDOException $e) {
            return [];
        }
    }

    /* ==================== Compare Two Buses ==================== */
    public function compareBuses(string $bus1, string $bus2): array
    {
        try {
            $scope = $this->depotScope();
            $join = $scope['join'];
            $params = $scope['params'];

            $latestRow  = $this->row("SELECT MAX(e.date) d FROM earnings e {$join} WHERE e.operator_type='SLTB'");
            $latestDate = $latestRow['d'] ?? null;
            
            if (!$latestDate) {
                return ['bus1' => null, 'bus2' => null];
            }

            $dt        = new \DateTime($latestDate);
            $firstThis = (clone $dt)->modify('first day of this month')->format('Y-m-d');
            $firstNext = (clone $dt)->modify('first day of next month')->format('Y-m-d');
            $firstPrev = (clone $dt)->modify('first day of last month')->format('Y-m-d');
            $latestDateSql = "'" . $latestDate . "'";

            // Process each bus
            $buses = [$bus1, $bus2];
            $result = [];

            foreach ($buses as $busReg) {
                // Total income (all time)
                $totalIncome = $this->scalar(
                    "SELECT COALESCE(SUM(e.amount),0) v FROM earnings e {$join}
                     WHERE e.operator_type='SLTB' AND e.bus_reg_no=?",
                    [$busReg]
                );

                // This month income
                $thisMonth = $this->scalar(
                    "SELECT COALESCE(SUM(e.amount),0) v FROM earnings e {$join}
                     WHERE e.operator_type='SLTB' AND e.bus_reg_no=? AND e.date >= ? AND e.date < ?",
                    [$busReg, $firstThis, $firstNext]
                );

                // Previous month income
                $prevMonth = $this->scalar(
                    "SELECT COALESCE(SUM(e.amount),0) v FROM earnings e {$join}
                     WHERE e.operator_type='SLTB' AND e.bus_reg_no=? AND e.date >= ? AND e.date < ?",
                    [$busReg, $firstPrev, $firstThis]
                );

                // Last day income
                $lastDayIncome = $this->scalar(
                    "SELECT COALESCE(SUM(e.amount),0) v FROM earnings e {$join}
                     WHERE e.operator_type='SLTB' AND e.bus_reg_no=? AND DATE(e.date)=DATE(?)",
                    [$busReg, $latestDate]
                );

                // Efficiency (compared to highest revenue bus)
                $allBusesMax = $this->scalar(
                    "SELECT COALESCE(MAX(total),0) v FROM (
                        SELECT SUM(e.amount) as total FROM earnings e {$join}
                        WHERE e.operator_type='SLTB' 
                        GROUP BY e.bus_reg_no
                    ) t"
                );

                $efficiency = $allBusesMax > 0 ? (100.0 * $totalIncome / $allBusesMax) : 0;

                $result[$busReg === $bus1 ? 'bus1' : 'bus2'] = [
                    'reg_no' => $busReg,
                    'total_income' => $totalIncome,
                    'this_month' => $thisMonth,
                    'prev_month' => $prevMonth,
                    'last_day' => $lastDayIncome,
                    'efficiency' => $efficiency,
                ];
            }

            return $result;
        } catch (PDOException $e) {
            return ['bus1' => null, 'bus2' => null];
        }
    }

    /* ==================== Get All Bus List for Dropdown ==================== */
    public function getAllBuses(): array
    {
        try {
            $scope = $this->depotScope();
            $sql = "SELECT DISTINCT e.bus_reg_no FROM earnings e {$scope['join']} WHERE e.operator_type='SLTB' ORDER BY e.bus_reg_no ASC";
            $st = $this->pdo->prepare($sql);
            $st->execute($scope['params']);
            $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
            
            $buses = [];
            foreach ($rows as $r) {
                $buses[] = $r['bus_reg_no'];
            }
            return $buses;
        } catch (PDOException $e) {
            return [];
        }
    }

    /** SLTB buses available under the logged-in manager's depot. */
    public function getDepotBusesForSelect(bool $activeOnly = false): array
    {
        $depotId = $this->depotId();
        if (!$depotId) {
            return [];
        }

        try {
            $sql = "SELECT reg_no
                    FROM sltb_buses
                    WHERE sltb_depot_id = :depot
                      AND reg_no NOT IN ('PA-1001', 'PB-1002')";
            if ($activeOnly) {
                $sql .= " AND LOWER(COALESCE(status, 'active')) = 'active'";
            }
            $sql .= " ORDER BY reg_no ASC";

            $st = $this->pdo->prepare($sql);
            $st->execute([':depot' => $depotId]);
            return $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /** Rows for export/reporting scoped to the manager's SLTB depot. */
    public function exportRows(): array
    {
        $depotId = $this->depotId();
        if (!$depotId) {
            return [];
        }

        try {
            $sql = "SELECT e.earning_id, e.date, e.bus_reg_no, e.amount, e.source
                    FROM earnings e
                    JOIN sltb_buses sb ON sb.reg_no = e.bus_reg_no
                    WHERE e.operator_type = 'SLTB'
                      AND sb.sltb_depot_id = :depot
                                            AND sb.reg_no NOT IN ('PA-1001', 'PB-1002')
                    ORDER BY e.date DESC, e.earning_id DESC";
            $st = $this->pdo->prepare($sql);
            $st->execute([':depot' => $depotId]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /* ==================== Existing helpers ==================== */
    public function add(array $d): bool
    {
        if (!$this->depotId()) return false;
        try {
            $bus = trim($d['bus_reg_no'] ?? '');
            if ($bus === '') return false;
            if (!$this->busBelongsToDepot($bus)) return false;
            $sql = "INSERT INTO earnings (operator_type, bus_reg_no, date, amount, source)
                    VALUES ('SLTB', :bus_reg_no, :date, :amount, :source)";
            $st = $this->pdo->prepare($sql);
            return $st->execute([
                ':bus_reg_no' => $bus,
                ':date'       => $d['date'] ?? date('Y-m-d'),
                ':amount'     => (float)($d['amount'] ?? 0),
                ':source'     => $d['source'] ?? 'Cash',
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function update(int $id, array $d): bool
    {
        if (!$this->depotId()) return false;
        try {
            $bus = trim($d['bus_reg_no'] ?? '');
            if ($id <= 0 || $bus === '') return false;
            if (!$this->busBelongsToDepot($bus)) return false;

            $sql = "UPDATE earnings e
                    JOIN sltb_buses sb ON sb.reg_no = e.bus_reg_no AND sb.sltb_depot_id = :depot
                    SET e.bus_reg_no = :bus_reg_no,
                        e.date = :date,
                        e.amount = :amount,
                        e.source = :source
                    WHERE e.earning_id = :id
                      AND e.operator_type = 'SLTB'";
            $st = $this->pdo->prepare($sql);
            return $st->execute([
                ':id' => $id,
                ':depot' => $this->depotId(),
                ':bus_reg_no' => $bus,
                ':date' => $d['date'] ?? date('Y-m-d'),
                ':amount' => (float)($d['amount'] ?? 0),
                ':source' => $d['source'] ?? 'Cash',
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        if (!$this->depotId()) return false;
        try {
            $sql = "DELETE e FROM earnings e
                    JOIN sltb_buses sb ON sb.reg_no = e.bus_reg_no AND sb.sltb_depot_id = :depot
                    WHERE e.earning_id = :id
                      AND e.operator_type = 'SLTB'";
            $st = $this->pdo->prepare($sql);
            return $st->execute([':id' => $id, ':depot' => $this->depotId()]);
        } catch (PDOException $e) {
            return false;
        }
    }

    private function busBelongsToDepot(string $busReg): bool
    {
        try {
            $st = $this->pdo->prepare("SELECT 1 FROM sltb_buses WHERE reg_no = :reg AND sltb_depot_id = :depot AND reg_no NOT IN ('PA-1001', 'PB-1002') LIMIT 1");
            $st->execute([':reg' => $busReg, ':depot' => $this->depotId()]);
            return (bool)$st->fetchColumn();
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

