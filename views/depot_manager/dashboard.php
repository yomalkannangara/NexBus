<?php use App\Support\Icons; ?>
<section class="section">
  <!-- Gradient Header -->
  <div class="gradient-card">
    <div>
      <h1 class="h1">Bus Management Dashboard</h1>
      <p class="muted">National Transport Commission - Sri Lanka</p>
    </div>
    <div class="right">
      <p class="muted">Admin Dashboard</p>
      <p><?= htmlspecialchars($todayLabel) ?></p>
    </div>
  </div>

  <!-- Top stats (3) -->
  <div class="grid grid-3 gap-6">
    <?php foreach ($stats as $s): ?>
      <div class="card">
        <div class="card__head">
          <div class="card__title muted"><?= htmlspecialchars($s['title']) ?></div>
          <div class="icon colored" style="color:<?= htmlspecialchars($s['color']) ?>"><?= Icons::svg($s['icon'],'',20,20) ?></div>
        </div>
        <div class="card__body">
          <div class="value primary"><?= htmlspecialchars($s['value']) ?></div>
          <div class="trend">
            <?php
              $ticon = $s['trend']==='up' ? 'trending-up' : ($s['trend']==='down' ? 'trending-down' : 'minus');
              echo Icons::svg($ticon,'',16,16);
            ?>
            <span class="muted"><?= htmlspecialchars($s['change']) ?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Daily stats (3) -->
  <div class="grid grid-3 gap-6 mt-6">
    <?php foreach ($dailyStats as $s): ?>
      <div class="card">
        <div class="card__head">
          <div class="card__title muted"><?= htmlspecialchars($s['title']) ?></div>
          <div class="icon colored" style="color:<?= htmlspecialchars($s['color']) ?>"><?= Icons::svg($s['icon'],'',20,20) ?></div>
        </div>
        <div class="card__body">
          <div class="value"><?= htmlspecialchars($s['value']) ?></div>
          <div class="trend"><?= Icons::svg($s['trend']==='up'?'trending-up':'trending-down','',16,16) ?><span class="muted"><?= htmlspecialchars($s['change']) ?></span></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Map block -->
  <div class="card mt-6">
    <div class="card__head">
      <div class="card__title">Real-Time Fleet Location Map</div>
    </div>
    <div class="map">
      <div class="map__bg"></div>
      <div class="map__badge">Live Bus Tracking</div>

      <!-- markers -->
      <span class="dot" style="top:20%;left:16%"></span>
      <span class="dot" style="top:32%;left:32%"></span>
      <span class="dot" style="top:24%;right:20%"></span>
      <span class="dot delayed" style="bottom:20%;left:24%"></span>
      <span class="dot" style="bottom:32%;right:32%"></span>

      <div class="map__center">
        <div class="icon primary"><?= Icons::svg('map-pin','',48,48) ?></div>
        <p class="primary">Sri Lanka Bus Fleet Map</p>
        <p class="muted"><?= (int)$activeCount ?> buses currently active</p>
        <div class="legend">
          <div class="legend__item"><span class="k green"></span>Active (<?= (int)$activeCount ?>)</div>
          <div class="legend__item"><span class="k yellow"></span>Delayed (<?= (int)$delayed ?>)</div>
          <div class="legend__item"><span class="k red"></span>Issues (<?= (int)$issues ?>)</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick actions -->
  <div class="grid grid-4 gap-4 mt-6">
    <div class="card hoverable center">
      <div class="icon primary"><?= Icons::svg('bus','',32,32) ?></div>
      <p class="label">Register New Bus</p>
    </div>
    <div class="card hoverable center">
      <div class="icon primary"><?= Icons::svg('user','',32,32) ?></div>
      <p class="label">Add Driver</p>
    </div>
    <div class="card hoverable center">
      <div class="icon primary"><?= Icons::svg('map-pin','',32,32) ?></div>
      <p class="label">Create Route</p>
    </div>
    <div class="card hoverable center">
      <div class="icon primary"><?= Icons::svg('alert','',32,32) ?></div>
      <p class="label">Report Issue</p>
    </div>
  </div>
</section>
