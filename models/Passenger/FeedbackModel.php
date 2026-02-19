<?php
namespace App\Models\Passenger;

use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() { $this->pdo = $GLOBALS['db']; }
}

class FeedbackModel extends BaseModel {
    /** Change this to 'feedback' if that's your table name */
    protected string $tbl = 'complaints';

    private ?bool $complaintsHasRating = null;

    private function complaintsHasRating(): bool {
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
        } catch (\Throwable $e) {
            $this->complaintsHasRating = false;
        }
        return $this->complaintsHasRating;
    }

    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }

    public function routes(): array {
        $sql = "SELECT route_id, route_no, stops_json FROM routes ORDER BY route_no+0, route_no";
        $rows = $this->pdo->query($sql)->fetchAll() ?: [];
        foreach ($rows as &$r) $r['name'] = $this->getRouteDisplayName($r['stops_json']);
        return $rows;
    }

    public function addFeedback(array $p, ?int $passengerId): void {
        // Store both types (feedback/complaint) in one table via 'category'
        $rating = isset($p['rating']) && is_numeric($p['rating']) ? (int)$p['rating'] : null;
        if ($rating !== null && ($rating < 1 || $rating > 5)) $rating = null;

        if ($this->complaintsHasRating()) {
            $sql = "INSERT INTO {$this->tbl}
                      (passenger_id, operator_type, bus_reg_no, route_id, category, description, rating, status, created_at)
                    VALUES (?,?,?,?,?,?,?, 'Open', NOW())";
            $st  = $this->pdo->prepare($sql);
            $st->execute([
                $passengerId,
                $p['bus_type'] ?? 'SLTB',
                $p['bus_id']   ?? null,                       // free-text bus number
                !empty($p['route_id']) ? (int)$p['route_id'] : null,
                $p['type'] ?? 'feedback',                    // 'feedback' | 'complaint'
                trim($p['description'] ?? ''),
                $rating
            ]);
        } else {
            $sql = "INSERT INTO {$this->tbl}
                      (passenger_id, operator_type, bus_reg_no, route_id, category, description, status, created_at)
                    VALUES (?,?,?,?,?,?, 'Open', NOW())";
            $st  = $this->pdo->prepare($sql);
            $st->execute([
                $passengerId,
                $p['bus_type'] ?? 'SLTB',
                $p['bus_id']   ?? null,                       // free-text bus number
                !empty($p['route_id']) ? (int)$p['route_id'] : null,
                $p['type'] ?? 'feedback',                    // 'feedback' | 'complaint'
                trim($p['description'] ?? '')
            ]);
        }
    }

    public function mine(int $passengerId): array {
        // If your table has reply_text/status, this will show them. Otherwise it still renders cleanly.
        $ratingSelect = $this->complaintsHasRating() ? 'rating' : 'NULL AS rating';
        $sql = "SELECT complaint_id, created_at, route_id, bus_reg_no, operator_type,
                       category, status,
                       COALESCE(reply_text, NULL) AS reply_text,
                       {$ratingSelect},
                       description
                  FROM {$this->tbl}
                 WHERE passenger_id = ?
              ORDER BY complaint_id DESC";
        $st = $this->pdo->prepare($sql);
        $st->execute([$passengerId]);
        return $st->fetchAll() ?: [];
    }
   public function busesByRoute(int $routeId): array {
    $sql = "
        SELECT DISTINCT t.bus_reg_no, t.operator_type
          FROM timetables t
         WHERE t.route_id = ?
           AND (t.effective_from IS NULL OR t.effective_from <= CURDATE())
           AND (t.effective_to IS NULL OR t.effective_to >= CURDATE())
         ORDER BY t.bus_reg_no
    ";
    $st = $this->pdo->prepare($sql);
    $st->execute([$routeId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}




}
