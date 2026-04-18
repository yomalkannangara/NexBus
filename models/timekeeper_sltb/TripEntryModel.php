<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;
use PDO;

class TripEntryModel extends BaseModel
{
    private ?array $routeStopColumns = null;
    private ?string $locationCache = null;

    public function info(): array
    {
        $id = $this->depotId();
        if ($id <= 0) {
            return ['depot_name' => 'My Depot'];
        }

        try {
            $st = $this->pdo->prepare("SELECT name FROM sltb_depots WHERE sltb_depot_id=:d LIMIT 1");
            $st->execute([':d' => $id]);
            $name = (string)($st->fetchColumn() ?: 'My Depot');
            return ['depot_name' => $name];
        } catch (\Throwable $e) {
            return ['depot_name' => 'My Depot'];
        }
    }

    private function depotId(): int
    {
        $u = $_SESSION['user'] ?? [];
        return (int)($u['sltb_depot_id'] ?? 0);
    }

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

    private function insertNotifications(array $recipientIds, string $type, string $message, string $priority, string $metadata): void
    {
        if (empty($recipientIds)) {
            return;
        }
        $hasPriority = $this->columnExists('notifications', 'priority');
        $hasMetadata = $this->columnExists('notifications', 'metadata');

        $columns = ['user_id', 'type', 'message', 'is_seen'];
        $values  = [':uid', ':type', ':message', '0'];
        if ($hasPriority) { $columns[] = 'priority'; $values[] = ':priority'; }
        if ($hasMetadata) { $columns[] = 'metadata'; $values[] = ':metadata'; }
        $columns[] = 'created_at';
        $values[]  = 'NOW()';

        $sql = 'INSERT INTO notifications (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ')';
        $ins = $this->pdo->prepare($sql);

