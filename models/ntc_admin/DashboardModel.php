<?php
namespace App\models\ntc_admin;

use PDO;

class DashboardModel extends BaseModel
{
    private function scalarInt(string $sql, array $params = []): int
    {
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            return (int) ($st->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function latestStatusCount(string $day, string $status): int
    {
        return $this->scalarInt(
            "SELECT COUNT(*) c
             FROM (
               SELECT t.bus_reg_no,
                      t.operational_status,
                      ROW_NUMBER() OVER (PARTITION BY t.bus_reg_no ORDER BY t.snapshot_at DESC) AS rn
               FROM tracking_monitoring t
               WHERE DATE(t.snapshot_at) = :day
             ) x
             WHERE x.rn = 1 AND x.operational_status = :status",
            [
                ':day' => $day,
                ':status' => $status,
            ]
        );
    }

    private function complaintsCount(string $day, bool $feedbackOnly = false): int
    {
        if ($feedbackOnly) {
            return $this->scalarInt(
                "SELECT COUNT(*) c
                 FROM complaints
                 WHERE DATE(created_at) = :day
                   AND LOWER(COALESCE(category,'')) = 'feedback'",
                [':day' => $day]
            );
        }

        return $this->scalarInt(
            "SELECT COUNT(*) c
             FROM complaints
             WHERE DATE(created_at) = :day
               AND LOWER(COALESCE(category,'')) <> 'feedback'",
            [':day' => $day]
        );
    }

    public function stats(): array
    {
        $p = (int) $this->pdo->query("SELECT COUNT(*) c FROM private_buses")->fetch()['c'];
        $s = (int) $this->pdo->query("SELECT COUNT(*) c FROM sltb_buses")->fetch()['c'];
        $owners = (int) $this->pdo->query("SELECT COUNT(*) c FROM private_bus_owners")->fetch()['c'];
        $depots = (int) $this->pdo->query("SELECT COUNT(*) c FROM sltb_depots")->fetch()['c'];
        $routes = (int) $this->pdo->query("SELECT COUNT(*) c FROM routes WHERE is_active=1")->fetch()['c'];

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $complaints = $this->complaintsCount($today, false);
        $complaintsYesterday = $this->complaintsCount($yesterday, false);
        $feedbackToday = $this->complaintsCount($today, true);

        $delayed = $this->latestStatusCount($today, 'Delayed');
        $delayedYesterday = $this->latestStatusCount($yesterday, 'Delayed');

        $broken = $this->latestStatusCount($today, 'Breakdown');
        $brokenYesterday = $this->latestStatusCount($yesterday, 'Breakdown');

        $avgRating = (new AnalyticsModel())->avgRating([]);

        return compact(
            'p',
            's',
            'owners',
            'depots',
            'routes',
            'complaints',
            'complaintsYesterday',
            'feedbackToday',
            'delayed',
            'delayedYesterday',
            'broken',
            'brokenYesterday',
            'avgRating'
        );
    }
    public function routes(): array
    {
        return $this->pdo->query("SELECT route_id, route_no FROM routes ORDER BY route_no+0, route_no")->fetchAll();
    }
}
?>