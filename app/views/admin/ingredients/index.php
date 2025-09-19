<?php
$title = "Ingredientes - " . ($company['name'] ?? '');
$slug = rawurlencode($company['slug']);
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
      <th class="text-left p-3">Produto</th>
      <th class="p-3"></th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($items as $item): ?>
    <tr class="border-t">
      <td class="p-3"><?= e($item['name']) ?></td>
      <td class="p-3"><?= e($item['product_name'] ?? '') ?></td>
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
$content = ob_get_clean(); include __DIR__ . '/../layout.php';
