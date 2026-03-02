<?php
namespace App\models\depot_manager;

use PDO;
use PDOException;
abstract class BaseModel {
    protected PDO $pdo;
    protected int $depotId = 0;
    
    public function __construct() {
        $this->pdo = $GLOBALS['db'];
        // Get current user's depot context
        $this->depotId = (int)($_SESSION['user']['sltb_depot_id'] ?? $_SESSION['user']['depot_id'] ?? 0);
    }
}

class DriverModel extends BaseModel
{
    public function metrics(): array
    {
        $total     = $this->countSafe("SELECT COUNT(*) c FROM sltb_drivers WHERE sltb_depot_id=?", [$this->depotId]);
        $active    = $this->countSafe("SELECT COUNT(*) c FROM sltb_drivers WHERE sltb_depot_id=? AND status='Active'", [$this->depotId]);
        $suspended = $this->countSafe("SELECT COUNT(*) c FROM sltb_drivers WHERE sltb_depot_id=? AND status='Suspended'", [$this->depotId]);
        
        $conTotal  = $this->countSafe("SELECT COUNT(*) c FROM sltb_conductors WHERE sltb_depot_id=?", [$this->depotId]);
        $conActive = $this->countSafe("SELECT COUNT(*) c FROM sltb_conductors WHERE sltb_depot_id=? AND status='Active'", [$this->depotId]);
        
        return [
            ['label' => 'Total Drivers', 'value' => (string)$total],
            ['label' => 'Active Drivers', 'value' => (string)$active],
            ['label' => 'Suspended', 'value' => (string)$suspended],
            ['label' => 'Conductors', 'value' => (string)$conTotal],
        ];
    }

    public function driverActivities(): array
    {
        return []; // Placeholder for now
    }

    public function conductorActivities(): array
    {
        return []; // Placeholder for now
    }

    /**
     * Return all drivers for this depot in bus_owner view compatible shape
     * Maps: sltb_driver_id -> private_driver_id, full_name, employee_no -> license_no, phone, status
     */
    public function allDrivers(): array
    {
        try {
            $sql = "SELECT sltb_driver_id AS private_driver_id, 
                           full_name, 
                           employee_no AS license_no, 
                           phone, 
                           status,
                           suspend_reason
                    FROM sltb_drivers 
                    WHERE sltb_depot_id=? 
                    ORDER BY full_name ASC";
            $st = $this->pdo->prepare($sql);
            $st->execute([$this->depotId]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Return all conductors for this depot in bus_owner view compatible shape
     * Maps: sltb_conductor_id -> private_conductor_id, full_name, phone, status
     */
    public function allConductors(): array
    {
        try {
            $sql = "SELECT sltb_conductor_id AS private_conductor_id, 
                           full_name, 
                           phone, 
                           status,
                           suspend_reason
                    FROM sltb_conductors 
                    WHERE sltb_depot_id=? 
                    ORDER BY full_name ASC";
            $st = $this->pdo->prepare($sql);
            $st->execute([$this->depotId]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * For compatibility with bus_owner JS that expects an operator id, return 0.
     */
    public function getResolvedOperatorId(): int
    {
        return 0;
    }

    public function createDriver(array $d): bool
    {
        try {
            // Map view field names to sltb_drivers columns
            $sql = "INSERT INTO sltb_drivers (sltb_depot_id, full_name, employee_no, phone, status)
                    VALUES (?, ?, ?, ?, ?)";
            $st = $this->pdo->prepare($sql);
            $status = $d['status'] ?? 'Active';
            $status = in_array($status, ['Active', 'Suspended'], true) ? $status : 'Active';
            
            return $st->execute([
                $this->depotId,
                $d['full_name'] ?? '',
                $d['license_no'] ?? '', // mapped from view field
                $d['phone'] ?? null,
                $status
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateDriver(array $d): bool
    {
        try {
            $sql = "UPDATE sltb_drivers
                    SET full_name=?, employee_no=?, phone=?, status=?, suspend_reason=?
                    WHERE sltb_driver_id=? AND sltb_depot_id=?";
            $st = $this->pdo->prepare($sql);
            $status = $d['status'] ?? 'Active';
            $status = in_array($status, ['Active', 'Suspended'], true) ? $status : 'Active';
            $reason = ($status === 'Suspended') ? ($d['suspend_reason'] ?? null) : null;
            
            return $st->execute([
                $d['full_name'] ?? '',
                $d['license_no'] ?? '',
                $d['phone'] ?? null,
                $status,
                $reason,
                (int)($d['private_driver_id'] ?? 0),
                $this->depotId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function setStatus(int $id, string $status): bool
    {
        $status = in_array($status, ['Active','Suspended'], true) ? $status : 'Active';
        try {
            $st = $this->pdo->prepare("UPDATE sltb_drivers SET status=? WHERE sltb_driver_id=? AND sltb_depot_id=?");
            return $st->execute([$status, $id, $this->depotId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deleteDriver(int $id): bool
    {
        try {
            $st = $this->pdo->prepare("DELETE FROM sltb_drivers WHERE sltb_driver_id=? AND sltb_depot_id=?");
            return $st->execute([$id, $this->depotId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // ===== CONDUCTOR METHODS =====
    
    public function createConductor(array $d): bool
    {
        try {
            $sql = "INSERT INTO sltb_conductors (sltb_depot_id, full_name, employee_no, phone, status)
                    VALUES (?, ?, ?, ?, ?)";
            $st = $this->pdo->prepare($sql);
            $status = $d['status'] ?? 'Active';
            $status = in_array($status, ['Active', 'Suspended'], true) ? $status : 'Active';
            
            return $st->execute([
                $this->depotId,
                $d['full_name'] ?? '',
                $d['employee_no'] ?? '',
                $d['phone'] ?? null,
                $status
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateConductor(array $d): bool
    {
        try {
            $sql = "UPDATE sltb_conductors
                    SET full_name=?, employee_no=?, phone=?, status=?, suspend_reason=?
                    WHERE sltb_conductor_id=? AND sltb_depot_id=?";
            $st = $this->pdo->prepare($sql);
            $status = $d['status'] ?? 'Active';
            $status = in_array($status, ['Active', 'Suspended'], true) ? $status : 'Active';
            $reason = ($status === 'Suspended') ? ($d['suspend_reason'] ?? null) : null;
            
            return $st->execute([
                $d['full_name'] ?? '',
                $d['employee_no'] ?? '',
                $d['phone'] ?? null,
                $status,
                $reason,
                (int)($d['private_conductor_id'] ?? 0),
                $this->depotId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function setConductorStatus(int $id, string $status): bool
    {
        $status = in_array($status, ['Active','Suspended'], true) ? $status : 'Active';
        try {
            $st = $this->pdo->prepare("UPDATE sltb_conductors SET status=? WHERE sltb_conductor_id=? AND sltb_depot_id=?");
            return $st->execute([$status, $id, $this->depotId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deleteConductor(int $id): bool
    {
        try {
            $st = $this->pdo->prepare("DELETE FROM sltb_conductors WHERE sltb_conductor_id=? AND sltb_depot_id=?");
            return $st->execute([$id, $this->depotId]);
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
