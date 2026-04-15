<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;
use PDO;

class AssignmentModel extends BaseModel
{
    private array $columnCache = [];

    private function columnExists(string $table, string $column): bool {
        $key = $table . ':' . $column;
        if (array_key_exists($key, $this->columnCache)) return $this->columnCache[$key];
        $st = $this->pdo->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
        $st->execute([$table, $column]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $exists = !empty($row) && ((int)($row['c'] ?? 0) > 0);
        $this->columnCache[$key] = $exists;
        return $exists;
    }

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

    /** Grid for today's rows (capacity + latest location) */
public function allToday(int $depotId): array {
    $timetableSelect = $this->columnExists('sltb_assignments', 'timetable_id')
        ? "a.timetable_id,"
        : "NULL AS timetable_id,";

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
                tt.departure_time,
                tm.lat,
                tm.lng,
                tm.snapshot_at
            FROM sltb_assignments a
            /* --- ensure one row per BUS for today (latest assignment row) --- */
            JOIN (
                SELECT bus_reg_no, MAX(assignment_id) AS assignment_id
                FROM sltb_assignments
                WHERE assigned_date = CURDATE() AND sltb_depot_id = ?
                GROUP BY bus_reg_no
            ) pick ON pick.assignment_id = a.assignment_id

            /* bus must belong to this depot */
            JOIN sltb_buses b 
                  ON b.reg_no = a.bus_reg_no 
                 AND b.sltb_depot_id = ?

            LEFT JOIN sltb_drivers d    ON d.sltb_driver_id    = a.sltb_driver_id
            LEFT JOIN sltb_conductors c ON c.sltb_conductor_id = a.sltb_conductor_id

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

            LEFT JOIN routes r ON r.route_id = tt.route_id

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

            ORDER BY a.shift, a.bus_reg_no";
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
    public function routes(): array {
        $rows = $this->pdo->query(
            "SELECT route_id, route_no, stops_json 
               FROM routes 
              WHERE is_active=1 
           ORDER BY route_no+0, route_no"
        )->fetchAll(PDO::FETCH_ASSOC);
        
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

    /** Create new assignment (relies on DB UNIQUE(bus_reg_no,assigned_date,shift)) */
    public function create(array $d, int $depotId): mixed {
        $assigned_date = $d['assigned_date'] ?? date('Y-m-d');
        $timetableId   = !empty($d['timetable_id']) ? (int)$d['timetable_id'] : null;
        $shift = $d['shift'] ?? '';
        if ($timetableId) {
            $ts = $this->pdo->prepare("SELECT departure_time FROM timetables WHERE timetable_id=? LIMIT 1");
            $ts->execute([$timetableId]);
            $tr = $ts->fetch(PDO::FETCH_ASSOC);
            if ($tr) { $shift = substr($tr['departure_time'], 0, 5); }
        }
        if (!$shift) { $shift = 'Morning'; }
        $bus = $d['bus_reg_no'] ?? '';
        $driver = (int)($d['sltb_driver_id'] ?? 0);
        $conductor = (int)($d['sltb_conductor_id'] ?? 0);

        $overrideRemark = trim((string)($d['override_remark'] ?? '')) ?: null;
        $overriddenBy = $_SESSION['user']['user_id'] ?? null;
        $now = date('Y-m-d H:i:s');

        // --- Check if this bus is already assigned for this date+shift with DIFFERENT staff ---
        $stBus = $this->pdo->prepare(
            "SELECT sltb_driver_id, sltb_conductor_id FROM sltb_assignments
              WHERE bus_reg_no=? AND assigned_date=? AND shift=? AND sltb_depot_id=? LIMIT 1"
        );
        $stBus->execute([$bus, $assigned_date, $this->timeToShift($shift), $depotId]);
        $ebRow = $stBus->fetch(PDO::FETCH_ASSOC);
        if ($ebRow) {
            $sameDriver    = (int)$ebRow['sltb_driver_id']    === $driver;
            $sameConductor = (int)$ebRow['sltb_conductor_id'] === $conductor;
            if (!$sameDriver || !$sameConductor) {
                if (!$overrideRemark) {
                    return 'conflict_bus::' . $bus;
                }
            }
        }
        // --- Prevent same driver/conductor being assigned to different buses on same date ---
        $prevBusForDriver = null;
        if ($driver) {
            if ($timetableId) {
                $st = $this->pdo->prepare("SELECT bus_reg_no FROM sltb_assignments WHERE assigned_date=? AND timetable_id=? AND sltb_depot_id=? AND sltb_driver_id=? LIMIT 1");
                $st->execute([$assigned_date, $timetableId, $depotId, $driver]);
            } else {
                $st = $this->pdo->prepare("SELECT bus_reg_no FROM sltb_assignments WHERE assigned_date=? AND shift=? AND sltb_depot_id=? AND sltb_driver_id=? LIMIT 1");
                $st->execute([$assigned_date, $shift, $depotId, $driver]);
            }
            $row = $st->fetch(\PDO::FETCH_ASSOC);
            if ($row && ($row['bus_reg_no'] ?? '') !== $bus) {
                $prevBusForDriver = $row['bus_reg_no'] ?? null;
                if (!$overrideRemark) {
                    return 'conflict_driver::' . ($row['bus_reg_no'] ?? '');
                }
            }
        }

        $prevBusForConductor = null;
        if ($conductor) {
            if ($timetableId) {
                $st = $this->pdo->prepare("SELECT bus_reg_no FROM sltb_assignments WHERE assigned_date=? AND timetable_id=? AND sltb_depot_id=? AND sltb_conductor_id=? LIMIT 1");
                $st->execute([$assigned_date, $timetableId, $depotId, $conductor]);
            } else {
                $st = $this->pdo->prepare("SELECT bus_reg_no FROM sltb_assignments WHERE assigned_date=? AND shift=? AND sltb_depot_id=? AND sltb_conductor_id=? LIMIT 1");
                $st->execute([$assigned_date, $shift, $depotId, $conductor]);
            }
            $row = $st->fetch(\PDO::FETCH_ASSOC);
            if ($row && ($row['bus_reg_no'] ?? '') !== $bus) {
                $prevBusForConductor = $row['bus_reg_no'] ?? null;
                if (!$overrideRemark) {
                    return 'conflict_conductor::' . ($row['bus_reg_no'] ?? '');
                }
            }
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
    public function reassign(int $depotId, int $assignmentId, int $driverId, int $conductorId, ?string $shift=null): bool {
        if ($shift) {
            $sql = "UPDATE sltb_assignments
                       SET sltb_driver_id=?, sltb_conductor_id=?, shift=?
                     WHERE assignment_id=? AND sltb_depot_id=?";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([$driverId, $conductorId, $shift, $assignmentId, $depotId]);
        } else {
            $sql = "UPDATE sltb_assignments
                       SET sltb_driver_id=?, sltb_conductor_id=?
                     WHERE assignment_id=? AND sltb_depot_id=?";
            $st  = $this->pdo->prepare($sql);
            return $st->execute([$driverId, $conductorId, $assignmentId, $depotId]);
        }
    }

    public function update(int $depotId, array $d): bool|string {
        $assignmentId = (int)($d['assignment_id'] ?? 0);
        $assignedDate = trim((string)($d['assigned_date'] ?? ''));
        $timetableId = !empty($d['timetable_id']) ? (int)$d['timetable_id'] : null;
        $shift = trim((string)($d['shift'] ?? ''));
        if ($timetableId) {
            $ts = $this->pdo->prepare("SELECT departure_time FROM timetables WHERE timetable_id=? LIMIT 1");
            $ts->execute([$timetableId]);
            $tr = $ts->fetch(\PDO::FETCH_ASSOC);
            if ($tr) { $shift = substr($tr['departure_time'], 0, 5); }
        }
        if (!$shift) { $shift = 'Morning'; }
        $bus = trim((string)($d['bus_reg_no'] ?? ''));
        $driverId = (int)($d['sltb_driver_id'] ?? 0);
        $conductorId = (int)($d['sltb_conductor_id'] ?? 0);

        if ($assignmentId <= 0 || !$assignedDate || !$bus || $driverId <= 0 || $conductorId <= 0) {
            return false;
        }

        // Prevent same driver being used on a different bus at the same exact shift/date
        if ($driverId) {
            if ($timetableId) {
                $st = $this->pdo->prepare(
                    "SELECT bus_reg_no FROM sltb_assignments
                      WHERE assigned_date=? AND timetable_id=? AND sltb_depot_id=?
                        AND sltb_driver_id=? AND assignment_id != ? LIMIT 1"
                );
                $st->execute([$assignedDate, $timetableId, $depotId, $driverId, $assignmentId]);
            } else {
                $st = $this->pdo->prepare(
                    "SELECT bus_reg_no FROM sltb_assignments
                      WHERE assigned_date=? AND shift=? AND sltb_depot_id=?
                        AND sltb_driver_id=? AND assignment_id != ? LIMIT 1"
                );
                $st->execute([$assignedDate, $shift, $depotId, $driverId, $assignmentId]);
            }
            $row = $st->fetch(\PDO::FETCH_ASSOC);
            if ($row && ($row['bus_reg_no'] ?? '') !== $bus) {
                return 'conflict_driver::' . ($row['bus_reg_no'] ?? '');
            }
        }

        // Prevent same conductor being used on a different bus at the same exact shift/date
        if ($conductorId) {
            if ($timetableId) {
                $st = $this->pdo->prepare(
                    "SELECT bus_reg_no FROM sltb_assignments
                      WHERE assigned_date=? AND timetable_id=? AND sltb_depot_id=?
                        AND sltb_conductor_id=? AND assignment_id != ? LIMIT 1"
                );
                $st->execute([$assignedDate, $timetableId, $depotId, $conductorId, $assignmentId]);
            } else {
                $st = $this->pdo->prepare(
                    "SELECT bus_reg_no FROM sltb_assignments
                      WHERE assigned_date=? AND shift=? AND sltb_depot_id=?
                        AND sltb_conductor_id=? AND assignment_id != ? LIMIT 1"
                );
                $st->execute([$assignedDate, $shift, $depotId, $conductorId, $assignmentId]);
            }
            $row = $st->fetch(\PDO::FETCH_ASSOC);
            if ($row && ($row['bus_reg_no'] ?? '') !== $bus) {
                return 'conflict_conductor::' . ($row['bus_reg_no'] ?? '');
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
        $st = $this->pdo->prepare(
            "SELECT assignment_id, assigned_date, shift, bus_reg_no, sltb_driver_id, sltb_conductor_id
             FROM sltb_assignments
             WHERE assignment_id=? AND sltb_depot_id=?
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
    public function busConflictsForPeriod(int $depotId, string $bus, string $departureTime, string $from, string $to): array
    {
        $shift = $this->timeToShift($departureTime);
        $sql = "SELECT a.assigned_date,
                       d.full_name AS driver_name,
                       c.full_name AS conductor_name
                FROM sltb_assignments a
                LEFT JOIN sltb_drivers    d ON d.sltb_driver_id    = a.sltb_driver_id
                LEFT JOIN sltb_conductors c ON c.sltb_conductor_id = a.sltb_conductor_id
                WHERE a.sltb_depot_id=? AND a.bus_reg_no=? AND a.shift=?
                  AND a.assigned_date BETWEEN ? AND ?
                ORDER BY a.assigned_date";
        $st = $this->pdo->prepare($sql);
        $st->execute([$depotId, $bus, $shift, $from, $to]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Map HH:MM departure time to the ENUM shift category used in sltb_assignments.
     */
    private function timeToShift(string $hhmm): string
    {
        $h = (int)substr($hhmm, 0, 2);
        if ($h < 12) return 'Morning';
        if ($h < 17) return 'Evening';
        return 'Night';
    }

    /**
     * For a given timetable departure time (HH:MM) and effective date range, return
     * driver/conductor IDs already assigned to a different bus within that period,
     * along with which bus they are assigned to.
     * Used by the JS conflict-checker AJAX endpoint.
     */
    public function staffConflictsForTurn(int $depotId, string $departureTime, string $from, string $to): array
    {
        $shift = $this->timeToShift($departureTime);
        $sql = "SELECT a.sltb_driver_id, a.sltb_conductor_id, a.bus_reg_no
                FROM sltb_assignments a
                WHERE a.sltb_depot_id=? AND a.shift=?
                  AND a.assigned_date BETWEEN ? AND ?";
        $st = $this->pdo->prepare($sql);
        $st->execute([$depotId, $shift, $from, $to]);
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