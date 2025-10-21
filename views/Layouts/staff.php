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
        <a href="/O/dashboard"   class="menu-item<?= $active('O','dashboard')   ?>"><i class="icon">🏠</i>Dashboard</a>
        <a href="/O/assignments" class="menu-item<?= $active('O','assignments') ?>"><i class="icon">🗂️</i>Assignments</a>
        <a href="/O/timetables"  class="menu-item<?= $active('O','timetables')  ?>"><i class="icon">📅</i>Timetables</a>
        <a href="/O/messages"    class="menu-item<?= $active('O','messages')    ?>"><i class="icon">💬</i>Messages</a>
        <a href="/O/complaints"  class="menu-item<?= $active('O','complaints')  ?>"><i class="icon">🛠️</i>Complaints</a>
        <a href="/O/trip_logs"   class="menu-item<?= $active('O','trip_logs')   ?>"><i class="icon">🧭</i>Trip Logs</a>
        <a href="/O/reports"     class="menu-item<?= $active('O','reports')     ?>"><i class="icon">📈</i>Reports</a>
        <a href="/O/attendance"  class="menu-item<?= $active('O','attendance')  ?>"><i class="icon">🗒️</i>Attendance</a>
      <?php elseif ($module === 'TP'): ?>
        <a href="/TP/dashboard"   class="menu-item<?= $active('TP','dashboard')   ?>"><i class="icon">🏠</i>Dashboard</a>
        <a href="/TP/trip_entry"  class="menu-item<?= $active('TP','trip_entry')  ?>"><i class="icon">🛫</i>Trip Entry</a>
        <a href="/TP/turns"       class="menu-item<?= $active('TP','turns')       ?>"><i class="icon">🧭</i>Turns</a>
        <a href="/TP/history"     class="menu-item<?= $active('TP','history')     ?>"><i class="icon">📜</i>History</a>
      <?php elseif ($module === 'TS'): ?>
        <a href="/TS/dashboard"  class="menu-item<?= $active('TS','dashboard')  ?>"><i class="icon">🏠</i>Dashboard</a>
        <a href="/TS/trip_entry"    class="menu-item<?= $active('TS','trip_entry')    ?>"><i class="icon">📈</i>Trip Entry</a>
        <a href="/TS/turns" class="menu-item<?= $active('TS','turns') ?>"><i class="icon">📅</i>Turns</a>
        <a href="/TS/history"  class="menu-item<?= $active('TS','history')  ?>"><i class="icon">🧭</i>History</a>
      <?php else: ?>
        <!-- Fallback: staff dashboard -->
        <a href="/home" class="menu-item"><i class="icon">🏠</i>Home</a>
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
