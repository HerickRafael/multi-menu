// Enhanced UI
(function(){
  'use strict';
  
  function once(el, key){
    if (!el) return false;
    if (el.dataset[key]) return false;
    el.dataset[key] = '1';
    return true;
  }

  // Enhanced Search with Skeleton
  function initEnhancedSearch(){
    const form = document.querySelector('form[data-search-url]');
    if (!form || !once(form, 'search')) return;
    
    const input = form.querySelector('input[name="q"]');
    const results = document.getElementById('search-results');
    const url = form.dataset.searchUrl;
    
    if (!input || !results || !url) return;
    
    let searchTimeout;
    
    function showSearchSkeleton(){
      results.innerHTML = '<div class="mb-4"><div class="h-6 bg-gray-200 rounded w-48 mb-3 animate-pulse"></div><div class="grid gap-3"><div class="bg-white border rounded-2xl p-4 flex gap-3 animate-pulse"><div class="w-24 h-24 bg-gray-200 rounded-xl"></div><div class="flex-1"><div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div><div class="h-3 bg-gray-200 rounded w-1/2 mb-2"></div></div></div></div></div>';
    }
    
    function doSearch(){
      const term = input.value.trim();
      
      if (term === '') { 
        results.innerHTML = ''; 
        return; 
      }
      
      showSearchSkeleton();
      
      fetch(url + '?q=' + encodeURIComponent(term), { 
        headers: { 'X-Requested-With': 'XMLHttpRequest' } 
      })
      .then(function(res){ return res.text(); })
      .then(function(html){
        setTimeout(function(){
          results.innerHTML = html;
          initLazyLoading();
        }, 300);
      })
      .catch(function(e){
        console.error('Search error:', e);
        results.innerHTML = '<div class="p-4 text-red-600">Erro ao buscar produtos. Tente novamente.</div>';
      });
    }
    
    input.addEventListener('input', function(){
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(doSearch, 400);
    });
  }

  // Modal Functions
  function initModal(id, openSelectors, closeSelectors){
    const modal = document.getElementById(id);
    if (!modal || !once(modal, 'init')) return;
    
    function open(){ modal.classList.remove('hidden'); }
    function close(){ modal.classList.add('hidden'); }
    
    (openSelectors||[]).forEach(function(sel){
      document.querySelectorAll(sel).forEach(function(btn){ 
        btn.addEventListener('click', open); 
      });
    });
    
    (closeSelectors||[]).forEach(function(sel){
      document.querySelectorAll(sel).forEach(function(btn){ 
        btn.addEventListener('click', close); 
      });
    });
    
    modal.addEventListener('click', function(e){ 
      if (e.target===modal) close(); 
    });
    
    return { open: open, close: close };
  }

  function initHoursModal(){
    return initModal('hours-modal', ['#btn-hours', '#btn-hours-ico'], ['#hours-close']);
  }

  function initLoginModal(){
    const modal = document.getElementById('login-modal');
    if (!modal || !once(modal, 'init')) return null;
    
    const redirectInput = modal.querySelector('input[name="redirect_to"]');
    
    function open(){ 
      if (redirectInput) redirectInput.value = window.location.pathname + window.location.search; 
      modal.classList.remove('hidden'); 
    }
    function close(){ modal.classList.add('hidden'); }
    
    document.querySelectorAll('#btn-open-login').forEach(function(btn){ 
      btn.addEventListener('click', open); 
    });
    
    document.querySelectorAll('#login-close').forEach(function(btn){ 
      btn.addEventListener('click', close); 
    });
    
    modal.addEventListener('click', function(e){ 
      if (e.target===modal) close(); 
    });
    
    return { open: open, close: close };
  }

  function initCategoryTabs(){
    const tabs = Array.from(document.querySelectorAll('.category-tab'));
    if (!tabs.length || !once(tabs[0].closest('div') || tabs[0], 'tabs')) return;

    function activate(tab){
      if (!tab) return;
      tabs.forEach(function(t){ t.classList.remove('active'); });
      tab.classList.add('active');
    }
    
    tabs.forEach(function(t){ 
      t.addEventListener('click', function(){ activate(t); }); 
    });

    function onScroll(){
      let chosen = tabs[0];
      const offset = 80;
      tabs.forEach(function(t){
        const id = (t.getAttribute('href')||'').slice(1);
        const anchor = document.getElementById(id);
        const target = anchor && anchor.nextElementSibling || anchor;
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

  // Initialize everything
  function init(){
    document.body.classList.add('js-loading');
    
    initHoursModal();
    initLoginModal();
    initCategoryTabs();
    initEnhancedSearch();
    
    // Copy functionality
    document.querySelectorAll('[data-action="copy"]').forEach(function(el){
      el.addEventListener('click', function(){
        const target = el.dataset.target;
        const copyEl = target ? document.querySelector(target) : el;
        if (!copyEl) return;
        
        const text = copyEl.innerText || copyEl.value || copyEl.textContent || '';
        
        if (navigator.clipboard) {
          navigator.clipboard.writeText(text);
        } else {
          const tmp = document.createElement('textarea'); 
          tmp.value = text; 
          document.body.appendChild(tmp); 
          tmp.select(); 
          document.execCommand('copy'); 
          tmp.remove();
        }
      });
    });
    
    window.addEventListener('load', function(){
      setTimeout(function(){
        document.body.classList.remove('js-loading');
        document.body.classList.add('js-loaded');
      }, 100);
    });
    
    // Force login if needed
    try {
      const login = initLoginModal();
      if (window.__FORCE_LOGIN && login && typeof login.open === 'function') {
        login.open();
      }
    } catch(e){}
  }

  // Run when ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();