<div class="container">
  <h2>Inbox</h2>
  <a href="/messages/compose" class="btn btn-primary">Compose</a>
  <table class="table mt-3">
    <thead><tr><th></th><th>Subject</th><th>From</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach (($messages ?? []) as $m): ?>
      <tr data-recipient-id="<?= htmlspecialchars($m['recipient_id']) ?>" class="<?= $m['is_read'] ? 'read' : 'unread' ?>">
        <td><button class="mark-read btn btn-sm btn-link">Mark</button></td>
        <td><?= htmlspecialchars($m['subject'] ?? '(no subject)') ?><div class="small text-muted"><?= nl2br(htmlspecialchars(substr($m['body'],0,200))) ?></div></td>
        <td><?= htmlspecialchars($m['sender_id'] ?? '-') ?></td>
        <td><?= htmlspecialchars($m['created_at'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
document.querySelectorAll('.mark-read').forEach(btn=>{
  btn.addEventListener('click', e=>{
    const tr = e.target.closest('tr');
    const rid = tr.dataset.recipientId;
    fetch('/messages/markread', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'recipient_id='+encodeURIComponent(rid)})
      .then(r=>r.json()).then(j=>{ if (j.ok) tr.classList.add('read'); });
  });
});
</script>
