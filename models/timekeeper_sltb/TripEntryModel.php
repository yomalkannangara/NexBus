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

    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }

    /** Today’s SLTB timetables for my depot; marks current window & whether already started today. */
    public function todayList(): array
    {
        $sql = <<<SQL
        SELECT
            t.timetable_id,
            r.route_no, r.stops_json,
            t.bus_reg_no,
            TIME(t.departure_time) AS sched_dep,
            TIME(t.arrival_time)   AS sched_arr,
            (CURRENT_TIME() BETWEEN TIME(t.departure_time)
                               AND IFNULL(TIME(t.arrival_time),'23:59:59')) AS is_current,
            s.sltb_trip_id AS trip_id,
            s.status       AS trip_status,
            (s.sltb_trip_id IS NOT NULL) AS already_today
        FROM timetables t
        JOIN routes r     ON r.route_id = t.route_id
        JOIN sltb_buses b ON b.reg_no   = t.bus_reg_no
        LEFT JOIN sltb_trips s ON s.timetable_id = t.timetable_id AND s.trip_date = CURDATE()
        WHERE t.operator_type='SLTB' AND b.sltb_depot_id=:depot
        ORDER BY TIME(t.departure_time), r.route_no+0, r.route_no
        SQL;

        $st = $this->pdo->prepare($sql);
        $st->execute([':depot'=>$this->depotId()]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as &$r) {
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
        }
        return $rows;
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

    /** Cancel an in-progress trip from the starting depot (entry page). */
    public function cancel(int $tripId, ?string $reason=null): array
    {
        $reasonText = trim((string)($reason ?? ''));
        if ($reasonText === '') return ['ok'=>false,'msg'=>'no_reason'];

        $trip = $this->pdo->prepare("SELECT sltb_trip_id, status, sltb_depot_id FROM sltb_trips WHERE sltb_trip_id=:id AND trip_date=CURDATE()");
        $trip->execute([':id'=>$tripId]);
        $t = $trip->fetch(PDO::FETCH_ASSOC);
        if (!$t) return ['ok'=>false,'msg'=>'no_trip'];
        if ($t['status'] !== 'InProgress') return ['ok'=>false,'msg'=>'not_in_progress'];
        // only depot that started the trip may cancel from entry page
        if ((int)$t['sltb_depot_id'] !== $this->depotId()) return ['ok'=>false,'msg'=>'not_authorized'];

        $upd = $this->pdo->prepare(
            "UPDATE sltb_trips SET status='Cancelled', cancelled_by=:user, cancel_reason=:reason, cancelled_at=CURRENT_TIMESTAMP()
             WHERE sltb_trip_id=:id AND status='InProgress'"
        );
        $upd->execute([':user'=>($_SESSION['user']['user_id'] ?? null), ':reason'=>$reasonText, ':id'=>$tripId]);
        return ['ok'=>$upd->rowCount() > 0, 'msg'=>$upd->rowCount()>0 ? null : 'update_failed'];
    }
}
