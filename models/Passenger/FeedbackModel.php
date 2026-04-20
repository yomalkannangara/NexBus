<?php
namespace App\models\Passenger;

use PDO;

abstract class BaseModel
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = $GLOBALS['db'];
    }
}

class FeedbackModel extends BaseModel
{
    protected string $tbl = 'complaints';

    private ?bool $complaintsHasRating = null;
    private ?bool $timetablesHasEffectiveWindow = null;

    private function complaintsHasRating(): bool
    {
        if ($this->complaintsHasRating !== null)
            return $this->complaintsHasRating;

        try {
            $st = $this->pdo->prepare(
                "SELECT COUNT(*) c
                   FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'complaints'
                    AND COLUMN_NAME = 'rating'"
            );
            $st->execute();
            $this->complaintsHasRating = ((int)($st->fetch(PDO::FETCH_ASSOC)['c'] ?? 0)) > 0;
        }
        catch (\Throwable $e) {
            $this->complaintsHasRating = false;
        }

        return $this->complaintsHasRating;
    }

    private function timetablesHasEffectiveWindow(): bool
    {
        if ($this->timetablesHasEffectiveWindow !== null)
            return $this->timetablesHasEffectiveWindow;

        try {
            $st = $this->pdo->prepare(
                "SELECT COUNT(*) c
                   FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'timetables'
                    AND COLUMN_NAME IN ('effective_from', 'effective_to')"
            );
            $st->execute();
            $this->timetablesHasEffectiveWindow = ((int)($st->fetch(PDO::FETCH_ASSOC)['c'] ?? 0)) === 2;
        }
        catch (\Throwable $e) {
            $this->timetablesHasEffectiveWindow = false;
        }

        return $this->timetablesHasEffectiveWindow;
    }

    private function normalizeOperatorType(?string $raw): string
    {
        $v = strtoupper(trim((string)$raw));
        return ($v === 'PRIVATE') ? 'Private' : 'SLTB';
    }

    private function normalizeCategory(?string $raw): string
    {
        return strtolower(trim((string)$raw)) === 'complaint' ? 'complaint' : 'feedback';
    }

    private function normalizeBusRegNo($raw): ?string
    {
        $v = trim((string)$raw);
        if ($v === '')
            return null;

        $lower = strtolower($v);
        if (in_array($lower, ['undefined', 'null', 'nan'], true))
            return null;

        return $v;
    }

    private function canonicalRouteNo(?string $raw): string
    {
        $v = trim((string)$raw);
        if ($v === '')
            return '';

        if (preg_match('/^\d+$/', $v)) {
            $v = ltrim($v, '0');
            return ($v === '') ? '0' : $v;
        }

        return strtoupper($v);
    }

    private function routeExists(int $routeId): bool
    {
        $st = $this->pdo->prepare("SELECT 1 FROM routes WHERE route_id = ? LIMIT 1");
        $st->execute([$routeId]);
        return (bool)$st->fetchColumn();
    }

    private function routeGroupIdsForRouteId(int $routeId): array
    {
        if ($routeId <= 0)
            return [];

        $st = $this->pdo->prepare("SELECT route_no FROM routes WHERE route_id = ? LIMIT 1");
        $st->execute([$routeId]);
        $routeNo = $st->fetchColumn();

        if ($routeNo === false)
            return [$routeId];

        $canon = $this->canonicalRouteNo((string)$routeNo);
        if ($canon === '')
            return [$routeId];

        if (ctype_digit($canon)) {
            $st = $this->pdo->prepare(
                "SELECT route_id
                   FROM routes
                  WHERE route_no REGEXP '^[0-9]+$'
                    AND CAST(route_no AS UNSIGNED) = ?
               ORDER BY route_id"
            );
            $st->execute([(int)$canon]);
        }
        else {
            $st = $this->pdo->prepare(
                "SELECT route_id
                   FROM routes
                  WHERE UPPER(TRIM(route_no)) = ?
               ORDER BY route_id"
            );
            $st->execute([$canon]);
        }

        $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []);
        if (empty($ids))
            $ids = [$routeId];
        if (!in_array($routeId, $ids, true))
            $ids[] = $routeId;

