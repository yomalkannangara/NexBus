<?php
namespace App\models\timekeeper_private;

use PDO;

class TripEntryModel extends BaseModel
{
    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last  = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }

    /* ── Schedule list ────────────────────────────────────────────── */

    /**
     * Today's timetable for this operator's buses, with computed ui_status.
     * Status logic: Scheduled / Running / Delayed / Completed / Cancelled.
     */
    public function todayList(): array
    {
        $sql = "
        SELECT
            tt.timetable_id,
            r.route_no, r.stops_json,
            tt.bus_reg_no,
            TIME(tt.departure_time)  AS sched_dep,
            TIME(tt.arrival_time)    AS sched_arr,
            (CURRENT_TIME() BETWEEN TIME(tt.departure_time)
                               AND IFNULL(TIME(tt.arrival_time),'23:59:59')) AS is_current,
            p.private_trip_id         AS trip_id,
            COALESCE(p.status,'Planned') AS trip_status,
            COALESCE(p.turn_no, 0)    AS turn_no,
            (p.private_trip_id IS NOT NULL) AS already_today
        FROM timetables tt
        JOIN private_buses pb ON pb.reg_no = tt.bus_reg_no
                              AND pb.private_operator_id = :op
        JOIN routes r ON r.route_id = tt.route_id
        LEFT JOIN private_trips p
               ON p.timetable_id = tt.timetable_id AND p.trip_date = CURDATE()
        ORDER BY TIME(tt.departure_time), r.route_no+0, r.route_no";

        $st = $this->pdo->prepare($sql);
        $st->execute([':op' => $this->opId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $now = date('H:i:s');
        foreach ($rows as &$r) {
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
            $ts  = (string)($r['trip_status'] ?? '');
            $arr = (string)($r['sched_arr']   ?? '');
            if ($ts === 'InProgress') {
                $r['ui_status'] = ($arr && $now > $arr) ? 'Delayed' : 'Running';
            } elseif ($ts === 'Completed') {
                $r['ui_status'] = 'Completed';
            } elseif ($ts === 'Cancelled') {
                $r['ui_status'] = 'Cancelled';
            } else {
                $r['ui_status'] = 'Scheduled';
            }
        }
        unset($r);
        return $rows;
    }

    /**
     * Upcoming departures for this operator's buses in the next $minutes.
     * Used for the notification bar.
     */
    public function upcoming(int $minutes = 60): array
    {
        $now   = date('H:i:s');
        $until = date('H:i:s', strtotime("+{$minutes} minutes"));
        $tenTo = date('H:i:s', strtotime('+10 minutes'));

        $sql = "
        SELECT tt.timetable_id, r.route_no, r.stops_json, tt.bus_reg_no,
               TIME(tt.departure_time) AS sched_dep,
               TIME(tt.arrival_time)   AS sched_arr,
               p.private_trip_id AS trip_id,
               COALESCE(p.status,'Planned') AS trip_status
        FROM timetables tt
        JOIN private_buses pb ON pb.reg_no = tt.bus_reg_no
                              AND pb.private_operator_id = :op
        JOIN routes r ON r.route_id = tt.route_id
        LEFT JOIN private_trips p
               ON p.timetable_id = tt.timetable_id AND p.trip_date = CURDATE()
        WHERE TIME(tt.departure_time) BETWEEN :now AND :until
          AND (p.private_trip_id IS NULL
               OR p.status NOT IN ('InProgress','Completed','Cancelled'))
        ORDER BY TIME(tt.departure_time)";

        $st = $this->pdo->prepare($sql);
        $st->execute([':op' => $this->opId, ':now' => $now, ':until' => $until]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
            $dep = $r['sched_dep'] ?? '';
            $r['reminder']  = ($dep && $dep <= $tenTo);
            $r['eta_label'] = substr($dep, 0, 5);
        }
        unset($r);
        return $rows;
    }

    /* ── History list ─────────────────────────────────────────────── */

    /**
     * History: Completed (with Delayed computed), Cancelled.
     * Includes today's Absent entries (scheduled but never started, dep >30 min ago).
     */
    public function historyList(string $from, string $to, ?string $busNo = null): array
    {
        $params  = [':op' => $this->opId, ':from' => $from, ':to' => $to];
        $busCond = '';
        if ($busNo !== null && $busNo !== '') {
            $busCond = 'AND p.bus_reg_no = :bus';
            $params[':bus'] = $busNo;
        }

        $sql = "
        SELECT
            DATE(p.trip_date)   AS date,
            r.route_no, r.stops_json,
            COALESCE(p.turn_no, 0)  AS turn_no,
            p.bus_reg_no,
            TIME_FORMAT(COALESCE(p.departure_time, p.scheduled_departure_time),'%H:%i') AS dep_time,
            TIME_FORMAT(COALESCE(p.arrival_time,   p.scheduled_arrival_time),  '%H:%i') AS arr_time,
            p.cancel_reason,
            CASE
              WHEN p.status='Cancelled' THEN 'Cancelled'
              WHEN p.status='Completed'
                   AND p.arrival_time IS NOT NULL
                   AND p.scheduled_arrival_time IS NOT NULL
                   AND p.arrival_time > p.scheduled_arrival_time THEN 'Delayed'
              WHEN p.status='Completed' THEN 'Completed'
              ELSE p.status
            END AS ui_status
        FROM private_trips p
        JOIN private_buses pb ON pb.reg_no = p.bus_reg_no
                              AND pb.private_operator_id = :op
        LEFT JOIN routes r ON r.route_id = p.route_id
        WHERE p.trip_date BETWEEN :from AND :to
          AND p.status IN ('Completed','Cancelled')
          {$busCond}
        ORDER BY p.trip_date DESC, dep_time DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
        }
        unset($r);

        // Absent (today only): scheduled timetable entries with no trip, departure >30 min ago
        if ($from <= date('Y-m-d') && $to >= date('Y-m-d')) {
            $cutoff = date('H:i:s', strtotime('-30 minutes'));
            $absSql = "
            SELECT
                CURDATE()               AS date,
                r.route_no, r.stops_json,
                0                       AS turn_no,
                tt.bus_reg_no,
                TIME_FORMAT(TIME(tt.departure_time),'%H:%i') AS dep_time,
                NULL                    AS arr_time,
                NULL                    AS cancel_reason,
                'Absent'                AS ui_status
            FROM timetables tt
            JOIN private_buses pb ON pb.reg_no = tt.bus_reg_no
                                  AND pb.private_operator_id = :op2
            JOIN routes r ON r.route_id = tt.route_id
            LEFT JOIN private_trips p2
                   ON p2.timetable_id = tt.timetable_id AND p2.trip_date = CURDATE()
            WHERE p2.private_trip_id IS NULL
              AND TIME(tt.departure_time) < :cutoff
            ORDER BY TIME(tt.departure_time)";
            $absst = $this->pdo->prepare($absSql);
            $absst->execute([':op2' => $this->opId, ':cutoff' => $cutoff]);
            $absent = $absst->fetchAll(PDO::FETCH_ASSOC);
            foreach ($absent as &$a) {
                $a['route_name'] = $this->getRouteDisplayName($a['stops_json'] ?? '[]');
            }
            unset($a);
            $rows = array_merge($absent, $rows);
        }
        return $rows;
    }

    /** Bus list for the history filter dropdown. */
    public function busList(): array
    {
        $st = $this->pdo->prepare(
            "SELECT reg_no FROM private_buses WHERE private_operator_id=:op ORDER BY reg_no"
        );
        $st->execute([':op' => $this->opId]);
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }

    /* ── Actions ─────────────────────────────────────────────────── */

    /** Start Journey: insert into private_trips for today (idempotent). */
    public function start(int $timetableId): array
    {
        if ($timetableId <= 0) return ['ok' => false, 'msg' => 'bad_tt'];

        $q = "SELECT tt.timetable_id, tt.route_id, tt.bus_reg_no,
                     tt.departure_time AS sdep, tt.arrival_time AS sarr
              FROM timetables tt
              JOIN private_buses pb ON pb.reg_no = tt.bus_reg_no
                                    AND pb.private_operator_id = :op
              WHERE tt.timetable_id = :tt";
        $st = $this->pdo->prepare($q);
        $st->execute([':op' => $this->opId, ':tt' => $timetableId]);
        $t = $st->fetch(PDO::FETCH_ASSOC);
        if (!$t) return ['ok' => false, 'msg' => 'not_found'];

        // Latest assignment today (optional)
        $as = $this->pdo->prepare(
            "SELECT private_driver_id, private_conductor_id
             FROM private_assignments
             WHERE private_operator_id=:op AND bus_reg_no=:b AND assigned_date=CURDATE()
             ORDER BY assignment_id DESC LIMIT 1"
        );
        $as->execute([':op' => $this->opId, ':b' => $t['bus_reg_no']]);
        $a = $as->fetch(PDO::FETCH_ASSOC);

        // Next turn for this bus today
        $st2 = $this->pdo->prepare(
            "SELECT IFNULL(MAX(turn_no),0) FROM private_trips
             WHERE bus_reg_no=:b AND trip_date=CURDATE()"
        );
        $st2->execute([':b' => $t['bus_reg_no']]);
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
            ':tt'   => $t['timetable_id'],
            ':bus'  => $t['bus_reg_no'],
            ':sdep' => $t['sdep'],
            ':sarr' => $t['sarr'],
            ':rid'  => $t['route_id'],
            ':drv'  => $a['private_driver_id']   ?? null,
            ':con'  => $a['private_conductor_id'] ?? null,
            ':op'   => $this->opId,
            ':turn' => $turn,
        ]);
        return ['ok' => $ok, 'turn' => $turn];
    }

    /**
     * Mark Arrived: set status=Completed + arrival_time = now.
     * Private timekeeper acts as both start and end point.
     */
    public function arrive(int $tripId): array
    {
        $trip = $this->pdo->prepare(
            "SELECT private_trip_id, status, private_operator_id
             FROM private_trips WHERE private_trip_id=:id AND trip_date=CURDATE()"
        );
        $trip->execute([':id' => $tripId]);
        $t = $trip->fetch(PDO::FETCH_ASSOC);
        if (!$t)                               return ['ok' => false, 'msg' => 'no_trip'];
        if ($t['status'] !== 'InProgress')     return ['ok' => false, 'msg' => 'not_running'];
        if ((int)$t['private_operator_id'] !== $this->opId)
                                               return ['ok' => false, 'msg' => 'not_authorized'];

        $uid = (int)($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0);
        $upd = $this->pdo->prepare(
            "UPDATE private_trips
             SET status='Completed', arrival_time=CURRENT_TIME(), completed_by=:user
             WHERE private_trip_id=:id AND status='InProgress'"
        );
        $upd->execute([':user' => $uid ?: null, ':id' => $tripId]);
        $ok = $upd->rowCount() > 0;
        return ['ok' => $ok, 'msg' => $ok ? null : 'update_failed'];
    }

    /** Cancel an InProgress trip (operator-scoped). */
    public function cancel(int $tripId, ?string $reason = null): array
    {
        $reasonText = trim((string)($reason ?? ''));
        if ($reasonText === '') return ['ok' => false, 'msg' => 'no_reason'];

        $trip = $this->pdo->prepare(
            "SELECT private_trip_id, status, private_operator_id
             FROM private_trips WHERE private_trip_id=:id AND trip_date=CURDATE()"
        );
        $trip->execute([':id' => $tripId]);
        $t = $trip->fetch(PDO::FETCH_ASSOC);
        if (!$t)                             return ['ok' => false, 'msg' => 'no_trip'];
        if ($t['status'] !== 'InProgress')   return ['ok' => false, 'msg' => 'not_in_progress'];
        if ((int)$t['private_operator_id'] !== $this->opId)
                                             return ['ok' => false, 'msg' => 'not_authorized'];

        $uid = (int)($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0);
        $upd = $this->pdo->prepare(
            "UPDATE private_trips
             SET status='Cancelled', cancelled_by=:user, cancel_reason=:reason,
                 cancelled_at=CURRENT_TIMESTAMP()
             WHERE private_trip_id=:id AND status='InProgress'"
        );
        $upd->execute([':user' => $uid ?: null, ':reason' => $reasonText, ':id' => $tripId]);
        $ok = $upd->rowCount() > 0;
        return ['ok' => $ok, 'msg' => $ok ? null : 'update_failed'];
    }
}