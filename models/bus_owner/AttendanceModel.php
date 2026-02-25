<?php
namespace App\models\bus_owner;

use PDO;
use App\models\common\BaseModel;

class AttendanceModel extends BaseModel
{
    private function operatorId(): ?int
    {
        $u = $_SESSION['user'] ?? null;
        return isset($u['private_operator_id']) ? (int)$u['private_operator_id'] : null;
    }

    /** All drivers belonging to this operator */
    public function getDrivers(): array
    {
        $op = $this->operatorId();
        $sql = "SELECT private_driver_id AS id, full_name, status
                FROM private_drivers
                WHERE private_operator_id = :op
                ORDER BY full_name ASC";
        $st = $this->pdo->prepare($sql);
        $st->execute([':op' => $op]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** All conductors belonging to this operator */
    public function getConductors(): array
    {
        $op = $this->operatorId();
        $sql = "SELECT private_conductor_id AS id, full_name, status
                FROM private_conductors
                WHERE private_operator_id = :op
                ORDER BY full_name ASC";
        $st = $this->pdo->prepare($sql);
        $st->execute([':op' => $op]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all attendance records for a given date (for this operator).
     * Returns array keyed by "Driver__{id}" or "Conductor__{id}".
     */
    public function getForDate(string $date): array
    {
        $op = $this->operatorId();
        $sql = "SELECT staff_type, staff_id, status, notes
                FROM private_staff_attendance
                WHERE operator_id = :op AND work_date = :date";
        $st = $this->pdo->prepare($sql);
        $st->execute([':op' => $op, ':date' => $date]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $r) {
            $map[$r['staff_type'] . '__' . $r['staff_id']] = $r;
        }
        return $map;
    }

    /**
     * Save (UPSERT) attendance for a single staff member.
     */
    public function save(string $staffType, int $staffId, string $date, string $status, string $notes = ''): bool
    {
        $op = $this->operatorId();
        $userId = (int)(($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0));

        $sql = "INSERT INTO private_staff_attendance
                    (operator_id, staff_type, staff_id, work_date, status, notes, marked_by)
                VALUES
                    (:op, :type, :sid, :date, :status, :notes, :by)
                ON DUPLICATE KEY UPDATE
                    status     = VALUES(status),
                    notes      = VALUES(notes),
                    marked_by  = VALUES(marked_by),
                    updated_at = CURRENT_TIMESTAMP";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':op'     => $op,
            ':type'   => $staffType,
            ':sid'    => $staffId,
            ':date'   => $date,
            ':status' => $status,
            ':notes'  => $notes,
            ':by'     => $userId ?: null,
        ]);
    }

    /**
     * Bulk save attendance from POST data.
     * Expects $_POST['attendance'][staffType][staffId] = status
     * and      $_POST['notes'][staffType][staffId]     = note
     */
    public function bulkSave(string $date, array $attendancePost, array $notesPost): void
    {
        foreach (['Driver', 'Conductor'] as $type) {
            $entries = $attendancePost[$type] ?? [];
            foreach ($entries as $staffId => $status) {
                $note = $notesPost[$type][$staffId] ?? '';
                $allowed = ['Present', 'Absent', 'Late', 'Half_Day'];
                if (!in_array($status, $allowed, true)) $status = 'Present';
                $this->save($type, (int)$staffId, $date, $status, trim($note));
            }
        }
    }

    /**
     * Attendance summary for the past N days (for stats cards).
     * Returns ['present'=>int, 'absent'=>int, 'late'=>int, 'half'=>int, 'total'=>int]
     */
    public function summary(int $days = 30): array
    {
        $op = $this->operatorId();
        $sql = "SELECT
                    SUM(status='Present')  AS present,
                    SUM(status='Absent')   AS absent,
                    SUM(status='Late')     AS late,
                    SUM(status='Half_Day') AS half,
                    COUNT(*)               AS total
                FROM private_staff_attendance
                WHERE operator_id = :op
                  AND work_date >= CURDATE() - INTERVAL :days DAY";
        $st = $this->pdo->prepare($sql);
        $st->execute([':op' => $op, ':days' => $days]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'present' => (int)($row['present'] ?? 0),
            'absent'  => (int)($row['absent']  ?? 0),
            'late'    => (int)($row['late']    ?? 0),
            'half'    => (int)($row['half']    ?? 0),
            'total'   => (int)($row['total']   ?? 0),
        ];
    }

    /**
     * Recent attendance history for a date range.
     * Returns rows with staff name, type, date, status.
     */
    public function history(string $from, string $to): array
    {
        $op = $this->operatorId();
        $sql = "SELECT
                    a.work_date,
                    a.staff_type,
                    a.staff_id,
                    CASE a.staff_type
                        WHEN 'Driver'    THEN d.full_name
                        WHEN 'Conductor' THEN c.full_name
                    END AS full_name,
                    a.status,
                    a.notes
                FROM private_staff_attendance a
                LEFT JOIN private_drivers    d ON d.private_driver_id    = a.staff_id AND a.staff_type = 'Driver'
                LEFT JOIN private_conductors c ON c.private_conductor_id = a.staff_id AND a.staff_type = 'Conductor'
                WHERE a.operator_id = :op
                  AND a.work_date BETWEEN :from AND :to
                ORDER BY a.work_date DESC, a.staff_type, full_name ASC";
        $st = $this->pdo->prepare($sql);
        $st->execute([':op' => $op, ':from' => $from, ':to' => $to]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
