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
        $rows = $this->pdo->query("SELECT route_id, route_no, stops_json FROM routes ORDER BY route_no+0, route_no")->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $stops = json_decode($row['stops_json'], true) ?: [];
            $first = $stops[0] ?? null;
            $last  = $stops[count($stops) - 1] ?? null;
            $label = function ($stop) {
                if (is_array($stop)) {
                    return $stop['name'] ?? $stop['stop'] ?? $stop['code'] ?? '';
                }
                return is_string($stop) ? $stop : '';
            };
            $start = $label($first);
            $end   = $label($last);
            $result[] = [
                'route_id' => $row['route_id'],
                'route_no' => $row['route_no'],
                'name'     => trim($start . ($end ? " - {$end}" : ''))
            ];
        }
        return $result;
    }
    private function isActivePrice($value): int {
        return ($value !== null && $value !== '' && floatval($value) != 0.0) ? 1 : 0;
    }
    public function create(array $d): void {
        $super = $d['super_luxury'] ?? null;
        $lux   = $d['luxury'] ?? null;
        $semi  = $d['semi_luxury'] ?? null;
        $norm  = $d['normal_service'] ?? null;
        $sql = "INSERT INTO fares (route_id, stage_number, super_luxury, luxury, semi_luxury, normal_service,
                is_super_luxury_active, is_luxury_active, is_semi_luxury_active, is_normal_service_active,
                effective_from, effective_to)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            $d['route_id'], $d['stage_number'], $super, $lux, $semi, $norm,
            $this->isActivePrice($super),
            $this->isActivePrice($lux),
            $this->isActivePrice($semi),
            $this->isActivePrice($norm),
            $d['effective_from'], $d['effective_to'] ?: null
        ]);
    }
    public function update(array $d): void {
        $super = $d['super_luxury'] ?? null;
        $lux   = $d['luxury'] ?? null;
        $semi  = $d['semi_luxury'] ?? null;
        $norm  = $d['normal_service'] ?? null;

        $sql = "UPDATE fares SET
                    route_id=?,
                    stage_number=?,
                    super_luxury=?, luxury=?, semi_luxury=?, normal_service=?,
                    is_super_luxury_active=?, is_luxury_active=?, is_semi_luxury_active=?, is_normal_service_active=?,
                    effective_from=?, effective_to=?
                WHERE fare_id=?";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            $d['route_id'], $d['stage_number'], $super, $lux, $semi, $norm,
            $this->isActivePrice($super),
            $this->isActivePrice($lux),
            $this->isActivePrice($semi),
            $this->isActivePrice($norm),
            $d['effective_from'], $d['effective_to'] ?: null,
            $d['fare_id']
        ]);
    }
    public function delete($id): void {
        $st = $this->pdo->prepare("DELETE FROM fares WHERE fare_id=?");
        $st->execute([$id]);
    }
}
?>