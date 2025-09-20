<?php
$title   = "Ingrediente - " . ($company['name'] ?? '');
$editing = !empty($ingredient['id']);
$slug    = rawurlencode($company['slug'] ?? '');
$action  = $editing
  ? 'admin/' . $slug . '/ingredients/' . (int)($ingredient['id'] ?? 0)
  : 'admin/' . $slug . '/ingredients';

$image = $ingredient['image_path'] ?? null;
ob_start(); ?>
<h1 class="text-2xl font-bold mb-4"><?= $editing ? 'Editar' : 'Novo' ?> Ingrediente</h1>

<?php if (!empty($error)): ?>
  <div class="mb-3 p-3 bg-red-100 text-red-800 rounded-xl"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" action="<?= e(base_url($action)) ?>" class="grid gap-3 max-w-xl bg-white p-4 rounded-2xl border">
  <label class="grid gap-1">
    <span class="text-sm">Nome do ingrediente</span>
    <input name="name" value="<?= e($ingredient['name'] ?? '') ?>" class="border rounded-xl p-2" required>
  </label>

  <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
    <label class="grid gap-1">
      <span class="text-sm">Quantidade mínima</span>
      <input type="number" min="0" name="min_qty" value="<?= (int)($ingredient['min_qty'] ?? 0) ?>" class="border rounded-xl p-2" required>
    </label>
    <label class="grid gap-1">
      <span class="text-sm">Quantidade máxima</span>
      <input type="number" min="1" name="max_qty" value="<?= (int)($ingredient['max_qty'] ?? 1) ?>" class="border rounded-xl p-2" required>
    </label>
  </div>

  <div class="grid gap-2">
    <span class="text-sm">Foto</span>
    <div class="flex items-center gap-3">
      <label class="inline-flex items-center px-3 py-2 border rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100">
        <input type="file" name="image" accept="image/*" class="hidden">
        <span>Enviar imagem</span>
      </label>
      <?php if ($image): ?>
        <img src="<?= e(base_url($image)) ?>" alt="" class="w-14 h-14 rounded-full object-cover border">
      <?php else: ?>
        <span class="text-xs text-slate-500">Sem imagem</span>
      <?php endif; ?>
    </div>
    <p class="text-xs text-slate-500">Formatos aceitos: JPG, PNG ou WEBP.</p>
  </div>

  <div class="flex gap-2">
    <button class="px-4 py-2 rounded-xl border">Salvar</button>
    <a href="<?= e(base_url('admin/' . $slug . '/ingredients')) ?>" class="px-4 py-2 rounded-xl border">Cancelar</a>
  </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
