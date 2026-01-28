<?php
namespace App\models\timekeeper_private;

use PDO;

class TripEntryModel extends BaseModel
{
    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }

    /** Same shape as your SLTB markup expects */
    public function todayList(): array
    {
        $sql = "SELECT
            tt.timetable_id,
            r.route_no, r.stops_json,
            tt.bus_reg_no,
            TIME(tt.departure_time) AS sched_dep,
            TIME(tt.arrival_time)   AS sched_arr,
            (CURRENT_TIME() BETWEEN TIME(tt.departure_time)
                               AND IFNULL(TIME(tt.arrival_time),'23:59:59')) AS is_current,
            EXISTS(SELECT 1 FROM private_trips p
                    WHERE p.timetable_id=tt.timetable_id AND p.trip_date=CURDATE()
                  ) AS already_today
        FROM timetables tt
        JOIN private_buses pb ON pb.reg_no=tt.bus_reg_no AND pb.private_operator_id=:op
        JOIN routes r ON r.route_id=tt.route_id
        ORDER BY TIME(tt.departure_time), r.route_no+0, r.route_no";
        $st = $this->pdo->prepare($sql);
        $st->execute([':op'=>$this->opId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
        }
        return $rows;
    }

    /** Insert into private_trips for today; compute next turn_no per bus */
    public function start(int $timetableId): array
    {
        if ($timetableId <= 0) return ['ok'=>false,'msg'=>'bad_tt'];

        $q = "SELECT tt.timetable_id, tt.route_id, tt.bus_reg_no,
                     tt.departure_time AS sdep, tt.arrival_time AS sarr
                FROM timetables tt
                JOIN private_buses pb ON pb.reg_no=tt.bus_reg_no AND pb.private_operator_id=:op
               WHERE tt.timetable_id=:tt";
        $st = $this->pdo->prepare($q);
        $st->execute([':op'=>$this->opId, ':tt'=>$timetableId]);
        $t = $st->fetch(PDO::FETCH_ASSOC);
        if (!$t) return ['ok'=>false,'msg'=>'not_found'];

        // latest assignment today (optional)
        $as = $this->pdo->prepare(
            "SELECT private_driver_id, private_conductor_id
               FROM private_assignments
              WHERE private_operator_id=:op AND bus_reg_no=:b AND assigned_date=CURDATE()
              ORDER BY assignment_id DESC LIMIT 1"
        );
        $as->execute([':op'=>$this->opId, ':b'=>$t['bus_reg_no']]);
        $a = $as->fetch(PDO::FETCH_ASSOC);

        // next turn for this bus today
        $st2 = $this->pdo->prepare(
            "SELECT IFNULL(MAX(turn_no),0) FROM private_trips WHERE bus_reg_no=:b AND trip_date=CURDATE()"
        );
        $st2->execute([':b'=>$t['bus_reg_no']]);
        $turn = ((int)$st2->fetchColumn()) + 1;

        $ins = "INSERT INTO private_trips
                  (timetable_id, bus_reg_no, trip_date,
                   scheduled_departure_time, scheduled_arrival_time,
                   route_id, private_driver_id, private_conductor_id, private_operator_id,
                   turn_no, departure_time, status)
                VALUES
                  (:tt, :bus, CURDATE(),
                   :sdep, :sarr,
                   :rid, :drv, :con, :op,
                   :turn, CURRENT_TIME(), 'InProgress')
                ON DUPLICATE KEY UPDATE
                   status='InProgress',
                   departure_time=VALUES(departure_time),
                   private_driver_id=VALUES(private_driver_id),
                   private_conductor_id=VALUES(private_conductor_id),
                   turn_no=VALUES(turn_no)";
        $ok = $this->pdo->prepare($ins)->execute([
            ':tt'=>$t['timetable_id'],
            ':bus'=>$t['bus_reg_no'],
            ':sdep'=>$t['sdep'],
            ':sarr'=>$t['sarr'],
            ':rid'=>$t['route_id'],
            ':drv'=>$a['private_driver_id'] ?? null,
            ':con'=>$a['private_conductor_id'] ?? null,
            ':op'=>$this->opId,
            ':turn'=>$turn
        ]);

        return ['ok'=>$ok, 'turn'=>$turn];
    }
}
