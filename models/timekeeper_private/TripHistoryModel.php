<?php
namespace App\models\timekeeper_private;

use PDO;

class TripHistoryModel extends BaseModel
{
    public function list(string $from, string $to): array
    {
        $sql = "
        SELECT
          DATE(p.trip_date) AS date,
          r.route_no, r.name AS route_name,
          p.turn_no,
          p.bus_reg_no,
          TIME(p.scheduled_departure_time) AS dep_time,
          TIME(p.arrival_time)             AS arr_time,
          CASE
            WHEN p.status='Completed' THEN 'Completed'
            WHEN p.status='Cancelled' THEN 'Cancelled'
            WHEN p.status='InProgress' THEN 'Running'
            ELSE 'Planned'
          END AS ui_status
        FROM private_trips p
        JOIN private_buses pb ON pb.reg_no=p.bus_reg_no AND pb.private_operator_id=:op
        LEFT JOIN routes r ON r.route_id=p.route_id
        WHERE p.trip_date BETWEEN :from AND :to
        ORDER BY p.trip_date DESC, p.scheduled_departure_time DESC";
        $st = $this->pdo->prepare($sql);
        $st->execute([':op'=>$this->opId, ':from'=>$from, ':to'=>$to]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        return [$rows, count($rows)];
    }
}
