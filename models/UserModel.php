<?php
require_once __DIR__.'/BaseModel.php';
class UserModel extends BaseModel {
    public function counts(): array {
        $dm = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role='DepotManager'")->fetch()['c'];
        $admin = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role='NTCAdmin'")->fetch()['c'];
        $owner = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role='PrivateBusOwner'")->fetch()['c'];
        $tk = (int)$this->pdo->query("SELECT COUNT(*) c FROM users WHERE role IN ('SLTBTimekeeper','PrivateTimekeeper')")->fetch()['c'];

        return compact('dm','admin','owner','tk');
    }
    public function list(): array {
        $sql = "SELECT user_id, full_name, email, phone, role, status, last_login FROM users ORDER BY full_name";
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
                $private_operator_id = null;

                if (($d['role'] ?? '') === 'PrivateBusOwner') {
                    // create the "Private Bus Owner" entity
                    $ownerName = $d['full_name'] ;
                    $regNo     = $d['org_reg_no'] ?? null; // optional field from form
                    $phone     = $d['phone'] ?? null;
                    $email     = $d['email'] ?? null;

                    $st = $this->pdo->prepare("
                        INSERT INTO private_bus_owners (name, reg_no, contact_phone, contact_email)
                        VALUES (?,?,?,?)
                    ");
                    $st->execute([$ownerName, $regNo, $phone, $email]);
                    $private_operator_id = (int)$this->pdo->lastInsertId();
                }
                $depotId    = !empty($d['sltb_depot_id']) ? $d['sltb_depot_id'] : null;
                $operatorId = !empty($d['private_operator_id']) ? $d['private_operator_id'] : null;

                // insert the user and link to the owner if it was created
                $st = $this->pdo->prepare("
                    INSERT INTO users (role, full_name, email, phone, password_hash, status, private_operator_id, sltb_depot_id)
                    VALUES (?,?,?,?,?, 'Active', ?, ?)
                ");
                $st->execute([
                    $d['role'],
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
}
?>