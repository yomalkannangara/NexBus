<?php
namespace App\models\ntc_admin;

use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}

class DashboardModel extends BaseModel {
    public function stats(): array {
        $p = (int)$this->pdo->query("SELECT COUNT(*) c FROM private_buses")->fetch()['c'];
        $s = (int)$this->pdo->query("SELECT COUNT(*) c FROM sltb_buses")->fetch()['c'];
        $owners = (int)$this->pdo->query("SELECT COUNT(*) c FROM private_bus_owners")->fetch()['c'];
        $depots = (int)$this->pdo->query("SELECT COUNT(*) c FROM sltb_depots")->fetch()['c'];
        $routes = (int)$this->pdo->query("SELECT COUNT(*) c FROM routes WHERE is_active=1")->fetch()['c'];
        $complaints = (int)$this->pdo->query("SELECT COUNT(*) c FROM complaints WHERE DATE(created_at)=CURDATE()")->fetch()['c'];
        $delayed = (int)$this->pdo->query("SELECT COUNT(DISTINCT bus_reg_no) c FROM tracking_monitoring WHERE operational_status='Delayed' AND DATE(snapshot_at)=CURDATE()")->fetch()['c'];
        $broken = (int)$this->pdo->query("SELECT COUNT(DISTINCT bus_reg_no) c FROM tracking_monitoring WHERE operational_status='Breakdown' AND DATE(snapshot_at)=CURDATE()")->fetch()['c'];
        return compact('p','s','owners','depots','routes','complaints','delayed','broken');
    }
    public function routes(): array {
        return $this->pdo->query("SELECT route_id, route_no FROM routes ORDER BY route_no+0, route_no")->fetchAll();
    }
}
?>