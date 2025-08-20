<section class="page-hero"><h1>Depot & Bus Owner Management</h1><p>Manage depot facilities and bus owner registrations</p></section>
<div class="tabs"><button class="tab active" data-tab="depots">Depots</button><button class="tab" data-tab="owners">Bus Owners</button></div>
<section id="depots" class="tabcontent show"><div class="cards grid3">
<?php foreach($depots as $d): ?><div class="card outline"><div class="card-title"><?=htmlspecialchars($d['name'])?></div>
<div class="muted">City: <?=htmlspecialchars($d['city'])?></div><div>Phone: <?=htmlspecialchars($d['phone'])?></div></div><?php endforeach; ?>
</div></section>
<section id="owners" class="tabcontent"><div class="cards grid3">
<?php foreach($owners as $o): ?><div class="card outline"><div class="card-title"><?=htmlspecialchars($o['name'])?></div><div>Reg No: <?=htmlspecialchars($o['reg_no'])?></div><div>Phone: <?=htmlspecialchars($o['contact_phone'])?></div></div><?php endforeach; ?>
</div></section>