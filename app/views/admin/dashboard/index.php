<?php
// (opcional) helpers de segurança caso a view seja renderizada isolada
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
$ingredientsCount   = (int)($ingredientsCount ?? 0);

// Slugs/título com fallback
$activeSlug = (string)($activeSlug ?? ($company['slug'] ?? ''));
$slug       = rawurlencode($activeSlug);
$publicSlug = rawurlencode((string)($company['slug'] ?? ''));
$title      = "Dashboard - " . ($company['name'] ?? 'Empresa');

// Logo com fallback
$companyLogo = $company['logo'] ?? 'assets/logo-placeholder.png';

ob_start(); ?>

<header class="flex items-center gap-3 mb-6">
  <img src="<?= e(base_url($companyLogo)) ?>" class="w-12 h-12 rounded-xl object-cover" alt="Logo">
  <div>
    <h1 class="text-xl font-bold"><?= e($company['name'] ?? '') ?></h1>
    <p class="text-sm text-gray-600">
      Categorias: <?= (int)count($categories) ?> • Produtos: <?= (int)count($products) ?>
      <?php if (!empty($company['hours_text'])): ?> • Horário: <?= e($company['hours_text']) ?><?php endif; ?>
      <?php if (isset($company['min_order'])): ?> • Mín.: R$ <?= number_format((float)$company['min_order'], 2, ',', '.') ?><?php endif; ?>
    </p>
  </div>
  <a class="ml-auto px-3 py-2 rounded-xl border" href="<?= e(base_url('admin/' . $slug . '/logout')) ?>">Sair</a>
</header>

<!-- Abas -->
<nav class="flex flex-wrap gap-2 mb-5">
  <a href="<?= e(base_url('admin/' . $slug . '/settings')) ?>"    class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50 inline-flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-gear-fill" viewBox="0 0 16 16" aria-hidden="true">
      <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
    </svg>
    Geral
  </a>
  <a href="<?= e(base_url('admin/' . $slug . '/categories')) ?>"  class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50 inline-flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-folder2-open" viewBox="0 0 16 16" aria-hidden="true">
      <path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.374 3.334 5.82 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/>
    </svg>
    Categorias
  </a>
  <a href="<?= e(base_url('admin/' . $slug . '/products')) ?>"    class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50 inline-flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-receipt-cutoff" viewBox="0 0 16 16" aria-hidden="true">
      <path d="M3 4.5a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5M11.5 4a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1z"/>
      <path d="M2.354.646a.5.5 0 0 0-.801.13l-.5 1A.5.5 0 0 0 1 2v13H.5a.5.5 0 0 0 0 1h15a.5.5 0 0 0 0-1H15V2a.5.5 0 0 0-.053-.224l-.5-1a.5.5 0 0 0-.8-.13L13 1.293l-.646-.647a.5.5 0 0 0-.708 0L11 1.293l-.646-.647a.5.5 0 0 0-.708 0L9 1.293 8.354.646a.5.5 0 0 0-.708 0L7 1.293 6.354.646a.5.5 0 0 0-.708 0L5 1.293 4.354.646a.5.5 0 0 0-.708 0L3 1.293zm-.217 1.198.51.51a.5.5 0 0 0 .707 0L4 1.707l.646.647a.5.5 0 0 0 .708 0L6 1.707l.646.647a.5.5 0 0 0 .708 0L8 1.707l.646.647a.5.5 0 0 0 .708 0L10 1.707l.646.647a.5.5 0 0 0 .708 0L12 1.707l.646.647a.5.5 0 0 0 .708 0l.509-.51.137.274V15H2V2.118z"/>
    </svg>
    Produtos
  </a>
  <a href="<?= e(base_url('admin/' . $slug . '/ingredients')) ?>" class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50 inline-flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-basket" viewBox="0 0 16 16" aria-hidden="true">
      <path d="M5.757 1.071a.5.5 0 0 1 .172.686L3.383 6h9.234L10.07 1.757a.5.5 0 1 1 .858-.514L13.783 6H15a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1v4.5a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 1 13.5V9a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h1.217L5.07 1.243a.5.5 0 0 1 .686-.172zM2 9v4.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V9zM1 7v1h14V7zm3 3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0v-3A.5.5 0 0 1 4 10m2 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0v-3A.5.5 0 0 1 6 10m2 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0v-3A.5.5 0 0 1 8 10m2 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0v-3a.5.5 0 0 1 .5-.5m2 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0v-3a.5.5 0 0 1 .5-.5"/>
    </svg>
    Ingredientes
  </a>
  <a href="<?= e(base_url('admin/' . $slug . '/orders')) ?>"      class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50 inline-flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-box-seam" viewBox="0 0 16 16" aria-hidden="true">
      <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2z"/>
      <path d="M11.75 2.539 5.596 5 8 5.961 14.154 3.5z"/>
      <path d="M14.5 4.24 8 6.838v7.924l6.5-2.599zM7.5 14.762V6.838L1 4.239v7.923z"/>
      <path d="M7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09A1 1 0 0 1 0 12.162V3.5a.5.5 0 0 1 .314-.464z"/>
    </svg>
    Pedidos
  </a>
  <a href="<?= e(base_url($publicSlug)) ?>" target="_blank"       class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50 inline-flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-link-45deg" viewBox="0 0 16 16" aria-hidden="true">
      <path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1 1 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4 4 0 0 1-.128-1.287z"/>
      <path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-.896-3.346L9.12 3.55a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 1 0-4.243-4.243z"/>
    </svg>
    Ver cardápio
  </a>
