<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;

class TimetableModel extends BaseModel
{
    /** Get depot id from session (strict) */
    private function myDepotId(): int
    {
        return (int)($_SESSION['user']['sltb_depot_id'] ?? 0);
    }

    /**
     * TODAY's SLTB timetables only for the logged-in user's depot.
     * Uses columns exactly from your SQL: operator_type, route_id, bus_reg_no, day_of_week,
     * departure_time, arrival_time, start_seq, end_seq, effective_from, effective_to.
     */
    public function todayTimetables(?int $depotId = null): array
    {
        $dep = $depotId ?? $this->myDepotId();
        if ($dep <= 0) return [];

        $sql = "
            SELECT
                t.*,
                r.route_no
            FROM timetables t
            JOIN sltb_buses b
              ON b.reg_no = t.bus_reg_no
             AND b.sltb_depot_id = :dep
            LEFT JOIN routes r
              ON r.route_id = t.route_id
            WHERE t.operator_type = 'SLTB'
              AND t.day_of_week = DAYOFWEEK(CURDATE())
              AND (t.effective_from IS NULL OR t.effective_from <= CURDATE())
              AND (t.effective_to   IS NULL OR t.effective_to   >= CURDATE())
            ORDER BY (r.route_no+0), r.route_no, t.departure_time
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([':dep' => $dep]);
        return $st->fetchAll();
    }

    /** Safe updater using only columns present in your dump */
    public function updateTimetable(array $d): bool
    {
        $id = (int)($d['timetable_id'] ?? 0);
        if ($id <= 0) return false;

        $set = [];
        $p   = [':id' => $id];

        if (isset($d['departure_time']) && $d['departure_time'] !== '') {
            $set[] = "departure_time = :departure_time";
            $p[':departure_time'] = $d['departure_time'];
        }
        if (isset($d['arrival_time']) && $d['arrival_time'] !== '') {
            $set[] = "arrival_time = :arrival_time";
            $p[':arrival_time'] = $d['arrival_time'];
        }
        if (isset($d['start_seq']) && $d['start_seq'] !== '') {
            $set[] = "start_seq = :start_seq";
            $p[':start_seq'] = (int)$d['start_seq'];
        }
        if (isset($d['end_seq']) && $d['end_seq'] !== '') {
            $set[] = "end_seq = :end_seq";
            $p[':end_seq'] = (int)$d['end_seq'];
        }
        if (isset($d['effective_from']) && $d['effective_from'] !== '') {
            $set[] = "effective_from = :effective_from";
            $p[':effective_from'] = $d['effective_from'];
        }
        if (array_key_exists('effective_to', $d)) {
            if ($d['effective_to'] === '' || strcasecmp((string)$d['effective_to'], 'null') === 0) {
                $set[] = "effective_to = NULL";
            } else {
                $set[] = "effective_to = :effective_to";
                $p[':effective_to'] = $d['effective_to'];
            }
        }
        if (isset($d['route_id']) && $d['route_id'] !== '') {
            $set[] = "route_id = :route_id";
            $p[':route_id'] = (int)$d['route_id'];
        }
        if (isset($d['bus_reg_no']) && $d['bus_reg_no'] !== '') {
            $set[] = "bus_reg_no = :bus_reg_no";
            $p[':bus_reg_no'] = $d['bus_reg_no'];
        }

        if (!$set) return true;

        $sql = "UPDATE timetables SET " . implode(", ", $set) . " WHERE timetable_id = :id";
        $st  = $this->pdo->prepare($sql);
        return $st->execute($p);
    }
}
