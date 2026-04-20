<?php
namespace App\models\timekeeper_private;

use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    protected int $opId;
    private ?string $locationCache = null;
    private ?array $routeStopColumns = null;

    public function __construct(int $privateOperatorId = 0) {
        $this->pdo  = $GLOBALS['db'];
        $u = $_SESSION['user'] ?? [];
        $sessionOpId = (int)($u['private_operator_id'] ?? 0);
        $this->opId = $sessionOpId > 0 ? $sessionOpId : (int)$privateOperatorId;
    }

    /** Page header label to match your SLTB markup: use depot_name key */
    public function info(): array {
        if ($this->opId <= 0) {
            $location = $this->currentLocation();
            if ($location !== '' && strcasecmp($location, 'Common') !== 0) {
                return ['depot_name' => $this->locationDisplayName($location) . ' Private Network'];
            }
            return ['depot_name' => 'Private Network'];
        }

        // final DB uses private_bus_owners for operator profile
        $st = $this->pdo->prepare("SELECT name FROM private_bus_owners WHERE private_operator_id=?");
        $st->execute([$this->opId]);
        $name = $st->fetchColumn();
        return ['depot_name' => (string)($name ?: 'My Operator')];
    }

    protected function currentLocation(): string
    {
        if ($this->locationCache !== null) {
            return $this->locationCache;
        }

        $u = $_SESSION['user'] ?? [];
        $uid = (int)($u['user_id'] ?? $u['id'] ?? 0);
        $cached = trim((string)($u['timekeeper_location'] ?? ''));

        try {
            if ($uid > 0) {
                $st = $this->pdo->prepare(
                    "SELECT COALESCE(NULLIF(timekeeper_location,''), 'Common') FROM users WHERE user_id = ? LIMIT 1"
                );
                $st->execute([$uid]);
                $location = trim((string)($st->fetchColumn() ?: ''));
                if ($location !== '') {
                    $this->locationCache = $location;
                    $_SESSION['user']['timekeeper_location'] = $location;
                    return $location;
                }
            }
        } catch (\Throwable $e) {
            // Fall back to session/default when DB lookup is unavailable.
        }

        $location = $cached !== '' ? $cached : 'Common';
        $this->locationCache = $location;
        $_SESSION['user']['timekeeper_location'] = $location;
        return $location;
    }

    protected function locationDisplayName(?string $location = null): string
    {
        $value = trim((string)($location ?? $this->currentLocation()));
        if ($value === '') {
            return 'Common';
        }

        return ucwords(strtolower($value));
    }

    protected function normalizedRouteToken(string $text): string
    {
        $norm = strtolower(trim($text));
        if ($norm === '') {
            return '';
        }
        $norm = preg_replace('/[^a-z0-9 ]+/i', ' ', $norm) ?? $norm;
        $norm = preg_replace('/\s+/', ' ', $norm) ?? $norm;
        return trim($norm);
    }

    protected function baseRouteStopColumns(): array
    {
        if ($this->routeStopColumns !== null) {
            return $this->routeStopColumns;
        }

        $out = ['stops_json' => false, 'stops' => false];
        try {
            $st = $this->pdo->query('SHOW COLUMNS FROM routes');
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $name = strtolower((string)($row['Field'] ?? ''));
                if ($name === 'stops_json') {
                    $out['stops_json'] = true;
                }
                if ($name === 'stops') {
                    $out['stops'] = true;
                }
            }
        } catch (\Throwable $e) {
            $out['stops_json'] = true;
        }

        $this->routeStopColumns = $out;
        return $out;
    }

    protected function routeStopsExpression(string $alias = 'r'): string
    {
        $cols = $this->baseRouteStopColumns();
        if ($cols['stops_json'] && $cols['stops']) {
            return "COALESCE({$alias}.stops_json, {$alias}.stops)";
        }
        if ($cols['stops_json']) {
            return "{$alias}.stops_json";
        }
        if ($cols['stops']) {
            return "{$alias}.stops";
        }
        return "'[]'";
    }

    protected function collectRouteStops(mixed $node, array &$out): void
    {
        if (is_string($node)) {
            $value = trim($node);
            if ($value !== '') {
                $out[] = $value;
            }
            return;
        }
        if (!is_array($node)) {
            return;
        }

        if (isset($node['stops'])) {
            $this->collectRouteStops($node['stops'], $out);
            return;
        }

        foreach (['stop', 'name', 'location'] as $key) {
            if (isset($node[$key]) && is_string($node[$key])) {
                $value = trim((string)$node[$key]);
                if ($value !== '') {
                    $out[] = $value;
                }
                return;
            }
        }

        foreach ($node as $child) {
            $this->collectRouteStops($child, $out);
        }
    }

    protected function extractRouteStops(string $raw): array
    {
        $text = trim($raw);
        if ($text === '' || strtolower($text) === 'null') {
            return [];
        }

        $attempts = [$text, stripslashes($text), str_replace('\\"', '"', $text)];
        if (
            (str_starts_with($text, '"') && str_ends_with($text, '"'))
            || (str_starts_with($text, "'") && str_ends_with($text, "'"))
        ) {
            $attempts[] = substr($text, 1, -1);
        }

        foreach ($attempts as $candidate) {
            $decoded = json_decode($candidate, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            if (is_string($decoded)) {
                $decoded2 = json_decode($decoded, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $decoded = $decoded2;
                }
            }

            $out = [];
            $this->collectRouteStops($decoded, $out);
            if (!empty($out)) {
                return $out;
            }
        }

        if (preg_match('/,|\||->|;|>/', $text)) {
            $parts = preg_split('/\s*(?:,|\||->|;|>)\s*/', $text) ?: [];
            $parts = array_values(array_filter(array_map('trim', $parts), static fn($value) => $value !== ''));
            if (!empty($parts)) {
                return $parts;
            }
        }

        return [$text];
    }

    protected function routeMatchesLocation(string $stopsRaw, ?string $location = null): bool
    {
        $locationNorm = $this->normalizedRouteToken((string)($location ?? $this->currentLocation()));
        if ($locationNorm === '' || $locationNorm === 'common') {
            return true;
        }

        $stops = $this->extractRouteStops($stopsRaw ?: '[]');
        if (empty($stops)) {
            return false;
        }

        foreach ($stops as $stop) {
            $stopNorm = $this->normalizedRouteToken((string)$stop);
            if ($stopNorm === '') {
                continue;
            }
            if (
                $stopNorm === $locationNorm
                || str_contains($stopNorm, $locationNorm)
                || str_contains($locationNorm, $stopNorm)
            ) {
                return true;
            }
        }

        return false;
    }

    protected function visiblePrivateTimetablesForDay(int $dow): array
    {
        $expr = $this->routeStopsExpression('r');
        $scope = $this->opId > 0 ? ' AND pb.private_operator_id = :op' : '';

        try {
            $st = $this->pdo->prepare(
                "SELECT tt.timetable_id, tt.bus_reg_no, tt.route_id, {$expr} AS stops_raw
                 FROM timetables tt
                 JOIN private_buses pb ON pb.reg_no = tt.bus_reg_no{$scope}
                 JOIN routes r ON r.route_id = tt.route_id
                 WHERE tt.operator_type = 'Private'
                   AND tt.day_of_week = :dow
                 ORDER BY tt.timetable_id ASC"
            );

            $params = [':dow' => $dow];
            if ($this->opId > 0) {
                $params[':op'] = $this->opId;
            }
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [
                'rows' => [],
                'timetable_ids' => [],
                'bus_reg_nos' => [],
                'route_ids' => [],
            ];
        }

        $filtered = [];
        $timetableIds = [];
        $busRegs = [];
        $routeIds = [];
        foreach ($rows as $row) {
            if (!$this->routeMatchesLocation((string)($row['stops_raw'] ?? '[]'))) {
                continue;
            }

            $filtered[] = $row;

            $timetableId = (int)($row['timetable_id'] ?? 0);
            $routeId = (int)($row['route_id'] ?? 0);
            $busReg = trim((string)($row['bus_reg_no'] ?? ''));

            if ($timetableId > 0) {
                $timetableIds[$timetableId] = $timetableId;
            }
            if ($routeId > 0) {
                $routeIds[$routeId] = $routeId;
            }
            if ($busReg !== '') {
                $busRegs[$busReg] = $busReg;
            }
        }

        return [
            'rows' => $filtered,
            'timetable_ids' => array_values($timetableIds),
            'bus_reg_nos' => array_values($busRegs),
            'route_ids' => array_values($routeIds),
        ];
    }
}
