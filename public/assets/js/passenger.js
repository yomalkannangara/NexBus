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


document.addEventListener('DOMContentLoaded', ()=>{
  const routeSelect = document.querySelector('select[name="route_id"]');
  const busSelect   = document.getElementById('busSelect');

  if (!routeSelect || !busSelect) return;

  routeSelect.addEventListener('change', async function(){
    const routeId = this.value;
    busSelect.innerHTML = '<option value="">Loading...</option>';
    if (!routeId) {
      busSelect.innerHTML = '<option value="">Choose a bus</option>';
      return;
    }

    try {
      const res = await fetch(`/feedback?route_id=${routeId}`);
      const data = await res.json();

      if (Array.isArray(data) && data.length > 0) {
        busSelect.innerHTML = '<option value="">Choose a bus</option>';
        data.forEach(bus=>{
          const opt = document.createElement('option');
          opt.value = bus.bus_id;
          opt.textContent = `${bus.bus_reg_no} (${bus.operator_type})`;
          busSelect.appendChild(opt);
        });
      } else {
        busSelect.innerHTML = '<option value="">No buses found</option>';
      }
    } catch (err) {
      console.error('Bus fetch failed:', err);
      busSelect.innerHTML = '<option value="">Error loading buses</option>';
    }
  });
});


