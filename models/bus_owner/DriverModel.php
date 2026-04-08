<?php
namespace App\models\bus_owner;

use PDO;
use App\models\common\BaseModel;

class DriverModel extends BaseModel
{
    /** Cache for this request */
    protected ?int $cachedOpId = null;

    /* =========================
     * Operator resolution (users.user_id first)
     * ========================= */
    private function userId(): int
    {
        // Accept common session shapes
        $u = $_SESSION['user'] ?? [];
        $candidates = [
            $u['user_id'] ?? null,
            $u['id'] ?? null,              // harmless if not present
            $_SESSION['user_id'] ?? null,
            $_SESSION['id'] ?? null,
        ];
        foreach ($candidates as $v) {
            $n = (int)($v ?? 0);
            if ($n > 0) return $n;
        }
        return 0;
    }

    private function resolveOperatorId(): int
    {
        if ($this->cachedOpId !== null) return $this->cachedOpId;

        $uid = $this->userId();
        if ($uid <= 0) return $this->cachedOpId = 0;

        // 1) users.user_id -> private_operator_id
        $st = $this->pdo->prepare("SELECT COALESCE(private_operator_id,0) FROM users WHERE user_id = :uid LIMIT 1");
        $st->execute([':uid' => $uid]);
        $op = (int)$st->fetchColumn();
        if ($op > 0) return $this->cachedOpId = $op;

        // 2) Fallback mapping: private_bus_owners.user_id / owner_user_id
        $st = $this->pdo->prepare("
            SELECT private_operator_id
              FROM private_bus_owners
             WHERE user_id = :uid OR owner_user_id = :uid2
             LIMIT 1
        ");
        $st->execute([':uid' => $uid, ':uid2' => $uid]);
        $op = (int)$st->fetchColumn();

        return $this->cachedOpId = ($op > 0 ? $op : 0);
    }

    private function operatorId(): int { return $this->resolveOperatorId(); }
    private function hasOperator(): bool { return $this->operatorId() > 0; }
    public  function getResolvedOperatorId(): int { return $this->operatorId(); }

    /* =========================
     * Drivers: lists & CRUD
     * ========================= */
    public function all(): array
    {
        $sql = "SELECT private_driver_id, private_operator_id, full_name, license_no, phone, status, suspend_reason
                  FROM private_drivers";
        $params = [];
        if ($this->hasOperator()) { $sql .= " WHERE private_operator_id = :op"; $params[':op'] = $this->operatorId(); }
        $sql .= " ORDER BY full_name ASC";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $d): bool
    {
        // Always use operator id resolved from server-side user row
        $op = $this->operatorId();
        if ($op <= 0) return false;

        $name = trim((string)($d['full_name'] ?? ''));
        if ($name === '') return false;

        $st = $this->pdo->prepare(
            "INSERT INTO private_drivers (private_operator_id, full_name, license_no, phone, status, suspend_reason)
             VALUES (:op, :name, :license, :phone, :status, :reason)"
        );
        try {
            return $st->execute([
                ':op'      => $op,
                ':name'    => $name,
                ':license' => $d['license_no'] ?? null,
                ':phone'   => $d['phone'] ?? null,
                ':status'  => $d['status'] ?? 'Active',
                ':reason'  => ($d['status'] ?? 'Active') === 'Suspended' ? ($d['suspend_reason'] ?? null) : null,
            ]);
        } catch (\PDOException $e) {
            // Handle duplicate license_no or other constraints
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    public function update(int $id, array $d): bool
    {
        if ($id <= 0) return false;

        $name = trim((string)($d['full_name'] ?? ''));
        if ($name === '') return false;

        $newStatus = $d['status'] ?? 'Active';
        $newReason = ($newStatus === 'Suspended') ? ($d['suspend_reason'] ?? null) : null;

        // Fetch old status before updating so we can log the transition
        $stOld = $this->pdo->prepare("SELECT status FROM private_drivers WHERE private_driver_id = :id LIMIT 1");
        $stOld->execute([':id' => $id]);
        $oldStatus = (string)($stOld->fetchColumn() ?: 'Active');

        $sql = "UPDATE private_drivers
                   SET full_name = :name,
                       license_no = :license,
                       phone = :phone,
                       status = :status,
                       suspend_reason = :reason
                 WHERE private_driver_id = :id";
        $params = [
            ':name'    => $name,
            ':license' => $d['license_no'] ?? null,
            ':phone'   => $d['phone'] ?? null,
            ':status'  => $newStatus,
            ':reason'  => $newReason,
            ':id'      => $id,
        ];
        if ($this->hasOperator()) { $sql .= " AND private_operator_id = :op"; $params[':op'] = $this->operatorId(); }
        $st = $this->pdo->prepare($sql);
        try {
            $ok = $st->execute($params);
            // Log the status change if it actually changed
            if ($ok && strcasecmp($oldStatus, $newStatus) !== 0) {
                $this->logStatusChange('driver', $id, $oldStatus, $newStatus, $newReason);
            }
            return $ok;
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) return false;
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) return false;

        $sql = "DELETE FROM private_drivers WHERE private_driver_id = :id";
        $params = [':id' => $id];
        if ($this->hasOperator()) { $sql .= " AND private_operator_id = :op"; $params[':op'] = $this->operatorId(); }
        $st = $this->pdo->prepare($sql);
        return $st->execute($params);
    }

    public function getCount(): int
    {
        if ($this->hasOperator()) {
            $st = $this->pdo->prepare("SELECT COUNT(*) FROM private_drivers WHERE private_operator_id = :op");
            $st->execute([':op' => $this->operatorId()]);
            return (int)$st->fetchColumn();
        }
        return (int)$this->pdo->query("SELECT COUNT(*) FROM private_drivers")->fetchColumn();
    }

    public function getTopDrivers(int $limit = 5): array
    {
        $limit = max(1, (int)$limit);
        $sql = "SELECT private_driver_id, full_name, license_no, phone, status
                  FROM private_drivers";
        $params = [];
        if ($this->hasOperator()) { $sql .= " WHERE private_operator_id = :op"; $params[':op'] = $this->operatorId(); }
        $sql .= " ORDER BY full_name ASC LIMIT {$limit}";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
     * Conductors: lists & CRUD
     * ========================= */
    public function allConductors(): array
    {
        $sql = "SELECT private_conductor_id, private_operator_id, full_name, phone, status, suspend_reason
                  FROM private_conductors";
        $params = [];
        if ($this->hasOperator()) { $sql .= " WHERE private_operator_id = :op"; $params[':op'] = $this->operatorId(); }
        $sql .= " ORDER BY full_name ASC";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createConductor(array $d): bool
    {
        $op = $this->operatorId();
        if ($op <= 0) return false;

        $name = trim((string)($d['full_name'] ?? ''));
        if ($name === '') return false;

        $st = $this->pdo->prepare(
            "INSERT INTO private_conductors (private_operator_id, full_name, phone, status, suspend_reason)
             VALUES (:op, :name, :phone, :status, :reason)"
        );
        return $st->execute([
            ':op'     => $op,
            ':name'   => $name,
            ':phone'  => $d['phone'] ?? null,
            ':status' => $d['status'] ?? 'Active',
            ':reason' => ($d['status'] ?? 'Active') === 'Suspended' ? ($d['suspend_reason'] ?? null) : null,
        ]);
    }

    public function updateConductor(int $id, array $d): bool
    {
        if ($id <= 0) return false;

        $name = trim((string)($d['full_name'] ?? ''));
        if ($name === '') return false;

        $newStatus = $d['status'] ?? 'Active';
        $newReason = ($newStatus === 'Suspended') ? ($d['suspend_reason'] ?? null) : null;

        // Fetch old status before updating
        $stOld = $this->pdo->prepare("SELECT status FROM private_conductors WHERE private_conductor_id = :id LIMIT 1");
        $stOld->execute([':id' => $id]);
        $oldStatus = (string)($stOld->fetchColumn() ?: 'Active');

        $sql = "UPDATE private_conductors
                   SET full_name = :name,
                       phone = :phone,
                       status = :status,
                       suspend_reason = :reason
                 WHERE private_conductor_id = :id";
        $params = [
            ':name'   => $name,
            ':phone'  => $d['phone'] ?? null,
            ':status' => $newStatus,
            ':reason' => $newReason,
            ':id'     => $id,
        ];
        if ($this->hasOperator()) { $sql .= " AND private_operator_id = :op"; $params[':op'] = $this->operatorId(); }
        $st = $this->pdo->prepare($sql);
        $ok = $st->execute($params);
        if ($ok && strcasecmp($oldStatus, $newStatus) !== 0) {
            $this->logStatusChange('conductor', $id, $oldStatus, $newStatus, $newReason);
        }
        return $ok;
    }

    public function deleteConductor(int $id): bool
    {
        if ($id <= 0) return false;

        $sql = "DELETE FROM private_conductors WHERE private_conductor_id = :id";
        $params = [':id' => $id];
        if ($this->hasOperator()) { $sql .= " AND private_operator_id = :op"; $params[':op'] = $this->operatorId(); }
        $st = $this->pdo->prepare($sql);
        return $st->execute($params);
    }

    public function getConductorCount(): int
    {
        if ($this->hasOperator()) {
            $st = $this->pdo->prepare("SELECT COUNT(*) FROM private_conductors WHERE private_operator_id = :op");
            $st->execute([':op' => $this->operatorId()]);
            return (int)$st->fetchColumn();
        }
        return (int)$this->pdo->query("SELECT COUNT(*) FROM private_conductors")->fetchColumn();
    }

    /* =========================
     * Status-change logging
     * ========================= */

    /**
     * Insert a row into private_staff_status_logs whenever status changes.
     */
    private function logStatusChange(
        string $staffType,
        int    $staffId,
        string $oldStatus,
        string $newStatus,
        ?string $reason
    ): void {
        try {
            $st = $this->pdo->prepare(
                "INSERT INTO private_staff_status_logs
                    (staff_type, staff_id, old_status, new_status, reason, changed_at)
                 VALUES (:type, :sid, :old, :new, :reason, NOW())"
            );
            $st->execute([
                ':type'   => $staffType,
                ':sid'    => $staffId,
                ':old'    => $oldStatus,
                ':new'    => $newStatus,
                ':reason' => $reason,
            ]);
        } catch (\PDOException $e) {
            // Log failure is non-fatal — swallow silently
            error_log('[DriverModel::logStatusChange] ' . $e->getMessage());
        }
    }

    /**
     * Return all status-change log entries for a given driver or conductor,
     * ordered newest first.
     *
     * @param string $staffType  'driver' | 'conductor'
     * @param int    $staffId    private_driver_id or private_conductor_id
     * @return array
     */
    public function getStatusLogs(string $staffType, int $staffId): array
    {
        if ($staffId <= 0) return [];
        $st = $this->pdo->prepare(
            "SELECT log_id, staff_type, staff_id, old_status, new_status, reason,
                    DATE_FORMAT(changed_at, '%Y-%m-%d %H:%i') AS changed_at_fmt
               FROM private_staff_status_logs
              WHERE staff_type = :type AND staff_id = :sid
              ORDER BY changed_at DESC"
        );
        $st->execute([':type' => $staffType, ':sid' => $staffId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return status logs for ALL drivers belonging to this operator,
     * keyed by driver id.
     */
    public function getAllDriverLogs(): array
    {
        $drivers = $this->all();
        $result  = [];
        foreach ($drivers as $d) {
            $id = (int)($d['private_driver_id'] ?? 0);
            if ($id > 0) {
                $result[$id] = $this->getStatusLogs('driver', $id);
            }
        }
        return $result;
    }

    /**
     * Return status logs for ALL conductors belonging to this operator,
     * keyed by conductor id.
     */
    public function getAllConductorLogs(): array
    {
        $conductors = $this->allConductors();
        $result     = [];
        foreach ($conductors as $c) {
            $id = (int)($c['private_conductor_id'] ?? 0);
            if ($id > 0) {
                $result[$id] = $this->getStatusLogs('conductor', $id);
            }
        }
        return $result;
    }
}
