<?php
namespace App\models\bus_owner;

use PDO;

class EarningModel extends BaseModel
{
    /**
     * Ensure we know the current operator id. Uses (1) BaseModel value if present,
     * then (2) $_SESSION['user']['private_operator_id'], then (3) users table by user_id.
     */
    private function resolvedOperatorId(): ?int
    {
        // If BaseModel already set one, keep it
        if (isset($this->operatorId) && (int)$this->operatorId > 0) {
            return (int)$this->operatorId;
        }

        // Session: expected in most flows
        $op = $_SESSION['user']['private_operator_id'] ?? null;
        if ($op) {
            $this->operatorId = (int)$op;
            return $this->operatorId;
        }

        // Fallback: look up via users table
        $uid = $_SESSION['user']['user_id'] ?? ($_SESSION['user']['id'] ?? null);
        if ($uid) {
            $st = $this->pdo->prepare("SELECT private_operator_id FROM users WHERE user_id = :u LIMIT 1");
            $st->execute([':u' => (int)$uid]);
            $op = (int)$st->fetchColumn();
            if ($op > 0) {
                $this->operatorId = $op;
                return $op;
            }
        }

        return null;
    }

    /** Helper: make sure operatorId is set before owner-scoped work */
    private function ensureOperator(): bool
    {
        if (method_exists($this, 'hasOperator') && $this->hasOperator()) {
            // BaseModel already has it
            return true;
        }
        return (bool)$this->resolvedOperatorId();
    }

    /**
     * Create a private earning row only if the bus belongs to the logged-in owner.
     */
    public function create(array $d): bool
    {
        if (!$this->ensureOperator()) return false;

        $bus    = trim((string)($d['bus_reg_no'] ?? ''));
        $date   = (string)($d['date'] ?? null);
        $amount = isset($d['amount']) ? (float)$d['amount'] : 0.0;
        $source = $d['source'] ?? null;

        if ($bus === '' || !$date) return false;
        if (!$this->busBelongsToMe($bus)) return false;

        $sql = "INSERT INTO earnings (operator_type, bus_reg_no, `date`, amount, `source`)
                VALUES ('Private', :bus, :date, :amount, :source)";
        $st  = $this->pdo->prepare($sql);
        return $st->execute([
            ':bus'    => $bus,
            ':date'   => $date,
            ':amount' => $amount,
            ':source' => $source,
        ]);
    }

    /**
     * Update only if the row is Private AND its current/new bus is owned by me.
     * Uses UPDATE .. JOIN to enforce ownership at SQL level.
     */
    public function update(int $id, array $d): bool
    {
        if (!$this->ensureOperator()) return false;

        $bus    = trim((string)($d['bus_reg_no'] ?? ''));
        $date   = (string)($d['date'] ?? null);
        $amount = isset($d['amount']) ? (float)$d['amount'] : 0.0;
        $source = $d['source'] ?? null;

        if ($bus === '' || !$date) return false;
        if (!$this->busBelongsToMe($bus)) return false;

        $sql = "UPDATE earnings e
                  JOIN private_buses b ON b.reg_no = e.bus_reg_no
                 SET e.bus_reg_no = :bus,
                     e.`date`     = :date,
                     e.amount     = :amount,
                     e.`source`   = :source
               WHERE e.earning_id = :id
                 AND e.operator_type = 'Private'
                 AND b.private_operator_id = :op";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':bus'    => $bus,
            ':date'   => $date,
            ':amount' => $amount,
            ':source' => $source,
            ':id'     => $id,
            ':op'     => $this->operatorId,
        ]);
    }

    /**
     * Delete only if the row belongs to my fleet.
     */
    public function delete(int $id): bool
    {
        if (!$this->ensureOperator()) return false;

        $sql = "DELETE e FROM earnings e
                 JOIN private_buses b ON b.reg_no = e.bus_reg_no
                WHERE e.earning_id = :id
                  AND e.operator_type = 'Private'
                  AND b.private_operator_id = :op";
        $st = $this->pdo->prepare($sql);
        return $st->execute([':id' => $id, ':op' => $this->operatorId]);
    }

    /**
     * Total revenue for this owner (optional filters).
     * $filters = ['from' => 'YYYY-MM-DD', 'to' => 'YYYY-MM-DD', 'bus' => 'REG', 'source' => 'text']
     */
    public function getTotalRevenue(array $filters = []): float
    {
        if (!$this->ensureOperator()) return 0.0;

        $sql = "SELECT COALESCE(SUM(e.amount),0) AS total
                  FROM earnings e
                  JOIN private_buses b ON b.reg_no = e.bus_reg_no
                 WHERE e.operator_type = 'Private'
                   AND b.private_operator_id = :op";
        $params = [':op' => $this->operatorId];

        if (!empty($filters['from']))   { $sql .= " AND e.`date` >= :from"; $params[':from'] = $filters['from']; }
        if (!empty($filters['to']))     { $sql .= " AND e.`date` <= :to";   $params[':to']   = $filters['to']; }
        if (!empty($filters['bus']))    { $sql .= " AND e.bus_reg_no = :bus"; $params[':bus'] = $filters['bus']; }
        if (!empty($filters['source'])) { $sql .= " AND e.`source` = :src";   $params[':src'] = $filters['source']; }

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (float) $st->fetchColumn();
    }

    /**
     * Owner-scoped rows (optionally filterable).
     * $filters: same as getTotalRevenue()
     */
    public function getAll(array $filters = []): array
    {
        if (!$this->ensureOperator()) return [];

        $sql = "SELECT e.earning_id, e.bus_reg_no, e.`date`, e.amount, e.`source`
                  FROM earnings e
                  JOIN private_buses b ON b.reg_no = e.bus_reg_no
                 WHERE e.operator_type = 'Private'
                   AND b.private_operator_id = :op";
        $params = [':op' => $this->operatorId];

        if (!empty($filters['from']))   { $sql .= " AND e.`date` >= :from"; $params[':from'] = $filters['from']; }
        if (!empty($filters['to']))     { $sql .= " AND e.`date` <= :to";   $params[':to']   = $filters['to']; }
        if (!empty($filters['bus']))    { $sql .= " AND e.bus_reg_no = :bus"; $params[':bus'] = $filters['bus']; }
        if (!empty($filters['source'])) { $sql .= " AND e.`source` = :src";   $params[':src'] = $filters['source']; }

        $sql .= " ORDER BY e.`date` DESC, e.earning_id DESC";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Dropdown data: buses in my fleet.
     * @param bool $activeOnly true => only Active (default). false => include all statuses.
     * Returns: [['reg_no' => 'WP-NA-1234'], ...]
     */
    public function getMyBuses(bool $activeOnly = true): array
    {
        $op = $this->resolvedOperatorId();
        if (!$op) return [];

        $sql = "SELECT reg_no
                  FROM private_buses
                 WHERE private_operator_id = :op";
        if ($activeOnly) {
            $sql .= " AND status = 'Active'";
        }
        $sql .= " ORDER BY reg_no";

        $st = $this->pdo->prepare($sql);
        $st->execute([':op' => $op]);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Utility: check that a bus is in my fleet.
     */
    private function busBelongsToMe(string $regNo): bool
    {
        $op = $this->resolvedOperatorId();
        if (!$op) return false;

        $st = $this->pdo->prepare(
            "SELECT 1 FROM private_buses WHERE reg_no = :bus AND private_operator_id = :op LIMIT 1"
        );
        $st->execute([':bus' => $regNo, ':op' => $op]);
        return (bool) $st->fetchColumn();
    }
}
