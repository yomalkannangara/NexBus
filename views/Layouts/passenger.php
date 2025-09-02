<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$segments = array_values(array_filter(explode('/', $uri)));

$module = $segments[0] ?? 'passenger';
$page   = $segments[1] ?? 'home';


?><!doctype html><html lang="en">
  
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title ?? 'NexBus Passenger') ?></title>
<link rel="stylesheet" href="../assets/css/passenger.css">
<script defer src="../assets/js/passenger.js"></script>
</head><body>
<div class="m-page">
<header class="m-topbar">
  <div class="brand">
    <img src="../assets/images/logo.png" alt="NexBus logo">
    <div class="title">NexBus</div>
  </div>
  <div class="top-actions">
    <a class="icon-btn" href="/notifications" title="Notifications">🔔</a>
    <a class="icon-btn" href="/profile" title="Profile">👤</a>
  </div>
</header>



  <main class="m-content">
    <?php require $contentViewFile; ?>
  </main>

  <nav class="tabbar">
    <a class="tab<?=($page==='home')?' active':''?>" href="/home">🏠<span>Home</span></a>
    <a class="tab<?=($page==='favourites')?' active':''?>" href="/favourites">❤<span>Fav</span></a>
    <a class="tab<?=($page==='ticket')?' active':''?>" href="/ticket">🎫<span>Ticket</span></a>
    <a class="tab<?=($page==='feedback')?' active':''?>" href="/feedback">💬<span>Feedback</span></a>
    <a class="tab<?=($page==='profile')?' active':''?>" href="/profile">👤<span>Profile</span></a>
  </nav>
</div>
</body></html>
