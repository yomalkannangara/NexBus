<?php
namespace App\models\bus_owner;

use PDO;

// EarningModel handles all database operations for the Earnings page.
// It extends BaseModel, which already gives us $this->pdo (database connection)
// and $this->operatorId (the logged-in bus owner's ID).

class EarningModel extends BaseModel
{
    // ─────────────────────────────────────────────────────────────
    // HELPER: Check if a bus belongs to the logged-in owner
    // We use this before saving or deleting, as a security check.
    // ─────────────────────────────────────────────────────────────
    private function busBelongsToMe(string $regNo): bool
    {
        $st = $this->pdo->prepare(
            "SELECT 1 FROM private_buses
              WHERE reg_no = :bus
                AND private_operator_id = :op
              LIMIT 1"
        );
        $st->execute([':bus' => $regNo, ':op' => $this->operatorId]);
        return (bool) $st->fetchColumn();
    }

    // ─────────────────────────────────────────────────────────────
    // CREATE: Insert a new earning record into the database
    // ─────────────────────────────────────────────────────────────
    public function create(array $d): bool
    {
        // Safety check: make sure the owner is logged in
        if (!$this->operatorId) return false;

        $bus = trim($d['bus_reg_no'] ?? '');

        // Safety check: make sure the bus belongs to this owner
        if ($bus === '' || !$this->busBelongsToMe($bus)) return false;

        $sql = "INSERT INTO earnings (operator_type, bus_reg_no, date, amount, source)
                VALUES ('Private', :bus, :date, :amount, :source)";

        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':bus'    => $bus,
            ':date'   => $d['date']   ?? date('Y-m-d'),
            ':amount' => (float) ($d['amount'] ?? 0),
            ':source' => $d['source'] ?? null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // UPDATE: Edit an existing earning record
    // ─────────────────────────────────────────────────────────────
    public function update(int $id, array $d): bool
    {
        if (!$this->operatorId) return false;

        $bus = trim($d['bus_reg_no'] ?? '');
        if ($bus === '' || !$this->busBelongsToMe($bus)) return false;

        // JOIN with private_buses ensures the owner can only edit their own records
        $sql = "UPDATE earnings e
                   JOIN private_buses b ON b.reg_no = e.bus_reg_no
                SET e.bus_reg_no = :bus,
                    e.date       = :date,
                    e.amount     = :amount,
                    e.source     = :source
              WHERE e.earning_id        = :id
                AND b.private_operator_id = :op
                AND e.operator_type     = 'Private'";

        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':bus'    => $bus,
            ':date'   => $d['date']   ?? date('Y-m-d'),
            ':amount' => (float) ($d['amount'] ?? 0),
            ':source' => $d['source'] ?? null,
            ':id'     => $id,
            ':op'     => $this->operatorId,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // DELETE: Remove an earning record
    // ─────────────────────────────────────────────────────────────
    public function delete(int $id): bool
    {
        if (!$this->operatorId) return false;

        // JOIN ensures owner can only delete their own records
        $sql = "DELETE e FROM earnings e
                  JOIN private_buses b ON b.reg_no = e.bus_reg_no
                WHERE e.earning_id          = :id
                  AND b.private_operator_id = :op
                  AND e.operator_type       = 'Private'";

        $st = $this->pdo->prepare($sql);
        return $st->execute([':id' => $id, ':op' => $this->operatorId]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET ALL: Fetch all earning records for the logged-in owner
    // ─────────────────────────────────────────────────────────────
    public function getAll(): array
    {
        if (!$this->operatorId) return [];

        $sql = "SELECT
                    e.earning_id,
                    e.date,
                    e.bus_reg_no,
                    e.amount,
                    e.source,
                    r.route_no AS route_number
                FROM earnings e
                JOIN private_buses b ON b.reg_no = e.bus_reg_no
                LEFT JOIN timetables t ON t.bus_reg_no = e.bus_reg_no AND t.operator_type = 'Private'
                LEFT JOIN routes r ON r.route_id = t.route_id
                WHERE b.private_operator_id = :op
                  AND e.operator_type = 'Private'
                GROUP BY e.earning_id
                ORDER BY e.date DESC, e.earning_id DESC";

        $st = $this->pdo->prepare($sql);
        $st->execute([':op' => $this->operatorId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─────────────────────────────────────────────────────────────
    // GET MY BUSES: List buses owned by this operator (for dropdown)
    // ─────────────────────────────────────────────────────────────
    public function getMyBuses(bool $activeOnly = true): array
    {
        if (!$this->operatorId) return [];

        $sql = "SELECT reg_no FROM private_buses WHERE private_operator_id = :op";
        if ($activeOnly) $sql .= " AND status = 'Active'";
        $sql .= " ORDER BY reg_no";

        $st = $this->pdo->prepare($sql);
        $st->execute([':op' => $this->operatorId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─────────────────────────────────────────────────────────────
    // KPI STATS: Summary numbers shown at the top of the earnings page
    // Returns: total revenue, active bus count, top route
    // ─────────────────────────────────────────────────────────────
    public function getKpiStats(): array
    {
        if (!$this->operatorId) {
            return ['total_revenue' => 0, 'total_expenses' => 0, 'top_route' => 'N/A', 'active_buses' => 0];
        }

        // Total revenue (sum of all earnings)
        $st = $this->pdo->prepare(
            "SELECT COALESCE(SUM(e.amount), 0)
               FROM earnings e
               JOIN private_buses b ON b.reg_no = e.bus_reg_no
              WHERE e.operator_type = 'Private' AND b.private_operator_id = :op"
        );
        $st->execute([':op' => $this->operatorId]);
        $totalRevenue = (float) $st->fetchColumn();

        // Count active buses
        $st = $this->pdo->prepare(
            "SELECT COUNT(*) FROM private_buses
              WHERE private_operator_id = :op AND status = 'Active'"
        );
        $st->execute([':op' => $this->operatorId]);
        $activeBuses = (int) $st->fetchColumn();

        // Top route by total earnings
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
        $st->execute([':op' => $this->operatorId]);
        $row      = $st->fetch(PDO::FETCH_ASSOC);
        $topRoute = ($row && $row['route_no']) ? 'Route ' . $row['route_no'] : 'N/A';

        return [
            'total_revenue'  => $totalRevenue,
            'total_expenses' => 0,        // no expense table yet
            'top_route'      => $topRoute,
            'active_buses'   => $activeBuses,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // REVENUE TREND: Daily revenue for the last N days (line chart)
    // Returns: ['labels' => ['01 Apr', ...], 'values' => [1200, ...]]
    // ─────────────────────────────────────────────────────────────
    public function getRevenueTrend(int $days = 7): array
    {
        if (!$this->operatorId) return ['labels' => [], 'values' => []];

        $days = max(1, $days); // must be at least 1

        // NOTE: $days is cast to int above, so it's safe to put directly in SQL.
        // PDO named params don't work inside MySQL INTERVAL syntax.
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
        $st->execute([':op' => $this->operatorId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        // Build a lookup map: date string => total amount
        $map = [];
        foreach ($rows as $r) {
            $map[$r['day']] = (float) $r['total'];
        }

        // Build labels and values for every day, even days with no earnings (show 0)
        $labels = [];
        $values = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date     = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('d M', strtotime($date));
            $values[] = $map[$date] ?? 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    // ─────────────────────────────────────────────────────────────
    // REVENUE BY ROUTE: Earnings grouped by route (doughnut chart)
    // Returns: ['labels' => ['Route 1', ...], 'values' => [5000, ...]]
    // ─────────────────────────────────────────────────────────────
    public function getRevenueByRoute(): array
    {
        if (!$this->operatorId) return ['labels' => [], 'values' => []];

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
        $st->execute([':op' => $this->operatorId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        return [
            'labels' => array_map(fn($r) => 'Route ' . $r['route_no'], $rows),
            'values' => array_map(fn($r) => (float) $r['total'], $rows),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // UNIQUE ROUTES: For the filter dropdown on the earnings table
    // ─────────────────────────────────────────────────────────────
    public function getUniqueRoutes(): array
    {
        if (!$this->operatorId) return [];

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
        $st->execute([':op' => $this->operatorId]);
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>
