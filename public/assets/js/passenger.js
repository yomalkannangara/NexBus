// Theme toggle (profile)
document.addEventListener('change', e=>{
  if(e.target && e.target.id==='themeToggle'){
    const on = e.target.checked;
    try{ localStorage.setItem('nexbus:theme:dark', on?'1':'0'); }catch(_){}
    document.documentElement.classList.toggle('dark', on);
    toast(on?'Dark mode on 🌙':'Light mode ☀️');
  }
});

// Apply stored theme
(()=>{ try{ if(localStorage.getItem('nexbus:theme:dark')==='1') document.documentElement.classList.add('dark'); }catch(_){}})();

// Simple toast
function toast(msg){
  let el = document.createElement('div');
  el.textContent = msg;
  el.style.cssText = `
    position: fixed; left: 50%; bottom: 86px; transform: translateX(-50%);
    background: linear-gradient(135deg,#223056,#1b2a4e);
    color:#fff; padding:10px 14px; border-radius:12px; border:1px solid rgba(255,255,255,.08);
    z-index:100; box-shadow:0 10px 26px rgba(0,0,0,.45); font-weight:700; letter-spacing:.2px;
  `;
  document.body.appendChild(el);
  setTimeout(()=>{ el.style.opacity='0'; el.style.transition='opacity .3s'; }, 1600);
  setTimeout(()=> el.remove(), 2000);
}

// Button ripple
document.addEventListener('click', e=>{
  const btn = e.target.closest('.btn');
  if(!btn) return;
  const d = document.createElement('span');
  const rect = btn.getBoundingClientRect();
  const size = Math.max(rect.width, rect.height);
  d.style.cssText = `
    position:absolute; left:${e.clientX-rect.left-size/2}px; top:${e.clientY-rect.top-size/2}px;
    width:${size}px; height:${size}px; border-radius:50%;
    background: radial-gradient(circle, rgba(255,255,255,.5), transparent 60%);
    pointer-events:none; transform: scale(0); opacity:.8; animation:rip .6s ease-out forwards;
  `;
  btn.style.position='relative'; btn.appendChild(d);
  setTimeout(()=>d.remove(), 650);
});
const st = document.createElement('style');
st.textContent = `@keyframes rip { to { transform: scale(1); opacity:0; } }`;
document.head.appendChild(st);
// Auto-submit when route is changed (Home filter)

// feedback photo filename preview
document.addEventListener('change', (e)=>{
  if(e.target && e.target.id === 'fbPhoto'){
    const out = document.getElementById('fbPhotoName');
    if(!out) return;
    out.textContent = e.target.files && e.target.files[0] ? e.target.files[0].name : '';
  }
});


document.addEventListener('DOMContentLoaded', () => {
  const routeSelect = document.querySelector('select[name="route_id"]');
  const busSelect   = document.getElementById('busSelect');

  if (!routeSelect || !busSelect) return;

  // Maps bus_reg_no -> operator_type so we can auto-tick the Bus Type radio
  const busTypeMap = {};

  function resetBuses(placeholder) {
    busSelect.innerHTML = '<option value="">' + placeholder + '</option>';
    Object.keys(busTypeMap).forEach(k => delete busTypeMap[k]);
  }

  routeSelect.addEventListener('change', async function () {
    const routeId = this.value;
    if (!routeId) { resetBuses('Choose a bus'); return; }

    resetBuses('Loading buses…');
    busSelect.disabled = true;

    try {
      const res  = await fetch('/feedback?route_id=' + encodeURIComponent(routeId));
      const data = await res.json();

      resetBuses('Any bus (optional)');

      if (Array.isArray(data) && data.length > 0) {
        data.forEach(bus => {
          const regNo  = bus.bus_reg_no || '';
          const opType = bus.operator_type || '';
          if (!regNo) return;
          busTypeMap[regNo] = opType;
          const opt = document.createElement('option');
          opt.value       = regNo;           // <-- was bus.bus_id (wrong field, caused null in DB)
          opt.textContent = regNo + (opType ? ' (' + opType + ')' : '');
          busSelect.appendChild(opt);
        });
      } else {
        resetBuses('No buses found for this route');
      }
    } catch (err) {
      console.error('[NexBus] Bus fetch failed:', err);
      resetBuses('Could not load buses');
    }

    busSelect.disabled = false;
  });

  // Auto-tick Bus Type radio when a bus is chosen
  busSelect.addEventListener('change', function () {
    const regNo  = this.value;
    const opType = regNo ? (busTypeMap[regNo] || '') : '';
    if (!opType) return;
    const radioVal = opType.toUpperCase() === 'SLTB' ? 'SLTB' : 'Private';
    const radio = document.querySelector('input[name="bus_type"][value="' + radioVal + '"]');
    if (radio) radio.checked = true;
  });

  // Guarantee busSelect is NEVER disabled when the form submits
  // (disabled controls are excluded from POST data)
  const feedbackForm = busSelect.closest('form');
  if (feedbackForm) {
    feedbackForm.addEventListener('submit', () => {
      busSelect.disabled = false;
    });
  }
});
