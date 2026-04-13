<?php
namespace App\controllers;

use PDO;

/**
 * Proxy controller for the external live-bus API.
 * Enriches each bus with DB metadata (operator, depot, owner).
 * Exposes a /live/buses/missing-sql endpoint for unregistered buses.
 */
class LiveBusesController
{
    private const EXTERNAL_URL = 'http://140.245.9.34:4000/api/buses/lives';
    private const CACHE_TTL    = 10;
    private const CACHE_FILE   = __DIR__ . '/../logs/live_buses_cache.json';
    private const ZERO_SPEED_THRESHOLD = 0.1;

    /* ─── fetch raw live data (with file cache) ─────────────────── */
    private function fetchRaw(): array
    {
        if (is_file(self::CACHE_FILE) &&
            (time() - filemtime(self::CACHE_FILE)) < self::CACHE_TTL) {
            $cached = json_decode(file_get_contents(self::CACHE_FILE), true);
            if (is_array($cached)) return $cached;
        }

        $ctx = stream_context_create([
            'http' => ['timeout' => 5, 'ignore_errors' => true, 'method' => 'GET'],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $raw = @file_get_contents(self::EXTERNAL_URL, false, $ctx);

        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                file_put_contents(self::CACHE_FILE, $raw);
                return $decoded;
            }
        }

        if (is_file(self::CACHE_FILE)) {
            return json_decode(file_get_contents(self::CACHE_FILE), true) ?: [];
        }
        return [];
    }

    /* ─── build a lookup map: reg_no → {operatorType,depot,depotId,owner,ownerId} ── */
    private function buildDbLookup(array $regNos): array
    {
        if (empty($regNos) || !isset($GLOBALS['db'])) return [];

        $pdo = $GLOBALS['db'];
        $ph  = implode(',', array_fill(0, count($regNos), '?'));

        // SLTB buses (LEFT JOIN so buses still resolve even if depot master row is missing)
        $stmt = $pdo->prepare(
            "SELECT sb.reg_no,
                    sb.sltb_depot_id AS depot_id,
                    sd.name AS depot
             FROM sltb_buses sb
             LEFT JOIN sltb_depots sd ON sd.sltb_depot_id = sb.sltb_depot_id
             WHERE sb.reg_no IN ($ph)"
        );
        $stmt->execute($regNos);
        $sltb = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Private buses (LEFT JOIN so buses still resolve even if owner master row is missing)
        $stmt2 = $pdo->prepare(
            "SELECT pb.reg_no,
                    pb.private_operator_id AS owner_id,
                    pbo.name AS owner
             FROM private_buses pb
             LEFT JOIN private_bus_owners pbo ON pbo.private_operator_id = pb.private_operator_id
             WHERE pb.reg_no IN ($ph)"
        );
        $stmt2->execute($regNos);
        $priv = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($sltb as $r) {
            $depotId = (int)($r['depot_id'] ?? 0);
            $map[$r['reg_no']] = [
                'operatorType' => 'SLTB',
                'depot'        => $r['depot'] ?: ($depotId > 0 ? ('Depot #' . $depotId) : 'SLTB Depot'),
                'depotId'      => $depotId ?: null,
                'owner'        => null,
                'ownerId'      => null,
                'inDb'         => true,
            ];
        }
        foreach ($priv as $r) {
            $ownerId = (int)($r['owner_id'] ?? 0);
            $map[$r['reg_no']] = [
                'operatorType' => 'Private',
                'depot'        => null,
                'depotId'      => null,
                'owner'        => $r['owner'] ?: ($ownerId > 0 ? ('Owner #' . $ownerId) : 'Private Owner'),
                'ownerId'      => $ownerId ?: null,
                'inDb'         => true,
            ];
        }
        return $map;
    }

    /* ─── build routeNoInt → route_id map, auto-creating unknowns ── */
    private function buildRouteMap(array $buses): array
    {
        if (!isset($GLOBALS['db'])) return [];
        $pdo = $GLOBALS['db'];

        // Collect distinct integer route numbers from the live payload
        // API sends "002" (string) or 100 (int); normalise to int
        $intRoutes = array_values(array_unique(array_filter(
            array_map(fn($b) => (int)($b['routeNo'] ?? 0), $buses)
        )));
        if (empty($intRoutes)) return [];

        // Query existing routes
        $ph   = implode(',', array_fill(0, count($intRoutes), '?'));
        $stmt = $pdo->prepare(
            "SELECT MIN(route_id) AS route_id, CAST(route_no AS UNSIGNED) AS rno_int
             FROM routes
             WHERE CAST(route_no AS UNSIGNED) IN ($ph)
             GROUP BY rno_int"
        );
        $stmt->execute($intRoutes);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $map[(int)$r['rno_int']] = (int)$r['route_id'];
        }

