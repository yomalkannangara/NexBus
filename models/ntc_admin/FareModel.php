<?php
namespace App\models\ntc_admin;

use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}
class FareModel extends BaseModel {
    public function all(): array {
        $sql = "SELECT f.*, r.route_no FROM fares f JOIN routes r ON r.route_id=f.route_id
                ORDER BY r.route_no+0, r.route_no, f.stage_number";
        return $this->pdo->query($sql)->fetchAll();
    }
    public function routes(): array {
        return $this->pdo->query("SELECT route_id, route_no, name FROM routes ORDER BY route_no+0, route_no")->fetchAll();
    }
    public function create(array $d): void {
        $sql = "INSERT INTO fares (route_id, stage_number, super_luxury, luxury, semi_luxury, normal_service,
                is_super_luxury_active, is_luxury_active, is_semi_luxury_active, is_normal_service_active,
                effective_from, effective_to)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            $d['route_id'], $d['stage_number'], $d['super_luxury'] ?? null, $d['luxury'] ?? null,
            $d['semi_luxury'] ?? null, $d['normal_service'] ?? null,
            !empty($d['is_super_luxury_active']) ? 1 : 0,
            !empty($d['is_luxury_active']) ? 1 : 0,
            !empty($d['is_semi_luxury_active']) ? 1 : 0,
            !empty($d['is_normal_service_active']) ? 1 : 0,
            $d['effective_from'], $d['effective_to'] ?: null
        ]);
    }
    public function delete($id): void {
        $st = $this->pdo->prepare("DELETE FROM fares WHERE fare_id=?");
        $st->execute([$id]);
    }
}
?>