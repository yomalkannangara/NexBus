<?php
namespace App\models\depot_manager;

use PDO;
use PDOException;
abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}
class FleetModel extends BaseModel
{
    public function summaryCards(): array
    {
        $total       = $this->countSafe("SELECT COUNT(*) c FROM buses");
        $active      = $this->countSafe("SELECT COUNT(*) c FROM buses WHERE status='Active'");
        $maintenance = $this->countSafe("SELECT COUNT(*) c FROM buses WHERE status='Maintenance'");
        $inactive    = $this->countSafe("SELECT COUNT(*) c FROM buses WHERE status IN ('Inactive','OutOfService')");

        return [
            ['label' => 'Total Buses',    'value' => (string)$total,       'class' => 'primary'],
            ['label' => 'Active Buses',   'value' => (string)$active,      'class' => 'green'],
            ['label' => 'In Maintenance', 'value' => (string)$maintenance, 'class' => 'yellow'],
            ['label' => 'Out of Service', 'value' => (string)$inactive,    'class' => 'red'],
        ];
    }

    public function list(): array
    {
        try {
            $sql = "SELECT b.id, b.reg_no, r.name AS route, r.route_no, b.status, b.current_location,
                           b.capacity, b.last_maintenance, b.next_service
                    FROM buses b
                    LEFT JOIN routes r ON r.route_id = b.route_id
                    ORDER BY b.id DESC
                    LIMIT 200";
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /* Filter dropdown data */
    public function routes(): array
    {
        try {
            $sql = "SELECT route_id, route_no, name
                    FROM routes
                    ORDER BY route_no+0, route_no";
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function buses(): array
    {
        try {
            $sql = "SELECT id, reg_no FROM buses ORDER BY reg_no";
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /* CRUD from your previous version kept as-is */
    public function createBus(array $d): bool
    {
        try {
            $sql = "INSERT INTO buses (reg_no, route_id, status, current_location, capacity, last_maintenance, next_service, depot_id)
                    VALUES (:reg_no, :route_id, :status, :loc, :cap, :lm, :ns, :depot_id)";
            $st = $this->pdo->prepare($sql);
            return $st->execute([
                ':reg_no'   => $d['reg_no'] ?? null,
                ':route_id' => $d['route_id'] ?? null,
                ':status'   => $d['status'] ?? 'Active',
                ':loc'      => $d['current_location'] ?? null,
                ':cap'      => isset($d['capacity']) ? (int)$d['capacity'] : null,
                ':lm'       => $d['last_maintenance'] ?? null,
                ':ns'       => $d['next_service'] ?? null,
                ':depot_id' => $d['depot_id'] ?? ($_SESSION['user']['depot_id'] ?? null),
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateBus(array $d): bool
    {
        try {
            $sql = "UPDATE buses
                       SET route_id=:route_id, status=:status, current_location=:loc, capacity=:cap,
                           last_maintenance=:lm, next_service=:ns
                     WHERE id=:id";
            $st = $this->pdo->prepare($sql);
            return $st->execute([
                ':route_id' => $d['route_id'] ?? null,
                ':status'   => $d['status'] ?? 'Active',
                ':loc'      => $d['current_location'] ?? null,
                ':cap'      => isset($d['capacity']) ? (int)$d['capacity'] : null,
                ':lm'       => $d['last_maintenance'] ?? null,
                ':ns'       => $d['next_service'] ?? null,
                ':id'       => (int)($d['id'] ?? 0),
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deleteBus($idOrReg): bool
    {
        try {
            if (is_numeric($idOrReg)) {
                $st = $this->pdo->prepare("DELETE FROM buses WHERE id=?");
                return $st->execute([(int)$idOrReg]);
            }
            $st = $this->pdo->prepare("DELETE FROM buses WHERE reg_no=?");
            return $st->execute([(string)$idOrReg]);
        } catch (PDOException $e) {
            return false;
        }
    }

    private function countSafe(string $sql, array $params = []): int
    {
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            return (int)($st->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }
}