</nav>

<!-- Cards resumo -->
<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="rounded-2xl bg-white border p-4">
    <div class="text-sm text-gray-500 mb-1">Categorias</div>
    <div class="text-3xl font-bold mb-3"><?= (int)count($categories) ?></div>
    <a class="px-3 py-2 rounded-xl border inline-block" href="<?= e(base_url('admin/' . $slug . '/categories')) ?>">Gerenciar</a>
  </div>

  <div class="rounded-2xl bg-white border p-4">
    <div class="text-sm text-gray-500 mb-1">Produtos</div>
    <div class="text-3xl font-bold mb-3"><?= (int)count($products) ?></div>
    <div class="flex gap-2">
      <a class="px-3 py-2 rounded-xl border" href="<?= e(base_url('admin/' . $slug . '/products')) ?>">Gerenciar</a>
      <a class="px-3 py-2 rounded-xl border" href="<?= e(base_url('admin/' . $slug . '/products/create')) ?>">+ Novo</a>
    </div>
  </div>

  <div class="rounded-2xl bg-white border p-4">
    <div class="text-sm text-gray-500 mb-1">Ingredientes</div>
    <div class="text-3xl font-bold mb-3"><?= (int)$ingredientsCount ?></div>
    <div class="flex gap-2">
      <a class="px-3 py-2 rounded-xl border" href="<?= e(base_url('admin/' . $slug . '/ingredients')) ?>">Gerenciar</a>
      <a class="px-3 py-2 rounded-xl border" href="<?= e(base_url('admin/' . $slug . '/ingredients/create')) ?>">+ Novo</a>
    </div>
  </div>

  <div class="rounded-2xl bg-white border p-4">
    <div class="text-sm text-gray-500 mb-1">Pedidos</div>
    <div class="mb-3">
      <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-box-seam w-12 h-12 text-gray-800" viewBox="0 0 16 16" aria-hidden="true">
        <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2z"/>
        <path d="M11.75 2.539 5.596 5 8 5.961 14.154 3.5z"/>
        <path d="M14.5 4.24 8 6.838v7.924l6.5-2.599zM7.5 14.762V6.838L1 4.239v7.923z"/>
        <path d="M7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09A1 1 0 0 1 0 12.162V3.5a.5.5 0 0 1 .314-.464z"/>
      </svg>
    </div>
    <a class="px-3 py-2 rounded-xl border inline-block" href="<?= e(base_url('admin/' . $slug . '/orders')) ?>">Ver pedidos</a>
  </div>
</div>

