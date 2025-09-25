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
$company            = is_array($company ?? null) ? $company : [];
$categories         = is_array($categories ?? null) ? $categories : [];
$products           = is_array($products ?? null) ? $products : [];
$recentIngredients  = is_array($recentIngredients ?? null) ? $recentIngredients : [];
$recentOrders       = is_array($recentOrders ?? null) ? $recentOrders : []; // <— NOVO: lista de últimos pedidos
$ingredientsCount   = (int)($ingredientsCount ?? 0);
$ordersCount        = (int)($ordersCount ?? 0);

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
          <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.246.835 1.428.835 1.674 0l.094-.319a1.873 1.873 0 0 0 2.692-1.115z"/>
        </svg>
        Configurações
      </a>
      <a href="<?= e(base_url($publicSlug)) ?>" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm text-white ring-1 ring-white/30 hover:bg-white/15">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-house" viewBox="0 0 16 16">
          <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5z"/>
        </svg>
        Ver cardápio
      </a>
      <a href="<?= e(base_url('admin/' . $slug . '/logout')) ?>" class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-sm font-medium text-slate-900 shadow hover:opacity-95">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 7l5 5-5 5M20 12H9M15 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Sair
      </a>
    </div>
  </div>
</section>

<!-- AÇÕES RÁPIDAS -->
<div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
  <a href="<?= e(base_url('admin/' . $slug . '/categories/create')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/></svg>
    </div>
    <div class="font-semibold text-slate-900">Nova categoria</div>
    <p class="text-sm text-slate-500">Organize seu cardápio por grupos.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/products/create')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/></svg>
    </div>
    <div class="font-semibold text-slate-900">Novo produto</div>
    <p class="text-sm text-slate-500">Cadastre simples ou combos.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/ingredients/create')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/></svg>
    </div>
    <div class="font-semibold text-slate-900">Novo ingrediente</div>
    <p class="text-sm text-slate-500">Vincule aos produtos.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/orders/create')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600 ring-1 ring-sky-100">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/></svg>
    </div>
    <div class="font-semibold text-slate-900">Novo pedido</div>
    <p class="text-sm text-slate-500">Registre um pedido manualmente.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/delivery-fees')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-100">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-truck" viewBox="0 0 16 16">
        <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5v7A1.5 1.5 0 0 1 10.5 12H10a2 2 0 1 1-4 0H4a2 2 0 1 1-3.874-.5A1.5 1.5 0 0 1 0 10.5zm1.5-.5a.5.5 0 0 0-.5.5v5.473A2 2 0 0 1 3.874 11H6V3h4.5a.5.5 0 0 0 .5-.5V3h.086a1.5 1.5 0 0 1 1.3.75l1.528 2.75a1.5 1.5 0 0 1 .186.725V9.5A1.5 1.5 0 0 1 12.5 11H12a2 2 0 1 1-4 0H6v1h4.5a.5.5 0 0 0 .5-.5V9h1.5a.5.5 0 0 0 .5-.5v-.525a.5.5 0 0 0-.062-.242l-1.528-2.75A.5.5 0 0 0 11.438 5H11V3.5A1.5 1.5 0 0 0 9.5 2zM4.5 12a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3m7 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3"/>
      </svg>
    </div>
    <div class="font-semibold text-slate-900">Taxas de entrega</div>
    <p class="text-sm text-slate-500">Gerencie cidades e bairros atendidos.</p>
  </a>
</div>

<!-- COLUNAS -->
<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">

<!-- Categorias -->
<div class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md card-link"
     data-href="<?= e(base_url('admin/' . $slug . '/categories')) ?>" role="button" tabindex="0">
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

  <ul class="divide-y rounded-xl border border-slate-100 bg-white text-sm max-h-56 overflow-auto pr-1 thin-scroll">
    <?php foreach ($categories as $c): ?>
      <li class="flex items-center gap-3 px-3 py-2.5 hover:bg-slate-50">
        <a class="flex w-full items-center justify-between gap-3"
           href="<?= e(base_url('admin/' . $slug . '/categories/' . (int)($c['id'] ?? 0) . '/edit')) ?>">
          <div class="truncate font-medium text-slate-800"><?= e($c['name'] ?? '') ?></div>
          <span class="text-[11px] text-slate-500">#<?= (int)($c['id'] ?? 0) ?></span>
        </a>
      </li>
    <?php endforeach; ?>
    <?php if (!count($categories)): ?>
      <li class="px-3 py-3 text-slate-500">Nenhuma categoria ainda.</li>
    <?php endif; ?>
  </ul>
