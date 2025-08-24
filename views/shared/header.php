<?php
$module = $_GET['module'] ?? 'ntc_admin';
$page   = $_GET['page']   ?? 'dashboard';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>NTC Admin</title>
  <link rel="stylesheet" href="public/css/styles.css">
  <script defer src="public/js/app.js"></script>
</head>
<body>
  <header class="topbar">
    <div class="brand">
      <div class="logo">
        <img src="/NexBus-1/public/images/logo.png" alt="NexBus Logo">
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
        <a href="?module=ntc_admin&page=dashboard" 
           class="menu-item<?= ($module==='ntc_admin' && $page==='dashboard') ? ' active' : '' ?>">Dashboard</a>
        <a href="?module=ntc_admin&page=fares" 
           class="menu-item<?= ($module==='ntc_admin' && $page==='fares') ? ' active' : '' ?>">Fare Stages</a>
        <a href="?module=ntc_admin&page=timetables" 
           class="menu-item<?= ($module==='ntc_admin' && $page==='timetables') ? ' active' : '' ?>">Timetables</a>
        <a href="?module=ntc_admin&page=users" 
           class="menu-item<?= ($module==='ntc_admin' && $page==='users') ? ' active' : '' ?>">User Management</a>
        <a href="?module=ntc_admin&page=depots_owners" 
           class="menu-item<?= ($module==='ntc_admin' && $page==='depots_owners') ? ' active' : '' ?>">Depots & Owners</a>
        <a href="?module=ntc_admin&page=analytics" 
           class="menu-item<?= ($module==='ntc_admin' && $page==='analytics') ? ' active' : '' ?>">Analytics</a>
      </nav>
    <div class="sidebar-foot">
      <div class="version">&copy; <?= date('Y'); ?> NTC<br>Version 1.0.0</div>
    </div>
    </aside>

    <main class="content">
      <!-- Page-specific content will load here -->
