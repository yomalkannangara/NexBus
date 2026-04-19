<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class AttendanceModel extends BaseModel
{
    private function normalizeStatus(string $status): string
    {
        $allowed = ['Present', 'Absent', 'Late', 'Half_Day'];
        return in_array($status, $allowed, true) ? $status : 'Present';
    }

    private function statusFromLegacy(array $row): string
    {
        $raw = trim((string)($row['notes'] ?? ''));
        if (preg_match('/^\[STATUS:(Present|Absent|Late|Half_Day)\]\s*/', $raw, $m)) {
            return $this->normalizeStatus($m[1]);
        }
        return !empty($row['mark_absent']) ? 'Absent' : 'Present';
    }

    private function cleanNotes(array $row): string
    {
        $raw = trim((string)($row['notes'] ?? ''));
        return preg_replace('/^\[STATUS:(Present|Absent|Late|Half_Day)\]\s*/', '', $raw) ?? $raw;
    }

    private function hasDepotStatusColumn(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }

        try {
            $st = $this->pdo->query("SHOW COLUMNS FROM depot_attendance LIKE 'status'");
            $has = (bool)$st->fetch();
        } catch (\Throwable $e) {
            $has = false;
        }

        return $has;
    }

    public function forDate(int $depotId, string $date): array {
        // Prefer new depot_attendance table (keyed by attendance_key).
        // If the table doesn't exist yet, fall back to legacy staff_attendance
        // joined with users and return keys as 'user:<id>' so callers can
        // match both user-based and sltb driver/conductor keys.
        try {
            $st = $this->pdo->prepare("SELECT * FROM depot_attendance WHERE sltb_depot_id=? AND work_date=?");
            $st->execute([$depotId, $date]);
            $rows = $st->fetchAll();
            $by = [];
            foreach ($rows as $r) {
                if (!isset($r['status'])) {
                    $r['status'] = $this->statusFromLegacy($r);
                } else {
                    $r['status'] = $this->normalizeStatus((string)$r['status']);
                }
                $r['notes'] = $this->cleanNotes($r);
                $by[$r['attendance_key']] = $r;
            }
            return $by;
        } catch (\PDOException $e) {
            // Table missing (SQLSTATE 42S02) — fallback
            if ($e->getCode() !== '42S02') throw $e;
        }

        // Legacy fallback: read staff_attendance (user accounts only)
        $st = $this->pdo->prepare(
            "SELECT a.*, u.user_id
             FROM staff_attendance a
             JOIN users u ON u.user_id = a.user_id
             WHERE u.sltb_depot_id=? AND a.work_date=?"
        );
        $st->execute([$depotId, $date]);
        $rows = $st->fetchAll();
        $by = [];
        foreach ($rows as $r) {
            $key = 'user:' . (int)$r['user_id'];
            $r['status'] = $this->statusFromLegacy($r);
            $r['notes'] = $this->cleanNotes($r);
            $by[$key] = $r;
        }
        return $by;
    }

    public function markBulk(int $depotId, string $date, array $mark, array $validStaff): void {
        // If depot_attendance exists, write into it. Otherwise fallback to
        // writing to legacy staff_attendance for user:<id> keys only.
        $useDepot = true;
        try {
            $this->pdo->query('SELECT 1 FROM depot_attendance LIMIT 1');
        } catch (\PDOException $e) {
            $useDepot = false;
        }

        if ($useDepot) {
            $hasStatus = $this->hasDepotStatusColumn();
            $ins = $hasStatus
                ? $this->pdo->prepare(
                    "INSERT INTO depot_attendance(sltb_depot_id, attendance_key, work_date, mark_absent, status, notes)
                     VALUES(?,?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE mark_absent=VALUES(mark_absent), status=VALUES(status), notes=VALUES(notes), updated_at=NOW()"
                )
                : $this->pdo->prepare(
                    "INSERT INTO depot_attendance(sltb_depot_id, attendance_key, work_date, mark_absent, notes)
                     VALUES(?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE mark_absent=VALUES(mark_absent), notes=VALUES(notes), updated_at=NOW()"
                );
            $this->pdo->beginTransaction();
            foreach ($mark as $akey => $row) {
                $akey = (string)$akey;
                if (!in_array($akey, $validStaff, true)) continue;
                $status = $this->normalizeStatus((string)($row['status'] ?? 'Present'));
                if (isset($row['absent']) && (int)$row['absent'] === 1) {
                    $status = 'Absent';
                }
                $abs = (int)($status === 'Absent');
                $notes = trim((string)($row['notes'] ?? ''));
                if (!$hasStatus && $status !== 'Present' && $status !== 'Absent') {
                    $notes = '[STATUS:' . $status . '] ' . $notes;
                }
                if ($hasStatus) {
                    $ins->execute([$depotId, $akey, $date, $abs, $status, $notes]);
                } else {
                    $ins->execute([$depotId, $akey, $date, $abs, $notes]);
                }
            }
            $this->pdo->commit();
            return;
        }

        // Fallback: only process user:<id> keys and write into staff_attendance
        $insUser = $this->pdo->prepare(
            "INSERT INTO staff_attendance(user_id, work_date, mark_absent, notes)
             VALUES(?,?,?,?)
             ON DUPLICATE KEY UPDATE mark_absent=VALUES(mark_absent), notes=VALUES(notes)"
        );
        $this->pdo->beginTransaction();
        foreach ($mark as $akey => $row) {
            $akey = (string)$akey;
            if (!in_array($akey, $validStaff, true)) continue;
            if (str_starts_with($akey, 'user:')) {
                $uid = (int)substr($akey, 5);
                $status = $this->normalizeStatus((string)($row['status'] ?? 'Present'));
                if (isset($row['absent']) && (int)$row['absent'] === 1) {
                    $status = 'Absent';
                }
                $abs = (int)($status === 'Absent');
                $notes = trim((string)($row['notes'] ?? ''));
                if ($status !== 'Present' && $status !== 'Absent') {
                    $notes = '[STATUS:' . $status . '] ' . $notes;
                }
                $insUser->execute([$uid, $date, $abs, $notes]);
            }
            // non-user keys (driver: / conductor:) are skipped until migration runs
        }
        $this->pdo->commit();
    }

    public function summary(int $depotId, int $days = 30): array
    {
        $from = date('Y-m-d', strtotime('-' . max(1, $days) . ' days'));
        $to = date('Y-m-d');
        $counts = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Half_Day' => 0];

        try {
            $st = $this->pdo->prepare(
                "SELECT mark_absent, notes" . ($this->hasDepotStatusColumn() ? ", status" : "") . "
                 FROM depot_attendance
                 WHERE sltb_depot_id=? AND work_date BETWEEN ? AND ?"
            );
            $st->execute([$depotId, $from, $to]);
            foreach ($st->fetchAll() as $row) {
                $status = isset($row['status'])
                    ? $this->normalizeStatus((string)$row['status'])
                    : $this->statusFromLegacy($row);
                $counts[$status] = ($counts[$status] ?? 0) + 1;
            }
        } catch (\PDOException $e) {
            if ($e->getCode() !== '42S02') {
                throw $e;
            }
            $st = $this->pdo->prepare(
                "SELECT a.mark_absent, a.notes
                 FROM staff_attendance a
                 JOIN users u ON u.user_id = a.user_id
                 WHERE u.sltb_depot_id=? AND a.work_date BETWEEN ? AND ?"
            );
            $st->execute([$depotId, $from, $to]);
            foreach ($st->fetchAll() as $row) {
                $status = $this->statusFromLegacy($row);
                $counts[$status] = ($counts[$status] ?? 0) + 1;
            }
        }

        return [
            'present' => (int)$counts['Present'],
            'absent' => (int)$counts['Absent'],
            'late' => (int)$counts['Late'],
            'half' => (int)$counts['Half_Day'],
            'total' => (int)array_sum($counts),
        ];
    }

    public function trendByDay(int $depotId, string $from, string $to, ?string $akey = null, ?string $role = null): array
    {
        $params = [$depotId, $from, $to];
        $where  = '';
        if ($akey !== null && $akey !== '') {
            $where   .= ' AND a.attendance_key = ?';
            $params[] = $akey;
        } elseif ($role !== null && $role !== '' && $role !== 'all') {
            $prefix   = ($role === 'conductor') ? 'conductor:' : 'driver:';
            $where   .= ' AND a.attendance_key LIKE ?';
            $params[] = $prefix . '%';
        }

        try {
            if ($this->hasDepotStatusColumn()) {
                $sql = "SELECT a.work_date,
                    SUM(CASE WHEN a.status='Present'  THEN 1 ELSE 0 END) AS present,
                    SUM(CASE WHEN a.status='Absent'   THEN 1 ELSE 0 END) AS absent,
                    SUM(CASE WHEN a.status='Late'     THEN 1 ELSE 0 END) AS late,
                    SUM(CASE WHEN a.status='Half_Day' THEN 1 ELSE 0 END) AS half_day,
                    COUNT(*) AS total
                FROM depot_attendance a
                WHERE a.sltb_depot_id=? AND a.work_date BETWEEN ? AND ?" . $where . "
                GROUP BY a.work_date ORDER BY a.work_date";
                $st = $this->pdo->prepare($sql);
                $st->execute($params);
                return array_map(fn($r) => [
                    'date'     => $r['work_date'],
                    'present'  => (int)$r['present'],
                    'absent'   => (int)$r['absent'],
                    'late'     => (int)$r['late'],
                    'half_day' => (int)$r['half_day'],
                    'total'    => (int)$r['total'],
                ], $st->fetchAll());
            }

            // Legacy: fetch raw rows, aggregate in PHP
            $sql = "SELECT a.work_date, a.mark_absent, a.notes
                FROM depot_attendance a
                WHERE a.sltb_depot_id=? AND a.work_date BETWEEN ? AND ?" . $where . "
                ORDER BY a.work_date";
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $byDay = [];
            foreach ($st->fetchAll() as $r) {
                $d = $r['work_date'];
                if (!isset($byDay[$d])) {
                    $byDay[$d] = ['date' => $d, 'present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0, 'total' => 0];
                }
                $status = $this->statusFromLegacy($r);
                $key = match ($status) {
                    'Absent'   => 'absent',
                    'Late'     => 'late',
                    'Half_Day' => 'half_day',
                    default    => 'present',
                };
                $byDay[$d][$key]++;
                $byDay[$d]['total']++;
            }
            return array_values($byDay);
        } catch (\PDOException $e) {
            if ($e->getCode() === '42S02') return [];
            throw $e;
        }
    }

    public function savedAt(int $depotId, string $date): ?string
    {
        try {
            $st = $this->pdo->prepare(
                "SELECT MAX(updated_at) FROM depot_attendance WHERE sltb_depot_id=? AND work_date=?"
            );
            $st->execute([$depotId, $date]);
            $val = $st->fetchColumn();
            return ($val && $val !== '0000-00-00 00:00:00') ? (string)$val : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function history(int $depotId, string $from, string $to): array
    {
        try {
            $sql = "SELECT
                        a.work_date,
                        a.attendance_key,
                        a.mark_absent,
                        a.notes" . ($this->hasDepotStatusColumn() ? ", a.status" : "") . ",
                        a.updated_at,
                        d.full_name AS driver_name,
                        c.full_name AS conductor_name,
                        CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS user_name,
                        u.role AS user_role
                    FROM depot_attendance a
                    LEFT JOIN sltb_drivers d
                      ON a.attendance_key LIKE 'driver:%'
                     AND d.sltb_driver_id = CAST(SUBSTRING_INDEX(a.attendance_key, ':', -1) AS UNSIGNED)
                    LEFT JOIN sltb_conductors c
                      ON a.attendance_key LIKE 'conductor:%'
                     AND c.sltb_conductor_id = CAST(SUBSTRING_INDEX(a.attendance_key, ':', -1) AS UNSIGNED)
                    LEFT JOIN users u
                      ON a.attendance_key LIKE 'user:%'
                     AND u.user_id = CAST(SUBSTRING_INDEX(a.attendance_key, ':', -1) AS UNSIGNED)
                    WHERE a.sltb_depot_id=?
                      AND a.work_date BETWEEN ? AND ?
                    ORDER BY a.work_date DESC, a.attendance_key";
            $st = $this->pdo->prepare($sql);
            $st->execute([$depotId, $from, $to]);

            $out = [];
            foreach ($st->fetchAll() as $row) {
                $key = (string)($row['attendance_key'] ?? '');
                $staffType = str_starts_with($key, 'conductor:') ? 'Conductor' : 'Driver';
                if (str_starts_with($key, 'user:') && strcasecmp((string)($row['user_role'] ?? ''), 'Conductor') === 0) {
                    $staffType = 'Conductor';
                }

                $fullName = trim((string)($row['driver_name'] ?? ''));
                if ($staffType === 'Conductor') {
                    $fullName = trim((string)($row['conductor_name'] ?? ''));
                }
                if ($fullName === '') {
                    $fullName = trim((string)($row['user_name'] ?? ''));
                }

                $status = isset($row['status'])
                    ? $this->normalizeStatus((string)$row['status'])
                    : $this->statusFromLegacy($row);

                $out[] = [
                    'work_date' => $row['work_date'],
                    'staff_type' => $staffType,
                    'full_name' => $fullName !== '' ? $fullName : 'Unknown',
                    'status' => $status,
                    'notes' => $this->cleanNotes($row),
                    'updated_at' => $row['updated_at'] ?? null,
                ];
            }

            return $out;
        } catch (\PDOException $e) {
            if ($e->getCode() !== '42S02') {
                throw $e;
            }
        }

        $st = $this->pdo->prepare(
            "SELECT
                a.work_date,
                CASE WHEN u.role='Conductor' THEN 'Conductor' ELSE 'Driver' END AS staff_type,
                CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS full_name,
                a.mark_absent,
                a.notes
             FROM staff_attendance a
             JOIN users u ON u.user_id = a.user_id
             WHERE u.sltb_depot_id=?
               AND a.work_date BETWEEN ? AND ?
             ORDER BY a.work_date DESC, full_name ASC"
        );
        $st->execute([$depotId, $from, $to]);
        $out = [];
        foreach ($st->fetchAll() as $row) {
            $out[] = [
                'work_date' => $row['work_date'],
                'staff_type' => $row['staff_type'] ?: 'Driver',
                'full_name' => trim((string)($row['full_name'] ?? '')) ?: 'Unknown',
                'status' => $this->statusFromLegacy($row),
                'notes' => $this->cleanNotes($row),
            ];
        }
        return $out;
    }
}