<!doctype html>
<html lang="pt-br">
<?php
$companyData = is_array($company ?? null) ? $company : null;
$activeSlugValue = $activeSlug ?? null;
$companySlug = $activeSlugValue ?? ($companyData['slug'] ?? null);
$adminPrimaryColor = admin_theme_primary_color($companyData);
$adminPrimarySoft = hex_to_rgba($adminPrimaryColor, 0.55, $adminPrimaryColor);
$adminPrimaryGradient = admin_theme_gradient($companyData);
$kdsDataUrl = $companySlug ? base_url('admin/' . rawurlencode($companySlug) . '/kds/data') : null;
$orderDetailBaseUrl = $companySlug ? base_url('admin/' . rawurlencode($companySlug) . '/orders/show?id=') : null;
$kdsPageUrl = $companySlug ? base_url('admin/' . rawurlencode($companySlug) . '/kds') : null;
$bellConfig = (string)(config('kds_bell_url') ?? '');
$resolvedBellUrl = '';
if ($bellConfig !== '') {
    if (preg_match('/^(data:|https?:\\/\\/|\\/\\/)/i', $bellConfig)) {
        $resolvedBellUrl = $bellConfig;
    } else {
        $resolvedBellUrl = base_url(ltrim($bellConfig, '/'));
    }
}

