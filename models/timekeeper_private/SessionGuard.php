<?php
namespace App\models\timekeeper_private;

class SessionGuard {
    public function me(): array { return $_SESSION['user'] ?? []; }

    public function requirePrivateTimekeeper(): void {
        $u = $this->me();
        if (!($u && ($u['role'] ?? '') === 'PrivateTimekeeper')) {
            header('Location: /login?denied=1'); exit;
        }
    }
}
