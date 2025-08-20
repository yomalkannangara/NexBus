<?php
require_once __DIR__.'/BaseController.php';
class TimekeeperPrivateController extends BaseController { public function home(){ $this->view('timekeeper_private','home'); } }
?>