        // Auto-create any routes not yet in the DB
        $insRoute = $pdo->prepare(
            "INSERT IGNORE INTO routes (route_no, is_active, stops_json) VALUES (?, 1, '[]')"
        );
        $missing = array_diff($intRoutes, array_keys($map));
        foreach ($missing as $rno) {
            $insRoute->execute([(string)$rno]);
        }

        // If we created any, re-fetch to get their new route_ids
        if (!empty($missing)) {
            $stmt->execute($intRoutes);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $map[(int)$r['rno_int']] = (int)$r['route_id'];
            }
        }

        return $map;
    }

    /* ─── latest snapshot per bus (for continuous wait-time calculation) ── */
    private function loadLatestSnapshots(array $regNos): array
    {
        if (empty($regNos) || !isset($GLOBALS['db'])) return [];

        $pdo = $GLOBALS['db'];
        $ph = implode(',', array_fill(0, count($regNos), '?'));

        $stmt = $pdo->prepare(
            "SELECT
                 tm.bus_reg_no,
                 tm.snapshot_at,
                 tm.speed,
                 COALESCE(tm.avg_delay_min, 0) AS wait_minutes
             FROM tracking_monitoring tm
             INNER JOIN (
                 SELECT bus_reg_no, MAX(snapshot_at) AS max_snap
                 FROM tracking_monitoring
                 WHERE bus_reg_no IN ($ph)
                 GROUP BY bus_reg_no
             ) latest
                 ON latest.bus_reg_no = tm.bus_reg_no
                AND latest.max_snap = tm.snapshot_at"
        );
        $stmt->execute($regNos);

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[$row['bus_reg_no']] = $row;
        }
        return $map;
    }

    /* ─── delayed buses are sourced from trip status, not tracker speed ── */
    private function loadDelayedTripBusSet(): array
    {
        if (!isset($GLOBALS['db'])) return [];

        try {
            $stmt = $GLOBALS['db']->query(
                "SELECT DISTINCT x.bus_reg_no
                 FROM (
                     SELECT bus_reg_no, trip_date, status FROM sltb_trips
                     UNION
                     SELECT bus_reg_no, trip_date, status FROM private_trips
                 ) x
                 WHERE LOWER(COALESCE(x.status, '')) = 'delayed'
                   AND (x.trip_date = CURDATE() OR x.trip_date IS NULL)"
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return array_flip(array_column($rows, 'bus_reg_no'));
        } catch (\Throwable $e) {
            return [];
        }
    }

    /* ─── auto-register unknown buses + write tracking snapshots ── */
    private function persistLiveBuses(array $buses, array $lookup): array
    {
        if (!isset($GLOBALS['db'])) return $lookup;
        $pdo = $GLOBALS['db'];

        // 1. Auto-register unknown buses into sltb_buses (Colombo depot, capacity 54)
        foreach ($buses as $b) {
            if (!($lookup[$b['busId']]['inDb'] ?? false)) {
                $pdo->prepare(
                    "INSERT IGNORE INTO sltb_buses (reg_no, sltb_depot_id, capacity, status)
                     VALUES (?, 1, 54, 'Active')"
                )->execute([$b['busId']]);
            }
        }

        // 2. Re-query DB for all buses (covers newly registered ones)
        $lookup = $this->buildDbLookup(
            array_values(array_unique(array_column($buses, 'busId')))
        );

        // 3. Build routeNo→route_id map (auto-creates missing routes in DB)
        $routeMap = $this->buildRouteMap($buses);

        // 4. Find buses that already have a snapshot within the last minute (throttle)
        $regNos = array_values(array_unique(array_column($buses, 'busId')));
        if (empty($regNos)) return $lookup;

        $ph     = implode(',', array_fill(0, count($regNos), '?'));
        $stmt   = $pdo->prepare(
            "SELECT bus_reg_no FROM tracking_monitoring
             WHERE bus_reg_no IN ($ph)
               AND snapshot_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
             GROUP BY bus_reg_no"
        );
        $stmt->execute($regNos);
        $recentlyInserted = array_flip(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'bus_reg_no'));

        // 5. Preload data needed for metric derivation.
        $latestSnapshots = $this->loadLatestSnapshots($regNos);
        $delayedBusSet = $this->loadDelayedTripBusSet();

        // 6. Insert one snapshot per bus not seen in the last minute
        $ins = $pdo->prepare(
            "INSERT INTO tracking_monitoring
               (operator_type, bus_reg_no, snapshot_at,
                lat, lng, speed, heading,
                route_id, operational_status, speed_violations, avg_delay_min)
             VALUES
               (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $snapshotAt = date('Y-m-d H:i:s');
        $snapshotTs = strtotime($snapshotAt) ?: time();

        foreach ($buses as $b) {
            $busId = (string)($b['busId'] ?? '');
            if ($busId === '' || isset($recentlyInserted[$busId])) continue;

            $info    = $lookup[$busId] ?? [];
            $opType  = ($info['operatorType'] ?? '') === 'Private' ? 'Private' : 'SLTB';
            $speed   = (float)($b['speedKmh'] ?? 0);
            $routeId = $routeMap[(int)($b['routeNo'] ?? 0)] ?? null;

            $waitMinutes = 0.0;
            if ($speed <= self::ZERO_SPEED_THRESHOLD) {
                $prev = $latestSnapshots[$busId] ?? null;
                if ($prev) {
                    $prevTs = strtotime((string)($prev['snapshot_at'] ?? ''));
                    $elapsedMinutes = $prevTs ? max(0, ($snapshotTs - $prevTs) / 60) : 0.0;
                    $prevSpeed = (float)($prev['speed'] ?? 0);
                    $prevWait = (float)($prev['wait_minutes'] ?? 0);
                    $waitMinutes = $prevSpeed <= self::ZERO_SPEED_THRESHOLD
                        ? ($prevWait + $elapsedMinutes)
                        : $elapsedMinutes;
                }
            }
            $waitMinutes = round($waitMinutes, 2);

            $status  = isset($delayedBusSet[$busId]) ? 'Delayed' : 'OnTime';
            $viols   = $speed > 60 ? 1 : 0;

            $ins->execute([
                $opType,
                $busId,
                $snapshotAt,
                isset($b['lat'])  ? (float)$b['lat']  : null,
                isset($b['lng'])  ? (float)$b['lng']  : null,
                $speed,
                isset($b['heading']) ? (int)$b['heading'] : null,
                $routeId,
                $status,
                $viols,
                $waitMinutes,
            ]);
        }

        return $lookup;
    }

    /* ─── main endpoint: /live/buses/pull ───────────────────────── */
    public function proxy(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        $buses = $this->fetchRaw();
        if (empty($buses)) { echo '[]'; return; }

        $regNos = array_values(array_unique(array_column($buses, 'busId')));
        $lookup = $this->buildDbLookup($regNos);

        // Auto-register unknowns and write tracking_monitoring snapshots
        $lookup = $this->persistLiveBuses($buses, $lookup);

        $enriched = array_map(function ($b) use ($lookup) {
            // Use DB lookup; if not found fall back to whatever the raw API already tells us
            $rawOpType = $b['operatorType'] ?? 'SLTB';
            $info = $lookup[$b['busId']] ?? [
                'operatorType' => $rawOpType,
                'depot'        => $rawOpType === 'SLTB'    ? 'Colombo Depot' : null,
                'depotId'      => $rawOpType === 'SLTB'    ? 1               : null,
                'owner'        => $rawOpType === 'Private' ? 'Unknown Owner'  : null,
                'ownerId'      => null,
                'inDb'         => false,   // genuinely not found in DB
            ];
            return array_merge($b, $info);
        }, $buses);

        echo json_encode($enriched, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    }

    /* ─── DB-read endpoint: /live/buses/db ─────────────────────
     * Reads the most-recent tracking_monitoring snapshot per bus
     * (written by proxy() / a cron that calls /live/buses/pull).
     * Returns the exact same JSON shape as proxy() so the frontend
     * needs zero changes other than the URL it fetches.
     * Only includes buses with a snapshot in the last 5 minutes.
     * ─────────────────────────────────────────────────────────── */
    public function dbLive(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        if (!isset($GLOBALS['db'])) { echo '[]'; return; }
        $pdo = $GLOBALS['db'];

        try {
            $stmt = $pdo->query(
                "SELECT
                     tm.bus_reg_no                   AS busId,
                     tm.operator_type                AS operatorType,
                     ROUND(tm.speed, 1)              AS speedKmh,
                     tm.lat,
                     tm.lng,
                     tm.heading,
                     tm.operational_status           AS operationalStatus,
                     tm.snapshot_at                  AS snapshotAt,
                     r.route_no                      AS routeNo,
                     sd.name                         AS depot,
                     sb.sltb_depot_id                AS depotId,
                     pbo.name                        AS owner,
                     pb.private_operator_id          AS ownerId
                 FROM tracking_monitoring tm
                 /* Latest snapshot per bus within the last 5 minutes */
                 INNER JOIN (
                     SELECT bus_reg_no, MAX(snapshot_at) AS max_snap
                     FROM   tracking_monitoring
                     WHERE  snapshot_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                     GROUP  BY bus_reg_no
                 ) latest
                     ON  latest.bus_reg_no = tm.bus_reg_no
                     AND latest.max_snap   = tm.snapshot_at
                 LEFT JOIN routes              r   ON  r.route_id           = tm.route_id
                 LEFT JOIN sltb_buses          sb  ON  sb.reg_no            = tm.bus_reg_no
                 LEFT JOIN sltb_depots         sd  ON  sd.sltb_depot_id     = sb.sltb_depot_id
                 LEFT JOIN private_buses       pb  ON  pb.reg_no            = tm.bus_reg_no
                 LEFT JOIN private_bus_owners  pbo ON  pbo.private_operator_id = pb.private_operator_id
                 ORDER BY tm.snapshot_at DESC"
            );

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('[dbLive] query error: ' . $e->getMessage());
            echo '[]'; return;
        }

        if (empty($rows)) { echo '[]'; return; }

        $out = array_map(static function (array $r): array {
            $opType = $r['operatorType'] ?? 'SLTB';
            return [
                'busId'             => $r['busId'],
                'routeNo'           => $r['routeNo'] ?? '',
                'speedKmh'          => (float)($r['speedKmh'] ?? 0),
                'operatorType'      => $opType,
                'depot'             => $opType === 'SLTB'
                                         ? ($r['depot'] ?: ($r['depotId'] ? 'Depot #' . $r['depotId'] : 'SLTB Depot'))
                                         : null,
                'depotId'           => $r['depotId'] !== null ? (int)$r['depotId'] : null,
                'owner'             => $opType === 'Private'
                                         ? ($r['owner'] ?: ($r['ownerId'] ? 'Owner #' . $r['ownerId'] : 'Private Owner'))
                                         : null,
                'ownerId'           => $r['ownerId'] !== null ? (int)$r['ownerId'] : null,
                'lat'               => $r['lat']     !== null ? (float)$r['lat']     : null,
                'lng'               => $r['lng']     !== null ? (float)$r['lng']     : null,
                'heading'           => $r['heading'] !== null ? (int)$r['heading']   : null,
                'operationalStatus' => $r['operationalStatus'] ?? 'OnTime',
                'snapshotAt'        => $r['snapshotAt'],
                // legacy field used by some pages that previously hit the proxy payload
                'updatedAt'         => $r['snapshotAt'],
                'inDb'              => true,
            ];
        }, $rows);

        echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    }

    /* ─── diagnostic: /live/buses/missing-sql ─────────────────── */
    public function missingSql(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');

        $buses  = $this->fetchRaw();
        if (empty($buses)) { echo '-- No live buses fetched.'; return; }

        $regNos = array_values(array_unique(array_column($buses, 'busId')));
        $lookup = $this->buildDbLookup($regNos);

        $missing = array_filter($buses, fn($b) => !($lookup[$b['busId']]['inDb'] ?? false));
        if (empty($missing)) {
            echo '-- All live buses are already registered in the database. No INSERT needed.';
            return;
        }

        $lines = [
            "-- ============================================================",
            "-- Missing live buses — generated " . date('Y-m-d H:i:s'),
            "-- Add to: private_buses (if private operator) or sltb_buses (if SLTB)",
            "-- REVIEW operator type, depot/owner, capacity before running!",
            "-- ============================================================",
            "",
            "-- Unmatched bus IDs from live API:",
        ];
        foreach (array_unique(array_column($missing, 'busId')) as $id) {
            $lines[] = "-- Bus ID: " . htmlspecialchars_decode($id);
        }
        $lines[] = "";
        $lines[] = "-- Option A: add as SLTB bus (change sltb_depot_id as needed)";
        $lines[] = "-- sltb_depot_id 1=Colombo, 2=Kandy, 3=Galle";
        foreach (array_unique(array_column($missing, 'busId')) as $reg) {
            $safe = addslashes($reg);
            $lines[] = "INSERT IGNORE INTO sltb_buses (reg_no, sltb_depot_id, status) VALUES ('$safe', 1, 'Active');";
        }
        $lines[] = "";
        $lines[] = "-- Option B: add as Private bus (change private_operator_id as needed)";
        $lines[] = "-- private_operator_id 1=Prime Transport, 2=CityExpress, 3=Sunrise Travels";
        foreach (array_unique(array_column($missing, 'busId')) as $reg) {
            $safe = addslashes($reg);
            $lines[] = "INSERT IGNORE INTO private_buses (reg_no, private_operator_id, status) VALUES ('$safe', 1, 'Active');";
        }

        // Also save to file
        $sqlContent = implode("\n", $lines);
        $outFile = __DIR__ . '/../database/migrations/missing_live_buses_' . date('Ymd_His') . '.sql';
        @file_put_contents($outFile, $sqlContent);

        echo $sqlContent;
    }
}
