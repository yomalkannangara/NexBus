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

    /**
     * Returns 'start' (originating depot) or 'end' (destination depot).
     * Reads from session; falls back to DB lookup if not cached.
     */
    public function myPoint(): string
    {
        $u = $_SESSION['user'] ?? [];
        $v = $u['timekeeper_point'] ?? null;
        if ($v === 'start' || $v === 'end') return $v;

        $uid = (int)($u['user_id'] ?? $u['id'] ?? 0);
        if ($uid <= 0) return 'start';
        try {
            $st = $this->pdo->prepare(
                "SELECT COALESCE(timekeeper_point,'start') FROM users WHERE user_id=? LIMIT 1"
            );
            $st->execute([$uid]);
            $val = (string)($st->fetchColumn() ?: 'start');
            $_SESSION['user']['timekeeper_point'] = ($val === 'end') ? 'end' : 'start';
            return $_SESSION['user']['timekeeper_point'];
        } catch (\Throwable $e) {
            return 'start';
        }
    }

    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }

    private function emergencyTypeAndPriority(string $reason): array
    {
        $r = strtolower($reason);
        $isBreakdown = str_contains($r, 'breakdown')
            || str_contains($r, 'engine')
            || str_contains($r, 'mechanical')
            || str_contains($r, 'failure');
        return $isBreakdown
            ? ['type' => 'Breakdown', 'priority' => 'critical']
            : ['type' => 'Alert', 'priority' => 'urgent'];
    }

    private function notifyDepotEmergency(int $depotId, int $tripId, array $trip, string $reason): void
    {
        if ($depotId <= 0) return;

        $event = $this->emergencyTypeAndPriority($reason);
        $u = $_SESSION['user'] ?? [];
        $sourceUserId = (int)($u['user_id'] ?? $u['id'] ?? 0);
        $sourceRole   = (string)($u['role'] ?? 'SLTBTimekeeper');
        $sourceName   = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
        if ($sourceName === '') $sourceName = (string)($u['name'] ?? 'SLTB Timekeeper');

        $message = sprintf(
            'EMERGENCY UPDATE: Trip #%d was cancelled by %s. Bus: %s, Route ID: %d. Reason: %s',
            $tripId, $sourceName,
            (string)($trip['bus_reg_no'] ?? 'N/A'),
            (int)($trip['route_id'] ?? 0),
            $reason
        );

        $metadata = json_encode([
            'source' => 'sltb_timekeeper_emergency',
            'source_role' => $sourceRole,
            'source_user_id' => $sourceUserId,
            'source_name' => $sourceName,
            'event_kind' => 'trip_cancelled',
            'trip_id' => $tripId,
            'timetable_id' => (int)($trip['timetable_id'] ?? 0),
            'route_id' => (int)($trip['route_id'] ?? 0),
            'bus_reg_no' => (string)($trip['bus_reg_no'] ?? ''),
            'depot_id' => $depotId,
            'reason' => $reason,
        ], JSON_UNESCAPED_UNICODE);

        $stRecipients = $this->pdo->prepare(
            "SELECT user_id FROM users WHERE sltb_depot_id=:depot AND role IN ('DepotOfficer','DepotManager')"
        );
        $stRecipients->execute([':depot' => $depotId]);
        $recipientIds = array_map('intval', $stRecipients->fetchAll(PDO::FETCH_COLUMN));
        if (empty($recipientIds)) return;

        $ins = $this->pdo->prepare(
            "INSERT INTO notifications (user_id, type, message, is_seen, priority, metadata, created_at)
             VALUES (:uid, :type, :message, 0, :priority, :metadata, NOW())"
        );
        foreach ($recipientIds as $rid) {
            $ins->execute([
                ':uid' => $rid, ':type' => $event['type'],
                ':message' => $message, ':priority' => $event['priority'],
                ':metadata' => $metadata,
            ]);
        }
    }

    /* ── Public schedule methods ─────────────────────────────────────── */

    /** Today's schedule list, role-aware (start vs end point). */
    public function todayList(): array
    {
        $point = $this->myPoint();
        $rows  = ($point === 'end') ? $this->todayListEnd() : $this->todayListStart();

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

    /** START-POINT: today's timetable for my depot's buses. */
    private function todayListStart(): array
    {
        $sql = <<<SQL
        SELECT
            t.timetable_id,
            r.route_no, r.stops_json,
            t.bus_reg_no,
            TIME(t.departure_time)  AS sched_dep,
            TIME(t.arrival_time)    AS sched_arr,
            (CURRENT_TIME() BETWEEN TIME(t.departure_time)
                               AND IFNULL(TIME(t.arrival_time),'23:59:59')) AS is_current,
            s.sltb_trip_id                 AS trip_id,
            COALESCE(s.status, 'Planned')  AS trip_status,
            COALESCE(s.turn_no, 0)         AS turn_no,
            (s.sltb_trip_id IS NOT NULL)   AS already_today
        FROM timetables t
        JOIN routes r     ON r.route_id = t.route_id
        JOIN sltb_buses b ON b.reg_no   = t.bus_reg_no
        LEFT JOIN sltb_trips s
               ON s.timetable_id = t.timetable_id AND s.trip_date = CURDATE()
        WHERE t.operator_type='SLTB' AND b.sltb_depot_id=:depot
        ORDER BY TIME(t.departure_time), r.route_no+0, r.route_no
        SQL;

        $st = $this->pdo->prepare($sql);
        $st->execute([':depot' => $this->depotId()]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** END-POINT: today's InProgress + Planned trips from any bus/depot. */
    private function todayListEnd(): array
    {
        $sql = <<<SQL
        SELECT
            st.timetable_id,
            r.route_no, r.stops_json,
            st.bus_reg_no,
            TIME(st.scheduled_departure_time)  AS sched_dep,
            TIME(st.scheduled_arrival_time)    AS sched_arr,
            0                                  AS is_current,
            st.sltb_trip_id                    AS trip_id,
            st.status                          AS trip_status,
            COALESCE(st.turn_no, 1)            AS turn_no,
            1                                  AS already_today,
            d.name                             AS origin_depot
        FROM sltb_trips st
        JOIN routes r       ON r.route_id  = st.route_id
        JOIN sltb_buses b   ON b.reg_no    = st.bus_reg_no
        LEFT JOIN sltb_depots d ON d.sltb_depot_id = st.sltb_depot_id
        WHERE st.trip_date = CURDATE()
          AND st.status IN ('InProgress','Planned')
        ORDER BY TIME(st.scheduled_arrival_time), st.bus_reg_no
        SQL;

        $st = $this->pdo->prepare($sql);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Upcoming departures (start) or arrivals (end) within $minutes — for notification bar. */
    public function upcoming(int $minutes = 60): array
    {
        $point = $this->myPoint();
        $now   = date('H:i:s');
        $until = date('H:i:s', strtotime("+{$minutes} minutes"));

        if ($point === 'start') {
            $sql = <<<SQL
            SELECT t.timetable_id, r.route_no, r.stops_json, t.bus_reg_no,
                   TIME(t.departure_time) AS sched_dep,
                   TIME(t.arrival_time)   AS sched_arr,
                   s.sltb_trip_id AS trip_id,
                   COALESCE(s.status,'Planned') AS trip_status
            FROM timetables t
            JOIN routes r     ON r.route_id = t.route_id
            JOIN sltb_buses b ON b.reg_no   = t.bus_reg_no
            LEFT JOIN sltb_trips s
                   ON s.timetable_id = t.timetable_id AND s.trip_date = CURDATE()
            WHERE t.operator_type = 'SLTB'
              AND b.sltb_depot_id = :depot
              AND TIME(t.departure_time) BETWEEN :now AND :until
              AND (s.sltb_trip_id IS NULL
                   OR s.status NOT IN ('InProgress','Completed','Cancelled'))
            ORDER BY TIME(t.departure_time)
            SQL;
            $st = $this->pdo->prepare($sql);
            $st->execute([':depot' => $this->depotId(), ':now' => $now, ':until' => $until]);
        } else {
            $sql = <<<SQL
            SELECT st.timetable_id, r.route_no, r.stops_json, st.bus_reg_no,
                   TIME(st.scheduled_departure_time) AS sched_dep,
                   TIME(st.scheduled_arrival_time)   AS sched_arr,
                   st.sltb_trip_id AS trip_id,
                   st.status       AS trip_status
            FROM sltb_trips st
            JOIN routes r ON r.route_id = st.route_id
            WHERE st.trip_date = CURDATE()
              AND st.status    = 'InProgress'
              AND TIME(st.scheduled_arrival_time) BETWEEN :now AND :until
            ORDER BY TIME(st.scheduled_arrival_time)
            SQL;
            $st = $this->pdo->prepare($sql);
            $st->execute([':now' => $now, ':until' => $until]);
        }

        $rows  = $st->fetchAll(PDO::FETCH_ASSOC);
        $tenTo = date('H:i:s', strtotime('+10 minutes'));
        foreach ($rows as &$r) {
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
            $dep = ($point === 'start') ? ($r['sched_dep'] ?? '') : ($r['sched_arr'] ?? '');
            $r['reminder']  = ($dep && $dep <= $tenTo);
            $r['eta_label'] = substr($dep, 0, 5);
        }
        unset($r);
        return $rows;
    }

    /**
     * History list for the embedded History tab.
     * Returns Completed, Delayed (computed), Cancelled from sltb_trips,
     * plus today's Absent entries (start-point only).
     */
    public function historyList(string $from, string $to, ?string $busNo = null): array
    {
        $depot = $this->depotId();
        $point = $this->myPoint();

        $params  = [':from' => $from, ':to' => $to];
        $busCond = '';
        if ($busNo !== null && $busNo !== '') {
            $busCond = 'AND st.bus_reg_no = :bus';
            $params[':bus'] = $busNo;
        }
        $depotCond = '';
        if ($point === 'start') {
            $depotCond = 'AND b.sltb_depot_id = :depot';
            $params[':depot'] = $depot;
        }

        $sql = <<<SQL
        SELECT
            st.trip_date                                                     AS date,
            r.route_no, r.stops_json,
            COALESCE(st.turn_no, 0)                                         AS turn_no,
            st.bus_reg_no,
            TIME_FORMAT(COALESCE(st.departure_time,st.scheduled_departure_time),'%H:%i') AS dep_time,
            TIME_FORMAT(COALESCE(st.arrival_time,st.scheduled_arrival_time),'%H:%i')    AS arr_time,
            st.cancel_reason,
            CASE
              WHEN st.status='Cancelled' THEN 'Cancelled'
              WHEN st.status='Completed'
                   AND st.arrival_time IS NOT NULL
                   AND st.scheduled_arrival_time IS NOT NULL
                   AND st.arrival_time > st.scheduled_arrival_time THEN 'Delayed'
              WHEN st.status='Completed' THEN 'Completed'
              ELSE st.status
            END AS ui_status
        FROM sltb_trips st
        JOIN routes r     ON r.route_id = st.route_id
        JOIN sltb_buses b ON b.reg_no   = st.bus_reg_no
        WHERE st.trip_date BETWEEN :from AND :to
          AND st.status IN ('Completed','Cancelled')
          {$depotCond}
          {$busCond}
        ORDER BY st.trip_date DESC, dep_time DESC
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
        }
        unset($r);

        // Absent (today, start-point): timetable entries whose departure passed 30 min ago with no trip
        if ($point === 'start' && $from <= date('Y-m-d') && $to >= date('Y-m-d')) {
            $cutoff = date('H:i:s', strtotime('-30 minutes'));
            $absSql = <<<SQL
            SELECT
                CURDATE()               AS date,
                r.route_no, r.stops_json,
                0                       AS turn_no,
                t.bus_reg_no,
                TIME_FORMAT(TIME(t.departure_time),'%H:%i') AS dep_time,
                NULL                    AS arr_time,
                NULL                    AS cancel_reason,
                'Absent'                AS ui_status
            FROM timetables t
            JOIN routes r     ON r.route_id = t.route_id
            JOIN sltb_buses b ON b.reg_no   = t.bus_reg_no
            LEFT JOIN sltb_trips s
                   ON s.timetable_id = t.timetable_id AND s.trip_date = CURDATE()
            WHERE t.operator_type  = 'SLTB'
              AND b.sltb_depot_id  = :dep2
              AND s.sltb_trip_id   IS NULL
              AND TIME(t.departure_time) < :cutoff
            ORDER BY TIME(t.departure_time)
            SQL;
            $absst = $this->pdo->prepare($absSql);
            $absst->execute([':dep2' => $depot, ':cutoff' => $cutoff]);
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
        if ($this->myPoint() === 'end') {
            $st = $this->pdo->query(
                "SELECT DISTINCT bus_reg_no FROM sltb_trips
                 WHERE trip_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                 ORDER BY bus_reg_no"
            );
        } else {
            $st = $this->pdo->prepare(
                "SELECT reg_no AS bus_reg_no FROM sltb_buses
                 WHERE sltb_depot_id=:d ORDER BY reg_no"
            );
            $st->execute([':d' => $this->depotId()]);
        }
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }

    /* ── Action methods ──────────────────────────────────────────────── */

    /** Start: copy timetable to sltb_trips (idempotent for today). Only start-point. */
    public function start(int $timetableId): array
    {
        $q = "SELECT t.timetable_id, t.route_id, t.bus_reg_no, t.departure_time, t.arrival_time,
                     b.sltb_depot_id, a.sltb_driver_id, a.sltb_conductor_id
              FROM timetables t
              JOIN sltb_buses b ON b.reg_no = t.bus_reg_no
              LEFT JOIN sltb_assignments a
                     ON a.assigned_date = CURDATE() AND a.bus_reg_no = t.bus_reg_no
              WHERE t.timetable_id=:tt AND t.operator_type='SLTB'";
        $st = $this->pdo->prepare($q);
        $st->execute([':tt' => $timetableId]);
        $t = $st->fetch(PDO::FETCH_ASSOC);
        if (!$t) return ['ok' => false, 'msg' => 'Timetable not found'];

        $cnt = (int)$this->pdo->query(
            "SELECT COUNT(*) FROM sltb_trips
             WHERE bus_reg_no=" . $this->pdo->quote($t['bus_reg_no']) . "
               AND trip_date=CURDATE() AND status='Completed'"
        )->fetchColumn();
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
            ':tt'    => (int)$t['timetable_id'],
            ':bus'   => $t['bus_reg_no'],
            ':sdep'  => $t['departure_time'],
            ':sarr'  => $t['arrival_time'],
            ':rid'   => (int)$t['route_id'],
            ':drv'   => $t['sltb_driver_id'] ?? null,
            ':con'   => $t['sltb_conductor_id'] ?? null,
            ':depot' => (int)$t['sltb_depot_id'],
            ':turn'  => $turn,
        ]);
        return ['ok' => $ok, 'turn' => $turn];
    }

    /**
     * Mark an InProgress trip as Completed (arrived).
     * Used by end-point timekeeper.
     */
    public function arrive(int $tripId): array
    {
        $trip = $this->pdo->prepare(
            "SELECT sltb_trip_id, status FROM sltb_trips
             WHERE sltb_trip_id=:id AND trip_date=CURDATE()"
        );
        $trip->execute([':id' => $tripId]);
        $t = $trip->fetch(PDO::FETCH_ASSOC);
        if (!$t)                        return ['ok' => false, 'msg' => 'no_trip'];
        if ($t['status'] !== 'InProgress') return ['ok' => false, 'msg' => 'not_running'];

        $uid = (int)($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0);
        $upd = $this->pdo->prepare(
            "UPDATE sltb_trips
             SET status='Completed', arrival_time=CURRENT_TIME(), completed_by=:user
             WHERE sltb_trip_id=:id AND status='InProgress'"
        );
        $upd->execute([':user' => $uid ?: null, ':id' => $tripId]);
        $ok = $upd->rowCount() > 0;
        return ['ok' => $ok, 'msg' => $ok ? null : 'update_failed'];
    }

    /**
     * Cancel an InProgress trip.
     * Start-point: only own depot trips.
     * End-point: any InProgress trip.
     */
    public function cancel(int $tripId, ?string $reason = null): array
    {
        $reasonText = trim((string)($reason ?? ''));
        if ($reasonText === '') return ['ok' => false, 'msg' => 'no_reason'];

        $trip = $this->pdo->prepare(
            "SELECT sltb_trip_id, timetable_id, route_id, bus_reg_no, status, sltb_depot_id
             FROM sltb_trips WHERE sltb_trip_id=:id AND trip_date=CURDATE()"
        );
        $trip->execute([':id' => $tripId]);
        $t = $trip->fetch(PDO::FETCH_ASSOC);
        if (!$t) return ['ok' => false, 'msg' => 'no_trip'];
        if ($t['status'] !== 'InProgress') return ['ok' => false, 'msg' => 'not_in_progress'];

        // Start-point may only cancel trips originating from its own depot
        if ($this->myPoint() === 'start' && (int)$t['sltb_depot_id'] !== $this->depotId()) {
            return ['ok' => false, 'msg' => 'not_authorized'];
        }

        $uid = (int)($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0);
        $upd = $this->pdo->prepare(
            "UPDATE sltb_trips
             SET status='Cancelled', cancelled_by=:user, cancel_reason=:reason,
                 cancelled_at=CURRENT_TIMESTAMP()
             WHERE sltb_trip_id=:id AND status='InProgress'"
        );
        $upd->execute([':user' => $uid ?: null, ':reason' => $reasonText, ':id' => $tripId]);
        $ok = $upd->rowCount() > 0;
        if ($ok) {
            $this->notifyDepotEmergency(
                (int)$t['sltb_depot_id'], $tripId, $t, $reasonText
            );
        }
        return ['ok' => $ok, 'msg' => $ok ? null : 'update_failed'];
    }
}
