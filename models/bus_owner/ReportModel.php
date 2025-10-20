<?php
namespace App\models\bus_owner;

use PDO;

class ReportModel extends BaseModel
{
    /**
     * Compute key performance metrics using tracking_monitoring
     * Scope: today's data, operator_type='Private', owner’s buses.
     */
    public function getPerformanceMetrics(): array
    {
        $metrics = [
            'delayed_buses'     => 0,
            'average_rating'    => null, // no rating in schema; use reliability_index avg
            'speed_violations'  => 0,
            'long_wait_rate'    => 0,    // derive from avg_delay_min >= 10
        ];

        // delayed buses
        $sql = "SELECT COUNT(*) FROM tracking_monitoring tm
                JOIN private_buses b ON b.reg_no = tm.bus_reg_no
                WHERE tm.operator_type='Private' AND DATE(tm.snapshot_at)=CURDATE()
                  AND tm.operational_status='Delayed'";
        $params=[];
        if ($this->hasOperator()) { $sql .= " AND b.private_operator_id = :op"; $params[':op'] = $this->operatorId; }
        $st = $this->pdo->prepare($sql); $st->execute($params);
        $metrics['delayed_buses'] = (int)$st->fetchColumn();

        // speed violations sum
        $sql = "SELECT COALESCE(SUM(tm.speed_violations),0) FROM tracking_monitoring tm
                JOIN private_buses b ON b.reg_no = tm.bus_reg_no
                WHERE tm.operator_type='Private' AND DATE(tm.snapshot_at)=CURDATE()";
        $st = $this->pdo->prepare($sql); $st->execute($params);
        $metrics['speed_violations'] = (int)$st->fetchColumn();

        // average reliability_index as a proxy for rating
        $sql = "SELECT AVG(tm.reliability_index) FROM tracking_monitoring tm
                JOIN private_buses b ON b.reg_no = tm.bus_reg_no
                WHERE tm.operator_type='Private' AND DATE(tm.snapshot_at)=CURDATE()";
        $st = $this->pdo->prepare($sql); $st->execute($params);
        $avg = $st->fetchColumn();
        $metrics['average_rating'] = $avg !== null ? round((float)$avg, 1) : null;

        // long wait rate: % of records with avg_delay_min >= 10 today
        $sql = "SELECT SUM(CASE WHEN tm.avg_delay_min>=10 THEN 1 ELSE 0 END) AS long_wait,
                       COUNT(*) AS total
                FROM tracking_monitoring tm
                JOIN private_buses b ON b.reg_no = tm.bus_reg_no
                WHERE tm.operator_type='Private' AND DATE(tm.snapshot_at)=CURDATE()";
        $st = $this->pdo->prepare($sql); $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['long_wait'=>0,'total'=>0];
        $metrics['long_wait_rate'] = ($row['total'] > 0)
            ? round( ( (int)$row['long_wait'] / (int)$row['total'] ) * 100 )
            : 0;

        return $metrics;
    }

    /** Convenience: list top drivers (no rating column, sorted alphabetically) */
    public function topDrivers(int $limit = 5): array
    {
        $sql = "SELECT private_driver_id, full_name, status
                FROM private_drivers";
        $params = [];
        if ($this->hasOperator()) { $sql .= " WHERE private_operator_id = :op"; $params[':op'] = $this->operatorId; }
        $sql .= " ORDER BY full_name ASC LIMIT " . max(1, (int)$limit);
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
