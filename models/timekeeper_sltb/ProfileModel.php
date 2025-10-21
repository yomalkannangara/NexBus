<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;
use PDO;

class ProfileModel extends BaseModel
{
    public function findById(int $id): ?array {
        $st = $this->pdo->prepare("SELECT user_id, full_name, email, phone, sltb_depot_id FROM users WHERE user_id=? LIMIT 1");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function emailTaken(string $email, int $excludeId): bool {
        $st = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email=? AND user_id<>?");
        $st->execute([$email, $excludeId]);
        return (int)$st->fetchColumn() > 0;
    }

    public function updateProfile(int $id, array $d): bool {
        $full = trim($d['full_name'] ?? '');
        $email= trim($d['email'] ?? '');
        $phone= trim($d['phone'] ?? '');

        if ($full === '' || $email === '') return false;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        if ($this->emailTaken($email, $id)) return false;

        $st = $this->pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, updated_at=NOW() WHERE id=?");
        return $st->execute([$full, $email, $phone, $id]);
    }

    public function changePassword(int $id, string $current, string $new, string $confirm): bool {
        if ($new === '' || $new !== $confirm || strlen($new) < 8) return false;

        $st = $this->pdo->prepare("SELECT password_hash FROM users WHERE id=?");
        $st->execute([$id]);
        $hash = $st->fetchColumn();
        if (!$hash || !password_verify($current, (string)$hash)) return false;

        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $up = $this->pdo->prepare("UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?");
        return $up->execute([$newHash, $id]);
    }
}
