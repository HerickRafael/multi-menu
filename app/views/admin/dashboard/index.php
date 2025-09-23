<?php
// admin/dashboard/index.php — Dashboard (estilo moderno coeso)

// Helpers (caso a view seja renderizada isolada)
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('base_url')) {
  function base_url($p=''){
    $b = rtrim($_SERVER['BASE_URL'] ?? '/', '/');
    return $b . '/' . ltrim((string)$p, '/');
  }
}

// Normalizações seguras
$company           = is_array($company ?? null) ? $company : [];
$categories        = is_array($categories ?? null) ? $categories : [];
$products          = is_array($products ?? null) ? $products : [];
$recentIngredients = is_array($recentIngredients ?? null) ? $recentIngredients : [];
$ingredientsCount  = (int)($ingredientsCount ?? 0);
$ordersCount       = (int)($ordersCount ?? 0);

// Slugs/título com fallback
$activeSlug = (string)($activeSlug ?? ($company['slug'] ?? ''));
$slug       = rawurlencode($activeSlug);
$publicSlug = rawurlencode((string)($company['slug'] ?? ''));
$title      = "Dashboard - " . ($company['name'] ?? 'Empresa');

// Logo
$companyLogo = $company['logo'] ?? 'assets/logo-placeholder.png';

// Pequenos helpers
$price = function($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); };

ob_start(); ?>

<!-- HERO / TOPO -->
<section class="relative mb-6 overflow-hidden rounded-3xl border border-slate-200 admin-gradient-bg text-white">
  <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
  <div class="absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-black/10 blur-3xl"></div>

  <div class="relative z-10 grid gap-4 p-5 md:grid-cols-[auto_1fr_auto] md:items-center md:p-7">
    <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 p-0.5 ring-1 ring-white/30">
      <img src="<?= e(base_url($companyLogo)) ?>" alt="Logo" class="h-16 w-16 rounded-[0.9rem] object-cover ring-1 ring-black/10">
    </div>

    <div class="text-white">
      <h1 class="text-2xl font-semibold leading-tight">
        <?= e($company['name'] ?? '—') ?>
      </h1>
      <p class="mt-0.5 text-sm text-white/80">
        Categorias: <?= (int)count($categories) ?> • Produtos: <?= (int)count($products) ?>
        <?php if (!empty($company['hours_text'])): ?> • Horário: <?= e($company['hours_text']) ?><?php endif; ?>
        <?php if (isset($company['min_order'])): ?> • Mín.: <?= $price($company['min_order']) ?><?php endif; ?>
      </p>
    </div>

    <div class="flex flex-wrap gap-2">
      <a href="<?= e(base_url('admin/' . $slug . '/settings')) ?>" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm text-white ring-1 ring-white/30 hover:bg-white/15">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
  <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/>
  <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z"/>
</svg>
        Configurações
      </a>
      <a href="<?= e(base_url($publicSlug)) ?>" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm text-white ring-1 ring-white/30 hover:bg-white/15">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-house" viewBox="0 0 16 16">
  <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5z"/>
</svg>        Ver cardápio
      </a>
      <a href="<?= e(base_url('admin/' . $slug . '/logout')) ?>" class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-sm font-medium text-slate-900 shadow hover:opacity-95">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 7l5 5-5 5M20 12H9M15 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Sair
      </a>
    </div>
  </div>
</section>

<!-- AÇÕES RÁPIDAS -->
<div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
  <a href="<?= e(base_url('admin/' . $slug . '/categories/create')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16">
  <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/>
</svg> 
    </div>
    <div class="font-semibold text-slate-900">Nova categoria</div>
    <p class="text-sm text-slate-500">Organize seu cardápio por grupos.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/products/create')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16">
  <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/>
</svg>     </div>
    <div class="font-semibold text-slate-900">Novo produto</div>
    <p class="text-sm text-slate-500">Cadastre simples ou combos.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/ingredients/create')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16">
  <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/>
</svg>    </div>
    <div class="font-semibold text-slate-900">Novo ingrediente</div>
    <p class="text-sm text-slate-500">Vincule aos produtos.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/orders/create')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600 ring-1 ring-sky-100">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16">
  <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/>
</svg> 
    </div>
    <div class="font-semibold text-slate-900">Novo pedido</div>
    <p class="text-sm text-slate-500">Registre um pedido manualmente.</p>
  </a>
</div>

<!-- COLUNAS (visual coeso com o restante do dashboard) -->
<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">

