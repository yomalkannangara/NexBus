<?php
namespace App\models\depot_manager;

class ProfileModel
{
    private string $lastError = '';

    public function getLastError(): string { return $this->lastError; }

    public function getAccount(int $userId): array
    {
        // In a real app, fetch from DB by $userId.
        // For now, use session values.
        $u = $_SESSION['user'] ?? [];
        return [
            'id'        => $u['id']        ?? $userId,
            'full_name' => $u['full_name'] ?? '',
            'email'     => $u['email']     ?? '',
            'phone'     => $u['phone']     ?? '',
        ];
    }

    public function updateDetails(int $userId, array $data): bool
    {
        $name  = trim($data['full_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');

        if ($name === '') { $this->lastError = 'Name is required'; return false; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->lastError = 'Invalid email'; return false; }

        // TODO: persist to DB
        // Example:
        // $stmt = $pdo->prepare('UPDATE users SET full_name=?, email=?, phone=? WHERE id=?');
        // $stmt->execute([$name, $email, $phone, $userId]);

        // Keep session in sync
        $_SESSION['user']['full_name'] = $name;
        $_SESSION['user']['email']     = $email;
        $_SESSION['user']['phone']     = $phone;

        return true;
    }

    public function changePassword(int $userId, string $current, string $new, string $confirm): bool
    {
        if (strlen($new) < 6) { $this->lastError = 'Password too short'; return false; }
        if ($new !== $confirm) { $this->lastError = 'Passwords do not match'; return false; }

        $storedHash = $_SESSION['user']['password_hash'] ?? null;

        // If we have a hash, verify current; otherwise, allow change to initialize.
        if ($storedHash && !password_verify($current, $storedHash)) {
            $this->lastError = 'Current password is incorrect';
            return false;
        }

        $newHash = password_hash($new, PASSWORD_DEFAULT);

        // TODO: persist to DB
        // Example:
        // $stmt = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
        // $stmt->execute([$newHash, $userId]);

        $_SESSION['user']['password_hash'] = $newHash;
        return true;
    }
}
