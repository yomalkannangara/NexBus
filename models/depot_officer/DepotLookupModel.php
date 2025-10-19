<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class DepotLookupModel extends BaseModel
{
    public function depot(int $depotId): ?array {
        $st = $this->pdo->prepare("SELECT * FROM sltb_depots WHERE sltb_depot_id=?");
        $st->execute([$depotId]);
        return $st->fetch() ?: null;
    }

    public function depotBuses(int $depotId): array {
        $st = $this->pdo->prepare("SELECT * FROM sltb_buses WHERE sltb_depot_id=? ORDER BY reg_no");
        $st->execute([$depotId]);
        return $st->fetchAll();
    }

    public function depotDrivers(int $depotId): array {
        $st = $this->pdo->prepare("SELECT * FROM sltb_drivers WHERE sltb_depot_id=? AND status='Active' ORDER BY full_name");
        $st->execute([$depotId]);
        return $st->fetchAll();
    }

    public function depotStaff(int $depotId): array {
        $st = $this->pdo->prepare("SELECT user_id, full_name, role, email, phone
                                   FROM users
                                   WHERE sltb_depot_id=? AND role IN ('DepotManager','DepotOfficer','SLTBTimekeeper')
                                   ORDER BY role, full_name");
        $st->execute([$depotId]);
        return $st->fetchAll();
    }

    public function routes(): array {
        return $this->pdo->query("SELECT route_id, route_no, name
                                  FROM routes
                                  WHERE is_active=1
                                  ORDER BY route_no+0, route_no")->fetchAll();
    }
}
