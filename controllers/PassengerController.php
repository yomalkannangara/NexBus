<?php
namespace App\controllers;

use App\controllers\BaseController;
use App\models\passenger\HomeModel;  
use App\models\passenger\FavouritesModel;  // folders: models/ntc_admin/
use App\models\passenger\TicketModel;
use App\models\passenger\FeedbackModel;
use App\models\passenger\ProfileModel;
use App\models\passenger\NotificationsModel;


class PassengerController extends BaseController {
  public function __construct(){ parent::__construct(); $this->setLayout('passenger'); }

  public function home(){
    $m = new HomeModel();
    $rid = (isset($_GET['route_id']) && $_GET['route_id'] !== '') ? (int)$_GET['route_id'] : null;
    $otype = (isset($_GET['operator_type']) && $_GET['operator_type'] !== '') ? (string)$_GET['operator_type']: null;

    $this->view('passenger','home',[
      'routes'=>$m->routes(),
      'route_id'=>$rid,
      'operator_type'=>$otype,
      'nextBuses'=>$m->nextBuses($rid,$otype, 12)
    ]);
  }

  public function favourites(){
    $m = new FavouritesModel();
    $uid = 1; // TODO: session user id
    if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='toggle') {
      $m->toggle($uid, (int)$_POST['route_id'], (($_POST['on']??'1')==='1'));
      $this->redirect('?module=passenger&page=favourites');
    }
    $this->view('passenger','favourites',[
      'routes'=>$m->routes(),
      'favs'=>$m->list($uid)
    ]);
  }

public function ticket() {
  $m = new TicketModel();
  $routes = $m->routes();

  $selectedRoute = isset($_POST['route_id']) ? (int)$_POST['route_id'] : null;
  $stops = $selectedRoute ? $m->stops($selectedRoute) : [];
  $fare = null;

  if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='calc') {
    $routeId  = (int)$_POST['route_id'];
    $startIdx = (int)$_POST['start_idx']; // 1-based index from select
    $endIdx   = (int)$_POST['end_idx'];
    $fare = $m->fares($routeId, $startIdx, $endIdx);
  }

  $this->view('passenger','ticket', compact('routes','selectedRoute','stops','fare'));
}


  public function feedback(){
    $m = new FeedbackModel();
    $msg=null;
    if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='create') {
      try { $m->addFeedback($_POST); $msg='Thanks! Submitted.'; } catch (\Throwable $e) { $msg='Submit failed.'; }
    }
    $this->view('passenger','feedback',[ 'routes'=>$m->routes(), 'msg'=>$msg ]);
  }

  public function profile(){ $this->view('passenger','profile',[]); }
  public function notifications(){ $this->view('passenger','notifications',[ 'items'=>(new NotificationsModel())->list() ]); }
}
