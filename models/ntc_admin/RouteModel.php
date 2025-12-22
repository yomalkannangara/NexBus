<?php
namespace App\models\ntc_admin;

use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];
    }
}

class RouteModel extends BaseModel
{
    private function hydrate(array $rows): array
    {
        foreach ($rows as &$r) {
            $stops = json_decode($r['stops_json'] ?? '[]', true) ?: [];
            $first = $stops[0] ?? null;
            $last  = $stops[count($stops) - 1] ?? null;

            $start = is_array($first) ? ($first['stop'] ?? '') : $first;
            $end   = is_array($last)  ? ($last['stop']  ?? '') : $last;

            $r['stops_count'] = is_array($stops) ? count($stops) : 0;
            $r['start'] = $start ?: '';
            $r['end']   = $end   ?: '';
            $r['label'] = sprintf('%s | %s → %s', $r['route_no'], $r['start'], $r['end']);
            // add buses_count default
            $r['buses_count'] = isset($r['buses_count']) ? (int)$r['buses_count'] : 0;
            // add today_schedules default
            $r['today_schedules'] = isset($r['today_schedules']) ? (int)$r['today_schedules'] : 0;
            // collect weekly counts into an array
            $week = [
                (int)($r['dow0'] ?? 0),
                (int)($r['dow1'] ?? 0),
                (int)($r['dow2'] ?? 0),
                (int)($r['dow3'] ?? 0),
                (int)($r['dow4'] ?? 0),
                (int)($r['dow5'] ?? 0),
                (int)($r['dow6'] ?? 0),
            ];
            $r['week_counts'] = $week;
        }
        return $rows;
    }

