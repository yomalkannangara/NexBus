(function () {
  const entry = document.getElementById('tripEntry');
  const turns = document.getElementById('turnMgmt');

  // helpers
  function getEndpoint(root) {
    return (root && root.dataset.endpoint) ? root.dataset.endpoint : window.location.href;
  }
  async function post(endpoint, data) {
    const fd = new FormData();
    Object.entries(data).forEach(([k, v]) => fd.append(k, v));
    const r = await fetch(endpoint, { method: 'POST', body: fd, headers: {'X-Requested-With':'fetch'} });
    return r.json();
  }
  function parseHM(txt) { // accepts HH:MM or HH:MM:SS
    if (!txt) return null;
    const [h,m,s] = txt.split(':').map(n => parseInt(n,10)||0);
    const d = new Date(); d.setSeconds(0,0);
    d.setHours(h,m,(s||0),0);
    return d;
  }
  function inWindow(dep, arr, now=new Date()) {
    if (!dep) return false;
    if (!arr) { // treat no-arrival as end of day
      const end = new Date(now); end.setHours(23,59,59,999);
      return now >= dep && now <= end;
    }
    return now >= dep && now <= arr;
  }

  // ===== Trip Entry page =====
  if (entry) {
    const endpoint = getEndpoint(entry);
    const table = document.getElementById('entryTable');

    // highlight current rows on load + every 30s
    function highlight() {
      const rows = table.querySelectorAll('tbody tr.row');
      const now = new Date();
      rows.forEach(tr => {
        const dep = parseHM(tr.dataset.sdep || '');
        const arr = parseHM(tr.dataset.sarr || '');
        tr.classList.toggle('row-current', inWindow(dep, arr, now));
        // also update badge if not recorded yet
        const badge = tr.querySelector('.js-badge');
        const startBtn = tr.querySelector('.btn-start');
        if (badge && startBtn && !startBtn.disabled) {
          badge.className = 'badge ' + (tr.classList.contains('row-current') ? 'green' : 'blue');
          badge.textContent = tr.classList.contains('row-current') ? 'Current' : 'Scheduled';
        }
      });
    }
    highlight();
    setInterval(highlight, 30000);

    table.addEventListener('click', async (e) => {
      const btn = e.target.closest('.btn-start');
      if (!btn) return;
      if (btn.disabled) return;

      const tr = btn.closest('tr');
      const tt = tr?.dataset.tt;
      if (!tt) return;

      if (!confirm('Start this trip now?')) return;
      try {
        const js = await post(endpoint, { action:'start', timetable_id: tt });
        if (js && js.ok) {
          // lock this row
          btn.disabled = true;
          const badge = tr.querySelector('.js-badge');
          if (badge) { badge.className='badge gray'; badge.textContent='Recorded'; }
          // optional flash
          tr.style.transition = 'background-color .6s ease';
          tr.style.backgroundColor = 'rgba(34,197,94,.12)';
          setTimeout(()=> tr.style.backgroundColor = '', 700);
        } else {
          alert((js && js.msg) || 'Failed to start.');
        }
      } catch (err) {
        alert('Network error.');
      }
    });
  }

  // ===== Turn Management page =====
  if (turns) {
    const endpoint = getEndpoint(turns);
    const table = document.getElementById('turnTable');

    table.addEventListener('click', async (e) => {
      const btn = e.target.closest('.btn-complete');
      if (!btn) return;

      const tr = btn.closest('tr');
      const id = tr?.dataset.tripId;
      if (!id) return;

      if (!confirm('Mark this trip as completed?')) return;
      try {
        const js = await post(endpoint, { action:'complete', sltb_trip_id:id });
        if (js && js.ok) {
          tr.remove(); // disappears from running list
        } else {
          alert('Failed to complete.');
        }
      } catch (err) {
        alert('Network error.');
      }
    });
  }
})();
