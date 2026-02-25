<?php
namespace App\models\ntc_admin;

use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    public function __construct() {
        $this->pdo = $GLOBALS['db'];
    }
}
