<?php

namespace App\Support;

use PDO;

class DepotLocator
{
    private PDO $pdo;
    private ?array $depots = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function attachNearestDepot(array $buses): array
    {
        if (empty($buses)) {
            return $buses;
        }

        $depots = $this->loadDepots();

        return array_map(function (array $bus) use ($depots): array {
            $lat = $this->numericValue($bus['lat'] ?? null);
            $lng = $this->numericValue($bus['lng'] ?? null);

            if ($lat === null || $lng === null || empty($depots)) {
                $bus['nearestDepot'] = null;
                $bus['nearestDepotId'] = null;
                $bus['nearestDepotDistanceKm'] = null;
                $bus['nearestDepotDistanceText'] = null;
                return $bus;
            }

            $nearestDepot = null;
            $nearestDistanceKm = null;

            foreach ($depots as $depot) {
                $distanceKm = $this->haversineKm($lat, $lng, $depot['latitude'], $depot['longitude']);
                if ($nearestDistanceKm === null || $distanceKm < $nearestDistanceKm) {
                    $nearestDepot = $depot;
                    $nearestDistanceKm = $distanceKm;
                }
            }

            if ($nearestDepot === null || $nearestDistanceKm === null) {
                $bus['nearestDepot'] = null;
                $bus['nearestDepotId'] = null;
                $bus['nearestDepotDistanceKm'] = null;
                $bus['nearestDepotDistanceText'] = null;
                return $bus;
            }

            $bus['nearestDepot'] = $nearestDepot['name'];
            $bus['nearestDepotId'] = $nearestDepot['sltb_depot_id'];
            $bus['nearestDepotDistanceKm'] = round($nearestDistanceKm, 2);
            $bus['nearestDepotDistanceText'] = $this->formatDistance($nearestDistanceKm);

            return $bus;
        }, $buses);
    }

    private function loadDepots(): array
    {
        if ($this->depots !== null) {
            return $this->depots;
        }

        try {
            $stmt = $this->pdo->query(
                "SELECT sltb_depot_id, name, latitude, longitude
                 FROM sltb_depots
                 WHERE latitude IS NOT NULL
                   AND longitude IS NOT NULL"
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('[DepotLocator] loadDepots failed: ' . $e->getMessage());
            return $this->depots = [];
        }

        $this->depots = [];
        foreach ($rows as $row) {
            $lat = $this->numericValue($row['latitude'] ?? null);
            $lng = $this->numericValue($row['longitude'] ?? null);
            if ($lat === null || $lng === null) {
                continue;
            }
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                continue;
            }

            $this->depots[] = [
                'sltb_depot_id' => (int)($row['sltb_depot_id'] ?? 0),
                'name' => trim((string)($row['name'] ?? '')) ?: ('Depot #' . (int)($row['sltb_depot_id'] ?? 0)),
                'latitude' => $lat,
                'longitude' => $lng,
            ];
        }

        return $this->depots;
    }

    private function numericValue($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float)$value : null;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $angle = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        $arc = 2 * atan2(sqrt($angle), sqrt(1 - $angle));
        return $earthRadiusKm * $arc;
    }

    private function formatDistance(float $distanceKm): string
    {
        if ($distanceKm < 1) {
            return (string)max(1, (int)round($distanceKm * 1000)) . ' m';
        }

        return number_format($distanceKm, 1) . ' km';
    }
}