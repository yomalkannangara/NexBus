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

        // Dummy fallback when everything is zero
        if ($total === 0 && $active === 0 && $suspended === 0 && $todayLogs === 0) {
            return [
                ['label' => 'Total Drivers', 'value' => '28'],
                ['label' => 'Active',        'value' => '24'],
                ['label' => 'Suspended',     'value' => '4'],
                ['label' => 'Logs Today',    'value' => '17'],
            ];
        }

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
            $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Dummy fallback when no rows
            if (!$rows) {
                $rows = [
                    ['id' => 1, 'driver_name' => 'J. Perera',   'reg_no' => 'NB-1234', 'activity' => 'Checked in for route 138',  'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
                    ['id' => 2, 'driver_name' => 'A. Silva',    'reg_no' => 'NB-7788', 'activity' => 'Completed morning trip',    'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
                    ['id' => 3, 'driver_name' => 'K. Fernando', 'reg_no' => 'NB-4521', 'activity' => 'Break scheduled at depot', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))],
                ];
            }
            return $rows;
        } catch (PDOException $e) {
            // Dummy fallback on error
            return [
                ['id' => 1, 'driver_name' => 'J. Perera',   'reg_no' => 'NB-1234', 'activity' => 'Checked in for route 138',  'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
                ['id' => 2, 'driver_name' => 'A. Silva',    'reg_no' => 'NB-7788', 'activity' => 'Completed morning trip',    'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
                ['id' => 3, 'driver_name' => 'K. Fernando', 'reg_no' => 'NB-4521', 'activity' => 'Break scheduled at depot', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))],
            ];
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
            $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Dummy fallback when no rows
            if (!$rows) {
                $rows = [
                    ['id' => 11, 'conductor_name' => 'D. Jayasinghe', 'reg_no' => 'NB-3399', 'activity' => 'Ticket audit completed',    'created_at' => date('Y-m-d H:i:s', strtotime('-45 minutes'))],
                    ['id' => 12, 'conductor_name' => 'M. Peris',      'reg_no' => 'NB-5566', 'activity' => 'Assisted passenger boarding','created_at' => date('Y-m-d H:i:s', strtotime('-90 minutes'))],
                ];
            }
            return $rows;
        } catch (PDOException $e) {
            // Dummy fallback on error
            return [
                ['id' => 11, 'conductor_name' => 'D. Jayasinghe', 'reg_no' => 'NB-3399', 'activity' => 'Ticket audit completed',    'created_at' => date('Y-m-d H:i:s', strtotime('-45 minutes'))],
                ['id' => 12, 'conductor_name' => 'M. Peris',      'reg_no' => 'NB-5566', 'activity' => 'Assisted passenger boarding','created_at' => date('Y-m-d H:i:s', strtotime('-90 minutes'))],
            ];
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
