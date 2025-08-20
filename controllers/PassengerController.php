<?php
require_once __DIR__.'/BaseController.php';
class PassengerController extends BaseController { public function home(){ $this->view('passenger','home'); } }
?>