    public function list(array $filters = []): array
    {
        // include distinct bus count + schedules today + weekly breakdown from timetables
        $sql = "SELECT route_id, route_no, is_active, stops_json,
                       COALESCE(
                         (SELECT COUNT(DISTINCT tt.bus_reg_no)
                          FROM timetables tt
                          WHERE tt.route_id = routes.route_id),
                         0
                       ) AS buses_count,
                       COALESCE(
                         (SELECT COUNT(*)
                          FROM timetables tt
                          WHERE tt.route_id = routes.route_id
                            AND tt.day_of_week = :today
                            AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                            AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                         ),
                         0
                       ) AS today_schedules,
                       COALESCE((SELECT COUNT(*) FROM timetables tt WHERE tt.route_id = routes.route_id AND tt.day_of_week = 0
                                AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                                AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                       ),0) AS dow0,
                       COALESCE((SELECT COUNT(*) FROM timetables tt WHERE tt.route_id = routes.route_id AND tt.day_of_week = 1
                                AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                                AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                       ),0) AS dow1,
                       COALESCE((SELECT COUNT(*) FROM timetables tt WHERE tt.route_id = routes.route_id AND tt.day_of_week = 2
                                AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                                AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                       ),0) AS dow2,
                       COALESCE((SELECT COUNT(*) FROM timetables tt WHERE tt.route_id = routes.route_id AND tt.day_of_week = 3
                                AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                                AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                       ),0) AS dow3,
                       COALESCE((SELECT COUNT(*) FROM timetables tt WHERE tt.route_id = routes.route_id AND tt.day_of_week = 4
                                AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                                AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                       ),0) AS dow4,
                       COALESCE((SELECT COUNT(*) FROM timetables tt WHERE tt.route_id = routes.route_id AND tt.day_of_week = 5
                                AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                                AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                       ),0) AS dow5,
                       COALESCE((SELECT COUNT(*) FROM timetables tt WHERE tt.route_id = routes.route_id AND tt.day_of_week = 6
                                AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                                AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                       ),0) AS dow6
                FROM routes";
        $where = [];
        $params = [];

        // filters
        if (!empty($filters['q'])) {
            $where[] = "route_no LIKE :q";
            $params[':q'] = '%' . trim((string)$filters['q']) . '%';
        }
        if (isset($filters['active']) && $filters['active'] !== '') {
            $where[] = "is_active = :act";
            $params[':act'] = (int)$filters['active'];
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY route_no+0, route_no';

        // bind today's weekday (PHP: 0=Sun..6=Sat)
        $params[':today'] = (int)date('w');

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();
        return $this->hydrate($rows);
    }

    public function create(array $d): void
    {
        // Duplicate guard
        $chk = $this->pdo->prepare('SELECT COUNT(*) FROM routes WHERE route_no = ?');
        $chk->execute([$d['route_no']]);
        if ((int)$chk->fetchColumn() > 0) {
            throw new \RuntimeException('Route number already exists');
        }

        $sql = 'INSERT INTO routes (route_no, is_active, stops_json) VALUES (?,?,?)';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            $d['route_no'],
            isset($d['is_active']) ? (int)$d['is_active'] : 1,
            $d['stops_json'] ?: '[]',
        ]);
    }

    public function setActive(int $routeId, bool $active): void
    {
        $st = $this->pdo->prepare('UPDATE routes SET is_active = ? WHERE route_id = ?');
        $st->execute([$active ? 1 : 0, $routeId]);
    }

    public function delete(int $routeId): void
    {
        $st = $this->pdo->prepare('DELETE FROM routes WHERE route_id = ?');
        $st->execute([$routeId]);
    }

    public function update(array $d): void
    {
        $routeId = (int)($d['route_id'] ?? 0);
        if ($routeId <= 0) {
            throw new \InvalidArgumentException('Invalid route id');
        }

        $routeNo   = trim((string)($d['route_no'] ?? ''));
        if ($routeNo === '') {
            throw new \InvalidArgumentException('Route number is required');
        }
        $isActive  = isset($d['is_active']) ? (int)$d['is_active'] : 1;
        $stopsJson = ($d['stops_json'] ?? '') !== '' ? (string)$d['stops_json'] : '[]';

        // Duplicate guard (exclude self)
        $chk = $this->pdo->prepare('SELECT COUNT(*) FROM routes WHERE route_no = ? AND route_id <> ?');
        $chk->execute([$routeNo, $routeId]);
        if ((int)$chk->fetchColumn() > 0) {
            throw new \RuntimeException('Route number already exists');
        }

        $sql = 'UPDATE routes SET route_no = ?, is_active = ?, stops_json = ? WHERE route_id = ?';
        $st  = $this->pdo->prepare($sql);
        $st->execute([$routeNo, $isActive, $stopsJson, $routeId]);
    }

    public function listPaged(array $filters, int $limit, int $offset): array
    {
        // Build WHERE for both count and data queries
        $where  = [];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = "route_no LIKE :q";
            $params[':q'] = '%' . trim((string)$filters['q']) . '%';
        }
        if (isset($filters['active']) && $filters['active'] !== '') {
            $where[] = "is_active = :act";
            $params[':act'] = (int)$filters['active'];
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        // total count
        $countSql = "SELECT COUNT(*) FROM routes" . $whereSql;
        $stc = $this->pdo->prepare($countSql);
        $stc->execute($params);
        $total = (int)$stc->fetchColumn();

        // data rows (same projections as list())
        $sql = "SELECT route_id, route_no, is_active, stops_json,
                       COALESCE(
                         (SELECT COUNT(DISTINCT tt.bus_reg_no)
                          FROM timetables tt
                          WHERE tt.route_id = routes.route_id),
                         0
                       ) AS buses_count,
                       COALESCE(
                         (SELECT COUNT(*)
                          FROM timetables tt
                          WHERE tt.route_id = routes.route_id
                            AND tt.day_of_week = :today
                            AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                            AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                         ),
                         0
                       ) AS today_schedules,
                       COALESCE((SELECT COUNT(*) FROM timetables tt WHERE tt.route_id = routes.route_id AND tt.day_of_week = 0
                                AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                                AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                       ),0) AS dow0,
                       COALESCE((SELECT COUNT(*) FROM timetables tt WHERE tt.route_id = routes.route_id AND tt.day_of_week = 1
                                AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                                AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                       ),0) AS dow1,
                       COALESCE((SELECT COUNT(*) FROM timetables tt WHERE tt.route_id = routes.route_id AND tt.day_of_week = 2
                                AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                                AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                       ),0) AS dow2,
                       COALESCE((SELECT COUNT(*) FROM timetables tt WHERE tt.route_id = routes.route_id AND tt.day_of_week = 3
                                AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                                AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                       ),0) AS dow3,
                       COALESCE((SELECT COUNT(*) FROM timetables tt WHERE tt.route_id = routes.route_id AND tt.day_of_week = 4
                                AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                                AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                       ),0) AS dow4,
                       COALESCE((SELECT COUNT(*) FROM timetables tt WHERE tt.route_id = routes.route_id AND tt.day_of_week = 5
                                AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                                AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                       ),0) AS dow5,
                       COALESCE((SELECT COUNT(*) FROM timetables tt WHERE tt.route_id = routes.route_id AND tt.day_of_week = 6
                                AND (tt.effective_from IS NULL OR tt.effective_from <= CURRENT_DATE())
                                AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURRENT_DATE())
                       ),0) AS dow6
                FROM routes" . $whereSql . ' ORDER BY route_no+0, route_no LIMIT :limit OFFSET :offset';

        // bind today + limit/offset
        $params[':today'] = (int)date('w');
        $st = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            if ($k === ':limit' || $k === ':offset') continue;
            $st->bindValue($k, $v);
        }
        $st->bindValue(':limit',  max(0, (int)$limit),  PDO::PARAM_INT);
        $st->bindValue(':offset', max(0, (int)$offset), PDO::PARAM_INT);

        $st->execute();
        $rows = $this->hydrate($st->fetchAll());

        return ['rows' => $rows, 'total' => $total];
    }
}
