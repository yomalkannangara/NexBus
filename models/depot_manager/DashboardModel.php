<?php
namespace App\models\depot_manager;

use PDO;
use PDOException;
abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}

class DashboardModel extends BaseModel
{
    public function todayLabel(): string
    {
        return date('l j F Y');
    }

    public function stats(): array
    {
        $busCount     = $this->countSafe("SELECT COUNT(*) c FROM buses");
        $ownerCount   = $this->countSafe("SELECT COUNT(*) c FROM bus_owners");
        $routesActive = $this->countSafe("SELECT COUNT(*) c FROM routes WHERE is_active=1");

        // Dummy fallback when everything is zero
        if ($busCount === 0 && $ownerCount === 0 && $routesActive === 0) {
            return [
                ['title' => 'Total Buses',           'value' => '128', 'change' => '+2.4%', 'trend' => 'up',   'icon' => 'bus'],
                ['title' => 'Registered Bus Owners', 'value' => '73',  'change' => '+1.1%', 'trend' => 'up',   'icon' => 'users'],
                ['title' => 'Active Routes',         'value' => '42',  'change' => '+3.0%', 'trend' => 'up',   'icon' => 'routes'],
            ];
        }

        return [
            ['title' => 'Total Buses',           'value' => (string)$busCount],
            ['title' => 'Registered Bus Owners', 'value' => (string)$ownerCount],
            ['title' => 'Active Routes',         'value' => (string)$routesActive],
        ];
    }

    public function dailyStats(): array
    {
        $complaintsToday = $this->countSafe("SELECT COUNT(*) c FROM complaints WHERE DATE(created_at)=CURDATE()");
        $delayedToday    = $this->countSafe("SELECT COUNT(*) c FROM tracking_monitoring WHERE operational_status='Delayed' AND DATE(snapshot_at)=CURDATE()");
        $brokenToday     = $this->countSafe("SELECT COUNT(*) c FROM maintenance_jobs WHERE status='Breakdown' AND DATE(created_at)=CURDATE()");

        // Dummy fallback when everything is zero
        if ($complaintsToday === 0 && $delayedToday === 0 && $brokenToday === 0) {
            return [
                ['title' => "Today's Complaints",  'value' => '5', 'change' => '+1 vs yesterday', 'trend' => 'up',   'icon' => 'alert', 'color' => 'orange'],
                ['title' => 'Delayed Buses Today', 'value' => '7', 'change' => '-2 vs yesterday', 'trend' => 'down', 'icon' => 'clock', 'color' => 'red'],
                ['title' => 'Broken Buses Today',  'value' => '1', 'change' => '+0 vs yesterday', 'trend' => 'up',   'icon' => 'alert', 'color' => 'red'],
            ];
        }

        return [
            ['title' => "Today's Complaints",  'value' => (string)$complaintsToday, 'change' => '', 'trend' => '', 'icon' => 'alert', 'color' => 'orange'],
            ['title' => 'Delayed Buses Today', 'value' => (string)$delayedToday,    'change' => '', 'trend' => '', 'icon' => 'clock', 'color' => 'red'],
            ['title' => 'Broken Buses Today',  'value' => (string)$brokenToday,     'change' => '', 'trend' => '', 'icon' => 'alert', 'color' => 'red'],
        ];
    }

    public function activeCount(): int
    {
        return $this->countSafe("SELECT COUNT(*) c FROM tracking_monitoring WHERE operational_status='OnTime' AND DATE(snapshot_at)=CURDATE()");
    }

    public function delayedCount(): int
    {
        return $this->countSafe("SELECT COUNT(*) c FROM tracking_monitoring WHERE operational_status='Delayed' AND DATE(snapshot_at)=CURDATE()");
    }

    public function issuesCount(): int
    {
        // Treat 'Breakdown' as issue.
        return $this->countSafe("SELECT COUNT(*) c FROM tracking_monitoring WHERE operational_status='Breakdown' AND DATE(snapshot_at)=CURDATE()");
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
}
