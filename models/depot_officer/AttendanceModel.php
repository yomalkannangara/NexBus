<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class AttendanceModel extends BaseModel
{
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
            $ins = $this->pdo->prepare(
                "INSERT INTO depot_attendance(sltb_depot_id, attendance_key, work_date, mark_absent, notes)
                 VALUES(?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE mark_absent=VALUES(mark_absent), notes=VALUES(notes), updated_at=NOW()"
            );
            $this->pdo->beginTransaction();
            foreach ($mark as $akey => $row) {
                $akey = (string)$akey;
                if (!in_array($akey, $validStaff, true)) continue;
                $abs = (int)!empty($row['absent']);
                $notes = trim($row['notes'] ?? '');
                $ins->execute([$depotId, $akey, $date, $abs, $notes]);
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
                $abs = (int)!empty($row['absent']);
                $notes = trim($row['notes'] ?? '');
                $insUser->execute([$uid, $date, $abs, $notes]);
            }
            // non-user keys (driver: / conductor:) are skipped until migration runs
        }
        $this->pdo->commit();
    }
}