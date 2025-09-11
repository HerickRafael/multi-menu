<?php
$title = "Produto - " . ($company['name'] ?? '');
$editing = !empty($p['id']);
$slug = rawurlencode($company['slug']);
$action = $editing ? 'admin/' . $slug . '/products/' . (int)$p['id'] : 'admin/' . $slug . '/products';
ob_start(); ?>
<h1 class="text-2xl font-bold mb-4"><?= $editing ? 'Editar' : 'Novo' ?> Produto</h1>
  <form method="post" action="<?= e(base_url($action)) ?>" enctype="multipart/form-data" class="grid gap-3 max-w-2xl bg-white p-4 rounded-2xl border">
  <label class="grid gap-1">
    <span class="text-sm">Categoria</span>
    <select name="category_id" class="border rounded-xl p-2">
      <option value="">— sem categoria —</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= isset($p['category_id']) && (int)$p['category_id']===(int)$c['id'] ? 'selected':'' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label class="grid gap-1">
    <span class="text-sm">Nome</span>
    <input name="name" value="<?= e($p['name'] ?? '') ?>" class="border rounded-xl p-2">
  </label>

  <label class="grid gap-1">
    <span class="text-sm">Descrição</span>
    <textarea name="description" rows="3" class="border rounded-xl p-2"><?= e($p['description'] ?? '') ?></textarea>
  </label>

  <label class="inline-flex items-center gap-2">
    <input type="checkbox" id="ingredients-toggle" <?= !empty($ingredients) ? 'checked' : '' ?>>
    <span>Ingredientes</span>
  </label>
  <div id="ingredients-block" class="grid gap-2 <?= empty($ingredients) ? 'hidden' : '' ?>">
    <div id="ingredients-container" class="grid gap-2">
      <?php foreach ($ingredients as $ing): ?>
        <input name="ingredients[]" value="<?= e($ing['name']) ?>" class="border rounded-xl p-2" placeholder="Ingrediente">
      <?php endforeach; ?>
    </div>
    <button type="button" id="add-ingredient" class="px-2 py-1 border rounded-lg text-sm">+ Ingrediente</button>
  </div>

  <div class="grid md:grid-cols-3 gap-3">
    <label class="grid gap-1">
      <span class="text-sm">Preço</span>
      <input name="price" type="number" step="0.01" value="<?= e($p['price'] ?? 0) ?>" class="border rounded-xl p-2">
    </label>
    <label class="grid gap-1">
      <span class="text-sm">Preço promocional</span>
      <input name="promo_price" type="number" step="0.01" value="<?= e($p['promo_price'] ?? '') ?>" class="border rounded-xl p-2" placeholder="opcional">
    </label>
    <label class="grid gap-1">
      <span class="text-sm">Ordem</span>
      <input name="sort_order" type="number" value="<?= e($p['sort_order'] ?? 0) ?>" class="border rounded-xl p-2">
    </label>
  </div>

  <div class="grid md:grid-cols-2 gap-3">
    <label class="grid gap-1">
      <span class="text-sm">SKU</span>
      <input name="sku" value="<?= e($p['sku'] ?? '') ?>" class="border rounded-xl p-2">
    </label>
    <label class="grid gap-1">
      <span class="text-sm">Imagem (jpg/png/webp)</span>
      <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="border rounded-xl p-2">
      <?php if (!empty($p['image'])): ?>
        <img src="<?= base_url($p['image']) ?>" class="w-24 h-24 object-cover rounded-lg mt-2">
      <?php endif; ?>
    </label>
  </div>

  <label class="inline-flex items-center gap-2">
    <input type="checkbox" name="active" <?= !isset($p['active']) || $p['active'] ? 'checked' : '' ?>>
    <span>Ativo</span>
  </label>

  <div class="flex gap-2">
    <button class="px-4 py-2 rounded-xl border">Salvar</button>
      <a href="<?= e(base_url('admin/' . $slug . '/products')) ?>" class="px-4 py-2 rounded-xl border">Cancelar</a>
  </div>
  <script>
    const toggle = document.getElementById('ingredients-toggle');
    const block = document.getElementById('ingredients-block');
    const container = document.getElementById('ingredients-container');
    document.getElementById('add-ingredient').addEventListener('click', () => {
      const input = document.createElement('input');
      input.name = 'ingredients[]';
      input.placeholder = 'Ingrediente';
      input.className = 'border rounded-xl p-2';
      container.appendChild(input);
    });
    toggle.addEventListener('change', () => {
      block.classList.toggle('hidden', !toggle.checked);
    });
  </script>
</form>
<?php
$content = ob_get_clean(); include __DIR__ . '/../layout.php';
