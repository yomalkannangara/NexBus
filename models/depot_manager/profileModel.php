<?php
namespace App\models\depot_manager;

use PDO;
use PDOException;
use App\models\common\BaseModel;

class ProfileModel extends BaseModel
{
    private string $lastError = '';

    public function getLastError(): string { return $this->lastError; }

    /** Fetch account from DB; fall back to session values if unavailable. */
    public function getAccount(int $userId): array
    {
        try {
            $st = $this->pdo->prepare(
                "SELECT user_id AS id, first_name, last_name, email, phone
                   FROM users WHERE user_id = ? LIMIT 1"
            );
            $st->execute([$userId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        } catch (PDOException $e) {
            // fall through to session values
        }
        $u = $_SESSION['user'] ?? [];
        return [
            'id'         => $u['user_id'] ?? $u['id'] ?? $userId,
            'first_name' => $u['first_name'] ?? '',
            'last_name'  => $u['last_name']  ?? '',
            'email'      => $u['email']      ?? '',
            'phone'      => $u['phone']      ?? '',
        ];
    }

    /** Update name/email/phone in the DB then sync to session. */
    public function updateDetails(int $userId, array $data): bool
    {
        $first = trim($data['first_name'] ?? '');
        $last  = trim($data['last_name']  ?? '');
        $email = trim($data['email']      ?? '');
        $phone = trim($data['phone']      ?? '');

        if ($first === '') {
            $this->lastError = 'First name is required';
            return false;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = 'Invalid email address';
            return false;
        }

        try {
            $st = $this->pdo->prepare(
                "UPDATE users SET first_name=?, last_name=?, email=?, phone=? WHERE user_id=?"
            );
            $ok = $st->execute([$first, $last ?: null, $email, $phone ?: null, $userId]);
            if (!$ok || $st->rowCount() === 0) {
                // rowCount may be 0 if nothing changed — that is still success
            }
        } catch (PDOException $e) {
            $this->lastError = 'Database error: ' . $e->getMessage();
            return false;
        }

        // Sync session
        $_SESSION['user']['first_name'] = $first;
        $_SESSION['user']['last_name']  = $last;
        $_SESSION['user']['email']      = $email;
        $_SESSION['user']['phone']      = $phone;

        return true;
    }

    /** Verify current password then persist new password hash to DB and session. */
    public function changePassword(int $userId, string $current, string $new, string $confirm): bool
    {
        if (strlen($new) < 6) {
            $this->lastError = 'Password must be at least 6 characters';
            return false;
        }
        if ($new !== $confirm) {
            $this->lastError = 'Passwords do not match';
            return false;
        }

        // Fetch stored hash from DB
        try {
            $st = $this->pdo->prepare(
                "SELECT password_hash FROM users WHERE user_id=? LIMIT 1"
            );
            $st->execute([$userId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = 'Database error';
            return false;
        }

        $storedHash = $row['password_hash'] ?? null;
        if ($storedHash && !password_verify($current, $storedHash)) {
            $this->lastError = 'Current password is incorrect';
            return false;
        }

        $newHash = password_hash($new, PASSWORD_DEFAULT);

        try {
            $st = $this->pdo->prepare(
                "UPDATE users SET password_hash=? WHERE user_id=?"
            );
            $st->execute([$newHash, $userId]);
        } catch (PDOException $e) {
            $this->lastError = 'Database error';
            return false;
        }

        $_SESSION['user']['password_hash'] = $newHash;
        return true;
    }
}

