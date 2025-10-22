<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;
use PDO;

class TrackingModel extends BaseModel
{
    /**
     * Returns SLTB trip logs for the logged-in user's depot between [from..to]
     * Columns needed for the table:
     * Date, Route, Turn Number, Bus ID, Departure Time, Arrival Time, Status
     */
    public function logs(string $from, string $to): array
    {
        // read depot directly from session (as you asked)
        $depotId = (int)($_SESSION['user']['sltb_depot_id'] ?? 0);

        // safety: if not logged/available, return empty
        if ($depotId <= 0) return [];

        // build query
        $sql = "
        SELECT
            t.sltb_trip_id,
            COALESCE(t.trip_date, CURDATE())               AS trip_date,
            r.route_no                                     AS route,
            -- if turn_no not set, compute it by order of departures for the bus on that date
            COALESCE(t.turn_no,
                     ROW_NUMBER() OVER (
                        PARTITION BY t.bus_reg_no, COALESCE(t.trip_date, CURDATE())
                        ORDER BY t.departure_time
                     )
            )                                              AS turn_number,
            t.bus_reg_no                                   AS bus_id,
            t.departure_time,
            t.arrival_time,
            t.status
        FROM sltb_trips t
        LEFT JOIN timetables tt ON tt.timetable_id = t.timetable_id
        LEFT JOIN routes r       ON r.route_id = COALESCE(t.route_id, tt.route_id)
        INNER JOIN sltb_buses b  ON b.reg_no = t.bus_reg_no
        WHERE b.sltb_depot_id = :depot
          AND COALESCE(t.trip_date, CURDATE()) BETWEEN :from AND :to
        ORDER BY trip_date DESC, t.departure_time DESC, t.sltb_trip_id DESC
        ";

        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':depot' => $depotId,
            ':from'  => $from,
            ':to'    => $to,
        ]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
