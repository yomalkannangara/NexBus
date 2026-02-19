<?php
namespace App\Models\Passenger;

use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() { $this->pdo = $GLOBALS['db']; }
}

class TimetableModel extends BaseModel
{
    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }

    /** Routes for dropdown */
    public function routes(): array {
        $sql = "SELECT route_id, route_no, stops_json FROM routes WHERE is_active=1 ORDER BY route_no+0, route_no";
        $rows = $this->pdo->query($sql)->fetchAll();
        foreach ($rows as &$r) $r['name'] = $this->getRouteDisplayName($r['stops_json']);
        return $rows;
    }

    /** All stops for a route (decoded from routes.stops_json) */
    public function stopsForRoute(int $routeId): array {
        $st = $this->pdo->prepare("SELECT stops_json FROM routes WHERE route_id=? LIMIT 1");
        $st->execute([$routeId]);
        $row = $st->fetch();
        if (!$row) return [];
        $arr = json_decode($row['stops_json'] ?? '[]', true) ?: [];
        // normalize to ['name'=>..., 'idx'=>1..N]
        $out = [];
        foreach ($arr as $i=>$s) {
            $out[] = [
                'idx'  => $i+1,
                'name' => is_array($s) ? ($s['stop'] ?? ('Stop '.($i+1))) : (string)$s
            ];
        }
        return $out;
    }

    /** Return only the portion of stops a trip covers (by start_seq..end_seq) */
    public function segmentStops(array $stops, int $startSeq, int $endSeq): array {
        if (empty($stops)) return [];
        $start = max(1, min(count($stops), $startSeq));
        $end   = max(1, min(count($stops), $endSeq));
        if ($start > $end) [$start, $end] = [$end, $start];
        $slice = array_slice($stops, $start-1, $end-$start+1);
        return array_map(fn($s)=>$s['name'], $slice);
    }

    /** PHP: 0..6 (Sun..Sat) from Y-m-d */
    public function dowFromDate(string $dateYmd): int {
        $ts = strtotime($dateYmd) ?: time();
        // PHP: 0 (Sun) .. 6 (Sat)
        return (int)date('w', $ts);
    }

    /** Timetable rows for a given route + date (optional operator type) */
    public function tripsForDate(int $routeId, string $dateYmd, ?string $operatorType=null): array {
        $dow = $this->dowFromDate($dateYmd);

        $sql = "SELECT tt.timetable_id, tt.operator_type, tt.route_id, tt.bus_reg_no,
                       tt.day_of_week, tt.departure_time, tt.arrival_time,
                       tt.start_seq, tt.end_seq,
                       r.route_no, r.stops_json
                  FROM timetables tt
                  JOIN routes r ON r.route_id = tt.route_id
                 WHERE tt.route_id = ?
                   AND tt.day_of_week = ?
                   AND (tt.effective_from IS NULL OR tt.effective_from <= ?)
                   AND (tt.effective_to   IS NULL OR tt.effective_to   >= ?)";
        $args = [$routeId, $dow, $dateYmd, $dateYmd];

        if ($operatorType === 'SLTB' || $operatorType === 'Private') {
            $sql .= " AND tt.operator_type = ?";
            $args[] = $operatorType;
        }

        $sql .= " ORDER BY tt.departure_time ASC";
        $st = $this->pdo->prepare($sql);
        $st->execute($args);
        $rows = $st->fetchAll() ?: [];
        foreach ($rows as &$r) {
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
        }
        return $rows;
    }

    /** Rough minutes between hh:mm:ss times (same-day) */
    public function durationMinutes(?string $dep, ?string $arr): ?int {
        if (!$dep || !$arr) return null;
        $d = strtotime($dep);
        $a = strtotime($arr);
        if ($d===false || $a===false) return null;
        $mins = (int)round(($a - $d)/60);
        return $mins >= 0 ? $mins : null;
    }

    /** Latest monitoring status for a bus on a given date (OnTime/Delayed/Breakdown/OffDuty) */
    public function latestStatus(string $busRegNo, string $dateYmd): ?string {
        $sql = "SELECT operational_status
                  FROM tracking_monitoring
                 WHERE bus_reg_no = ?
                   AND DATE(snapshot_at) = ?
              ORDER BY snapshot_at DESC
                 LIMIT 1";
        $st = $this->pdo->prepare($sql);
        $st->execute([$busRegNo, $dateYmd]);
        $row = $st->fetch();
        return $row['operational_status'] ?? null;
    }
}
