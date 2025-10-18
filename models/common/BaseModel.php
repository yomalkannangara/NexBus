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
}
