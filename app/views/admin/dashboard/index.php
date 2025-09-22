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
$ordersCount        = (int)($ordersCount ?? 0);

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
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
  <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/>
  <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z"/>
</svg>
    Geral
  </a>
  <a href="<?= e(base_url('admin/' . $slug . '/categories')) ?>"  class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50 inline-flex items-center gap-2">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-list-ul" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m-3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2m0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2m0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
</svg>
    Categorias
  </a>
  <a href="<?= e(base_url('admin/' . $slug . '/products')) ?>"    class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50 inline-flex items-center gap-2">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cup-straw" viewBox="0 0 16 16">
  <path d="M13.902.334a.5.5 0 0 1-.28.65l-2.254.902-.4 1.927c.376.095.715.215.972.367.228.135.56.396.56.82q0 .069-.011.132l-.962 9.068a1.28 1.28 0 0 1-.524.93c-.488.34-1.494.87-3.01.87s-2.522-.53-3.01-.87a1.28 1.28 0 0 1-.524-.93L3.51 5.132A1 1 0 0 1 3.5 5c0-.424.332-.685.56-.82.262-.154.607-.276.99-.372C5.824 3.614 6.867 3.5 8 3.5c.712 0 1.389.045 1.985.127l.464-2.215a.5.5 0 0 1 .303-.356l2.5-1a.5.5 0 0 1 .65.278M9.768 4.607A14 14 0 0 0 8 4.5c-1.076 0-2.033.11-2.707.278A3.3 3.3 0 0 0 4.645 5c.146.073.362.15.648.222C5.967 5.39 6.924 5.5 8 5.5c.571 0 1.109-.03 1.588-.085zm.292 1.756C9.445 6.45 8.742 6.5 8 6.5c-1.133 0-2.176-.114-2.95-.308a6 6 0 0 1-.435-.127l.838 8.03c.013.121.06.186.102.215.357.249 1.168.69 2.438.69s2.081-.441 2.438-.69c.042-.029.09-.094.102-.215l.852-8.03a6 6 0 0 1-.435.127 9 9 0 0 1-.89.17zM4.467 4.884s.003.002.005.006zm7.066 0-.005.006zM11.354 5a3 3 0 0 0-.604-.21l-.099.445.055-.013c.286-.072.502-.149.648-.222"/>
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
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-box-seam" viewBox="0 0 16 16">
  <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2zm3.564 1.426L5.596 5 8 5.961 14.154 3.5zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464z"/>
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
    <div class="text-3xl font-bold mb-3"><?= (int)$ordersCount ?></div>
    <div class="flex gap-2">
      <a class="px-3 py-2 rounded-xl border" href="<?= e(base_url('admin/' . $slug . '/orders')) ?>">Ver pedidos</a>
      <a class="px-3 py-2 rounded-xl border" href="<?= e(base_url('admin/' . $slug . '/orders/create')) ?>">+ Novo</a>
    </div>
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
