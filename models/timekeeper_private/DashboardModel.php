<?php
namespace App\models\timekeeper_private;

class DashboardModel extends BaseModel
{
    private function resolvedOperatorId(): int
    {
        if ($this->opId > 0) {
            return $this->opId;
        }

        $u = $_SESSION['user'] ?? [];
        $uid = (int)($u['user_id'] ?? $u['id'] ?? 0);
        if ($uid <= 0) {
            return 0;
        }

        try {
            $st = $this->pdo->prepare(
                "SELECT COALESCE(private_operator_id, 0) FROM users WHERE user_id=:uid LIMIT 1"
            );
            $st->execute([':uid' => $uid]);
            return (int)($st->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function scalar(string $sql, array $p = [])
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($p);
        return $st->fetchColumn();
    }

    private function countAssignedBuses(int $opId, string $date): int
    {
        if ($opId > 0) {
            return (int)$this->scalar(
                "SELECT COUNT(DISTINCT bus_reg_no)
                   FROM private_assignments
                  WHERE private_operator_id=:op AND assigned_date=:d",
                [':op' => $opId, ':d' => $date]
            );
        }

        return (int)$this->scalar(
            "SELECT COUNT(DISTINCT bus_reg_no)
               FROM private_assignments
              WHERE assigned_date=:d",
            [':d' => $date]
        );
    }

    private function latestAssignmentDate(int $opId): ?string
    {
        if ($opId > 0) {
            $dt = $this->scalar(
                "SELECT MAX(assigned_date)
                   FROM private_assignments
                  WHERE private_operator_id=:op",
                [':op' => $opId]
            );
            return $dt ? (string)$dt : null;
        }

        $dt = $this->scalar("SELECT MAX(assigned_date) FROM private_assignments");
        return $dt ? (string)$dt : null;
    }

    private function countDistinctAssignments(int $opId, string $date, string $column): int
    {
        $col = $column === 'private_conductor_id' ? 'private_conductor_id' : 'private_driver_id';

        if ($opId > 0) {
            return (int)$this->scalar(
                "SELECT COUNT(DISTINCT {$col})
                   FROM private_assignments
                  WHERE private_operator_id=:op AND assigned_date=:d",
                [':op' => $opId, ':d' => $date]
            );
        }

        return (int)$this->scalar(
            "SELECT COUNT(DISTINCT {$col})
               FROM private_assignments
              WHERE assigned_date=:d",
            [':d' => $date]
        );
    }

    private function countScheduledBusesForDay(int $opId, int $dow): int
    {
        if ($opId > 0) {
            return (int)$this->scalar(
                "SELECT COUNT(DISTINCT tt.bus_reg_no)
                   FROM timetables tt
                   JOIN private_buses pb ON pb.reg_no=tt.bus_reg_no
                  WHERE tt.operator_type='Private'
                    AND tt.day_of_week=:dow
                    AND pb.private_operator_id=:op",
                [':dow' => $dow, ':op' => $opId]
            );
        }

        return (int)$this->scalar(
            "SELECT COUNT(DISTINCT tt.bus_reg_no)
               FROM timetables tt
              WHERE tt.operator_type='Private'
                AND tt.day_of_week=:dow",
            [':dow' => $dow]
        );
    }

    private function countActiveRoutesForDay(int $opId, int $dow): int
    {
        if ($opId > 0) {
            return (int)$this->scalar(
                "SELECT COUNT(DISTINCT tt.route_id)
                   FROM timetables tt
                   JOIN private_buses pb ON pb.reg_no=tt.bus_reg_no
                  WHERE tt.operator_type='Private'
                    AND tt.day_of_week=:dow
                    AND pb.private_operator_id=:op",
                [':dow' => $dow, ':op' => $opId]
            );
        }

        return (int)$this->scalar(
            "SELECT COUNT(DISTINCT tt.route_id)
               FROM timetables tt
              WHERE tt.operator_type='Private'
                AND tt.day_of_week=:dow",
            [':dow' => $dow]
        );
    }

    private function countBuses(int $opId): int
    {
        if ($opId > 0) {
            return (int)$this->scalar(
                "SELECT COUNT(*) FROM private_buses WHERE private_operator_id=:op",
                [':op' => $opId]
            );
        }

        return (int)$this->scalar("SELECT COUNT(*) FROM private_buses");
    }

    private function countCrewFromBuses(int $opId, string $column): int
    {
        $col = $column === 'conductor_id' ? 'conductor_id' : 'driver_id';

        if ($opId > 0) {
            return (int)$this->scalar(
                "SELECT COUNT(DISTINCT {$col})
                   FROM private_buses
                  WHERE private_operator_id=:op
                    AND {$col} IS NOT NULL",
                [':op' => $opId]
            );
        }

        return (int)$this->scalar(
            "SELECT COUNT(DISTINCT {$col})
               FROM private_buses
              WHERE {$col} IS NOT NULL"
        );
    }

    private function revenueOnDate(int $opId, string $date): int
    {
        if ($opId > 0) {
            return (int)$this->scalar(
                "SELECT COALESCE(SUM(e.amount),0)
                   FROM earnings e
                   JOIN private_buses pb ON pb.reg_no=e.bus_reg_no
                  WHERE e.operator_type='Private'
                    AND e.date=:d
                    AND pb.private_operator_id=:op",
                [':d' => $date, ':op' => $opId]
            );
        }

        return (int)$this->scalar(
            "SELECT COALESCE(SUM(e.amount),0)
               FROM earnings e
              WHERE e.operator_type='Private'
                AND e.date=:d",
            [':d' => $date]
        );
    }

    private function latestRevenueDate(int $opId): ?string
    {
        if ($opId > 0) {
            $dt = $this->scalar(
                "SELECT MAX(e.date)
                   FROM earnings e
                   JOIN private_buses pb ON pb.reg_no=e.bus_reg_no
                  WHERE e.operator_type='Private'
                    AND pb.private_operator_id=:op",
                [':op' => $opId]
            );
            return $dt ? (string)$dt : null;
        }

        $dt = $this->scalar("SELECT MAX(date) FROM earnings WHERE operator_type='Private'");
        return $dt ? (string)$dt : null;
    }

    private function countUpdatedLastHour(int $opId): int
    {
        if ($opId > 0) {
            return (int)$this->scalar(
                "SELECT COUNT(DISTINCT tm.bus_reg_no)
                   FROM tracking_monitoring tm
                   JOIN private_buses pb ON pb.reg_no=tm.bus_reg_no
                  WHERE pb.private_operator_id=:op
                    AND tm.snapshot_at >= NOW() - INTERVAL 1 HOUR",
                [':op' => $opId]
            );
        }

        return (int)$this->scalar(
            "SELECT COUNT(DISTINCT tm.bus_reg_no)
               FROM tracking_monitoring tm
               JOIN private_buses pb ON pb.reg_no=tm.bus_reg_no
              WHERE tm.snapshot_at >= NOW() - INTERVAL 1 HOUR"
        );
    }

    public function stats(): array
    {
        $opId = $this->resolvedOperatorId();
        $todayDow = (int)date('w');

        $busParams = [];
        $busScope = '1=1';
        if ($opId > 0) {
            $busScope = 'pb.private_operator_id=:op';
            $busParams[':op'] = $opId;
        }

        $st = $this->pdo->prepare("SELECT COUNT(*) FROM private_buses pb WHERE {$busScope}");
        $st->execute($busParams);
        $totalBuses = (int)$st->fetchColumn();

        $delayedSql = "SELECT COUNT(DISTINCT p.bus_reg_no)
                         FROM private_trips p";
        if ($opId > 0) {
            $delayedSql .= " JOIN private_buses pb ON pb.reg_no=p.bus_reg_no AND pb.private_operator_id=:op";
        }
        $delayedSql .= " WHERE p.trip_date=CURDATE() AND p.status='Delayed'";
        $st = $this->pdo->prepare($delayedSql);
        $st->execute($busParams);
        $delayedBusesTotal = (int)$st->fetchColumn();

        $completedSql = "SELECT COUNT(*)
                           FROM private_trips p";
        if ($opId > 0) {
            $completedSql .= " JOIN private_buses pb ON pb.reg_no=p.bus_reg_no AND pb.private_operator_id=:op";
        }
        $completedSql .= " WHERE p.trip_date=CURDATE()
                             AND (
                                 p.status='Completed'
                                 OR (p.status='Delayed' AND p.arrival_time IS NOT NULL)
                             )";
        $st = $this->pdo->prepare($completedSql);
        $st->execute($busParams);
        $completedTripsToday = (int)$st->fetchColumn();

        $runningSql = "SELECT COUNT(DISTINCT p.bus_reg_no)
                         FROM private_trips p";
        if ($opId > 0) {
            $runningSql .= " JOIN private_buses pb ON pb.reg_no=p.bus_reg_no AND pb.private_operator_id=:op";
        }
        $runningSql .= " WHERE p.trip_date=CURDATE()
                           AND p.status IN ('InProgress','Delayed')
                           AND p.arrival_time IS NULL";
        $st = $this->pdo->prepare($runningSql);
        $st->execute($busParams);
        $runningBusesNow = (int)$st->fetchColumn();

        $totalSql = "SELECT COUNT(*)
                   FROM timetables tt
                   JOIN private_buses pb ON pb.reg_no=tt.bus_reg_no
                  WHERE tt.operator_type='Private'
                AND tt.day_of_week=:dow
                AND {$busScope}";
        $totalParams = $busParams;
        $totalParams[':dow'] = $todayDow;
        $st = $this->pdo->prepare($totalSql);
        $st->execute($totalParams);
        $totalTripsToday = (int)$st->fetchColumn();

        $leftSql = "SELECT COUNT(*)
                      FROM timetables tt
                      JOIN private_buses pb ON pb.reg_no=tt.bus_reg_no
                      LEFT JOIN private_trips p
                             ON p.timetable_id=tt.timetable_id
                            AND p.trip_date=CURDATE()
                     WHERE tt.operator_type='Private'
                       AND tt.day_of_week=:dow
                       AND {$busScope}
                       AND (
                           p.private_trip_id IS NULL
                           OR p.status IN ('Planned','InProgress')
                           OR (p.status='Delayed' AND p.arrival_time IS NULL)
                       )";
        $leftParams = $busParams;
        $leftParams[':dow'] = $todayDow;
        $st = $this->pdo->prepare($leftSql);
        $st->execute($leftParams);
        $tripsLeftToday = (int)$st->fetchColumn();

        return [
            'total_buses' => $totalBuses,
            'total_trips_today' => $totalTripsToday,
            'delayed_buses_total' => $delayedBusesTotal,
            'completed_trips_today' => $completedTripsToday,
            'trips_left_today' => $tripsLeftToday,
            'running_buses_now' => $runningBusesNow,
        ];
    }
}
