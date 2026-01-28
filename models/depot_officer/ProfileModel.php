<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;
use PDO;

class ProfileModel extends BaseModel
{
    public function findById(int $userId): ?array
    {
        $st = $this->pdo->prepare("SELECT user_id, first_name, last_name, email, phone, profile_image, role, status FROM users WHERE user_id=? LIMIT 1");
        $st->execute([$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateProfile(int $userId, array $d): bool
    {
        $first = trim($d['first_name'] ?? '');
        $last = trim($d['last_name'] ?? '');
        $email = trim($d['email'] ?? '');
        $phone = trim($d['phone'] ?? '');
        if ($first === '' || $last === '' || $email === '') return false;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;

        $st = $this->pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=? WHERE user_id=?");
        return $st->execute([$first, $last, $email, $phone, $userId]);
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

    public function updateProfileImage(int $userId, string $imagePath): bool
    {
        $st = $this->pdo->prepare("UPDATE users SET profile_image=? WHERE user_id=?");
        return $st->execute([$imagePath, $userId]);
    }

    public function deleteProfileImage(int $userId): bool
    {
        $st = $this->pdo->prepare("UPDATE users SET profile_image=NULL WHERE user_id=?");
        return $st->execute([$userId]);
    }

    public function emailTaken(string $email, int $excludeId): bool
    {
        $st = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email=? AND user_id!=?");
        $st->execute([$email, $excludeId]);
        return (int)$st->fetchColumn() > 0;
    }
}

