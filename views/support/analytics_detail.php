<?php
  $pageTitle = $pageTitle ?? 'Analytics Details';
  $pageSubtitle = $pageSubtitle ?? 'Detailed chart view';
  $detailPath = $detailPath ?? '';
  $backUrl = $backUrl ?? '/';
  $chartLabel = $chartLabel ?? 'Chart';
  $rows = $rows ?? [];
  $columns = $columns ?? [];
  $summaryCards = $summaryCards ?? [];
  $filterValues = $filterValues ?? [];
  $filterOptions = $filterOptions ?? [];
  $secondaryRows = $secondaryRows ?? [];
  $secondaryTitle = $secondaryTitle ?? '';
  $secondaryColumns = $secondaryColumns ?? [];
  $byRouteRows = $byRouteRows ?? [];
  $byRouteColumns = $byRouteColumns ?? [];
  $byDepotRows = $byDepotRows ?? [];
  $byDepotColumns = $byDepotColumns ?? [];
?>

<section class="page-hero">
  <h1><?= htmlspecialchars($pageTitle) ?></h1>
  <p><?= htmlspecialchars($pageSubtitle) ?></p>
</section>

<section class="filters-panel">
  <div class="filters-title">
    <span><?= htmlspecialchars($chartLabel) ?> Details</span>
    <a class="button outline" href="<?= htmlspecialchars($backUrl) ?>">Back to Analytics</a>
  </div>

  <form method="get" action="<?= htmlspecialchars($detailPath) ?>" class="filters-grid-3">
    <input type="hidden" name="chart" value="<?= htmlspecialchars($filterValues['chart'] ?? '') ?>">

    <?php if (isset($filterOptions['routes'])): ?>
      <div>
        <label for="flt-route">Route</label>
        <div class="nb-select">
          <select id="flt-route" name="route_no">
            <option value="">All Routes</option>
            <?php foreach (($filterOptions['routes'] ?? []) as $r):
              $routeNo = (string)($r['route_no'] ?? '');
            ?>
              <option value="<?= htmlspecialchars($routeNo) ?>" <?= (($filterValues['route_no'] ?? '') === $routeNo) ? 'selected' : '' ?>>
                <?= htmlspecialchars($routeNo) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    <?php endif; ?>

    <?php if (isset($filterOptions['depots'])): ?>
      <div>
        <label for="flt-depot">Depot</label>
        <div class="nb-select">
          <select id="flt-depot" name="depot_id">
            <option value="0">All Depots</option>
            <?php foreach (($filterOptions['depots'] ?? []) as $d):
              $depId = (int)($d['id'] ?? 0);
            ?>
              <option value="<?= $depId ?>" <?= ((int)($filterValues['depot_id'] ?? 0) === $depId) ? 'selected' : '' ?>>
                <?= htmlspecialchars((string)($d['name'] ?? 'Depot')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    <?php endif; ?>

    <?php if (isset($filterOptions['owners'])): ?>
      <div>
        <label for="flt-owner">Bus Owner</label>
        <div class="nb-select">
          <select id="flt-owner" name="owner_id">
            <option value="0">All Owners</option>
            <?php foreach (($filterOptions['owners'] ?? []) as $o):
              $ownerId = (int)($o['id'] ?? 0);
            ?>
              <option value="<?= $ownerId ?>" <?= ((int)($filterValues['owner_id'] ?? 0) === $ownerId) ? 'selected' : '' ?>>
                <?= htmlspecialchars((string)($o['name'] ?? 'Owner')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    <?php endif; ?>

    <?php if (isset($filterOptions['buses'])): ?>
      <div>
        <label for="flt-bus">Bus</label>
        <div class="nb-select">
          <select id="flt-bus" name="bus_reg">
            <option value="">All Buses</option>
            <?php foreach (($filterOptions['buses'] ?? []) as $b):
              $reg = (string)($b['reg_no'] ?? $b['bus_registration_no'] ?? $b['reg_no_alt'] ?? '');
              if ($reg === '') { continue; }
            ?>
              <option value="<?= htmlspecialchars($reg) ?>" <?= (($filterValues['bus_reg'] ?? '') === $reg) ? 'selected' : '' ?>>
                <?= htmlspecialchars($reg) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($filterOptions['showDateRange'])): ?>
      <div>
        <label for="flt-from">From</label>
        <input id="flt-from" type="date" name="from" value="<?= htmlspecialchars((string)($filterValues['from'] ?? '')) ?>">
      </div>
      <div>
        <label for="flt-to">To</label>
        <input id="flt-to" type="date" name="to" value="<?= htmlspecialchars((string)($filterValues['to'] ?? '')) ?>">
      </div>
    <?php endif; ?>

    <div>
      <button class="button btn btn-primary" type="submit">Apply Filters</button>
    </div>
  </form>
</section>

<?php if (!empty($summaryCards)): ?>
<section class="kpi-wrap kpi-wrap--neo">
  <?php foreach ($summaryCards as $card): ?>
    <article class="kpi2">
      <header><h3><?= htmlspecialchars((string)($card['title'] ?? 'Metric')) ?></h3></header>
      <div class="value"><?= htmlspecialchars((string)($card['value'] ?? '0')) ?></div>
      <div class="hint"><?= htmlspecialchars((string)($card['hint'] ?? '')) ?></div>
    </article>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<section class="table-panel card" data-expand-table-section>
  <div class="table-panel-head">
    <h2>Detailed Data Table</h2>
  </div>
  <div class="table-wrap">
    <table class="table js-expandable-table">
      <thead>
        <tr>
          <?php foreach ($columns as $c): ?>
            <th><?= htmlspecialchars((string)$c) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="<?= max(1, count($columns)) ?>">No data found for the selected filters.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <?php foreach (array_keys($columns) as $k): ?>
                <td><?= htmlspecialchars((string)($r[$k] ?? '')) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="table-panel-head">
    <span></span>
    <button type="button" class="btn btn-outline small js-expand-toggle">Expand</button>
  </div>
</section>

<?php if (!empty($secondaryRows)): ?>
<section class="table-panel card" data-expand-table-section>
  <div class="table-panel-head">
    <h2><?= htmlspecialchars($secondaryTitle ?: 'Additional Breakdown') ?></h2>
  </div>
  <div class="table-wrap">
    <table class="table js-expandable-table">
      <thead>
        <tr>
          <?php foreach ($secondaryColumns as $c): ?>
            <th><?= htmlspecialchars((string)$c) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($secondaryRows as $r): ?>
          <tr>
            <?php foreach (array_keys($secondaryColumns) as $k): ?>
              <td><?= htmlspecialchars((string)($r[$k] ?? '')) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="table-panel-head">
    <span></span>
    <button type="button" class="btn btn-outline small js-expand-toggle">Expand</button>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($byRouteRows)): ?>
