<?php
namespace App\models\bus_owner;

use PDO;

class BusModel extends BaseModel
{
    private function normalizeBusClass(?string $busClass): string
    {
        $allowed = ['Luxury', 'Semi-Luxury', 'Normal'];
        $value = trim((string)$busClass);
        if ($value === 'AC') {
            $value = 'Luxury';
        }
        return in_array($value, $allowed, true) ? $value : 'Normal';
    }

    private function normalizeYear($year): ?int
    {
        if ($year === null || $year === '') {
            return null;
        }
        $y = (int)$year;
        if ($y < 1900 || $y > 2100) {
            return null;
        }
        return $y;
    }

    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }

    /**
     * Get all buses belonging to the logged-in owner,
     * joined with timetables and routes to show route info.
     */
    public function all(): array
    {
        $sql = "SELECT 
                    b.reg_no AS bus_number,
                    b.private_operator_id,
                    b.chassis_no,
                    b.manufactured_date,
                    b.manufactured_year,
                    b.model,
                    b.bus_class,
                    b.capacity,
                    b.status,
                    b.driver_id,
                    b.conductor_id,
                    r.stops_json,
                    r.route_no AS route_number,
                    d.full_name AS driver_name,
                    d.license_no AS driver_license,
                    c.full_name AS conductor_name,
                    c.private_conductor_id AS conductor_id_display,
                    -- Real location from latest tracking snapshot
                    tm_latest.lat               AS live_lat,
                    tm_latest.lng               AS live_lng,
                    tm_latest.speed             AS live_speed,
                    tm_latest.operational_status AS live_status,
                    tm_latest.snapshot_at       AS live_snapshot_at
                FROM private_buses b
                LEFT JOIN timetables t 
                    ON t.bus_reg_no = b.reg_no 
                    AND t.operator_type = 'Private'
                LEFT JOIN routes r 
                    ON r.route_id = t.route_id
                LEFT JOIN private_drivers d
                    ON d.private_driver_id = b.driver_id
                LEFT JOIN private_conductors c
                    ON c.private_conductor_id = b.conductor_id
                -- Latest tracking snapshot per bus (subquery avoids GROUP BY issues)
                LEFT JOIN (
                    SELECT tm.bus_reg_no,
                           tm.lat,
                           tm.lng,
                           tm.speed,
                           tm.operational_status,
                           tm.snapshot_at
                    FROM tracking_monitoring tm
                    INNER JOIN (
                        SELECT bus_reg_no, MAX(snapshot_at) AS max_snap
                        FROM   tracking_monitoring
                        GROUP  BY bus_reg_no
                    ) latest ON latest.bus_reg_no = tm.bus_reg_no
                              AND latest.max_snap  = tm.snapshot_at
                ) tm_latest ON tm_latest.bus_reg_no = b.reg_no";

        $params = [];
        if ($this->hasOperator()) {
            $sql .= " WHERE b.private_operator_id = :op";
            $params[':op'] = $this->operatorId;
        }

        $sql .= " GROUP BY b.reg_no ORDER BY b.reg_no DESC";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['route'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');

            // Build a human-readable current_location from real tracking data
            $lat  = $r['live_lat']  !== null ? (float)$r['live_lat']  : null;
            $lng  = $r['live_lng']  !== null ? (float)$r['live_lng']  : null;
            $snap = $r['live_snapshot_at'] ?? null;

            if ($lat !== null && $lng !== null) {
                // Format: "6.9271° N, 79.8612° E  · 2 min ago"
                $latStr = abs($lat) . '° ' . ($lat >= 0 ? 'N' : 'S');
                $lngStr = abs($lng) . '° ' . ($lng >= 0 ? 'E' : 'W');
                $age    = $snap ? $this->formatAge($snap) : '';
                $r['current_location'] = $latStr . ',  ' . $lngStr . ($age ? '  · ' . $age : '');
                $r['has_live_location'] = true;
            } else {
                $r['current_location']  = null; // view will show "No tracking data"
                $r['has_live_location'] = false;
            }
        }
        return $rows;
    }

    /** Return human-friendly age string for a snapshot timestamp */
    private function formatAge(string $snapshot): string
    {
        $diff = time() - strtotime($snapshot);
        if ($diff < 0)   return 'just now';
        if ($diff < 60)  return $diff . 's ago';
        if ($diff < 3600) return round($diff / 60) . ' min ago';
        if ($diff < 86400) return round($diff / 3600) . 'h ago';
        return round($diff / 86400) . 'd ago';
    }

    /** Create a new private bus */
    public function create(array $d): bool
    {
        $sql = "INSERT INTO private_buses 
                    (reg_no, private_operator_id, chassis_no, manufactured_date, manufactured_year, model, bus_class, capacity, status)
                VALUES (:reg_no, :op, :chassis_no, :manufactured_date, :manufactured_year, :model, :bus_class, :capacity, :status)";
        $st = $this->pdo->prepare($sql);
        try {
            return $st->execute([
                ':reg_no'     => $d['reg_no'] ?? null,
                ':op'         => $d['private_operator_id'] ?? $this->operatorId,
                ':chassis_no' => $d['chassis_no'] ?? null,
                ':manufactured_date' => $d['manufactured_date'] ?? null,
                ':manufactured_year' => $this->normalizeYear($d['manufactured_year'] ?? null),
                ':model'      => $d['model'] ?? null,
                ':bus_class'  => $this->normalizeBusClass($d['bus_class'] ?? null),
                ':capacity'   => isset($d['capacity']) ? (int)$d['capacity'] : null,
                ':status'     => $d['status'] ?? 'Active',
            ]);
        } catch (\PDOException $e) {
            // Duplicate primary key (SQLSTATE 23000 / 1062)
            if ($e->getCode() === '23000' || $e->getCode() === 23000) {
                return false; // caller will redirect with error
            }
            throw $e; // re-throw unexpected errors
        }
    }

    /** Update an existing bus record */
    public function update(string $regNo, array $d): bool
    {
        $newStatus = $d['status'] ?? 'Active';
        $isLocked  = in_array(strtolower($newStatus), ['maintenance', 'inactive'], true);

        // When setting Maintenance / Out-of-Service, release assigned driver & conductor
        // so they become available for other buses.
        $releaseStaff = $isLocked ? ",\n                       driver_id    = NULL,\n                       conductor_id = NULL" : '';

        $sql = "UPDATE private_buses
                   SET chassis_no        = :chassis_no,
                       manufactured_date = :manufactured_date,
                       manufactured_year = :manufactured_year,
                       model      = :model,
                       bus_class  = :bus_class,
                       capacity   = :capacity,
                       status     = :status{$releaseStaff}
                 WHERE reg_no = :reg_no";

        $params = [
            ':chassis_no'         => $d['chassis_no'] ?? null,
            ':manufactured_date'  => $d['manufactured_date'] ?? null,
            ':manufactured_year'  => $this->normalizeYear($d['manufactured_year'] ?? null),
            ':model'              => $d['model'] ?? null,
            ':bus_class'          => $this->normalizeBusClass($d['bus_class'] ?? null),
            ':capacity'           => isset($d['capacity']) ? (int)$d['capacity'] : null,
            ':status'             => $newStatus,
            ':reg_no'             => $regNo,
        ];

        if ($this->hasOperator()) {
            $sql .= " AND private_operator_id = :op";
            $params[':op'] = $this->operatorId;
        }

        $st = $this->pdo->prepare($sql);
        return $st->execute($params);
    }

    /** Delete a bus record (only from own fleet) — cascades child rows first */
    public function delete(string $regNo): bool
    {
        // Verify the bus belongs to this operator before doing anything
        if ($this->hasOperator()) {
            $chk = $this->pdo->prepare(
                "SELECT 1 FROM private_buses WHERE reg_no = :r AND private_operator_id = :op LIMIT 1"
            );
            $chk->execute([':r' => $regNo, ':op' => $this->operatorId]);
            if (!$chk->fetchColumn()) return false; // not yours
        }

        $this->pdo->beginTransaction();
        try {
            // 1. FK-constrained child tables (must go first)
            $this->pdo->prepare("DELETE FROM private_trips       WHERE bus_reg_no = :r")->execute([':r' => $regNo]);
            $this->pdo->prepare("DELETE FROM private_assignments WHERE bus_reg_no = :r")->execute([':r' => $regNo]);

            // 2. Non-FK tables that reference this bus
            $this->pdo->prepare("DELETE FROM timetables WHERE bus_reg_no = :r AND operator_type = 'Private'")->execute([':r' => $regNo]);
            $this->pdo->prepare("DELETE FROM earnings    WHERE bus_reg_no = :r AND operator_type = 'Private'")->execute([':r' => $regNo]);

            // 3. Finally delete the bus itself
            $sql    = "DELETE FROM private_buses WHERE reg_no = :r";
            $params = [':r' => $regNo];
            if ($this->hasOperator()) {
                $sql .= " AND private_operator_id = :op";
                $params[':op'] = $this->operatorId;
            }
            $st = $this->pdo->prepare($sql);
            $st->execute($params);

            $this->pdo->commit();
            return $st->rowCount() > 0;

        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            error_log('[BusModel::delete] ' . $e->getMessage());
            throw $e;
        }
    }

    /** Assign driver and/or conductor to a bus */
    public function isMaintenanceBus(string $regNo): bool
    {
        $sql = "SELECT status FROM private_buses WHERE reg_no = :reg_no";
        $params = [':reg_no' => $regNo];

        if ($this->hasOperator()) {
            $sql .= " AND private_operator_id = :op";
            $params[':op'] = $this->operatorId;
        }

        $sql .= " LIMIT 1";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $status = (string)($st->fetchColumn() ?: '');
        return in_array(strtolower($status), ['maintenance', 'inactive'], true);
    }

    public function assignDriverConductor(string $regNo, ?int $driverId, ?int $conductorId): bool
    {
        $sql = "UPDATE private_buses
                   SET driver_id = :driver_id,
                       conductor_id = :conductor_id
                 WHERE reg_no = :reg_no
                                     AND status NOT IN ('Maintenance', 'Inactive')";

        $params = [
            ':driver_id'    => $driverId,
            ':conductor_id' => $conductorId,
            ':reg_no'       => $regNo,
        ];

        if ($this->hasOperator()) {
            $sql .= " AND private_operator_id = :op";
            $params[':op'] = $this->operatorId;
        }

        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $affected = $st->rowCount();
            if ($affected === 0) {
                error_log("[BusModel] assignDriverConductor: 0 rows affected. reg_no={$regNo}, operatorId={$this->operatorId}, driverId={$driverId}, conductorId={$conductorId}");
            }
            return $affected > 0;
        } catch (\PDOException $e) {
            error_log("[BusModel] assignDriverConductor PDOException: " . $e->getMessage() . " | reg_no={$regNo}");
            return false;
        }
    }

    /** Count buses for the current operator */
    public function getCount(): int
    {
        if ($this->hasOperator()) {
            $st = $this->pdo->prepare(
                "SELECT COUNT(*) FROM private_buses WHERE private_operator_id = :op"
            );
            $st->execute([':op' => $this->operatorId]);
            return (int)$st->fetchColumn();
        }
        return (int)$this->pdo->query("SELECT COUNT(*) FROM private_buses")->fetchColumn();
    }

    /** Count buses by status (Active, Maintenance, Inactive) */
    public function getCountByStatus(string $status): int
    {
        $sql = "SELECT COUNT(*) FROM private_buses WHERE status = :s";
        $params = [':s' => $status];
        if ($this->hasOperator()) {
            $sql .= " AND private_operator_id = :op";
            $params[':op'] = $this->operatorId;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    /** Recent buses for dashboard cards */
    public function getRecent(int $limit = 5): array
    {
        $sql = "SELECT 
                    b.reg_no AS bus_number,
                    b.chassis_no,
                    b.manufactured_date,
                    b.manufactured_year,
                    b.model,
                    b.bus_class,
                    b.capacity,
                    b.status,
                    r.stops_json,
                    r.route_no AS route_number
                FROM private_buses b
                LEFT JOIN timetables t 
                    ON t.bus_reg_no = b.reg_no 
                    AND t.operator_type = 'Private'
                LEFT JOIN routes r 
                    ON r.route_id = t.route_id";

        $params = [];
        if ($this->hasOperator()) {
            $sql .= " WHERE b.private_operator_id = :op";
            $params[':op'] = $this->operatorId;
        }

        $sql .= " GROUP BY b.reg_no ORDER BY b.reg_no DESC LIMIT " . max(1, (int)$limit);

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['route'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
        }
        return $rows;
    }
}
?>
