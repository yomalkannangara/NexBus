<?php
/**
 * Seed sltb_trips for Depot 1 — generates realistic driver performance data.
 * Run once: C:\xampp\php\php.exe -f scripts\seed_trips.php
 */
require __DIR__ . '/../bootstrap/autoload.php';
$cfg = require __DIR__ . '/../config/database.php';
$pdo = new PDO(
    "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset={$cfg['charset']}",
    $cfg['username'], $cfg['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ── Configuration ─────────────────────────────────────────────────────────────
$depotId    = 1;
$maxId      = (int)$pdo->query("SELECT MAX(sltb_trip_id) FROM sltb_trips")->fetchColumn();

$drivers    = [1, 2, 8, 11, 1001, 1002, 2011, 2012, 2013, 2014, 2015, 2016, 2017, 2018, 2019, 2020, 2021, 2022];
$conductors = [1, 2, 8, 11, 2001, 3011, 3012, 3013, 3014, 3015, 3016, 3017];
$buses      = ['NB-001','NB-002','NB-003','NB-004','NB-005','NB-006','NB-007','NB-008','NB-009','NB-010',
               'NB-1002','NA-2024','NA-2025','NA-2030','NA-2420','NA-2581','NA-2589','NA-2592'];
$routeIds   = [1, 2, 3, 4, 5];
$ttIds      = [2, 5, 6, 7];   // valid SLTB timetable IDs

// Departure time slots (scheduled)
$slots      = ['05:30','06:00','06:30','07:00','07:30','08:00','08:30','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00'];

/**
 * Per-driver behavioural profile:
 *   delay_pct   → % of trips that depart late
 *   delay_max   → max minutes late
 *   cancel_pct  → % of trips cancelled
 * Remaining trips → Completed on-time
 */
$profiles = [
    1    => ['delay_pct'=>10,'delay_max'=>8, 'cancel_pct'=>2],   // Sunimal   → A
    2    => ['delay_pct'=>30,'delay_max'=>20,'cancel_pct'=>5],   // Hemantha  → B
    8    => ['delay_pct'=>20,'delay_max'=>15,'cancel_pct'=>3],   // Manjula   → B
    11   => ['delay_pct'=>15,'delay_max'=>10,'cancel_pct'=>2],   // Nimal     → A/B
    1001 => ['delay_pct'=>5, 'delay_max'=>6, 'cancel_pct'=>1],  // Kamal     → A
    1002 => ['delay_pct'=>45,'delay_max'=>25,'cancel_pct'=>12],  // gayashan  → C
    2011 => ['delay_pct'=>8, 'delay_max'=>7, 'cancel_pct'=>2],  // Dilshan   → A
    2012 => ['delay_pct'=>22,'delay_max'=>18,'cancel_pct'=>4],  // Lasitha   → B
    2013 => ['delay_pct'=>35,'delay_max'=>22,'cancel_pct'=>15], // Ruwan     → C
    2014 => ['delay_pct'=>20,'delay_max'=>12,'cancel_pct'=>4],  // Nalinda   → B
    2015 => ['delay_pct'=>6, 'delay_max'=>5, 'cancel_pct'=>1],  // Saman     → A
    2016 => ['delay_pct'=>50,'delay_max'=>30,'cancel_pct'=>8],  // Tharaka   → C
    2017 => ['delay_pct'=>18,'delay_max'=>14,'cancel_pct'=>3],  // Prasad    → B
    2018 => ['delay_pct'=>40,'delay_max'=>20,'cancel_pct'=>10], // Ishara    → C
    2019 => ['delay_pct'=>5, 'delay_max'=>4, 'cancel_pct'=>1],  // Kanishka  → A
    2020 => ['delay_pct'=>60,'delay_max'=>35,'cancel_pct'=>18], // Supun     → D
    2021 => ['delay_pct'=>14,'delay_max'=>10,'cancel_pct'=>3],  // Asitha    → B
    2022 => ['delay_pct'=>25,'delay_max'=>16,'cancel_pct'=>5],  // Dhanushka → B
];

// ── Date periods to generate trips for ───────────────────────────────────────
// Recent 30 days (default report range): ~8 trips per driver
// Historical (Oct 2025 – Mar 2026): ~4 trips per driver per month
$periods = [];

// Last 30 days: March 16 – April 15, 2026 — 8 trips per driver
$start = strtotime('2026-03-16');
$end   = strtotime('2026-04-15');
foreach ($drivers as $dId) {
    for ($i = 0; $i < 9; $i++) {
        $ts = $start + mt_rand(0, $end - $start);
        $periods[] = ['driver' => $dId, 'date' => date('Y-m-d', $ts)];
    }
}

// Historical months Oct 2025 – Mar 2026 — 3 trips per driver per month
$historicalMonths = [
    ['2025-10-01','2025-10-31'],
    ['2025-11-01','2025-11-30'],
    ['2025-12-01','2025-12-31'],
    ['2026-01-01','2026-01-31'],
    ['2026-02-01','2026-02-28'],
    ['2026-03-01','2026-03-15'],
];
foreach ($historicalMonths as [$ms, $me]) {
    $mStart = strtotime($ms);
    $mEnd   = strtotime($me);
    foreach ($drivers as $dId) {
        for ($i = 0; $i < 3; $i++) {
            $ts = $mStart + mt_rand(0, $mEnd - $mStart);
            $periods[] = ['driver' => $dId, 'date' => date('Y-m-d', $ts)];
        }
    }
}

// ── Generate and insert ───────────────────────────────────────────────────────
$sql = "INSERT INTO sltb_trips
    (sltb_trip_id, timetable_id, bus_reg_no, trip_date,
     scheduled_departure_time, scheduled_arrival_time,
     route_id, sltb_driver_id, sltb_conductor_id, sltb_depot_id,
     turn_no, departure_time, arrival_time, arrival_depot_id,
     status, completed_by)
    VALUES (?,?,?,?, ?,?, ?,?,?,?, ?,?,?,?, ?,?)
    ON DUPLICATE KEY UPDATE sltb_trip_id=sltb_trip_id";

$stmt   = $pdo->prepare($sql);
$nextId = $maxId + 1;
$inserted = 0;
$skipped  = 0;

foreach ($periods as $p) {
    $dId  = $p['driver'];
    $date = $p['date'];
    $prof = $profiles[$dId];

    $slotIdx  = array_rand($slots);
    $schedDep = $slots[$slotIdx];
    // Arrival ~75 min later
    $schedArr = date('H:i:s', strtotime("$date $schedDep") + 75 * 60);
    $schedDep .= ':00';

    $busIdx      = ($dId % count($buses));
    $busReg      = $buses[$busIdx];
    $conductorId = $conductors[array_rand($conductors)];
    $routeId     = $routeIds[array_rand($routeIds)];
    $ttId        = $ttIds[array_rand($ttIds)];
    $turn        = (mt_rand(0,1) ? 1 : 2);

    $roll        = mt_rand(1, 100);
    $isCancelled = $roll <= $prof['cancel_pct'];
    $isDelayed   = !$isCancelled && $roll <= ($prof['cancel_pct'] + $prof['delay_pct']);

    if ($isCancelled) {
        $status       = 'Cancelled';
        $depTime      = null;
        $arrTime      = null;
        $completedBy  = null;
    } else {
        $delayMin  = $isDelayed ? mt_rand(2, max(5, $prof['delay_max'])) : 0;
        $depTs     = strtotime("$date $schedDep") + $delayMin * 60;
        $arrTs     = $depTs + 75 * 60 + mt_rand(-5, 10) * 60;
        $depTime   = date('H:i:s', $depTs);
        $arrTime   = date('H:i:s', $arrTs);
        $status    = 'Completed';
        $completedBy = $conductorId;
    }

    try {
        $stmt->execute([
            $nextId, $ttId, $busReg, $date,
            $schedDep, $schedArr,
            $routeId, $dId, $conductorId, $depotId,
            $turn, $depTime, $arrTime, $depotId,
            $status, $completedBy,
        ]);
        $nextId++;
        $inserted++;
    } catch (\Throwable $e) {
        echo "SKIP row: " . $e->getMessage() . "\n";
        $skipped++;
    }
}

echo "Done. Inserted: $inserted, Skipped: $skipped\n";
echo "Total trips in DB: " . $pdo->query("SELECT COUNT(*) FROM sltb_trips WHERE sltb_depot_id=$depotId")->fetchColumn() . " for depot $depotId\n";
