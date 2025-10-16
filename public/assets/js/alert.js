/* Minimal global popups (vanilla):
   - Pretty modal for alert() (blocks until OK; returns Promise).
   - Pretty modal for inline HTML "return confirm('...')" (auto-patched).
   - Native window.confirm() (JS) remains unchanged.
*/
(() => {
  if (window.__ALERT_PATCHED__) return; window.__ALERT_PATCHED__ = true;

  // ---------- core ----------
  function ensureRoot(){
    if (!document.getElementById('ap-root')){
      const r = document.createElement('div'); r.id='ap-root'; document.body.appendChild(r);
    }
  }
  function escapeHtml(s){ return String(s).replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }
  function buildModal({title, message, buttons}) {
    ensureRoot();
    const root = document.getElementById('ap-root');
    const wrap = document.createElement('div'); wrap.className='ap-backdrop';
    wrap.innerHTML = `
      <div class="ap-modal" role="dialog" aria-live="assertive">
        <div class="ap-head">${escapeHtml(title||'Notice')}</div>
        <div class="ap-body"></div>
        <div class="ap-foot"></div>
      </div>`;
    const body = wrap.querySelector('.ap-body'); body.textContent = String(message);
    const foot = wrap.querySelector('.ap-foot');
    buttons.forEach(b => {
      const btn = document.createElement('button');
      btn.className = 'ap-btn' + (b.primary ? ' primary' : '');
      btn.type = 'button'; btn.textContent = b.text || 'OK';
      btn.addEventListener('click', () => b.onClick?.());
      foot.appendChild(btn);
    });
    document.getElementById('ap-root').appendChild(wrap);
    return wrap;
  }
  function closeModal(wrap){ wrap.remove(); }

  // ---------- alert() that waits for OK ----------
  const alertQ = []; let alertShowing = false;
  function nextAlert(){ const t = alertQ.shift(); if(!t){ alertShowing=false; return; } alertShowing = true; t().then(()=>{ alertShowing=false; nextAlert(); }); }

  window.alert = function(message){
    // Create a task that shows the modal and resolves ONLY on OK/Enter
    const task = () => new Promise(res=>{
      const w = buildModal({
        title: 'Notice',
        message,
        buttons: [{ text:'OK', primary:true, onClick:() => { closeModal(w); res(); } }]
      });
      // ONLY Enter acts like OK (no ESC, no backdrop close)
      document.addEventListener('keydown', function onKey(e){
        if(e.key === 'Enter'){ e.preventDefault(); closeModal(w); res(); }
      }, { once:true });
    });

    // Return a promise that resolves when this specific alert finishes
    const p = new Promise(resolve => {
      alertQ.push(() => task().then(resolve));
      if (!alertShowing) nextAlert();
    });
    return p;
  };

  // ---------- pretty confirm for inline HTML only ----------
  function confirmPretty(message){
    return new Promise(resolve=>{
      const w = buildModal({
        title: 'Please Confirm', message,
        buttons: [
          { text:'Cancel', onClick:()=>{ closeModal(w); resolve(false); } },
          { text:'OK',     primary:true, onClick:()=>{ closeModal(w); resolve(true); } }
        ]
      });
      // ESC/backdrop behave like Cancel for confirm
      w.addEventListener('click', e => { if(e.target===w){ closeModal(w); resolve(false); } });
      document.addEventListener('keydown', function onKey(e){
        if(e.key==='Escape'){ e.preventDefault(); closeModal(w); resolve(false); }
        if(e.key==='Enter'){  e.preventDefault(); closeModal(w); resolve(true); }
      }, { once:true });
    });
  }

  // ---------- auto-patch inline confirm patterns ----------
  function patchInlineConfirms(ctx=document){
    // onclick="return confirm('...')"
    ctx.querySelectorAll('[onclick]').forEach(el=>{
      const code = (el.getAttribute('onclick')||'').trim();
      const m = code.match(/^return\s*confirm\(\s*(["'])([\s\S]*?)\1\s*\)\s*;?$/);
      if(!m) return;
      const msg = m[2];
      el.removeAttribute('onclick');
      el.addEventListener('click', function(e){
        if (el.dataset.apBypass==='1') return;
        e.preventDefault();
        confirmPretty(msg).then(ok=>{
          if(!ok) return;
          el.dataset.apBypass='1';
          if (el.tagName==='A' && el.href) {
            window.location.href = el.href;
          } else if (el.type==='submit') {
            const form = el.closest('form'); if(form) form.submit();
          } else {
            el.click();
          }
          delete el.dataset.apBypass;
        });
      });
    });

    // <form onsubmit="return confirm('...')">
    ctx.querySelectorAll('form[onsubmit]').forEach(form=>{
      const code = (form.getAttribute('onsubmit')||'').trim();
      const m = code.match(/^return\s*confirm\(\s*(["'])([\s\S]*?)\1\s*\)\s*;?$/);
      if(!m) return;
      const msg = m[2];
      form.removeAttribute('onsubmit');
      form.addEventListener('submit', function(e){
        if (form.dataset.apBypass==='1') return;
        e.preventDefault();
        confirmPretty(msg).then(ok=>{
          if(ok){ form.dataset.apBypass='1'; form.submit(); delete form.dataset.apBypass; }
        });
      });
    });

    // data-confirm="..."
    document.addEventListener('click', function(e){
      const t = e.target.closest('[data-confirm]'); if(!t) return;
      if (t.dataset.apHandling==='1') return;
      e.preventDefault();
      const msg = t.getAttribute('data-confirm') || 'Are you sure?';
      confirmPretty(msg).then(ok=>{
        if(!ok) return;
        t.dataset.apHandling='1';
        if (t.tagName==='A' && t.href) window.location.href = t.href;
        else if (t.type==='submit'){ const f=t.closest('form'); if(f) f.submit(); }
        else t.click();
        delete t.dataset.apHandling;
      });
    }, true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { ensureRoot(); patchInlineConfirms(); }, { once:true });
  } else { ensureRoot(); patchInlineConfirms(); }
})();
