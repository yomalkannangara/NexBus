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
        // unchanged (staff-only counts). If you later want a passenger card:
        // $passengers = (int)$this->pdo->query("SELECT COUNT(*) c FROM passengers")->fetch()['c'];
        $dm    = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role='DepotManager'")->fetch()['c'];
        $admin = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role='NTCAdmin'")->fetch()['c'];
        $owner = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role='PrivateBusOwner'")->fetch()['c'];
        $tk    = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role IN ('SLTBTimekeeper','PrivateTimekeeper')")->fetch()['c'];
        return compact('dm','admin','owner','tk');
    }

    public function list(array $filters = []): array {
        $sql = "SELECT user_id, first_name, last_name, email, phone, role, status, last_login, private_operator_id, sltb_depot_id
                FROM users";
        $where = [];
        $params = [];

        // Role filter
        if (!empty($filters['role'])) {
            $where[] = "role = :role";
            $params[':role'] = $filters['role'];
        }

        // Status filter
        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params[':status'] = $filters['status'];
        }

        // Linked org filter: '', 'none', 'owner:<id>', 'depot:<id>'
        if (!empty($filters['link'])) {
            $link = (string)$filters['link'];
            if ($link === 'none') {
                $where[] = "private_operator_id IS NULL AND sltb_depot_id IS NULL";
            } elseif (str_starts_with($link, 'owner:')) {
                $id = substr($link, 6);
                if ($id !== '') {
                    $where[] = "private_operator_id = :po";
                    $params[':po'] = $id;
                }
            } elseif (str_starts_with($link, 'depot:')) {
                $id = substr($link, 6);
                if ($id !== '') {
                    $where[] = "sltb_depot_id = :dp";
                    $params[':dp'] = $id;
                }
            }
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY first_name, last_name";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
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

            $employeeId = (int)($d['employee_id'] ?? 0);
            if ($employeeId <= 0) {
                throw new \InvalidArgumentException('Employee ID is required');
            }

            // Compute once so both tables share the SAME hash (bcrypt is salted)
            $plainPwd = $d['password'] ?? '123456';
            $pwdHash  = password_hash($plainPwd, PASSWORD_BCRYPT);

            // Insert into users (employee id goes to user_id)
            $st = $this->pdo->prepare("
                INSERT INTO users (user_id, role, first_name, last_name, email, phone, password_hash, status, private_operator_id, sltb_depot_id)
                VALUES (?,?,?,?,?,?,?, 'Active', ?, ?)
            ");
            $st->execute([
                $employeeId,
                $role,
                $d['first_name'],
                $d['last_name'],
                $d['email'] ?: null,
                $d['phone'] ?: null,
                $pwdHash,
                $operatorId,
                $depotId
            ]);

            $userId = $employeeId;

            // If passenger role → also create/attach a passengers row
            if ($role === 'Passenger') {
                $st2 = $this->pdo->prepare("
                    INSERT INTO passengers (user_id, first_name, last_name, email, phone, password_hash)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                      first_name=VALUES(first_name),
                      last_name=VALUES(last_name),
                      email=VALUES(email),
                      phone=VALUES(phone),
                      password_hash=VALUES(password_hash)
                ");
                $st2->execute([
                    $userId,
                    $d['first_name'],
                    $d['last_name'],
                    $d['email'] ?: null,
                    $d['phone'] ?: null,
                    $pwdHash,
                ]);
            }

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

            // Read current role BEFORE update (to detect flips)
            $st0 = $this->pdo->prepare("SELECT role FROM users WHERE user_id=?");
            $st0->execute([$userId]);
            $oldRole = (string)($st0->fetchColumn() ?: '');

            // Normalize linkage fields by role (same rules as create)
            $role       = $d['role'] ?? '';
            $firstName  = $d['first_name'] ?? '';
            $lastName   = $d['last_name'] ?? '';
            $email      = trim($d['email'] ?? '');
            $phone      = trim($d['phone'] ?? '');
            $depotId    = !empty($d['sltb_depot_id']) ? $d['sltb_depot_id'] : null;
            $operatorId = !empty($d['private_operator_id']) ? $d['private_operator_id'] : null;

            if (in_array($role, ['PrivateBusOwner','PrivateTimekeeper'], true)) {
                $depotId = null;
            } elseif (in_array($role, ['DepotManager','DepotOfficer','SLTBTimekeeper'], true)) {
                $operatorId = null;
            } else {
                $operatorId = null;
                $depotId = null;
            }

            $sets = "role=:role, first_name=:first_name, last_name=:last_name, email=:email, phone=:phone, private_operator_id=:po, sltb_depot_id=:dp";
            $args = [
                ':role'       => $role,
                ':first_name' => $firstName,
                ':last_name'  => $lastName,
                ':email'      => ($email !== '') ? $email : null,
                ':phone'      => ($phone !== '') ? $phone : null,
                ':po'         => $operatorId,
                ':dp'         => $depotId,
                ':user_id'    => $userId,
            ];

            // If password provided, hash once and push to both tables later
            $pwdProvided = false;
            $pwdHash = null;
            if (($d['password'] ?? '') !== '') {
                $pwdProvided = true;
                $pwdHash = password_hash($d['password'], PASSWORD_BCRYPT);
                $sets .= ", password_hash=:ph";
                $args[':ph'] = $pwdHash;
            }

            // Update users
            $sql = "UPDATE users SET $sets WHERE user_id=:user_id";
            $st  = $this->pdo->prepare($sql);
            $st->execute($args);

            // Keep passengers table in sync if role is Passenger
            if ($role === 'Passenger') {
                $sqlUpsert = "
                    INSERT INTO passengers (user_id, first_name, last_name, email, phone, password_hash)
                    SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone, u.password_hash
                    FROM users u WHERE u.user_id = ?
                    ON DUPLICATE KEY UPDATE
                        first_name=VALUES(first_name),
                        last_name=VALUES(last_name),
                        email=VALUES(email),
                        phone=VALUES(phone),
                        password_hash=VALUES(password_hash)
                ";
                $st2 = $this->pdo->prepare($sqlUpsert);
                $st2->execute([$userId]);

            } elseif ($oldRole === 'Passenger' && $role !== 'Passenger') {
                // SAFER DEFAULT: do nothing (keep passengers row) because other tables
                // (complaints, favourites, etc.) likely reference passengers.passenger_id.
                // If you truly want to remove it, ensure cascades are set and then:
                // $this->pdo->prepare("DELETE FROM passengers WHERE user_id=?")->execute([$userId]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function setStatus(int $userId, string $status): void {
        if (!in_array($status, ['Active','Suspended'], true)) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $st = $this->pdo->prepare('UPDATE users SET status = :status WHERE user_id = :user_id');
        $st->execute([':status' => $status, ':user_id' => $userId]);
    }

    public function delete(int $userId): void {
        // With passengers.user_id → users.user_id FK (ON DELETE RESTRICT by your Part A),
        // this will fail if a passenger row exists. That’s intentional for data safety.
        $st = $this->pdo->prepare('DELETE FROM users WHERE user_id = :user_id');
        $st->execute([':user_id' => $userId]);
    }
}
