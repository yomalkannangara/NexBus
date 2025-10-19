<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class AssignmentModel extends BaseModel
{
    public function todayAssignments(int $depotId): array {
        $sql = "SELECT tt.*, r.route_no FROM timetables tt
                JOIN sltb_buses b ON b.reg_no=tt.bus_reg_no AND b.sltb_depot_id=?
                LEFT JOIN routes r ON r.route_id=tt.route_id
                WHERE tt.operator_type='SLTB'
                  AND tt.effective_from<=CURDATE()
                  AND (tt.effective_to IS NULL OR tt.effective_to>=CURDATE())
                  AND tt.day_of_week=DAYOFWEEK(CURDATE())-1
                ORDER BY tt.departure_time";
        $st = $this->pdo->prepare($sql);
        $st->execute([$depotId]);
        return $st->fetchAll();
    }

    public function createAssignment(int $depotId, array $d): bool {
        $bus   = trim($d['bus_reg_no'] ?? '');
        $route = (int)($d['route_id'] ?? 0);
        $date  = $d['date'] ?? date('Y-m-d');
        $depT  = $d['departure_time'] ?? '';
        $arrT  = $d['arrival_time'] ?? null;
        if (!$bus || !$route || !$depT) return false;

        $st=$this->pdo->prepare("SELECT COUNT(*) FROM sltb_buses WHERE reg_no=? AND sltb_depot_id=?");
        $st->execute([$bus,$depotId]);
        if (!$st->fetchColumn()) return false;

        $dow = (int)date('w', strtotime($date)); // 0..6

        $st=$this->pdo->prepare(
            "SELECT COUNT(*) FROM timetables
             WHERE operator_type='SLTB' AND bus_reg_no=? AND day_of_week=?
               AND (effective_from IS NULL OR effective_from<=?)
               AND (effective_to IS NULL OR effective_to>=?) AND departure_time=?"
        );
        $st->execute([$bus,$dow,$date,$date,$depT]);
        if ($st->fetchColumn()) return false;

        $sql="INSERT INTO timetables(operator_type, route_id, bus_reg_no, day_of_week, departure_time, arrival_time,
                                     start_seq, end_seq, effective_from, effective_to)
              VALUES ('SLTB',?,?,?,?,?,?,?, ?, NULL)";
        $st=$this->pdo->prepare($sql);
        return $st->execute([$route,$bus,$dow,$depT,$arrT,1,3,$date]);
    }

    public function deleteAssignment(int $depotId, int $ttId): void {
        $sql = "DELETE tt FROM timetables tt
                JOIN sltb_buses b ON b.reg_no=tt.bus_reg_no AND b.sltb_depot_id=?
                WHERE tt.timetable_id=? AND tt.operator_type='SLTB'";
        $st=$this->pdo->prepare($sql);
        $st->execute([$depotId,$ttId]);
    }
}
