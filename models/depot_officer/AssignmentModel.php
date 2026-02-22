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
    $sql = "SELECT 
                a.assignment_id,
                a.assigned_date,
                a.shift,
                a.bus_reg_no,
                b.status AS bus_status,
                COALESCE(b.capacity,0) AS capacity,
                d.full_name AS driver_name,
                c.full_name AS conductor_name,
                r.route_no,
                r.stops_json,
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

            /* --- pick ONE timetable row per bus for today (earliest dep) --- */
            LEFT JOIN (
                SELECT t1.*
                FROM timetables t1
                JOIN (
                    SELECT bus_reg_no, MIN(departure_time) AS dep
                    FROM timetables
                    WHERE operator_type='SLTB'
                      AND day_of_week = DAYOFWEEK(CURDATE())-1
                      AND effective_from <= CURDATE()
                      AND (effective_to IS NULL OR effective_to >= CURDATE())
                    GROUP BY bus_reg_no
                ) m ON m.bus_reg_no = t1.bus_reg_no AND m.dep = t1.departure_time
                WHERE t1.operator_type='SLTB'
                  AND t1.day_of_week = DAYOFWEEK(CURDATE())-1
                  AND t1.effective_from <= CURDATE()
                  AND (t1.effective_to IS NULL OR t1.effective_to >= CURDATE())
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
                               r.route_no,
                               r.stops_json
                           FROM sltb_buses b
                         LEFT JOIN (
                                 /* pick one timetable row per bus for today (earliest dep) */
                                 SELECT t1.bus_reg_no, t1.route_id
                                 FROM timetables t1
                                 JOIN (
                                     SELECT bus_reg_no, MIN(departure_time) AS dep
                                     FROM timetables
                                     WHERE operator_type='SLTB'
                                         AND day_of_week = DAYOFWEEK(CURDATE())-1
                                         AND effective_from <= CURDATE()
                                         AND (effective_to IS NULL OR effective_to >= CURDATE())
                                     GROUP BY bus_reg_no
                                 ) m ON m.bus_reg_no = t1.bus_reg_no AND m.dep = t1.departure_time
                                 WHERE t1.operator_type='SLTB'
                                     AND t1.day_of_week = DAYOFWEEK(CURDATE())-1
                                     AND t1.effective_from <= CURDATE()
                                     AND (t1.effective_to IS NULL OR t1.effective_to >= CURDATE())
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

    /** Create new assignment (relies on DB UNIQUE(bus_reg_no,assigned_date,shift)) */
    public function create(array $d, int $depotId): mixed {
        $assigned_date = $d['assigned_date'] ?? date('Y-m-d');
        $shift = $d['shift'] ?? 'Morning';
        $bus = $d['bus_reg_no'] ?? '';
        $driver = (int)($d['sltb_driver_id'] ?? 0);
        $conductor = (int)($d['sltb_conductor_id'] ?? 0);

        $overrideRemark = trim((string)($d['override_remark'] ?? '')) ?: null;
        $overriddenBy = $_SESSION['user']['user_id'] ?? null;
        $now = date('Y-m-d H:i:s');

        // --- Prevent same driver/conductor being assigned to different buses on same date ---
        // If overrideRemark is provided we allow the operation but will record an audit row.
        $prevBusForDriver = null;
        if ($driver) {
            $st = $this->pdo->prepare("SELECT bus_reg_no FROM sltb_assignments WHERE assigned_date=? AND shift=? AND sltb_depot_id=? AND sltb_driver_id=? LIMIT 1");
            $st->execute([$assigned_date, $shift, $depotId, $driver]);
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
            $st = $this->pdo->prepare("SELECT bus_reg_no FROM sltb_assignments WHERE assigned_date=? AND shift=? AND sltb_depot_id=? AND sltb_conductor_id=? LIMIT 1");
            $st->execute([$assigned_date, $shift, $depotId, $conductor]);
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

    public function delete(int $id, int $depotId): bool {
        $st = $this->pdo->prepare(
            "DELETE FROM sltb_assignments WHERE assignment_id=? AND sltb_depot_id=?"
        );
        return $st->execute([$id, $depotId]);
    }
}