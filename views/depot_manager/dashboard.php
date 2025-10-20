<?php
// Expected from controller: $todayLabel, $stats (3), $dailyStats (3), $activeCount, $delayed, $issues
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* Tiny inline SVG helper (no external class) */
function _svg(string $name, int $size = 18, string $stroke = 'currentColor'): string {
  $map = [
    'bus'   => '<rect x="3" y="11" width="18" height="7" rx="2"/><path d="M7 11V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v4"/><circle cx="7.5" cy="18.5" r="1.5"/><circle cx="16.5" cy="18.5" r="1.5"/>',
    'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'routes'=> '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',   // simple clock-like marker
    'check' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
    'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    'alert' => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    'pin'   => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
  ];
  $inner = $map[$name] ?? '<circle cx="12" cy="12" r="9"/>';
  $s = (int)$size;
  return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$s.'" height="'.$s.'" viewBox="0 0 24 24" fill="none" stroke="'.htmlspecialchars($stroke,ENT_QUOTES).'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$inner.'</svg>';
}
?>
<section class="section dashboard">
  <!-- Header strip (light, compact) -->
<div class="title-card">
  <h1 class="title-heading">Bus Management Dashboard</h1>
  <p class="title-sub">National Transport Commission – Sri Lanka</p>
</div>


  <!-- Top 3 cards -->
  <div class="grid grid-3 gap-16 mt-12">
    <?php foreach (($stats ?? []) as $s): ?>
      <?php
        $title = h($s['title'] ?? '');
        $value = h($s['value'] ?? '');
        $trend = strtolower((string)($s['trend'] ?? '')); // up|down
        $change= h($s['change'] ?? '');
        $icon  = (string)($s['icon'] ?? 'bus');
        $ico   = _svg($icon, 18, '#b25b66');              // subtle maroon
        $arrow = $trend === 'down' ? '▼' : '▲';
        $tcls  = $trend === 'down' ? 'text-red' : 'text-green';
      ?>
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-title"><?= $title ?></div>
          <div class="corner-ico"><?= $ico ?></div>
        </div>
        <div class="stat-main">
          <div class="stat-value"><?= $value ?></div>
          <div class="stat-trend">
            <span class="<?= $tcls ?>"><?= $arrow ?></span>
            <span class="muted"><?= $change ?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Daily 3 cards -->
  <div class="grid grid-3 gap-16 mt-16">
    <?php foreach (($dailyStats ?? []) as $s): ?>
      <?php
        $title = h($s['title'] ?? '');
        $value = h($s['value'] ?? '');
        $trend = strtolower((string)($s['trend'] ?? ''));
        $change= h($s['change'] ?? '');
        $icon  = (string)($s['icon'] ?? 'alert');
        $ico   = _svg($icon, 18, '#b25b66');
        $arrow = $trend === 'down' ? '▼' : '▲';
        $tcls  = $trend === 'down' ? 'text-red' : 'text-green';
      ?>
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-title"><?= $title ?></div>
          <div class="corner-ico"><?= $ico ?></div>
        </div>
        <div class="stat-main">
          <div class="stat-value"><?= $value ?></div>
          <div class="stat-trend">
            <span class="<?= $tcls ?>"><?= $arrow ?></span>
            <span class="muted"><?= $change ?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Map -->
  <div class="card mt-16">
    <div class="card__head">
      <div class="card__title">Real-Time Fleet Location Map</div>
    </div>
    <div class="map map--soft">
      <button class="map-badge">Live Bus Tracking</button>

      <!-- sample markers -->
      <span class="dot" style="top:24%;left:14%"></span>
      <span class="dot" style="top:36%;left:30%"></span>
      <span class="dot" style="top:28%;right:18%"></span>
      <span class="dot delayed" style="bottom:22%;left:22%"></span>
      <span class="dot" style="bottom:30%;right:30%"></span>

      <div class="map-center">
        <div class="pin"><?= _svg('pin', 42, '#7a0f2e') ?></div>
        <p class="primary">Sri Lanka Bus Fleet Map</p>
        <p class="muted"><?= (int)($activeCount ?? 0) ?> buses currently active</p>
        <div class="legend">
          <div class="legend__item"><span class="k green"></span>Active (<?= (int)($activeCount ?? 0) ?>)</div>
          <div class="legend__item"><span class="k yellow"></span>Delayed (<?= (int)($delayed ?? 0) ?>)</div>
          <div class="legend__item"><span class="k red"></span>Issues (<?= (int)($issues ?? 0) ?>)</div>
        </div>
      </div>
    </div>
  </div>
</section>
