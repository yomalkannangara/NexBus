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
public function routes(): array {
  // get enough fields to render a nice card
  $sql = "SELECT route_id, route_no, name, is_active
            FROM routes
        ORDER BY route_no";
  return $this->pdo->query($sql)->fetchAll();
}

  public function list(int $userId): array {
    $st = $this->pdo->prepare("SELECT route_id FROM passenger_favourites WHERE passenger_id=?");
    $st->execute([$userId]);
    return $st->fetchAll();
  }
  public function toggle(int $userId, int $routeId, bool $on): bool {
    if ($on) {
      $st=$this->pdo->prepare("INSERT IGNORE INTO passenger_favourites(passenger_id,route_id) VALUES(?,?)");
      return $st->execute([$userId,$routeId]);
    } else {
      $st=$this->pdo->prepare("DELETE FROM passenger_favourites WHERE passenger_id=? AND route_id=?");
      return $st->execute([$userId,$routeId]);
    }
  }
}