// Helper function para renderizar status pills
if (!function_exists('status_pill')) {
    function status_pill($status, $text = null, $showDot = true) {
        $statusMap = [
            // Evolution / Conexão
            'open' => 'connected',
            'connecting' => 'connecting', 
            'disconnected' => 'disconnected',
            'close' => 'disconnected',
            
            // Pedidos
            'concluido' => 'connected',
            'concluded' => 'connected', 
            'cancelado' => 'disconnected',
            'cancelled' => 'disconnected',
            'pendente' => 'pending',
            'pending' => 'pending',
            'preparando' => 'connecting', 
            'preparing' => 'connecting',
            'erro' => 'error',
            'error' => 'error',
            'failed' => 'error'
        ];
        
        $statusClass = $statusMap[strtolower($status)] ?? 'pending';
        $displayText = $text ?? ucfirst($status);
        $dot = $showDot ? '<span class="status-dot"></span>' : '';
        
        return '<span class="status-pill status-' . $statusClass . '">' . $dot . htmlspecialchars($displayText) . '</span>';
    }
}
?>
<head>
  <meta charset="utf-8">
  <title><?= e($title ?? 'Admin') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root {
      --admin-primary-color: <?= e($adminPrimaryColor) ?>;
      --admin-primary-soft: <?= e($adminPrimarySoft) ?>;
      --admin-primary-gradient: <?= e($adminPrimaryGradient) ?>;
    }
    .admin-gradient-bg { background-image: var(--admin-primary-gradient); background-color: var(--admin-primary-color); }
    .admin-gradient-text { background-image: var(--admin-primary-gradient); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .admin-primary-text { color: var(--admin-primary-color); }
    .admin-primary-underline { text-decoration-color: var(--admin-primary-color); }
    .admin-primary-bg { background-color: var(--admin-primary-color); }
    .admin-primary-soft-bg { background-color: var(--admin-primary-soft); }
    .admin-primary-soft-badge { background-color: var(--admin-primary-soft); color: var(--admin-primary-color); }
    .admin-primary-border { border-color: var(--admin-primary-color); }
    .admin-print-only { display: none; }
    /* Toast container style kept here so the element behaves visually when present */
    .admin-order-toasts { position: fixed; top: 1rem; right: 1rem; display: flex; flex-direction: column; gap: 0.75rem; z-index: 9999; pointer-events: none; }
    .admin-order-toast { pointer-events: auto; min-width: 260px; max-width: 320px; background: #fff; color: #0f172a; border-radius: 1rem; box-shadow: 0 22px 45px -24px rgba(15,23,42,0.55); padding: 1rem 1.2rem; border: 1px solid rgba(15,23,42,0.08); display: flex; flex-direction: column; gap: 0.55rem; opacity: 0; transform: translateY(-8px); transition: opacity 0.18s ease, transform 0.18s ease; }
    .admin-order-toast.show { opacity: 1; transform: translateY(0); }
    .admin-order-toast h3 { margin: 0; font-size: 1rem; color: #0f172a; font-weight: 700; }
    .admin-order-toast p { margin: 0; font-size: 0.85rem; line-height: 1.35; color: #475569; }
    .admin-order-toast .toast-icon { width:36px; height:36px; flex-shrink:0; border-radius:.6rem; display:grid; place-items:center; color:#fff; font-weight:700; }
    .admin-order-toast.success { background: linear-gradient(90deg,#059669,#10b981); color:#fff; border: none; }
    .admin-order-toast.error { background: linear-gradient(90deg,#ef4444,#f97316); color:#fff; border: none; }
    .admin-order-toast.info { background: #0f172a; color:#fff; border: none; }
    .admin-order-toast .toast-close { position: absolute; right: 0.6rem; top: 0.6rem; background:transparent;border:none;color:inherit;font-size:14px;cursor:pointer; }
    .admin-order-toast-footer { display: flex; justify-content: flex-end; align-items: center; gap: 0.45rem; }
    .admin-order-toast-actions { display: flex; align-items: center; gap: 0.45rem; }
    .admin-order-toast-btn { display: inline-flex; align-items: center; justify-content: center; font-size: 0.74rem; font-weight: 600; border-radius: 0.75rem; padding: 0.45rem 0.9rem; text-decoration: none; transition: background 0.15s ease, color 0.15s ease; border: none; cursor: pointer; }
    .admin-order-toast-btn.primary { background: var(--admin-primary-gradient); color: #fff; }
    .admin-order-toast-btn.secondary { background: rgba(226,232,240,0.6); color: #1f2937; }
    
    /* Status System - Reutilizável para toda aplicação */
    .status-pill { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 500; border-radius: 9999px; padding: 0.375rem 0.75rem; }
    .status-dot { width: 0.375rem; height: 0.375rem; border-radius: 50%; flex-shrink: 0; }
    
    /* Status: Conectado / Concluído / Ativo */
    .status-connected { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .status-connected .status-dot { background-color: #16a34a; }
    
    /* Status: Desconectado / Cancelado / Inativo */
    .status-disconnected { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .status-disconnected .status-dot { background-color: #dc2626; }
    
    /* Status: Pendente / Aguardando / Em Processo */
    .status-pending { background-color: #fffbeb; color: #92400e; border: 1px solid #fed7aa; }
    .status-pending .status-dot { background-color: #f59e0b; }
    
    /* Status: Conectando / Preparando / Em Progresso */
    .status-connecting { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .status-connecting .status-dot { background-color: #3b82f6; }
    
    /* Status: Erro / Falha */
    .status-error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
    .status-error .status-dot { background-color: #ef4444; }
  </style>
</head>
<body class="bg-slate-50 text-slate-900"
      data-company-slug="<?= e($companySlug ?? '') ?>"
      data-kds-url="<?= e($kdsDataUrl ?? '') ?>"
      data-order-url="<?= e($orderDetailBaseUrl ?? '') ?>"
      data-kds-link="<?= e($kdsPageUrl ?? '') ?>"
      data-bell-url="<?= e($resolvedBellUrl) ?>">
  <div class="max-w-7xl mx-auto p-4">
    <?= $content ?? '' ?>
  </div>

  <!-- JavaScript comum do admin -->
  <script src="<?= base_url('public/assets/js/admin-common.js') ?>"></script>

  <?php if (!isset($_SERVER['REQUEST_URI']) || !preg_match('/\/kds(\/|$)/', $_SERVER['REQUEST_URI'])): ?>
    <div class="admin-order-toasts" id="admin-order-toasts" aria-live="polite"></div>

    <script>
    (function(){
      // Polling + toast + chime logic used across admin pages (excluded on KDS views)
      const dataUrl = document.body.dataset.kdsUrl;
      const orderUrlBase = document.body.dataset.orderUrl || '';
      const kdsLink = document.body.dataset.kdsLink || '';
      const bellConfig = (document.body.dataset.bellUrl || '').trim();
      if (!dataUrl) return;

      const POLL_INTERVAL = 6000;
      const DEFAULT_BELL_URI = 'data:audio/wav;base64,UklGRjQrAABXQVZFZm10IBAAAAABAAEAIlYAAESsAAACABAAZGF0YRArAAAA...';

      const ACTIVITY_TIMEOUT = 15000;
      let lastUserActivity = 0;
      let isFetching = false;
      let syncToken = null;
      let initialized = false;
      let knownPending = new Set();

      const toastContainer = document.getElementById('admin-order-toasts');

      const prepareUri = (value) => {
        if (!value) return '';
        const raw = String(value).trim();
        if (!raw) return '';
        if (/^(data:|https?:|\/\/)/i.test(raw)) return raw;
        if (raw.startsWith('/')) return window.location.origin + raw;
        try { return new URL(raw, window.location.href).toString(); } catch { return raw; }
      };

      const isPageActive = () => {
        if (document.hidden) return false;
        if (!lastUserActivity) return false;
        return (Date.now() - lastUserActivity) <= ACTIVITY_TIMEOUT;
      };

      // Lightweight chime: try AudioContext or fallback audio file
      class KdsChime {
        constructor(fallbackUri){
          this.AudioContext = window.AudioContext || window.webkitAudioContext || null;
          this.fallbackUri = prepareUri((typeof fallbackUri === 'string' && fallbackUri.trim()) ? fallbackUri.trim() : DEFAULT_BELL_URI);
          this.preferFallback = this.fallbackUri && this.fallbackUri !== DEFAULT_BELL_URI;
          this.context = null; this.unlocked = false; this.pendingRing = false; this.pendingMode = null; this.lastPlayedAt = 0; this.minimumGapMs = 450; this.loopInterval = null; this.shortTimeout = null; this.isAlarmRunning = false; this.audioEl = null; this.audioFailed = false;
        }
        isActivated(){ return this.unlocked; }
        activate(){ if (this.unlocked) return; this.unlocked = true; this.ensureContext(); this.warmFallbackAudio(); if (this.pendingRing) { const mode = this.pendingMode || 'loop'; this.pendingRing = false; this.pendingMode = null; if (mode === 'short') this.playShortAlert(); else this.startPersistentAlert(); } }
        ring(mode){ const chosenMode = mode || (isPageActive() ? 'short' : 'loop'); if (!this.unlocked) { this.pendingRing = true; this.pendingMode = chosenMode; return; } const now = Date.now(); if (this.isAlarmRunning && (now - this.lastPlayedAt) < this.minimumGapMs) return; if (chosenMode === 'short') this.playShortAlert(); else this.startPersistentAlert(); }
        playShortAlert(){ this.stopAlarm(false); this.pendingRing = false; this.pendingMode = null; this.isAlarmRunning = true; this.playOnce(); this.shortTimeout = setTimeout(() => { this.playOnce(); this.shortTimeout = setTimeout(() => this.stopAlarm(), 900); }, 800); }
        startPersistentAlert(){ this.stopAlarm(false); this.pendingRing = false; this.pendingMode = null; this.isAlarmRunning = true; const playNow = () => this.playOnce(); playNow(); this.loopInterval = setInterval(playNow, 4000); }
        handleUserActivity(){ if (!this.unlocked) return; if (this.isAlarmRunning) this.stopAlarm(); }
        stopAlarm(resetPending = true){ if (this.loopInterval) { clearInterval(this.loopInterval); this.loopInterval = null; } if (this.shortTimeout) { clearTimeout(this.shortTimeout); this.shortTimeout = null; } this.isAlarmRunning = false; if (resetPending) { this.pendingRing = false; this.pendingMode = null; } }
        playOnce(){ let played = false; const markPlayed = () => { this.lastPlayedAt = Date.now(); }; if (this.preferFallback && !this.audioFailed) { played = this.playFallback(markPlayed); if (!played && this.AudioContext) { this.ensureContext(); if (this.context && this.playWithContext()) { markPlayed(); played = true; } } } else { this.ensureContext(); if (this.context && this.playWithContext()) { markPlayed(); played = true; } if (!played) { played = this.playFallback(markPlayed); } } if (played) this.pendingRing = false; return played; }
        ensureContext(){ if (this.context || !this.AudioContext) return; try { this.context = new this.AudioContext(); if (this.context && this.context.state === 'suspended') this.context.resume().catch(()=>{}); } catch (err) { this.context = null; this.AudioContext = null; } }
        playWithContext(){ if (!this.context) return false; try { const ctx = this.context; if (ctx.state === 'suspended') ctx.resume().catch(()=>{}); const now = ctx.currentTime; const osc = ctx.createOscillator(); const gain = ctx.createGain(); osc.type = 'triangle'; osc.frequency.setValueAtTime(880, now); gain.gain.setValueAtTime(0.0001, now); gain.gain.exponentialRampToValueAtTime(0.32, now + 0.02); gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.7); osc.connect(gain); gain.connect(ctx.destination); osc.start(now); osc.stop(now + 0.72); return true; } catch (err) { this.context = null; return false; } }
        playFallback(onSuccess){ const fallbackToTone = () => { this.audioFailed = true; this.preferFallback = false; const ok = this.fallbackToTone(); if (ok && typeof onSuccess === 'function') onSuccess(); return ok; }; if (!this.fallbackUri) return fallbackToTone(); try { if (!this.audioEl) { this.audioEl = new Audio(); this.audioEl.preload = 'auto'; this.audioEl.src = this.fallbackUri; this.audioEl.volume = 0.8; } this.audioEl.muted = false; this.audioEl.onerror = () => { if (!fallbackToTone()) this.pendingRing = true; }; try { this.audioEl.currentTime = 0; } catch {} const p = this.audioEl.play(); if (p && typeof p.then === 'function') { p.then(() => { this.audioFailed = false; if (typeof onSuccess === 'function') onSuccess(); }).catch(() => { if (!fallbackToTone()) this.pendingRing = true; }); return true; } this.audioFailed = false; if (typeof onSuccess === 'function') onSuccess(); return true; } catch (err) { return fallbackToTone(); } }
        warmFallbackAudio(){ if (!this.fallbackUri) return; try { if (!this.audioEl) { this.audioEl = new Audio(); this.audioEl.preload = 'auto'; this.audioEl.src = this.fallbackUri; this.audioEl.volume = 0.8; } const el = this.audioEl; const prevMuted = el.muted; el.muted = true; const reset = () => { try { el.pause(); el.currentTime = 0; } catch {} el.muted = prevMuted; }; const playPromise = el.play(); if (playPromise && typeof playPromise.then === 'function') playPromise.then(()=>reset()).catch(()=>reset()); else reset(); } catch {} }
        fallbackToTone(){ if (!this.AudioContext) return false; if (this.audioEl) try { this.audioEl.pause(); } catch {} this.audioEl = null; this.ensureContext(); if (!this.context) return false; return this.playWithContext(); }
        dispose(){ this.stopAlarm(); if (this.context && typeof this.context.close === 'function') try { this.context.close(); } catch {} this.context = null; if (this.audioEl) try { this.audioEl.pause(); this.audioEl.currentTime = 0; } catch {} this.audioEl = null; }
      }

      const chime = new KdsChime(prepareUri(bellConfig) || DEFAULT_BELL_URI);
      const ensureChimeActivated = () => { if (chime.isActivated()) return; chime.activate(); };
      ensureChimeActivated(); lastUserActivity = Date.now();
      const trackActivity = () => { ensureChimeActivated(); lastUserActivity = Date.now(); chime.handleUserActivity(); };
      ['pointerdown','touchstart','keydown','wheel','touchmove'].forEach(evt => document.addEventListener(evt, trackActivity, {passive: true}));
      window.addEventListener('scroll', trackActivity, {passive: true});

      const formatCurrency = (value) => { try { return new Intl.NumberFormat('pt-BR', {style: 'currency', currency: 'BRL'}).format(Number(value || 0)); } catch (err) { const num = Number(value || 0).toFixed(2).replace('.', ','); return 'R$ ' + num; } };

      const showToast = (order) => {
        if (!toastContainer) return;
        const toast = document.createElement('article');
        toast.className = 'admin-order-toast';
        const total = formatCurrency(order.total || order.subtotal || 0);
        const name = order.customer_name || 'Cliente';
        const phone = order.customer_phone || '';
        const orderLink = orderUrlBase ? orderUrlBase + encodeURIComponent(order.id) : '';
        toast.innerHTML = `
          <h3>Novo pedido #${order.id}</h3>
          <p>${name}${phone ? ' · ' + phone : ''}</p>
          <div class="admin-order-toast-footer">
            <span class="text-sm font-semibold text-slate-500">${total}</span>
            <div class="admin-order-toast-actions">
              ${orderLink ? `<a class="admin-order-toast-btn secondary" href="${orderLink}" target="_blank" rel="noopener">Ver pedido</a>` : ''}
              ${kdsLink ? `<a class="admin-order-toast-btn primary" href="${kdsLink}" target="_blank" rel="noopener">Abrir KDS</a>` : ''}
              <button type="button" class="admin-order-toast-btn secondary" data-dismiss>Fechar</button>
            </div>
          </div>
        `;
        toast.querySelectorAll('[data-dismiss]').forEach(btn => btn.addEventListener('click', () => { chime.stopAlarm(); toast.remove(); }));
        toastContainer.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 200); }, 15000);
      };

      const orderKey = (value) => { const num = Number(value); return Number.isFinite(num) ? Math.trunc(num) : 0; };

      const collectPending = (orders) => { const set = new Set(); orders.forEach(order => { if (!order) return; const status = String(order.status || '').toLowerCase(); if (status !== 'pending') return; const id = orderKey(order.id || order.order_id); if (id > 0) set.add(id); }); return set; };

      const processData = (data) => {
        const orders = Array.isArray(data.orders) ? data.orders : [];
        const pendingSet = collectPending(orders);
        if (!initialized) { knownPending = pendingSet; initialized = true; return; }
        const newOrders = [];
        orders.forEach(order => { if (!order) return; const status = String(order.status || '').toLowerCase(); if (status !== 'pending') return; const id = orderKey(order.id || order.order_id); if (id > 0 && !knownPending.has(id)) newOrders.push(order); });
        knownPending = pendingSet;
        if (!newOrders.length) return;
        newOrders.forEach(order => showToast(order));
        chime.ring();
      };

      const fetchData = () => {
        if (isFetching) return;
        isFetching = true;
        let url = dataUrl;
        if (syncToken) url += (url.includes('?') ? '&' : '?') + 'since=' + encodeURIComponent(syncToken);
        fetch(url, {credentials: 'include'})
          .then(res => res.ok ? res.json() : Promise.reject())
          .then(data => { syncToken = data.sync_token || data.server_time || syncToken; processData(data); })
          .catch(() => {})
          .finally(() => { isFetching = false; });
      };

      fetchData();
      setInterval(fetchData, POLL_INTERVAL);
      window.addEventListener('beforeunload', () => chime.dispose());
    })();
    </script>
  <?php endif; ?>

  <script src="<?= base_url('assets/js/admin.js') ?>"></script>
</body>
</html>
