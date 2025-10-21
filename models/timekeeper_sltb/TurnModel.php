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

    public function running(): array
    {
        $sql = <<<SQL
        SELECT
          st.sltb_trip_id, st.timetable_id, st.bus_reg_no, st.turn_no,
          r.route_no, r.name AS route_name,
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
        return $st->fetchAll(PDO::FETCH_ASSOC);
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
