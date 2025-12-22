<?php
namespace App\models\ntc_admin;

use PDO;

abstract class BaseModel {
    protected PDO $pdo;

    public function __construct() {
        $this->pdo = $GLOBALS['db'];
    }
}

class TimetableModel extends BaseModel
{
    /**
     * Reuse login-style alert page with alert.js + alert.css.
     */
    private function showAlert(string $message, string $redirect = '/A/timetables'): void
    {
        http_response_code(400);

        $msgJson   = json_encode($message, JSON_UNESCAPED_UNICODE);
        $redirJson = json_encode($redirect, JSON_UNESCAPED_UNICODE);

        echo '<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/assets/css/alert.css">
  <script src="/assets/js/alert.js"></script>
</head>
<body>
  <script>
    alert(' . $msgJson . ').then(function () {
      window.location.href = ' . $redirJson . ';
    });
  </script>
  <noscript>
    <meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect, ENT_QUOTES) . '">
  </noscript>
</body>
</html>';
        exit;
    }

    private function timeToMinutes(string $t): int
    {
        $t = substr($t, 0, 5); // HH:MM
        [$h, $m] = explode(':', $t);
        return ((int)$h) * 60 + (int)$m;
    }

    private function minutesToTime(int $minutes): string
    {
        $minutes = $minutes % (24 * 60);
        if ($minutes < 0) {
            $minutes += 24 * 60;
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        // store as TIME: HH:MM:SS
        return sprintf('%02d:%02d:00', $h, $m);
    }

    /**
     * Helper: add route_display using stops_json.
     */
    private function hydrateRouteDisplay(array $rows): array
    {
        foreach ($rows as &$row) {
            $stops = json_decode($row['stops_json'], true) ?: [];

            $first = $stops[0] ?? null;
            $last  = $stops[count($stops) - 1] ?? null;

            $start = is_array($first) ? ($first['stop'] ?? '') : $first;
            $end   = is_array($last)  ? ($last['stop']  ?? '') : $last;

            $row['route_display'] = sprintf('%s | %s → %s', $row['route_no'], $start, $end);
        }
        return $rows;
    }

    /**
     * Original "all" (kept for compatibility) – returns all rows.
     */
    public function all(): array
    {
        $sql = "SELECT t.*, r.route_no, r.stops_json
                FROM timetables t
                JOIN routes r ON r.route_id = t.route_id
                ORDER BY r.route_no+0, r.route_no, t.day_of_week, t.departure_time";

        $rows = $this->pdo->query($sql)->fetchAll();
        return $this->hydrateRouteDisplay($rows);
    }

    /**
     * New list method with filters + pagination for admin screen.
     */
    public function listTimetables(array $filters, int $limit, int $offset): array
    {
        $baseFrom = "FROM timetables t JOIN routes r ON r.route_id = t.route_id";

        $whereParts = [];
        $params     = [];

        // Route filter (by route_no only)
        if (!empty($filters['route'])) {
            $routeNumber            = trim((string)$filters['route']);
            if ($routeNumber !== '') {
                $whereParts[]      = "r.route_no LIKE :route_no";
                $params['route_no'] = '%' . $routeNumber . '%';
            }
        }

        // Bus filter
        if (!empty($filters['bus'])) {
            $whereParts[]       = "t.bus_reg_no LIKE :bus";
            $params['bus']      = '%' . $filters['bus'] . '%';
        }

        // Operator type filter
        if (!empty($filters['operator_type']) && in_array($filters['operator_type'], ['Private','SLTB'], true)) {
            $whereParts[]   = "t.operator_type = :op";
            $params['op']   = $filters['operator_type'];
        }

        // Day-of-week filter (allow 0..6, note '' means all)
        if (array_key_exists('dow', $filters) && $filters['dow'] !== '' && $filters['dow'] !== null) {
            $whereParts[]   = "t.day_of_week = :dow";
            $params['dow']  = (int)$filters['dow'];
        }

        $whereSql = $whereParts ? (' WHERE ' . implode(' AND ', $whereParts)) : '';

        // ---- data query: fetch all to allow route-based pagination in the view ----
        $sql = "SELECT t.*, r.route_no, r.stops_json
                $baseFrom
                $whereSql
                ORDER BY r.route_no+0, r.route_no, t.day_of_week, t.departure_time";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        $rows = $st->fetchAll();
        $rows = $this->hydrateRouteDisplay($rows);

        // ---- total count for pagination ----
        $cntSql = "SELECT COUNT(*) $baseFrom $whereSql";
        $cntSt  = $this->pdo->prepare($cntSql);
        $cntSt->execute($params);
        $total = (int)$cntSt->fetchColumn();

        return ['rows' => $rows, 'total' => $total];
    }

    public function counts(): array
    {
        $routes  = (int)$this->pdo->query("SELECT COUNT(*) c FROM routes")->fetch()['c'];
        $depots  = (int)$this->pdo->query("SELECT COUNT(*) c FROM sltb_depots")->fetch()['c'];
        $powners = (int)$this->pdo->query("SELECT COUNT(*) c FROM private_bus_owners")->fetch()['c'];
        $sbus    = (int)$this->pdo->query("SELECT COUNT(*) c FROM sltb_buses")->fetch()['c'];
        $pbus    = (int)$this->pdo->query("SELECT COUNT(*) c FROM private_buses")->fetch()['c'];

        return compact('depots', 'routes', 'pbus', 'sbus', 'powners');
    }

    public function routes(): array
    {
        $sql  = "SELECT route_id, route_no, stops_json, is_active
                 FROM routes
                 ORDER BY route_no+0, route_no";
        $rows = $this->pdo->query($sql)->fetchAll();

        foreach ($rows as &$r) {
            $stops = json_decode($r['stops_json'], true) ?: [];

            $first = $stops[0] ?? null;
            $last  = $stops[count($stops) - 1] ?? null;

            $start = is_array($first) ? ($first['stop'] ?? '') : $first;
            $end   = is_array($last)  ? ($last['stop']  ?? '') : $last;

            $r['label'] = sprintf('%s | %s → %s', $r['route_no'], $start, $end);
        }

        return $rows;
    }

    public function ownersWithBuses(): array
    {
        $owners = $this->pdo->query("SELECT private_operator_id, name FROM private_bus_owners ORDER BY name")->fetchAll();
        $buses  = $this->pdo->query("SELECT reg_no, private_operator_id FROM private_buses ORDER BY reg_no")->fetchAll();

        $map = [];
        foreach ($owners as $o) {
            $map[$o['private_operator_id']] = [
                'id'    => $o['private_operator_id'],
                'name'  => $o['name'],
                'buses' => []
            ];
        }

        foreach ($buses as $b) {
            if (isset($map[$b['private_operator_id']])) {
                $map[$b['private_operator_id']]['buses'][] = $b['reg_no'];
            }
        }

        return array_values($map);
    }

    public function depotsWithBuses(): array
    {
        $depots = $this->pdo->query("SELECT sltb_depot_id, name FROM sltb_depots ORDER BY name")->fetchAll();
        $buses  = $this->pdo->query("SELECT reg_no, sltb_depot_id FROM sltb_buses ORDER BY reg_no")->fetchAll();

        $map = [];
        foreach ($depots as $d) {
            $map[$d['sltb_depot_id']] = [
                'id'    => $d['sltb_depot_id'],
                'name'  => $d['name'],
                'buses' => []
            ];
        }

        foreach ($buses as $b) {
            if (isset($map[$b['sltb_depot_id']])) {
                $map[$b['sltb_depot_id']]['buses'][] = $b['reg_no'];
            }
        }

        return array_values($map);
    }

    /**
     * For bus filter datalist.
     */
    public function busList(): array
    {
        $sql = "SELECT DISTINCT bus_reg_no 
                FROM timetables 
                WHERE bus_reg_no <> '' 
                ORDER BY bus_reg_no";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Create multiple timetables for:
     *  - multiple days (checkboxes)
     *  - auto-generated turns (optional)
     */
    public function create(array $d): void
    {
        // --- normalize selected days ---
        $days = [];
        if (!empty($d['days']) && is_array($d['days'])) {
            foreach ($d['days'] as $val) {
                $days[] = (int)$val;
            }
        } elseif (isset($d['day_of_week'])) {
            // fallback if old form posts a single day_of_week
            $days[] = (int)$d['day_of_week'];
        }
        $days = array_values(array_unique($days));

        if (empty($days)) {
            $this->showAlert('Please select at least one day of the week.');
        }

        if (empty($d['departure_time'])) {
            $this->showAlert('Departure time is required.');
        }

        $auto  = !empty($d['auto_schedule']);
        $cands = []; // list of [day_of_week, departure_time, arrival_time]

        if ($auto) {
            if (empty($d['arrival_time'])) {
                $this->showAlert('Arrival time is required for auto scheduling.');
            }

            $startMin    = $this->timeToMinutes($d['departure_time']);
            $endMin      = $this->timeToMinutes($d['arrival_time']);
            $tripMinutes = $endMin - $startMin;

            if ($tripMinutes <= 0) {
                $this->showAlert('Arrival time must be after departure time for auto scheduling.');
            }

            $waitMinutes = max(0, (int)($d['wait_minutes'] ?? 0));
            $turns       = max(1, (int)($d['turns_per_day'] ?? 1));

            foreach ($days as $dow) {
                for ($i = 0; $i < $turns; $i++) {
                    $depMin = $startMin + $i * ($tripMinutes + $waitMinutes);
                    $arrMin = $depMin + $tripMinutes;

                    $cands[] = [
                        'day_of_week'    => $dow,
                        'departure_time' => $this->minutesToTime($depMin),
                        'arrival_time'   => $this->minutesToTime($arrMin),
                    ];
                }
            }
        } else {
            $depTime = $d['departure_time'];
            $arrTime = $d['arrival_time'] ?: null;

            foreach ($days as $dow) {
                $cands[] = [
                    'day_of_week'    => $dow,
                    'departure_time' => $depTime,
                    'arrival_time'   => $arrTime,
                ];
            }
        }

        // --- 1) duplicate check: same route + day + departure ---
        $dupStmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM timetables
            WHERE route_id = ? AND day_of_week = ? AND departure_time = ?
        ");
        foreach ($cands as $c) {
            $dupStmt->execute([$d['route_id'], $c['day_of_week'], $c['departure_time']]);
            if ($dupStmt->fetchColumn() > 0) {
                $this->showAlert('A schedule for this route already starts at this time.');
            }
        }

        // --- 2) overlap check for same bus per day ---
        $bus         = trim($d['bus_reg_no']);
        $candByDay   = [];
        foreach ($cands as $c) {
            $day = $c['day_of_week'];
            if (!isset($candByDay[$day])) {
                $candByDay[$day] = [];
            }
            $candByDay[$day][] = $c;
        }

        $busStmt = $this->pdo->prepare("
            SELECT departure_time, arrival_time
            FROM timetables
            WHERE bus_reg_no = ? AND day_of_week = ?
        ");

        foreach ($candByDay as $day => $list) {
            $busStmt->execute([$bus, $day]);
            $existing = $busStmt->fetchAll();

            $existingIntervals = [];
            foreach ($existing as $row) {
                if (empty($row['arrival_time'])) {
                    continue;
                }
                $existingIntervals[] = [
                    'start' => $this->timeToMinutes($row['departure_time']),
                    'end'   => $this->timeToMinutes($row['arrival_time']),
                ];
            }

            $newIntervals = [];
            foreach ($list as $c) {
                if (empty($c['arrival_time'])) {
                    continue;
                }

                $start = $this->timeToMinutes($c['departure_time']);
                $end   = $this->timeToMinutes($c['arrival_time']);

                // against existing
                foreach ($existingIntervals as $iv) {
                    if ($start < $iv['end'] && $end > $iv['start']) {
                        $this->showAlert('This bus already has a schedule overlapping the generated time window.');
                    }
                }

                // against earlier new ones of same day
                foreach ($newIntervals as $iv) {
                    if ($start < $iv['end'] && $end > $iv['start']) {
                        $this->showAlert('Generated schedules overlap each other. Please adjust wait time or number of turns.');
                    }
                }

                $newIntervals[] = ['start' => $start, 'end' => $end];
            }
        }

        // --- 3) final insert of all generated schedules ---
        $sqlInsert = "INSERT INTO timetables (
                        operator_type, route_id, bus_reg_no, day_of_week,
                        departure_time, arrival_time, effective_from, effective_to
                      ) VALUES (?,?,?,?,?,?,?,?)";
        $ins = $this->pdo->prepare($sqlInsert);

        foreach ($cands as $c) {
            $ins->execute([
                $d['operator_type'],
                $d['route_id'],
                $bus,
                $c['day_of_week'],
                $c['departure_time'],
                $c['arrival_time'],
                $d['effective_from'] ?: null,
                $d['effective_to']   ?: null,
            ]);
        }
    }

    public function createRoute(array $d): void
    {
        // duplicate route_no guard
        $check = $this->pdo->prepare("SELECT COUNT(*) FROM routes WHERE route_no = ?");
        $check->execute([$d['route_no']]);
        if ($check->fetchColumn() > 0) {
            $this->showAlert('Route number already exists!', '/A/timetables');
        }

        $sql = "INSERT INTO routes (route_no, is_active, stops_json)
                VALUES (?,?,?)";
        $st  = $this->pdo->prepare($sql);
        $st->execute([
            $d['route_no'],
            $d['is_active'] ?? 1,
            $d['stops_json'] ?: '[]',
        ]);
    }

    public function delete($id): void
    {
        $st = $this->pdo->prepare("DELETE FROM timetables WHERE timetable_id = ?");
        $st->execute([$id]);
    }

    public function update(array $d): void
    {
        // minimal validation (keep consistent with create() checks)
        if (empty($d['timetable_id'])) {
            $this->showAlert('Missing timetable id.');
        }
        if (empty($d['departure_time'])) {
            $this->showAlert('Departure time is required.');
        }

        $sql = "UPDATE timetables SET
                    operator_type=?,
                    route_id=?,
                    bus_reg_no=?,
                    day_of_week=?,
                    departure_time=?,
                    arrival_time=?,
                    effective_from=?,
                    effective_to=?
                WHERE timetable_id=?";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            $d['operator_type'],
            $d['route_id'],
            trim((string)$d['bus_reg_no']),
            (int)$d['day_of_week'],
            $d['departure_time'],
            ($d['arrival_time'] ?? '') !== '' ? $d['arrival_time'] : null,
            ($d['effective_from'] ?? '') !== '' ? $d['effective_from'] : null,
            ($d['effective_to'] ?? '') !== '' ? $d['effective_to'] : null,
            (int)$d['timetable_id'],
        ]);
    }
}
