<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;
use PDO;

class TurnModel extends BaseModel
{
    public function info(): array
    {
        $id = $this->depotId();
        if ($id <= 0) {
            return ['depot_name' => 'My Depot'];
        }

        try {
            $st = $this->pdo->prepare("SELECT name FROM sltb_depots WHERE sltb_depot_id=:d LIMIT 1");
            $st->execute([':d' => $id]);
            $name = (string)($st->fetchColumn() ?: 'My Depot');
            return ['depot_name' => $name];
        } catch (\Throwable $e) {
            return ['depot_name' => 'My Depot'];
        }
    }

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

    private function resolveEndDepotId(string $stopsJson): ?int
    {
        $stops = json_decode($stopsJson ?: '[]', true) ?: [];
        if (empty($stops)) return null;

        $last = $stops[count($stops)-1];
        $token = '';
        if (is_array($last)) {
            $token = $last['code'] ?? $last['stop'] ?? $last['name'] ?? '';
        } else {
            $token = (string)$last;
        }
        $token = trim($token);
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

    private function emergencyTypeAndPriority(string $reason): array
    {
        $r = strtolower($reason);
        $isBreakdown = str_contains($r, 'breakdown')
            || str_contains($r, 'engine')
            || str_contains($r, 'mechanical')
            || str_contains($r, 'failure');

        return $isBreakdown
            ? ['type' => 'Breakdown', 'priority' => 'critical']
            : ['type' => 'Alert', 'priority' => 'urgent'];
    }

    private function notifyDepotEmergency(int $depotId, int $tripId, array $trip, string $reason): void
    {
        if ($depotId <= 0) return;

        $event = $this->emergencyTypeAndPriority($reason);
        $u = $_SESSION['user'] ?? [];
        $sourceUserId = (int)($u['user_id'] ?? $u['id'] ?? 0);
        $sourceRole = (string)($u['role'] ?? 'SLTBTimekeeper');
        $sourceName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
        if ($sourceName === '') $sourceName = (string)($u['name'] ?? 'SLTB Timekeeper');

        $message = sprintf(
            'EMERGENCY UPDATE: Trip #%d was cancelled by %s at end depot. Bus: %s, Route ID: %d. Reason: %s',
            $tripId,
            $sourceName,
            (string)($trip['bus_reg_no'] ?? 'N/A'),
            (int)($trip['route_id'] ?? 0),
            $reason
        );

        $metadata = json_encode([
            'source' => 'sltb_timekeeper_emergency',
            'source_role' => $sourceRole,
            'source_user_id' => $sourceUserId,
            'source_name' => $sourceName,
            'event_kind' => 'trip_cancelled_end_depot',
            'trip_id' => $tripId,
            'timetable_id' => (int)($trip['timetable_id'] ?? 0),
            'route_id' => (int)($trip['route_id'] ?? 0),
            'bus_reg_no' => (string)($trip['bus_reg_no'] ?? ''),
            'depot_id' => $depotId,
            'reason' => $reason,
        ], JSON_UNESCAPED_UNICODE);

        $stRecipients = $this->pdo->prepare(
            "SELECT user_id FROM users WHERE sltb_depot_id=:depot AND role IN ('DepotOfficer','DepotManager')"
        );
        $stRecipients->execute([':depot' => $depotId]);
        $recipientIds = array_map('intval', $stRecipients->fetchAll(PDO::FETCH_COLUMN));
        if (empty($recipientIds)) return;

        $ins = $this->pdo->prepare(
            "INSERT INTO notifications (user_id, type, message, is_seen, priority, metadata, created_at)
             VALUES (:uid, :type, :message, 0, :priority, :metadata, NOW())"
        );
        foreach ($recipientIds as $rid) {
            $ins->execute([
                ':uid' => $rid,
                ':type' => $event['type'],
                ':message' => $message,
                ':priority' => $event['priority'],
                ':metadata' => $metadata,
            ]);
        }
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
        WHERE st.trip_date=CURDATE() AND st.status='InProgress'
        ORDER BY st.scheduled_departure_time, r.route_no+0, r.route_no
        SQL;

        $st = $this->pdo->prepare($sql);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $currentDepot = $this->depotId();
        $filtered = [];
        foreach ($rows as &$r) {
            $endDepot = $this->resolveEndDepotId($r['stops_json'] ?? '[]');
            if ($endDepot === null || $endDepot !== $currentDepot) continue;
            $r['route_name'] = $this->getRouteDisplayName($r['stops_json'] ?? '[]');
            $filtered[] = $r;
        }
        return $filtered;
    }

    public function complete(int $tripId): bool
    {
        // load trip + route stops
          $q = "SELECT st.sltb_trip_id, st.timetable_id, st.route_id, st.bus_reg_no, r.stops_json
              FROM sltb_trips st
              LEFT JOIN routes r ON r.route_id = st.route_id
              WHERE st.sltb_trip_id = :id AND st.status='InProgress' AND st.trip_date=CURDATE()";
        $st = $this->pdo->prepare($q);
        $st->execute([':id'=>$tripId]);
        $trip = $st->fetch(PDO::FETCH_ASSOC);
        if (!$trip) return false;

        // decode stops and determine last stop token
        $depotId = $this->resolveEndDepotId($trip['stops_json'] ?? '[]');

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
    public function cancel(int $tripId, ?string $reason=null): array
    {
        // load trip + route stops
        $q = "SELECT st.sltb_trip_id, st.route_id, r.stops_json
              FROM sltb_trips st
              LEFT JOIN routes r ON r.route_id = st.route_id
              WHERE st.sltb_trip_id = :id AND st.status='InProgress' AND st.trip_date=CURDATE()";
        $st = $this->pdo->prepare($q);
        $st->execute([':id'=>$tripId]);
        $trip = $st->fetch(PDO::FETCH_ASSOC);
        if (!$trip) return ['ok'=>false,'msg'=>'no_trip'];

        $depotId = $this->resolveEndDepotId($trip['stops_json'] ?? '[]');

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
        $ok = $upd->rowCount() > 0;
        if ($ok) {
            $this->notifyDepotEmergency($depotId, $tripId, $trip, $reasonText);
        }
        return ['ok'=>$ok, 'msg'=>$ok ? null : 'update_failed'];
    }
}
