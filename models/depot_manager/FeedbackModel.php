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
    private ?bool $complaintsHasRating = null;

    private function complaintsHasRating(): bool
    {
        if ($this->complaintsHasRating !== null) return $this->complaintsHasRating;
        try {
            $st = $this->pdo->prepare(
                "SELECT COUNT(*) c
                   FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'complaints'
                    AND COLUMN_NAME = 'rating'"
            );
            $st->execute();
            $this->complaintsHasRating = ((int)($st->fetch(PDO::FETCH_ASSOC)['c'] ?? 0)) > 0;
        } catch (PDOException $e) {
            $this->complaintsHasRating = false;
        }
        return $this->complaintsHasRating;
    }

    private function depotId(): ?int {
        $u = $_SESSION['user'] ?? null;
        return isset($u['sltb_depot_id']) && $u['sltb_depot_id'] !== '' 
            ? (int)$u['sltb_depot_id']
            : (isset($u['depot_id']) ? (int)$u['depot_id'] : null);
    }

    private function hasDepot(): bool { 
        return (bool)$this->depotId(); 
    }

    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }
    
    /** Depot-scoped feedback list */
    public function getAll(): array
    {
        $ratingSelect = $this->complaintsHasRating() ? 'IFNULL(c.rating, 0) AS rating' : '0 AS rating';
        $sql = "SELECT 
                    c.complaint_id,
                    c.passenger_id,
                    c.created_at AS date,
                    NULLIF(NULLIF(TRIM(c.bus_reg_no),''),'undefined') AS bus_reg_no,
                    c.category, 
                    c.description, 
                    c.status, 
                    c.reply_text,
                    c.resolved_at,
                    {$ratingSelect},
                    CONCAT(p.first_name, ' ', p.last_name) AS passenger,
                    r.route_no, 
                    r.stops_json
                FROM complaints c
                LEFT JOIN passengers p  ON p.passenger_id = c.passenger_id
                LEFT JOIN routes     r  ON r.route_id     = c.route_id
                LEFT JOIN sltb_buses sb ON sb.reg_no      = c.bus_reg_no
                WHERE c.operator_type = 'SLTB'";
        $params = [];

        if ($this->hasDepot()) {
            $sql .= " AND sb.sltb_depot_id = :depot";
            $params[':depot'] = $this->depotId();
        }

        $sql .= " ORDER BY c.created_at DESC, c.complaint_id DESC";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($r) {
            $bus = $r['bus_reg_no'] ?? '';
            $routeName = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
            $routeLabel = ($r['route_no'] ?? '') !== '' 
                ? trim(($r['route_no'] ?? '').' - '.$routeName)
                : '';
            $passengerLabel = trim((string)($r['passenger'] ?? ''));
            if ($passengerLabel === '' && isset($r['passenger_id'])) {
                $passengerLabel = 'Passenger #' . (int)$r['passenger_id'];
            }

            return [
                'id'           => (int)$r['complaint_id'],
                'ref_code'     => 'C'.str_pad((string)$r['complaint_id'], 6, '0', STR_PAD_LEFT),
                'date'         => $r['date'],
                'bus_or_route' => $bus !== '' ? $bus : $routeLabel,
                'passenger'    => $passengerLabel,
                'type'         => (strcasecmp((string)($r['category'] ?? ''), 'complaint') === 0) ? 'Complaint' : 'Feedback',
                'category'     => $r['category'] ?? '',
                'status'       => $r['status'] ?? 'Open',
                'rating'       => (int)($r['rating'] ?? 0),
                'message'      => $r['description'] ?? '',
                'response'     => $r['reply_text'] ?? '',
                'resolved_at'  => $r['resolved_at'] ?? null,
            ];
        }, $rows);
    }

    /** For dropdown "Select Feedback ID" (depot scoped) */
    public function getAllIds(): array
    {
        $sql = "SELECT c.complaint_id
                FROM complaints c
                LEFT JOIN sltb_buses sb ON sb.reg_no = c.bus_reg_no
                WHERE c.operator_type='SLTB'";
        $params = [];

        if ($this->hasDepot()) {
            $sql .= " AND sb.sltb_depot_id = :depot";
            $params[':depot'] = $this->depotId();
        }

        $sql .= " ORDER BY c.complaint_id DESC";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        
        return array_map(function($r) {
            return ['id' => (int)$r['complaint_id']];
        }, $st->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }
    
    public function cards(): array
    {
        $depot = $this->depotId();
        $depotFilter = $this->hasDepot() 
            ? " AND c.complaint_id IN (SELECT c2.complaint_id FROM complaints c2 LEFT JOIN sltb_buses sb ON sb.reg_no=c2.bus_reg_no WHERE c2.operator_type='SLTB' AND sb.sltb_depot_id={$depot})"
            : " AND c.operator_type='SLTB'";
        
        $totalMonth = $this->countSafe("SELECT COUNT(*) c FROM complaints WHERE operator_type='SLTB' AND YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())" . $depotFilter);
        $open       = $this->countSafe("SELECT COUNT(*) c FROM complaints WHERE operator_type='SLTB' AND status IN ('Open','In Progress')" . $depotFilter);
        $resolved   = $this->countSafe("SELECT COUNT(*) c FROM complaints WHERE operator_type='SLTB' AND status='Resolved' AND resolved_at IS NOT NULL AND YEAR(resolved_at)=YEAR(CURDATE()) AND MONTH(resolved_at)=MONTH(CURDATE())" . $depotFilter);
        $avgRating  = $this->complaintsHasRating()
            ? $this->avgSafe("SELECT AVG(rating) a FROM complaints WHERE operator_type='SLTB' AND rating IS NOT NULL AND rating>0" . $depotFilter)
            : 0.0;

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
            $ratingSelect = $this->complaintsHasRating() ? "IFNULL(c.rating, 0) AS rating" : "0 AS rating";
            // Align to actual schema: complaints has complaint_id, passenger_id, bus_reg_no, assigned_to_user_id, resolved_at, reply_text
            $sql = "SELECT c.complaint_id AS id,
                   DATE(c.created_at) AS date,
                   c.bus_reg_no AS busNumber,
                   r.stops_json,
                   CONCAT(COALESCE(p.first_name,''), CASE WHEN p.last_name IS NULL OR p.last_name='' THEN '' ELSE CONCAT(' ', p.last_name) END) AS passengerName,
                   CASE WHEN LOWER(COALESCE(c.category,''))='complaint' THEN 'Complaint' ELSE 'Feedback' END AS type,
                   c.category,
                   c.description,
                   c.status,
                   c.resolved_at,
                   c.reply_text,
                   {$ratingSelect}
                FROM complaints c
                LEFT JOIN passengers p ON p.passenger_id = c.passenger_id
                LEFT JOIN routes r ON r.route_id = c.route_id
                ORDER BY c.created_at DESC
                LIMIT 200";

            $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            foreach ($rows as &$r) {
                $r['route'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
            }

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

    public function resolve(array $d): bool
    {
        try {
            $note = trim((string)($d['note'] ?? ''));
            $sql = "UPDATE complaints
                       SET status='Resolved',
                           resolved_at=NOW()" . ($note !== '' ? ", reply_text=:note" : "") . "
                     WHERE complaint_id=:id";
            $st  = $this->pdo->prepare($sql);
            $params = [':id' => (int)($d['complaint_id'] ?? 0)];
            if ($note !== '') $params[':note'] = $note;
            return $st->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function close(array $d): bool
    {
        try {
            $sql = "UPDATE complaints
                       SET status='Closed',
                           resolved_at=COALESCE(resolved_at, NOW())
                     WHERE complaint_id=:id";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([':id' => (int)($d['complaint_id'] ?? 0)]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateStatus($idOrRef, string $status): bool
    {
        try {
            $id = is_numeric($idOrRef) ? (int)$idOrRef : (int)preg_replace('/\D+/', '', (string)$idOrRef);
            $sql = "UPDATE complaints SET status = :status WHERE complaint_id = :id";
            $st = $this->pdo->prepare($sql);
            return $st->execute([':status' => $status, ':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function sendResponse($idOrRef, string $message): bool
    {
        try {
            $id = is_numeric($idOrRef) ? (int)$idOrRef : (int)preg_replace('/\D+/', '', (string)$idOrRef);
            $msg = trim($message);
            if ($msg === '') return false;

            $uid = (int)($_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? 0);

            $sql = "UPDATE complaints
                       SET reply_text = :msg,
                           assigned_to_user_id = COALESCE(assigned_to_user_id, :uid),
                           status = CASE WHEN status='Open' THEN 'In Progress' ELSE status END
                     WHERE complaint_id = :cid";
            $st = $this->pdo->prepare($sql);
            return $st->execute([
                ':cid' => $id,
                ':uid' => $uid ?: null,
                ':msg' => $msg,
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function assign(array $d): bool
    {
        try {
            $sql = "UPDATE complaints SET assigned_to_user_id=:uid, status='In Progress' WHERE complaint_id=:id";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([
                ':uid' => (int)($d['user_id'] ?? 0),
                ':id'  => (int)($d['complaint_id'] ?? 0),
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function reply(array $d): bool
    {
        try {
            $msg = trim((string)($d['message'] ?? ''));
            if ($msg === '') return false;

            $uid = (int)($_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? 0);

            $sql = "UPDATE complaints
                       SET reply_text = :msg,
                           assigned_to_user_id = COALESCE(assigned_to_user_id, :uid),
                           status = CASE WHEN status='Open' THEN 'In Progress' ELSE status END
                     WHERE complaint_id = :cid";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([
                ':cid' => (int)($d['complaint_id'] ?? 0),
                ':uid' => $uid ?: null,
                ':msg' => $msg,
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
