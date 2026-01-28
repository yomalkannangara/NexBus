<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;
use PDO;

class TurnModel extends BaseModel
{
    private function depotId(): int {
        $u = $_SESSION['user'] ?? [];
        return (int)($u['sltb_depot_id'] ?? 0);
    }

    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }

    public function running(): array
    {
        $sql = <<<SQL
        SELECT
          st.sltb_trip_id, st.timetable_id, st.bus_reg_no, st.turn_no,
          r.route_no, r.stops_json,
          st.scheduled_departure_time AS sched_dep,
          st.scheduled_arrival_time   AS sched_arr,
          st.departure_time           AS actual_dep,
          st.status                   AS trip_status,
          TIMESTAMPDIFF(MINUTE, st.scheduled_departure_time, st.departure_time) AS delay_min
        FROM sltb_trips st
        JOIN routes r     ON r.route_id = st.route_id
        JOIN sltb_buses b ON b.reg_no   = st.bus_reg_no
        WHERE st.trip_date=CURDATE() AND st.status='InProgress' AND b.sltb_depot_id=:depot
        ORDER BY st.scheduled_departure_time, r.route_no+0, r.route_no
        SQL;

        $st = $this->pdo->prepare($sql);
        $st->execute([':depot'=>$this->depotId()]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
        }
        return $rows;
    }

    public function complete(int $tripId): bool
    {
        $st = $this->pdo->prepare(
            "UPDATE sltb_trips
             SET arrival_time=CURRENT_TIME(), status='Completed'
             WHERE sltb_trip_id=:id AND status='InProgress' AND trip_date=CURDATE()"
        );
        $st->execute([':id'=>$tripId]);
        return $st->rowCount() > 0;
    }
}
