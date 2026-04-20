<?php
namespace App\models\common;

use PDO;

abstract class BaseModel {
    protected PDO $pdo;

    public function __construct() {
        // Uses the PDO created in bootstrap/app.php
        $this->pdo = $GLOBALS['db'];
    }

    // Optional convenience (child can override)
    protected function me(): array { return $_SESSION['user'] ?? []; }

    /** Check if a column exists in a table (cached per request). */
    protected function columnExists(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (!isset($cache[$key])) {
            try {
                $st = $this->pdo->prepare(
                    'SELECT 1
                     FROM information_schema.columns
                     WHERE table_schema = DATABASE()
                       AND table_name = ?
                       AND column_name = ?
                     LIMIT 1'
                );
                $st->execute([$table, $column]);
                $cache[$key] = (bool)$st->fetchColumn();
            } catch (\Throwable $e) {
                $cache[$key] = false;
            }
        }
        return $cache[$key];
    }

    protected function routeStopsExpr(string $alias = 'r'): string
    {
        $hasStopsJson = $this->columnExists('routes', 'stops_json');
        $hasStops = $this->columnExists('routes', 'stops');

        if ($hasStopsJson && $hasStops) {
            return "COALESCE({$alias}.stops_json, {$alias}.stops)";
        }
        if ($hasStopsJson) {
            return "{$alias}.stops_json";
        }
        if ($hasStops) {
            return "{$alias}.stops";
        }
        return "'[]'";
    }

    protected function normalizeRouteText(string $text): string
    {
        $norm = strtolower(trim($text));
        if ($norm === '') {
            return '';
        }

        $norm = preg_replace('/[^a-z0-9 ]+/i', ' ', $norm) ?? $norm;
        $norm = preg_replace('/\s+/', ' ', $norm) ?? $norm;
        return trim($norm);
    }

    protected function collectStopNamesFromNode(mixed $node, array &$out): void
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
            $this->collectStopNamesFromNode($node['stops'], $out);
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
            $this->collectStopNamesFromNode($child, $out);
        }
    }

    protected function extractRouteStopNames(string $raw): array
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
            $this->collectStopNamesFromNode($decoded, $out);
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

    protected function routeContainsLocationValue(string $stopsRaw, ?string $location): bool
    {
        $locationNorm = $this->normalizeRouteText((string)($location ?? ''));
        if ($locationNorm === '' || $locationNorm === 'common') {
            return true;
        }

        $stops = $this->extractRouteStopNames($stopsRaw ?: '[]');
        if (empty($stops)) {
            return false;
        }

        foreach ($stops as $stop) {
            $stopNorm = $this->normalizeRouteText((string)$stop);
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

    protected function routeStopRows(array $routeIds): array
    {
        $routeIds = array_values(array_unique(array_filter(array_map('intval', $routeIds), static fn($id) => $id > 0)));
        if (empty($routeIds)) {
            return [];
        }

        try {
            $expr = $this->routeStopsExpr('r');
            $ph = implode(',', array_fill(0, count($routeIds), '?'));
            $st = $this->pdo->prepare(
                "SELECT r.route_id, {$expr} AS stops_raw
                 FROM routes r
                 WHERE r.route_id IN ({$ph})"
            );
            $st->execute($routeIds);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $routeId = (int)($row['route_id'] ?? 0);
            if ($routeId <= 0) {
                continue;
            }
            $out[$routeId] = [
                'route_id' => $routeId,
                'stops_raw' => (string)($row['stops_raw'] ?? '[]'),
            ];
        }

        return $out;
    }

    protected function privateTimekeeperVisibleRouteIds(?string $location, int $operatorId = 0): array
    {
        $expr = $this->routeStopsExpr('r');
        $scope = $operatorId > 0 ? ' AND pb.private_operator_id = :op' : '';

        try {
            $st = $this->pdo->prepare(
                "SELECT DISTINCT tt.route_id, {$expr} AS stops_raw
                 FROM timetables tt
                 JOIN private_buses pb ON pb.reg_no = tt.bus_reg_no{$scope}
                 JOIN routes r ON r.route_id = tt.route_id
                 WHERE tt.operator_type = 'Private'
                   AND tt.day_of_week = :dow
                   AND tt.route_id IS NOT NULL"
            );

            $params = [':dow' => (int)date('w')];
            if ($operatorId > 0) {
                $params[':op'] = $operatorId;
            }
            $st->execute($params);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        $visible = [];
        foreach ($rows as $row) {
            $routeId = (int)($row['route_id'] ?? 0);
            if ($routeId <= 0) {
                continue;
            }
            if (!$this->routeContainsLocationValue((string)($row['stops_raw'] ?? '[]'), $location)) {
                continue;
            }
            $visible[$routeId] = $routeId;
        }

        return array_values($visible);
    }

    protected function sltbDepotRowsFromRouteStops(array $routeIds): array
    {
        $routeRows = $this->routeStopRows($routeIds);
        if (empty($routeRows)) {
            return [];
        }

        static $depotRows = null;
        if ($depotRows === null) {
            $depotRows = [];
            try {
                $rows = $this->pdo->query(
                    "SELECT sltb_depot_id, name, code FROM sltb_depots ORDER BY sltb_depot_id"
                )->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {
                $rows = [];
            }

            foreach ($rows as $row) {
                $depotId = (int)($row['sltb_depot_id'] ?? 0);
                if ($depotId <= 0) {
                    continue;
                }

                $tokens = [];
                $name = $this->normalizeRouteText((string)($row['name'] ?? ''));
                if ($name !== '') {
                    $tokens[] = $name;
                    if (str_ends_with($name, ' depot')) {
                        $trimmed = trim(substr($name, 0, -6));
                        if ($trimmed !== '') {
                            $tokens[] = $trimmed;
                        }
                    }
                }

                $code = $this->normalizeRouteText((string)($row['code'] ?? ''));
                if ($code !== '') {
                    $tokens[] = $code;
                }

                $depotRows[$depotId] = [
                    'depot_id' => $depotId,
                    'depot_name' => (string)($row['name'] ?? ('Depot #' . $depotId)),
                    'depot_code' => (string)($row['code'] ?? ''),
                    'tokens' => array_values(array_unique(array_filter($tokens, static fn($token) => $token !== ''))),
                ];
            }
        }

        $matched = [];
        foreach ($routeRows as $routeRow) {
            $stopNames = $this->extractRouteStopNames((string)($routeRow['stops_raw'] ?? '[]'));
            foreach ($stopNames as $stopName) {
                $stopNorm = $this->normalizeRouteText((string)$stopName);
                if ($stopNorm === '') {
                    continue;
                }

                foreach ($depotRows as $depotId => $depotRow) {
                    foreach ($depotRow['tokens'] as $token) {
                        $token = (string)$token;
                        if ($token === '') {
                            continue;
                        }

                        $isMatch = $stopNorm === $token
                            || (strlen($token) >= 4 && str_contains($stopNorm, $token))
                            || (strlen($stopNorm) >= 5 && strlen($token) >= 5 && str_contains($token, $stopNorm));

                        if ($isMatch) {
                            $matched[$depotId] = [
                                'depot_id' => $depotRow['depot_id'],
                                'depot_name' => $depotRow['depot_name'],
                                'depot_code' => $depotRow['depot_code'],
                            ];
                            break 2;
                        }
                    }
                }
            }
        }

        uasort($matched, static function (array $a, array $b): int {
            return strcasecmp((string)($a['depot_name'] ?? ''), (string)($b['depot_name'] ?? ''));
        });

        return array_values($matched);
    }
}
