<?php
namespace App\models\timekeeper_private;

use PDO;

class TurnModel extends BaseModel
{
    public function running(): array
    {
        $sql = "
        SELECT
          p.private_trip_id, p.timetable_id, p.bus_reg_no, p.turn_no,
          r.route_no, r.name AS route_name,
          p.scheduled_departure_time AS sched_dep,
          p.scheduled_arrival_time   AS sched_arr,
          p.departure_time           AS actual_dep
        FROM private_trips p
        JOIN private_buses pb ON pb.reg_no=p.bus_reg_no AND pb.private_operator_id=:op
        LEFT JOIN routes r ON r.route_id=p.route_id
        WHERE p.trip_date=CURDATE() AND p.status='InProgress'
        ORDER BY p.scheduled_departure_time, r.route_no+0, r.route_no";
        $st = $this->pdo->prepare($sql);
        $st->execute([':op'=>$this->opId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['delay_min'] = max(0, (int)round((strtotime($r['actual_dep']) - strtotime($r['sched_dep']))/60));
        }
        return $rows;
    }

    public function complete(int $tripId): bool
    {
        $st = $this->pdo->prepare(
            "UPDATE private_trips p
               JOIN private_buses pb ON pb.reg_no=p.bus_reg_no AND pb.private_operator_id=:op
               SET p.arrival_time=CURRENT_TIME(), p.status='Completed'
             WHERE p.private_trip_id=:id AND p.status='InProgress' AND p.trip_date=CURDATE()"
        );
        $st->execute([':id'=>$tripId, ':op'=>$this->opId]);
        return $st->rowCount() > 0;
    }
}
