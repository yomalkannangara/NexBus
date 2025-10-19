<?php use App\Support\Icons; ?>
<!doctype html>
<html lang="en" class="">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>NTC Fleet Management</title>
  <link rel="stylesheet" href="/assets/css/globals.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
  <script defer src="/assets/js/app.js"></script>
</head>
<body>
<div class="app-shell">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar__brand">
      <div class="brand__logo">
        <img src="/assets/images/logo.png" alt="NTC Logo">
      </div>
      <div class="brand__text">
        <div class="brand__title">NTC</div>
        <div class="brand__subtitle">Fleet Management</div>
      </div>
    </div>

    <div class="sidebar__search">
      <span class="icon"><?php echo Icons::svg('search','',16,16); ?></span>
      <input type="text" placeholder="Search modules..." id="moduleSearch">
    </div>

    <nav class="sidebar__nav" id="sideNav">
      <?php
      $links = [
        ['/dashboard','Dashboard','layout-dashboard'],
        ['/fleet','Fleet Management','bus'],
        ['/feedback','Passenger Feedback','message-square'],
        ['/maintenance','Bus Health Monitor','wrench'],
        ['/drivers','Driver Database','database'],
        ['/performance','Driver Performance','trending-up'],
        ['/earnings','Earnings','dollar-sign'],
      ];
      $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
      foreach ($links as $ln) {
        $href = $ln[0]; $label = $ln[1]; $icon = $ln[2];
        $active = ($href === $path) ? ' active' : '';
        echo '<a href="'.$href.'" class="nav__item'.$active.'"><span class="icon">'.Icons::svg($icon,'',20,20).'</span><span>'.$label.'</span></a>';
      }
      ?>
    </nav>

    <div class="sidebar__cta">
      <button class="btn btn-secondary w-100">
        <span class="icon"><?php echo Icons::svg('plus','',18,18); ?></span>
        New Project
      </button>
    </div>

    <div class="sidebar__profile">
      <button class="profile__trigger" id="profileTrigger" type="button">
        <div class="avatar">AD</div>
        <div class="profile__meta">
          <div class="name">Admin User</div>
          <div class="role">Administrator</div>
        </div>
        <span class="icon"><?php echo Icons::svg('chevron-down','',18,18); ?></span>
      </button>
      <div class="dropdown" id="profileDropdown" aria-hidden="true">
        <a href="#" class="dropdown__item"><?php echo Icons::svg('user','',16,16); ?> Profile</a>
        <a href="#" class="dropdown__item"><?php echo Icons::svg('settings','',16,16); ?> Settings</a>
        <div class="dropdown__sep"></div>
        <a href="/logout" class="dropdown__item destructive"><?php echo Icons::svg('user','',16,16); ?> Logout</a>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <main class="main">
    <?php include $contentView; ?>
  </main>
</div>
</body>
</html>
