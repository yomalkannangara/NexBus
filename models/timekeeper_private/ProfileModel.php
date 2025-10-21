<?php
namespace App\models\timekeeper_private;

class ProfileModel extends BaseModel
{
// models/timekeeper_private/ProfileModel.php
public function findById(int $userId): ?array {
    $st = $this->pdo->prepare("SELECT user_id, full_name, email, phone FROM users WHERE user_id=?");
    $st->execute([$userId]);
    return $st->fetch() ?: null;
}

    public function updateProfile(int $userId, array $d): bool
    {
        $st = $this->pdo->prepare("UPDATE users SET full_name=?, email=?, phone=? WHERE user_id=?");
        return $st->execute([ $d['full_name'], $d['email'], $d['phone'], $userId ]);
    }

    public function changePassword(int $userId, string $current, string $new, string $confirm): bool
    {
        if (!$new || $new !== $confirm) return false;
        // fetch current hash
    $st = $this->pdo->prepare("SELECT password_hash FROM users WHERE user_id=?");
        $st->execute([$userId]);
        $hash = (string)$st->fetchColumn();

        // if hash empty, allow set; else verify
        if ($hash && !password_verify($current, $hash)) return false;

        $newHash = password_hash($new, PASSWORD_BCRYPT);
    $up = $this->pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=?");
        return $up->execute([$newHash, $userId]);
    }
}
