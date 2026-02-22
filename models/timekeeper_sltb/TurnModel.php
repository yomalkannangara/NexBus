<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;
use PDO;

class TurnModel extends BaseModel
{
    private function depotId(): int {
        $u = $_SESSION['user'] ?? [];
        return (int)($u['sltb_depot_id'] ?? 0);
    }

    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }

    public function running(): array
    {
        $sql = <<<SQL
        SELECT
          st.sltb_trip_id, st.timetable_id, st.bus_reg_no, st.turn_no,
          r.route_no, r.stops_json,
          st.scheduled_departure_time AS sched_dep,
          st.scheduled_arrival_time   AS sched_arr,
          st.departure_time           AS actual_dep,
          st.status                   AS trip_status,
          TIMESTAMPDIFF(MINUTE, st.scheduled_departure_time, st.departure_time) AS delay_min
        FROM sltb_trips st
        JOIN routes r     ON r.route_id = st.route_id
        JOIN sltb_buses b ON b.reg_no   = st.bus_reg_no
        WHERE st.trip_date=CURDATE() AND st.status='InProgress' AND b.sltb_depot_id=:depot
        ORDER BY st.scheduled_departure_time, r.route_no+0, r.route_no
        SQL;

        $st = $this->pdo->prepare($sql);
        $st->execute([':depot'=>$this->depotId()]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
        }
        return $rows;
    }

    public function complete(int $tripId): bool
    {
        // load trip + route stops
        $q = "SELECT st.sltb_trip_id, st.route_id, r.stops_json
              FROM sltb_trips st
              LEFT JOIN routes r ON r.route_id = st.route_id
              WHERE st.sltb_trip_id = :id AND st.status='InProgress' AND st.trip_date=CURDATE()";
        $st = $this->pdo->prepare($q);
        $st->execute([':id'=>$tripId]);
        $trip = $st->fetch(PDO::FETCH_ASSOC);
        if (!$trip) return false;

        // decode stops and determine last stop token
        $stops = json_decode($trip['stops_json'] ?? '[]', true) ?: [];
        if (empty($stops)) return false; // cannot validate without route stops
        $last = $stops[count($stops)-1];
        $token = '';
        if (is_array($last)) {
            $token = $last['code'] ?? $last['stop'] ?? $last['name'] ?? '';
        } else {
            $token = (string)$last;
        }
        $token = trim($token);
        if ($token === '') return false;

        // try to resolve token to a depot id: prefer code match, then name LIKE
        $depotId = null;
        $pst = $this->pdo->prepare('SELECT sltb_depot_id FROM sltb_depots WHERE code = :tok LIMIT 1');
        $pst->execute([':tok'=>$token]);
        $row = $pst->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $depotId = (int)$row['sltb_depot_id'];
        } else {
            $pst = $this->pdo->prepare('SELECT sltb_depot_id FROM sltb_depots WHERE name LIKE :tok LIMIT 1');
            $pst->execute([':tok'=>'%'.$token.'%']);
            $row = $pst->fetch(PDO::FETCH_ASSOC);
            if ($row) $depotId = (int)$row['sltb_depot_id'];
        }

        // enforce Option B: only the timekeeper at the route's ending depot can close
        if ($depotId === null) return false;
        if ($depotId !== $this->depotId()) return false;

        // perform update and record arrival_depot_id and completed_by
        $upd = $this->pdo->prepare(
            "UPDATE sltb_trips
             SET arrival_time = CURRENT_TIME(), status = 'Completed', arrival_depot_id = :adp, completed_by = :user
             WHERE sltb_trip_id = :id AND status='InProgress' AND trip_date=CURDATE()"
        );
        $upd->execute([':adp'=>$depotId, ':user'=>($_SESSION['user']['user_id'] ?? null), ':id'=>$tripId]);
        return $upd->rowCount() > 0;
    }

    /** Cancel an in-progress trip from the route end timekeeper page. */
    public function cancel(int $tripId, ?string $reason=null): bool
    {
        // load trip + route stops
        $q = "SELECT st.sltb_trip_id, st.route_id, r.stops_json
              FROM sltb_trips st
              LEFT JOIN routes r ON r.route_id = st.route_id
              WHERE st.sltb_trip_id = :id AND st.status='InProgress' AND st.trip_date=CURDATE()";
        $st = $this->pdo->prepare($q);
        $st->execute([':id'=>$tripId]);
        $trip = $st->fetch(PDO::FETCH_ASSOC);
        if (!$trip) return false;

        $stops = json_decode($trip['stops_json'] ?? '[]', true) ?: [];
        if (empty($stops)) return false;
        $last = $stops[count($stops)-1];
        $token = '';
        if (is_array($last)) {
            $token = $last['code'] ?? $last['stop'] ?? $last['name'] ?? '';
        } else {
            $token = (string)$last;
        }
        $token = trim($token);
        if ($token === '') return false;

        $depotId = null;
        $pst = $this->pdo->prepare('SELECT sltb_depot_id FROM sltb_depots WHERE code = :tok LIMIT 1');
        $pst->execute([':tok'=>$token]);
        $row = $pst->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $depotId = (int)$row['sltb_depot_id'];
        } else {
            $pst = $this->pdo->prepare('SELECT sltb_depot_id FROM sltb_depots WHERE name LIKE :tok LIMIT 1');
            $pst->execute([':tok'=>'%'.$token.'%']);
            $row = $pst->fetch(PDO::FETCH_ASSOC);
            if ($row) $depotId = (int)$row['sltb_depot_id'];
        }

        if ($depotId === null) return ['ok'=>false,'msg'=>'no_depot_match'];
        if ($depotId !== $this->depotId()) return ['ok'=>false,'msg'=>'not_authorized'];

        $reasonText = trim((string)($reason ?? ''));
        if ($reasonText === '') return ['ok'=>false,'msg'=>'no_reason'];

        $upd = $this->pdo->prepare(
            "UPDATE sltb_trips
             SET status='Cancelled', cancelled_by=:user, cancel_reason=:reason, cancelled_at=CURRENT_TIMESTAMP(), arrival_depot_id=:adp
             WHERE sltb_trip_id = :id AND status='InProgress' AND trip_date=CURDATE()"
        );
        $upd->execute([':user'=>($_SESSION['user']['user_id'] ?? null), ':reason'=>$reasonText, ':adp'=>$depotId, ':id'=>$tripId]);
        return ['ok'=>$upd->rowCount() > 0, 'msg'=>$upd->rowCount()>0 ? null : 'update_failed'];
    }
}
