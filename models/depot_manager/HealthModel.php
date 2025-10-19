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

class HealthModel extends BaseModel
{
    public function metrics(): array
    {
        $scheduled  = $this->countSafe("SELECT COUNT(*) c FROM maintenance_jobs WHERE status='Scheduled'");
        $ongoing    = $this->countSafe("SELECT COUNT(*) c FROM maintenance_jobs WHERE status='Ongoing'");
        $completed  = $this->countSafe("SELECT COUNT(*) c FROM maintenance_jobs WHERE status='Completed' AND DATE(updated_at)=CURDATE()");
        $breakdowns = $this->countSafe("SELECT COUNT(*) c FROM maintenance_jobs WHERE status='Breakdown' AND DATE(created_at)=CURDATE()");

        return [
            ['label' => 'Scheduled',  'value' => (string)$scheduled],
            ['label' => 'Ongoing',    'value' => (string)$ongoing],
            ['label' => 'Completed',  'value' => (string)$completed],
            ['label' => 'Breakdowns', 'value' => (string)$breakdowns],
        ];
    }

    public function ongoing(): array
    {
        try {
            $sql = "SELECT j.id, b.reg_no, j.job_type, j.priority, j.status, j.created_at
                    FROM maintenance_jobs j
                    LEFT JOIN buses b ON b.id=j.bus_id
                    WHERE j.status IN ('Scheduled','Ongoing','Breakdown')
                    ORDER BY j.created_at DESC
                    LIMIT 100";
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function completed(): array
    {
        try {
            $sql = "SELECT j.id, b.reg_no, j.job_type, j.priority, j.status, j.completed_at
                    FROM maintenance_jobs j
                    LEFT JOIN buses b ON b.id=j.bus_id
                    WHERE j.status='Completed'
                    ORDER BY j.completed_at DESC
                    LIMIT 100";
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function schedule(array $d): bool
    {
        try {
            $sql = "INSERT INTO maintenance_jobs (bus_id, job_type, priority, status, created_at, notes)
                    VALUES (:bus_id, :job_type, :priority, 'Scheduled', NOW(), :notes)";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([
                ':bus_id'  => (int)($d['bus_id'] ?? 0),
                ':job_type'=> $d['job_type'] ?? 'General',
                ':priority'=> $d['priority'] ?? 'Normal',
                ':notes'   => $d['notes'] ?? null,
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function complete(array $d): bool
    {
        try {
            $sql = "UPDATE maintenance_jobs
                       SET status='Completed', completed_at=NOW(), updated_at=NOW(), notes=CONCAT(IFNULL(notes,''), :noteAppend)
                     WHERE id=:id";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([
                ':noteAppend' => "\nCompleted: ".($d['note'] ?? ''),
                ':id'         => (int)($d['job_id'] ?? 0),
            ]);
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
