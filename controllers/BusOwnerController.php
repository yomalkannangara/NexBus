<?php
require_once __DIR__.'/BaseController.php';
class BusOwnerController extends BaseController { public function home(){ $this->view('bus_owner','home'); } }
?>