        foreach ($recipientIds as $rid) {
            $params = [':uid' => $rid, ':type' => $type, ':message' => $message];
            if ($hasPriority) $params[':priority'] = $priority;
            if ($hasMetadata) $params[':metadata'] = $metadata;
            try {
                $ins->execute($params);
            } catch (\Throwable $e) {
                // Do not let notification failure break the cancel operation
            }
        }
    }

    private function nearbyDepotIds(int $ownDepotId, int $routeId): array
    {
        if ($routeId <= 0) {
            return [];
        }
        try {
            $st = $this->pdo->prepare(
                "SELECT DISTINCT sb.sltb_depot_id
                 FROM timetables t
                 JOIN sltb_buses sb ON sb.reg_no = t.bus_reg_no
                 WHERE t.route_id = :rid
                   AND sb.sltb_depot_id != :own
                   AND sb.sltb_depot_id > 0"
            );
            $st->execute([':rid' => $routeId, ':own' => $ownDepotId]);
            return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function notifyDepotEmergency(int $depotId, int $tripId, array $trip, string $reason): void
    {
        $event = $this->emergencyTypeAndPriority($reason);
        $u = $_SESSION['user'] ?? [];
        $sourceUserId = (int)($u['user_id'] ?? $u['id'] ?? 0);
        $sourceRole = (string)($u['role'] ?? 'SLTBTimekeeper');
        $sourceName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
        if ($sourceName === '') {
            $sourceName = (string)($u['name'] ?? 'SLTB Timekeeper');
        }

        // The timekeeper's OWN depot (from session) — always gets notified,
        // because this is the depot whose officers actually need to respond.
        $tkDepotId = (int)($u['sltb_depot_id'] ?? 0);

        $routeId = (int)($trip['route_id'] ?? 0);
        $busNo   = (string)($trip['bus_reg_no'] ?? 'N/A');

        $message = sprintf(
            'EMERGENCY UPDATE: Trip #%d was cancelled by %s. Bus: %s, Route ID: %d. Reason: %s',
            $tripId, $sourceName, $busNo, $routeId, $reason
        );
        $metadata = json_encode([
            'source' => 'sltb_timekeeper_emergency',
            'source_role' => $sourceRole,
            'source_user_id' => $sourceUserId,
            'source_name' => $sourceName,
            'event_kind' => 'trip_cancelled',
            'trip_id' => $tripId,
            'timetable_id' => (int)($trip['timetable_id'] ?? 0),
            'route_id' => $routeId,
            'bus_reg_no' => $busNo,
            'depot_id' => $depotId,
            'reason' => $reason,
        ], JSON_UNESCAPED_UNICODE);

        // Collect all depot IDs to notify (deduplicated)
        $depotsToNotify = array_unique(array_filter([$depotId, $tkDepotId]));

        // ── Notify bus's depot + timekeeper's depot ───────────────────────
        $notifiedUserIds = [];
        foreach ($depotsToNotify as $did) {
            $stRecipients = $this->pdo->prepare(
                "SELECT user_id FROM users WHERE sltb_depot_id=:depot AND role IN ('DepotOfficer','DepotManager')"
            );
            $stRecipients->execute([':depot' => $did]);
            $recipientIds = array_diff(
                array_map('intval', $stRecipients->fetchAll(PDO::FETCH_COLUMN)),
                $notifiedUserIds
            );
            if ($recipientIds) {
                $this->insertNotifications(array_values($recipientIds), $event['type'], $message, $event['priority'], $metadata);
                $notifiedUserIds = array_merge($notifiedUserIds, $recipientIds);
            }
        }

        // ── Notify nearby depots that serve the same route ────────────────
        $nearbyDepots = $this->nearbyDepotIds($depotId, $routeId);
        foreach ($nearbyDepots as $nearDepotId) {
            if (in_array($nearDepotId, $depotsToNotify, true)) {
                continue; // already notified above
            }
            try {
                $depotName = (string)($this->pdo->query(
                    "SELECT name FROM sltb_depots WHERE sltb_depot_id=" . (int)$nearDepotId . " LIMIT 1"
                )->fetchColumn() ?: 'Nearby Depot');
            } catch (\Throwable $e) {
                $depotName = 'Nearby Depot';
            }

            $nearMessage = sprintf(
                'NEARBY ROUTE ALERT: Trip #%d on Bus %s (Route ID: %d) was cancelled near your route. Originating depot: %s. Reason: %s. Passengers may need re-routing.',
                $tripId, $busNo, $routeId, $depotName, $reason
            );
            $nearMetadata = json_encode([
                'source' => 'sltb_timekeeper_emergency',
                'source_role' => $sourceRole,
                'source_user_id' => $sourceUserId,
                'source_name' => $sourceName,
                'event_kind' => 'nearby_trip_cancelled',
                'trip_id' => $tripId,
                'route_id' => $routeId,
                'bus_reg_no' => $busNo,
                'originating_depot_id' => $depotId,
                'reason' => $reason,
            ], JSON_UNESCAPED_UNICODE);

            $stNearby = $this->pdo->prepare(
                "SELECT user_id FROM users WHERE sltb_depot_id=:depot AND role IN ('DepotOfficer','DepotManager')"
            );
            $stNearby->execute([':depot' => $nearDepotId]);
            $nearRecipients = array_diff(
                array_map('intval', $stNearby->fetchAll(PDO::FETCH_COLUMN)),
                $notifiedUserIds
            );
            if ($nearRecipients) {
                $this->insertNotifications(array_values($nearRecipients), $event['type'], $nearMessage, 'urgent', $nearMetadata);
            }
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
            TIME(s.arrival_time)         AS actual_arr,
            COALESCE(s.start_delay_seconds, 0) AS start_delay_seconds,
            COALESCE(s.end_delay_seconds, 0)   AS end_delay_seconds,
            (s.sltb_trip_id IS NOT NULL) AS already_today,
            d.full_name  AS driver_name,
            d.phone      AS driver_phone,
            c.full_name  AS conductor_name,
            c.phone      AS conductor_phone
        FROM timetables t
        JOIN routes r ON r.route_id = t.route_id
        LEFT JOIN sltb_trips s
               ON s.timetable_id = t.timetable_id AND s.trip_date = CURDATE()
        LEFT JOIN sltb_drivers d ON d.sltb_driver_id = s.sltb_driver_id
        LEFT JOIN sltb_conductors c ON c.sltb_conductor_id = s.sltb_conductor_id
                WHERE t.operator_type='SLTB'
                    AND t.day_of_week = :dow
                ORDER BY
                    CASE
                        WHEN s.arrival_time IS NULL AND s.status IN ('InProgress','Delayed') THEN 0
                        WHEN s.sltb_trip_id IS NULL OR COALESCE(s.status,'Planned') = 'Planned' THEN 1
                        ELSE 2
                    END,
                    TIME(t.departure_time), r.route_no+0, r.route_no
        SQL;

        $st = $this->pdo->prepare($sql);
                $st->execute([':dow' => (int)date('w')]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $filtered = [];
        foreach ($rows as &$r) {
            if (!$this->routeContainsLocation((string)($r['stops_json'] ?? '[]'))) {
                continue;
            }
            $r['route_name'] = $this->getRouteDisplayName((string)($r['stops_json'] ?? '[]'));
            $ts = (string)($r['trip_status'] ?? '');
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
                    AND t.day_of_week = :dow
          AND TIME(t.departure_time) BETWEEN :now AND :until
          AND (
            s.sltb_trip_id IS NULL
                        OR s.status NOT IN ('InProgress','Completed','Cancelled','Delayed')
          )
        ORDER BY TIME(t.departure_time)
        SQL;

        $st = $this->pdo->prepare($sql);
        $st->execute([':dow' => (int)date('w'), ':now' => $now, ':until' => $until]);
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
                        COALESCE(st.start_delay_seconds, 0) AS start_delay_seconds,
                        COALESCE(st.end_delay_seconds, 0)   AS end_delay_seconds,
            st.cancel_reason,
            CASE
              WHEN st.status='Cancelled' THEN 'Cancelled'
                            WHEN st.status='Delayed' THEN 'Delayed'
                            WHEN COALESCE(st.start_delay_seconds,0) > 0 OR COALESCE(st.end_delay_seconds,0) > 0 THEN 'Delayed'
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
                    AND st.status IN ('Completed','Cancelled','Delayed')
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
                0                       AS start_delay_seconds,
                0                       AS end_delay_seconds,
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
              WHERE t.timetable_id=:tt
                AND t.operator_type='SLTB'
                AND t.day_of_week=:dow";
        $st = $this->pdo->prepare($q);
          $st->execute([':tt' => $timetableId, ':dow' => (int)date('w')]);
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
                    AND trip_date=CURDATE() AND status IN ('Completed','Delayed')"
        )->fetchColumn();
        $turn = $cnt + 1;
          $startDelaySeconds = $this->delaySeconds((string)($t['departure_time'] ?? ''));
          $startStatus = $startDelaySeconds > 0 ? 'Delayed' : 'InProgress';

        $ins = "INSERT INTO sltb_trips
                  (timetable_id, bus_reg_no, trip_date,
                   scheduled_departure_time, scheduled_arrival_time,
                   route_id, sltb_driver_id, sltb_conductor_id, sltb_depot_id,
                         turn_no, departure_time, status, start_delay_seconds, end_delay_seconds)
                VALUES
                  (:tt, :bus, CURDATE(),
                   :sdep, :sarr,
                   :rid, :drv, :con, :depot,
                         :turn, CURRENT_TIME(), :status, :start_delay, 0)
                ON DUPLICATE KEY UPDATE
                         status=VALUES(status),
                   departure_time=VALUES(departure_time),
                         start_delay_seconds=VALUES(start_delay_seconds),
                         end_delay_seconds=0,
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
            ':status' => $startStatus,
            ':start_delay' => $startDelaySeconds,
        ]);

        return ['ok' => $ok, 'turn' => $turn, 'status' => $startStatus, 'start_delay_seconds' => $startDelaySeconds];
    }

    public function arrive(int $tripId): array
    {
        $stopsExpr = $this->routeStopsExpr('r');
        $trip = $this->pdo->prepare(
            "SELECT st.sltb_trip_id, st.status, st.scheduled_arrival_time,
                    COALESCE(st.start_delay_seconds,0) AS start_delay_seconds,
                    {$stopsExpr} AS stops_json
             FROM sltb_trips st
             LEFT JOIN routes r ON r.route_id = st.route_id
             WHERE st.sltb_trip_id=:id AND st.trip_date=CURDATE()"
        );
        $trip->execute([':id' => $tripId]);
        $t = $trip->fetch(PDO::FETCH_ASSOC);
        if (!$t) {
            return ['ok' => false, 'msg' => 'no_trip'];
        }
        if (!in_array((string)$t['status'], ['InProgress', 'Delayed'], true)) {
            return ['ok' => false, 'msg' => 'not_running'];
        }
        if (!$this->routeContainsLocation((string)($t['stops_json'] ?? '[]'))) {
            return ['ok' => false, 'msg' => 'not_authorized'];
        }

        $endDelaySeconds = $this->delaySeconds((string)($t['scheduled_arrival_time'] ?? ''));
        $hasDelay = ((int)($t['start_delay_seconds'] ?? 0) > 0) || ($endDelaySeconds > 0) || ((string)$t['status'] === 'Delayed');
        $finalStatus = $hasDelay ? 'Delayed' : 'Completed';

        $uid = (int)($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0);
        $upd = $this->pdo->prepare(
            "UPDATE sltb_trips
             SET status=:status, arrival_time=CURRENT_TIME(), end_delay_seconds=:end_delay, completed_by=:user
             WHERE sltb_trip_id=:id AND status IN ('InProgress','Delayed')"
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
        if (!in_array((string)$t['status'], ['InProgress', 'Delayed'], true)) {
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
             WHERE sltb_trip_id=:id AND status IN ('InProgress','Delayed')"
        );
        $upd->execute([':user' => $uid ?: null, ':reason' => $reasonText, ':id' => $tripId]);
        $ok = $upd->rowCount() > 0;

        if ($ok) {
            $this->notifyDepotEmergency((int)($t['sltb_depot_id'] ?? 0), $tripId, $t, $reasonText);
        }
        return ['ok' => $ok, 'msg' => $ok ? null : 'update_failed'];
    }
}
