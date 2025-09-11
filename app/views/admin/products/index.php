<?php
$title = "Produtos - " . ($company['name'] ?? '');
$slug = rawurlencode($company['slug']);
ob_start(); ?>
<header class="flex items-center gap-3 mb-4">
  <h1 class="text-2xl font-bold">Produtos</h1>
    <a href="<?= e(base_url('admin/' . $slug . '/products/create')) ?>" class="ml-auto px-3 py-2 rounded-xl border">+ Novo</a>
    <a href="<?= e(base_url('admin/' . $slug . '/categories')) ?>" class="px-3 py-2 rounded-xl border">Categorias</a>
    <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>" class="px-3 py-2 rounded-xl border">Dashboard</a>
</header>

<?php if (!empty($error)): ?>
  <div class="mb-3 p-3 bg-red-100 text-red-800 rounded-xl"><?= e($error) ?></div>
<?php endif; ?>

<form method="get" class="mb-3">
  <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Buscar por nome/descrição" class="w-full border rounded-xl px-3 py-2">
</form>

<table class="w-full bg-white border rounded-2xl overflow-hidden">
  <thead class="bg-slate-100">
    <tr>
      <th class="text-left p-3">Imagem</th>
      <th class="text-left p-3">Nome</th>
      <th class="text-left p-3">Categoria</th>
      <th class="text-left p-3">Preço</th>
      <th class="text-left p-3">Promo</th>
      <th class="text-left p-3">Ativo</th>
      <th class="p-3"></th>
    </tr>
  </thead>
  <tbody>
  <?php
    $byId = []; foreach ($cats as $c){ $byId[$c['id']]=$c['name']; }
    foreach ($items as $p): ?>
    <tr class="border-t">
      <td class="p-3">
        <?php if ($p['image']): ?>
          <img src="<?= base_url($p['image']) ?>" class="w-12 h-12 object-cover rounded-lg">
        <?php endif; ?>
      </td>
      <td class="p-3"><?= e($p['name']) ?></td>
      <td class="p-3"><?= e($byId[$p['category_id']] ?? '-') ?></td>
      <td class="p-3">R$ <?= number_format($p['price'],2,',','.') ?></td>
      <td class="p-3"><?= $p['promo_price'] ? 'R$ '.number_format($p['promo_price'],2,',','.') : '-' ?></td>
      <td class="p-3"><?= $p['active'] ? 'Sim' : 'Não' ?></td>
      <td class="p-3 text-right">
          <a class="px-3 py-1 border rounded-xl" href="<?= e(base_url('admin/' . $slug . '/products/' . (int)$p['id'] . '/edit')) ?>">Editar</a>
          <form method="post" action="<?= e(base_url('admin/' . $slug . '/products/' . (int)$p['id'] . '/del')) ?>" class="inline" onsubmit="return confirm('Excluir produto?');">
          <button class="px-3 py-1 border rounded-xl">Excluir</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php
$content = ob_get_clean(); include __DIR__ . '/../layout.php';
