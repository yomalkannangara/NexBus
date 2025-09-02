<?php
namespace App\models\ntc_admin;

use PDO;

class ProfileModel {
    private PDO $db;

    public function __construct(PDO $pdo = null) {
        $this->db = $pdo ?: $GLOBALS['db'];
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    }

    /* ---------- getters ---------- */

    /** Return user from SESSION (source of truth for UI). */
    public function sessionUser(): ?array {
        return $_SESSION['user'] ?? null;
    }

    /** Fresh copy from DB (optional re-hydrate). */
    public function findById(int $userId): ?array {
        $sql = "SELECT user_id AS id, full_name AS name, email, phone, role, status
                FROM users WHERE user_id = ? LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute([$userId]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** UI theme pref (stored only in session per your requirement). */
    public function theme(): string {
        return $_SESSION['prefs']['theme'] ?? 'light';
    }

    /* ---------- commands (mutations) ---------- */

    /** Update basic profile fields in DB and sync SESSION. */
    public function updateProfile(array $post): bool {
        $me = $this->sessionUser();
        if (!$me) return false;

        $name  = trim($post['full_name'] ?? ($me['name'] ?? ''));
        $email = trim($post['email'] ?? ($me['email'] ?? ''));
        $phone = trim($post['phone'] ?? ($me['phone'] ?? ''));

        // (Optional) validations here (email format, unique email, length caps)
        $sql = "UPDATE users SET full_name=:name, email=:email, phone=:phone WHERE user_id=:id LIMIT 1";
        $st  = $this->db->prepare($sql);
        $ok  = $st->execute([':name'=>$name, ':email'=>$email, ':phone'=>$phone, ':id'=>(int)$me['id']]);

        if ($ok) {
            // sync session (you said: “get it all from the session”)
            $_SESSION['user']['name']  = $name;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['phone'] = $phone;
        }
        return $ok;
    }

    /** Change password (verify current -> hash new). */
    public function changePassword(array $post): bool {
        $me = $this->sessionUser();
        if (!$me) return false;

        $cur = (string)($post['current_password'] ?? '');
        $new = (string)($post['new_password'] ?? '');
        $rep = (string)($post['confirm_password'] ?? '');

        if ($new !== $rep || strlen($new) < 8) return false;

        $st = $this->db->prepare("SELECT password_hash FROM users WHERE user_id=? LIMIT 1");
        $st->execute([(int)$me['id']]);
        $hash = $st->fetchColumn();

        if (!$hash || !password_verify($cur, $hash)) return false;

        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $up = $this->db->prepare("UPDATE users SET password_hash=? WHERE user_id=? LIMIT 1");
        return $up->execute([$newHash, (int)$me['id']]);
    }

    /** Save dark/light preference to SESSION only (no DB). */
    public function savePrefs(array $post): void {
        $mode = (isset($post['theme']) && $post['theme'] === 'dark') ? 'dark' : 'light';
        $_SESSION['prefs']['theme'] = $mode;
    }
}
