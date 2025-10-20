<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;

class TimetableModel extends BaseModel
{
    public function todayTimetables(int $depotId): array
    {
        if ($depotId <= 0) return [];

        $queries = [
            // If depot column lives on timetables
            "SELECT * FROM timetables
             WHERE (depot_id=:dep OR sltb_depot_id=:dep)
               AND work_date=CURDATE()
             ORDER BY route_no, departure_time",

            // If depot is derived via bus join
            "SELECT t.*
             FROM timetables t
             JOIN sltb_buses b ON b.reg_no=t.bus_reg_no AND (b.depot_id=:dep OR b.sltb_depot_id=:dep)
             WHERE t.work_date=CURDATE()
             ORDER BY t.route_no, t.departure_time",
        ];

        foreach ($queries as $sql) {
            try {
                $st = $this->pdo->prepare($sql);
                $st->execute([':dep'=>$depotId]);
                $rows = $st->fetchAll();
                if ($rows) return $rows;
            } catch (\Throwable $e) {}
        }
        return [];
    }

    public function updateTimetable(array $d): bool
    {
        $id   = (int)($d['timetable_id'] ?? 0);
        $note = trim($d['note'] ?? ($d['remarks'] ?? ''));
        $time = $d['actual_time'] ?? ($d['actual_departure'] ?? null);
        if (!$id) return true;

        // try to update a note column
        try {
            $st = $this->pdo->prepare("UPDATE timetables SET timekeeper_note=? WHERE timetable_id=?");
            $st->execute([$note, $id]);
            return true;
        } catch (\Throwable $e) {}

        try {
            $st = $this->pdo->prepare("UPDATE timetables SET remarks=? WHERE timetable_id=?");
            $st->execute([$note, $id]);
            return true;
        } catch (\Throwable $e) {}

        // optional actual time capture
        if ($time) {
            try {
                $st = $this->pdo->prepare("UPDATE timetables SET actual_departure=? WHERE timetable_id=?");
                $st->execute([$time, $id]);
                return true;
            } catch (\Throwable $e) {}
        }
        return true;
    }
}
