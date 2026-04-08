<?php
namespace App\models\bus_owner;

use PDO;

class EarningModel extends BaseModel
{
    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }

    /** Resolve operator ID */
    private function operatorId(): ?int
    {
        if (!empty($this->operatorId)) return (int)$this->operatorId;
        $u = $_SESSION['user'] ?? [];
        $op = $u['private_operator_id'] ?? null;
        if ($op) return $this->operatorId = (int)$op;

        $uid = $u['user_id'] ?? ($u['id'] ?? null);
        if ($uid) {
            $st = $this->pdo->prepare("SELECT private_operator_id FROM users WHERE user_id=? LIMIT 1");
            $st->execute([$uid]);
            $op = (int)$st->fetchColumn();
            if ($op > 0) return $this->operatorId = $op;
        }
        return null;
    }

    /** Check if bus belongs to logged owner */
    private function busBelongsToMe(string $regNo): bool
    {
        $op = $this->operatorId();
        if (!$op) return false;
        $st = $this->pdo->prepare("SELECT 1 FROM private_buses WHERE reg_no=:bus AND private_operator_id=:op LIMIT 1");
        $st->execute([':bus' => $regNo, ':op' => $op]);
        return (bool)$st->fetchColumn();
    }

    /** Create new earning */
    public function create(array $d): bool
    {
        $op = $this->operatorId();
        if (!$op) return false;

        $bus = trim($d['bus_reg_no'] ?? '');
        if ($bus === '' || !$this->busBelongsToMe($bus)) return false;

        $sql = "INSERT INTO earnings (operator_type, bus_reg_no, date, amount, source)
                VALUES ('Private', :bus, :date, :amount, :source)";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':bus'    => $bus,
            ':date'   => $d['date'] ?? date('Y-m-d'),
            ':amount' => (float)($d['amount'] ?? 0),
            ':source' => $d['source'] ?? null
        ]);
    }

    /** Update existing earning */
    public function update(int $id, array $d): bool
    {
        $op = $this->operatorId();
        if (!$op) return false;

        $bus = trim($d['bus_reg_no'] ?? '');
        if ($bus === '' || !$this->busBelongsToMe($bus)) return false;

        $sql = "UPDATE earnings e
                   JOIN private_buses b ON b.reg_no = e.bus_reg_no
                SET e.bus_reg_no=:bus, e.date=:date, e.amount=:amount, e.source=:source
              WHERE e.earning_id=:id
                AND b.private_operator_id=:op
                AND e.operator_type='Private'";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':bus'    => $bus,
            ':date'   => $d['date'] ?? date('Y-m-d'),
            ':amount' => (float)($d['amount'] ?? 0),
            ':source' => $d['source'] ?? null,
            ':id'     => $id,
            ':op'     => $op
        ]);
    }

    /** Delete earning */
    public function delete(int $id): bool
    {
        $op = $this->operatorId();
        if (!$op) return false;
        $sql = "DELETE e FROM earnings e
                 JOIN private_buses b ON b.reg_no = e.bus_reg_no
                WHERE e.earning_id=:id
                  AND b.private_operator_id=:op
                  AND e.operator_type='Private'";
        $st = $this->pdo->prepare($sql);
        return $st->execute([':id' => $id, ':op' => $op]);
    }

    /** Fetch all earnings (with route info) */
    public function getAll(): array
    {
        $op = $this->operatorId();
        if (!$op) return [];

        $sql = "SELECT 
                    e.earning_id,
                    e.date,
                    e.bus_reg_no,
                    e.amount,
                    e.source,
                    r.route_no AS route_number,
                    r.stops_json
                FROM earnings e
                JOIN private_buses b ON b.reg_no = e.bus_reg_no
                LEFT JOIN timetables t ON t.bus_reg_no = e.bus_reg_no
                LEFT JOIN routes r ON r.route_id = t.route_id
                WHERE b.private_operator_id = :op
                  AND e.operator_type = 'Private'
                GROUP BY e.earning_id
                ORDER BY e.date DESC, e.earning_id DESC";

        $st = $this->pdo->prepare($sql);
        $st->execute([':op' => $op]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as &$r) {
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
        }
        
        return $rows;
    }

    /** Get buses owned by this operator (active only or all) */
    public function getMyBuses(bool $activeOnly = true): array
    {
        $op = $this->operatorId();
        if (!$op) return [];
        $sql = "SELECT reg_no FROM private_buses WHERE private_operator_id=:op";
        if ($activeOnly) $sql .= " AND status='Active'";
        $sql .= " ORDER BY reg_no";
        $st = $this->pdo->prepare($sql);
        $st->execute([':op' => $op]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * KPI summary: total revenue, top route, active bus count.
     * Expenses are not stored separately, so we derive an expense estimate
     * (or just show 0 until an expenses table is added).
     */
    public function getKpiStats(): array
    {
        $op = $this->operatorId();
        if (!$op) return ['total_revenue' => 0, 'total_expenses' => 0, 'top_route' => 'N/A', 'active_buses' => 0];

        // Total revenue
        $st = $this->pdo->prepare(
            "SELECT COALESCE(SUM(e.amount), 0)
               FROM earnings e
               JOIN private_buses b ON b.reg_no = e.bus_reg_no
              WHERE e.operator_type = 'Private' AND b.private_operator_id = :op"
        );
        $st->execute([':op' => $op]);
        $totalRevenue = (float)$st->fetchColumn();

        // Active buses
        $st = $this->pdo->prepare(
            "SELECT COUNT(*) FROM private_buses WHERE private_operator_id = :op AND status = 'Active'"
        );
        $st->execute([':op' => $op]);
        $activeBuses = (int)$st->fetchColumn();

        // Top route (by total earnings)
        $st = $this->pdo->prepare(
            "SELECT r.route_no, SUM(e.amount) AS total
               FROM earnings e
               JOIN private_buses b ON b.reg_no = e.bus_reg_no
               LEFT JOIN timetables t ON t.bus_reg_no = e.bus_reg_no
               LEFT JOIN routes r ON r.route_id = t.route_id
              WHERE e.operator_type = 'Private' AND b.private_operator_id = :op
              GROUP BY r.route_no
              ORDER BY total DESC
              LIMIT 1"
        );
        $st->execute([':op' => $op]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $topRoute = $row && $row['route_no'] ? 'Route ' . $row['route_no'] : 'N/A';

        return [
            'total_revenue'  => $totalRevenue,
            'total_expenses' => 0,   // placeholder until expense tracking table is added
            'top_route'      => $topRoute,
            'active_buses'   => $activeBuses,
        ];
    }

    /**
     * Revenue for each of the last N days — for the line chart.
     * Returns ['labels' => [...], 'values' => [...]]
     *
     * NOTE: $days is cast to int at the signature level — safe to embed directly.
     * PDO named params do not work inside MySQL INTERVAL syntax.
     */
    public function getRevenueTrend(int $days = 7): array
    {
        $op = $this->operatorId();
        if (!$op) return ['labels' => [], 'values' => []];

        $days = max(1, (int)$days); // extra safety guard

        $st = $this->pdo->prepare(
            "SELECT DATE(e.date) AS day, COALESCE(SUM(e.amount), 0) AS total
               FROM earnings e
               JOIN private_buses b ON b.reg_no = e.bus_reg_no
              WHERE e.operator_type = 'Private'
                AND b.private_operator_id = :op
                AND DATE(e.date) >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY)
              GROUP BY DATE(e.date)
              ORDER BY day ASC"
        );
        $st->execute([':op' => $op]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        // Ensure all N days appear even if no data
        $map = [];
        foreach ($rows as $r) $map[$r['day']] = (float)$r['total'];

        $labels = [];
        $values = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('d M', strtotime($d));
            $values[] = $map[$d] ?? 0;
        }
        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Revenue grouped by route — for the doughnut chart.
     * Returns ['labels' => [...], 'values' => [...]]
     */
    public function getRevenueByRoute(): array
    {
        $op = $this->operatorId();
        if (!$op) return ['labels' => [], 'values' => []];

        $st = $this->pdo->prepare(
            "SELECT COALESCE(r.route_no, 'Unassigned') AS route_no,
                    COALESCE(SUM(e.amount), 0) AS total
               FROM earnings e
               JOIN private_buses b ON b.reg_no = e.bus_reg_no
               LEFT JOIN timetables t ON t.bus_reg_no = e.bus_reg_no
               LEFT JOIN routes r ON r.route_id = t.route_id
              WHERE e.operator_type = 'Private' AND b.private_operator_id = :op
              GROUP BY COALESCE(r.route_no, 'Unassigned')
              ORDER BY total DESC
              LIMIT 8"
        );
        $st->execute([':op' => $op]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        return [
            'labels' => array_map(fn($r) => 'Route ' . $r['route_no'], $rows),
            'values' => array_map(fn($r) => (float)$r['total'], $rows),
        ];
    }

    /** Unique route numbers for filter dropdown */
    public function getUniqueRoutes(): array
    {
        $op = $this->operatorId();
        if (!$op) return [];
        $st = $this->pdo->prepare(
            "SELECT DISTINCT r.route_no
               FROM earnings e
               JOIN private_buses b ON b.reg_no = e.bus_reg_no
               LEFT JOIN timetables t ON t.bus_reg_no = e.bus_reg_no
               LEFT JOIN routes r ON r.route_id = t.route_id
              WHERE e.operator_type = 'Private' AND b.private_operator_id = :op
                AND r.route_no IS NOT NULL
              ORDER BY r.route_no"
        );
        $st->execute([':op' => $op]);
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }
}