</div>


  <!-- Produtos (sem botão Editar; item inteiro clicável pro form) -->
  <div class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md card-link"
       data-href="<?= e(base_url('admin/' . $slug . '/products')) ?>" role="button" tabindex="0">
    <div class="mb-3 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-bag" viewBox="0 0 16 16"><path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/></svg>
        </span>
        <h2 class="font-semibold text-slate-900">Produtos</h2>
      </div>
      <span class="rounded-xl bg-slate-900 px-2.5 py-1 text-xs font-bold text-white"><?= (int)count($products) ?></span>
    </div>

    <ul class="divide-y rounded-xl border border-slate-100 bg-white text-sm">
      <?php $show = array_slice($products, 0, 8); ?>
      <?php foreach ($show as $p): $pid = (int)($p['id'] ?? 0); ?>
        <li class="flex items-center gap-3 px-3 py-2.5 hover:bg-slate-50">
          <a class="flex w-full items-center gap-3" href="<?= e(base_url('admin/' . $slug . '/products/' . $pid . '/edit')) ?>">
            <?php if (!empty($p['image'])): ?>
              <img src="<?= e(base_url($p['image'])) ?>" class="h-11 w-11 rounded-lg object-cover ring-1 ring-slate-200" alt="">
            <?php else: ?>
              <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-100 text-slate-400 ring-1 ring-slate-200">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
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
          </a>
        </li>
      <?php endforeach; ?>
      <?php if (!count($show)): ?>
        <li class="px-3 py-3 text-slate-500">Sem produtos ainda.</li>
      <?php endif; ?>
    </ul>
  </div>

<!-- Ingredientes -->
<div class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md card-link"
     data-href="<?= e(base_url('admin/' . $slug . '/ingredients')) ?>" role="button" tabindex="0">
  <div class="mb-3 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cup-straw" viewBox="0 0 16 16">
          <path d="M13.902.334a.5.5 0 0 1-.28.65l-2.254.902-.4 1.927c.376.095.715.215.972.367.228.135.56.396.56.82q0 .069-.011.132l-.962 9.068a1.28 1.28 0 0 1-.524.93c-.488.34-1.494.87-3.01.87s-2.522-.53-3.01-.87a1.28 1.28 0 0 1-.524-.93L3.51 5.132A1 1 0 0 1 3.5 5c0-.424.332-.685.56-.82.262-.154.607-.276.99-.372C5.824 3.614 6.867 3.5 8 3.5c.712 0 1.389.045 1.985.127l.464-2.215a.5.5 0 0 1 .303-.356l2.5-1a.5.5 0 0 1 .65.278"/>
        </svg>
      </span>
      <h2 class="font-semibold text-slate-900">Ingredientes</h2>
    </div>
    <span class="rounded-xl bg-slate-900 px-2.5 py-1 text-xs font-bold text-white"><?= (int)$ingredientsCount ?></span>
  </div>

  <ul class="divide-y rounded-xl border border-slate-100 bg-white text-sm max-h-56 overflow-auto pr-1 thin-scroll">
    <?php $ingsToShow = array_slice($recentIngredients, 0, 8); ?>
    <?php foreach ($ingsToShow as $ing): ?>
      <?php
        $iid = (int)($ing['id'] ?? 0);
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
      <li class="flex items-center gap-3 px-3 py-2.5 hover:bg-slate-50">
        <a class="flex w-full items-center gap-3" href="<?= e(base_url('admin/' . $slug . '/ingredients/' . $iid . '/edit')) ?>">
          <?php $ingImage = trim((string)($ing['image_path'] ?? '')); ?>
          <?php if ($ingImage !== ''): ?>
            <img src="<?= e(base_url($ingImage)) ?>" alt=""
                 class="h-11 w-11 rounded-lg object-cover ring-1 ring-slate-200">
          <?php else: ?>
            <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-100 text-slate-400 ring-1 ring-slate-200">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
          <?php endif; ?>
          <div class="min-w-0 flex-1">
            <div class="truncate font-medium text-slate-800"><?= e($ing['name'] ?? '') ?></div>
            <?php if (!empty($pn)): ?>
              <div class="mt-1 flex flex-wrap gap-1.5">
                <?php foreach ($pn as $one): ?>
                  <span class="rounded-md bg-amber-50 px-2 py-0.5 text-[11px] text-amber-700 ring-1 ring-amber-100"><?= e($one) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </a>
      </li>
    <?php endforeach; ?>
    <?php if (!count($ingsToShow)): ?>
      <li class="px-3 py-3 text-slate-500">Sem ingredientes cadastrados.</li>
    <?php endif; ?>
  </ul>
