<?php
namespace App\Models\Passenger;

use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}
class FeedbackModel extends BaseModel {
  public function routes(): array {
    return $this->pdo->query("SELECT route_id, route_no FROM routes ORDER BY route_no")->fetchAll();
  }
  public function addFeedback(array $p): void {
    $st = $this->pdo->prepare("INSERT INTO feedback (message_type, route_id, bus_id, bus_type, description, created_at)
                               VALUES (?,?,?,?,?, NOW())");
    $st->execute([
      $p['type'] ?? 'feedback',
      !empty($p['route_id'])?(int)$p['route_id']:null,
      $p['bus_id'] ?? null,
      $p['bus_type'] ?? 'SLTB',
      $p['description'] ?? ''
    ]);
  }
}
