<?php
$title   = "Ingrediente - " . ($company['name'] ?? '');
$editing = !empty($ingredient['id']);
$slug    = rawurlencode($company['slug'] ?? '');
$action  = $editing
  ? 'admin/' . $slug . '/ingredients/' . (int)($ingredient['id'] ?? 0)
  : 'admin/' . $slug . '/ingredients';

$image = $ingredient['image_path'] ?? null;
$imageSrc = null;
if (!empty($image)) {
  $imageFile = basename((string)$image);
  if ($imageFile !== '' && $imageFile !== '.' && $imageFile !== '..') {
    $imageSrc = base_url('uploads/' . $imageFile);
  }
}

$unitOptions = [
  ['value' => 'un', 'label' => 'Unidade (un)'],
  ['value' => 'kg', 'label' => 'Quilo (kg)'],
  ['value' => 'g',  'label' => 'Grama (g)'],
  ['value' => 'mg', 'label' => 'Miligrama (mg)'],
  ['value' => 'l',  'label' => 'Litro (L)'],
  ['value' => 'ml', 'label' => 'Mililitro (mL)'],
  ['value' => 'pc', 'label' => 'Peça (pc)'],
];

$unitLabelMap = [
  'un' => 'unidade',
  'kg' => 'kg',
  'g'  => 'g',
  'mg' => 'mg',
  'l'  => 'litro',
  'ml' => 'mililitro',
  'pc' => 'peça',
];

$unitRaw = trim((string)($ingredient['unit'] ?? ''));
$unitSelectValue = '';
foreach ($unitOptions as $opt) {
  if (strcasecmp($unitRaw, $opt['value']) === 0) {
    $unitSelectValue = $opt['value'];
    break;
  }
}
$unitCustomValue = '';
if ($unitSelectValue === '') {
  if ($unitRaw !== '') {
    $unitSelectValue = 'custom';
    $unitCustomValue = $unitRaw;
  }
} else {
  $unitCustomValue = '';
}

$unitLabelDisplay = $unitSelectValue === 'custom'
  ? ($unitCustomValue !== '' ? $unitCustomValue : 'unidade')
  : ($unitLabelMap[$unitSelectValue] ?? ($unitSelectValue !== '' ? $unitSelectValue : 'unidade'));
$unitLabelDisplay = $unitLabelDisplay !== '' ? $unitLabelDisplay : 'unidade';
$unitValuePlaceholder = trim('Ex.: 1 ' . $unitLabelDisplay);
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

  <?php
    $costVal = $ingredient['cost'] ?? '';
    if ($costVal !== '' && !is_string($costVal)) {
      $costVal = number_format((float)$costVal, 2, ',', '.');
    }
    $saleVal = $ingredient['sale_price'] ?? '';
    if ($saleVal !== '' && !is_string($saleVal)) {
      $saleVal = number_format((float)$saleVal, 2, ',', '.');
    }
    $unitValueVal = $ingredient['unit_value'] ?? '';
    if ($unitValueVal !== '' && !is_string($unitValueVal)) {
      $unitValueVal = rtrim(rtrim(number_format((float)$unitValueVal, 3, ',', '.'), '0'), ',');
    }
  ?>

  <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
    <label class="grid gap-1">
      <span class="text-sm">Custo <span class="text-red-500">*</span></span>
      <input type="text" name="cost" value="<?= e($costVal) ?>" class="border rounded-xl p-2" inputmode="decimal" placeholder="Ex.: 3,50" required>
    </label>
    <label class="grid gap-1">
      <span class="text-sm">Valor de venda <span class="text-red-500">*</span></span>
      <input type="text" name="sale_price" value="<?= e($saleVal) ?>" class="border rounded-xl p-2" inputmode="decimal" placeholder="Ex.: 5,90" required>
    </label>
  </div>

  <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
    <div class="grid gap-1">
      <span class="text-sm">Unidade de medida <span class="text-red-500">*</span></span>
      <div class="grid gap-2">
        <select name="unit_select" id="unit_select" class="border rounded-xl p-2" required>
          <option value="">Selecione</option>
          <?php foreach ($unitOptions as $opt): ?>
            <option value="<?= e($opt['value']) ?>" <?= $unitSelectValue === $opt['value'] ? 'selected' : '' ?>><?= e($opt['label']) ?></option>
          <?php endforeach; ?>
          <option value="custom" <?= $unitSelectValue === 'custom' ? 'selected' : '' ?>>Outra unidade…</option>
        </select>
        <input
          type="text"
          name="unit_custom"
          id="unit_custom"
          value="<?= e($unitCustomValue) ?>"
          class="border rounded-xl p-2 <?= $unitSelectValue === 'custom' ? '' : 'hidden' ?>"
          placeholder="Informe a unidade"
          maxlength="30"
        >
      </div>
    </div>
    <label class="grid gap-1">
      <span class="text-sm">Valor por <span id="unit_label" data-unit-label><?= e($unitLabelDisplay) ?></span> <span class="text-red-500">*</span></span>
      <input
        type="text"
        name="unit_value"
        id="unit_value"
        value="<?= e($unitValueVal) ?>"
        class="border rounded-xl p-2"
        inputmode="decimal"
        placeholder="<?= e($unitValuePlaceholder) ?>"
        required
      >
    </label>
  </div>

  <div class="grid gap-2">
    <span class="text-sm">Foto</span>
    <div class="flex items-center gap-3">
      <label class="inline-flex items-center px-3 py-2 border rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100">
        <input type="file" name="image" accept="image/*" class="hidden">
        <span>Enviar imagem</span>
      </label>
      <?php if ($imageSrc): ?>
        <img src="<?= e($imageSrc) ?>" alt="" class="w-14 h-14 rounded-full object-cover border">
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

  <script>
    (function(){
      const select = document.getElementById('unit_select');
      const custom = document.getElementById('unit_custom');
      const labelEl = document.getElementById('unit_label');
      const valueInput = document.getElementById('unit_value');
      const labelMap = <?= json_encode($unitLabelMap, JSON_UNESCAPED_UNICODE) ?>;

      function resolveLabel(){
        const sel = select?.value || '';
        if (sel === 'custom') {
          const customVal = (custom?.value || '').trim();
          return customVal !== '' ? customVal : 'unidade';
        }
        if (sel && Object.prototype.hasOwnProperty.call(labelMap, sel)) {
          return labelMap[sel] || sel;
        }
        return sel !== '' ? sel : 'unidade';
      }

      function sync(){
        if (custom) {
          const isCustom = select?.value === 'custom';
          custom.classList.toggle('hidden', !isCustom);
          if (isCustom) {
            custom.setAttribute('required', 'required');
          } else {
            custom.removeAttribute('required');
          }
        }

        const unitText = resolveLabel();
        if (labelEl) {
          labelEl.textContent = unitText;
        }
        if (valueInput) {
          valueInput.setAttribute('placeholder', ('Ex.: 1 ' + unitText).trim());
        }
      }

      select?.addEventListener('change', sync);
      custom?.addEventListener('input', sync);
      sync();
    })();
  </script>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
