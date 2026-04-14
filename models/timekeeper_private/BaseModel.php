<?php
namespace App\models\timekeeper_private;

use PDO;

abstract class BaseModel {
    protected PDO $pdo;
    protected int $opId;
    public function __construct(int $privateOperatorId = 0) {
        $this->pdo  = $GLOBALS['db'];
        $u = $_SESSION['user'] ?? [];
        $sessionOpId = (int)($u['private_operator_id'] ?? 0);
        $this->opId = $sessionOpId > 0 ? $sessionOpId : (int)$privateOperatorId;
    }

    /** Page header label to match your SLTB markup: use depot_name key */
    public function info(): array {
        if ($this->opId <= 0) {
            return ['depot_name' => 'Private Network'];
        }

        // final DB uses private_bus_owners for operator profile
        $st = $this->pdo->prepare("SELECT name FROM private_bus_owners WHERE private_operator_id=?");
        $st->execute([$this->opId]);
        $name = $st->fetchColumn();
        return ['depot_name' => (string)($name ?: 'My Operator')];
    }
}
