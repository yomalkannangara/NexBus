<?php
namespace App\models\depot_manager;

use PDO;
use PDOException;
use App\models\common\BaseModel;

class DashboardModel extends BaseModel
{
    private function depotId(): ?int {
        $u = $_SESSION['user'] ?? [];
        $id = $u['sltb_depot_id'] ?? $u['depot_id'] ?? null;
        return $id ? (int)$id : null;
    }

    public function todayLabel(): string
    {
        return date('l j F Y');
    }

    public function stats(): array
    {
        $depotId = $this->depotId();
        
        // Get bus count for this depot
        $busCount = $depotId ? $this->countSafe("SELECT COUNT(*) c FROM sltb_buses WHERE sltb_depot_id=:d", [':d' => $depotId]) 
                             : $this->countSafe("SELECT COUNT(*) c FROM sltb_buses");
        
        // Total staff: drivers + conductors for this depot
        $driversCount = $depotId ? $this->countSafe("SELECT COUNT(*) c FROM sltb_drivers WHERE sltb_depot_id=:d", [':d' => $depotId])
                                 : $this->countSafe("SELECT COUNT(*) c FROM sltb_drivers");
        $conductorsCount = $depotId ? $this->countSafe("SELECT COUNT(*) c FROM sltb_conductors WHERE sltb_depot_id=:d", [':d' => $depotId])
                                    : $this->countSafe("SELECT COUNT(*) c FROM sltb_conductors");
        $staffCount = $driversCount + $conductorsCount;
        
        // Get active routes
        $routesActive = $this->countSafe("SELECT COUNT(*) c FROM routes WHERE is_active=1");

        return [
            ['title' => 'Total Buses',           'value' => (string)$busCount],
            ['title' => 'Total Staff',           'value' => (string)$staffCount],
            ['title' => 'Active Routes',         'value' => (string)$routesActive],
        ];
    }

    public function dailyStats(): array
    {
        $depotId = $this->depotId();
        
        // Complaints today for this depot (via bus route)
        $complaintsToday = $depotId 
            ? $this->countSafe("SELECT COUNT(DISTINCT c.complaint_id) c FROM complaints c 
                               JOIN sltb_buses b ON c.bus_reg_no = b.reg_no 
                               WHERE b.sltb_depot_id=:d AND DATE(c.created_at)=CURDATE()", [':d' => $depotId])
            : $this->countSafe("SELECT COUNT(*) c FROM complaints WHERE DATE(created_at)=CURDATE()");
        
        // Delayed buses today for this depot
        $delayedToday = $depotId
            ? $this->countSafe("SELECT COUNT(DISTINCT tm.bus_reg_no) c FROM tracking_monitoring tm 
                               JOIN sltb_buses b ON tm.bus_reg_no = b.reg_no 
                               WHERE b.sltb_depot_id=:d AND tm.operational_status='Delayed' AND DATE(tm.snapshot_at)=CURDATE()", [':d' => $depotId])
            : $this->countSafe("SELECT COUNT(*) c FROM tracking_monitoring WHERE operational_status='Delayed' AND DATE(snapshot_at)=CURDATE()");
        
        // Broken buses: align with Fleet page "In Maintenance" count
        $brokenBuses = $depotId
            ? $this->countSafe("SELECT COUNT(*) c FROM sltb_buses WHERE sltb_depot_id=:d AND status='Maintenance'", [':d' => $depotId])
            : $this->countSafe("SELECT COUNT(*) c FROM sltb_buses WHERE status='Maintenance'");

        return [
            ['title' => "Today's Complaints",  'value' => (string)$complaintsToday, 'change' => '', 'trend' => '', 'icon' => 'alert', 'color' => 'orange'],
            ['title' => 'Delayed Buses Today', 'value' => (string)$delayedToday,    'change' => '', 'trend' => '', 'icon' => 'clock', 'color' => 'red'],
            ['title' => 'Broken Busses',       'value' => (string)$brokenBuses,     'change' => '', 'trend' => '', 'icon' => 'alert', 'color' => 'red'],
        ];
    }

    public function activeCount(): int
    {
        $depotId = $this->depotId();
        return $depotId
            ? $this->countSafe("SELECT COUNT(DISTINCT tm.bus_reg_no) c FROM tracking_monitoring tm 
                               JOIN sltb_buses b ON tm.bus_reg_no = b.reg_no 
                               WHERE b.sltb_depot_id=:d AND tm.operational_status='OnTime' AND DATE(tm.snapshot_at)=CURDATE()", [':d' => $depotId])
            : $this->countSafe("SELECT COUNT(*) c FROM tracking_monitoring WHERE operational_status='OnTime' AND DATE(snapshot_at)=CURDATE()");
    }

    public function delayedCount(): int
    {
        $depotId = $this->depotId();
        return $depotId
            ? $this->countSafe("SELECT COUNT(DISTINCT tm.bus_reg_no) c FROM tracking_monitoring tm 
                               JOIN sltb_buses b ON tm.bus_reg_no = b.reg_no 
                               WHERE b.sltb_depot_id=:d AND tm.operational_status='Delayed' AND DATE(tm.snapshot_at)=CURDATE()", [':d' => $depotId])
            : $this->countSafe("SELECT COUNT(*) c FROM tracking_monitoring WHERE operational_status='Delayed' AND DATE(snapshot_at)=CURDATE()");
    }

    public function issuesCount(): int
    {
        // Treat 'Breakdown' as issue.
        $depotId = $this->depotId();
        return $depotId
            ? $this->countSafe("SELECT COUNT(DISTINCT tm.bus_reg_no) c FROM tracking_monitoring tm 
                               JOIN sltb_buses b ON tm.bus_reg_no = b.reg_no 
                               WHERE b.sltb_depot_id=:d AND tm.operational_status='Breakdown' AND DATE(tm.snapshot_at)=CURDATE()", [':d' => $depotId])
            : $this->countSafe("SELECT COUNT(*) c FROM tracking_monitoring WHERE operational_status='Breakdown' AND DATE(snapshot_at)=CURDATE()");
    }

    private function countSafe(string $sql, array $params = []): int
    {
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            return (int)($st->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    /** Human-readable name of this manager's depot. */
    public function depotName(): string
    {
        $depotId = $this->depotId();
        if (!$depotId) return 'All Depots';
        try {
            $st = $this->pdo->prepare("SELECT name FROM sltb_depots WHERE sltb_depot_id = ?");
            $st->execute([$depotId]);
            return (string)($st->fetchColumn() ?: ('Depot #' . $depotId));
        } catch (PDOException $e) {
            return 'Depot #' . $depotId;
        }
    }

    /** Routes served by this depot's buses (for the map filter dropdown). */
    public function routes(): array
    {
        $depotId = $this->depotId();
        try {
            if ($depotId) {
                $st = $this->pdo->prepare(
                    "SELECT DISTINCT r.route_no
                       FROM routes r
                       JOIN timetables t ON t.route_id = r.route_id
                       JOIN sltb_buses sb ON sb.reg_no = t.bus_reg_no
                      WHERE sb.sltb_depot_id = ?
                        AND r.is_active = 1
                      ORDER BY r.route_no+0, r.route_no"
                );
                $st->execute([$depotId]);
            } else {
                $st = $this->pdo->query(
                    "SELECT DISTINCT route_no FROM routes WHERE is_active=1 ORDER BY route_no+0, route_no"
                );
            }
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
}
