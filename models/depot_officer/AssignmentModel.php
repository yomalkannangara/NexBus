<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;
use PDO;

class AssignmentModel extends BaseModel
{
    private array $columnCache = [];

    private function tableExists(string $table): bool {
        $key = 'table:' . $table;
        if (array_key_exists($key, $this->columnCache)) return $this->columnCache[$key];
        $st = $this->pdo->prepare("SELECT COUNT(*) AS c FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
        $st->execute([$table]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $exists = !empty($row) && ((int)($row['c'] ?? 0) > 0);
        $this->columnCache[$key] = $exists;
        return $exists;
    }
    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }

    private function fetchTimetableDeparture(int $timetableId): ?string
    {
        if ($timetableId <= 0) {
            return null;
        }

        $st = $this->pdo->prepare("SELECT departure_time FROM timetables WHERE timetable_id=? LIMIT 1");
        $st->execute([$timetableId]);
        $value = $st->fetchColumn();
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return substr(trim($value), 0, 5);
    }

    private function normalizeStoredShift(?string $value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $value)) {
            return substr($value, 0, 5);
        }

        return $value;
    }

    private function legacyShiftLabel(string $value): string
    {
        $value = $this->normalizeStoredShift($value);
        if ($value === '') {
            return '';
        }

        if (in_array($value, ['Morning', 'Evening', 'Night'], true)) {
            return $value;
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
            return '';
        }

        $hour = (int)substr($value, 0, 2);
        if ($hour < 12) {
            return 'Morning';
        }
        if ($hour < 17) {
            return 'Evening';
        }
        return 'Night';
    }

    private function resolveShiftValue(?string $shift, ?int $timetableId = null): string
    {
        $fromTimetable = $timetableId ? $this->fetchTimetableDeparture($timetableId) : null;
        if ($fromTimetable !== null) {
            return $fromTimetable;
        }

        return $this->normalizeStoredShift($shift);
    }

    private function turnCandidates(string $shift): array
    {
        $shift = $this->normalizeStoredShift($shift);
        if ($shift === '') {
            return [];
        }

        $candidates = [$shift];
        $legacy = $this->legacyShiftLabel($shift);
        if ($legacy !== '' && !in_array($legacy, $candidates, true)) {
            $candidates[] = $legacy;
        }

        return $candidates;
    }

    private function turnMatchSql(string $alias, string $shift, ?int $timetableId, array &$params): string
    {
        $clauses = [];
        $hasTimetableId = $this->columnExists('sltb_assignments', 'timetable_id');

        if ($hasTimetableId && $timetableId && $timetableId > 0) {
            $clauses[] = "{$alias}.timetable_id = ?";
            $params[] = $timetableId;
        }

        $candidates = $this->turnCandidates($shift);
        if (!empty($candidates)) {
            $placeholders = implode(',', array_fill(0, count($candidates), '?'));
            if ($hasTimetableId && $timetableId && $timetableId > 0) {
                $clauses[] = "({$alias}.timetable_id IS NULL AND {$alias}.shift IN ({$placeholders}))";
            } else {
                $clauses[] = "{$alias}.shift IN ({$placeholders})";
            }
            foreach ($candidates as $candidate) {
                $params[] = $candidate;
            }
        }

        return $clauses ? '(' . implode(' OR ', $clauses) . ')' : '1=0';
    }

    private function findBusTurnAssignment(
        int $depotId,
        string $bus,
        string $date,
        string $shift,
        ?int $timetableId = null,
        int $excludeAssignmentId = 0
    ): ?array {
        $params = [$depotId, $bus, $date];
        $turnParams = [];
        $turnSql = $this->turnMatchSql('a', $shift, $timetableId, $turnParams);
        $timetableSelect = $this->columnExists('sltb_assignments', 'timetable_id')
            ? 'a.timetable_id,'
            : 'NULL AS timetable_id,';

        $sql = "SELECT a.assignment_id,
                       a.bus_reg_no,
                       a.shift,
                       {$timetableSelect}
                       a.sltb_driver_id,
                       a.sltb_conductor_id
                FROM sltb_assignments a
                WHERE a.sltb_depot_id=?
                  AND a.bus_reg_no=?
                  AND a.assigned_date=?
                  AND {$turnSql}";

        $params = array_merge($params, $turnParams);
        if ($excludeAssignmentId > 0) {
            $sql .= ' AND a.assignment_id <> ?';
            $params[] = $excludeAssignmentId;
        }
        $sql .= ' ORDER BY a.assignment_id DESC LIMIT 1';

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function findStaffConflictBus(
        int $depotId,
        string $date,
        string $shift,
        ?int $timetableId,
        string $staffColumn,
        int $staffId,
        int $excludeAssignmentId = 0
    ): ?string {
        if ($staffId <= 0) {
            return null;
        }

        if (!in_array($staffColumn, ['sltb_driver_id', 'sltb_conductor_id'], true)) {
            return null;
        }

        $params = [$depotId, $date, $staffId];
        $turnParams = [];
        $turnSql = $this->turnMatchSql('a', $shift, $timetableId, $turnParams);

        $sql = "SELECT a.bus_reg_no
                FROM sltb_assignments a
                WHERE a.sltb_depot_id=?
                  AND a.assigned_date=?
                  AND a.{$staffColumn}=?
                  AND {$turnSql}";

        $params = array_merge($params, $turnParams);
        if ($excludeAssignmentId > 0) {
            $sql .= ' AND a.assignment_id <> ?';
            $params[] = $excludeAssignmentId;
        }
        $sql .= ' ORDER BY a.assignment_id DESC LIMIT 1';

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $value = $st->fetchColumn();
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /** Grid for today's rows (capacity + latest location) */
public function allToday(int $depotId): array {
    $hasTimetableId = $this->columnExists('sltb_assignments', 'timetable_id');
    $timetableSelect = $hasTimetableId
        ? "a.timetable_id,"
        : "NULL AS timetable_id,";
    $pickTurnExpr = $hasTimetableId
        ? "COALESCE(CAST(timetable_id AS CHAR), shift)"
        : "shift";
    $exactTimetableJoin = $hasTimetableId
        ? "LEFT JOIN timetables tt_exact ON tt_exact.timetable_id = a.timetable_id"
        : '';
    $departureSelect = $hasTimetableId
        ? "COALESCE(tt_exact.departure_time, tt.departure_time) AS departure_time,"
        : "tt.departure_time,";
    $routeJoin = $hasTimetableId
        ? "LEFT JOIN routes r ON r.route_id = COALESCE(tt_exact.route_id, tt.route_id)"
        : "LEFT JOIN routes r ON r.route_id = tt.route_id";
    $orderShiftExpr = "CASE
                WHEN a.shift REGEXP '^[0-9]{2}:[0-9]{2}(:[0-9]{2})?$' THEN LEFT(a.shift, 5)
                WHEN a.shift = 'Morning' THEN '08:00'
                WHEN a.shift = 'Evening' THEN '14:00'
                ELSE '18:00'
            END";

    $sql = "SELECT 
                a.assignment_id,
                a.assigned_date,
                a.shift,
                {$timetableSelect}
                a.bus_reg_no,
                a.sltb_driver_id,
                a.sltb_conductor_id,
                b.status AS bus_status,
                COALESCE(b.capacity,0) AS capacity,
                d.full_name AS driver_name,
                c.full_name AS conductor_name,
                r.route_no,
                r.stops_json,
                {$departureSelect}
                tm.lat,
                tm.lng,
                tm.snapshot_at
            FROM sltb_assignments a
            /* --- ensure one row per BUS (latest assignment ever, up to today) --- */
            JOIN (
                SELECT MAX(assignment_id) AS assignment_id
                FROM sltb_assignments
                WHERE assigned_date <= CURDATE() AND sltb_depot_id = ?
                GROUP BY bus_reg_no, {$pickTurnExpr}
            ) pick ON pick.assignment_id = a.assignment_id

            /* bus must belong to this depot */
            JOIN sltb_buses b 
                  ON b.reg_no = a.bus_reg_no 
                 AND b.sltb_depot_id = ?

            LEFT JOIN sltb_drivers d    ON d.sltb_driver_id    = a.sltb_driver_id
            LEFT JOIN sltb_conductors c ON c.sltb_conductor_id = a.sltb_conductor_id
            {$exactTimetableJoin}

            /* --- pick ONE active timetable route per bus (prefer today's service) --- */
            LEFT JOIN (
                SELECT
                    t.bus_reg_no,
                    CAST(
                        SUBSTRING_INDEX(
                            GROUP_CONCAT(
                                t.route_id
                                ORDER BY
                                    (t.day_of_week = DAYOFWEEK(CURDATE())-1) DESC,
                                    t.effective_from DESC,
                                    t.departure_time ASC,
                                    t.timetable_id DESC
                                SEPARATOR ','
                            ),
                            ',',
                            1
                        ) AS UNSIGNED
                    ) AS route_id
                    ,
                    SUBSTRING_INDEX(
                        GROUP_CONCAT(
                            t.departure_time
                            ORDER BY
                                (t.day_of_week = DAYOFWEEK(CURDATE())-1) DESC,
                                t.effective_from DESC,
                                t.departure_time ASC,
                                t.timetable_id DESC
                            SEPARATOR ','
                        ),
                        ',',
                        1
                    ) AS departure_time
                FROM timetables t
                WHERE t.operator_type='SLTB'
                  AND t.effective_from <= CURDATE()
                  AND (t.effective_to IS NULL OR t.effective_to >= CURDATE())
                GROUP BY t.bus_reg_no
            ) tt ON tt.bus_reg_no = a.bus_reg_no

            {$routeJoin}

            /* latest tracking snapshot for today (single row) */
            LEFT JOIN tracking_monitoring tm ON tm.track_id = (
                 SELECT t2.track_id
                 FROM tracking_monitoring t2
                 WHERE t2.operator_type='SLTB'
                   AND t2.bus_reg_no = a.bus_reg_no
                   AND DATE(t2.snapshot_at) = CURDATE()
                 ORDER BY t2.snapshot_at DESC
                 LIMIT 1
            )

            ORDER BY {$orderShiftExpr}, a.bus_reg_no, a.assignment_id DESC";
    $st = $this->pdo->prepare($sql);
    $st->execute([$depotId, $depotId]);  // depotId used twice (pick + bus join)
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    // compute route start/end from stops_json (if available)
    foreach ($rows as &$row) {
        $row['route_name'] = $this->getRouteDisplayName($row['stops_json'] ?? '[]');
        $stops = json_decode($row['stops_json'] ?? '[]', true) ?: [];
        $first = $stops[0] ?? null;
        $last  = $stops[count($stops) - 1] ?? null;
        $start = is_array($first) ? ($first['stop'] ?? '') : $first;
        $end   = is_array($last) ? ($last['stop'] ?? '') : $last;
        $row['route_start'] = $start ?: '';
        $row['route_end']   = $end ?: '';
        $row['route_name']  = trim($start . ' → ' . $end);
        $row['route_display'] = trim(($row['route_no'] ?? '') . ' | ' . ($row['route_start'] ?: '-') . ' → ' . ($row['route_end'] ?: '-'));
    }
    return $rows;
}


    /** Dropdown data */
    public function buses(int $depotId): array {
                // Return active buses along with their primary route (if any)
                   $sql = "SELECT b.reg_no,
                               COALESCE(b.capacity, 0) AS capacity,
                               r.route_no,
                               r.stops_json
                           FROM sltb_buses b
                         JOIN (
                                 /* pick one active timetable route per bus (prefer today's service) */
                                 SELECT
                                     t.bus_reg_no,
                                     CAST(
                                         SUBSTRING_INDEX(
                                             GROUP_CONCAT(
                                                 t.route_id
                                                 ORDER BY
                                                     (t.day_of_week = DAYOFWEEK(CURDATE())-1) DESC,
                                                     t.effective_from DESC,
                                                     t.departure_time ASC,
                                                     t.timetable_id DESC
                                                 SEPARATOR ','
                                             ),
                                             ',',
                                             1
                                         ) AS UNSIGNED
                                     ) AS route_id
                                 FROM timetables t
                                 WHERE t.operator_type='SLTB'
                                   AND t.effective_from <= CURDATE()
                                   AND (t.effective_to IS NULL OR t.effective_to >= CURDATE())
                                 GROUP BY t.bus_reg_no
                         ) tt ON tt.bus_reg_no = b.reg_no
                         LEFT JOIN routes r ON r.route_id = tt.route_id
                                 WHERE b.sltb_depot_id = ? AND b.status='Active'
                            ORDER BY b.reg_no";
                $st = $this->pdo->prepare($sql);
                $st->execute([$depotId]);
                $rows = $st->fetchAll(PDO::FETCH_ASSOC);
                // compute route_display from stops_json
                foreach ($rows as &$r) {
                    $stops = json_decode($r['stops_json'] ?? '[]', true) ?: [];
                    $first = $stops[0] ?? null;
                    $last  = $stops[count($stops) - 1] ?? null;
                    $start = is_array($first) ? ($first['stop'] ?? '') : $first;
                    $end   = is_array($last) ? ($last['stop'] ?? '') : $last;
                    $r['route_name'] = trim($start . ' → ' . $end);
                    $r['route_start'] = $start ?: '';
                    $r['route_end']   = $end ?: '';
                    $r['capacity']    = (int)($r['capacity'] ?? 0);
                }
                return $rows;
    }
    public function drivers(int $depotId): array {
        $st = $this->pdo->prepare(
            "SELECT sltb_driver_id, full_name 
               FROM sltb_drivers 
              WHERE sltb_depot_id=? AND status='Active'
              ORDER BY full_name"
        );
        $st->execute([$depotId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
    public function conductors(int $depotId): array {
        $st = $this->pdo->prepare(
            "SELECT sltb_conductor_id, full_name 
               FROM sltb_conductors 
              WHERE sltb_depot_id=? AND status='Active'
              ORDER BY full_name"
        );
        $st->execute([$depotId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
    public function routes(int $depotId): array {
        $st = $this->pdo->prepare(
            "SELECT DISTINCT r.route_id, r.route_no, r.stops_json
               FROM routes r
               JOIN timetables tt ON tt.route_id = r.route_id AND tt.operator_type = 'SLTB'
               JOIN sltb_buses sb ON sb.reg_no = tt.bus_reg_no AND sb.sltb_depot_id = :depot
              WHERE r.is_active = 1
           ORDER BY r.route_no+0, r.route_no"
        );
        $st->execute([':depot' => $depotId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
        }

        return $rows;
    }

    /** Return timetable trips for a bus on a given date (SLTB, day-of-week aware). */
    public function shiftsForBus(string $busReg, string $date): array {
        $dow = (int)date('w', strtotime($date));
        $st = $this->pdo->prepare(
            "SELECT t.timetable_id, t.departure_time, t.arrival_time, r.route_no, r.stops_json
               FROM timetables t
               LEFT JOIN routes r ON r.route_id = t.route_id
              WHERE t.bus_reg_no = ? AND t.operator_type = 'SLTB' AND t.day_of_week = ?
                AND t.effective_from <= ? AND (t.effective_to IS NULL OR t.effective_to >= ?)
              ORDER BY t.departure_time"
        );
        $st->execute([$busReg, $dow, $date, $date]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $stops = json_decode($row['stops_json'] ?? '[]', true) ?: [];
            $first = $stops[0] ?? null; $last = $stops[count($stops)-1] ?? null;
            $start = is_array($first) ? ($first['stop'] ?? '') : (string)$first;
            $end   = is_array($last)  ? ($last['stop']  ?? '') : (string)$last;
            $row['route_display']   = trim(($row['route_no'] ?? '') . ' | ' . $start . ' to ' . $end);
            $row['departure_label'] = substr($row['departure_time'] ?? '', 0, 5);
            $row['arrival_label']   = substr($row['arrival_time']   ?? '', 0, 5);
        }
        return $rows;
    }

    private function normalizeRouteContext(?array $row): ?array
    {
        if (!$row) {
            return null;
        }

        $routeId = (int)($row['route_id'] ?? 0);
        $routeNo = trim((string)($row['route_no'] ?? ''));
        if ($routeId <= 0 && $routeNo === '') {
            return null;
        }

        return [
            'route_id' => $routeId,
            'route_no' => $routeNo,
        ];
    }

    public function routeContextForTimetable(int $timetableId): ?array
    {
        if ($timetableId <= 0) {
            return null;
        }

        try {
            $st = $this->pdo->prepare(
                "SELECT t.route_id, r.route_no
                   FROM timetables t
                   LEFT JOIN routes r ON r.route_id = t.route_id
                  WHERE t.timetable_id = ?
                  LIMIT 1"
            );
            $st->execute([$timetableId]);
            return $this->normalizeRouteContext($st->fetch(PDO::FETCH_ASSOC) ?: null);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function routeContextForBus(string $busReg, ?string $date = null): ?array
    {
        $busReg = trim($busReg);
        if ($busReg === '') {
            return null;
        }

        $date = trim((string)($date ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        try {
            $st = $this->pdo->prepare(
                "SELECT t.route_id, r.route_no
                   FROM timetables t
                   LEFT JOIN routes r ON r.route_id = t.route_id
                  WHERE t.bus_reg_no = ?
                    AND t.operator_type = 'SLTB'
                    AND t.effective_from <= ?
                    AND (t.effective_to IS NULL OR t.effective_to >= ?)
                  ORDER BY
                    (t.day_of_week = DAYOFWEEK(?) - 1) DESC,
                    t.effective_from DESC,
                    t.departure_time ASC,
                    t.timetable_id DESC
                  LIMIT 1"
            );
            $st->execute([$busReg, $date, $date, $date]);
            return $this->normalizeRouteContext($st->fetch(PDO::FETCH_ASSOC) ?: null);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Create new assignment (relies on DB UNIQUE(bus_reg_no,assigned_date,shift)) */
    public function create(array $d, int $depotId): mixed {
        $assigned_date = trim((string)($d['assigned_date'] ?? date('Y-m-d')));
        $timetableId   = !empty($d['timetable_id']) ? (int)$d['timetable_id'] : null;
        $shift = $this->resolveShiftValue((string)($d['shift'] ?? ''), $timetableId);
        if ($shift === '') {
            return false;
        }

        $bus = strtoupper(trim((string)($d['bus_reg_no'] ?? '')));
        $driver = (int)($d['sltb_driver_id'] ?? 0);
        $conductor = (int)($d['sltb_conductor_id'] ?? 0);
        if ($bus === '' || $driver <= 0 || $conductor <= 0) {
            return false;
        }

        $overrideRemark = trim((string)($d['override_remark'] ?? '')) ?: null;
        $overriddenBy = $_SESSION['user']['user_id'] ?? null;
        $now = date('Y-m-d H:i:s');
        $existingRow = $this->findBusTurnAssignment($depotId, $bus, $assigned_date, $shift, $timetableId);
        $existingAssignmentId = (int)($existingRow['assignment_id'] ?? 0);

        if ($existingRow) {
            $sameDriver = (int)($existingRow['sltb_driver_id'] ?? 0) === $driver;
            $sameConductor = (int)($existingRow['sltb_conductor_id'] ?? 0) === $conductor;
            if ((!$sameDriver || !$sameConductor) && !$overrideRemark) {
                return 'conflict_bus::' . $bus;
            }
        }

        $prevBusForDriver = $this->findStaffConflictBus(
            $depotId,
            $assigned_date,
            $shift,
            $timetableId,
            'sltb_driver_id',
            $driver,
            $existingAssignmentId
        );
        if ($prevBusForDriver && $prevBusForDriver !== $bus && !$overrideRemark) {
            return 'conflict_driver::' . $prevBusForDriver;
        }

        $prevBusForConductor = $this->findStaffConflictBus(
            $depotId,
            $assigned_date,
            $shift,
            $timetableId,
            'sltb_conductor_id',
            $conductor,
            $existingAssignmentId
        );
        if ($prevBusForConductor && $prevBusForConductor !== $bus && !$overrideRemark) {
            return 'conflict_conductor::' . $prevBusForConductor;
        }

        // Build INSERT dynamically depending on whether override columns exist
        $baseCols = ['assigned_date','shift','bus_reg_no','sltb_driver_id','sltb_conductor_id','sltb_depot_id'];
        $values = [$assigned_date, $shift, $bus, $driver, $conductor, $depotId];
        if ($timetableId && $this->columnExists('sltb_assignments','timetable_id')) {
            $baseCols[] = 'timetable_id';
            $values[] = $timetableId;
        }
        if ($this->columnExists('sltb_assignments','override_remark')) {
            $baseCols[] = 'override_remark';
            $baseCols[] = 'overridden_by';
            $baseCols[] = 'override_at';
            $values[] = $overrideRemark;
            $values[] = $overriddenBy;
            $values[] = $overrideRemark ? $now : null;
        }

        if ($existingAssignmentId > 0) {
            $setParts = ['assigned_date = ?', 'shift = ?', 'bus_reg_no = ?', 'sltb_driver_id = ?', 'sltb_conductor_id = ?'];
            $updateValues = [$assigned_date, $shift, $bus, $driver, $conductor];
            if ($this->columnExists('sltb_assignments', 'timetable_id')) {
                $setParts[] = 'timetable_id = ?';
                $updateValues[] = $timetableId;
            }
            if ($this->columnExists('sltb_assignments','override_remark')) {
                $setParts[] = 'override_remark = ?';
                $setParts[] = 'overridden_by = ?';
                $setParts[] = 'override_at = ?';
                $updateValues[] = $overrideRemark;
                $updateValues[] = $overriddenBy;
                $updateValues[] = $overrideRemark ? $now : null;
            }

            $upd = "UPDATE sltb_assignments SET " . implode(', ', $setParts) . " WHERE assignment_id = ? AND sltb_depot_id = ?";
            $ust = $this->pdo->prepare($upd);
            $ok = (bool)$ust->execute(array_merge($updateValues, [$existingAssignmentId, $depotId]));
            if ($ok && $overrideRemark && $this->tableExists('sltb_assignment_overrides')) {
                $previous = array_filter(array_unique([$prevBusForDriver, $prevBusForConductor]));
                $previousStr = $previous ? implode(',', $previous) : null;
                $ins = $this->pdo->prepare("INSERT INTO sltb_assignment_overrides (assignment_id, assigned_date, shift, bus_reg_no, previous_bus_reg_no, driver_id, conductor_id, override_remark, overridden_by, override_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $ins->execute([$existingAssignmentId, $assigned_date, $shift, $bus, $previousStr, $driver ?: null, $conductor ?: null, $overrideRemark, $overriddenBy, $now]);
            }
            return $ok;
        }

        $placeholders = implode(',', array_fill(0, count($baseCols), '?'));
        $sql = "INSERT INTO sltb_assignments (" . implode(',', $baseCols) . ") VALUES ($placeholders)";
        $st = $this->pdo->prepare($sql);
        try {
            $ok = (bool)$st->execute($values);
            // record audit only if audit table exists and an override was provided
            if ($ok && $overrideRemark && $this->tableExists('sltb_assignment_overrides')) {
                // fetch assignment_id for audit
                $aidSt = $this->pdo->prepare("SELECT assignment_id FROM sltb_assignments WHERE bus_reg_no=? AND assigned_date=? AND shift=? AND sltb_depot_id=? LIMIT 1");
                $aidSt->execute([$bus, $assigned_date, $shift, $depotId]);
                $aidRow = $aidSt->fetch(PDO::FETCH_ASSOC);
                $assignmentId = $aidRow['assignment_id'] ?? null;
                $previous = array_filter(array_unique([$prevBusForDriver, $prevBusForConductor]));
                $previousStr = $previous ? implode(',', $previous) : null;
                if ($assignmentId) {
                    $ins = $this->pdo->prepare("INSERT INTO sltb_assignment_overrides (assignment_id, assigned_date, shift, bus_reg_no, previous_bus_reg_no, driver_id, conductor_id, override_remark, overridden_by, override_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
                    $ins->execute([$assignmentId, $assigned_date, $shift, $bus, $previousStr, $driver ?: null, $conductor ?: null, $overrideRemark, $overriddenBy, $now]);
                }
            }
            return $ok;
        } catch (\PDOException $e) {
            // If duplicate key (same bus/date/shift), perform an UPDATE instead
            $code = $e->getCode();
            if ($code === '23000' || strpos($e->getMessage(), 'Duplicate') !== false) {
                $setParts = ['sltb_driver_id = ?', 'sltb_conductor_id = ?'];
                $updValues = [$driver, $conductor];
                if ($this->columnExists('sltb_assignments','timetable_id')) {
                    $setParts[] = 'timetable_id = ?';
                    $updValues[] = $timetableId;
                }
                if ($this->columnExists('sltb_assignments','override_remark')) {
                    $setParts[] = 'override_remark = ?';
                    $setParts[] = 'overridden_by = ?';
                    $setParts[] = 'override_at = ?';
                    $updValues[] = $overrideRemark;
                    $updValues[] = $overriddenBy;
                    $updValues[] = $overrideRemark ? $now : null;
                }
                $upd = "UPDATE sltb_assignments SET " . implode(', ', $setParts) . " WHERE bus_reg_no = ? AND assigned_date = ? AND shift = ? AND sltb_depot_id = ?";
                $ust = $this->pdo->prepare($upd);
                $updValues = array_merge($updValues, [$bus, $assigned_date, $shift, $depotId]);
                $ok = (bool)$ust->execute($updValues);
                if ($ok && $overrideRemark && $this->tableExists('sltb_assignment_overrides')) {
                    $aidSt = $this->pdo->prepare("SELECT assignment_id FROM sltb_assignments WHERE bus_reg_no=? AND assigned_date=? AND shift=? AND sltb_depot_id=? LIMIT 1");
                    $aidSt->execute([$bus, $assigned_date, $shift, $depotId]);
                    $aidRow = $aidSt->fetch(PDO::FETCH_ASSOC);
                    $assignmentId = $aidRow['assignment_id'] ?? null;
                    $previous = array_filter(array_unique([$prevBusForDriver, $prevBusForConductor]));
                    $previousStr = $previous ? implode(',', $previous) : null;
                    if ($assignmentId) {
                        $ins = $this->pdo->prepare("INSERT INTO sltb_assignment_overrides (assignment_id, assigned_date, shift, bus_reg_no, previous_bus_reg_no, driver_id, conductor_id, override_remark, overridden_by, override_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
                        $ins->execute([$assignmentId, $assigned_date, $shift, $bus, $previousStr, $driver ?: null, $conductor ?: null, $overrideRemark, $overriddenBy, $now]);
                    }
                }
                return $ok;
            }
            throw $e;
        }
    }

    /** Re-assign staff (update existing row) */
    public function reassign(int $depotId, int $assignmentId, int $driverId, int $conductorId, ?string $shift=null): bool|string {
        $current = $this->findById($depotId, $assignmentId);
        if (!$current) {
            return false;
        }

        $currentTimetableId = (int)($current['timetable_id'] ?? 0);
        $currentShift = $this->resolveShiftValue((string)($current['shift'] ?? ''), $currentTimetableId > 0 ? $currentTimetableId : null);
        $resolvedShift = $this->resolveShiftValue($shift ?? $currentShift, $currentTimetableId > 0 ? $currentTimetableId : null);
        if ($resolvedShift === '') {
            return false;
        }

        $bus = strtoupper(trim((string)($current['bus_reg_no'] ?? '')));
        $assignedDate = trim((string)($current['assigned_date'] ?? ''));

        $driverConflict = $this->findStaffConflictBus($depotId, $assignedDate, $resolvedShift, $currentTimetableId ?: null, 'sltb_driver_id', $driverId, $assignmentId);
        if ($driverConflict && $driverConflict !== $bus) {
            return 'conflict_driver::' . $driverConflict;
        }

        $conductorConflict = $this->findStaffConflictBus($depotId, $assignedDate, $resolvedShift, $currentTimetableId ?: null, 'sltb_conductor_id', $conductorId, $assignmentId);
        if ($conductorConflict && $conductorConflict !== $bus) {
            return 'conflict_conductor::' . $conductorConflict;
        }

        $setParts = ['sltb_driver_id=?', 'sltb_conductor_id=?', 'shift=?'];
        $values = [$driverId, $conductorId, $resolvedShift];
        if ($this->columnExists('sltb_assignments', 'timetable_id')) {
            $preserveTimetableId = ($currentTimetableId > 0 && $resolvedShift === $currentShift) ? $currentTimetableId : null;
            $setParts[] = 'timetable_id=?';
            $values[] = $preserveTimetableId;
        }

        $sql = "UPDATE sltb_assignments SET " . implode(', ', $setParts) . " WHERE assignment_id=? AND sltb_depot_id=?";
        $st  = $this->pdo->prepare($sql);
        return $st->execute(array_merge($values, [$assignmentId, $depotId]));
    }

    public function update(int $depotId, array $d): bool|string {
        $assignmentId = (int)($d['assignment_id'] ?? 0);
        $assignedDate = trim((string)($d['assigned_date'] ?? ''));
        $current = $this->findById($depotId, $assignmentId);
        if ($assignmentId <= 0 || !$current) {
            return false;
        }

        $currentBus = strtoupper(trim((string)($current['bus_reg_no'] ?? '')));
        $currentTimetableId = (int)($current['timetable_id'] ?? 0);
        $currentShift = $this->resolveShiftValue((string)($current['shift'] ?? ''), $currentTimetableId > 0 ? $currentTimetableId : null);

        $timetableId = !empty($d['timetable_id']) ? (int)$d['timetable_id'] : null;
        $shift = $this->resolveShiftValue((string)($d['shift'] ?? ''), $timetableId);
        $bus = strtoupper(trim((string)($d['bus_reg_no'] ?? '')));
        $driverId = (int)($d['sltb_driver_id'] ?? 0);
        $conductorId = (int)($d['sltb_conductor_id'] ?? 0);

        if ($timetableId === null && $currentTimetableId > 0 && $bus === $currentBus && $shift === $currentShift) {
            $timetableId = $currentTimetableId;
        }
        if ($shift === '' && $currentShift !== '') {
            $shift = $currentShift;
        }

        if ($assignmentId <= 0 || !$assignedDate || !$bus || $driverId <= 0 || $conductorId <= 0) {
            return false;
        }

        $busConflict = $this->findBusTurnAssignment($depotId, $bus, $assignedDate, $shift, $timetableId, $assignmentId);
        if ($busConflict) {
            return 'conflict_bus::' . $bus;
        }

        // Prevent same driver being used on a different bus at the same exact shift/date
        if ($driverId) {
            $rowBus = $this->findStaffConflictBus($depotId, $assignedDate, $shift, $timetableId, 'sltb_driver_id', $driverId, $assignmentId);
            if ($rowBus && $rowBus !== $bus) {
                return 'conflict_driver::' . $rowBus;
            }
        }

        // Prevent same conductor being used on a different bus at the same exact shift/date
        if ($conductorId) {
            $rowBus = $this->findStaffConflictBus($depotId, $assignedDate, $shift, $timetableId, 'sltb_conductor_id', $conductorId, $assignmentId);
            if ($rowBus && $rowBus !== $bus) {
                return 'conflict_conductor::' . $rowBus;
            }
        }

        $setCols = ['assigned_date=?', 'shift=?', 'bus_reg_no=?', 'sltb_driver_id=?', 'sltb_conductor_id=?'];
        $setVals = [$assignedDate, $shift, $bus, $driverId, $conductorId];
        if ($this->columnExists('sltb_assignments', 'timetable_id')) {
            $setCols[] = 'timetable_id=?';
            $setVals[] = $timetableId;
        }
        $sql = "UPDATE sltb_assignments SET " . implode(', ', $setCols) . " WHERE assignment_id=? AND sltb_depot_id=?";
        $st = $this->pdo->prepare($sql);
        return (bool)$st->execute(array_merge($setVals, [$assignmentId, $depotId]));
    }

    public function findById(int $depotId, int $assignmentId): ?array {
        $hasTimetableId = $this->columnExists('sltb_assignments', 'timetable_id');
        $timetableSelect = $hasTimetableId ? 'a.timetable_id,' : 'NULL AS timetable_id,';
        $exactTimetableJoin = $hasTimetableId ? 'LEFT JOIN timetables tt_exact ON tt_exact.timetable_id = a.timetable_id' : '';
        $routeSelect = $hasTimetableId
            ? 'COALESCE(tt_exact.route_id, tt.route_id) AS route_id,
                COALESCE(r_exact.route_no, r.route_no) AS route_no'
            : 'tt.route_id,
                r.route_no';
        $routeJoin = $hasTimetableId ? 'LEFT JOIN routes r_exact ON r_exact.route_id = tt_exact.route_id' : '';
        $st = $this->pdo->prepare(
            "SELECT a.assignment_id,
                    a.assigned_date,
                    a.shift,
                {$timetableSelect}
                    a.bus_reg_no,
                    a.sltb_driver_id,
                    a.sltb_conductor_id,
                {$routeSelect}
             FROM sltb_assignments a
             {$exactTimetableJoin}
             LEFT JOIN (
                SELECT t.bus_reg_no,
                       CAST(
                           SUBSTRING_INDEX(
                               GROUP_CONCAT(
                                   t.route_id
                                   ORDER BY t.effective_from DESC, t.departure_time ASC, t.timetable_id DESC
                                   SEPARATOR ','
                               ),
                               ',',
                               1
                           ) AS UNSIGNED
                       ) AS route_id
                FROM timetables t
                WHERE t.operator_type='SLTB'
                GROUP BY t.bus_reg_no
             ) tt ON tt.bus_reg_no = a.bus_reg_no
                 {$routeJoin}
             LEFT JOIN routes r ON r.route_id = tt.route_id
             WHERE a.assignment_id=? AND a.sltb_depot_id=?
             LIMIT 1"
        );
        $st->execute([$assignmentId, $depotId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function delete(int $id, int $depotId): bool {
        $st = $this->pdo->prepare(
            "DELETE FROM sltb_assignments WHERE assignment_id=? AND sltb_depot_id=?"
        );
        return $st->execute([$id, $depotId]);
    }

    /**
     * Counts of available (unassigned today) buses, drivers, conductors for the mini analytics bar.
     */
    public function availability(int $depotId): array
    {
        $cnt = function (string $sql, array $p = []): int {
            $st = $this->pdo->prepare($sql);
            $st->execute($p);
            return (int)($st->fetchColumn() ?? 0);
        };

        $assignedBuses      = $cnt("SELECT COUNT(DISTINCT bus_reg_no) FROM sltb_assignments WHERE sltb_depot_id=? AND assigned_date=CURDATE()", [$depotId]);
        $totalBuses         = $cnt("SELECT COUNT(*) FROM sltb_buses WHERE sltb_depot_id=? AND status='Active'", [$depotId]);
        $assignedDrivers    = $cnt("SELECT COUNT(DISTINCT sltb_driver_id) FROM sltb_assignments WHERE sltb_depot_id=? AND assigned_date=CURDATE()", [$depotId]);
        $totalDrivers       = $cnt("SELECT COUNT(*) FROM sltb_drivers WHERE sltb_depot_id=? AND status='Active'", [$depotId]);
        $assignedConductors = $cnt("SELECT COUNT(DISTINCT sltb_conductor_id) FROM sltb_assignments WHERE sltb_depot_id=? AND assigned_date=CURDATE()", [$depotId]);
        $totalConductors    = $cnt("SELECT COUNT(*) FROM sltb_conductors WHERE sltb_depot_id=? AND status='Active'", [$depotId]);

        return [
            'available_buses'       => max(0, $totalBuses - $assignedBuses),
            'total_buses'           => $totalBuses,
            'available_drivers'     => max(0, $totalDrivers - $assignedDrivers),
            'total_drivers'         => $totalDrivers,
            'available_conductors'  => max(0, $totalConductors - $assignedConductors),
            'total_conductors'      => $totalConductors,
        ];
    }

    /**
     * Return existing assignments for a bus+shift within a date range.
     * Used by the AJAX conflict checker to warn before the form is submitted.
     */
    public function busConflictsForPeriod(int $depotId, string $bus, string $departureTime, string $from, string $to, int $timetableId = 0, int $excludeAssignmentId = 0): array
    {
        $shift = $this->resolveShiftValue($departureTime, $timetableId > 0 ? $timetableId : null);
        if ($shift === '') {
            return [];
        }

        $params = [$depotId, $bus, $from, $to];
        $turnParams = [];
        $extraParams = [];
        $turnSql = $this->turnMatchSql('a', $shift, $timetableId > 0 ? $timetableId : null, $turnParams);
        $sql = "SELECT a.assigned_date,
                       d.full_name AS driver_name,
                       c.full_name AS conductor_name
                FROM sltb_assignments a
                LEFT JOIN sltb_drivers    d ON d.sltb_driver_id    = a.sltb_driver_id
                LEFT JOIN sltb_conductors c ON c.sltb_conductor_id = a.sltb_conductor_id
                WHERE a.sltb_depot_id=? AND a.bus_reg_no=?
                  AND a.assigned_date BETWEEN ? AND ?
                  AND {$turnSql}
                ORDER BY a.assigned_date";
                if ($excludeAssignmentId > 0) {
                        $sql = str_replace('ORDER BY a.assigned_date', 'AND a.assignment_id <> ? ORDER BY a.assigned_date', $sql);
            $extraParams[] = $excludeAssignmentId;
                }
        $st = $this->pdo->prepare($sql);
        $st->execute(array_merge($params, $turnParams, $extraParams));
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * For a given timetable departure time (HH:MM) and effective date range, return
     * driver/conductor IDs already assigned to a different bus within that period,
     * along with which bus they are assigned to.
     * Used by the JS conflict-checker AJAX endpoint.
     */
    public function staffConflictsForTurn(int $depotId, string $departureTime, string $from, string $to, int $timetableId = 0, int $excludeAssignmentId = 0): array
    {
        $shift = $this->resolveShiftValue($departureTime, $timetableId > 0 ? $timetableId : null);
        if ($shift === '') {
            return ['drivers' => [], 'conductors' => []];
        }

        $params = [$depotId, $from, $to];
        $turnParams = [];
        $extraParams = [];
        $turnSql = $this->turnMatchSql('a', $shift, $timetableId > 0 ? $timetableId : null, $turnParams);
        $sql = "SELECT a.sltb_driver_id, a.sltb_conductor_id, a.bus_reg_no
                FROM sltb_assignments a
                WHERE a.sltb_depot_id=?
                  AND a.assigned_date BETWEEN ? AND ?
                  AND {$turnSql}";
                if ($excludeAssignmentId > 0) {
                        $sql .= ' AND a.assignment_id <> ?';
            $extraParams[] = $excludeAssignmentId;
                }
        $st = $this->pdo->prepare($sql);
        $st->execute(array_merge($params, $turnParams, $extraParams));
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $drivers = [];
        $conductors = [];
        foreach ($rows as $r) {
            if ($r['sltb_driver_id'])    $drivers[(int)$r['sltb_driver_id']]    = $r['bus_reg_no'];
            if ($r['sltb_conductor_id']) $conductors[(int)$r['sltb_conductor_id']] = $r['bus_reg_no'];
        }
        return ['drivers' => $drivers, 'conductors' => $conductors];
    }
}