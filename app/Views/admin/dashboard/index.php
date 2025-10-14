<?php
// admin/dashboard/index.php — Dashboard (estilo moderno coeso)

// Helpers (caso a view seja renderizada isolada)
if (!function_exists('e')) {
    function e($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('base_url')) {
    function base_url($p = '')
    {
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
$title      = 'Dashboard - ' . ($company['name'] ?? 'Empresa');

// Logo
$companyLogo = $company['logo'] ?? 'assets/logo-placeholder.png';

// Pequenos helpers
$price = function ($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); };

ob_start(); ?>

<div class="mx-auto max-w-6xl p-4">

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
      <a href="<?= e(base_url('admin/' . $slug . '/kds')) ?>" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm text-white ring-1 ring-white/30 hover:bg-white/15">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-display" viewBox="0 0 16 16">
          <path d="M0 1a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1z"/>
          <path d="M2 13.5a.5.5 0 0 1 .5-.5H6v-1H3.5a.5.5 0 0 1 0-1h9a.5.5 0 0 1 0 1H10v1h3.5a.5.5 0 0 1 0 1H2.5a.5.5 0 0 1-.5-.5"/>
        </svg>
        Abrir KDS
      </a>
      <a href="<?= e(base_url('admin/' . $slug . '/settings')) ?>" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm text-white ring-1 ring-white/30 hover:bg-white/15">
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear-fill" viewBox="0 0 16 16">
  <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
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
<div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
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

    <ul class="divide-y rounded-xl border border-slate-100 bg-white text-sm max-h-56 overflow-auto pr-1 thin-scroll">
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
            <!-- product names removed from quick dashboard view -->
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


<!-- Gestão operacional -->
<div class="mt-6 grid gap-3 sm:grid-cols-3">
  <a href="<?= e(base_url('admin/' . $slug . '/payment-methods')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50 text-purple-600 ring-1 ring-purple-100">
<svg xmlns="http://www.w3.org/2000/svg" width="" height="20" fill="currentColor" class="bi bi-credit-card-2-back-fill" viewBox="0 0 16 16">
  <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v5H0zm11.5 1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zM0 11v1a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1z"/>
</svg>
    </div>
    <div class="font-semibold text-slate-900">Métodos de pagamento</div>
    <p class="text-sm text-slate-500">Gerencie as opções exibidas no checkout.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/delivery-fees')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-100">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-rocket-takeoff-fill" viewBox="0 0 16 16">
  <path d="M12.17 9.53c2.307-2.592 3.278-4.684 3.641-6.218.21-.887.214-1.58.16-2.065a3.6 3.6 0 0 0-.108-.563 2 2 0 0 0-.078-.23V.453c-.073-.164-.168-.234-.352-.295a2 2 0 0 0-.16-.045 4 4 0 0 0-.57-.093c-.49-.044-1.19-.03-2.08.188-1.536.374-3.618 1.343-6.161 3.604l-2.4.238h-.006a2.55 2.55 0 0 0-1.524.734L.15 7.17a.512.512 0 0 0 .433.868l1.896-.271c.28-.04.592.013.955.132.232.076.437.16.655.248l.203.083c.196.816.66 1.58 1.275 2.195.613.614 1.376 1.08 2.191 1.277l.082.202c.089.218.173.424.249.657.118.363.172.676.132.956l-.271 1.9a.512.512 0 0 0 .867.433l2.382-2.386c.41-.41.668-.949.732-1.526zm.11-3.699c-.797.8-1.93.961-2.528.362-.598-.6-.436-1.733.361-2.532.798-.799 1.93-.96 2.528-.361s.437 1.732-.36 2.531Z"/>
  <path d="M5.205 10.787a7.6 7.6 0 0 0 1.804 1.352c-1.118 1.007-4.929 2.028-5.054 1.903-.126-.127.737-4.189 1.839-5.18.346.69.837 1.35 1.411 1.925"/>
</svg>
    </div>
    <div class="font-semibold text-slate-900">Taxas de entrega</div>
    <p class="text-sm text-slate-500">Atualize cidades, bairros e valores.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/evolution')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-green-50 text-green-600 ring-1 ring-green-100">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
  <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.78-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.336-.445-.342-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/>
</svg>
    </div>
    <div class="font-semibold text-slate-900">Evolution API</div>
    <p class="text-sm text-slate-500">Gerencie instâncias WhatsApp e notificações.</p>
  </a>
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
// Close the main wrapper which was left open in some older templates
echo "</div>\n";
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
