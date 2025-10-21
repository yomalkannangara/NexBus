<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;

class AttendanceModel extends BaseModel
{
    /** information_schema check for table.column */
    private function colExists(string $table, string $column): bool
    {
        try {
            $db = (string)$this->pdo->query("SELECT DATABASE()")->fetchColumn();
            $st = $this->pdo->prepare(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?"
            );
            $st->execute([$db, $table, $column]);
            return (int)$st->fetchColumn() > 0;
        } catch (\Throwable $e) { return false; }
    }

    /**
     * Build a WHERE fragment for users' depot filter using only existing columns.
     * Populates $params with :dep when a column is used.
     * Returns '1=1' if neither column exists (we’ll post-filter in that case).
     */
    private function usersDepotWhere(int $depotId, array &$params): string
    {
        $conds = [];
        if ($this->colExists('users', 'depot_id')) {
            $conds[] = 'u.depot_id = :dep';
        }
        if ($this->colExists('users', 'sltb_depot_id')) {
            $conds[] = 'u.sltb_depot_id = :dep';
        }
        if ($conds) {
            $params[':dep'] = $depotId;
            return '(' . implode(' OR ', $conds) . ')';
        }
        return '1=1';
    }

    public function staffList(int $depotId): array
    {
        if ($depotId <= 0) return [];

        $params = [];
        $where  = $this->usersDepotWhere($depotId, $params);

        // Try to get by depot columns that actually exist
        $sql = "SELECT u.user_id, u.full_name, u.role
                FROM users u
                WHERE {$where} AND u.role IN ('SLTBTimekeeper')
                ORDER BY u.full_name";
        $st  = $this->pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();
        if ($rows && $where !== '1=1') return $rows;

        // Optional fallback via mapping table if present
        try {
            $st = $this->pdo->prepare(
                "SELECT u.user_id, u.full_name, u.role
                 FROM users u
                 JOIN sltb_depot_users m ON m.user_id = u.user_id AND m.sltb_depot_id = ?
                 WHERE u.role IN ('SLTBTimekeeper')
                 ORDER BY u.full_name"
            );
            $st->execute([$depotId]);
            $rows = $st->fetchAll();
            if ($rows) return $rows;
        } catch (\Throwable $e) {
            // mapping table may not exist; ignore
        }

        // Nothing matched
        return [];
    }

    public function attendanceForDate(int $depotId, string $date): array
    {
        if ($depotId <= 0) return [];

        $params = [':date' => $date];
        $where  = $this->usersDepotWhere($depotId, $params);

        if ($where !== '1=1') {
            // Safe query: only includes columns that actually exist
            $sql = "SELECT a.*, u.full_name, u.role
                    FROM staff_attendance a
                    JOIN users u ON u.user_id = a.user_id
                    WHERE {$where} AND a.work_date = :date
                    ORDER BY u.full_name";
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            $rows = $st->fetchAll();
        } else {
            // If neither depot column exists: get all for the date, then post-filter to staff of this depot
            $st = $this->pdo->prepare("SELECT * FROM staff_attendance WHERE work_date = :date");
            $st->execute([':date' => $date]);
            $rows  = $st->fetchAll();
            $valid = array_column($this->staffList($depotId), 'user_id');
            $rows  = array_values(array_filter($rows, fn($r) => in_array((int)$r['user_id'], $valid, true)));

            // Enrich with user fields for UI parity
            if ($rows) {
                $in = implode(',', array_fill(0, count($valid), '?'));
                try {
                    $q  = $this->pdo->prepare("SELECT user_id, full_name, role FROM users WHERE user_id IN ($in)");
                    $q->execute($valid);
                    $info = [];
                    foreach ($q->fetchAll() as $u) $info[(int)$u['user_id']] = $u;
                    foreach ($rows as &$r) {
                        $u = $info[(int)$r['user_id']] ?? ['full_name'=>'','role'=>''];
                        $r['full_name'] = $u['full_name'];
                        $r['role']      = $u['role'];
                    }
                } catch (\Throwable $e) {}
            }
        }

        // Index by user_id for easy lookup in the view
        $by = [];
        foreach ($rows as $r) {
            $by[(int)$r['user_id']] = $r;
        }
        return $by;
    }

    public function markAttendance(int $depotId, string $date, array $mark): void
    {
        if ($depotId <= 0) return;

        $valid = array_column($this->staffList($depotId), 'user_id');
        if (!$valid) return;

        $ins = $this->pdo->prepare(
            "INSERT INTO staff_attendance(user_id, work_date, mark_absent, notes)
             VALUES(?,?,?,?)
             ON DUPLICATE KEY UPDATE
                mark_absent = VALUES(mark_absent),
                notes       = VALUES(notes)"
        );

        $this->pdo->beginTransaction();
        foreach ($mark as $uid => $row) {
            $uid = (int)$uid;
            if (!in_array($uid, $valid, true)) continue;
            $abs   = (int)!empty($row['absent']);
            $notes = trim($row['notes'] ?? '');
            $ins->execute([$uid, $date, $abs, $notes]);
        }
        $this->pdo->commit();
    }
}
