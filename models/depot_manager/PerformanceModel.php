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

class PerformanceModel extends BaseModel
{
    public function cards(): array
    {
        $avgPunctual  = $this->avgSafe("SELECT AVG(punctuality_score) a FROM trip_performance WHERE DATE(trip_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $avgSafeDrive = $this->avgSafe("SELECT AVG(safe_driving_score) a FROM trip_performance WHERE DATE(trip_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $trips        = $this->countSafe("SELECT COUNT(*) c FROM trip_performance WHERE DATE(trip_date)=CURDATE()");
        $topAlerts    = $this->countSafe("SELECT COUNT(*) c FROM trip_performance WHERE DATE(trip_date)=CURDATE() AND violations>0");

        return [
            ['label' => 'Avg Punctuality (7d)', 'value' => number_format((float)$avgPunctual, 1)],
            ['label' => 'Avg Safe Driving (7d)', 'value' => number_format((float)$avgSafeDrive, 1)],
            ['label' => 'Trips Today', 'value' => (string)$trips],
            ['label' => 'Alerts Today', 'value' => (string)$topAlerts],
        ];
    }

    public function topDrivers(): array
    {
        try {
            $sql = "SELECT d.id, d.name, AVG(p.overall_score) AS score, COUNT(*) trips
                      FROM trip_performance p
                      JOIN drivers d ON d.id=p.driver_id
                  GROUP BY d.id, d.name
                  HAVING trips >= 5
                  ORDER BY score DESC
                  LIMIT 20";
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
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

    private function avgSafe(string $sql, array $params = []): float
    {
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            return (float)($st->fetch(PDO::FETCH_ASSOC)['a'] ?? 0.0);
        } catch (PDOException $e) {
            return 0.0;
        }
    }
}
