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

    /** Check if a column exists in a table (cached per request). */
    protected function columnExists(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (!isset($cache[$key])) {
            try {
                $st = $this->pdo->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE ?');
                $st->execute([$column]);
                $cache[$key] = (bool)$st->fetch();
            } catch (\Throwable $e) {
                $cache[$key] = false;
            }
        }
        return $cache[$key];
    }
}
