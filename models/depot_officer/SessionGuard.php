<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class SessionGuard extends BaseModel
{
    public function me(): array { return $_SESSION['user'] ?? []; }

    public function requireDepotOfficer(): void {
        $u = $this->me();
        if (!($u && ($u['role'] ?? '') === 'DepotOfficer')) {
            header('Location: /login?denied=1'); exit;
        }
    }

    public function myDepotId(array $u): int {
        if (!empty($u['sltb_depot_id'])) return (int)$u['sltb_depot_id'];
        $st = $this->pdo->prepare("SELECT sltb_depot_id FROM users WHERE user_id=?");
        $st->execute([(int)($u['user_id'] ?? 0)]);
        return (int)($st->fetchColumn() ?: 0);
    }
}
?>
