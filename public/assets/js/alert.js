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
  // expose confirmPretty for external callers
  window.confirmPretty = confirmPretty;

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

  // ---------- toast notifications (top-right on desktop) ----------
  window.showNotification = function(message, type='info', duration=4000) {
    ensureRoot();
    // On narrow screens show a blocking modal-style notification
    if (window.innerWidth <= 640) {
      const root = document.getElementById('ap-root');
      const wrap = document.createElement('div');
      wrap.className = 'ap-backdrop ap-notify-modal';
      wrap.innerHTML = `
        <div class="ap-modal" role="dialog">
          <div class="ap-head">${escapeHtml(type==='success'?'Success': type==='error'?'Error':'Notice')}</div>
          <div class="ap-body">${escapeHtml(message)}</div>
          <div class="ap-foot"><button class="ap-btn primary ap-ok">OK</button></div>
        </div>`;
      root.appendChild(wrap);
      wrap.querySelector('.ap-ok').addEventListener('click', () => wrap.remove());
      return wrap;
    }

    const toast = document.createElement('div');
    toast.className = `ap-toast ap-toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: ${type==='success'?'#4caf50':type==='error'?'#f44336':'#2196f3'};
      color: white;
      padding: 12px 20px;
      border-radius: 4px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      font-size: 14px;
      z-index: 10000;
      animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(toast);
    if (duration) {
      setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
      }, duration);
    }
    return toast;
  };

  // Inject animations if not already present
  if (!document.getElementById('ap-animations')) {
    const style = document.createElement('style');
    style.id = 'ap-animations';
    style.textContent = `
      @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
      @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
      }
    `;
    document.head.appendChild(style);
  }

  // ---------- input modal for reason (responsive) ----------
  window.showReasonModal = function(title='Enter Reason', placeholder='Why are you cancelling?') {
    return new Promise(resolve => {
      ensureRoot();
      const root = document.getElementById('ap-root');
      const wrap = document.createElement('div');
      wrap.className = 'ap-backdrop ap-reason-modal';
      wrap.innerHTML = `
        <div class="ap-modal ap-modal-input" role="dialog">
          <div class="ap-head">${escapeHtml(title)}</div>
          <div class="ap-body">
            <textarea id="ap-reason-input" placeholder="${escapeHtml(placeholder)}" style="width:100%;min-height:60px;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit;font-size:14px;"></textarea>
          </div>
          <div class="ap-foot">
            <button class="ap-btn ap-cancel-btn" type="button">Cancel</button>
            <button class="ap-btn ap-confirm-btn primary" type="button">Confirm</button>
          </div>
        </div>
      `;
      root.appendChild(wrap);
      
      const input = wrap.querySelector('#ap-reason-input');
      const cancelBtn = wrap.querySelector('.ap-cancel-btn');
      const confirmBtn = wrap.querySelector('.ap-confirm-btn');
      
      input.focus();
      
      const close = () => {
        wrap.remove();
      };
      
      cancelBtn.addEventListener('click', () => {
        close();
        resolve(null);
      });
      
      confirmBtn.addEventListener('click', () => {
        const reason = input.value.trim();
        if (!reason) {
          input.style.borderColor = '#f44336';
          input.style.backgroundColor = 'rgba(244,67,54,0.1)';
          return;
        }
        close();
        resolve(reason);
      });
      
      wrap.addEventListener('click', e => {
        if (e.target === wrap) {
          close();
          resolve(null);
        }
      });
      
      document.addEventListener('keydown', function onKey(e) {
        if (e.key === 'Escape') {
          close();
          resolve(null);
        } else if (e.key === 'Enter' && e.ctrlKey) {
          e.preventDefault();
          const reason = input.value.trim();
          if (reason) {
            close();
            resolve(reason);
          }
        }
      }, { once: true });
    });
  };
})();
