<?php
namespace App\Models\Passenger;

// models/Passenger/HomeModel.php
use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}
class HomeModel extends BaseModel {
  public function routes(): array {
    // Only use columns we are sure exist: route_id, route_no
    return $this->pdo->query("SELECT route_id, route_no FROM routes ORDER BY route_no")->fetchAll();
  }

public function nextBuses(?int $routeId = null, ?string $otype = null, int $limit = 12): array
{
    $sql = "SELECT r.route_no,
                   r.name,
                   tt.bus_reg_no,
                   tt.operator_type,
                   tt.departure_time,
                   tt.arrival_time,
                   TIMESTAMPDIFF(MINUTE, tt.departure_time, CURTIME()) AS minutes_from_departure
            FROM timetables tt
            JOIN routes r ON r.route_id = tt.route_id
            WHERE 1=1
              AND tt.arrival_time > CURTIME()
              AND tt.departure_time < CURTIME()           
              AND (tt.effective_from IS NULL OR tt.effective_from <= CURDATE())
              AND (tt.effective_to   IS NULL OR tt.effective_to   >= CURDATE())";

    if ($routeId !== null) {
        $sql .= " AND r.route_id = " . (int)$routeId;          // numeric -> cast
    }
    if ($otype !== null) {
        $sql .= " AND tt.operator_type = " . $this->pdo->quote($otype); // string -> quote
    }

    $limit = max(1, (int)$limit);
    $sql  .= " ORDER BY ABS(TIME_TO_SEC(TIMEDIFF(tt.departure_time, CURTIME()))) ASC
               LIMIT $limit";

    // No parameters => query() is simplest
    $st = $this->pdo->query($sql);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

}