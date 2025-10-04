<?php
// admin/kds/index.php — Kitchen Display System (SSE + polling fallback)

$title = 'KDS - ' . ($company['name'] ?? 'Empresa');
$slug  = rawurlencode((string)($activeSlug ?? ($company['slug'] ?? '')));

if (!function_exists('e')) {
    function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$initialSnapshot = is_array($initialSnapshot ?? null) ? $initialSnapshot : [];
$kdsConfig       = is_array($kdsConfig ?? null) ? $kdsConfig : [];
$hasCanceled     = !empty($hasCanceled);

$initialJson = json_encode($initialSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$configJson  = json_encode($kdsConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<style>
  .kds-columns { display: grid; gap: 1.25rem; }
  @media (min-width: 768px) { .kds-columns { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  @media (min-width: 1024px) { .kds-columns { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
  .kds-column { display:flex; flex-direction:column; gap:1rem; border-radius:1.5rem; padding:1.25rem; border-width:1px; border-style:solid; background:linear-gradient(135deg, rgba(255,255,255,0.9), rgba(248,250,252,0.9)); box-shadow:0 10px 25px -20px rgba(15,23,42,0.5); min-height:320px; }
  .kds-column-header { display:flex; justify-content:space-between; align-items:center; gap:0.75rem; }
  .kds-column-header h2 { font-size:0.95rem; margin:0; font-weight:600; color:#0f172a; text-transform:uppercase; letter-spacing:.08em; }
  .kds-column-count { display:inline-flex; align-items:center; gap:0.35rem; padding:0.25rem 0.65rem; border-radius:999px; font-size:0.7rem; font-weight:600; background:#0f172a; color:#fff; }
  .kds-list { display:flex; flex-direction:column; gap:0.75rem; overflow-y:auto; padding-right:0.4rem; max-height:70vh; }
  .kds-card { border-radius:1.25rem; border:1px solid rgba(226,232,240,0.8); background:rgba(255,255,255,0.96); padding:1rem; box-shadow:0 12px 30px -24px rgba(15,23,42,0.65); display:grid; gap:0.75rem; position:relative; }
  .kds-card.kds-alert-warning { border-color:#f97316; box-shadow:0 0 0 2px rgba(249,115,22,0.15); }
  .kds-card.kds-alert-danger { border-color:#ef4444; box-shadow:0 0 0 2px rgba(239,68,68,0.15); animation: pulse-danger 1.2s ease-in-out infinite; }
  @keyframes pulse-danger { 0% { box-shadow:0 0 0 2px rgba(239,68,68,0.15);} 50% { box-shadow:0 0 0 6px rgba(239,68,68,0.05);} 100% { box-shadow:0 0 0 2px rgba(239,68,68,0.15);} }
  .kds-card-header { display:flex; justify-content:space-between; align-items:flex-start; gap:0.5rem; }
  .kds-card-header h3 { margin:0; font-size:1rem; font-weight:700; color:#0f172a; }
  .kds-badge { border-radius:999px; background:#0f172a; color:#fff; font-size:0.65rem; padding:0.15rem 0.55rem; text-transform:uppercase; letter-spacing:.08em; }
  .kds-meta { font-size:0.75rem; color:#64748b; display:flex; flex-direction:column; gap:0.25rem; }
  .kds-meta strong { color:#0f172a; }
  .kds-items { border-radius:0.9rem; background:#f8fafc; border:1px solid rgba(226,232,240,0.7); padding:0.75rem; font-size:0.8rem; color:#334155; display:flex; flex-direction:column; gap:0.4rem; }
  .kds-items li { display:flex; justify-content:space-between; gap:0.75rem; }
  .kds-actions { display:flex; flex-wrap:wrap; gap:0.5rem; }
  .kds-btn { display:inline-flex; align-items:center; justify-content:center; gap:0.4rem; border-radius:0.85rem; font-size:0.75rem; font-weight:600; padding:0.45rem 0.9rem; border:1px solid transparent; cursor:pointer; transition:all 0.15s ease; text-decoration:none; }
  .kds-btn-primary { background:#2563eb; color:#fff; border-color:#2563eb; }
  .kds-btn-primary:hover { background:#1d4ed8; }
  .kds-btn-success { background:#059669; color:#fff; border-color:#059669; }
  .kds-btn-success:hover { background:#047857; }
  .kds-btn-ghost { border-color:#e2e8f0; background:#fff; color:#1f2937; }
  .kds-btn-ghost:hover { background:#f8fafc; }
  .kds-btn-danger { border-color:#fda4af; background:#fff1f2; color:#b91c1c; }
  .kds-btn-danger:hover { background:#fee2e2; }
  .kds-tag { font-size:0.7rem; padding:0.2rem 0.5rem; border-radius:0.65rem; display:inline-flex; align-items:center; gap:0.3rem; }
  .kds-tag.sla-now { background:#fee2e2; color:#b91c1c; }
  .kds-tag.sla-warning { background:#fef3c7; color:#92400e; }
  .kds-tag.sla-safe { background:#dcfce7; color:#166534; }
  #kds-canceled { margin-top:1.5rem; }
  .kds-empty { border:1px dashed rgba(148,163,184,0.6); border-radius:1rem; padding:1.25rem; text-align:center; font-size:0.85rem; color:#64748b; }
</style>

<?php ob_start(); ?>
<div class="mx-auto max-w-7xl p-4" id="kds-app" data-slug="<?= e($slug) ?>">
  <header class="mb-6 flex flex-wrap items-center gap-3">
    <div class="flex items-center gap-3">
      <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl admin-gradient-bg text-white shadow">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M3 5h18M7 5v14a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <div>
        <h1 class="admin-gradient-text bg-clip-text text-2xl font-semibold text-transparent">KDS · <?= e($company['name'] ?? '') ?></h1>
        <p class="text-sm text-slate-500">Pedidos em tempo real. Mantenha esta aba aberta na cozinha.</p>
      </div>
    </div>
    <div class="ml-auto flex flex-wrap gap-2">
      <button id="kds-refresh" class="kds-btn kds-btn-ghost">Recarregar</button>
      <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>" class="kds-btn kds-btn-ghost" data-kds-nav>Voltar ao painel</a>
    </div>
  </header>

  <section class="mb-5 flex flex-wrap items-center gap-3" id="kds-range-buttons">
    <button type="button" class="kds-btn kds-btn-ghost" data-range="today">Hoje</button>
    <button type="button" class="kds-btn kds-btn-ghost" data-range="yesterday">Ontem</button>
    <button type="button" class="kds-btn kds-btn-ghost" data-range="all">Todos</button>
    <div class="relative ml-auto">
      <input type="search" id="kds-search" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" placeholder="Buscar por cliente, telefone ou #">
    </div>
  </section>

  <section class="kds-columns" id="kds-columns"></section>

  <section id="kds-canceled" class="<?= $hasCanceled ? '' : 'hidden' ?>">
    <div class="flex items-center justify-between">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Cancelados</h2>
      <button id="toggle-canceled" data-visible="0" class="kds-btn kds-btn-ghost <?= $hasCanceled ? '' : 'cursor-not-allowed text-slate-400' ?>" <?= $hasCanceled ? '' : 'disabled' ?>><?= $hasCanceled ? 'Mostrar cancelados' : 'Sem cancelados' ?></button>
    </div>
    <div id="kds-canceled-count" class="mt-2 text-xs text-slate-400"></div>
    <div id="kds-canceled-list" class="mt-3 grid gap-3 md:grid-cols-2 lg:grid-cols-3"></div>
  </section>
</div>

<script>
(function(){
  const CONFIG = <?= $configJson ?: '{}' ?>;
  const INITIAL_ORDERS = <?= $initialJson ?: '[]' ?>;
  const STATUS_FLOW = {
    pending:   { next: 'paid',       label: 'Iniciar preparo' },
    paid:      { next: 'completed',  label: 'Marcar como pronto' },
    completed: null,
    canceled:  null,
  };
  const STATUS_LABELS = {
    pending:   'Recebido',
    paid:      'Preparando',
    completed: 'Pronto',
    canceled:  'Cancelado'
  };
  const LANE_BY_STATUS = {
    pending:   'pending',
    paid:      'paid',
    completed: 'completed',
    canceled:  'canceled'
  };

  class KdsRealtime {
    constructor(config, initial){
      this.config = config || {};
      this.state = {
        orders: new Map(),
        range: 'today',
        search: '',
      };
      this.columnsEl = document.getElementById('kds-columns');
      this.columnRefs = new Map();
      this.canceledSection = document.getElementById('kds-canceled');
      this.canceledList = document.getElementById('kds-canceled-list');
      this.canceledCount = document.getElementById('kds-canceled-count');
      this.toggleCanceledBtn = document.getElementById('toggle-canceled');
      this.rangeButtons = Array.from(document.querySelectorAll('#kds-range-buttons [data-range]'));
      this.searchInput = document.getElementById('kds-search');
      this.refreshBtn = document.getElementById('kds-refresh');
      this.pollTimer = null;
      this.pollInterval = this.resolveInterval();
      this.lastSyncToken = null;
      this.isFetching = false;
      this.renderRequested = false;
      (initial || []).forEach(order => this.ingestOrder(order));
      this.updateSyncTokenFromState();
    }

    init(){
      this.renderColumnsSkeleton();
      this.scheduleRender();
      this.bindUi();
      this.startPolling();
    }

    resolveInterval(){
      const raw = Number(this.config.refreshMs || 0);
      const minInterval = 1500;
      if (!Number.isFinite(raw) || raw < minInterval) {
        return minInterval;
      }
      return raw;
    }

    bindUi(){
      this.rangeButtons.forEach(btn=>{
        btn.addEventListener('click', ()=>{
          this.rangeButtons.forEach(b=>b.classList.remove('kds-btn-primary'));
          btn.classList.add('kds-btn-primary');
          this.state.range = btn.dataset.range || 'today';
          this.scheduleRender();
          this.fetchData();
        });
      });
      if (this.rangeButtons.length) {
        this.rangeButtons[0].classList.add('kds-btn-primary');
      }
      if (this.searchInput) {
        this.searchInput.addEventListener('input', () => {
          this.state.search = this.searchInput.value.trim().toLowerCase();
          this.scheduleRender();
        });
      }
      if (this.refreshBtn) {
        this.refreshBtn.addEventListener('click', () => this.fetchData({forceFull: true}));
      }
      if (this.toggleCanceledBtn) {
        this.toggleCanceledBtn.addEventListener('click', () => {
          if (this.toggleCanceledBtn.disabled) return;
          const visible = this.toggleCanceledBtn.dataset.visible === '1';
          this.toggleCanceledBtn.dataset.visible = visible ? '0' : '1';
          this.updateCanceledVisibility(!visible);
        });
      }
    }

    startPolling(){
      this.pollInterval = this.resolveInterval();
      this.fetchData();
      if (this.pollTimer) {
        clearInterval(this.pollTimer);
      }
      this.pollTimer = setInterval(() => this.fetchData(), this.pollInterval);
    }

    fetchData(options = {}){
      if (!this.config.dataUrl) return;
      if (this.isFetching) return;
      const forceFull = options && options.forceFull === true;
      let endpoint = this.config.dataUrl;
      if (!forceFull && this.lastSyncToken) {
        const separator = endpoint.includes('?') ? '&' : '?';
        endpoint = `${endpoint}${separator}since=${encodeURIComponent(this.lastSyncToken)}`;
      }
      this.isFetching = true;
      fetch(endpoint, {credentials:'include', cache:'no-store'})
        .then(r => r.ok ? r.json() : Promise.reject(new Error('fetch_failed')))
        .then(data => {
          const orders = Array.isArray(data.orders) ? data.orders : [];
          const removedIds = Array.isArray(data.removed_ids) ? data.removed_ids : [];
          const fullRefresh = forceFull || !!data.full_refresh;
          const next = fullRefresh ? new Map() : new Map(this.state.orders);
          orders.forEach(order => {
            if (!order) return;
            const idKey = this.orderKey(order.id ?? order.order_id ?? 0);
            if (idKey <= 0) return;
            const existing = next.get(idKey) || null;
            const normalized = this.normalizeOrder(order, existing);
            if (normalized.id > 0) {
              next.set(normalized.id, normalized);
            }
          });
          removedIds.forEach(id => {
            const key = this.orderKey(id);
            if (key > 0) {
              next.delete(key);
            }
          });
          this.state.orders = next;
          const syncHint = (typeof data.sync_token === 'string' && data.sync_token.trim())
            ? data.sync_token.trim()
            : (typeof data.server_time === 'string' && data.server_time.trim() ? data.server_time.trim() : null);
          this.updateSyncTokenFromState(syncHint);
          this.scheduleRender();
        })
        .catch(()=>{})
        .finally(() => {
          this.isFetching = false;
        });
    }

    orderKey(value){
      const num = Number(value);
      return Number.isFinite(num) ? Math.trunc(num) : 0;
    }

    computeLatestToken(){
      let latest = 0;
      this.state.orders.forEach(order => {
        ['status_changed_at', 'updated_at', 'created_at'].forEach(field => {
          const value = order[field];
          if (!value) return;
          const ts = Date.parse(value);
          if (Number.isFinite(ts) && ts > latest) {
            latest = ts;
          }
        });
      });
      return latest ? new Date(latest).toISOString() : null;
    }

    updateSyncTokenFromState(token){
      let candidate = this.lastSyncToken || null;
      if (typeof token === 'string' && token.trim()) {
        candidate = token.trim();
      }
      const computed = this.computeLatestToken();
      if (computed) {
        if (!candidate) {
          candidate = computed;
        } else {
          const candidateTs = Date.parse(candidate);
          const computedTs = Date.parse(computed);
          const candidateValid = Number.isFinite(candidateTs);
          const computedValid = Number.isFinite(computedTs);
          if (!candidateValid && computedValid) {
            candidate = computed;
          } else if (candidateValid && computedValid && computedTs > candidateTs) {
            candidate = computed;
          }
        }
      }
      if (candidate) {
        this.lastSyncToken = candidate;
      }
    }

    renderColumnsSkeleton(){
      this.columnsEl.innerHTML = '';
      this.columnRefs.clear();
      const columns = Array.isArray(this.config.columns) && this.config.columns.length
        ? this.config.columns
        : [
            {id:'pending', label:'Recebidos'},
            {id:'paid', label:'Preparando'},
            {id:'completed', label:'Prontos'}
          ];
      columns.forEach(col => {
        const section = document.createElement('section');
        section.className = 'kds-column';
        section.dataset.status = col.id;
        section.innerHTML = `
          <div class="kds-column-header">
            <h2>${col.label}</h2>
            <span class="kds-column-count" data-count="0"></span>
          </div>
          <div class="kds-list" id="kds-list-${col.id}"></div>
        `;
        const listEl = section.querySelector('.kds-list');
        const countEl = section.querySelector('.kds-column-count');
        this.columnRefs.set(col.id, {section, list: listEl, count: countEl});
        this.columnsEl.appendChild(section);
      });
    }

    scheduleRender(){
      if (this.renderRequested) return;
      this.renderRequested = true;
      const run = () => {
        this.renderRequested = false;
        this.renderAll();
      };
      if (window.requestAnimationFrame) {
        window.requestAnimationFrame(run);
      } else {
        setTimeout(run, 80);
      }
    }

    ingestOrder(raw){
      if (!raw) return;
      const idKey = this.orderKey(raw.id ?? raw.order_id ?? 0);
      if (idKey <= 0) return;
      const existing = this.state.orders.get(idKey) || null;
      const normalized = this.normalizeOrder(raw, existing);
      if (normalized.id > 0) {
        this.state.orders.set(normalized.id, normalized);
      }
    }

    normalizeOrder(order, previous = null){
      const result = previous ? {...previous} : {};

      const idKey = this.orderKey(order.id ?? result.id ?? 0);
      if (idKey > 0) {
        result.id = idKey;
      } else if (previous) {
        const prevKey = this.orderKey(previous.id ?? 0);
        result.id = prevKey > 0 ? prevKey : 0;
      } else {
        result.id = 0;
      }
      result.status = order.status || result.status || 'pending';
      result.created_at = order.created_at || result.created_at || null;
      result.updated_at = order.updated_at || result.updated_at || result.created_at || null;
      result.status_changed_at = order.status_changed_at || result.status_changed_at || result.updated_at || result.created_at || null;
      result.sla_deadline = order.sla_deadline || result.sla_deadline || null;

      result.customer_name = order.customer_name ?? result.customer_name ?? '';
      result.customer_phone = order.customer_phone ?? result.customer_phone ?? '';
      result.customer_address = order.customer_address ?? result.customer_address ?? '';
      result.notes = order.notes ?? result.notes ?? '';

      const numeric = (value, fallback) => {
        if (value === undefined || value === null || value === '') {
          return Number(fallback || 0);
        }
        const num = Number(value);
        return Number.isFinite(num) ? num : Number(fallback || 0);
      };

      result.total = numeric(order.total, result.total);
      result.subtotal = numeric(order.subtotal, result.subtotal);
      result.delivery_fee = numeric(order.delivery_fee, result.delivery_fee);
      result.discount = numeric(order.discount, result.discount);

      if (Array.isArray(order.items)) {
        result.items = order.items;
      } else if (!Array.isArray(result.items)) {
        result.items = [];
      }

      return result;
    }

    renderAll(){
      const groups = {
        pending: [],
        paid: [],
        completed: [],
        canceled: []
      };
      this.state.orders.forEach(order => {
        const status = order.status || 'pending';
        const lane = LANE_BY_STATUS[status] || 'pending';
        if (!groups[lane]) groups[lane] = [];
        groups[lane].push(order);
      });
      Object.keys(groups).forEach(status => {
        groups[status].sort((a,b)=>{
          const aTime = Date.parse(a.created_at || '') || 0;
          const bTime = Date.parse(b.created_at || '') || 0;
          return aTime - bTime;
        });
      });

      const filters = {
        range: this.state.range,
        search: this.state.search,
      };

      const now = Date.now();
      const slaMs = (minutes)=> minutes * 60000;
      const warningThreshold = slaMs(Math.max(5, (this.config.slaMinutes || 20) / 3));

      const applyFilters = (order) => {
        if (filters.search) {
          const haystack = `${order.customer_name || ''} ${order.customer_phone || ''} #${order.id}`.toLowerCase();
          if (!haystack.includes(filters.search)) return false;
        }
        if (filters.range === 'today' || filters.range === 'yesterday') {
          const created = Date.parse(order.created_at || '') || 0;
          if (!created) return false;
          const date = new Date(created);
          const today = new Date(); today.setHours(0,0,0,0);
          if (filters.range === 'today') {
            const tomorrow = new Date(today); tomorrow.setDate(tomorrow.getDate()+1);
            return date >= today && date < tomorrow;
          }
          if (filters.range === 'yesterday') {
            const yesterday = new Date(today); yesterday.setDate(yesterday.getDate()-1);
            return date >= yesterday && date < today;
          }
        }
        return true;
      };

      Object.entries(groups).forEach(([status, orders]) => {
        if (status === 'canceled') return;
        const refs = this.columnRefs.get(status);
        if (!refs || !refs.list || !refs.count) return;
        const container = refs.list;
        const header = refs.count;
        const filtered = orders.filter(applyFilters);
        header.textContent = `${filtered.length} pedido${filtered.length === 1 ? '' : 's'}`;
        if (!filtered.length) {
          container.innerHTML = '<div class="kds-empty">Nenhum pedido por aqui.</div>';
          return;
        }
        container.innerHTML = filtered.map(order => this.renderCard(order, now, warningThreshold)).join('');
      });

      const canceledOrders = (groups['canceled'] || []).filter(applyFilters);
      if (this.toggleCanceledBtn) {
        this.toggleCanceledBtn.dataset.count = canceledOrders.length;
        this.toggleCanceledBtn.textContent = canceledOrders.length
          ? (this.toggleCanceledBtn.dataset.visible === '1' ? `Ocultar cancelados (${canceledOrders.length})` : `Mostrar cancelados (${canceledOrders.length})`)
          : 'Sem cancelados';
        if (canceledOrders.length === 0) {
          this.toggleCanceledBtn.classList.add('cursor-not-allowed','text-slate-400');
          this.toggleCanceledBtn.disabled = true;
        } else {
          this.toggleCanceledBtn.classList.remove('cursor-not-allowed','text-slate-400');
          this.toggleCanceledBtn.disabled = false;
        }
      }

      if (this.toggleCanceledBtn && this.toggleCanceledBtn.dataset.visible === '1' && canceledOrders.length) {
        this.canceledList.innerHTML = canceledOrders.map(order => this.renderCanceled(order)).join('');
        if (this.canceledCount) {
          this.canceledCount.textContent = `${canceledOrders.length} pedido${canceledOrders.length === 1 ? '' : 's'}`;
        }
        this.canceledSection.classList.remove('hidden');
      } else {
        this.canceledSection.classList.add('hidden');
      }
    }

    renderCard(order, now, warningThreshold){
      const createdAt = order.created_at ? new Date(order.created_at) : null;
      const createdLabel = createdAt ? createdAt.toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'}) : '--:--';
      const elapsedMs = createdAt ? now - createdAt.getTime() : 0;
      const elapsedMinutes = Math.max(0, Math.floor(elapsedMs / 60000));
      const slaDeadline = order.sla_deadline ? new Date(order.sla_deadline) : null;
      let slaClass = 'sla-safe';
      let slaLabel = 'Dentro do SLA';
      if (slaDeadline) {
        const remaining = slaDeadline.getTime() - now;
        if (remaining <= 0) {
          slaClass = 'sla-now';
          slaLabel = 'Atrasado';
        } else if (remaining < warningThreshold) {
          slaClass = 'sla-warning';
          slaLabel = 'Próximo do limite';
        }
      }
      const transition = STATUS_FLOW[order.status];
      const advanceBtn = (transition && transition.next)
        ? `<button class="kds-btn kds-btn-primary" data-action="advance" data-id="${order.id}" data-status="${transition.next}">${this.escape(transition.label)}</button>`
        : '';
      const cancelBtn = order.status !== 'canceled' && order.status !== 'completed'
        ? `<button class="kds-btn kds-btn-danger" data-action="cancel" data-id="${order.id}">Cancelar</button>`
        : '';
      const address = order.customer_address || order.address || '';
      const addressHtml = address ? `<span>Entrega: ${this.escape(address).replace(/\n/g, '<br>')}</span>` : '';
      const items = (order.items || []).map(item => `
        <li>
          <span><strong>${item.qty || item.quantity || 0}x</strong> ${this.escape(item.name ?? '')}</span>
          <span>${this.formatCurrency(item.line_total || item.total || 0)}</span>
        </li>`).join('');

      return `
        <article class="kds-card ${slaClass === 'sla-now' ? 'kds-alert-danger' : (slaClass === 'sla-warning' ? 'kds-alert-warning' : '')}" data-order="${order.id}">
          <div class="kds-card-header">
            <div>
              <h3>Pedido #${order.id}</h3>
              <div class="kds-meta">
                <span>Iniciado às <strong>${createdLabel}</strong> · ${elapsedMinutes} min atrás</span>
                ${order.customer_name ? `<span>Cliente: <strong>${this.escape(order.customer_name)}</strong></span>` : ''}
                ${order.customer_phone ? `<span>Telefone: ${this.escape(order.customer_phone)}</span>` : ''}
                ${addressHtml}
              </div>
            </div>
            <div class="text-right">
              <div class="kds-badge">${STATUS_LABELS[order.status] || order.status}</div>
              <div class="kds-tag ${slaClass}">${slaLabel}</div>
              <div class="mt-1 font-semibold text-slate-900">${this.formatCurrency(order.total)}</div>
            </div>
          </div>
          <ul class="kds-items">${items || '<li>Nenhum item registrado.</li>'}</ul>
          ${order.notes ? `<div class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl p-3 whitespace-pre-line">${this.escape(order.notes)}</div>` : ''}
          <div class="kds-actions">
            ${advanceBtn}
            ${cancelBtn}
            <a class="kds-btn kds-btn-ghost" href="${this.orderDetailUrl(order.id)}" target="_blank">Detalhes</a>
          </div>
        </article>`;
    }

    renderCanceled(order){
      const address = order.customer_address || order.address || '';
      const addressHtml = address ? `<span>Entrega: ${this.escape(address).replace(/\n/g,'<br>')}</span>` : '';
      return `
        <article class="kds-card" data-order="${order.id}">
          <div class="kds-card-header">
            <div>
              <h3>Pedido #${order.id}</h3>
              <div class="kds-meta">
                ${order.customer_name ? `<span>Cliente: <strong>${this.escape(order.customer_name)}</strong></span>` : ''}
                ${order.customer_phone ? `<span>Telefone: ${this.escape(order.customer_phone)}</span>` : ''}
                ${addressHtml}
              </div>
            </div>
            <div class="text-right">
              <div class="kds-badge" style="background:#dc2626">Cancelado</div>
              <div class="mt-1 font-semibold text-rose-600">${this.formatCurrency(order.total)}</div>
            </div>
          </div>
          <a class="kds-btn kds-btn-ghost" href="${this.orderDetailUrl(order.id)}" target="_blank">Ver detalhes</a>
        </article>`;
    }

    updateCanceledVisibility(show){
      if (!this.toggleCanceledBtn) return;
      if (show) {
        this.toggleCanceledBtn.dataset.visible = '1';
        this.scheduleRender();
      } else {
        this.toggleCanceledBtn.dataset.visible = '0';
        this.canceledSection.classList.add('hidden');
        this.toggleCanceledBtn.textContent = this.toggleCanceledBtn.dataset.count > 0 ? `Mostrar cancelados (${this.toggleCanceledBtn.dataset.count})` : 'Sem cancelados';
      }
    }

    orderDetailUrl(id){
      if (this.config.orderDetailBase) {
        return this.config.orderDetailBase + id;
      }
      return `${window.location.origin}${window.location.pathname.replace(/\/kds.*/, '')}/orders/show?id=${id}`;
    }

    formatCurrency(value){
      try {
        return new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(value || 0);
      } catch {
        return 'R$ ' + Number(value || 0).toFixed(2).replace('.', ',');
      }
    }

    escape(value){
      if (value === undefined || value === null) return '';
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    handleAction(target){
      const action = target.dataset.action;
      const orderId = parseInt(target.dataset.id, 10);
      if (!action || !orderId) return;
      if (action === 'advance') {
        const status = target.dataset.status;
        this.updateStatus(orderId, status);
      }
      if (action === 'cancel') {
        if (!confirm('Cancelar este pedido?')) return;
        this.updateStatus(orderId, 'canceled');
      }
    }

    updateStatus(orderId, status){
      if (!this.config.statusUrl) return;
      fetch(this.config.statusUrl, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        credentials: 'include',
        body: JSON.stringify({order_id: orderId, status})
      }).then(r=>r.ok?r.json():Promise.reject())
        .then(resp => {
          if (resp && resp.order) {
            this.ingestOrder(resp.order);
            this.updateSyncTokenFromState();
            this.scheduleRender();
          }
        }).catch(()=>{
          alert('Não foi possível atualizar o status.');
        });
    }

    cleanup(){
      if (this.pollTimer) {
        clearInterval(this.pollTimer);
        this.pollTimer = null;
      }
    }
  }

  document.addEventListener('click', (evt) => {
    const btn = evt.target.closest('[data-action]');
    if (!btn) return;
    if (!window.__kdsInstance) return;
    evt.preventDefault();
    window.__kdsInstance.handleAction(btn);
  });

  window.addEventListener('DOMContentLoaded', () => {
    window.__kdsInstance = new KdsRealtime(CONFIG || {}, INITIAL_ORDERS || []);
    window.__kdsInstance.init();
    document.querySelectorAll('[data-kds-nav]').forEach(link => {
      if (link.dataset.cleanupBound) return;
      link.dataset.cleanupBound = '1';
      link.addEventListener('click', (evt) => {
        const href = link.getAttribute('href');
        if (!href) return;
        evt.preventDefault();
        window.__kdsInstance?.cleanup();
        setTimeout(() => { window.location.href = href; }, 30);
      });
    });
  });

  window.addEventListener('beforeunload', () => {
    window.__kdsInstance?.cleanup();
  });

  window.addEventListener('pagehide', () => {
    window.__kdsInstance?.cleanup();
  });

  window.addEventListener('popstate', () => {
    window.__kdsInstance?.cleanup();
  });
})();
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
