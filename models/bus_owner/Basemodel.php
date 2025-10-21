<?php
namespace App\models\bus_owner;

use PDO;

abstract class BaseModel
{
    protected PDO $pdo;
    protected ?int $operatorId = null; // private_operator_id for the logged-in owner

    public function __construct()
    {
        $this->pdo = $GLOBALS['db'];
        if (!empty($_SESSION['user']['private_operator_id'])) {
            $this->operatorId = (int) $_SESSION['user']['private_operator_id'];
        }
    }

    protected function hasOperator(): bool
    {
        return $this->operatorId !== null;
    }
}
