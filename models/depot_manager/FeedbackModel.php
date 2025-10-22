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

class FeedbackModel extends BaseModel
{
    public function cards(): array
    {
        $totalMonth = $this->countSafe("SELECT COUNT(*) c FROM complaints WHERE YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())");
        $open       = $this->countSafe("SELECT COUNT(*) c FROM complaints WHERE status IN ('Open','In Progress')");
        $resolved   = $this->countSafe("SELECT COUNT(*) c FROM complaints WHERE status='Resolved' AND YEAR(updated_at)=YEAR(CURDATE()) AND MONTH(updated_at)=MONTH(CURDATE())");
        $avgRating  = $this->avgSafe("SELECT AVG(rating) a FROM complaints WHERE rating IS NOT NULL AND rating>0");

        // Dummy fallback when there is no data
        if ($totalMonth === 0 && $open === 0 && $resolved === 0 && (float)$avgRating === 0.0) {
            return [
                ['value' => '27',  'label' => 'Total This Month',   'trendText' => 'vs. last month', 'trend' => '+8.0%', 'trendClass' => 'green', 'icon' => 'message'],
                ['value' => '6',   'label' => 'Open Complaints',    'trendText' => 'open now',       'trend' => '',      'trendClass' => 'red',   'icon' => 'message-circle'],
                ['value' => '19',  'label' => 'Resolved This Month','trendText' => 'resolution rate','trend' => '',      'trendClass' => 'green', 'icon' => 'message-circle'],
                ['value' => '4.2', 'label' => 'Average Rating',     'trendText' => 'past 30 days',   'trend' => '+0.2',  'trendClass' => 'green', 'icon' => 'star'],
            ];
        }

        return [
            ['value' => (string)$totalMonth, 'label' => 'Total This Month',  'trendText' => '', 'trend' => '', 'trendClass' => 'green', 'icon' => 'message'],
            ['value' => (string)$open,       'label' => 'Open Complaints',   'trendText' => '', 'trend' => '', 'trendClass' => 'red',   'icon' => 'message-circle'],
            ['value' => (string)$resolved,   'label' => 'Resolved This Month','trendText' => '', 'trend' => '', 'trendClass' => 'green', 'icon' => 'message-circle'],
            ['value' => number_format((float)$avgRating, 1), 'label' => 'Average Rating', 'trendText' => '', 'trend' => '', 'trendClass' => 'green', 'icon' => 'star'],
        ];
    }

    public function list(): array
    {
        try {
            $sql = "SELECT c.id,
                           DATE(c.created_at) AS date,
                           b.reg_no AS busNumber,
                           CONCAT(r.route_no, ' - ', r.name) AS route,
                           c.passenger_name AS passengerName,
                           CASE WHEN c.type IS NULL OR c.type='' THEN 'Complaint' ELSE c.type END AS type,
                           c.category,
                           c.description,
                           c.status,
                           IFNULL(c.rating, 0) AS rating
                    FROM complaints c
                    LEFT JOIN buses b  ON b.id = c.bus_id
                    LEFT JOIN routes r ON r.route_id = c.route_id
                    ORDER BY c.created_at DESC
                    LIMIT 200";
            $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Dummy fallback when no rows
            if (!$rows) {
                $rows = $this->demoComplaints();
            }
            return $rows;
        } catch (PDOException $e) {
            // Dummy fallback on error
            return $this->demoComplaints();
        }
    }

    public function assign(array $d): bool
    {
        try {
            $sql = "UPDATE complaints SET assigned_to=:uid, status='In Progress', updated_at=NOW() WHERE id=:id";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([
                ':uid' => (int)($d['user_id'] ?? 0),
                ':id'  => (int)($d['complaint_id'] ?? 0),
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function resolve(array $d): bool
    {
        try {
            $sql = "UPDATE complaints SET status='Resolved', resolution_note=:note, updated_at=NOW(), resolved_at=NOW() WHERE id=:id";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([
                ':note' => $d['note'] ?? null,
                ':id'   => (int)($d['complaint_id'] ?? 0),
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function close(array $d): bool
    {
        try {
            $sql = "UPDATE complaints SET status='Closed', updated_at=NOW() WHERE id=:id";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([':id' => (int)($d['complaint_id'] ?? 0)]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function reply(array $d): bool
    {
        try {
            $sql = "INSERT INTO complaint_replies (complaint_id, user_id, message, created_at)
                    VALUES (:cid, :uid, :msg, NOW())";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([
                ':cid' => (int)($d['complaint_id'] ?? 0),
                ':uid' => (int)($_SESSION['user']['id'] ?? 0),
                ':msg' => $d['message'] ?? '',
            ]);
        } catch (PDOException $e) {
            return false;
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

    // Dummy data helper
    private function demoComplaints(): array
    {
        return [
            [
                'id' => 1001,
                'date' => date('Y-m-d', strtotime('-1 day')),
                'busNumber' => 'NB-1234',
                'route' => '138 - Colombo - Kandy',
                'passengerName' => 'R. Perera',
                'type' => 'Complaint',
                'category' => 'Delay',
                'description' => 'Bus arrived 15 minutes late.',
                'status' => 'Open',
                'rating' => 0,
            ],
            [
                'id' => 1002,
                'date' => date('Y-m-d', strtotime('-2 days')),
                'busNumber' => 'NB-7788',
                'route' => '101 - Pettah - Kadawatha',
                'passengerName' => 'S. Silva',
                'type' => 'Feedback',
                'category' => 'Cleanliness',
                'description' => 'Bus was clean and comfortable.',
                'status' => 'Resolved',
                'rating' => 5,
            ],
        ];
    }
}
