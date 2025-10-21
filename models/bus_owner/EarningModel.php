<?php
namespace App\models\bus_owner;

use PDO;

class EarningModel extends BaseModel
{
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
                    r.name AS route
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
        return $st->fetchAll(PDO::FETCH_ASSOC);
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
}