<!-- Categorias -->
<div class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
  <div class="mb-3 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-list-ul" viewBox="0 0 16 16">
          <path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m-3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2m0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2m0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
        </svg>
      </span>
      <h2 class="font-semibold text-slate-900">Categorias</h2>
    </div>
    <span class="rounded-xl bg-slate-900 px-2.5 py-1 text-xs font-bold text-white"><?= (int)count($categories) ?></span>
  </div>

  <ul class="max-h-56 space-y-1.5 overflow-auto pr-1 text-sm thin-scroll">
    <?php foreach ($categories as $c): ?>
      <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
        <span class="truncate"><?= e($c['name'] ?? '') ?></span>
        <span class="text-[11px] text-slate-500">#<?= (int)($c['id'] ?? 0) ?></span>
      </li>
    <?php endforeach; ?>
    <?php if (!count($categories)): ?>
      <li class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-slate-500">Nenhuma categoria ainda.</li>
    <?php endif; ?>
  </ul>

  <div class="mt-4 flex items-center justify-between gap-2">
    <a class="inline-flex items-center gap-1 rounded-xl bg-indigo-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-1"
       href="<?= e(base_url('admin/' . $slug . '/categories/create')) ?>">+ Categoria</a>

    <a class="inline-flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50"
       href="<?= e(base_url('admin/' . $slug . '/categories')) ?>">Ver todas</a>
  </div>
</div>


  <!-- Produtos -->
  <div class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-3 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-bag" viewBox="0 0 16 16">
  <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/>
</svg>
        </span>
        <h2 class="font-semibold text-slate-900">Produtos</h2>
      </div>
      <span class="rounded-xl bg-slate-900 px-2.5 py-1 text-xs font-bold text-white"><?= (int)count($products) ?></span>
    </div>

    <ul class="divide-y rounded-xl border border-slate-100 bg-white text-sm">
      <?php $show = array_slice($products, 0, 8); ?>
      <?php foreach ($show as $p): ?>
        <li class="flex items-center gap-3 px-3 py-2.5 hover:bg-slate-50">
          <?php if (!empty($p['image'])): ?>
            <img src="<?= e(base_url($p['image'])) ?>" class="h-11 w-11 rounded-lg object-cover ring-1 ring-slate-200" alt="">
          <?php else: ?>
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-100 ring-1 ring-slate-200">
              <svg viewBox="0 0 24 24" class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="m3 16 5-5 4 4 5-6 4 6"/>
              </svg>
            </div>
          <?php endif; ?>
          <div class="min-w-0 flex-1">
            <div class="truncate font-medium text-slate-800"><?= e($p['name'] ?? '') ?></div>
            <div class="text-xs text-slate-500">
              <?php if (isset($p['promo_price']) && $p['promo_price'] !== '' && $p['promo_price'] !== null): ?>
                <span class="line-through"><?= $price($p['price'] ?? 0) ?></span>
                <strong class="ml-1 text-slate-800"><?= $price($p['promo_price']) ?></strong>
              <?php else: ?>
                <?= $price($p['price'] ?? 0) ?>
              <?php endif; ?>
            </div>
          </div>
          <a class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/products/' . (int)($p['id'] ?? 0) . '/edit')) ?>">Editar</a>
        </li>
      <?php endforeach; ?>
      <?php if (!count($show)): ?>
        <li class="px-3 py-3 text-slate-500">Sem produtos ainda.</li>
      <?php endif; ?>
    </ul>

    <div class="mt-4 flex items-center justify-between gap-2">
      <a class="inline-flex items-center gap-1 rounded-xl bg-emerald-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-emerald-700" href="<?= e(base_url('admin/' . $slug . '/products/create')) ?>">+ Produto</a>
      <a class="inline-flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/products')) ?>">Ver todos</a>
    </div>
  </div>

  <!-- Ingredientes recentes -->
  <div class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-3 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cup-straw" viewBox="0 0 16 16">
  <path d="M13.902.334a.5.5 0 0 1-.28.65l-2.254.902-.4 1.927c.376.095.715.215.972.367.228.135.56.396.56.82q0 .069-.011.132l-.962 9.068a1.28 1.28 0 0 1-.524.93c-.488.34-1.494.87-3.01.87s-2.522-.53-3.01-.87a1.28 1.28 0 0 1-.524-.93L3.51 5.132A1 1 0 0 1 3.5 5c0-.424.332-.685.56-.82.262-.154.607-.276.99-.372C5.824 3.614 6.867 3.5 8 3.5c.712 0 1.389.045 1.985.127l.464-2.215a.5.5 0 0 1 .303-.356l2.5-1a.5.5 0 0 1 .65.278M9.768 4.607A14 14 0 0 0 8 4.5c-1.076 0-2.033.11-2.707.278A3.3 3.3 0 0 0 4.645 5c.146.073.362.15.648.222C5.967 5.39 6.924 5.5 8 5.5c.571 0 1.109-.03 1.588-.085zm.292 1.756C9.445 6.45 8.742 6.5 8 6.5c-1.133 0-2.176-.114-2.95-.308a6 6 0 0 1-.435-.127l.838 8.03c.013.121.06.186.102.215.357.249 1.168.69 2.438.69s2.081-.441 2.438-.69c.042-.029.09-.094.102-.215l.852-8.03a6 6 0 0 1-.435.127 9 9 0 0 1-.89.17zM4.467 4.884s.003.002.005.006zm7.066 0-.005.006zM11.354 5a3 3 0 0 0-.604-.21l-.099.445.055-.013c.286-.072.502-.149.648-.222"/>
