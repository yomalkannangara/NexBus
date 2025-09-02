<?php
namespace App\Models\Passenger;

use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}
class NotificationsModel extends BaseModel {
  public function list(): array {
    return [
      ['title'=>'Bus 138 arriving in 2 minutes', 'meta'=>'Near Bambalapitiya', 'tag'=>'alert', 'age'=>'2 min ago'],
      ['title'=>'Route 100 delayed', 'meta'=>'~15 min traffic', 'tag'=>'delay', 'age'=>'1 hour ago'],
      ['title'=>'New service', 'meta'=>'Route 250 Negombo → Colombo', 'tag'=>'service', 'age'=>'2 hours ago'],
    ];
  }
}