<!-- Listas rápidas -->
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
  <!-- Categorias -->
  <div class="rounded-2xl bg-white border p-4">
    <h2 class="font-semibold mb-2">Categorias</h2>
    <ul class="list-disc ml-5">
      <?php foreach ($categories as $c): ?>
        <li><?= e($c['name'] ?? '') ?> <span class="text-xs text-gray-500">(#<?= (int)($c['id'] ?? 0) ?>)</span></li>
      <?php endforeach; ?>
      <?php if (!count($categories)): ?>
        <li class="text-sm text-gray-500">Nenhuma categoria ainda.</li>
      <?php endif; ?>
    </ul>
    <div class="mt-3">
      <a class="px-3 py-2 rounded-xl border inline-block" href="<?= e(base_url('admin/' . $slug . '/categories/create')) ?>">+ Nova categoria</a>
    </div>
  </div>

  <!-- Produtos recentes -->
  <div class="rounded-2xl bg-white border p-4">
    <h2 class="font-semibold mb-2">Produtos (últimos cadastrados)</h2>
    <ul class="divide-y">
      <?php $show = array_slice($products, 0, 8); ?>
      <?php foreach ($show as $p): ?>
        <li class="py-2 flex items-center gap-3">
          <?php if (!empty($p['image'])): ?>
            <img src="<?= e(base_url($p['image'])) ?>" class="w-10 h-10 object-cover rounded-lg" alt="">
          <?php else: ?>
            <div class="w-10 h-10 rounded-lg bg-slate-200"></div>
          <?php endif; ?>
          <div class="flex-1">
            <div class="font-medium text-sm"><?= e($p['name'] ?? '') ?></div>
            <div class="text-xs text-gray-500">
              <?php if (isset($p['promo_price']) && $p['promo_price'] !== null && $p['promo_price'] !== ''): ?>
                <span class="line-through">R$ <?= number_format((float)($p['price'] ?? 0), 2, ',', '.') ?></span>
                <strong class="ml-1">R$ <?= number_format((float)$p['promo_price'], 2, ',', '.') ?></strong>
              <?php else: ?>
                R$ <?= number_format((float)($p['price'] ?? 0), 2, ',', '.') ?>
              <?php endif; ?>
            </div>
          </div>
          <a class="px-2 py-1 rounded-lg border text-sm" href="<?= e(base_url('admin/' . $slug . '/products/' . (int)($p['id'] ?? 0) . '/edit')) ?>">Editar</a>
        </li>
      <?php endforeach; ?>
      <?php if (!count($show)): ?>
        <li class="py-2 text-sm text-gray-500">Sem produtos ainda.</li>
      <?php endif; ?>
    </ul>
    <div class="mt-3 flex gap-2">
      <a class="px-3 py-2 rounded-xl border" href="<?= e(base_url('admin/' . $slug . '/products/create')) ?>">+ Novo produto</a>
      <a class="px-3 py-2 rounded-xl border" href="<?= e(base_url('admin/' . $slug . '/products')) ?>">Ver todos</a>
    </div>
  </div>

  <!-- Ingredientes recentes -->
  <div class="rounded-2xl bg-white border p-4">
    <h2 class="font-semibold mb-2">Ingredientes recentes</h2>
    <ul class="list-disc ml-5">
      <?php foreach ($recentIngredients as $ing): ?>
        <li>
          <?= e($ing['name'] ?? '') ?>
          <?php
            // Aceita tanto array quanto string com '||' de separador
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
            <span class="text-xs text-gray-500">(<?= e(implode(', ', $pn)) ?>)</span>
          <?php endif; ?>
          <?php if (!empty($ing['product_name'])): // fallback para chave singular se existir ?>
            <span class="text-xs text-gray-500">(<?= e((string)$ing['product_name']) ?>)</span>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
      <?php if (!count($recentIngredients)): ?>
        <li class="text-sm text-gray-500">Sem ingredientes cadastrados.</li>
      <?php endif; ?>
    </ul>
    <div class="mt-3 flex gap-2">
      <a class="px-3 py-2 rounded-xl border" href="<?= e(base_url('admin/' . $slug . '/ingredients/create')) ?>">+ Novo ingrediente</a>
      <a class="px-3 py-2 rounded-xl border" href="<?= e(base_url('admin/' . $slug . '/ingredients')) ?>">Ver todos</a>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
