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
  <a href="<?= e(base_url('admin/' . $slug . '/settings')) ?>"    class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50">⚙️ Geral</a>
  <a href="<?= e(base_url('admin/' . $slug . '/categories')) ?>"  class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50">🗂️ Categorias</a>
  <a href="<?= e(base_url('admin/' . $slug . '/products')) ?>"    class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50">🧾 Produtos</a>
  <a href="<?= e(base_url('admin/' . $slug . '/ingredients')) ?>" class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50">🥕 Ingredientes</a>
  <a href="<?= e(base_url('admin/' . $slug . '/orders')) ?>"      class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50">📦 Pedidos</a>
  <a href="<?= e(base_url($publicSlug)) ?>" target="_blank"       class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50">🔗 Ver cardápio</a>
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
    <div class="text-3xl font-bold mb-3">📦</div>
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
          <?php if (!empty($ing['product_names'])): ?>
            <span class="text-xs text-gray-500">
              (<?= e(is_array($ing['product_names']) ? implode(', ', $ing['product_names']) : (string)$ing['product_names']) ?>)
            </span>
          <?php endif; ?>
          <?php if (!empty($ing['product_name'])): /* fallback p/ chave singular se existir */ ?>
            <span class="text-xs text-gray-500">(<?= e($ing['product_name']) ?>)</span>
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
