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
    public function logs(string $from, string $to, array $filters = []): array
    {
        // read depot directly from session (as you asked)
        $depotId = (int)($_SESSION['user']['sltb_depot_id'] ?? 0);

        // safety: if not logged/available, return empty
        if ($depotId <= 0) return [];

        // build base query and params
        $sql = "
        SELECT
            t.sltb_trip_id                                              AS timekeeper_id,
            COALESCE(t.trip_date, CURDATE())                            AS trip_date,
            r.route_no                                                  AS route,
            COALESCE(t.turn_no,
                     ROW_NUMBER() OVER (
                        PARTITION BY t.bus_reg_no, COALESCE(t.trip_date, CURDATE())
                        ORDER BY COALESCE(t.scheduled_departure_time, t.departure_time)
                     )
            )                                                           AS turn_number,
            t.bus_reg_no                                                AS bus_id,
            COALESCE(sd.full_name, '—')                                 AS driver,
            t.scheduled_departure_time                                  AS scheduled_dep,
            t.departure_time                                            AS actual_dep,
            t.scheduled_arrival_time                                    AS scheduled_arr,
            t.arrival_time                                              AS actual_arr,
            CASE
                WHEN t.status = 'InProgress' AND tm_latest.operational_status = 'Delayed' THEN 'Delayed'
                ELSE COALESCE(t.status, 'Planned')
            END                                                         AS status,
            CASE
                WHEN t.cancelled_at IS NOT NULL
                    THEN t.cancelled_at
                WHEN t.arrival_time IS NOT NULL
                    THEN CONCAT(COALESCE(t.trip_date, CURDATE()), ' ', t.arrival_time)
                WHEN t.departure_time IS NOT NULL
                    THEN CONCAT(COALESCE(t.trip_date, CURDATE()), ' ', t.departure_time)
                ELSE CONCAT(COALESCE(t.trip_date, CURDATE()), ' 00:00:00')
            END                                                         AS last_updated
        FROM sltb_trips t
        LEFT JOIN timetables tt   ON tt.timetable_id = t.timetable_id
        LEFT JOIN routes r        ON r.route_id = COALESCE(t.route_id, tt.route_id)
        LEFT JOIN sltb_drivers sd ON sd.sltb_driver_id = t.sltb_driver_id
        INNER JOIN sltb_buses b   ON b.reg_no = t.bus_reg_no
        LEFT JOIN (
            SELECT tm1.bus_reg_no, tm1.operational_status
            FROM tracking_monitoring tm1
            INNER JOIN (
                SELECT bus_reg_no, MAX(snapshot_at) AS max_snap
                FROM tracking_monitoring
                GROUP BY bus_reg_no
            ) tm2 ON tm2.bus_reg_no = tm1.bus_reg_no AND tm2.max_snap = tm1.snapshot_at
        ) tm_latest ON tm_latest.bus_reg_no = t.bus_reg_no
        WHERE b.sltb_depot_id = :depot
          AND COALESCE(t.trip_date, CURDATE()) BETWEEN :from AND :to
        ";

        $params = [':depot' => $depotId, ':from' => $from, ':to' => $to];

        // optional filters
        if (!empty($filters['route'])) {
            // accept either route id or route_no
            if (ctype_digit(strval($filters['route']))) {
                $sql .= "\n AND r.route_id = :route_id";
                $params[':route_id'] = (int)$filters['route'];
            } else {
                $sql .= "\n AND r.route_no = :route_no";
                $params[':route_no'] = $filters['route'];
            }
        }
        if (!empty($filters['bus_id'])) {
            $sql .= "\n AND t.bus_reg_no LIKE :bus";
            $params[':bus'] = '%' . str_replace('%', '', $filters['bus_id']) . '%';
        }
        if (!empty($filters['departure_time'])) {
            $sql .= "\n AND t.departure_time LIKE :dep_time";
            $params[':dep_time'] = rtrim($filters['departure_time'], '%') . '%';
        }
        if (!empty($filters['arrival_time'])) {
            $sql .= "\n AND t.arrival_time LIKE :arr_time";
            $params[':arr_time'] = rtrim($filters['arrival_time'], '%') . '%';
        }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'Delayed') {
                // match rows explicitly marked Delayed OR InProgress buses tracked as Delayed
                $sql .= "\n AND (t.status = 'Delayed' OR (t.status = 'InProgress' AND tm_latest.operational_status = 'Delayed'))";
            } else {
                $sql .= "\n AND t.status = :status";
                $params[':status'] = $filters['status'];
            }
        }

        $sql .= "\n ORDER BY trip_date DESC, t.departure_time DESC, t.sltb_trip_id DESC";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
