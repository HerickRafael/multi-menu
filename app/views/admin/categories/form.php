<?php
$title = "Categoria - " . ($company['name'] ?? '');
$editing = !empty($cat['id']);
$slug = rawurlencode($company['slug']);
$action = $editing ? 'admin/' . $slug . '/categories/' . (int)$cat['id'] : 'admin/' . $slug . '/categories';
ob_start(); ?>
<h1 class="text-2xl font-bold mb-4"><?= $editing ? 'Editar' : 'Nova' ?> Categoria</h1>
  <form method="post" action="<?= e(base_url($action)) ?>" class="grid gap-3 max-w-lg bg-white p-4 rounded-2xl border">
  <label class="grid gap-1">
    <span class="text-sm">Nome</span>
    <input name="name" value="<?= e($cat['name'] ?? '') ?>" class="border rounded-xl p-2">
  </label>
  <label class="grid gap-1">
    <span class="text-sm">Ordem</span>
    <input name="sort_order" type="number" value="<?= e($cat['sort_order'] ?? 0) ?>" class="border rounded-xl p-2">
  </label>
  <label class="inline-flex items-center gap-2">
    <input type="checkbox" name="active" <?= !isset($cat['active']) || $cat['active'] ? 'checked' : '' ?>>
    <span>Ativa</span>
  </label>
  <div class="flex gap-2">
    <button class="px-4 py-2 rounded-xl border">Salvar</button>
      <a href="<?= e(base_url('admin/' . $slug . '/categories')) ?>" class="px-4 py-2 rounded-xl border">Cancelar</a>
  </div>
</form>
<?php
$content = ob_get_clean(); include __DIR__ . '/../layout.php';
