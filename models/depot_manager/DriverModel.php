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

class DriverModel extends BaseModel
{
    public function metrics(): array
    {
        $total     = $this->countSafe("SELECT COUNT(*) c FROM drivers");
        $active    = $this->countSafe("SELECT COUNT(*) c FROM drivers WHERE status='Active'");
        $suspended = $this->countSafe("SELECT COUNT(*) c FROM drivers WHERE status='Suspended'");
        $todayLogs = $this->countSafe("SELECT COUNT(*) c FROM driver_logs WHERE DATE(created_at)=CURDATE()");

        return [
            ['label' => 'Total Drivers', 'value' => (string)$total],
            ['label' => 'Active',        'value' => (string)$active],
            ['label' => 'Suspended',     'value' => (string)$suspended],
            ['label' => 'Logs Today',    'value' => (string)$todayLogs],
        ];
    }

    public function driverActivities(): array
    {
        try {
            $sql = "SELECT l.id, d.name AS driver_name, b.reg_no, l.activity, l.created_at
                    FROM driver_logs l
                    LEFT JOIN drivers d ON d.id=l.driver_id
                    LEFT JOIN buses b   ON b.id=l.bus_id
                    ORDER BY l.created_at DESC
                    LIMIT 100";
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function conductorActivities(): array
    {
        try {
            $sql = "SELECT l.id, c.name AS conductor_name, b.reg_no, l.activity, l.created_at
                    FROM conductor_logs l
                    LEFT JOIN conductors c ON c.id=l.conductor_id
                    LEFT JOIN buses b      ON b.id=l.bus_id
                    ORDER BY l.created_at DESC
                    LIMIT 100";
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function createDriver(array $d): bool
    {
        try {
            $sql = "INSERT INTO drivers (name, nic, license_no, phone, status, hired_at, depot_id)
                    VALUES (:name, :nic, :license_no, :phone, 'Active', NOW(), :depot_id)";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([
                ':name'       => $d['name'] ?? '',
                ':nic'        => $d['nic'] ?? '',
                ':license_no' => $d['license_no'] ?? '',
                ':phone'      => $d['phone'] ?? null,
                ':depot_id'   => $d['depot_id'] ?? ($_SESSION['user']['depot_id'] ?? null),
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateDriver(array $d): bool
    {
        try {
            $sql = "UPDATE drivers
                       SET name=:name, nic=:nic, license_no=:license_no, phone=:phone
                     WHERE id=:id";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([
                ':name'       => $d['name'] ?? '',
                ':nic'        => $d['nic'] ?? '',
                ':license_no' => $d['license_no'] ?? '',
                ':phone'      => $d['phone'] ?? null,
                ':id'         => (int)($d['id'] ?? 0),
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function setStatus(int $id, string $status): bool
    {
        $status = in_array($status, ['Active','Suspended'], true) ? $status : 'Active';
        try {
            $st = $this->pdo->prepare("UPDATE drivers SET status=?, updated_at=NOW() WHERE id=?");
            return $st->execute([$status, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deleteDriver(int $id): bool
    {
        try {
            $st = $this->pdo->prepare("DELETE FROM drivers WHERE id=?");
            return $st->execute([$id]);
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
