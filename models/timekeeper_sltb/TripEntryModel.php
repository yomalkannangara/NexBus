<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;
use PDO;

class TripEntryModel extends BaseModel
{
    private ?array $routeStopColumns = null;
    private ?string $locationCache = null;

    private function normalizeStop(string $text): string
    {
        $norm = strtolower(trim($text));
        if ($norm === '') {
            return '';
        }
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
                    "SELECT COALESCE(NULLIF(timekeeper_location,''), 'Common') FROM users WHERE user_id=? LIMIT 1"
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
        if ($depotId <= 0) {
            return;
        }

        $event = $this->emergencyTypeAndPriority($reason);
        $u = $_SESSION['user'] ?? [];
        $sourceUserId = (int)($u['user_id'] ?? $u['id'] ?? 0);
        $sourceRole = (string)($u['role'] ?? 'SLTBTimekeeper');
        $sourceName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
        if ($sourceName === '') {
            $sourceName = (string)($u['name'] ?? 'SLTB Timekeeper');
        }

        $message = sprintf(
            'EMERGENCY UPDATE: Trip #%d was cancelled by %s. Bus: %s, Route ID: %d. Reason: %s',
            $tripId,
            $sourceName,
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
        if (empty($recipientIds)) {
            return;
        }

        $ins = $this->pdo->prepare(
            "INSERT INTO notifications (user_id, type, message, is_seen, priority, metadata, created_at)
             VALUES (:uid, :type, :message, 0, :priority, :metadata, NOW())"
        );
        foreach ($recipientIds as $rid) {
            $ins->execute([
                ':uid' => $rid,
                ':type' => $event['type'],
                ':message' => $message,
                ':priority' => $event['priority'],
                ':metadata' => $metadata,
            ]);
        }
    }

    public function todayList(): array
    {
        $stopsExpr = $this->routeStopsExpr('r');
        $sql = <<<SQL
        SELECT
            t.timetable_id,
            r.route_no, {$stopsExpr} AS stops_json,
            t.bus_reg_no,
            TIME(t.departure_time)  AS sched_dep,
            TIME(t.arrival_time)    AS sched_arr,
            (CURRENT_TIME() BETWEEN TIME(t.departure_time)
                               AND IFNULL(TIME(t.arrival_time),'23:59:59')) AS is_current,
            s.sltb_trip_id               AS trip_id,
            COALESCE(s.status, 'Planned') AS trip_status,
            COALESCE(s.turn_no, 0)       AS turn_no,
            (s.sltb_trip_id IS NOT NULL) AS already_today
        FROM timetables t
        JOIN routes r ON r.route_id = t.route_id
        LEFT JOIN sltb_trips s
               ON s.timetable_id = t.timetable_id AND s.trip_date = CURDATE()
        WHERE t.operator_type='SLTB'
        ORDER BY TIME(t.departure_time), r.route_no+0, r.route_no
        SQL;

        $st = $this->pdo->prepare($sql);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $now = date('H:i:s');
        $filtered = [];
        foreach ($rows as &$r) {
            if (!$this->routeContainsLocation((string)($r['stops_json'] ?? '[]'))) {
                continue;
            }
            $r['route_name'] = $this->getRouteDisplayName((string)($r['stops_json'] ?? '[]'));
            $ts = (string)($r['trip_status'] ?? '');
            $arr = (string)($r['sched_arr'] ?? '');
            if ($ts === 'InProgress') {
                $r['ui_status'] = ($arr && $now > $arr) ? 'Delayed' : 'Running';
            } elseif ($ts === 'Completed') {
                $r['ui_status'] = 'Completed';
            } elseif ($ts === 'Cancelled') {
                $r['ui_status'] = 'Cancelled';
            } else {
                $r['ui_status'] = 'Scheduled';
            }
            $filtered[] = $r;
        }
        unset($r);
        return $filtered;
    }

    public function upcoming(int $minutes = 60): array
    {
        $now = date('H:i:s');
        $until = date('H:i:s', strtotime("+{$minutes} minutes"));
        $tenTo = date('H:i:s', strtotime('+10 minutes'));
        $stopsExpr = $this->routeStopsExpr('r');

        $sql = <<<SQL
        SELECT
            t.timetable_id, r.route_no, {$stopsExpr} AS stops_json, t.bus_reg_no,
            TIME(t.departure_time) AS sched_dep,
            TIME(t.arrival_time)   AS sched_arr,
            s.sltb_trip_id AS trip_id,
            COALESCE(s.status,'Planned') AS trip_status
        FROM timetables t
        JOIN routes r ON r.route_id = t.route_id
        LEFT JOIN sltb_trips s
               ON s.timetable_id = t.timetable_id AND s.trip_date = CURDATE()
        WHERE t.operator_type = 'SLTB'
          AND TIME(t.departure_time) BETWEEN :now AND :until
          AND (
            s.sltb_trip_id IS NULL
            OR s.status NOT IN ('InProgress','Completed','Cancelled')
          )
        ORDER BY TIME(t.departure_time)
        SQL;

        $st = $this->pdo->prepare($sql);
        $st->execute([':now' => $now, ':until' => $until]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $filtered = [];
        foreach ($rows as &$r) {
            if (!$this->routeContainsLocation((string)($r['stops_json'] ?? '[]'))) {
                continue;
            }
            $r['route_name'] = $this->getRouteDisplayName((string)($r['stops_json'] ?? '[]'));
            $dep = (string)($r['sched_dep'] ?? '');
            $r['reminder'] = ($dep && $dep <= $tenTo);
            $r['eta_label'] = substr($dep, 0, 5);
            $filtered[] = $r;
        }
        unset($r);

        return $filtered;
    }

    public function historyList(string $from, string $to, ?string $busNo = null): array
    {
        $params = [':from' => $from, ':to' => $to];
        $busCond = '';
        $stopsExpr = $this->routeStopsExpr('r');
        if ($busNo !== null && $busNo !== '') {
            $busCond = 'AND st.bus_reg_no = :bus';
            $params[':bus'] = $busNo;
        }

        $sql = <<<SQL
        SELECT
            st.trip_date AS date,
            r.route_no, {$stopsExpr} AS stops_json,
            COALESCE(st.turn_no, 0) AS turn_no,
            st.bus_reg_no,
            TIME_FORMAT(COALESCE(st.departure_time,st.scheduled_departure_time),'%H:%i') AS dep_time,
            TIME_FORMAT(COALESCE(st.arrival_time,st.scheduled_arrival_time),'%H:%i') AS arr_time,
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
        JOIN routes r ON r.route_id = st.route_id
        WHERE st.trip_date BETWEEN :from AND :to
          AND st.status IN ('Completed','Cancelled')
          {$busCond}
        ORDER BY st.trip_date DESC, dep_time DESC
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filtered = [];
        foreach ($rows as &$r) {
            if (!$this->routeContainsLocation((string)($r['stops_json'] ?? '[]'))) {
                continue;
            }
            $r['route_name'] = $this->getRouteDisplayName((string)($r['stops_json'] ?? '[]'));
            $filtered[] = $r;
        }
        unset($r);

        if ($from <= date('Y-m-d') && $to >= date('Y-m-d')) {
            $cutoff = date('H:i:s', strtotime('-30 minutes'));
            $absSql = <<<SQL
            SELECT
                CURDATE()               AS date,
                r.route_no, {$stopsExpr} AS stops_json,
                0                       AS turn_no,
                t.bus_reg_no,
                TIME_FORMAT(TIME(t.departure_time),'%H:%i') AS dep_time,
                NULL                    AS arr_time,
                NULL                    AS cancel_reason,
                'Absent'                AS ui_status
            FROM timetables t
            JOIN routes r ON r.route_id = t.route_id
            LEFT JOIN sltb_trips s
                   ON s.timetable_id = t.timetable_id AND s.trip_date = CURDATE()
            WHERE t.operator_type = 'SLTB'
              AND s.sltb_trip_id IS NULL
              AND TIME(t.departure_time) < :cutoff
            ORDER BY TIME(t.departure_time)
            SQL;
            $absst = $this->pdo->prepare($absSql);
            $absst->execute([':cutoff' => $cutoff]);
            $absent = $absst->fetchAll(PDO::FETCH_ASSOC);

            $absFiltered = [];
            foreach ($absent as &$a) {
                if (!$this->routeContainsLocation((string)($a['stops_json'] ?? '[]'))) {
                    continue;
                }
                $a['route_name'] = $this->getRouteDisplayName((string)($a['stops_json'] ?? '[]'));
                $absFiltered[] = $a;
            }
            unset($a);

            $filtered = array_merge($absFiltered, $filtered);
        }

        return $filtered;
    }

    public function busList(): array
    {
        $stopsExpr = $this->routeStopsExpr('r');
        $st = $this->pdo->query(
            "SELECT DISTINCT t.bus_reg_no, {$stopsExpr} AS stops_json
             FROM timetables t
             JOIN routes r ON r.route_id = t.route_id
             WHERE t.operator_type = 'SLTB'
             ORDER BY t.bus_reg_no"
        );
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

    public function start(int $timetableId): array
    {
        $stopsExpr = $this->routeStopsExpr('r');
        $q = "SELECT t.timetable_id, t.route_id, t.bus_reg_no, t.departure_time, t.arrival_time,
                     {$stopsExpr} AS stops_json, b.sltb_depot_id, a.sltb_driver_id, a.sltb_conductor_id
              FROM timetables t
              JOIN routes r ON r.route_id = t.route_id
              LEFT JOIN sltb_buses b ON b.reg_no = t.bus_reg_no
              LEFT JOIN sltb_assignments a
                     ON a.assigned_date = CURDATE() AND a.bus_reg_no = t.bus_reg_no
              WHERE t.timetable_id=:tt AND t.operator_type='SLTB'";
        $st = $this->pdo->prepare($q);
        $st->execute([':tt' => $timetableId]);
        $t = $st->fetch(PDO::FETCH_ASSOC);
        if (!$t) {
            return ['ok' => false, 'msg' => 'Timetable not found'];
        }
        if (!$this->routeContainsLocation((string)($t['stops_json'] ?? '[]'))) {
            return ['ok' => false, 'msg' => 'not_authorized'];
        }

        $cnt = (int)$this->pdo->query(
            "SELECT COUNT(*) FROM sltb_trips
             WHERE bus_reg_no=" . $this->pdo->quote((string)$t['bus_reg_no']) . "
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
            ':tt' => (int)$t['timetable_id'],
            ':bus' => $t['bus_reg_no'],
            ':sdep' => $t['departure_time'],
            ':sarr' => $t['arrival_time'],
            ':rid' => (int)$t['route_id'],
            ':drv' => $t['sltb_driver_id'] ?? null,
            ':con' => $t['sltb_conductor_id'] ?? null,
            ':depot' => (int)($t['sltb_depot_id'] ?? 0),
            ':turn' => $turn,
        ]);

        return ['ok' => $ok, 'turn' => $turn];
    }

    public function arrive(int $tripId): array
    {
        $stopsExpr = $this->routeStopsExpr('r');
        $trip = $this->pdo->prepare(
            "SELECT st.sltb_trip_id, st.status, {$stopsExpr} AS stops_json
             FROM sltb_trips st
             LEFT JOIN routes r ON r.route_id = st.route_id
             WHERE st.sltb_trip_id=:id AND st.trip_date=CURDATE()"
        );
        $trip->execute([':id' => $tripId]);
        $t = $trip->fetch(PDO::FETCH_ASSOC);
        if (!$t) {
            return ['ok' => false, 'msg' => 'no_trip'];
        }
        if ($t['status'] !== 'InProgress') {
            return ['ok' => false, 'msg' => 'not_running'];
        }
        if (!$this->routeContainsLocation((string)($t['stops_json'] ?? '[]'))) {
            return ['ok' => false, 'msg' => 'not_authorized'];
        }

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

    public function cancel(int $tripId, ?string $reason = null): array
    {
        $reasonText = trim((string)($reason ?? ''));
        if ($reasonText === '') {
            return ['ok' => false, 'msg' => 'no_reason'];
        }
        $stopsExpr = $this->routeStopsExpr('r');

        $trip = $this->pdo->prepare(
            "SELECT st.sltb_trip_id, st.timetable_id, st.route_id, st.bus_reg_no,
                    st.status, st.sltb_depot_id, {$stopsExpr} AS stops_json
             FROM sltb_trips st
             LEFT JOIN routes r ON r.route_id = st.route_id
             WHERE st.sltb_trip_id=:id AND st.trip_date=CURDATE()"
        );
        $trip->execute([':id' => $tripId]);
        $t = $trip->fetch(PDO::FETCH_ASSOC);
        if (!$t) {
            return ['ok' => false, 'msg' => 'no_trip'];
        }
        if ($t['status'] !== 'InProgress') {
            return ['ok' => false, 'msg' => 'not_in_progress'];
        }
        if (!$this->routeContainsLocation((string)($t['stops_json'] ?? '[]'))) {
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
            $this->notifyDepotEmergency((int)($t['sltb_depot_id'] ?? 0), $tripId, $t, $reasonText);
        }
        return ['ok' => $ok, 'msg' => $ok ? null : 'update_failed'];
    }
}
