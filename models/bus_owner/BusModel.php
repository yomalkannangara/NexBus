<?php
namespace App\models\bus_owner;

use PDO;

class BusModel extends BaseModel
{
    // Table: private_buses (reg_no PK, private_operator_id, chassis_no, capacity, status)
    public function all(): array
    {
        $sql = "SELECT reg_no, private_operator_id, chassis_no, capacity, status
                FROM private_buses";
        $params = [];
        if ($this->hasOperator()) { $sql .= " WHERE private_operator_id = :op"; $params[':op'] = $this->operatorId; }
        $sql .= " ORDER BY reg_no DESC";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $d): bool
    {
        $sql = "INSERT INTO private_buses (reg_no, private_operator_id, chassis_no, capacity, status)
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
        // Enforce owner scope if set
        if ($this->hasOperator()) {
            $sql .= " AND private_operator_id = :op";
            $params[':op'] = $this->operatorId;
        }
        $st = $this->pdo->prepare($sql);
        return $st->execute($params);
    }

    public function delete(string $regNo): bool
    {
        $sql = "DELETE FROM private_buses WHERE reg_no = :reg_no";
        $params = [':reg_no' => $regNo];
        if ($this->hasOperator()) { $sql .= " AND private_operator_id = :op"; $params[':op'] = $this->operatorId; }
        $st = $this->pdo->prepare($sql);
        return $st->execute($params);
    }

    public function getCount(): int
    {
        if ($this->hasOperator()) {
            $st = $this->pdo->prepare("SELECT COUNT(*) FROM private_buses WHERE private_operator_id = :op");
            $st->execute([':op' => $this->operatorId]);
            return (int) $st->fetchColumn();
        }
        return (int) $this->pdo->query("SELECT COUNT(*) FROM private_buses")->fetchColumn();
    }

    public function getCountByStatus(string $status): int
    {
        $sql = "SELECT COUNT(*) FROM private_buses WHERE status = :s";
        $params = [':s' => $status];
        if ($this->hasOperator()) { $sql .= " AND private_operator_id = :op"; $params[':op'] = $this->operatorId; }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (int) $st->fetchColumn();
    }

    public function getRecent(int $limit = 5): array
    {
        $sql = "SELECT reg_no, chassis_no, capacity, status FROM private_buses";
        $params = [];
        if ($this->hasOperator()) { $sql .= " WHERE private_operator_id = :op"; $params[':op'] = $this->operatorId; }
        $sql .= " ORDER BY reg_no DESC LIMIT " . max(1, (int)$limit);
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
