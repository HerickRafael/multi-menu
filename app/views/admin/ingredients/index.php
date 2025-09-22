<?php
// (opcional) helpers caso a view seja renderizada isolada
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('base_url')) {
  function base_url($p=''){
    $b = rtrim($_SERVER['BASE_URL'] ?? '/', '/');
    return $b . '/' . ltrim((string)$p, '/');
  }
}
if (!function_exists('price_br')) {
  function price_br($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
}

$title = "Ingredientes - " . ($company['name'] ?? '');
$slug = rawurlencode($company['slug'] ?? '');
$selectedProduct = $productId ?? null;
$search = $q ?? '';
ob_start(); ?>
<header class="flex items-center gap-3 mb-4">
  <h1 class="text-2xl font-bold">Ingredientes</h1>
  <a href="<?= e(base_url('admin/' . $slug . '/ingredients/create')) ?>" class="ml-auto px-3 py-2 rounded-xl border">+ Novo</a>
  <a href="<?= e(base_url('admin/' . $slug . '/products')) ?>" class="px-3 py-2 rounded-xl border">Produtos</a>
  <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>" class="px-3 py-2 rounded-xl border">Dashboard</a>
</header>

<?php if (!empty($error)): ?>
  <div class="mb-3 p-3 bg-red-100 text-red-800 rounded-xl"><?= e($error) ?></div>
<?php endif; ?>

<form method="get" class="mb-4 grid gap-2 md:flex md:items-center md:gap-3">
  <select name="product_id" class="border rounded-xl px-3 py-2">
    <option value="">Todos os produtos</option>
    <?php foreach ($products as $p): ?>
      <option value="<?= (int)$p['id'] ?>" <?= $selectedProduct === (int)$p['id'] ? 'selected' : '' ?>>
        <?= e($p['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar por nome" class="border rounded-xl px-3 py-2 flex-1">
  <button class="px-4 py-2 rounded-xl border bg-white">Filtrar</button>
</form>

<?php if (count($items)): ?>
<table class="w-full bg-white border rounded-2xl overflow-hidden">
  <thead class="bg-slate-100">
    <tr>
      <th class="text-left p-3">Ingrediente</th>
      <th class="text-left p-3">Custo</th>
      <th class="text-left p-3">Valor de venda</th>
      <th class="text-left p-3">Unidade</th>
      <th class="text-left p-3">Produtos</th>
      <th class="p-3"></th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($items as $item): ?>
    <tr class="border-t">
      <td class="p-3">
        <div class="flex items-center gap-3">
          <?php
            $ingredientImageShown = false;
            if (!empty($item['image_path'])) {
              $ingredientImgFile = basename((string)$item['image_path']);
              if ($ingredientImgFile !== '' && $ingredientImgFile !== '.' && $ingredientImgFile !== '..') {
                $ingredientImgSrc = base_url('uploads/' . $ingredientImgFile);
                $ingredientImageShown = true;
          ?>
            <img src="<?= e($ingredientImgSrc) ?>" alt="" class="w-10 h-10 rounded-full object-cover">
          <?php
              }
            }
            if (!$ingredientImageShown):
          ?>
            <div class="w-10 h-10 rounded-full bg-slate-200 grid place-items-center text-slate-500 text-xs">IMG</div>
          <?php endif; ?>
          <div>
            <div class="font-medium"><?= e($item['name'] ?? '') ?></div>
            <?php $created = !empty($item['created_at']) ? date('d/m/Y', strtotime($item['created_at'])) : null; ?>
            <?php if ($created): ?>
              <div class="text-xs text-slate-500">Criado em <?= e($created) ?></div>
            <?php endif; ?>
          </div>
        </div>
      </td>

      <td class="p-3 text-sm text-slate-600"><?= price_br($item['cost'] ?? 0) ?></td>
      <td class="p-3 text-sm text-slate-600"><?= price_br($item['sale_price'] ?? 0) ?></td>
      <td class="p-3 text-sm text-slate-600">
        <?php
          $uVal = $item['unit_value'] ?? null;
          $uTxt = $item['unit'] ?? '';
          if ($uVal !== null && $uVal !== '') {
            if (!is_string($uVal)) {
              $uVal = rtrim(rtrim(number_format((float)$uVal, 3, ',', '.'), '0'), ',');
            }
          }
        ?>
        <?php
          $unitDisplay = ($uVal !== null && $uVal !== '' ? $uVal : '1') . ' ' . (string)$uTxt;
          $unitDisplay = trim($unitDisplay);
        ?>
        <?= e($unitDisplay) ?>
      </td>

      <td class="p-3">
        <?php
          // Aceita tanto array quanto string com separador '||'
          $pnRaw = $item['product_names'] ?? null;
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
          <div class="flex flex-wrap gap-1">
            <?php foreach ($pn as $prodName): $prodName = trim((string)$prodName); if ($prodName === '') continue; ?>
              <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs text-amber-700">
                <?= e($prodName) ?>
              </span>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <span class="text-xs text-slate-400">Não vinculado</span>
        <?php endif; ?>
      </td>

      <td class="p-3 text-right">
        <a class="px-3 py-1 border rounded-xl" href="<?= e(base_url('admin/' . $slug . '/ingredients/' . (int)$item['id'] . '/edit')) ?>">Editar</a>
        <form method="post" action="<?= e(base_url('admin/' . $slug . '/ingredients/' . (int)$item['id'] . '/del')) ?>" class="inline" onsubmit="return confirm('Excluir ingrediente?');">
          <button class="px-3 py-1 border rounded-xl">Excluir</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php else: ?>
  <div class="p-4 bg-white border rounded-2xl text-sm text-gray-600">Nenhum ingrediente encontrado.</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
