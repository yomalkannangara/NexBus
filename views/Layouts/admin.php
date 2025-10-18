<?php
// public/index.php

// Grab the path only (without ?query=string)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove leading/trailing slashes
$segments = array_values(array_filter(explode('/', $uri)));

// Example: /ntc_admin/dashboard → ['ntc_admin','dashboard']
$module = $segments[0] ?? 'ntc_admin';
$page   = $segments[1] ?? 'dashboard';

// Now you can use $module and $page just like before
/* $role = $_SESSION['user']['role'] ?? 'guest';
$isStaffRole = in_array($role, ['DepotOfficer','SLTBTimekeeper','PrivateTimekeeper'], true); */

?>
<!doctype html>
<html lang="en" data-theme="<?= isset($_SESSION['prefs']['theme']) ? $_SESSION['prefs']['theme'] : 'light' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NTC Admin</title>
  <link rel="stylesheet" href="/assets/css/admin.css">
  <script defer src="/assets/js/app.js"></script>
  <link rel="stylesheet" href="/assets/css/alert.css">
<script src="/assets/js/alert.js"></script>

</head>
<body>
  <header class="topbar">
    <div class="brand">
      <div class="logo">
  <img src="/assets/images/logo.png" alt="NexBus Logo">
      </div>

      <div>
        <div class="app">Bus Management System</div>
        <div class="sub">National Transport Commission - Sri Lanka</div>
      </div>
    </div>
    <div class="right">
      <div class="user">Admin Dashboard</div>
      <div class="date"><?= date('l d F Y'); ?></div>

    </div>
  </header>

  <div class="app-shell">
    <aside class="sidebar">
      <div class="sidebar-head">
        <div class="mini-logo">🛡️</div>
        <div>
          <div class="sb-title">NTC Dashboard</div>
          <div class="sb-sub">Admin Portal</div>
        </div>
       </div>  
      <nav class="menu">
        <a href="/A/dashboard" class="menu-item<?= ($module==='A' && $page==='dashboard') ? ' active' : '' ?>">
          <i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-layout-grid" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg></i>
          Dashboard
        </a>

        <a href="/A/fares" class="menu-item<?= ($module==='A' && $page==='fares') ? ' active' : '' ?>">
          <i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-dollar-sign" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></i>
          Fare Stages
        </a>

        <a href="/A/timetables" class="menu-item<?= ($module==='A' && $page==='timetables') ? ' active' : '' ?>">
          <i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-calendar" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg></i>
          Timetables
        </a>

        <a href="/A/users" class="menu-item<?= ($module==='A' && $page==='users') ? ' active' : '' ?>">
          <i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-users" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></i>
          User Management
        </a>

        <a href="/A/depots_owners" class="menu-item<?= ($module==='A' && $page==='depots_owners') ? ' active' : '' ?>">
          <i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-building" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 21V9"/><path d="M15 21V9"/><path d="M9 12h6"/></svg></i>
          Depots & Company
        </a>

        <a href="/A/analytics" class="menu-item<?= ($module==='A' && $page==='analytics') ? ' active' : '' ?>">
          <i class="icon"><svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-bar-chart-2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg></i>
          Analytics
        </a>
      </nav>

      <?php
      $user = $_SESSION['user'] ?? null;
      $initial = $user ? strtoupper(substr($user['name'] ?? 'G', 0, 1)) : '?';
      $email   = $user['email'] ?? 'admin@ntc.gov.lk';
      $name    = $user['name']  ?? 'Admin User';
      $role    = $user['role']  ?? '';
      ?>
      <div class="sidebar-profile">
        <!-- Whole card clickable to /profile -->
        <a href="/A/profile" class="profile-card">
          <div class="profile-avatar"><?= $initial ?></div>
          <div class="profile-meta">
            <div class="profile-name"><?= htmlspecialchars($name) ?></div>
            <div class="profile-email"><?= htmlspecialchars($email) ?></div>
          </div>
        </a>
        <!-- Logout button -->
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

      <?php
        require $contentViewFile;
      ?>
    </main>
  </div>

  <!-- Footer -->
