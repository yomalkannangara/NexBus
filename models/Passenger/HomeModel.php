<?php
namespace App\Models\Passenger;

// models/Passenger/HomeModel.php
use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}
class HomeModel extends BaseModel {

    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }

    public function routes(): array {
        $sql = "SELECT route_id, route_no, stops_json FROM routes ORDER BY route_no";
        $rows = $this->pdo->query($sql)->fetchAll();
        foreach ($rows as &$r) $r['name'] = $this->getRouteDisplayName($r['stops_json']);
        return $rows;
    }

    public function nextBuses(?int $routeId = null, ?string $otype = null, int $limit = 12): array {
        $sql = "SELECT r.route_no, r.stops_json, tt.bus_reg_no, tt.operator_type, tt.departure_time, tt.arrival_time,
                       TIMESTAMPDIFF(MINUTE, tt.departure_time, CURTIME()) AS minutes_from_departure
                FROM timetables tt
                JOIN routes r ON r.route_id = tt.route_id
                WHERE tt.arrival_time > CURTIME() AND tt.departure_time < CURTIME()
                  AND (tt.effective_from IS NULL OR tt.effective_from <= CURDATE())
                  AND (tt.effective_to IS NULL OR tt.effective_to >= CURDATE())";
        if ($routeId !== null) $sql .= " AND r.route_id = " . (int)$routeId;
        if ($otype !== null) $sql .= " AND tt.operator_type = " . $this->pdo->quote($otype);
        $sql .= " ORDER BY ABS(TIME_TO_SEC(TIMEDIFF(tt.departure_time, CURTIME()))) ASC LIMIT " . max(1, (int)$limit);
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) $r['name'] = $this->getRouteDisplayName($r['stops_json']);
        return $rows;
    }
}