<?php
$title = "Ingrediente - " . ($company['name'] ?? '');
$editing = !empty($ingredient['id']);
$slug = rawurlencode($company['slug']);
$action = $editing ? 'admin/' . $slug . '/ingredients/' . (int)$ingredient['id'] : 'admin/' . $slug . '/ingredients';
ob_start(); ?>
<h1 class="text-2xl font-bold mb-4"><?= $editing ? 'Editar' : 'Novo' ?> Ingrediente</h1>

<?php if (!empty($error)): ?>
  <div class="mb-3 p-3 bg-red-100 text-red-800 rounded-xl"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" action="<?= e(base_url($action)) ?>" class="grid gap-3 max-w-xl bg-white p-4 rounded-2xl border">
  <label class="grid gap-1">
    <span class="text-sm">Produto</span>
    <select name="product_id" class="border rounded-xl p-2" required>
      <option value="">Selecione</option>
      <?php foreach ($products as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= (int)($ingredient['product_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
          <?= e($p['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label class="grid gap-1">
    <span class="text-sm">Nome do ingrediente</span>
    <input name="name" value="<?= e($ingredient['name'] ?? '') ?>" class="border rounded-xl p-2" required>
  </label>

  <div class="flex gap-2">
    <button class="px-4 py-2 rounded-xl border">Salvar</button>
      <a href="<?= e(base_url('admin/' . $slug . '/ingredients')) ?>" class="px-4 py-2 rounded-xl border">Cancelar</a>
  </div>
</form>
<?php
$content = ob_get_clean(); include __DIR__ . '/../layout.php';
