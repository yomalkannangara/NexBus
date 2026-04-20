<?php
namespace App\models\timekeeper_private;

use PDO;

class TripEntryModel extends BaseModel
{
    private ?array $routeStopColumns = null;
    private ?string $locationCache = null;

    private function hasOperatorScope(): bool
    {
        return $this->opId > 0;
    }

    private function normalizeStop(string $text): string
    {
        $norm = strtolower(trim($text));
        if ($norm === '') return '';
        $norm = preg_replace('/[^a-z0-9 ]+/i', ' ', $norm) ?? $norm;
        $norm = preg_replace('/\s+/', ' ', $norm) ?? $norm;
        return trim($norm);
    }

    private function routeStopColumns(): array
    {
        if ($this->routeStopColumns !== null) {
            return $this->routeStopColumns;
        }

        $out = ['stops_json' => false, 'stops' => false];
        try {
            $st = $this->pdo->query('SHOW COLUMNS FROM routes');
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $name = strtolower((string)($row['Field'] ?? ''));
                if ($name === 'stops_json') {
                    $out['stops_json'] = true;
                }
                if ($name === 'stops') {
                    $out['stops'] = true;
                }
            }
        } catch (\Throwable $e) {
            $out['stops_json'] = true;
        }

        $this->routeStopColumns = $out;
        return $out;
    }

    private function routeStopsExpr(string $alias = 'r'): string
    {
        $cols = $this->routeStopColumns();
        if ($cols['stops_json'] && $cols['stops']) {
            return "COALESCE({$alias}.stops_json, {$alias}.stops)";
        }
        if ($cols['stops_json']) {
            return "{$alias}.stops_json";
        }
        if ($cols['stops']) {
            return "{$alias}.stops";
        }
        return "'[]'";
    }

    private function collectStopNamesFromNode(mixed $node, array &$out): void
    {
        if (is_string($node)) {
            $v = trim($node);
            if ($v !== '') {
                $out[] = $v;
            }
            return;
        }
        if (!is_array($node)) {
            return;
        }

        if (isset($node['stops'])) {
            $this->collectStopNamesFromNode($node['stops'], $out);
            return;
        }

        foreach (['stop', 'name', 'location'] as $k) {
            if (isset($node[$k]) && is_string($node[$k])) {
                $v = trim((string)$node[$k]);
                if ($v !== '') {
                    $out[] = $v;
                }
                return;
            }
        }

        foreach ($node as $child) {
            $this->collectStopNamesFromNode($child, $out);
        }
    }

    private function extractStopNames(string $raw): array
    {
        $text = trim($raw);
        if ($text === '' || strtolower($text) === 'null') {
            return [];
        }

        $attempts = [$text, stripslashes($text), str_replace('\\"', '"', $text)];
        if (
            (str_starts_with($text, '"') && str_ends_with($text, '"'))
            || (str_starts_with($text, "'") && str_ends_with($text, "'"))
        ) {
            $attempts[] = substr($text, 1, -1);
        }

        foreach ($attempts as $candidate) {
            $decoded = json_decode($candidate, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            if (is_string($decoded)) {
                $decoded2 = json_decode($decoded, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $decoded = $decoded2;
                }
            }

            $out = [];
            $this->collectStopNamesFromNode($decoded, $out);
            if (!empty($out)) {
                return $out;
            }
        }

        if (preg_match('/,|\||->|;|>/', $text)) {
            $parts = preg_split('/\s*(?:,|\||->|;|>)\s*/', $text) ?: [];
            $parts = array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));
            if (!empty($parts)) {
                return $parts;
            }
        }

        return [$text];
    }

    private function myLocation(): string
    {
        if ($this->locationCache !== null) {
            return $this->locationCache;
        }

        $u = $_SESSION['user'] ?? [];
        $uid = (int)($u['user_id'] ?? $u['id'] ?? 0);
        $cached = trim((string)($u['timekeeper_location'] ?? ''));

        try {
            if ($uid > 0) {
                $st = $this->pdo->prepare(
                    "SELECT COALESCE(NULLIF(timekeeper_location,''), 'Common') FROM users WHERE user_id = ? LIMIT 1"
                );
                $st->execute([$uid]);
                $location = trim((string)($st->fetchColumn() ?: ''));
                if ($location !== '') {
                    $this->locationCache = $location;
                    $_SESSION['user']['timekeeper_location'] = $location;
                    return $location;
                }
            }
        } catch (\Throwable $e) {
            // Fallback to session/default when DB lookup is unavailable.
        }

        $location = $cached !== '' ? $cached : 'Common';
        $this->locationCache = $location;
        $_SESSION['user']['timekeeper_location'] = $location;
        return $location;
    }

    public function myLocationLabel(): string
    {
        return $this->myLocation();
    }

    private function routeContainsLocation(string $stopsJson): bool
    {
        $location = $this->normalizeStop($this->myLocation());
        if ($location === '' || $location === 'common') {
            return true;
        }

        $stops = $this->extractStopNames($stopsJson ?: '[]');
        if (empty($stops)) {
            return false;
        }

        foreach ($stops as $stop) {
            $stopNorm = $this->normalizeStop((string)$stop);
            if ($stopNorm === '') {
                continue;
            }
            if (
                $stopNorm === $location
                || str_contains($stopNorm, $location)
                || str_contains($location, $stopNorm)
            ) {
                return true;
            }
        }
        return false;
    }

    private function getRouteDisplayName(string $stopsJson): string
    {
        $stops = $this->extractStopNames($stopsJson);
        if (empty($stops)) {
            return 'Unknown';
        }
        $first = (string)$stops[0];
        $last = (string)$stops[count($stops)-1];
        return "$first - $last";
    }

    private function delaySeconds(string $scheduledTime, ?string $actualTime = null): int
    {
        $scheduled = trim($scheduledTime);
        if ($scheduled === '') {
            return 0;
        }

        $actual = trim((string)($actualTime ?? date('H:i:s')));
        if ($actual === '') {
            return 0;
        }

        $baseDate = date('Y-m-d');
        $schedTs = strtotime($baseDate . ' ' . $scheduled);
        $actualTs = strtotime($baseDate . ' ' . $actual);
        if ($schedTs === false || $actualTs === false) {
            return 0;
        }

        $delta = $actualTs - $schedTs;
        return $delta > 0 ? $delta : 0;
    }

    /* ── Schedule list ────────────────────────────────────────────── */

    /**
     * Today's timetable for this operator's buses, with computed ui_status.
     * Status logic: Scheduled / Running / Delayed / Completed / Cancelled.
     */
    public function todayList(): array
    {
        $stopsExpr = $this->routeStopsExpr('r');
        $operatorJoin = $this->hasOperatorScope() ? 'AND pb.private_operator_id = :op' : '';
        $sql = "
        SELECT
            tt.timetable_id,
            tt.route_id,
            r.route_no, {$stopsExpr} AS stops_json,
            tt.bus_reg_no,
            TIME(tt.departure_time)  AS sched_dep,
            TIME(tt.arrival_time)    AS sched_arr,
            (CURRENT_TIME() BETWEEN TIME(tt.departure_time)
                               AND IFNULL(TIME(tt.arrival_time),'23:59:59')) AS is_current,
            p.private_trip_id         AS trip_id,
            COALESCE(p.status,'Planned') AS trip_status,
            COALESCE(p.turn_no, 0)    AS turn_no,
            TIME(p.arrival_time)      AS actual_arr,
            COALESCE(p.start_delay_seconds, 0) AS start_delay_seconds,
            COALESCE(p.end_delay_seconds, 0)   AS end_delay_seconds,
            (p.private_trip_id IS NOT NULL) AS already_today,
            d.full_name  AS driver_name,
            d.phone      AS driver_phone,
            c.full_name  AS conductor_name,
            c.phone      AS conductor_phone
        FROM timetables tt
        JOIN private_buses pb ON pb.reg_no = tt.bus_reg_no
                              {$operatorJoin}
        JOIN routes r ON r.route_id = tt.route_id
        LEFT JOIN private_trips p
               ON p.timetable_id = tt.timetable_id AND p.trip_date = CURDATE()
        LEFT JOIN private_drivers d ON d.private_driver_id = p.private_driver_id
        LEFT JOIN private_conductors c ON c.private_conductor_id = p.private_conductor_id
                WHERE tt.operator_type = 'Private'
                    AND tt.day_of_week = :dow
                ORDER BY
                    CASE
                        WHEN p.arrival_time IS NULL AND p.status IN ('InProgress','Delayed') THEN 0
                        WHEN p.private_trip_id IS NULL OR COALESCE(p.status,'Planned') = 'Planned' THEN 1
                        WHEN p.status NOT IN ('Cancelled','Completed') THEN 1
                        ELSE 2
                    END,
                    TIME(tt.departure_time), r.route_no+0, r.route_no";

        $st = $this->pdo->prepare($sql);
                $params = [':dow' => (int)date('w')];
                if ($this->hasOperatorScope()) {
                        $params[':op'] = $this->opId;
                }
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $filtered = [];
        foreach ($rows as &$r) {
            if (!$this->routeContainsLocation((string)($r['stops_json'] ?? '[]'))) {
                continue;
            }
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
            $ts  = (string)($r['trip_status'] ?? '');
            $actualArr = trim((string)($r['actual_arr'] ?? ''));
            $startDelay = (int)($r['start_delay_seconds'] ?? 0);
            $endDelay = (int)($r['end_delay_seconds'] ?? 0);
            $isRunning = $actualArr === '' && ($ts === 'InProgress' || $ts === 'Delayed');
            $r['can_manage'] = $isRunning;

            if ($ts === 'Cancelled') {
                $r['ui_status'] = 'Cancelled';
            } elseif ($isRunning) {
                $r['ui_status'] = ($ts === 'Delayed' || $startDelay > 0) ? 'Delayed' : 'Running';
            } elseif ($ts === 'Delayed' || $startDelay > 0 || $endDelay > 0) {
                $r['ui_status'] = 'Delayed';
            } elseif ($ts === 'Completed') {
                $r['ui_status'] = 'Completed';
            } else {
                $r['ui_status'] = 'Scheduled';
            }
            $r['is_current_schedule'] = ((int)($r['is_current'] ?? 0) === 1)
                && !in_array((string)$r['ui_status'], ['Completed', 'Cancelled'], true);
            $filtered[] = $r;
        }
        unset($r);
        return $filtered;
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
        $stopsExpr = $this->routeStopsExpr('r');
        $operatorJoin = $this->hasOperatorScope() ? 'AND pb.private_operator_id = :op' : '';

        $sql = "
        SELECT tt.timetable_id, r.route_no, {$stopsExpr} AS stops_json, tt.bus_reg_no,
               TIME(tt.departure_time) AS sched_dep,
               TIME(tt.arrival_time)   AS sched_arr,
               p.private_trip_id AS trip_id,
               COALESCE(p.status,'Planned') AS trip_status
        FROM timetables tt
        JOIN private_buses pb ON pb.reg_no = tt.bus_reg_no
                      {$operatorJoin}
        JOIN routes r ON r.route_id = tt.route_id
        LEFT JOIN private_trips p
               ON p.timetable_id = tt.timetable_id AND p.trip_date = CURDATE()
                WHERE tt.operator_type = 'Private'
                    AND tt.day_of_week = :dow
                    AND TIME(tt.departure_time) BETWEEN :now AND :until
          AND (p.private_trip_id IS NULL
             OR p.status NOT IN ('InProgress','Completed','Cancelled','Delayed'))
        ORDER BY TIME(tt.departure_time)";

        $st = $this->pdo->prepare($sql);
        $params = [':dow' => (int)date('w'), ':now' => $now, ':until' => $until];
        if ($this->hasOperatorScope()) {
            $params[':op'] = $this->opId;
        }
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $filtered = [];
        foreach ($rows as &$r) {
            if (!$this->routeContainsLocation((string)($r['stops_json'] ?? '[]'))) {
                continue;
            }
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
            $dep = $r['sched_dep'] ?? '';
            $r['reminder']  = ($dep && $dep <= $tenTo);
            $r['eta_label'] = substr($dep, 0, 5);
            $filtered[] = $r;
        }
        unset($r);
        return $filtered;
    }

    /* ── History list ─────────────────────────────────────────────── */

    /**
     * History: Completed (with Delayed computed), Cancelled.
     * Includes today's Absent entries (scheduled but never started, dep >30 min ago).
     */
    public function historyList(string $from, string $to, ?string $busNo = null): array
    {
        $params  = [':from' => $from, ':to' => $to];
        $busCond = '';
        $stopsExpr = $this->routeStopsExpr('r');
        $operatorJoin = $this->hasOperatorScope() ? 'AND pb.private_operator_id = :op' : '';
        if ($this->hasOperatorScope()) {
            $params[':op'] = $this->opId;
        }
        if ($busNo !== null && $busNo !== '') {
            $busCond = 'AND p.bus_reg_no = :bus';
            $params[':bus'] = $busNo;
        }

        $sql = "
        SELECT
            DATE(p.trip_date)   AS date,
            r.route_no, {$stopsExpr} AS stops_json,
            COALESCE(p.turn_no, 0)  AS turn_no,
            p.bus_reg_no,
            TIME_FORMAT(COALESCE(p.departure_time, p.scheduled_departure_time),'%H:%i') AS dep_time,
            TIME_FORMAT(COALESCE(p.arrival_time,   p.scheduled_arrival_time),  '%H:%i') AS arr_time,
                        COALESCE(p.start_delay_seconds, 0) AS start_delay_seconds,
                        COALESCE(p.end_delay_seconds, 0)   AS end_delay_seconds,
            p.cancel_reason,
            CASE
              WHEN p.status='Cancelled' THEN 'Cancelled'
                            WHEN p.status='Delayed' THEN 'Delayed'
                            WHEN COALESCE(p.start_delay_seconds,0) > 0 OR COALESCE(p.end_delay_seconds,0) > 0 THEN 'Delayed'
              WHEN p.status='Completed'
                   AND p.arrival_time IS NOT NULL
                   AND p.scheduled_arrival_time IS NOT NULL
                   AND p.arrival_time > p.scheduled_arrival_time THEN 'Delayed'
              WHEN p.status='Completed' THEN 'Completed'
              ELSE p.status
            END AS ui_status
        FROM private_trips p
        JOIN private_buses pb ON pb.reg_no = p.bus_reg_no
                                                            {$operatorJoin}
        LEFT JOIN routes r ON r.route_id = p.route_id
        WHERE p.trip_date BETWEEN :from AND :to
                    AND p.status IN ('Completed','Cancelled','Delayed')
          {$busCond}
        ORDER BY p.trip_date DESC, dep_time DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $filtered = [];
        foreach ($rows as &$r) {
            if (!$this->routeContainsLocation((string)($r['stops_json'] ?? '[]'))) {
                continue;
            }
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
            $filtered[] = $r;
        }
        unset($r);
        $rows = $filtered;

        // Absent (today only): scheduled timetable entries with no trip, departure >30 min ago
        if ($from <= date('Y-m-d') && $to >= date('Y-m-d')) {
            $cutoff = date('H:i:s', strtotime('-30 minutes'));
            $absSql = "
            SELECT
                CURDATE()               AS date,
                r.route_no, {$stopsExpr} AS stops_json,
                0                       AS turn_no,
                tt.bus_reg_no,
                TIME_FORMAT(TIME(tt.departure_time),'%H:%i') AS dep_time,
                NULL                    AS arr_time,
                0                       AS start_delay_seconds,
                0                       AS end_delay_seconds,
                NULL                    AS cancel_reason,
                'Absent'                AS ui_status
            FROM timetables tt
            JOIN private_buses pb ON pb.reg_no = tt.bus_reg_no
                                                                    {$operatorJoin}
            JOIN routes r ON r.route_id = tt.route_id
            LEFT JOIN private_trips p2
                   ON p2.timetable_id = tt.timetable_id AND p2.trip_date = CURDATE()
            WHERE p2.private_trip_id IS NULL
              AND TIME(tt.departure_time) < :cutoff
            ORDER BY TIME(tt.departure_time)";
            $absst = $this->pdo->prepare($absSql);
                        $absParams = [':cutoff' => $cutoff];
                        if ($this->hasOperatorScope()) {
                                $absParams[':op'] = $this->opId;
                        }
                        $absst->execute($absParams);
            $absent = $absst->fetchAll(PDO::FETCH_ASSOC);
            $absFiltered = [];
            foreach ($absent as &$a) {
                if (!$this->routeContainsLocation((string)($a['stops_json'] ?? '[]'))) {
                    continue;
                }
                $a['route_name'] = $this->getRouteDisplayName($a['stops_json'] ?? '[]');
                $absFiltered[] = $a;
            }
            unset($a);
            $rows = array_merge($absFiltered, $rows);
        }
        return $rows;
    }

    /** Bus list for the history filter dropdown. */
    public function busList(): array
    {
        $stopsExpr = $this->routeStopsExpr('r');
        $operatorJoin = $this->hasOperatorScope() ? 'AND pb.private_operator_id = :op' : '';

        $st = $this->pdo->prepare(
            "SELECT DISTINCT tt.bus_reg_no, {$stopsExpr} AS stops_json
             FROM timetables tt
             JOIN private_buses pb ON pb.reg_no = tt.bus_reg_no {$operatorJoin}
             JOIN routes r ON r.route_id = tt.route_id
             ORDER BY tt.bus_reg_no"
        );
        $params = $this->hasOperatorScope() ? [':op' => $this->opId] : [];
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            if (!$this->routeContainsLocation((string)($r['stops_json'] ?? '[]'))) {
                continue;
            }
            $reg = (string)($r['bus_reg_no'] ?? '');
            if ($reg !== '' && !in_array($reg, $out, true)) {
                $out[] = $reg;
            }
        }

        return $out;
    }

    /* ── Actions ─────────────────────────────────────────────────── */

    /** Start Journey: insert into private_trips for today (idempotent). */
    public function start(int $timetableId): array
    {
        if ($timetableId <= 0) return ['ok' => false, 'msg' => 'bad_tt'];
        $stopsExpr = $this->routeStopsExpr('r');
        $operatorJoin = $this->hasOperatorScope() ? 'AND pb.private_operator_id = :op' : '';

        $q = "SELECT tt.timetable_id, tt.route_id, tt.bus_reg_no,
                     pb.private_operator_id AS trip_operator_id,
                     {$stopsExpr} AS stops_json,
                     tt.departure_time AS sdep, tt.arrival_time AS sarr
              FROM timetables tt
              JOIN private_buses pb ON pb.reg_no = tt.bus_reg_no
                                    {$operatorJoin}
              LEFT JOIN routes r ON r.route_id = tt.route_id
                            WHERE tt.timetable_id = :tt
                                AND tt.operator_type = 'Private'
                                AND tt.day_of_week = :dow";
        $st = $this->pdo->prepare($q);
                $params = [':tt' => $timetableId, ':dow' => (int)date('w')];
        if ($this->hasOperatorScope()) {
            $params[':op'] = $this->opId;
        }
        $st->execute($params);
        $t = $st->fetch(PDO::FETCH_ASSOC);
        if (!$t) return ['ok' => false, 'msg' => 'not_found'];
        if (!$this->routeContainsLocation((string)($t['stops_json'] ?? '[]'))) {
            return ['ok' => false, 'msg' => 'not_authorized'];
        }

        $tripOperatorId = (int)($t['trip_operator_id'] ?? 0);
        if ($tripOperatorId <= 0 && $this->hasOperatorScope()) {
            $tripOperatorId = $this->opId;
        }
        $startDelaySeconds = $this->delaySeconds((string)($t['sdep'] ?? ''));
        $startStatus = $startDelaySeconds > 0 ? 'Delayed' : 'InProgress';

        // Latest assignment today (optional)
        if ($tripOperatorId > 0) {
            $as = $this->pdo->prepare(
                "SELECT private_driver_id, private_conductor_id
                 FROM private_assignments
                 WHERE private_operator_id=:op AND bus_reg_no=:b AND assigned_date=CURDATE()
                 ORDER BY assignment_id DESC LIMIT 1"
            );
            $as->execute([':op' => $tripOperatorId, ':b' => $t['bus_reg_no']]);
        } else {
            $as = $this->pdo->prepare(
                "SELECT private_driver_id, private_conductor_id
                 FROM private_assignments
                 WHERE bus_reg_no=:b AND assigned_date=CURDATE()
                 ORDER BY assignment_id DESC LIMIT 1"
            );
            $as->execute([':b' => $t['bus_reg_no']]);
        }
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
                         turn_no, departure_time, status, start_delay_seconds, end_delay_seconds)
                VALUES
                  (:tt, :bus, CURDATE(),
                   :sdep, :sarr,
                   :rid, :drv, :con, :op,
                         :turn, CURRENT_TIME(), :status, :start_delay, 0)
                ON DUPLICATE KEY UPDATE
                         status=VALUES(status),
                   departure_time=VALUES(departure_time),
                         start_delay_seconds=VALUES(start_delay_seconds),
                         end_delay_seconds=0,
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
            ':op'   => $tripOperatorId > 0 ? $tripOperatorId : null,
            ':turn' => $turn,
            ':status' => $startStatus,
            ':start_delay' => $startDelaySeconds,
        ]);
        return ['ok' => $ok, 'turn' => $turn, 'status' => $startStatus, 'start_delay_seconds' => $startDelaySeconds];
    }

    /**
     * Mark Arrived: set status=Completed + arrival_time = now.
     * Private timekeeper acts as both start and end point.
     */
    public function arrive(int $tripId): array
    {
        $stopsExpr = $this->routeStopsExpr('r');
        $trip = $this->pdo->prepare(
            "SELECT p.private_trip_id, p.status, p.private_operator_id, p.scheduled_arrival_time,
                    COALESCE(p.start_delay_seconds,0) AS start_delay_seconds,
                    {$stopsExpr} AS stops_json
             FROM private_trips p
             LEFT JOIN routes r ON r.route_id = p.route_id
             WHERE p.private_trip_id=:id AND p.trip_date=CURDATE()"
        );
        $trip->execute([':id' => $tripId]);
        $t = $trip->fetch(PDO::FETCH_ASSOC);
        if (!$t)                               return ['ok' => false, 'msg' => 'no_trip'];
        if (!in_array((string)$t['status'], ['InProgress', 'Delayed'], true)) return ['ok' => false, 'msg' => 'not_running'];
        if ($this->hasOperatorScope() && (int)$t['private_operator_id'] !== $this->opId)
                                               return ['ok' => false, 'msg' => 'not_authorized'];
        if (!$this->routeContainsLocation((string)($t['stops_json'] ?? '[]')))
                                               return ['ok' => false, 'msg' => 'not_authorized'];

        $endDelaySeconds = $this->delaySeconds((string)($t['scheduled_arrival_time'] ?? ''));
        $hasDelay = ((int)($t['start_delay_seconds'] ?? 0) > 0) || ($endDelaySeconds > 0) || ((string)$t['status'] === 'Delayed');
        $finalStatus = $hasDelay ? 'Delayed' : 'Completed';

        $uid = (int)($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0);
        $upd = $this->pdo->prepare(
            "UPDATE private_trips
             SET status=:status, arrival_time=CURRENT_TIME(), end_delay_seconds=:end_delay, completed_by=:user
             WHERE private_trip_id=:id AND status IN ('InProgress','Delayed')"
        );
        $upd->execute([
            ':status' => $finalStatus,
            ':end_delay' => $endDelaySeconds,
            ':user' => $uid ?: null,
            ':id' => $tripId,
        ]);
        $ok = $upd->rowCount() > 0;
        return [
            'ok' => $ok,
            'msg' => $ok ? null : 'update_failed',
            'status' => $finalStatus,
            'end_delay_seconds' => $endDelaySeconds,
        ];
    }

    /** Cancel an InProgress trip (operator-scoped). */
    public function cancel(int $tripId, ?string $reason = null): array
    {
        $reasonText = trim((string)($reason ?? ''));
        if ($reasonText === '') return ['ok' => false, 'msg' => 'no_reason'];
        $stopsExpr = $this->routeStopsExpr('r');

        $trip = $this->pdo->prepare(
            "SELECT p.private_trip_id, p.route_id, p.bus_reg_no, p.status, p.private_operator_id, {$stopsExpr} AS stops_json
             FROM private_trips p
             LEFT JOIN routes r ON r.route_id = p.route_id
             WHERE p.private_trip_id=:id AND p.trip_date=CURDATE()"
        );
        $trip->execute([':id' => $tripId]);
        $t = $trip->fetch(PDO::FETCH_ASSOC);
        if (!$t)                             return ['ok' => false, 'msg' => 'no_trip'];
        if (!in_array((string)$t['status'], ['InProgress', 'Delayed'], true)) return ['ok' => false, 'msg' => 'not_in_progress'];
        if ($this->hasOperatorScope() && (int)$t['private_operator_id'] !== $this->opId)
                                             return ['ok' => false, 'msg' => 'not_authorized'];
        if (!$this->routeContainsLocation((string)($t['stops_json'] ?? '[]')))
                                             return ['ok' => false, 'msg' => 'not_authorized'];

        $uid = (int)($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0);
        $upd = $this->pdo->prepare(
            "UPDATE private_trips
             SET status='Cancelled', cancelled_by=:user, cancel_reason=:reason,
                 cancelled_at=CURRENT_TIMESTAMP()
             WHERE private_trip_id=:id AND status IN ('InProgress','Delayed')"
        );
        $upd->execute([':user' => $uid ?: null, ':reason' => $reasonText, ':id' => $tripId]);
        $ok = $upd->rowCount() > 0;

        if ($ok) {
            $this->notifySltbDepotsOnRoute($tripId, $t, $reasonText, $uid);
        }
        return ['ok' => $ok, 'msg' => $ok ? null : 'update_failed'];
    }

    private function notifySltbDepotsOnRoute(int $tripId, array $trip, string $reason, int $cancellerUid): void
    {
        $routeId = (int)($trip['route_id'] ?? 0);
        $busNo   = (string)($trip['bus_reg_no'] ?? 'N/A');
        if ($routeId <= 0) {
            return;
        }

        $u = $_SESSION['user'] ?? [];
        $sourceName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
        if ($sourceName === '') {
            $sourceName = (string)($u['name'] ?? 'Private Timekeeper');
        }

        // Find all SLTB depots that serve this route
        try {
            $st = $this->pdo->prepare(
                "SELECT DISTINCT sb.sltb_depot_id
                 FROM timetables t
                 JOIN sltb_buses sb ON sb.reg_no = t.bus_reg_no
                 WHERE t.route_id = :rid AND sb.sltb_depot_id > 0"
            );
            $st->execute([':rid' => $routeId]);
            $depotIds = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        } catch (\Throwable $e) {
            return;
        }

        // Check which optional columns exist in notifications
        $hasPriority = false;
        $hasMetadata = false;
        try {
            $cols = $this->pdo->query('SHOW COLUMNS FROM notifications')->fetchAll(PDO::FETCH_COLUMN);
            $hasPriority = in_array('priority', $cols, true);
            $hasMetadata = in_array('metadata', $cols, true);
        } catch (\Throwable $e) {
            // ignore
        }

        $columns = ['user_id', 'type', 'message', 'is_seen'];
        $values  = [':uid', ':type', ':message', '0'];
        if ($hasPriority) { $columns[] = 'priority'; $values[] = ':priority'; }
        if ($hasMetadata) { $columns[] = 'metadata'; $values[] = ':metadata'; }
        $columns[] = 'created_at';
        $values[]  = 'NOW()';
        $sql = 'INSERT INTO notifications (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ')';
        $ins = $this->pdo->prepare($sql);

        $metadata = json_encode([
            'source' => 'private_timekeeper_emergency',
            'source_role' => 'PrivateTimekeeper',
            'source_user_id' => $cancellerUid,
            'source_name' => $sourceName,
            'event_kind' => 'private_trip_cancelled',
            'trip_id' => $tripId,
            'route_id' => $routeId,
            'bus_reg_no' => $busNo,
            'reason' => $reason,
        ], JSON_UNESCAPED_UNICODE);

        $message = sprintf(
            'PRIVATE BUS ALERT: Private Trip #%d on Bus %s (Route ID: %d) was cancelled by %s. Passengers may need SLTB coverage. Reason: %s',
            $tripId, $busNo, $routeId, $sourceName, $reason
        );

        if (empty($depotIds)) {
            return;
        }

        $notifiedUserIds = [];
        foreach ($depotIds as $depotId) {
            try {
                $stUsers = $this->pdo->prepare(
                    "SELECT user_id FROM users WHERE sltb_depot_id=:depot AND role = 'DepotOfficer'"
                );
                $stUsers->execute([':depot' => $depotId]);
                $recipients = array_diff(
                    array_map('intval', $stUsers->fetchAll(PDO::FETCH_COLUMN)),
                    $notifiedUserIds
                );
                foreach ($recipients as $rid) {
                    $params = [':uid' => $rid, ':type' => 'Alert', ':message' => $message];
                    if ($hasPriority) $params[':priority'] = 'urgent';
                    if ($hasMetadata) $params[':metadata'] = $metadata;
                    $ins->execute($params);
                }
                $notifiedUserIds = array_merge($notifiedUserIds, $recipients);
            } catch (\Throwable $e) {
                // Do not let notification failure break the cancel operation
            }
        }
    }
}