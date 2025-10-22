<?php
namespace App\models\bus_owner;

use PDO;
use App\models\common\BaseModel; // same as used in other bus_owner models

class ReportModel extends BaseModel
{
    /** 
     * Resolve the current operator (private bus owner) from session 
     */
    private function operatorId(): ?int
    {
        $u = $_SESSION['user'] ?? null;
        return isset($u['private_operator_id']) ? (int)$u['private_operator_id'] : null;
    }

    /** 
     * Check if the current user has a private operator ID 
     */
    private function hasOperator(): bool
    {
        return (bool)$this->operatorId();
    }

    /**
     * Compute key performance metrics using tracking_monitoring
     */
    public function getPerformanceMetrics(): array
    {
        $metrics = [
            'delayed_buses'    => 0,
            'average_rating'   => null,
            'speed_violations' => 0,
            'long_wait_rate'   => 0,
            'total_complaints' => 0,
        ];

        $params = [];
        $opClause = '';
        if ($this->hasOperator()) {
            $opClause = " AND b.private_operator_id = :op";
            $params[':op'] = $this->operatorId();
        }

        // 1. Delayed buses
        $sql = "SELECT COUNT(*) FROM tracking_monitoring tm
                JOIN private_buses b ON b.reg_no = tm.bus_reg_no
                WHERE tm.operator_type='Private'
                  AND DATE(tm.snapshot_at)=CURDATE()
                  AND tm.operational_status='Delayed' $opClause";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $metrics['delayed_buses'] = (int)$st->fetchColumn();

        // 2. Speed violations
        $sql = "SELECT COALESCE(SUM(tm.speed_violations),0)
                FROM tracking_monitoring tm
                JOIN private_buses b ON b.reg_no = tm.bus_reg_no
                WHERE tm.operator_type='Private'
                  AND DATE(tm.snapshot_at)=CURDATE() $opClause";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $metrics['speed_violations'] = (int)$st->fetchColumn();

        // 3. Average reliability index
        $sql = "SELECT AVG(tm.reliability_index)
                FROM tracking_monitoring tm
                JOIN private_buses b ON b.reg_no = tm.bus_reg_no
                WHERE tm.operator_type='Private'
                  AND DATE(tm.snapshot_at)=CURDATE() $opClause";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $avg = $st->fetchColumn();
        $metrics['average_rating'] = $avg !== null ? round((float)$avg, 1) : null;

        // 4. Long wait rate
        $sql = "SELECT 
                    SUM(CASE WHEN tm.avg_delay_min>=10 THEN 1 ELSE 0 END) AS long_wait,
                    COUNT(*) AS total
                FROM tracking_monitoring tm
                JOIN private_buses b ON b.reg_no = tm.bus_reg_no
                WHERE tm.operator_type='Private'
                  AND DATE(tm.snapshot_at)=CURDATE() $opClause";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['long_wait' => 0, 'total' => 0];
        $metrics['long_wait_rate'] = ($row['total'] > 0)
            ? round(($row['long_wait'] / $row['total']) * 100)
            : 0;

        // 5. Total complaints (best-effort across common table names)
        $metrics['total_complaints'] = $this->countComplaints();

        return $metrics;
    }

    /**
     * Try to count complaints for current operator across likely feedback tables.
     * Adjust table/column names if your schema differs.
     */
    private function countComplaints(): int
    {
        $op = $this->operatorId();
        $candidates = [
            // table => operator column candidates (first found used)
            'passenger_feedback' => ['private_operator_id', 'operator_id'],
            'feedback'           => ['private_operator_id', 'operator_id'],
        ];

        foreach ($candidates as $table => $opCols) {
            try {
                $params = [];
                $where = "LOWER(type) = 'complaint'";
                if ($op) {
                    // Prefer first matching operator column that exists
                    foreach ($opCols as $col) {
                        // Try query with this operator column; fall back if it fails
                        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where} AND {$col} = :op";
                        $st = $this->pdo->prepare($sql);
                        $st->execute([':op' => $op]);
                        return (int)$st->fetchColumn();
                    }
                }
                // No operator filter
                $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
                $st = $this->pdo->prepare($sql);
                $st->execute($params);
                return (int)$st->fetchColumn();
            } catch (\Throwable $e) {
                // Try next candidate table
            }
        }

        return 0;
    }

    /** 
     * List top drivers for current operator 
     */
    public function topDrivers(int $limit = 5): array
    {
        $sql = "SELECT private_driver_id, full_name, status
                FROM private_drivers";
        $params = [];
        if ($this->hasOperator()) {
            $sql .= " WHERE private_operator_id = :op";
            $params[':op'] = $this->operatorId();
        }
        $sql .= " ORDER BY full_name ASC LIMIT " . max(1, (int)$limit);
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
