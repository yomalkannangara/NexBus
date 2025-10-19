<?php
// views/layouts/depot_manager.php

// Parse URI (e.g., /D/fleet -> module=D, page=fleet)
$uri      = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$segments = array_values(array_filter(explode('/', $uri)));
$module   = strtoupper($segments[0] ?? 'M');   // expect 'D'
$page     = $segments[1] ?? 'dashboard';

// Current user (optional)
$user    = $_SESSION['user'] ?? null;
$initial = $user ? strtoupper(substr($user['name'] ?? 'U', 0, 1)) : 'U';
$email   = $user['email'] ?? 'depot.manager@sltb.lk';
$name    = $user['name']  ?? 'Depot Manager';
?>
<!doctype html>
<html lang="en" data-theme="<?= htmlspecialchars($_SESSION['prefs']['theme'] ?? 'light') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Depot Manager</title>

  <!-- Depot layout CSS -->
  <link rel="stylesheet" href="/assets/css/depot.css">

  <!-- Optional alerts (if you already use these in project) -->
  <link rel="stylesheet" href="/assets/css/alert.css">
  <script defer src="/assets/js/alert.js"></script>
</head>
<body>
  <!-- Top bar -->
  <header class="topbar">
    <div class="brand">
      <div class="logo">
        <img src="/assets/images/logo.png" alt="NexBus Logo" onerror="this.style.display='none'">
      </div>
      <div>
        <div class="app">Depot Management System</div>
        <div class="sub">Sri Lanka Transport Board — SLTB</div>
      </div>
    </div>
    <div class="right">
      <div class="user">Depot Manager</div>
      <div class="date"><?= date('l d F Y'); ?></div>
    </div>
  </header>

  <div class="app-shell">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-head">
        <div class="mini-logo">🚌</div>
        <div>
          <div class="sb-title">Depot Manager</div>
          <div class="sb-sub">Depot Portal</div>
        </div>
      </div>

      <nav class="menu">
        <a href="/M/dashboard" class="menu-item<?= ($module==='M' && $page==='dashboard') ? ' active' : '' ?>">
          <i class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
          </i>
          Dashboard
        </a>

        <a href="/M/fleet" class="menu-item<?= ($module==='M' && $page==='fleet') ? ' active' : '' ?>">
          <i class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="7" rx="2"/><path d="M7 11V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v4"/><circle cx="7.5" cy="18.5" r="1.5"/><circle cx="16.5" cy="18.5" r="1.5"/></svg>
          </i>
          Fleet
        </a>

        <a href="/M/drivers" class="menu-item<?= ($module==='M' && $page==='drivers') ? ' active' : '' ?>">
          <i class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </i>
          Drivers
        </a>

        <a href="/M/feedback" class="menu-item<?= ($module==='M' && $page==='feedback') ? ' active' : '' ?>">
          <i class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          </i>
          Feedback
        </a>

        <a href="/M/health" class="menu-item<?= ($module==='M' && $page==='health') ? ' active' : '' ?>">
          <i class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7h-4l-3-3-3 3H6a2 2 0 0 0-2 2v7a4 4 0 0 0 4 4h8a4 4 0 0 0 4-4V9a2 2 0 0 0-2-2z"/></svg>
          </i>
          Health
        </a>

        <a href="/M/performance" class="menu-item<?= ($module==='M' && $page==='performance') ? ' active' : '' ?>">
          <i class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg>
          </i>
          Performance
        </a>

        <a href="/M/earnings" class="menu-item<?= ($module==='M' && $page==='earnings') ? ' active' : '' ?>">
          <i class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </i>
          Earnings
        </a>
      </nav>

      <!-- Sidebar profile -->
      <div class="sidebar-profile">
        <a href="/M/profile" class="profile-card">
          <div class="profile-avatar"><?= htmlspecialchars($initial) ?></div>
          <div class="profile-meta">
            <div class="profile-name"><?= htmlspecialchars($name) ?></div>
            <div class="profile-email"><?= htmlspecialchars($email) ?></div>
          </div>
        </a>

        <a href="/logout" class="profile-logout">
          <span class="logout-icon">⇦</span>
          <span class="logout-text">Logout</span>
        </a>
      </div>

      <div class="sidebar-foot">
        &copy; <?= date('Y'); ?> Sri Lanka Transport Board<br>
        <div class="version">Version 1.0.0</div>
      </div>
    </aside>

    <!-- Main content area -->
    <main id="content" class="active">
      <?php require $contentViewFile; ?>
    </main>
  </div>
</body>
</html>
