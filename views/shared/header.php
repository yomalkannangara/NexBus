<?php
$module = $_GET['module'] ?? 'ntc_admin';
$page = $_GET['page'] ?? 'dashboard';
?><!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1" />
<title>NTC Admin</title>
<link rel="stylesheet" href="public/css/styles.css">
<script defer src="public/js/app.js"></script>
</head><body>
<header class="topbar"><div class="brand"><div class="logo">🚌</div>
<div><div class="app">Bus Management System</div><div class="sub">National Transport Commission - Sri Lanka</div></div></div>
<div class="right"><div class="user">Admin Dashboard</div><div class="date"><?php echo date('l d F Y'); ?></div></div></header>
<div class="layout"><aside class="sidebar"><div class="sidebrand"><div class="logomark">🛡️</div><div class="portal">NTC Dashboard<br><span>Admin Portal</span></div></div>
<nav class="sidenav">
  <a href="?module=ntc_admin&page=dashboard" class="<?php echo ($module==='ntc_admin'&&$page==='dashboard')?'active':''; ?>">Dashboard</a>
  <a href="?module=ntc_admin&page=fares" class="<?php echo ($module==='ntc_admin'&&$page==='fares')?'active':''; ?>">Fare Stages</a>
  <a href="?module=ntc_admin&page=timetables" class="<?php echo ($module==='ntc_admin'&&$page==='timetables')?'active':''; ?>">Timetables</a>
  <a href="?module=ntc_admin&page=users" class="<?php echo ($module==='ntc_admin'&&$page==='users')?'active':''; ?>">User Management</a>
  <a href="?module=ntc_admin&page=depots_owners" class="<?php echo ($module==='ntc_admin'&&$page==='depots_owners')?'active':''; ?>">Depots & Owners</a>
  <a href="?module=ntc_admin&page=analytics" class="<?php echo ($module==='ntc_admin'&&$page==='analytics')?'active':''; ?>">Analytics</a>
</nav><div class="version">&copy; 2025 NTC<br>Version 1.0.0</div></aside><main class="content">