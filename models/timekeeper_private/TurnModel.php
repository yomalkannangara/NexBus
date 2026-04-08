<?php
namespace App\models\timekeeper_private;

use PDO;

class TurnModel extends BaseModel
{
    private function currentDepotId(): int {
        $u = $_SESSION['user'] ?? [];
        return (int)($u['sltb_depot_id'] ?? $u['depot_id'] ?? 0);
    }

    private function getRouteDisplayName(string $stopsJson): string {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops)) return 'Unknown';
        $first = is_array($stops[0]) ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start') : $stops[0];
        $last = is_array($stops[count($stops)-1]) ? ($stops[count($stops)-1]['stop'] ?? $stops[count($stops)-1]['name'] ?? 'End') : $stops[count($stops)-1];
        return "$first - $last";
    }

    private function resolveEndDepotId(string $stopsJson): ?int
    {
        $stops = json_decode($stopsJson ?: '[]', true) ?: [];
        if (empty($stops)) return null;
        $last = $stops[count($stops)-1];
        $token = is_array($last)
            ? ($last['code'] ?? $last['stop'] ?? $last['name'] ?? '')
            : (string)$last;
        $token = trim((string)$token);
        if ($token === '') return null;

        $pst = $this->pdo->prepare('SELECT sltb_depot_id FROM sltb_depots WHERE code = :tok LIMIT 1');
        $pst->execute([':tok'=>$token]);
        $row = $pst->fetch(PDO::FETCH_ASSOC);
        if ($row) return (int)$row['sltb_depot_id'];

        $pst = $this->pdo->prepare('SELECT sltb_depot_id FROM sltb_depots WHERE name LIKE :tok LIMIT 1');
        $pst->execute([':tok'=>'%'.$token.'%']);
        $row = $pst->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['sltb_depot_id'] : null;
    }

    public function running(): array
    {
        $sql = "
        SELECT
          p.private_trip_id, p.timetable_id, p.bus_reg_no, p.turn_no,
          r.route_no, r.stops_json,
          p.scheduled_departure_time AS sched_dep,
          p.scheduled_arrival_time   AS sched_arr,
          p.departure_time           AS actual_dep
        FROM private_trips p
        JOIN private_buses pb ON pb.reg_no=p.bus_reg_no AND pb.private_operator_id=:op
        LEFT JOIN routes r ON r.route_id=p.route_id
        WHERE p.trip_date=CURDATE() AND p.status='InProgress'
        ORDER BY p.scheduled_departure_time, r.route_no+0, r.route_no";
        $st = $this->pdo->prepare($sql);
        $st->execute([':op'=>$this->opId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $currentDepot = $this->currentDepotId();
        $filtered = [];
        foreach ($rows as &$r) {
            $endDepot = $this->resolveEndDepotId($r['stops_json'] ?? '[]');
            if ($currentDepot > 0 && $endDepot !== null && $endDepot !== $currentDepot) {
                continue;
            }
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
            $r['delay_min'] = max(0, (int)round((strtotime($r['actual_dep']) - strtotime($r['sched_dep']))/60));
            $filtered[] = $r;
        }
        return $filtered;
    }

    public function complete(int $tripId): bool
    {
        // load trip + route stops + operator
        $q = "SELECT p.private_trip_id, p.route_id, p.private_operator_id, r.stops_json
              FROM private_trips p
              LEFT JOIN routes r ON r.route_id = p.route_id
              WHERE p.private_trip_id = :id AND p.status='InProgress' AND p.trip_date=CURDATE()";
        $st = $this->pdo->prepare($q);
        $st->execute([':id'=>$tripId]);
        $trip = $st->fetch(PDO::FETCH_ASSOC);
        if (!$trip) return false;

        $depotId = $this->resolveEndDepotId($trip['stops_json'] ?? '[]');

        // If we resolved an end depot, enforce that current user is at that depot
        $currentUser = $_SESSION['user'] ?? [];
        $currentDepot = $this->currentDepotId();
        if ($depotId !== null && $currentDepot > 0) {
            if ($depotId !== $currentDepot) return false;
        } else {
            // fallback: ensure private operator matches user's operator
            if ((int)$trip['private_operator_id'] !== ($this->opId ?? 0)) return false;
        }

        // perform update and record arrival_depot_id and completed_by where applicable
        $upd = $this->pdo->prepare(
            "UPDATE private_trips p
             SET p.arrival_time = CURRENT_TIME(), p.status = 'Completed', p.arrival_depot_id = :adp, p.completed_by = :user
             WHERE p.private_trip_id = :id AND p.status='InProgress' AND p.trip_date=CURDATE()"
        );
        $upd->execute([
            ':adp' => $depotId,
            ':user'=> ($currentUser['user_id'] ?? null),
            ':id'  => $tripId
        ]);
        return $upd->rowCount() > 0;
    }

    /** Cancel an in-progress private trip from the route end/turn page. */
    public function cancel(int $tripId, ?string $reason=null): array
    {
        $reasonText = trim((string)($reason ?? ''));
        if ($reasonText === '') return ['ok'=>false,'msg'=>'no_reason'];

        $q = "SELECT p.private_trip_id, p.route_id, p.private_operator_id, r.stops_json
              FROM private_trips p
              LEFT JOIN routes r ON r.route_id = p.route_id
              WHERE p.private_trip_id = :id AND p.status='InProgress' AND p.trip_date=CURDATE()";
        $st = $this->pdo->prepare($q);
        $st->execute([':id'=>$tripId]);
        $trip = $st->fetch(PDO::FETCH_ASSOC);
        if (!$trip) return ['ok'=>false,'msg'=>'no_trip'];

        $depotId = $this->resolveEndDepotId($trip['stops_json'] ?? '[]');

        $currentUser = $_SESSION['user'] ?? [];
        $currentDepot = $this->currentDepotId();
        if ($depotId !== null && $currentDepot > 0) {
            if ($depotId !== $currentDepot) return ['ok'=>false,'msg'=>'not_authorized'];
        } else {
            // fallback: ensure private operator matches user's operator
            if ((int)$trip['private_operator_id'] !== ($this->opId ?? 0)) return ['ok'=>false,'msg'=>'not_authorized'];
        }

        $upd = $this->pdo->prepare(
            "UPDATE private_trips p
             SET p.status='Cancelled', p.cancelled_by = :user, p.cancel_reason = :reason, p.cancelled_at = CURRENT_TIMESTAMP(), p.arrival_depot_id = :adp
             WHERE p.private_trip_id = :id AND p.status='InProgress' AND p.trip_date=CURDATE()"
        );
        $upd->execute([':user'=>($currentUser['user_id'] ?? null), ':reason'=>$reasonText, ':adp'=>$depotId, ':id'=>$tripId]);
        return ['ok'=>$upd->rowCount() > 0, 'msg'=>$upd->rowCount()>0 ? null : 'update_failed'];
    }
}