<section class="table-panel card" data-expand-table-section>
  <div class="table-panel-head">
    <h2>Route Summary</h2>
  </div>
  <div class="table-wrap">
    <table class="table js-expandable-table">
      <thead>
        <tr>
          <?php foreach ($byRouteColumns as $c): ?>
            <th><?= htmlspecialchars((string)$c) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($byRouteRows as $r): ?>
          <tr>
            <?php foreach (array_keys($byRouteColumns) as $k): ?>
              <td><?= htmlspecialchars((string)($r[$k] ?? '')) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="table-panel-head">
    <span></span>
    <button type="button" class="btn btn-outline small js-expand-toggle">Expand</button>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($byDepotRows)): ?>
<section class="table-panel card" data-expand-table-section>
  <div class="table-panel-head">
    <h2>Depot / Owner Summary</h2>
  </div>
  <div class="table-wrap">
    <table class="table js-expandable-table">
      <thead>
        <tr>
          <?php foreach ($byDepotColumns as $c): ?>
            <th><?= htmlspecialchars((string)$c) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($byDepotRows as $r): ?>
          <tr>
            <?php foreach (array_keys($byDepotColumns) as $k): ?>
              <td><?= htmlspecialchars((string)($r[$k] ?? '')) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="table-panel-head">
    <span></span>
    <button type="button" class="btn btn-outline small js-expand-toggle">Expand</button>
  </div>
</section>
<?php endif; ?>

<script>
(function () {
  var LIMIT = 10;
  var sections = document.querySelectorAll('[data-expand-table-section]');

  sections.forEach(function (section) {
    var table = section.querySelector('.js-expandable-table');
    var btn = section.querySelector('.js-expand-toggle');
    if (!table || !btn) return;

    var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
    if (rows.length <= LIMIT) {
      btn.style.display = 'none';
      return;
    }

    var expanded = false;

    function render() {
      rows.forEach(function (row, index) {
        row.style.display = (!expanded && index >= LIMIT) ? 'none' : '';
      });
      btn.textContent = expanded ? 'Show Less' : 'Expand';
    }

    btn.addEventListener('click', function () {
      expanded = !expanded;
      render();
    });

    render();
  });
})();
</script>
