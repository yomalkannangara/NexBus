<?php
namespace App\models\ntc_admin;

use PDO;
abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}
class AnalyticsModel extends BaseModel {

    public function busStatus() {
        $sql = "SELECT status, COUNT(*) as total FROM (
                    SELECT status FROM private_buses
                    UNION ALL
                    SELECT status FROM sltb_buses
                ) all_buses GROUP BY status";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function onTimePerformance() {
        $sql = "SELECT operational_status, COUNT(*) as total 
                FROM tracking_monitoring GROUP BY operational_status";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function revenueTrends() {
        $sql = "SELECT date, operator_type, SUM(amount) as total
                FROM earnings
                WHERE date >= CURDATE() - INTERVAL 7 DAY
                GROUP BY date, operator_type
                ORDER BY date ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function complaints() {
        $sql = "SELECT category, COUNT(*) as total 
                FROM complaints GROUP BY category";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function routeUtilization() {
        $sql = "SELECT r.route_no, AVG(t.utilization_pct) as utilization
                FROM tracking_monitoring t
                JOIN routes r ON t.route_id = r.route_id
                GROUP BY r.route_no";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
