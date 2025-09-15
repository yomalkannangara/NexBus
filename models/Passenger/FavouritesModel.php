<?php
namespace App\Models\Passenger;

use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}
class FavouritesModel extends BaseModel {
      public function latestBusForRoute(int $routeId): ?array {
          $sql = "SELECT t.bus_reg_no, t.departure_time, t.arrival_time, t.operator_type
                    FROM timetables t
                  WHERE t.route_id = ?
                    AND t.departure_time <= CURTIME()
                    AND (t.arrival_time IS NULL OR t.arrival_time >= CURTIME())
                    AND (t.effective_from IS NULL OR t.effective_from <= CURDATE())
                    AND (t.effective_to IS NULL OR t.effective_to >= CURDATE())
                ORDER BY t.departure_time DESC
                  LIMIT 1";
          $st = $this->pdo->prepare($sql);
          $st->execute([$routeId]);
          $row = $st->fetch();

          if ($row) {
              // Calculate minutes since departure
              $dep = new \DateTime($row['departure_time']);
              $now = new \DateTime();
              $diffMins = ($now->getTimestamp() - $dep->getTimestamp()) / 60;
              $row['minutes_from_departure'] = max(0, (int)$diffMins);
          }
          return $row ?: null;
      }

      public function onlyFavs(int $userId): array {
          $sql = "SELECT r.route_id, r.route_no, r.name, r.is_active,
                        f.favourite_id, f.notify_enabled
                    FROM passenger_favourites f
                    JOIN routes r ON r.route_id = f.route_id
                  WHERE f.passenger_id = ?
                ORDER BY r.route_no";
          $st = $this->pdo->prepare($sql);
          $st->execute([$userId]);
          $rows = $st->fetchAll();

          foreach ($rows as &$r) {
              $latest = $this->latestBusForRoute((int)$r['route_id']);
              $r['latest_bus'] = $latest['bus_reg_no'] ?? null;
              $r['operator_type'] = $latest['operator_type'] ?? null;
              $r['minutes_from_departure'] = $latest['minutes_from_departure'] ?? null;
          }
          return $rows;
      }

public function allRoutes(): array {
    $sql = "SELECT route_id, route_no, name, is_active FROM routes ORDER BY route_no+0, route_no";
    return $this->pdo->query($sql)->fetchAll();
}

public function add(int $userId, int $routeId): bool {
    $st = $this->pdo->prepare(
        "INSERT IGNORE INTO passenger_favourites (passenger_id, route_id, notify_enabled) VALUES (?, ?, 1)"
    );
    return $st->execute([$userId, $routeId]);
}

public function delete(int $userId, int $routeId): bool {
    $st = $this->pdo->prepare("DELETE FROM passenger_favourites WHERE passenger_id=? AND route_id=?");
    return $st->execute([$userId, $routeId]);
}

public function setNotify(int $userId, int $routeId, bool $enabled): bool {
    $st = $this->pdo->prepare("UPDATE passenger_favourites SET notify_enabled=? WHERE passenger_id=? AND route_id=?");
    return $st->execute([$enabled ? 1 : 0, $userId, $routeId]);
}
}
