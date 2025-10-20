<?php
namespace App\models\bus_owner;

use PDO;
use PDOException;

class DashboardModel extends BaseModel
{
    /** Keep alias for controller compatibility */
    public function stats(): array { return $this->getStats(); }

    public function getStats(): array
    {
        return [
            'total_buses'   => $this->getBusCount(),
            'active_buses'  => $this->getActiveBusCount(),
            'total_drivers' => $this->getDriverCount(),
            'total_revenue' => $this->getTotalRevenue(),
        ];
    }

    private function getBusCount(): int
    {
        if ($this->hasOperator()) {
            $st = $this->pdo->prepare("SELECT COUNT(*) FROM private_buses WHERE private_operator_id = :op");
            $st->execute([':op' => $this->operatorId]);
            return (int) $st->fetchColumn();
        }
        return (int) $this->pdo->query("SELECT COUNT(*) FROM private_buses")->fetchColumn();
    }

    private function getActiveBusCount(): int
    {
        $sql = "SELECT COUNT(*) FROM private_buses WHERE status='Active'";
        $params = [];
        if ($this->hasOperator()) { $sql .= " AND private_operator_id = :op"; $params[':op'] = $this->operatorId; }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (int) $st->fetchColumn();
    }

    private function getDriverCount(): int
    {
        if ($this->hasOperator()) {
            $st = $this->pdo->prepare("SELECT COUNT(*) FROM private_drivers WHERE private_operator_id = :op");
            $st->execute([':op' => $this->operatorId]);
            return (int) $st->fetchColumn();
        }
        return (int) $this->pdo->query("SELECT COUNT(*) FROM private_drivers")->fetchColumn();
    }

    private function getTotalRevenue(): float
    {
        // earnings: earning_id, operator_type, bus_reg_no, date, amount, source
        // Sum only the owner's buses via join on reg_no
        $sql = "SELECT COALESCE(SUM(e.amount),0)
                FROM earnings e
                JOIN private_buses b ON b.reg_no = e.bus_reg_no
                WHERE e.operator_type='Private'";
        $params = [];
        if ($this->hasOperator()) { $sql .= " AND b.private_operator_id = :op"; $params[':op'] = $this->operatorId; }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (float) $st->fetchColumn();
    }
}
