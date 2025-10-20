<?php
// views/layouts/owner.php
// Expects $contentViewFile from BaseController::render()

// URL pieces
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = array_values(array_filter(explode('/', $uri)));
$module = $segments[0] ?? 'B';          // /B/dashboard → 'B'
$page   = $segments[1] ?? 'dashboard';  // 'dashboard'

// Session user
$user    = $_SESSION['user'] ?? null;
$initial = $user ? strtoupper(substr($user['name'] ?? 'G', 0, 1)) : '?';
$email   = $user['email'] ?? 'owner@nexbus.lk';
$name    = $user['name']  ?? 'Bus Owner';

// Base URL for owner routes
if (!defined('BASE_URL')) define('BASE_URL', '/B');
?>
<!doctype html>
<html lang="en" data-theme="<?= $_SESSION['prefs']['theme'] ?? 'light' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>NexBus — Owner Portal</title>

  <!-- Your owner CSS -->
  <link rel="stylesheet" href="/assets/css/styles.css">
  <!-- Combined owner JS -->
  <script defer src="/assets/js/bus_owner.js"></script>
  <script defer src="/assets/js/earnings.js"></script>

  <link rel="icon" href="/assets/images/logo.png" type="image/png">
</head>
<body>

<div class="container"><!-- matches .container in CSS -->
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="logo-section">
      <div class="logo-circle">
        <img src="/assets/images/logo.png" alt="NexBus">
      </div>
      <div class="logo-text">
        <h1>Bus Owner Portal</h1>
        <p>NexBus Sri Lanka</p>
      </div>
    </div>

    <div class="search-box">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
        <path d="M21 21l-4.3-4.3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
      <input type="text" placeholder="Search…">
    </div>

    <nav class="nav-menu"><!-- matches .nav-menu/.nav-item -->
      <a href="/B/dashboard" class="nav-item<?= ($module==='B' && $page==='dashboard') ? ' active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
        Dashboard
      </a>
      <a href="/B/fleet" class="nav-item<?= ($module==='B' && $page==='fleet') ? ' active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="12" rx="2"/><path d="M3 11h18M7 17h.01M17 17h.01"/></svg>
        Fleet
      </a>
      <a href="/B/drivers" class="nav-item<?= ($module==='B' && $page==='drivers') ? ' active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        Drivers
      </a>
      <a href="/B/earnings" class="nav-item<?= ($module==='B' && $page==='earnings') ? ' active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Earnings
      </a>
      <a href="/B/feedback" class="nav-item<?= ($module==='B' && $page==='feedback') ? ' active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 0 1-4 4H7l-4 3V5a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>
        Feedback
      </a>
      <a href="/B/performance" class="nav-item<?= ($module==='B' && $page==='performance') ? ' active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg>
        Performance
      </a>
      <a href="/B/profile" class="nav-item<?= ($module==='B' && $page==='profile') ? ' active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/></svg>
        Profile
      </a>
    </nav>

    <button class="new-project-btn" type="button">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      New Entry
    </button>

    <div class="user-profile">
      <div class="user-avatar"><?= $initial ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($name) ?></div>
        <div class="user-role"><?= htmlspecialchars($email) ?></div>
      </div>
    </div>
  </aside>

  <!-- Main content -->
  <main class="main-content"><!-- matches .main-content in CSS -->
    <?php require $contentViewFile; ?>
  </main>
</div>

</body>
</html>
