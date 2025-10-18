<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class AttendanceModel extends BaseModel
{
    public function forDate(int $depotId, string $date): array {
        $st=$this->pdo->prepare(
            "SELECT a.*, u.full_name, u.role
             FROM staff_attendance a
             JOIN users u ON u.user_id=a.user_id
             WHERE u.sltb_depot_id=? AND a.work_date=?"
        );
        $st->execute([$depotId,$date]);
        $rows=$st->fetchAll();
        $by=[];
        foreach($rows as $r) $by[$r['user_id']]=$r;
        return $by;
    }

    public function markBulk(int $depotId, string $date, array $mark, array $validStaff): void {
        $ins=$this->pdo->prepare(
            "INSERT INTO staff_attendance(user_id,work_date,mark_absent,notes)
             VALUES(?,?,?,?)
             ON DUPLICATE KEY UPDATE mark_absent=VALUES(mark_absent), notes=VALUES(notes)"
        );
        $this->pdo->beginTransaction();
        foreach ($mark as $uid=>$row) {
            $uid=(int)$uid; if (!in_array($uid,$validStaff,true)) continue;
            $abs=(int)!empty($row['absent']); $notes=trim($row['notes'] ?? '');
            $ins->execute([$uid,$date,$abs,$notes]);
        }
        $this->pdo->commit();
    }
}
