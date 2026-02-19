<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;
use PDO;

class TripHistoryModel extends BaseModel
{
    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }
    
    /** Logged depot id from session */
    private function depotId(): int {
        $u = $_SESSION['user'] ?? [];
        return (int)($u['sltb_depot_id'] ?? 0);
    }

    /** Routes for the dropdown (only this depot’s buses appeared on these routes) */
    public function routesForDepot(): array {
        $depot = $this->depotId();
        $sql = "
          SELECT DISTINCT r.route_id, r.route_no, r.stops_json
          FROM sltb_trips st
          JOIN routes r     ON r.route_id = st.route_id
          JOIN sltb_buses b ON b.reg_no   = st.bus_reg_no
          WHERE b.sltb_depot_id = :d
          ORDER BY r.route_no+0, r.route_no
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([':d'=>$depot]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as &$r) {
            $r['name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
        }
        
        return $rows;
    }

    /**
     * Trip rows for history.
     * Filters: date range (inclusive), optional route_id, optional turn_no.
     * Output fields match the screenshot.
     */
    public function list(string $from, string $to, ?int $routeId, ?int $turnNo): array
    {
        $depot = $this->depotId();

        $sql = "
        SELECT
            st.trip_date                                       AS date,
            r.route_no,
            r.stops_json,
            st.turn_no,
            st.bus_reg_no,
            TIME_FORMAT(COALESCE(st.departure_time, st.scheduled_departure_time),'%H:%i') AS dep_time,
            CASE 
              WHEN st.status='Cancelled' THEN NULL
              ELSE TIME_FORMAT(COALESCE(st.arrival_time, st.scheduled_arrival_time),'%H:%i')
            END                                                AS arr_time,
            CASE
              WHEN st.status='Cancelled' THEN 'Cancelled'
              WHEN st.status='Completed' 
                   AND st.arrival_time IS NOT NULL 
                   AND st.scheduled_arrival_time IS NOT NULL
                   AND st.arrival_time > st.scheduled_arrival_time
                THEN 'Delayed'
              WHEN st.status='InProgress' 
                   AND st.trip_date = CURDATE()
                   AND st.scheduled_arrival_time IS NOT NULL
                   AND CURTIME() > st.scheduled_arrival_time
                THEN 'Delayed'
              WHEN st.status='Completed' THEN 'Completed'
              WHEN st.status='InProgress' THEN 'Running'
              ELSE 'Scheduled'
            END AS ui_status
        FROM sltb_trips st
        JOIN routes r     ON r.route_id = st.route_id
        JOIN sltb_buses b ON b.reg_no   = st.bus_reg_no
        WHERE b.sltb_depot_id = :depot
          AND st.trip_date BETWEEN :from AND :to
          /**/ %ROUTE_FILT% /**/
          /**/ %TURN_FILT%  /**/
        ORDER BY st.trip_date DESC, COALESCE(st.departure_time, st.scheduled_departure_time) DESC, st.bus_reg_no
        ";

        $params = [
            ':depot' => $depot,
            ':from'  => $from,
            ':to'    => $to,
        ];

        // Optional filters
        if ($routeId && $routeId > 0) {
            $sql = str_replace('%ROUTE_FILT%', "AND st.route_id = :rid", $sql);
            $params[':rid'] = $routeId;
        } else {
            $sql = str_replace('%ROUTE_FILT%', "", $sql);
        }
        if ($turnNo && $turnNo > 0) {
            $sql = str_replace('%TURN_FILT%', "AND st.turn_no = :turn", $sql);
            $params[':turn'] = $turnNo;
        } else {
            $sql = str_replace('%TURN_FILT%', "", $sql);
        }

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as &$r) {
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
        }
        
        return $rows;
    }
}
