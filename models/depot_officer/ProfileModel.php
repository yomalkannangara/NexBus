<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;
use PDO;

class ProfileModel extends BaseModel
{
    public function findById(int $userId): ?array
    {
        $st = $this->pdo->prepare("SELECT user_id, full_name, email, phone FROM users WHERE user_id=? LIMIT 1");
        $st->execute([$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateProfile(int $userId, array $d): bool
    {
        $full = trim($d['full_name'] ?? '');
        $email = trim($d['email'] ?? '');
        $phone = trim($d['phone'] ?? '');
        if ($full === '' || $email === '') return false;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;

        $st = $this->pdo->prepare("UPDATE users SET full_name=?, email=?, phone=? WHERE user_id=?");
        return $st->execute([$full, $email, $phone, $userId]);
    }

    public function changePassword(int $userId, string $current, string $new, string $confirm): bool
    {
        if ($new === '' || $new !== $confirm || strlen($new) < 8) return false;

        $st = $this->pdo->prepare("SELECT password_hash FROM users WHERE user_id=? LIMIT 1");
        $st->execute([$userId]);
        $hash = (string)$st->fetchColumn();
        if (!$hash || !password_verify($current, $hash)) return false;

        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $up = $this->pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=?");
        return $up->execute([$newHash, $userId]);
    }
}
