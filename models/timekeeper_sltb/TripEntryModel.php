<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;
use PDO;

class TripEntryModel extends BaseModel
{
    private function depotId(): int {
        $u = $_SESSION['user'] ?? [];
        return (int)($u['sltb_depot_id'] ?? 0);
    }

    /** Today’s SLTB timetables for my depot; marks current window & whether already started today. */
    public function todayList(): array
    {
        $sql = <<<SQL
        SELECT
            t.timetable_id,
            r.route_no, r.name AS route_name,
            t.bus_reg_no,
            TIME(t.departure_time) AS sched_dep,
            TIME(t.arrival_time)   AS sched_arr,
            (CURRENT_TIME() BETWEEN TIME(t.departure_time)
                               AND IFNULL(TIME(t.arrival_time),'23:59:59')) AS is_current,
            EXISTS(
              SELECT 1 FROM sltb_trips s
               WHERE s.timetable_id=t.timetable_id AND s.trip_date=CURDATE()
            ) AS already_today
        FROM timetables t
        JOIN routes r     ON r.route_id = t.route_id
        JOIN sltb_buses b ON b.reg_no   = t.bus_reg_no
        WHERE t.operator_type='SLTB' AND b.sltb_depot_id=:depot
        ORDER BY TIME(t.departure_time), r.route_no+0, r.route_no
        SQL;

        $st = $this->pdo->prepare($sql);
        $st->execute([':depot'=>$this->depotId()]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Start: copy timetable → sltb_trips (idempotent for today via unique key). */
    public function start(int $timetableId): array
    {
        // pull TT + bus + route + depot + today's staff assignment
        $q = "SELECT t.timetable_id, t.route_id, t.bus_reg_no, t.departure_time, t.arrival_time,
                     b.sltb_depot_id, a.sltb_driver_id, a.sltb_conductor_id
              FROM timetables t
              JOIN sltb_buses b ON b.reg_no=t.bus_reg_no
              LEFT JOIN sltb_assignments a
                     ON a.assigned_date=CURDATE() AND a.bus_reg_no=t.bus_reg_no
              WHERE t.timetable_id=:tt AND t.operator_type='SLTB'";
        $st = $this->pdo->prepare($q);
        $st->execute([':tt'=>$timetableId]);
        $t = $st->fetch(PDO::FETCH_ASSOC);
        if (!$t) return ['ok'=>false,'msg'=>'Timetable not found'];

        // next turn = completed today + 1
        $cnt = (int)$this->pdo->query(
            "SELECT COUNT(*) c FROM sltb_trips
             WHERE bus_reg_no=".$this->pdo->quote($t['bus_reg_no'])."
               AND trip_date=CURDATE() AND status='Completed'"
        )->fetch()['c'];
        $turn = $cnt + 1;

        $ins = "INSERT INTO sltb_trips
                  (timetable_id, bus_reg_no, trip_date,
                   scheduled_departure_time, scheduled_arrival_time,
                   route_id, sltb_driver_id, sltb_conductor_id, sltb_depot_id,
                   turn_no, departure_time, status)
                VALUES
                  (:tt, :bus, CURDATE(),
                   :sdep, :sarr,
                   :rid, :drv, :con, :depot,
                   :turn, CURRENT_TIME(), 'InProgress')
                ON DUPLICATE KEY UPDATE
                   status='InProgress',
                   departure_time=VALUES(departure_time),
                   sltb_driver_id=VALUES(sltb_driver_id),
                   sltb_conductor_id=VALUES(sltb_conductor_id),
                   turn_no=VALUES(turn_no)";
        $ok = $this->pdo->prepare($ins)->execute([
            ':tt'   => (int)$t['timetable_id'],
            ':bus'  => $t['bus_reg_no'],
            ':sdep' => $t['departure_time'],
            ':sarr' => $t['arrival_time'],
            ':rid'  => (int)$t['route_id'],
            ':drv'  => $t['sltb_driver_id'] ?? null,
            ':con'  => $t['sltb_conductor_id'] ?? null,
            ':depot'=> (int)$t['sltb_depot_id'],
            ':turn' => $turn
        ]);

        return ['ok'=>$ok, 'turn'=>$turn];
    }
}
