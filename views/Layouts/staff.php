<?php
// Parse current path segments: /O/dashboard, etc.
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = array_values(array_filter(explode('/', $uri)));
$module = $segments[0] ?? 'O';        // 'O' for Depot Officer
$page   = $segments[1] ?? 'dashboard';

$user = $_SESSION['user'] ?? [];
$initial = $user ? strtoupper(substr($user['name'] ?? 'G', 0, 1)) : '?';
$email   = $user['email'] ?? 'officer@sltb.lk';
$name    = $user['name']  ?? 'Depot Officer';
?>
<!doctype html>
<html lang="en" data-theme="<?= isset($_SESSION['prefs']['theme']) ? $_SESSION['prefs']['theme'] : 'light' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Depot Officer</title>

  <!-- Load staff stylesheet (which imports admin.css inside) -->
  <link rel="stylesheet" href="/assets/css/staff.css?v=1">

  <script defer src="/assets/js/app.js"></script>
  <link rel="stylesheet" href="/assets/css/alert.css">
  <script src="/assets/js/alert.js"></script>
  <!-- LAYOUT FILE: <?= __FILE__ ?> -->
</head>
<body class="role-staff">
  <header class="topbar">
    <div class="brand">
      <div class="logo"><img src="/assets/images/logo.png" alt="NexBus Logo"></div>
      <div>
        <div class="app">Bus Management System</div>
        <div class="sub">National Transport Commission - Sri Lanka</div>
      </div>
    </div>
    <div class="right">
      <div class="user">Depot Officer</div>
      <div class="date"><?= date('l d F Y'); ?></div>
    </div>
  </header>

  <div class="app-shell">
    <aside class="sidebar">
      <div class="sidebar-head">
        <div class="mini-logo">🛡️</div>
        <div>
          <div class="sb-title">Depot Dashboard</div>
          <div class="sb-sub">Depot Officer Portal</div>
        </div>
      </div>

      <!-- Depot Officer menu (paths /O/...) -->
      <nav class="menu">
        <a href="/O/dashboard"   class="menu-item<?= ($module==='O' && $page==='dashboard') ? ' active' : '' ?>">
          <i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-layout-grid" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg></i>
          Dashboard
        </a>

        <a href="/O/assignments" class="menu-item<?= ($module==='O' && $page==='assignments') ? ' active' : '' ?>">
          <i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-clipboard-check" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="8" y="2" width="8" height="4" rx="1"/><rect x="4" y="6" width="16" height="14" rx="2"/><path d="m9 12 2 2 4-4"/></svg></i>
          Assignments
        </a>

        <a href="/O/timetables"  class="menu-item<?= ($module==='O' && $page==='timetables') ? ' active' : '' ?>">
          <i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-calendar" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg></i>
          Timetables
        </a>

        <a href="/O/messages"    class="menu-item<?= ($module==='O' && $page==='messages') ? ' active' : '' ?>">
          <i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-message-square" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></i>
          Messages
        </a>

        <a href="/O/complaints"  class="menu-item<?= ($module==='O' && $page==='complaints') ? ' active' : '' ?>">
          <i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-alert-circle" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12" y2="16"/></svg></i>
          Complaints
        </a>

        <a href="/O/trip_logs"   class="menu-item<?= ($module==='O' && $page==='trip_logs') ? ' active' : '' ?>">
          <i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-route" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="19" r="3"/><circle cx="18" cy="5" r="3"/><path d="M6 16V6a2 2 0 0 1 2-2h7"/></svg></i>
          Trip Logs
        </a>

        <a href="/O/reports"     class="menu-item<?= ($module==='O' && $page==='reports') ? ' active' : '' ?>">
          <i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-bar-chart-2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg></i>
          Reports
        </a>

        <a href="/O/attendance"  class="menu-item<?= ($module==='O' && $page==='attendance') ? ' active' : '' ?>">
          <i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-clipboard-list" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="8" y="2" width="8" height="4" rx="1"/><rect x="4" y="6" width="16" height="14" rx="2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg></i>
          Attendance
        </a>
      </nav>

      <div class="sidebar-profile">
        <a href="#" class="profile-card">
          <div class="profile-avatar"><?= $initial ?></div>
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
        &copy; <?= date('Y'); ?> National Transport Commission<br>
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