</div>


<!-- Pedidos (dashboard) — visual e status iguais ao admin/orders/index.php -->
<div class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md card-link"
     data-href="<?= e(base_url('admin/' . $slug . '/orders')) ?>" role="button" tabindex="0">

  <div class="mb-3 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600 ring-1 ring-sky-100">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cart4" viewBox="0 0 16 16">
          <path d="M0 2.5A.5.5 0 0 1 .5 2H2l.89 2H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4L2 3H.5a.5.5 0 0 1-.5-.5z"/>
          <path d="M5 12a1 1 0 1 0 0 2 1 1 0 0 0 0-2m6 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/>
        </svg>
      </span>
      <h2 class="font-semibold text-slate-900">Pedidos</h2>
    </div>
    <span class="rounded-xl bg-slate-900 px-2.5 py-1 text-xs font-bold text-white"><?= (int)$ordersCount ?></span>
  </div>

  <?php
    // mesmo mapeamento/labels da página orders
    $statusLabels = [
      'pending'   => 'Pendente',
      'paid'      => 'Pago',
      'completed' => 'Concluído',
      'canceled'  => 'Cancelado',
    ];
    $ordersToShow = array_slice($recentOrders, 0, 8);
  ?>

  <ul class="divide-y rounded-xl border border-slate-100 bg-white text-sm max-h-56 overflow-auto pr-1 thin-scroll">
    <?php foreach ($ordersToShow as $o): $oid = (int)($o['id'] ?? 0); ?>
      <?php
        $st    = (string)($o['status'] ?? 'pending');
        $label = $statusLabels[$st] ?? ucfirst($st);

        // classes do badge iguais ao admin/orders/index.php
        $badge = match ($st) {
          'paid'      => 'bg-blue-50  text-blue-700  ring-blue-200',
          'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
          'canceled'  => 'bg-rose-50 text-rose-700 ring-rose-200',
          default     => 'bg-amber-50 text-amber-700 ring-amber-200', // pending
        };

        // cor do pontinho
        $dot = match ($st) {
          'paid'      => 'bg-blue-500',
          'completed' => 'bg-emerald-500',
          'canceled'  => 'bg-rose-500',
          default     => 'bg-amber-500',
        };
      ?>
      <li>
        <a class="flex w-full items-center justify-between gap-3 px-3 py-2.5 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-200"
           href="<?= e(base_url('admin/' . $slug . '/orders/show?id=' . $oid)) ?>">

          <div class="min-w-0">
            <div class="truncate font-medium text-slate-800">
              #<?= $oid ?> · <?= e($o['customer_name'] ?? 'Cliente') ?>
            </div>
            <div class="text-xs text-slate-500">
              <?= e($o['created_at'] ?? '') ?>
            </div>
            <div class="mt-0.5 text-xs">
              <strong class="text-slate-800"><?= $price($o['total'] ?? 0) ?></strong>
            </div>
          </div>

          <!-- Badge de status igual ao da listagem de pedidos -->
          <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[12px] font-medium ring-1 <?= $badge ?>">
            <span class="h-1.5 w-1.5 rounded-full <?= $dot ?>"></span>
            <?= e($label) ?>
          </span>
        </a>
      </li>
    <?php endforeach; ?>
    <?php if (!count($ordersToShow)): ?>
      <li class="px-3 py-3 text-slate-500">Sem pedidos ainda.</li>
    <?php endif; ?>
  </ul>
</div>



</div>

<!-- Scrollbar fina + cursor de cartão -->
<style>
  .thin-scroll::-webkit-scrollbar{width:8px;height:8px}
  .thin-scroll::-webkit-scrollbar-thumb{background:#e5e7eb;border-radius:9999px}
  .thin-scroll::-webkit-scrollbar-track{background:transparent}
  .card-link{cursor:pointer}
</style>

<!-- JS: torna blocos clicáveis (e ignora cliques em links internos) -->
<script>
  document.querySelectorAll('.card-link').forEach(function(card){
    const href = card.getAttribute('data-href');
    if(!href) return;
    card.addEventListener('click', function(e){
      // Evita navegar se clicou num <a> interno
      const a = e.target.closest('a');
      if(a) return;
      window.location.href = href;
    });
    card.addEventListener('keydown', function(e){
      if(e.key === 'Enter' || e.key === ' '){
        e.preventDefault();
        window.location.href = href;
      }
    });
  });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
