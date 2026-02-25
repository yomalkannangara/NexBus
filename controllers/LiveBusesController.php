<?php
namespace App\controllers;

use PDO;

/**
 * Proxy controller for the external live-bus API.
 * Enriches each bus with DB metadata (operator, depot, owner).
 * Exposes a /api/buses/missing-sql endpoint for unregistered buses.
 */
class LiveBusesController
{
    private const EXTERNAL_URL = 'http://140.245.9.34/api/buses/live';
    private const CACHE_TTL    = 10;
    private const CACHE_FILE   = __DIR__ . '/../logs/live_buses_cache.json';

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
        $ph     = implode(',', array_fill(0, count($regNos), '?'));
        $stmt   = $pdo->prepare(
            "SELECT bus_reg_no FROM tracking_monitoring
             WHERE bus_reg_no IN ($ph)
               AND snapshot_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
             GROUP BY bus_reg_no"
        );
        $stmt->execute($regNos);
        $recentlyInserted = array_flip(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'bus_reg_no'));

        // 5. Insert one snapshot per bus not seen in the last minute
        $ins = $pdo->prepare(
            "INSERT INTO tracking_monitoring
               (operator_type, bus_reg_no, snapshot_at,
                lat, lng, speed, heading,
                route_id, operational_status, speed_violations, avg_delay_min)
             VALUES
               (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        foreach ($buses as $b) {
            if (isset($recentlyInserted[$b['busId']])) continue;

            $info    = $lookup[$b['busId']] ?? [];
            $opType  = ($info['operatorType'] ?? '') === 'Private' ? 'Private' : 'SLTB';
            $speed   = (float)($b['speedKmh'] ?? 0);
            $routeId = $routeMap[(int)($b['routeNo'] ?? 0)] ?? null;
            $status  = $speed > 60 ? 'Delayed' : 'OnTime';
            $viols   = $speed > 60 ? 1 : 0;

            $ins->execute([
                $opType,
                $b['busId'],
                isset($b['lat'])  ? (float)$b['lat']  : null,
                isset($b['lng'])  ? (float)$b['lng']  : null,
                $speed,
                isset($b['heading']) ? (int)$b['heading'] : null,
                $routeId,
                $status,
                $viols,
                0, // avg_delay_min starts at 0 for live
            ]);
        }

        return $lookup;
    }

    /* ─── main endpoint: /api/buses/live ────────────────────────── */
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

    /* ─── diagnostic: /api/buses/missing-sql ───────────────────── */
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
