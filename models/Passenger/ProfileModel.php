<?php
namespace App\Models\Passenger;

use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];   
    }
}
class ProfileModel extends BaseModel {} // simple for now
