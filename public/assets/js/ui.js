(function(){
  // util
  function once(el, key){
    if (!el) return false;
    if (el.dataset[key]) return false;
    el.dataset[key] = '1';
    return true;
  }

  // Modal generic initializer by id
  function initModal(id, openSelectors, closeSelectors){
    const modal = document.getElementById(id);
    if (!modal) return;
    if (!once(modal, 'init')) return;
    function open(){ modal.classList.remove('hidden'); }
    function close(){ modal.classList.add('hidden'); }
    (openSelectors||[]).forEach(sel=>{
      document.querySelectorAll(sel).forEach(btn=> btn.addEventListener('click', open));
    });
    (closeSelectors||[]).forEach(sel=>{
      document.querySelectorAll(sel).forEach(btn=> btn.addEventListener('click', close));
    });
    modal.addEventListener('click', (e)=>{ if (e.target===modal) close(); });
    return { open, close };
  }

  function initHoursModal(){
    return initModal('hours-modal', ['#btn-hours', '#btn-hours-ico'], ['#hours-close']);
  }

  function initLoginModal(){
    const modal = document.getElementById('login-modal');
    if (!modal) return null;
    if (!once(modal, 'init')) return null;
    const redirectInput = modal.querySelector('input[name="redirect_to"]');
    function open(){ if (redirectInput) redirectInput.value = window.location.pathname + window.location.search; modal.classList.remove('hidden'); }
    function close(){ modal.classList.add('hidden'); }
    document.querySelectorAll('#btn-open-login').forEach(btn=> btn.addEventListener('click', open));
    document.querySelectorAll('#login-close').forEach(btn=> btn.addEventListener('click', close));
    modal.addEventListener('click', (e)=>{ if (e.target===modal) close(); });
    return { open, close };
  }

  function initCategoryTabs(){
    const tabs = Array.from(document.querySelectorAll('.category-tab'));
    if (!tabs.length) return;
    if (!once(tabs[0].closest('div') || tabs[0], 'tabs')) {
      // already initialized (approx)
    }

    function activate(tab){
      if (!tab) return;
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
    }
    tabs.forEach(t => t.addEventListener('click', () => activate(t)));

    function onScroll(){
      let chosen = tabs[0];
      const offset = 80;
      tabs.forEach(t => {
        const id = (t.getAttribute('href')||'').slice(1);
        const anchor = document.getElementById(id);
        const target = anchor?.nextElementSibling || anchor;
        if (target && target.getBoundingClientRect().top - offset <= 0) {
          chosen = t;
        }
      });
      activate(chosen);
    }

    const initial = document.querySelector('.category-tab.active') || tabs[0];
    activate(initial);
    window.addEventListener('scroll', onScroll);
    onScroll();
  }

  function debounce(fn, wait){ let t; return function(...args){ clearTimeout(t); t = setTimeout(()=> fn.apply(this,args), wait); }; }

  function initSearch(){
    const form = document.querySelector('form[data-search-url]');
    if (!form) return;
    if (!once(form, 'search')) return;
    const input = form.querySelector('input[name="q"]');
    const results = document.getElementById('search-results');
    const url = form.dataset.searchUrl;
    if (!input || !results || !url) return;
    const doSearch = async ()=>{
      const term = input.value.trim();
      if (term === '') { results.innerHTML = ''; return; }
      try {
        const res  = await fetch(url + '?q=' + encodeURIComponent(term), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const html = await res.text();
        results.innerHTML = html;
      } catch (e) {
        console.error(e);
      }
    };
    input.addEventListener('input', debounce(doSearch, 300));
  }

  // Auto-init on DOM ready
  document.addEventListener('DOMContentLoaded', function(){
    const hours = initHoursModal();
    const login = initLoginModal();
    initCategoryTabs();
    initSearch();
    // Intercept footer sacola/perfil clicks to open login modal when not logged
    try {
      if (login && typeof login.open === 'function') {
        document.querySelectorAll('nav a').forEach(a => {
          const href = a.getAttribute('href') || '';
          // pattern match for cart or profile routes
          if (/\/cart$/.test(href) || /\/profile$/.test(href)) {
            a.addEventListener('click', function(e){
              // if customer not logged, open login modal and prevent navigation
              if (!window.__IS_CUSTOMER) {
                e.preventDefault();
                login.open();
              }
            });
          }
        });
      }
    } catch(e) { console.error(e); }
    // Delegated handlers for data-action
    document.body.addEventListener('click', function(e){
      const btn = e.target.closest('[data-action]');
      if (!btn) return;
      const action = btn.dataset.action;
      if (action === 'navigate'){
        const href = btn.dataset.href;
        if (href) window.location.href = href;
      } else if (action === 'confirm-navigate'){
        const msg = btn.dataset.message || 'Tem certeza?';
        const href = btn.dataset.href;
        if (confirm(msg) && href) window.location.href = href;
      } else if (action === 'print'){
        window.print();
      } else if (action === 'copy'){
        const target = btn.dataset.target;
        if (!target) return;
        const el = document.querySelector(target);
        if (!el) return;
        const text = el.innerText || el.value || el.textContent || '';
        navigator.clipboard?.writeText(text).then(()=>{
          // optional visual feedback
        }).catch(()=>{
          // fallback
          const tmp = document.createElement('textarea'); tmp.value = text; document.body.appendChild(tmp); tmp.select(); document.execCommand('copy'); tmp.remove();
        });
      }
    });
    try {
      if (window.__FORCE_LOGIN && login && typeof login.open === 'function') login.open();
    } catch(e){}
  });

})();
