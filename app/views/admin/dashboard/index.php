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
        <svg class="h-4 w-4" viewBox="0 0 16 16" fill="currentColor"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492A3.246 3.246 0 0 0 8 4.754"/></svg>
        Configurações
      </a>
      <a href="<?= e(base_url($publicSlug)) ?>" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm text-white ring-1 ring-white/30 hover:bg-white/15">
        <svg class="h-4 w-4" viewBox="0 0 16 16" fill="currentColor"><path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5"/></svg>
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
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <div class="font-semibold text-slate-900">Nova categoria</div>
    <p class="text-sm text-slate-500">Organize seu cardápio por grupos.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/products/create')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M7 12h10M12 7v10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <div class="font-semibold text-slate-900">Novo produto</div>
    <p class="text-sm text-slate-500">Cadastre simples ou combos.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/ingredients/create')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M7 12h10M10 17h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <div class="font-semibold text-slate-900">Novo ingrediente</div>
    <p class="text-sm text-slate-500">Vincule aos produtos.</p>
  </a>

  <a href="<?= e(base_url('admin/' . $slug . '/orders/create')) ?>" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600 ring-1 ring-sky-100">
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M6 6h12M6 12h10M6 18h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </div>
    <div class="font-semibold text-slate-900">Novo pedido</div>
    <p class="text-sm text-slate-500">Registre um pedido manualmente.</p>
  </a>
</div>

<!-- KPIs -->
<div class="mb-6 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="text-sm text-slate-500">Categorias</div>
    <div class="mb-3 text-3xl font-bold"><?= (int)count($categories) ?></div>
    <a class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/categories')) ?>">Gerenciar</a>
  </div>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="text-sm text-slate-500">Produtos</div>
    <div class="mb-3 text-3xl font-bold"><?= (int)count($products) ?></div>
    <div class="flex gap-2">
      <a class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/products')) ?>">Ver todos</a>
    </div>
  </div>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="text-sm text-slate-500">Ingredientes</div>
    <div class="mb-3 text-3xl font-bold"><?= (int)$ingredientsCount ?></div>
    <div class="flex gap-2">
      <a class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/ingredients')) ?>">Ver todos</a>
    </div>
  </div>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="text-sm text-slate-500">Pedidos</div>
    <div class="mb-3 text-3xl font-bold"><?= (int)$ordersCount ?></div>
    <div class="flex gap-2">
      <a class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/orders')) ?>">Ver pedidos</a>
    </div>
  </div>
</div>

<!-- COLUNAS: Categorias | Produtos recentes | Ingredientes recentes -->
<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
  <!-- Categorias -->
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-2 flex items-center justify-between">
      <h2 class="font-semibold text-slate-800">Categorias</h2>
      <a class="rounded-lg border border-slate-300 px-2 py-1 text-xs hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/categories/create')) ?>">+ Nova</a>
    </div>
    <ul class="ml-5 list-disc text-sm">
      <?php foreach ($categories as $c): ?>
        <li><?= e($c['name'] ?? '') ?> <span class="text-xs text-slate-500">(#<?= (int)($c['id'] ?? 0) ?>)</span></li>
      <?php endforeach; ?>
      <?php if (!count($categories)): ?>
        <li class="text-slate-500">Nenhuma categoria ainda.</li>
      <?php endif; ?>
    </ul>
  </div>

  <!-- Produtos recentes -->
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-2 flex items-center justify-between">
      <h2 class="font-semibold text-slate-800">Produtos </h2>
      <a class="rounded-lg border border-slate-300 px-2 py-1 text-xs hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/products')) ?>">Ver todos</a>
    </div>
    <ul class="divide-y text-sm">
      <?php $show = array_slice($products, 0, 8); ?>
      <?php foreach ($show as $p): ?>
        <li class="flex items-center gap-3 py-2">
          <?php if (!empty($p['image'])): ?>
            <img src="<?= e(base_url($p['image'])) ?>" class="h-10 w-10 rounded-lg object-cover ring-1 ring-slate-200" alt="">
          <?php else: ?>
            <div class="h-10 w-10 rounded-lg bg-slate-200"></div>
          <?php endif; ?>
          <div class="flex-1">
            <div class="font-medium text-slate-800"><?= e($p['name'] ?? '') ?></div>
            <div class="text-xs text-slate-500">
              <?php if (isset($p['promo_price']) && $p['promo_price'] !== '' && $p['promo_price'] !== null): ?>
                <span class="line-through"><?= $price($p['price'] ?? 0) ?></span>
                <strong class="ml-1"><?= $price($p['promo_price']) ?></strong>
              <?php else: ?>
                <?= $price($p['price'] ?? 0) ?>
              <?php endif; ?>
            </div>
          </div>
          <a class="rounded-lg border border-slate-300 px-2 py-1 text-xs hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/products/' . (int)($p['id'] ?? 0) . '/edit')) ?>">Editar</a>
        </li>
      <?php endforeach; ?>
      <?php if (!count($show)): ?>
        <li class="py-2 text-slate-500">Sem produtos ainda.</li>
      <?php endif; ?>
    </ul>
    <div class="mt-3 flex gap-2">
      <a class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/products/create')) ?>">+ Novo produto</a>
      <a class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/products')) ?>">Ver todos</a>
    </div>
  </div>

  <!-- Ingredientes recentes -->
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-2 flex items-center justify-between">
      <h2 class="font-semibold text-slate-800">Ingredientes recentes</h2>
      <a class="rounded-lg border border-slate-300 px-2 py-1 text-xs hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/ingredients')) ?>">Ver todos</a>
    </div>
    <ul class="ml-5 list-disc text-sm">
      <?php foreach ($recentIngredients as $ing): ?>
        <li>
          <?= e($ing['name'] ?? '') ?>
          <?php
            // Lê produtos vinculados (aceita string "A||B" ou array)
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
          <?php if (!empty($pn)): ?>
            <span class="text-xs text-slate-500">(<?= e(implode(', ', $pn)) ?>)</span>
          <?php endif; ?>
          <?php if (!empty($ing['product_name'])): ?>
            <span class="text-xs text-slate-500">(<?= e((string)$ing['product_name']) ?>)</span>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
      <?php if (!count($recentIngredients)): ?>
        <li class="text-slate-500">Sem ingredientes cadastrados.</li>
      <?php endif; ?>
    </ul>
    <div class="mt-3 flex gap-2">
      <a class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/ingredients/create')) ?>">+ Novo ingrediente</a>
      <a class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50" href="<?= e(base_url('admin/' . $slug . '/ingredients')) ?>">Ver todos</a>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