</svg>        </span>
        <h2 class="font-semibold text-slate-900">Ingredientes</h2>
      </div>
      <span class="rounded-xl bg-slate-900 px-2.5 py-1 text-xs font-bold text-white"><?= (int)$ingredientsCount ?></span>
    </div>

    <ul class="max-h-56 space-y-1.5 overflow-auto pr-1 text-sm thin-scroll">
      <?php foreach ($recentIngredients as $ing): ?>
        <?php
          $pnRaw = $ing['product_names'] ?? null;
          if (is_string($pnRaw) && strpos($pnRaw, '||') !== false) {
            $pn = array_values(array_filter(array_map('trim', explode('||', $pnRaw))));
          } elseif (is_string($pnRaw) && $pnRaw !== '') {
            $pn = [$pnRaw];
          } elseif (is_array($pnRaw)) {
            $pn = $pnRaw;
          } else {
            $pn = [];
          }
        ?>
        <li class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
          <div class="flex items-center justify-between gap-2">
            <span class="font-medium text-slate-800"><?= e($ing['name'] ?? '') ?></span>
            <?php if (!empty($ing['product_name'])): ?>
              <span class="rounded-lg bg-white px-2 py-0.5 text-[11px] text-slate-600 ring-1 ring-slate-200"><?= e((string)$ing['product_name']) ?></span>
            <?php endif; ?>
          </div>
          <?php if (!empty($pn)): ?>
            <div class="mt-1 flex flex-wrap gap-1.5">
              <?php foreach ($pn as $one): ?>
                <span class="rounded-md bg-amber-50 px-2 py-0.5 text-[11px] text-amber-700 ring-1 ring-amber-100"><?= e($one) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
      <?php if (!count($recentIngredients)): ?>
        <li class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-slate-500">Sem ingredientes cadastrados.</li>
      <?php endif; ?>
    </ul>

    <div class="mt-4 flex items-center justify-between gap-2">
      <a class="inline-flex items-center gap-1 rounded-xl bg-amber-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-amber-700" href="<?= e(base_url('admin/' . $slug . '/ingredients/create')) ?>">+ Ingredi..</a>
      <a class="inline-flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/ingredients')) ?>">Ver todos</a>
    </div>
  </div>

  <!-- Pedidos -->
  <div class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-3 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600 ring-1 ring-sky-100">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cart4" viewBox="0 0 16 16">
  <path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .485.379L2.89 4H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.485-.379L1.61 3H.5a.5.5 0 0 1-.5-.5M3.14 5l.5 2H5V5zM6 5v2h2V5zm3 0v2h2V5zm3 0v2h1.36l.5-2zm1.11 3H12v2h.61zM11 8H9v2h2zM8 8H6v2h2zM5 8H3.89l.5 2H5zm0 5a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0m9-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0"/>
</svg>        </span>
        <h2 class="font-semibold text-slate-900">Pedidos</h2>
      </div>
      <span class="rounded-xl bg-slate-900 px-2.5 py-1 text-xs font-bold text-white"><?= (int)$ordersCount ?></span>
    </div>

    <p class="text-sm text-slate-600">Acompanhe os pedidos do dia e o histórico completo.</p>

    <div class="mt-4 flex items-center justify-between gap-2">
      <a class="inline-flex items-center gap-1 rounded-xl bg-sky-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-sky-700" href="<?= e(base_url('admin/' . $slug . '/orders/create')) ?>">+ Pedido</a>
      <a class="inline-flex items-center gap-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/orders')) ?>">Ver pedidos</a>
    </div>
  </div>

</div>

<!-- Scrollbar fina (opcional, combina com o tema) -->
<style>
  .thin-scroll::-webkit-scrollbar{width:8px;height:8px}
  .thin-scroll::-webkit-scrollbar-thumb{background:#e5e7eb;border-radius:9999px}
  .thin-scroll::-webkit-scrollbar-track{background:transparent}
</style>





<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
