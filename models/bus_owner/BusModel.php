<?php
namespace App\models\bus_owner;

use PDO;

class BusModel extends BaseModel
{
    /**
     * Get all buses belonging to the logged-in owner,
     * joined with timetables and routes to show route info.
     */
    public function all(): array
    {
        $sql = "SELECT 
                    b.reg_no AS bus_number,
                    b.private_operator_id,
                    b.chassis_no,
                    b.capacity,
                    b.status,
                    r.name AS route,
                    r.route_no AS route_number,
                    CONCAT('Near ', 
                        ELT(FLOOR(1 + RAND() * 5),
                            'Colombo', 'Galle', 'Nugegoda', 'Panadura', 'Kottawa')
                    ) AS current_location
                FROM private_buses b
                LEFT JOIN timetables t 
                    ON t.bus_reg_no = b.reg_no 
                    AND t.operator_type = 'Private'
                LEFT JOIN routes r 
                    ON r.route_id = t.route_id";

        $params = [];
        if ($this->hasOperator()) {
            $sql .= " WHERE b.private_operator_id = :op";
            $params[':op'] = $this->operatorId; // ✅ use property, not method
        }

        $sql .= " GROUP BY b.reg_no ORDER BY b.reg_no DESC";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Create a new private bus */
    public function create(array $d): bool
    {
        $sql = "INSERT INTO private_buses 
                    (reg_no, private_operator_id, chassis_no, capacity, status)
                VALUES (:reg_no, :op, :chassis_no, :capacity, :status)";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':reg_no'     => $d['reg_no'] ?? null,
            ':op'         => $d['private_operator_id'] ?? $this->operatorId,
            ':chassis_no' => $d['chassis_no'] ?? null,
            ':capacity'   => isset($d['capacity']) ? (int)$d['capacity'] : null,
            ':status'     => $d['status'] ?? 'Active',
        ]);
    }

    /** Update an existing bus record */
    public function update(string $regNo, array $d): bool
    {
        $sql = "UPDATE private_buses
                   SET chassis_no = :chassis_no,
                       capacity   = :capacity,
                       status     = :status
                 WHERE reg_no = :reg_no";
        $params = [
            ':chassis_no' => $d['chassis_no'] ?? null,
            ':capacity'   => isset($d['capacity']) ? (int)$d['capacity'] : null,
            ':status'     => $d['status'] ?? 'Active',
            ':reg_no'     => $regNo,
        ];

        if ($this->hasOperator()) {
            $sql .= " AND private_operator_id = :op";
            $params[':op'] = $this->operatorId; // ✅ fixed here too
        }

        $st = $this->pdo->prepare($sql);
        return $st->execute($params);
    }

    /** Delete a bus record (only from own fleet) */
    public function delete(string $regNo): bool
    {
        $sql = "DELETE FROM private_buses WHERE reg_no = :reg_no";
        $params = [':reg_no' => $regNo];
        if ($this->hasOperator()) {
            $sql .= " AND private_operator_id = :op";
            $params[':op'] = $this->operatorId; // ✅ fixed
        }
        $st = $this->pdo->prepare($sql);
        return $st->execute($params);
    }

    /** Count buses for the current operator */
    public function getCount(): int
    {
        if ($this->hasOperator()) {
            $st = $this->pdo->prepare(
                "SELECT COUNT(*) FROM private_buses WHERE private_operator_id = :op"
            );
            $st->execute([':op' => $this->operatorId]);
            return (int)$st->fetchColumn();
        }
        return (int)$this->pdo->query("SELECT COUNT(*) FROM private_buses")->fetchColumn();
    }

    /** Count buses by status (Active, Maintenance, Inactive) */
    public function getCountByStatus(string $status): int
    {
        $sql = "SELECT COUNT(*) FROM private_buses WHERE status = :s";
        $params = [':s' => $status];
        if ($this->hasOperator()) {
            $sql .= " AND private_operator_id = :op";
            $params[':op'] = $this->operatorId;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    /** Recent buses for dashboard cards */
    public function getRecent(int $limit = 5): array
    {
        $sql = "SELECT 
                    b.reg_no AS bus_number,
                    b.chassis_no,
                    b.capacity,
                    b.status,
                    r.name AS route,
                    r.route_no AS route_number
                FROM private_buses b
                LEFT JOIN timetables t 
                    ON t.bus_reg_no = b.reg_no 
                    AND t.operator_type = 'Private'
                LEFT JOIN routes r 
                    ON r.route_id = t.route_id";

        $params = [];
        if ($this->hasOperator()) {
            $sql .= " WHERE b.private_operator_id = :op";
            $params[':op'] = $this->operatorId;
        }

        $sql .= " GROUP BY b.reg_no ORDER BY b.reg_no DESC LIMIT " . max(1, (int)$limit);

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
