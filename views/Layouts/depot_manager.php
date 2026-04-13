<?php
// views/layouts/depot_manager.php

// Parse URI (e.g., /D/fleet -> module=D, page=fleet)
$uri      = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$segments = array_values(array_filter(explode('/', $uri)));
$module   = strtoupper($segments[0] ?? 'M');   // expect 'D'
$page     = $segments[1] ?? 'dashboard';

// Current user (optional)
$user    = $_SESSION['user'] ?? null;
$displayName = $user ? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) : '';
$displayName = $displayName !== '' ? $displayName : ($user['name'] ?? ($user['full_name'] ?? ''));
$initial = $user ? strtoupper(substr($displayName !== '' ? $displayName : 'U', 0, 1)) : 'U';
$email   = $user['email'] ?? 'depot.manager@sltb.lk';
$name    = $displayName !== '' ? $displayName : 'Depot Manager';
?>
<!doctype html>
<html lang="en" data-theme="<?= htmlspecialchars($_SESSION['prefs']['theme'] ?? 'light') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Depot Manager</title>

  <!-- Depot layout CSS -->
  <link rel="stylesheet" href="/assets/css/depot.css">
  <script src="/assets/js/fleet.js"></script>
  <?php if ($page === 'timetables'): ?>
    <link rel="stylesheet" href="/assets/css/staff.css">
  <?php endif; ?>
  <?php if ($page === 'drivers'): ?>
    <link rel="stylesheet" href="/assets/css/owner.css">
    <script defer src="/assets/js/bus_owner.js"></script>
  <?php endif; ?>

  <style>
    .topbar {
      position: fixed;
      inset: 0 0 auto 0;
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 20px;
      z-index: 1000;
      background: #7a0f2e !important;
      color: #fff;
      border-bottom: 4px solid var(--gold);
    }
    .topbar .brand { display: flex; gap: 12px; align-items: center }
    .topbar .logo {
      font-size: 28px; width: 42px; height: 42px; border-radius: 50%;
      background: #fff; box-shadow: inset 0 0 0 3px var(--gold);
    }
    .topbar .logo img { width: 42px; height: 42px }
    .topbar .app { font-weight: 700 }
    .topbar .sub { font-size: 12px; opacity: .9 }
    .topbar .right { display: flex; gap: 12px; align-items: center }
    .topbar .user { font-weight: 600 }
    .topbar .date { font-size: 12px; opacity: .9 }

    .app-shell{
      position: fixed;
      inset: 64px 0 0 0;
      z-index: 1000;
      display: grid;
      grid-template-columns: 250px 1fr;
      min-height: calc(100vh - 64px);
    }

    .sidebar{
      width: 250px;
      background: #7a0f2e;
      color: #fff;
      display:flex;
      flex-direction:column;
      gap:16px;
      padding:18px 14px;
      position:relative;
    }
    .sidebar-head{
      display:flex;align-items:center;gap:12px;
      padding:8px 8px 16px 8px;
      border-bottom:1px solid #ffffff1f;
      margin-bottom:10px;
    }
    .mini-logo{width:36px;height:36px;border-radius:1rem;background:#fff1a0;color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;border:1px solid #ffffff30}
    .sb-title{font-weight:700;letter-spacing:.3px}
    .sb-sub{font-size:12px;opacity:.85}

    .menu { display:flex; flex-direction:column; gap:6px; margin-top:6px }
    .menu .menu-item {
      display: flex; align-items: center; gap: 10px;
      padding: 8px 14px; color: #fff; text-decoration: none; font-size: 14px;
      border-radius: 6px; transition: background .2s ease;
    }
    .menu .menu-item .icon { flex: 0 0 18px; display: grid; place-items: center }
    .menu .menu-item:hover { background: rgba(255, 255, 255, .15) }
    .menu .menu-item.active {
      color: var(--gold);
      background: rgba(255, 255, 255, .15);
      border-right: 3px solid var(--gold)
    }

    .sidebar-profile{
      margin-top:auto;
      padding-top:16px;
      border-top:1px solid #ffffff1f;
    }
    .sidebar-profile .profile-card{
      display:flex;
      gap:12px;
      align-items:center;
      text-decoration:none;
      color:#fff;
      padding:10px;
      border-radius:12px;
      background:#ffffff10;
      border:1px solid #ffffff20;
    }
    .sidebar-profile .profile-card:hover{ background:#ffffff18; }
    .sidebar-profile .profile-avatar{
      width:36px;
      height:36px;
      border-radius:50%;
      display:grid;
      place-items:center;
      background:#fff2;
      color:#fff;
      font-weight:700;
      border:1px solid #ffffff30;
    }
    .sidebar-profile .profile-meta{ display:flex; flex-direction:column; }
    .sidebar-profile .profile-name{ font-weight:700; line-height:1.2; }
    .sidebar-profile .profile-email{
      color:#8fc5ff;
      font-size:12px;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .sidebar-profile .profile-logout {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin: 10px auto 0;
      padding: 10px 18px;
      font-size: 14px;
      font-weight: 700;
      color: #fff;
      background: linear-gradient(135deg, #aa1b23, #b61c29);
      border-radius: 10px;
      text-decoration: none;
      transition: all .22s ease;
      box-shadow: 0 3px 8px rgba(0, 0, 0, .18);
      border: none;
      cursor: pointer;
      width: calc(100% - 36px);
      max-width: 260px;
    }
    .sidebar-profile .profile-logout:hover {
      background: linear-gradient(135deg, #b61c29, #c72a35);
      transform: translateY(-1px);
      box-shadow: 0 6px 14px rgba(0, 0, 0, .22);
    }
    .sidebar-profile .logout-icon{ font-size:16px; }
    .sidebar-foot{
      color:#ffffffcc;
      font-size:12px;
      margin-top:14px;
      padding:10px 6px 0 6px;
      border-top:1px dashed #ffffff2a;
    }
    .sidebar-foot .version{opacity:.9;margin-top:4px}

    @media (max-width: 768px) {
      .topbar{padding:12px 12px}
      .app-shell{
        position:relative;
        inset:auto;
        grid-template-columns:1fr;
        min-height:auto;
      }
      .sidebar{
        position:fixed;
        left:-260px;
        top:64px;
        width:260px;
        height:calc(100vh - 64px);
        z-index:999;
        transition:left .3s ease;
        overflow-y:auto;
        box-shadow:4px 0 12px rgba(0,0,0,.1);
      }
      .sidebar.open{ left:0; }
      .brand{ flex:1; min-width:0; }
      .topbar .logo{ width:36px; height:36px; font-size:20px; }
      .topbar .logo img{ width:36px; height:36px; }
      .topbar .app{ font-size:14px; }
      .topbar .sub{ display:none; }
      .topbar .right{ gap:8px; flex-wrap:nowrap; }
      .topbar .user{ display:none; }
      .topbar .date{ display:none; }
    }
  </style>

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

        <a href="/M/timetables" class="menu-item<?= ($module==='M' && $page==='timetables') ? ' active' : '' ?>">
          <i class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-calendar" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
          </i>
          Timetables
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
