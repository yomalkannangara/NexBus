<?php
require_once __DIR__.'/BaseModel.php';
class OrgModel extends BaseModel {
    public function depots(): array {
        return $this->pdo->query("SELECT * FROM sltb_depots ORDER BY name")->fetchAll();
    }
    public function owners(): array {
        return $this->pdo->query("SELECT * FROM private_bus_owners ORDER BY name")->fetchAll();
    }
}
?>