// timekeeper.js — single, robust listener for both pages
(function () {
  // Read endpoint from closest root container or fall back to current path
  function getEndpoint(el) {
    const root = el && el.closest('[data-endpoint]');
    return (root && root.dataset.endpoint) || window.location.pathname;
  }

  function postForm(url, data) {
    return fetch(url, {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
      body: new URLSearchParams(data)
    }).then(r => r.json());
  }

  function parseHM(txt) {
    if (!txt) return null;
    const [h,m,s] = txt.split(':').map(n => parseInt(n,10)||0);
    const d = new Date(); d.setSeconds(0,0); d.setHours(h,m,(s||0),0);
    return d;
  }
  function inWindow(dep, arr, now=new Date()) {
    if (!dep) return false;
    if (!arr) { const end = new Date(now); end.setHours(23,59,59,999); return now >= dep && now <= end; }
    return now >= dep && now <= arr;
  }

  // Highlight helper (Trip Entry)
  function refreshHighlights() {
    document.querySelectorAll('#entryTable tbody tr.row').forEach(tr => {
      const dep = parseHM(tr.dataset.sdep || '');
      const arr = parseHM(tr.dataset.sarr || '');
      const current = inWindow(dep, arr);
      tr.classList.toggle('row-current', current);
      const badge = tr.querySelector('.js-badge');
      const startBtn = tr.querySelector('[data-action="start"]');
      if (badge && startBtn && !startBtn.disabled) {
        badge.className = 'badge ' + (current ? 'green' : 'blue');
        badge.textContent = current ? 'Current' : 'Scheduled';
      }
    });
  }
  refreshHighlights(); setInterval(refreshHighlights, 30000);

  // ONE click handler for everything
  document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-action]');
    if (!btn) return;

    const action   = btn.dataset.action;
    const endpoint = getEndpoint(btn);
    btn.disabled   = true;

    // Resolve IDs robustly: prefer button data, then row data, support many names
    const row = btn.closest('tr.row');

    function resolveId(...names) {
      for (const n of names) {
        const vBtn = btn.dataset[n];
        if (vBtn) return vBtn;
        const vRow = row && row.dataset[n];
        if (vRow) return vRow;
      }
      return '';
    }

    if (action === 'start') {
      const tt = parseInt(resolveId('tt','id','timetableId','timetable_id'), 10);
      if (!tt || Number.isNaN(tt)) {
        btn.disabled = false;
        alert('Missing timetable id. Reload and try again.');
        return;
      }
      if (!confirm('Start this trip now?')) { btn.disabled=false; return; }

      postForm(endpoint, { action:'start', timetable_id:String(tt) })
        .then(j => {
          if (j && j.ok) { location.reload(); return; }
          alert((j && j.msg) || 'Failed to start.');
          btn.disabled = false;
        })
        .catch(() => { alert('Network error'); btn.disabled=false; });
    }

    if (action === 'complete') {
      const id = parseInt(resolveId('tripId','id','private_trip_id','sltb_trip_id'), 10);
      if (!id || Number.isNaN(id)) {
        btn.disabled = false;
        alert('Missing trip id. Reload and try again.');
        return;
      }
      if (!confirm('Mark this trip as completed?')) { btn.disabled=false; return; }

      // send both keys; server will read whichever it needs
      postForm(endpoint, { action:'complete', private_trip_id:String(id), sltb_trip_id:String(id) })
        .then(j => {
          if (j && j.ok) { location.reload(); return; }
          alert('Failed to complete.');
          btn.disabled = false;
        })
        .catch(() => { alert('Network error'); btn.disabled=false; });
    }
  }, false);
})();
