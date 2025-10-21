<?php
namespace App\models\bus_owner;

use PDO;

class ProfileModel extends BaseModel
{
    private function currentUserId(): ?int {
        return $_SESSION['user']['user_id'] ?? null;
    }

    /** Get current owner's full profile */
    public function getProfile(): ?array {
        $uid = $this->currentUserId();
        if (!$uid) return null;

        $sql = "SELECT u.user_id, u.full_name, u.email, u.phone, pbo.name AS company_name, 
                       pbo.reg_no, pbo.contact_phone, pbo.contact_email
                FROM users u
                LEFT JOIN private_bus_owners pbo ON pbo.private_operator_id = u.private_operator_id
                WHERE u.user_id = ?";
        $st = $this->pdo->prepare($sql);
        $st->execute([$uid]);
        return $st->fetch(PDO::FETCH_ASSOC);
    }

    /** Update profile (user + private_bus_owners info) */
    public function updateProfile(array $d): bool {
        $uid = $this->currentUserId();
        if (!$uid) return false;

        $this->pdo->beginTransaction();

        try {
            // update users
            $st = $this->pdo->prepare("UPDATE users 
                SET full_name=?, email=?, phone=? WHERE user_id=?");
            $st->execute([
                trim($d['full_name'] ?? ''), trim($d['email'] ?? ''), trim($d['phone'] ?? ''), $uid
            ]);

            // update private_bus_owners
            $st2 = $this->pdo->prepare("UPDATE private_bus_owners p
                JOIN users u ON u.private_operator_id = p.private_operator_id
                SET p.name=?, p.reg_no=?, p.contact_phone=?, p.contact_email=?
                WHERE u.user_id=?");
            $st2->execute([
                trim($d['company_name'] ?? ''), trim($d['reg_no'] ?? ''), 
                trim($d['company_phone'] ?? ''), trim($d['company_email'] ?? ''), $uid
            ]);

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /** Change password */
    public function changePassword(array $d): bool {
        $uid = $this->currentUserId();
        if (!$uid) return false;

        $cur = $d['current_password'] ?? '';
        $new = $d['new_password'] ?? '';

        $st = $this->pdo->prepare("SELECT password_hash FROM users WHERE user_id=?");
        $st->execute([$uid]);
        $hash = $st->fetchColumn();

        if (!$hash || !password_verify($cur, $hash)) return false;

        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $up = $this->pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=?");
        return $up->execute([$newHash, $uid]);
    }

    /** Delete own account (cascade handled by DB) */
    public function deleteAccount(): bool {
        $uid = $this->currentUserId();
        if (!$uid) return false;

        $st = $this->pdo->prepare("DELETE FROM users WHERE user_id=?");
        return $st->execute([$uid]);
    }
}
