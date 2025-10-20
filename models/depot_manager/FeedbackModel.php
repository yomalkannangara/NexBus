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
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
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
}
