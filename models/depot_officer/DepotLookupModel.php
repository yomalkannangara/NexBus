<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class DepotLookupModel extends BaseModel
{
    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }
    
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
        // FIXED: users table has first_name + last_name, not full_name
        $st = $this->pdo->prepare("SELECT user_id, 
                                          CONCAT(first_name, ' ', COALESCE(last_name, '')) AS full_name,
                                          role, email, phone
                                   FROM users
                                   WHERE sltb_depot_id=? AND role IN ('DepotManager','DepotOfficer','SLTBTimekeeper')
                                   ORDER BY role, first_name");
        $st->execute([$depotId]);
        return $st->fetchAll();
    }

    /**
     * Drivers and conductors for a depot (from users table when present).
     * Some deployments may store drivers/conductors in dedicated tables; this
     * method prefers user accounts with role 'Driver' or 'Conductor'.
     */
    public function depotDriversAndConductors(int $depotId): array {
                // Combine user accounts with dedicated SLTB driver/conductor tables.
                $sql = "SELECT CONCAT('user:', user_id) AS attendance_key, user_id AS id, 
                                             CONCAT(first_name, ' ', COALESCE(last_name, '')) AS full_name,
                                             role AS type
                                    FROM users
                                 WHERE sltb_depot_id=? AND role IN ('Driver','Conductor')
                                UNION ALL
                                SELECT CONCAT('driver:', sltb_driver_id) AS attendance_key, sltb_driver_id AS id, full_name, 'driver' AS type
                                    FROM sltb_drivers
                                 WHERE sltb_depot_id=? AND status='Active'
                                UNION ALL
                                SELECT CONCAT('conductor:', sltb_conductor_id) AS attendance_key, sltb_conductor_id AS id, full_name, 'conductor' AS type
                                    FROM sltb_conductors
                                 WHERE sltb_depot_id=? AND status='Active'
                                ORDER BY type, full_name";
                $st = $this->pdo->prepare($sql);
                $st->execute([$depotId, $depotId, $depotId]);
                return $st->fetchAll();
    }

    public function routes(): array {
        $rows = $this->pdo->query("SELECT route_id, route_no, stops_json
                                  FROM routes
                                  WHERE is_active=1
                                  ORDER BY route_no+0, route_no")->fetchAll();
        
        foreach ($rows as &$r) {
            $r['name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
        }
        
        return $rows;
    }
}