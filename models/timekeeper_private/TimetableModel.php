<?php
namespace App\models\timekeeper_private;

use App\models\common\BaseModel;

class TimetableModel extends BaseModel {

    public function todayTimetables(int $depotId): array {
        // Direct depot_id on timetables
        try {
            $sql="SELECT t.*, r.route_no
                  FROM timetables t
                  LEFT JOIN routes r ON r.route_id=t.route_id
                  WHERE t.depot_id=? AND DATE(t.departure_time)=CURDATE()
                  ORDER BY t.departure_time";
            $st=$this->pdo->prepare($sql); $st->execute([$depotId]); return $st->fetchAll();
        } catch (\Throwable $e) {}

        // Join via private_buses
        try {
            $sql="SELECT t.*, r.route_no
                  FROM timetables t
                  JOIN private_buses pb ON pb.reg_no=t.bus_reg_no AND pb.depot_id=?
                  LEFT JOIN routes r ON r.route_id=t.route_id
                  WHERE DATE(t.departure_time)=CURDATE()
                  ORDER BY t.departure_time";
            $st=$this->pdo->prepare($sql); $st->execute([$depotId]); return $st->fetchAll();
        } catch (\Throwable $e) {}

        // Fallback: join via sltb_buses
        $sql="SELECT t.*, r.route_no
              FROM timetables t
              JOIN sltb_buses b ON b.reg_no=t.bus_reg_no AND b.sltb_depot_id=?
              LEFT JOIN routes r ON r.route_id=t.route_id
              WHERE DATE(t.departure_time)=CURDATE()
              ORDER BY t.departure_time";
        $st=$this->pdo->prepare($sql); $st->execute([$depotId]); return $st->fetchAll();
    }

    public function updateTimetable(array $d): bool {
        $tt = (int)($d['timetable_id'] ?? 0);
        if (!$tt) return false;

        $dep  = trim($d['departure_time'] ?? '');
        $arr  = trim($d['arrival_time'] ?? '');
        $note = trim($d['remarks'] ?? '');

        $st = $this->pdo->prepare("UPDATE timetables SET departure_time=?, arrival_time=COALESCE(NULLIF(?,''), arrival_time), remarks=? WHERE timetable_id=?");
        return $st->execute([$dep,$arr,$note,$tt]);
    }
}
