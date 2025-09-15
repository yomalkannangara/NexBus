<?php
namespace App\Models\Passenger;

// models/passenger/TicketModel.php
use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}

class TicketModel extends BaseModel {

  /* ------------- ROUTES ------------- */
  public function routes(): array {
    // Uses columns that exist in your screenshot
    return $this->pdo->query("SELECT route_id, route_no, name FROM routes WHERE is_active=1 ORDER BY route_no")->fetchAll();
  }

  /* ------------- STOPS from routes.stops_json ------------- */
  public function stops(int $routeId): array {
    $st = $this->pdo->prepare("SELECT stops_json FROM routes WHERE route_id=? LIMIT 1");
    $st->execute([$routeId]);
    $row = $st->fetch();
    if (!$row || empty($row['stops_json'])) return [];

    $arr = json_decode($row['stops_json'], true) ?: [];
    // normalize -> we return index (1-based) + name for UI selects
    $out = [];
    foreach ($arr as $i => $s) {
      $name = is_array($s) ? ($s['stop'] ?? ('Stop '.($i+1))) : (string)$s;
      $out[] = ['idx' => $i + 1, 'name' => $name];
    }
    return $out;
  }

  /* ------------- FARE calculation ------------- */
  public function fares(int $routeId, int $startIdx, int $endIdx): array {
    // 1) Re-load stops to get names and validate indices
    $stops = $this->stops($routeId);
    if (empty($stops)) {
      return [
        'normal'=>0,'semi_luxury'=>0,'luxury'=>0,'super_luxury'=>0,
        'start_name'=>'Start','end_name'=>'End','stages'=>1,'distance_km'=>0
      ];
    }

    // Clamp indices to available range (1..N)
    $n = count($stops);
    $startIdx = max(1, min($n, $startIdx));
    $endIdx   = max(1, min($n, $endIdx));

    $startName = $stops[$startIdx-1]['name'];
    $endName   = $stops[$endIdx-1]['name'];

    // 2) Stages = absolute difference in positions (min 1)
    $stages = max(0, abs($endIdx - $startIdx));

    // 3) Pick fare row for route + stage_number (latest effective)
    $sql = "SELECT
              normal_service,
              semi_luxury,
              luxury,
              super_luxury,
              is_normal_service_active,
              is_semi_luxury_active,
              is_luxury_active,
              is_super_luxury_active
            FROM fares
           WHERE route_id=? AND stage_number=?
        ORDER BY COALESCE(effective_to, '9999-12-31') DESC, effective_from DESC
           LIMIT 1";
    $st = $this->pdo->prepare($sql);
    $st->execute([$routeId, $stages]);
    $f = $st->fetch();

    if ($f) {
      $normal = (int)$f['is_normal_service_active']   ? (float)$f['normal_service'] : 0.0;
      $semi   = (int)$f['is_semi_luxury_active']      ? (float)$f['semi_luxury']    : 0.0;
      $lux    = (int)$f['is_luxury_active']           ? (float)$f['luxury']         : 0.0;
      $super  = (int)$f['is_super_luxury_active']     ? (float)$f['super_luxury']   : 0.0;
    } else {
      $normal = $semi = $lux = $super = 0.0; // no row for that stage
    }

    // 4) Distance (simple approx – adjust if you have real km per stage)
    $distanceKm = round($stages * 2.0, 1);

    return [
      'normal'       => $normal,
      'semi_luxury'  => $semi,
      'luxury'       => $lux,
      'super_luxury' => $super,
      'start_name'   => $startName,
      'end_name'     => $endName,
      'stages'       => $stages,
      'distance_km'  => $distanceKm
    ];
  }
}
