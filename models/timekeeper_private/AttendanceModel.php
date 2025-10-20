<?php
namespace App\models\timekeeper_private;

use App\models\common\BaseModel;

class AttendanceModel extends BaseModel
{
    /**
     * Return Private Timekeepers for a depot, tolerant to column naming.
     */
    public function staffList(int $depotId): array
    {
        if (!$depotId) return [];

        // 1) users.depot_id
        try {
            $st = $this->pdo->prepare(
                "SELECT user_id, full_name, role
                 FROM users
                 WHERE depot_id=? AND role IN ('PrivateTimekeeper')
                 ORDER BY full_name"
            );
            $st->execute([$depotId]);
            $rows = $st->fetchAll();
            if ($rows) return $rows;
        } catch (\Throwable $e) { /* column may not exist */ }

        // 2) users.sltb_depot_id
        try {
            $st = $this->pdo->prepare(
                "SELECT user_id, full_name, role
                 FROM users
                 WHERE sltb_depot_id=? AND role IN ('PrivateTimekeeper')
                 ORDER BY full_name"
            );
            $st->execute([$depotId]);
            $rows = $st->fetchAll();
            if ($rows) return $rows;
        } catch (\Throwable $e) { /* column may not exist */ }

        // 3) Optional mapping table: private_depot_users(user_id,depot_id)
        try {
            $st = $this->pdo->prepare(
                "SELECT u.user_id, u.full_name, u.role
                 FROM users u
                 JOIN private_depot_users m ON m.user_id=u.user_id AND m.depot_id=?
                 WHERE u.role IN ('PrivateTimekeeper')
                 ORDER BY u.full_name"
            );
            $st->execute([$depotId]);
            $rows = $st->fetchAll();
            if ($rows) return $rows;
        } catch (\Throwable $e) { /* table may not exist */ }

        return [];
    }

    /**
     * Get attendance marks for a date. We filter by depot via JOIN on users,
     * so we don't need a depot_id column on staff_attendance.
     */
    public function attendanceForDate(int $depotId, string $date): array
    {
        // Try with both possible user columns
        try {
            $sql = "SELECT a.*, u.full_name, u.role
                    FROM staff_attendance a
                    JOIN users u ON u.user_id=a.user_id
                    WHERE (u.depot_id=? OR u.sltb_depot_id=?) AND a.work_date=?
                    ORDER BY u.full_name";
            $st = $this->pdo->prepare($sql);
            $st->execute([$depotId, $depotId, $date]);
            $rows = $st->fetchAll();
        } catch (\Throwable $e) {
            // Fallback: pull all for the date and filter in PHP with staff list
            $st = $this->pdo->prepare("SELECT * FROM staff_attendance WHERE work_date=?");
            $st->execute([$date]);
            $rows = $st->fetchAll();
            $valid = array_column($this->staffList($depotId), 'user_id');
            $rows = array_values(array_filter($rows, fn($r) => in_array((int)$r['user_id'], $valid, true)));
        }

        $by = [];
        foreach ($rows as $r) $by[(int)$r['user_id']] = $r;
        return $by;
    }

    /**
     * Insert/Update marks. No depot_id used — uniqueness should be (user_id, work_date).
     */
    public function markAttendance(int $depotId, string $date, array $mark): void
    {
        $validIds = array_column($this->staffList($depotId), 'user_id');

        $ins = $this->pdo->prepare(
            "INSERT INTO staff_attendance(user_id, work_date, mark_absent, notes)
             VALUES(?,?,?,?)
             ON DUPLICATE KEY UPDATE
               mark_absent=VALUES(mark_absent),
               notes=VALUES(notes)"
        );

        $this->pdo->beginTransaction();
        foreach ($mark as $uid => $row) {
            $uid = (int)$uid;
            if (!in_array($uid, $validIds, true)) continue;
            $abs   = !empty($row['absent']) ? 1 : 0;
            $notes = trim($row['notes'] ?? '');
            $ins->execute([$uid, $date, $abs, $notes]);
        }
        $this->pdo->commit();
    }
}
