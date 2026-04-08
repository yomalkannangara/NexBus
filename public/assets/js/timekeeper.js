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
      // use pretty confirm (modal) for both mobile and desktop
      window.confirmPretty('Start this trip now?').then(ok => {
        if (!ok) { btn.disabled=false; return; }
        postForm(endpoint, { action:'start', timetable_id:String(tt) })
          .then(j => {
            if (j && j.ok) {
              window.showNotification('Trip started — ready to go.', 'success');
              setTimeout(() => location.reload(), 900);
              return;
            }
            const msg = (j && j.msg) || 'Failed to start.';
            window.showNotification(msg, 'error');
            btn.disabled = false;
          })
          .catch(() => { window.showNotification('Network error', 'error'); btn.disabled=false; });
      });
      return;
    }

    if (action === 'complete') {
      const id = parseInt(resolveId('tripId','id','private_trip_id','sltb_trip_id'), 10);
      if (!id || Number.isNaN(id)) {
        btn.disabled = false;
        alert('Missing trip id. Reload and try again.');
        return;
      }
      window.confirmPretty('Mark this trip as completed?').then(ok => {
        if (!ok) { btn.disabled=false; return; }
        // send both keys; server will read whichever it needs
        postForm(endpoint, { action:'complete', private_trip_id:String(id), sltb_trip_id:String(id) })
          .then(j => {
            if (j && j.ok) {
              window.showNotification('Trip completed successfully.', 'success');
              setTimeout(() => location.reload(), 900);
              return;
            }
            window.showNotification('Failed to complete.', 'error');
            btn.disabled = false;
          })
          .catch(() => { window.showNotification('Network error', 'error'); btn.disabled=false; });
      });
      return;
    }
    if (action === 'cancel') {
      const id = parseInt(resolveId('tripId','id','private_trip_id','sltb_trip_id'), 10);
      if (!id || Number.isNaN(id)) {
        btn.disabled = false;
        window.showNotification('Missing trip ID. Reload and try again.', 'error');
        return;
      }
      
      // Use themed modal to collect reason
      window.showReasonModal(
        'Stop Trip',
        'Why are you stopping this trip in the middle? (required)'
      ).then(reason => {
        if (reason === null) { 
          btn.disabled = false; 
          return; 
        } // user cancelled modal

        // Confirm before sending using pretty confirm
        window.confirmPretty('Are you sure you want to stop this trip?').then(ok => {
          if (!ok) { btn.disabled = false; return; }

          postForm(endpoint, { 
            action: 'cancel', 
            private_trip_id: String(id), 
            sltb_trip_id: String(id), 
            reason: String(reason) 
          })
            .then(j => {
              if (j && j.ok) { 
                window.showNotification('Trip cancelled successfully.', 'success');
                setTimeout(() => { location.reload(); }, 1000);
                return; 
              }
              // Handle structured error response
              let msg = 'Failed to cancel.';
              if (j && j.msg) {
                const msgs = {
                  'no_reason': 'A reason is required.',
                  'not_authorized': 'You are not authorized to cancel this trip.',
                  'not_in_progress': 'This trip is not currently in progress.',
                  'no_trip': 'Trip not found — it may have been cancelled or completed. Please refresh.',
                  'no_depot_match': 'Could not determine the route endpoint.',
                  'update_failed': 'Database update failed.'
                };
                msg = msgs[j.msg] || j.msg;
              }
              window.showNotification(msg, 'error');
              btn.disabled = false;
            })
            .catch(err => { 
              window.showNotification('Network error: ' + (err.message||''), 'error');
              btn.disabled = false;
            });
        });
      });
      return;
    }
  }, false);
})();
