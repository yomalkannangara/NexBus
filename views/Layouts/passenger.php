<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = array_values(array_filter(explode('/', $uri)));
$page   = $segments[0] ?? 'home';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'NexBus Passenger') ?></title>
  <link rel="stylesheet" href="/assets/css/passenger.css">
  <script defer src="/assets/js/passenger.js"></script>
</head>
<body>
<div class="m-page">

  <!-- Top bar with SVG icons -->
  <header class="m-topbar">
    <div class="brand">
  <img src="/assets/images/logo.png" alt="NexBus logo">
      <div class="title">NexBus</div>
    </div>
    <div class="top-actions">
      <!-- Logout (icon-only) -->
      <a class="icon-btn" href="/logout" title="Log out" aria-label="Log out">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <!-- power icon -->
          <path d="M11 3h2v10h-2V3z"/>
          <path d="M7.05 7.05a7 7 0 1 0 9.9 0l-1.41 1.41a5 5 0 1 1-7.08 0L7.05 7.05z"/>
        </svg>
      </a>

      <!-- Notifications (bell) -->
      <a class="icon-btn" href="/notifications" title="Notifications" aria-label="Notifications">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2zM20 18l-2-2v-5a6 6 0 10-12 0v5l-2 2v2h16v-2z"/>
        </svg>
      </a>
      <!-- Profile (user) -->
      <a class="icon-btn" href="/profile" title="Profile" aria-label="Profile">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <circle cx="12" cy="8" r="4"/>
          <path d="M4 20c0-4.418 3.582-8 8-8s8 3.582 8 8H4z"/>
        </svg>
      </a>
    </div>
  </header>

  <main class="m-content">
    <?php require $contentViewFile; ?>
  </main>

<!-- Bottom tabbar with SVG icons -->
<nav class="tabbar" role="navigation" aria-label="Main">
  <!-- Home -->
  <a class="tab<?=($page==='home')?' active':''?>" href="/home" aria-label="Home">
    <span class="ic" aria-hidden="true">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
        <!-- roof -->
        <path d="M3 10l9-7 9 7z"/>
        <!-- body -->
        <path d="M5 10v10h6v-6h2v6h6V10z"/>
      </svg>
    </span>
    <span>Home</span>
  </a>

  <!-- Timetable (calendar/clock icon) -->
  <a class="tab<?=($page==='timetable')?' active':''?>" href="/timetable" aria-label="Timetable">
    <span class="ic" aria-hidden="true">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
        <!-- calendar-like shape -->
        <path d="M19 4h-1V2h-2v2H8V2H6v2H5a2 2 0 00-2 2v14
                 a2 2 0 002 2h14a2 2 0 002-2V6
                 a2 2 0 00-2-2zM5 20V9h14v11H5z"/>
        <!-- clock inside calendar -->
        <path d="M12 12a4 4 0 100 8 4 4 0 000-8zm.5 2v2.25l1.5.75-.75 1.5L11 16v-2h1.5z"/>
      </svg>
    </span>
    <span>Timetable</span>
  </a>

  <!-- Favourites -->
  <a class="tab<?=($page==='favourites')?' active':''?>" href="/favourites" aria-label="Favourites">
    <span class="ic" aria-hidden="true">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 21s-6.2-4.3-8.6-7.6C1.2 10.8 2.7 6 7 6c2 0 3.4 1 5 2.9C13.6 7 15 6 17 6c4.3 0 5.8 4.8 3.6 7.4C18.2 16.7 12 21 12 21z"/>
      </svg>
    </span>
    <span>Favourites</span>
  </a>

  <!-- Ticket -->
  <a class="tab<?=($page==='ticket')?' active':''?>" href="/ticket" aria-label="Ticket Price">
    <span class="ic" aria-hidden="true">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
        <path d="M4 7h16v3a2 2 0 0 1 0 4v3H4v-3a2 2 0 0 1 0-4V7zM9 9h6v2H9V9zm0 4h6v2H9v-2z"/>
      </svg>
    </span>
    <span>Ticket Price</span>
  </a>

  <!-- Feedback -->
  <a class="tab<?=($page==='feedback')?' active':''?>" href="/feedback" aria-label="Feedback">
    <span class="ic" aria-hidden="true">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
        <path d="M3 5h18v12H8l-4 4v-4H3V5z"/>
      </svg>
    </span>
    <span>Feedback</span>
  </a>
</nav>


</div>
</body>
</html>
