<?php

// derive current module/page from the path like /O/dashboard or /TP/timetables
$uri      = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$segments = array_values(array_filter(explode('/', $uri)));
$module   = $segments[0] ?? '';              // 'O' (Depot Officer), 'TP' (Private Timekeeper), 'TS' (SLTB TK)...
$page     = $segments[1] ?? 'dashboard';

$user     = $_SESSION['user'] ?? [];
$initial  = $user ? strtoupper(substr($user['name'] ?? $user['full_name'] ?? 'U', 0, 1)) : '?';
$email    = $user['email'] ?? '';
$name     = $user['full_name']  ?? ($user['full_name'] ?? 'Staff User');
  $profileHref = in_array($module, ['O','TP','TS'], true) ? ("/$module/profile") : "/home";

// labels per module
$roleLabel = match ($module) {
  'O'  => 'Depot Officer',
  'TP' => 'Private Timekeeper',
  'TS' => 'SLTB Timekeeper',
  default => ($user['role'] ?? 'Staff'),
};
$portalSub = match ($module) {
  'O'  => 'Depot Officer Portal',
  'TP' => 'Private Timekeeper Portal',
  'TS' => 'SLTB Timekeeper Portal',
  default => 'Staff Portal',
};
$sbTitle = match ($module) {
  'O'  => 'Depot Dashboard',
  'TP' => 'Timekeeper Dashboard',
  'TS' => 'Timekeeper Dashboard',
  default => 'Staff Dashboard',
};

// active helper
$active = function (string $m, string $p) use ($module, $page): string {
  return ($module === $m && $page === $p) ? ' active' : '';
};
?>
<!doctype html>
<html lang="en" data-theme="<?= isset($_SESSION['prefs']['theme']) ? $_SESSION['prefs']['theme'] : 'light' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($roleLabel) ?></title>
  <link rel="stylesheet" href="/assets/css/staff.css">
  <link rel="stylesheet" href="/assets/css/alert.css">
  <script defer src="/assets/js/app.js"></script>
  <script src="/assets/js/alert.js"></script>

</head>
<body>
<header class="topbar">
  <div class="brand">
    <div class="logo"><img src="/assets/images/logo.png" alt="NexBus Logo"></div>
    <div>
      <div class="app">Bus Management System</div>
      <div class="sub">National Transport Commission - Sri Lanka</div>
    </div>
  </div>
  <div class="right">
    <div class="user"><?= htmlspecialchars($roleLabel) ?></div>
    <div class="date"><?= date('l d F Y') ?></div>
  </div>
</header>

<div class="app-shell">
  <aside class="sidebar">
    <div class="sidebar-head">
      <div class="mini-logo">🚌</div>
      <div>
        <div class="sb-title"><?= htmlspecialchars($sbTitle) ?></div>
        <div class="sb-sub"><?= htmlspecialchars($portalSub) ?></div>
      </div>
    </div>

    <nav class="menu">
      <?php if ($module === 'O'): ?>
        <a href="/O/dashboard"   class="menu-item<?= $active('O','dashboard')   ?>"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-layout-grid" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg></i>Dashboard</a>
        <a href="/O/assignments" class="menu-item<?= $active('O','assignments') ?>"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-clipboard-list" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 14h6"/><path d="M9 10h6"/></svg></i>Assignments</a>
        <a href="/O/timetables"  class="menu-item<?= $active('O','timetables')  ?>"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-calendar" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg></i>Timetables</a>
        <a href="/O/messages"    class="menu-item<?= $active('O','messages')    ?>"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-message-square" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></i>Messages</a>
        <a href="/O/trip_logs"   class="menu-item<?= $active('O','trip_logs')   ?>"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-map-pin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></i>Trip Logs</a>
        <a href="/O/reports"     class="menu-item<?= $active('O','reports')     ?>"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-bar-chart-2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg></i>Reports</a>
        <a href="/O/attendance"  class="menu-item<?= $active('O','attendance')  ?>"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-clipboard-check" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/></svg></i>Attendance</a>
      <?php elseif ($module === 'TP'): ?>
        <a href="/TP/dashboard"   class="menu-item<?= $active('TP','dashboard')   ?>"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-layout-grid" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg></i>Dashboard</a>
        <a href="/TP/trip_entry"  class="menu-item<?= $active('TP','trip_entry')  ?>"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-edit-3" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 7H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-1"/><path d="m13.414 7.414 6-6a2 2 0 0 1 2.828 0l2.828 2.828a2 2 0 0 1 0 2.828l-6 6"/></svg></i>Trip Entry</a>
        <a href="/TP/turns"       class="menu-item<?= $active('TP','turns')       ?>"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-repeat" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 2 21 6 17 10"/><path d="M21 6H3a2 2 0 0 1 2-2h16a2 2 0 0 0-2 2"/><polyline points="7 22 3 18 7 14"/><path d="M3 18h18a2 2 0 0 0-2 2v2a2 2 0 0 1-2-2v-2a2 2 0 0 0 2-2"/></svg></i>Turns</a>
        <a href="/TP/history"     class="menu-item<?= $active('TP','history')     ?>"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-history" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.76 9.76 0 0 1-6.74-2.74L3 16"/><path d="M3 21v-5h5"/></svg></i>History</a>
      <?php elseif ($module === 'TS'): ?>
        <a href="/TS/dashboard"  class="menu-item<?= $active('TS','dashboard')  ?>"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-layout-grid" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg></i>Dashboard</a>
        <a href="/TS/trip_entry"    class="menu-item<?= $active('TS','trip_entry')    ?>"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-edit-3" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 7H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-1"/><path d="m13.414 7.414 6-6a2 2 0 0 1 2.828 0l2.828 2.828a2 2 0 0 1 0 2.828l-6 6"/></svg></i>Trip Entry</a>
        <a href="/TS/turns" class="menu-item<?= $active('TS','turns') ?>"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-repeat" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 2 21 6 17 10"/><path d="M21 6H3a2 2 0 0 1 2-2h16a2 2 0 0 0-2 2"/><polyline points="7 22 3 18 7 14"/><path d="M3 18h18a2 2 0 0 0-2 2v2a2 2 0 0 1-2-2v-2a2 2 0 0 0 2-2"/></svg></i>Turns</a>
        <a href="/TS/history"  class="menu-item<?= $active('TS','history')  ?>"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-history" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.76 9.76 0 0 1-6.74-2.74L3 16"/><path d="M3 21v-5h5"/></svg></i>History</a>
      <?php else: ?>
        <!-- Fallback: staff dashboard -->
        <a href="/home" class="menu-item"><i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-home" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></i>Home</a>
      <?php endif; ?>
    </nav>

   <div class="sidebar-profile">
      <a href="<?= htmlspecialchars($profileHref) ?>" class="profile-card" style="text-decoration:none;color:inherit;">
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
      &copy; <?= date('Y'); ?> National Transport Commission<br>
      <div class="version">Version 1.0.0</div>
    </div>
  </aside>

  <main id="content" class="active">
    <?php require $contentViewFile; ?>
  </main>
</div>
</body>
  <script src="/assets/js/timekeeper.js"></script>

</html>
