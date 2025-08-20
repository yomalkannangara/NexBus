<?php
require_once __DIR__.'/BaseController.php';
class DepotManagerController extends BaseController { public function home(){ $this->view('depot_manager','home'); } }
?>