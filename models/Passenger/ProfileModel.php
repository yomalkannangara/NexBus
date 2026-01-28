<?php
namespace App\Models\Passenger;

use PDO;
use Throwable;

class ProfileModel {
    protected PDO $pdo;
    public function __construct() { $this->pdo = $GLOBALS['db']; }

    /** Read the current session user (adjust if your auth array differs). */
    public function sessionUser(): ?array {
        return $_SESSION['auth'] ?? $_SESSION['user'] ?? null;
    }

    /** Load combined view of user + passenger by user_id. */
    public function findByUserId(int $userId): ?array {
        $sql = "SELECT 
                    u.user_id, u.role, u.first_name, u.last_name, u.email, u.phone, u.status,
                    p.passenger_id
                FROM users u
                LEFT JOIN passengers p ON p.user_id = u.user_id
                WHERE u.user_id = ?";
        $st = $this->pdo->prepare($sql);
        $st->execute([$userId]);
        $row = $st->fetch();
        if (!$row) return null;

        return [
            'user_id'      => (int)$row['user_id'],
            'passenger_id' => $row['passenger_id'] ? (int)$row['passenger_id'] : null,
            'first_name'   => $row['first_name'],
            'last_name'    => $row['last_name'],
            'email'        => $row['email'],
            'phone'        => $row['phone'],
            'status'       => $row['status'] ?? 'Active',
            'role'         => $row['role'] ?? 'Passenger',
        ];
    }

    /** Ensure a passengers row exists for this user. Returns passenger_id. */
    public function ensurePassengerForUser(int $userId): int {
        $st = $this->pdo->prepare("SELECT passenger_id FROM passengers WHERE user_id=?");
        $st->execute([$userId]);
        $pid = $st->fetchColumn();
        if ($pid) return (int)$pid;

        $st = $this->pdo->prepare("INSERT INTO passengers (user_id) VALUES (?)");
        $st->execute([$userId]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Update both users and passengers in a single transaction. */
    public function updateProfile(int $userId, array $d): array {
        $first = trim($d['first_name'] ?? '');
        $last = trim($d['last_name'] ?? '');
        $email = trim($d['email'] ?? '');
        $phone = trim($d['phone'] ?? '');

        if ($first === '' || $last === '' || $email === '') {
            return ['ok' => false, 'error' => 'First name, Last name, and Email are required.'];
        }

        try {
            $this->pdo->beginTransaction();

            $su = $this->pdo->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=? WHERE user_id=?");
            $su->execute([$first, $last, $email, $phone ?: null, $userId]);

            // Passengers (ensure row exists first)
            $pid = $this->ensurePassengerForUser($userId);
            $sp = $this->pdo->prepare("UPDATE passengers SET first_name=?, last_name=?, email=?, phone=? WHERE passenger_id=?");
            $sp->execute([$first, $last, $email, $phone ?: null, $pid]);

            $this->pdo->commit();
            return ['ok' => true];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            // Likely unique email violation in users/passengers tables
            $msg = (strpos($e->getMessage(), 'email') !== false) ? 'Email is already in use.' : 'Update failed.';
            return ['ok' => false, 'error' => $msg];
        }
    }

    /** Change password for both users and passengers. */
    public function changePassword(int $userId, string $current, string $new, string $confirm): array {
        if ($new === '' || $confirm === '') return ['ok' => false, 'error' => 'New password required.'];
        if ($new !== $confirm) return ['ok' => false, 'error' => 'New passwords do not match.'];

        // Get existing hashes (some seed data may not be bcrypt; handle gracefully)
        $uh = $this->pdo->prepare("SELECT password_hash FROM users WHERE user_id=?");
        $uh->execute([$userId]);
        $userHash = $uh->fetchColumn();

        $ph = $this->pdo->prepare("SELECT password_hash FROM passengers WHERE user_id=?");
        $ph->execute([$userId]);
        $passHash = $ph->fetchColumn();

        $oldOk = false;
        $cands = array_filter([$userHash, $passHash]);
        if (!$cands) {
            // No existing hash (seed data) – allow set without verifying old
            $oldOk = true;
        } else {
            foreach ($cands as $h) {
                if (is_string($h) && strlen($h) > 0) {
                    if (str_starts_with($h, '$2y$')) { // bcrypt
                        if (password_verify($current, $h)) { $oldOk = true; break; }
                    } else {
                        if ($current === $h) { $oldOk = true; break; } // legacy plain seed
                    }
                }
            }
        }

        if (!$oldOk) return ['ok' => false, 'error' => 'Current password is incorrect.'];

        $hash = password_hash($new, PASSWORD_BCRYPT);

        try {
            $this->pdo->beginTransaction();
            $su = $this->pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=?");
            $su->execute([$hash, $userId]);

            $sp = $this->pdo->prepare("UPDATE passengers SET password_hash=? WHERE user_id=?");
            $sp->execute([$hash, $userId]);

            $this->pdo->commit();
            return ['ok' => true];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'Could not update password.'];
        }
    }

    /** Soft delete: anonymize both tables and suspend account (preferred). */
    public function softDelete(int $userId): bool {
        $anon = "deleted+{$userId}@" . date('YmdHis') . ".invalid";
        try {
            $this->pdo->beginTransaction();

            $sp = $this->pdo->prepare(
                "UPDATE passengers 
                   SET first_name='Deleted', last_name='User', email=?, phone=NULL, password_hash=NULL
                 WHERE user_id=?"
            );
            $sp->execute([$anon, $userId]);

            $su = $this->pdo->prepare(
                "UPDATE users 
                   SET full_name='Deleted User', email=?, phone=NULL, password_hash=NULL, status='Suspended'
                 WHERE user_id=?"
            );
            $su->execute([$anon, $userId]);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /** Hard delete user row (FK will set passengers.user_id = NULL) after anonymizing passenger. */
    public function hardDelete(int $userId): bool {
        $anon = "deleted+{$userId}@" . date('YmdHis') . ".invalid";
        try {
            $this->pdo->beginTransaction();

            $sp = $this->pdo->prepare(
                "UPDATE passengers 
                   SET first_name='Deleted', last_name='User', email=?, phone=NULL, password_hash=NULL
                 WHERE user_id=?"
            );
            $sp->execute([$anon, $userId]);

            $du = $this->pdo->prepare("DELETE FROM users WHERE user_id=?");
            $du->execute([$userId]);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
