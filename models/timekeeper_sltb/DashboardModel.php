<?php
namespace App\models\timekeeper_sltb;

use App\models\common\BaseModel;

class DashboardModel extends BaseModel
{
    private function depotId(): int
    {
        $u = $_SESSION['user'] ?? [];
        $sid = (int)($u['sltb_depot_id'] ?? $u['depot_id'] ?? 0);
        if ($sid > 0) {
            return $sid;
        }

        $uid = (int)($u['user_id'] ?? $u['id'] ?? 0);
        if ($uid <= 0) {
            return 0;
        }

        try {
            $st = $this->pdo->prepare(
                "SELECT COALESCE(sltb_depot_id, 0) FROM users WHERE user_id=:uid LIMIT 1"
            );
            $st->execute([':uid' => $uid]);
            return (int)($st->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function depotName(): string
    {
        $id = $this->depotId();
        if ($id <= 0) {
            return 'My Depot';
        }

        try {
            $st = $this->pdo->prepare("SELECT name FROM sltb_depots WHERE sltb_depot_id=:d LIMIT 1");
            $st->execute([':d' => $id]);
            return (string)($st->fetchColumn() ?: 'My Depot');
        } catch (\Throwable $e) {
            return 'My Depot';
        }
    }

    private function tableExists(string $t): bool
    {
        try {
            $db = (string)$this->pdo->query("SELECT DATABASE()")->fetchColumn();
            $st = $this->pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema=? AND table_name=?");
            $st->execute([$db, $t]);
            return (bool)$st->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function countAssignedBuses(int $depotId, string $date): int
    {
        if ($depotId > 0) {
            $st = $this->pdo->prepare(
                "SELECT COUNT(DISTINCT bus_reg_no)
                   FROM sltb_assignments
                  WHERE sltb_depot_id=:d AND assigned_date=:dt"
            );
            $st->execute([':d' => $depotId, ':dt' => $date]);
            return (int)$st->fetchColumn();
        }

        $st = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT bus_reg_no)
               FROM sltb_assignments
              WHERE assigned_date=:dt"
        );
        $st->execute([':dt' => $date]);
        return (int)$st->fetchColumn();
    }

    private function latestAssignmentDate(int $depotId): ?string
    {
        if ($depotId > 0) {
            $st = $this->pdo->prepare("SELECT MAX(assigned_date) FROM sltb_assignments WHERE sltb_depot_id=:d");
            $st->execute([':d' => $depotId]);
            $dt = $st->fetchColumn();
            return $dt ? (string)$dt : null;
        }

        $dt = $this->pdo->query("SELECT MAX(assigned_date) FROM sltb_assignments")->fetchColumn();
        return $dt ? (string)$dt : null;
    }

    private function countDistinctAssignments(int $depotId, string $date, string $column): int
    {
        $col = $column === 'sltb_conductor_id' ? 'sltb_conductor_id' : 'sltb_driver_id';

        if ($depotId > 0) {
            $st = $this->pdo->prepare(
                "SELECT COUNT(DISTINCT {$col})
                   FROM sltb_assignments
                  WHERE sltb_depot_id=:d AND assigned_date=:dt"
            );
            $st->execute([':d' => $depotId, ':dt' => $date]);
            return (int)$st->fetchColumn();
        }

        $st = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT {$col})
               FROM sltb_assignments
              WHERE assigned_date=:dt"
        );
        $st->execute([':dt' => $date]);
        return (int)$st->fetchColumn();
    }

    private function countScheduledBusesForDay(int $depotId, int $dow): int
    {
        if ($depotId > 0) {
            $st = $this->pdo->prepare(
                "SELECT COUNT(DISTINCT t.bus_reg_no)
                   FROM timetables t
                   JOIN sltb_buses b ON b.reg_no=t.bus_reg_no
                  WHERE t.operator_type='SLTB'
                    AND t.day_of_week=:dow
                    AND b.sltb_depot_id=:d"
            );
            $st->execute([':dow' => $dow, ':d' => $depotId]);
            return (int)$st->fetchColumn();
        }

        $st = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT t.bus_reg_no)
               FROM timetables t
              WHERE t.operator_type='SLTB'
                AND t.day_of_week=:dow"
        );
        $st->execute([':dow' => $dow]);
        return (int)$st->fetchColumn();
    }

    private function revenueFromEarningsOnDate(int $depotId, string $date): int
    {
        if (!$this->tableExists('earnings')) {
            return 0;
        }

        if ($depotId > 0) {
            $st = $this->pdo->prepare(
                "SELECT COALESCE(SUM(e.amount),0)
                   FROM earnings e
                   JOIN sltb_buses b ON b.reg_no=e.bus_reg_no
                  WHERE e.operator_type='SLTB'
                    AND e.date=:dt
                    AND b.sltb_depot_id=:d"
            );
            $st->execute([':dt' => $date, ':d' => $depotId]);
            return (int)$st->fetchColumn();
        }

        $st = $this->pdo->prepare(
            "SELECT COALESCE(SUM(e.amount),0)
               FROM earnings e
              WHERE e.operator_type='SLTB'
                AND e.date=:dt"
        );
        $st->execute([':dt' => $date]);
        return (int)$st->fetchColumn();
    }

    private function latestSltbEarningsDate(int $depotId): ?string
    {
        if (!$this->tableExists('earnings')) {
            return null;
        }

        if ($depotId > 0) {
            $st = $this->pdo->prepare(
                "SELECT MAX(e.date)
                   FROM earnings e
                   JOIN sltb_buses b ON b.reg_no=e.bus_reg_no
                  WHERE e.operator_type='SLTB'
                    AND b.sltb_depot_id=:d"
            );
            $st->execute([':d' => $depotId]);
            $dt = $st->fetchColumn();
            return $dt ? (string)$dt : null;
        }

        $dt = $this->pdo->query("SELECT MAX(date) FROM earnings WHERE operator_type='SLTB'")->fetchColumn();
        return $dt ? (string)$dt : null;
    }

    public function stats(): array
    {
        $d = $this->depotId();
        $todayDow = (int)date('w');

        $scopeBus = $d > 0 ? 'b.sltb_depot_id=:d' : '1=1';
        $scopeTrip = $d > 0 ? 'st.sltb_depot_id=:d' : '1=1';

        $params = [];
        if ($d > 0) {
            $params[':d'] = $d;
        }

        $st = $this->pdo->prepare("SELECT COUNT(*) FROM sltb_buses b WHERE {$scopeBus}");
        $st->execute($params);
        $totalBuses = (int)$st->fetchColumn();

        $st = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT st.bus_reg_no)
               FROM sltb_trips st
              WHERE {$scopeTrip}
                AND st.trip_date=CURDATE()
                AND st.status='Delayed'"
        );
        $st->execute($params);
        $delayedBusesTotal = (int)$st->fetchColumn();

        $st = $this->pdo->prepare(
            "SELECT COUNT(*)
               FROM sltb_trips st
              WHERE {$scopeTrip}
                AND st.trip_date=CURDATE()
                AND (
                    st.status='Completed'
                    OR (st.status='Delayed' AND st.arrival_time IS NOT NULL)
                )"
        );
        $st->execute($params);
        $completedTripsToday = (int)$st->fetchColumn();

        $st = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT st.bus_reg_no)
               FROM sltb_trips st
              WHERE {$scopeTrip}
                AND st.trip_date=CURDATE()
                AND st.status IN ('InProgress','Delayed')
                AND st.arrival_time IS NULL"
        );
        $st->execute($params);
        $runningBusesNow = (int)$st->fetchColumn();

        $leftParams = $params;
        $leftParams[':dow'] = $todayDow;

        $st = $this->pdo->prepare(
            "SELECT COUNT(*)
               FROM timetables t
               JOIN sltb_buses b ON b.reg_no=t.bus_reg_no
              WHERE t.operator_type='SLTB'
            AND t.day_of_week=:dow
            AND {$scopeBus}"
        );
        $st->execute($leftParams);
        $totalTripsToday = (int)$st->fetchColumn();

        $st = $this->pdo->prepare(
            "SELECT COUNT(*)
               FROM timetables t
               JOIN sltb_buses b ON b.reg_no=t.bus_reg_no
               LEFT JOIN sltb_trips st
                      ON st.timetable_id=t.timetable_id
                     AND st.trip_date=CURDATE()
              WHERE t.operator_type='SLTB'
                AND t.day_of_week=:dow
                AND {$scopeBus}
                AND (
                    st.sltb_trip_id IS NULL
                    OR st.status IN ('Planned','InProgress')
                    OR (st.status='Delayed' AND st.arrival_time IS NULL)
                )"
        );
        $st->execute($leftParams);
        $tripsLeftToday = (int)$st->fetchColumn();

        return [
            'depot_name' => $this->depotName(),
            'total_buses' => $totalBuses,
            'total_trips_today' => $totalTripsToday,
            'delayed_buses_total' => $delayedBusesTotal,
            'completed_trips_today' => $completedTripsToday,
            'trips_left_today' => $tripsLeftToday,
            'running_buses_now' => $runningBusesNow,
        ];
    }
}
