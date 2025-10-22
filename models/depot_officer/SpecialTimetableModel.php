<?php
namespace App\models\depot_officer;

use App\models\common\BaseModel;

class SpecialTimetableModel extends BaseModel
{
    public function createSpecial(int $depotId, array $d): bool {
        $bus   = trim($d['bus_reg_no'] ?? '');
        $route = (int)($d['route_id'] ?? 0);
        $from  = $d['effective_from'] ?? null;
        $to    = $d['effective_to']   ?? null;
        $depT  = $d['departure_time'] ?? '';
        $arrT  = $d['arrival_time'] ?? null;
        $dow   = (int)($d['day_of_week'] ?? -1);
        if ($dow<0 || !$bus || !$route || !$from || !$depT) return false;

        $st=$this->pdo->prepare("SELECT COUNT(*) FROM sltb_buses WHERE reg_no=? AND sltb_depot_id=?");
        $st->execute([$bus,$depotId]);
        if (!$st->fetchColumn()) return false;

        $sql="INSERT INTO timetables(operator_type, route_id, bus_reg_no, day_of_week, departure_time, arrival_time,
                                     start_seq, end_seq, effective_from, effective_to)
              VALUES ('SLTB',?,?,?,?,?,?,?, ?, ?)";
        $st=$this->pdo->prepare($sql);
        return $st->execute([$route,$bus,$dow,$depT,$arrT,1,3,$from,$to ?: null]);
    }

    public function deleteSpecial(int $depotId, int $ttId): void {
        $sql = "DELETE tt FROM timetables tt
                JOIN sltb_buses b ON b.reg_no=tt.bus_reg_no AND b.sltb_depot_id=?
                WHERE tt.timetable_id=? AND tt.operator_type='SLTB'";
        $st=$this->pdo->prepare($sql);
        $st->execute([$depotId,$ttId]);
    }

    public function listSpecial(int $depotId): array {
        $sql = "SELECT tt.*, r.route_no FROM timetables tt
                JOIN sltb_buses b ON b.reg_no=tt.bus_reg_no AND b.sltb_depot_id=?
                LEFT JOIN routes r ON r.route_id=tt.route_id
                WHERE tt.operator_type='SLTB'
                  AND (tt.effective_from IS NOT NULL OR tt.effective_to IS NOT NULL)
                ORDER BY tt.effective_from DESC, tt.departure_time";
        $st=$this->pdo->prepare($sql);
        $st->execute([$depotId]);
        return $st->fetchAll();
    }

    public function updateSpecial(int $depotId, array $d): bool {
        $ttId  = (int)($d['timetable_id'] ?? 0);
        $bus   = trim($d['bus_reg_no'] ?? '');
        $route = (int)($d['route_id'] ?? 0);
        $from  = $d['effective_from'] ?? null;
        $to    = $d['effective_to']   ?? null;
        $depT  = $d['departure_time'] ?? '';
        $arrT  = $d['arrival_time'] ?? null;
        $dow   = (int)($d['day_of_week'] ?? -1);

        if ($ttId <= 0 || $dow < 0 || $dow > 6 || !$bus || !$route || !$from || !$depT) return false;

        // Ensure the timetable belongs to this depot
        $st = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM timetables tt
            JOIN sltb_buses b ON b.reg_no = tt.bus_reg_no
            WHERE tt.timetable_id = ? AND tt.operator_type='SLTB' AND b.sltb_depot_id = ?
        ");
        $st->execute([$ttId, $depotId]);
        if (!$st->fetchColumn()) return false;

        // Ensure the new bus is in this depot
        $st = $this->pdo->prepare("SELECT COUNT(*) FROM sltb_buses WHERE reg_no=? AND sltb_depot_id=?");
        $st->execute([$bus, $depotId]);
        if (!$st->fetchColumn()) return false;

        $sql = "UPDATE timetables
                SET route_id=?, bus_reg_no=?, day_of_week=?, departure_time=?, arrival_time=?, effective_from=?, effective_to=?
                WHERE timetable_id=? AND operator_type='SLTB'";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            $route,
            $bus,
            $dow,
            $depT,
            ($arrT ?: null),
            $from,
            ($to ?: null),
            $ttId
        ]);
    }
}
?>