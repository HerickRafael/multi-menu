<?php
$title = "Categorias - " . ($company['name'] ?? '');
$slug = rawurlencode($company['slug']);
ob_start(); ?>
<header class="flex items-center gap-3 mb-4">
  <h1 class="text-2xl font-bold">Categorias</h1>
    <a href="<?= e(base_url('admin/' . $slug . '/categories/create')) ?>" class="ml-auto px-3 py-2 rounded-xl border">+ Nova</a>
    <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>" class="px-3 py-2 rounded-xl border">Dashboard</a>
</header>

<table class="w-full bg-white border rounded-2xl overflow-hidden">
  <thead class="bg-slate-100">
    <tr>
      <th class="text-left p-3">Nome</th>
      <th class="text-left p-3">Ordem</th>
      <th class="text-left p-3">Ativa</th>
      <th class="p-3"></th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($cats as $c): ?>
    <tr class="border-t">
      <td class="p-3"><?= e($c['name']) ?></td>
      <td class="p-3"><?= (int)$c['sort_order'] ?></td>
      <td class="p-3"><?= $c['active'] ? 'Sim' : 'Não' ?></td>
      <td class="p-3 text-right">
          <a class="px-3 py-1 border rounded-xl" href="<?= e(base_url('admin/' . $slug . '/categories/' . (int)$c['id'] . '/edit')) ?>">Editar</a>
          <form method="post" action="<?= e(base_url('admin/' . $slug . '/categories/' . (int)$c['id'] . '/del')) ?>" class="inline" onsubmit="return confirm('Excluir categoria?');">
          <button class="px-3 py-1 border rounded-xl">Excluir</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php
$content = ob_get_clean(); include __DIR__ . '/../layout.php';
