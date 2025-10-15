<?php
namespace App\models\ntc_admin;
use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}
class UserModel extends BaseModel {
    public function counts(): array {
        $dm = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role='DepotManager'")->fetch()['c'];
        $admin = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role='NTCAdmin'")->fetch()['c'];
        $owner = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role='PrivateBusOwner'")->fetch()['c'];
        $tk = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role IN ('SLTBTimekeeper','PrivateTimekeeper')")->fetch()['c'];

        return compact('dm','admin','owner','tk');
    }
    public function list(): array {
        // include linkage fields so the view can set data-*
        $sql = "SELECT user_id, full_name, email, phone, role, status, last_login, private_operator_id, sltb_depot_id
                FROM users
                ORDER BY full_name";
        return $this->pdo->query($sql)->fetchAll();
    }
    public function owners(): array {
        return $this->pdo->query("SELECT private_operator_id, name FROM private_bus_owners ORDER BY name")->fetchAll();
    }
    public function depots(): array {
        return $this->pdo->query("SELECT sltb_depot_id, name FROM sltb_depots ORDER BY name")->fetchAll();
    }
    public function create(array $d): void {
        $this->pdo->beginTransaction();
        try {
            // normalize by role (same rule used in update)
            $role = $d['role'] ?? '';
            $depotId    = !empty($d['sltb_depot_id']) ? $d['sltb_depot_id'] : null;
            $operatorId = !empty($d['private_operator_id']) ? $d['private_operator_id'] : null;

            if ($role === 'PrivateBusOwner') {
                $depotId = null;
            } elseif (in_array($role, ['DepotManager','DepotOfficer','SLTBTimekeeper'], true)) {
                $operatorId = null;
            } else {
                $operatorId = null;
                $depotId = null;
            }

            $st = $this->pdo->prepare("
                INSERT INTO users (role, full_name, email, phone, password_hash, status, private_operator_id, sltb_depot_id)
                VALUES (?,?,?,?,?, 'Active', ?, ?)
            ");
            $st->execute([
                $role,
                $d['full_name'],
                $d['email'] ?: null,
                $d['phone'] ?: null,
                password_hash($d['password'] ?: '123456', PASSWORD_BCRYPT),
                $operatorId,
                $depotId
            ]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    public function update(array $d): void {
    $this->pdo->beginTransaction();
    try {
        $userId = (int)($d['user_id'] ?? 0);
        if ($userId <= 0) {
            throw new \InvalidArgumentException('Invalid user id');
        }

        // Normalize linkage fields by role (same rules as create)
        $role       = $d['role'] ?? '';
        $fullName   = $d['full_name'] ?? '';
        $email      = trim($d['email'] ?? '');
        $phone      = trim($d['phone'] ?? '');
        $depotId    = !empty($d['sltb_depot_id']) ? $d['sltb_depot_id'] : null;
        $operatorId = !empty($d['private_operator_id']) ? $d['private_operator_id'] : null;

        if ($role === 'PrivateBusOwner') {
            $depotId = null;
        } elseif (in_array($role, ['DepotManager','DepotOfficer','SLTBTimekeeper'], true)) {
            $operatorId = null;
        } else {
            $operatorId = null;
            $depotId = null;
        }

        $sets  = "role=:role, full_name=:full_name, email=:email, phone=:phone, private_operator_id=:po, sltb_depot_id=:dp";
        $args = [
            ':role'       => $role,
            ':full_name'  => $fullName,
            ':email'      => ($email !== '') ? $email : null,
            ':phone'      => ($phone !== '') ? $phone : null,
            ':po'         => $operatorId,
            ':dp'         => $depotId,
            ':user_id'    => $userId,
        ];

        // If password provided, hash & include
        $pwd = $d['password'] ?? '';
        if ($pwd !== '') {
            $sets .= ", password_hash=:ph";
            $args[':ph'] = password_hash($pwd, PASSWORD_BCRYPT);
        }

        $sql = "UPDATE users SET $sets WHERE user_id=:user_id";
        $st  = $this->pdo->prepare($sql);
        $st->execute($args);

        $this->pdo->commit();
    } catch (\Throwable $e) {
        $this->pdo->rollBack();
        throw $e;
    }
}

    // Set status to Active/Suspended
    public function setStatus(int $userId, string $status): void {
        if (!in_array($status, ['Active','Suspended'], true)) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $st = $this->pdo->prepare('UPDATE users SET status = :status WHERE user_id = :user_id');
        $st->execute([':status' => $status, ':user_id' => $userId]);
    }

    // Delete user
    public function delete(int $userId): void {
        $st = $this->pdo->prepare('DELETE FROM users WHERE user_id = :user_id');
        $st->execute([':user_id' => $userId]);
    }
}
?>