        $ids = array_values(array_unique($ids));
        sort($ids);
        return $ids;
    }

    private function busesByRouteIds(array $routeIds, bool $applyEffectiveWindow): array
    {
        if (empty($routeIds))
            return [];

        $placeholders = implode(',', array_fill(0, count($routeIds), '?'));
        $sql = "SELECT DISTINCT TRIM(t.bus_reg_no) AS bus_reg_no, t.operator_type
                  FROM timetables t
                 WHERE t.route_id IN ({$placeholders})
                   AND NULLIF(TRIM(t.bus_reg_no), '') IS NOT NULL
                   AND LOWER(TRIM(t.bus_reg_no)) <> 'undefined'";

        if ($applyEffectiveWindow && $this->timetablesHasEffectiveWindow()) {
            $sql .= "
                   AND (t.effective_from IS NULL OR t.effective_from <= CURDATE())
                   AND (t.effective_to IS NULL OR t.effective_to >= CURDATE())";
        }

        $sql .= "
              ORDER BY t.bus_reg_no";

        $st = $this->pdo->prepare($sql);
        $st->execute($routeIds);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function operatorTypeForRouteBus(int $routeId, string $busRegNo): ?string
    {
        $routeIds = $this->routeGroupIdsForRouteId($routeId);
        if (empty($routeIds))
            return null;

        $busRegNo = trim($busRegNo);
        if ($busRegNo === '')
            return null;

        $placeholders = implode(',', array_fill(0, count($routeIds), '?'));
        $baseSql = "SELECT operator_type
                      FROM timetables
                     WHERE route_id IN ({$placeholders})
                       AND TRIM(bus_reg_no) = ?";

        $params = $routeIds;
        $params[] = $busRegNo;

        $sql = $baseSql;
        if ($this->timetablesHasEffectiveWindow()) {
            $sql .= "
                       AND (effective_from IS NULL OR effective_from <= CURDATE())
                       AND (effective_to IS NULL OR effective_to >= CURDATE())";
        }
        $sql .= "
                  ORDER BY timetable_id DESC
                     LIMIT 1";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row && $this->timetablesHasEffectiveWindow()) {
            $st = $this->pdo->prepare($baseSql . " ORDER BY timetable_id DESC LIMIT 1");
            $st->execute($params);
            $row = $st->fetch(PDO::FETCH_ASSOC);
        }

        if (!$row || empty($row['operator_type']))
            return null;

        return $this->normalizeOperatorType((string)$row['operator_type']);
    }

    private function getRouteDisplayName(string $stopsJson): string
    {
        $stops = json_decode($stopsJson, true) ?: [];
        if (empty($stops))
            return 'Unknown';

        $first = is_array($stops[0])
            ? ($stops[0]['stop'] ?? $stops[0]['name'] ?? 'Start')
            : $stops[0];
        $last = is_array($stops[count($stops) - 1])
            ? ($stops[count($stops) - 1]['stop'] ?? $stops[count($stops) - 1]['name'] ?? 'End')
            : $stops[count($stops) - 1];

        return "$first - $last";
    }

    public function routes(): array
    {
        $sql = "SELECT r.route_id, r.route_no, r.stops_json, COUNT(t.timetable_id) AS tt_count
                  FROM routes r
             LEFT JOIN timetables t ON t.route_id = r.route_id
              GROUP BY r.route_id, r.route_no, r.stops_json
              ORDER BY r.route_no+0, r.route_no, r.route_id";

        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $bestByRouteNo = [];
        foreach ($rows as $r) {
            $canonNo = $this->canonicalRouteNo($r['route_no'] ?? '');
            $key = $canonNo !== '' ? $canonNo : ('route-' . (int)($r['route_id'] ?? 0));
            $displayNo = $canonNo !== '' ? $canonNo : trim((string)($r['route_no'] ?? ''));
            if ($displayNo === '')
                $displayNo = (string)($r['route_id'] ?? '');

            $candidate = [
                'route_id' => (int)($r['route_id'] ?? 0),
                'route_no' => $displayNo,
                'stops_json' => (string)($r['stops_json'] ?? '[]'),
                'name' => $this->getRouteDisplayName((string)($r['stops_json'] ?? '[]')),
                'tt_count' => (int)($r['tt_count'] ?? 0),
            ];

            if (!isset($bestByRouteNo[$key])) {
                $bestByRouteNo[$key] = $candidate;
                continue;
            }

            $current = $bestByRouteNo[$key];
            $replace = false;
            if ($candidate['tt_count'] > $current['tt_count']) {
                $replace = true;
            }
            elseif ($candidate['tt_count'] === $current['tt_count']
                && $candidate['route_id'] < $current['route_id']) {
                $replace = true;
            }

            if ($replace)
                $bestByRouteNo[$key] = $candidate;
        }

        $out = array_values($bestByRouteNo);
        usort($out, static function (array $a, array $b): int {
            $aNo = (string)($a['route_no'] ?? '');
            $bNo = (string)($b['route_no'] ?? '');

            $aNum = ctype_digit($aNo);
            $bNum = ctype_digit($bNo);
            if ($aNum && $bNum) {
                $cmp = ((int)$aNo) <=> ((int)$bNo);
                if ($cmp !== 0)
                    return $cmp;
            }
            elseif ($aNum !== $bNum) {
                return $aNum ? -1 : 1;
            }

            $cmp = strcasecmp($aNo, $bNo);
            if ($cmp !== 0)
                return $cmp;

            return ((int)($a['route_id'] ?? 0)) <=> ((int)($b['route_id'] ?? 0));
        });

        foreach ($out as &$r)
            unset($r['tt_count']);

        return $out;
    }

    public function addFeedback(array $p, ?int $passengerId): int
    {
        if (($passengerId ?? 0) <= 0)
            throw new \RuntimeException('Passenger account not found. Please sign in again.');

        $routeId = !empty($p['route_id']) ? (int)$p['route_id'] : 0;
        if ($routeId <= 0 || !$this->routeExists($routeId))
            throw new \RuntimeException('Please select a valid route.');

        $description = trim((string)($p['description'] ?? ''));
        if ($description === '')
            throw new \RuntimeException('Description is required.');

        $category = $this->normalizeCategory($p['type'] ?? null);

        $busRegNo = $this->normalizeBusRegNo($p['bus_id'] ?? null);
        if ($busRegNo === null) {
            // Hidden fallback for cases where disabled select controls are not posted.
            $busRegNo = $this->normalizeBusRegNo($p['selected_bus_id'] ?? null);
        }

        if ($busRegNo === null)
            throw new \RuntimeException('Please select a bus number.');

        $operatorType = $this->normalizeOperatorType($p['bus_type'] ?? null);
        if ($busRegNo !== null) {
            $detectedOperatorType = $this->operatorTypeForRouteBus($routeId, $busRegNo);
            if ($detectedOperatorType === null)
                throw new \RuntimeException('Selected bus is not available for the chosen route.');

            $operatorType = $detectedOperatorType;
        }

        $rating = null;
        if ($category === 'feedback' && isset($p['rating']) && is_numeric($p['rating'])) {
            $rating = (int)$p['rating'];
            if ($rating < 1 || $rating > 5)
                $rating = null;
        }

        if ($this->complaintsHasRating()) {
            $sql = "INSERT INTO {$this->tbl}
                      (passenger_id, operator_type, bus_reg_no, route_id, category, description, rating, status, created_at)
                    VALUES (?,?,?,?,?,?,?, 'Open', NOW())";
            $st = $this->pdo->prepare($sql);
            $ok = $st->execute([
                (int)$passengerId,
                $operatorType,
                $busRegNo,
                $routeId,
                $category,
                $description,
                $rating
            ]);
        }
        else {
            $sql = "INSERT INTO {$this->tbl}
                      (passenger_id, operator_type, bus_reg_no, route_id, category, description, status, created_at)
                    VALUES (?,?,?,?,?,?, 'Open', NOW())";
            $st = $this->pdo->prepare($sql);
            $ok = $st->execute([
                (int)$passengerId,
                $operatorType,
                $busRegNo,
                $routeId,
                $category,
                $description
            ]);
        }

        if (!$ok || $st->rowCount() < 1)
            throw new \RuntimeException('Could not save feedback. Please try again.');

        $insertId = (int)$this->pdo->lastInsertId();
        if ($insertId <= 0)
            throw new \RuntimeException('Feedback save confirmation failed. Please retry.');

        return $insertId;
    }

    public function mine(int $passengerId): array
    {
        $ratingSelect = $this->complaintsHasRating() ? 'rating' : 'NULL AS rating';
        $sql = "SELECT complaint_id, created_at, route_id,
                       CASE
                         WHEN LOWER(TRIM(COALESCE(bus_reg_no, ''))) IN ('', 'undefined', 'null') THEN NULL
                         ELSE bus_reg_no
                       END AS bus_reg_no,
                       operator_type,
                       category,
                       status,
                       COALESCE(reply_text, NULL) AS reply_text,
                       {$ratingSelect},
                       description
                  FROM {$this->tbl}
                 WHERE passenger_id = ?
              ORDER BY complaint_id DESC";

        $st = $this->pdo->prepare($sql);
        $st->execute([$passengerId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function busesByRoute(int $routeId): array
    {
        $routeIds = $this->routeGroupIdsForRouteId($routeId);
        if (empty($routeIds))
            return [];

        $rows = $this->busesByRouteIds($routeIds, true);
        if (empty($rows) && $this->timetablesHasEffectiveWindow()) {
            // If no current effective rows exist, still show known buses for that route group.
            $rows = $this->busesByRouteIds($routeIds, false);
        }

        $out = [];
        foreach ($rows as $row) {
            $regNo = $this->normalizeBusRegNo($row['bus_reg_no'] ?? null);
            if ($regNo === null)
                continue;

            $out[$regNo] = [
                'bus_reg_no' => $regNo,
                'operator_type' => $this->normalizeOperatorType($row['operator_type'] ?? null),
            ];
        }

        ksort($out, SORT_NATURAL | SORT_FLAG_CASE);
        return array_values($out);
    }
}
