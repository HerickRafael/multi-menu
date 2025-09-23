<?php
// admin/ingredients/form.php — Formulário de ingrediente (versão moderna com toolbar fixa)

$title   = "Ingrediente - " . ($company['name'] ?? '');
$editing = !empty($ingredient['id']);
$slug    = rawurlencode((string)($company['slug'] ?? ''));
$action  = $editing
  ? "admin/{$slug}/ingredients/" . (int)($ingredient['id'] ?? 0)
  : "admin/{$slug}/ingredients";

$image = $ingredient['image_path'] ?? null;

if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$unitOptions = [
  ['value' => 'un', 'label' => 'Unidade (un)'],
  ['value' => 'kg', 'label' => 'Quilo (kg)'],
  ['value' => 'g',  'label' => 'Grama (g)'],
  ['value' => 'mg', 'label' => 'Miligrama (mg)'],
  ['value' => 'l',  'label' => 'Litro (L)'],
  ['value' => 'ml', 'label' => 'Mililitro (mL)'],
  ['value' => 'pc', 'label' => 'Peça (pc)'],
];

$unitLabelMap = ['un'=>'unidade','kg'=>'kg','g'=>'g','mg'=>'mg','l'=>'litro','ml'=>'mililitro','pc'=>'peça'];

$unitRaw = trim((string)($ingredient['unit'] ?? ''));
$unitSelectValue = '';
foreach ($unitOptions as $opt) { if (strcasecmp($unitRaw, $opt['value']) === 0) { $unitSelectValue = $opt['value']; break; } }
$unitCustomValue = '';
if ($unitSelectValue === '') { if ($unitRaw !== '') { $unitSelectValue = 'custom'; $unitCustomValue = $unitRaw; } }

$unitLabelDisplay = $unitSelectValue === 'custom'
  ? ($unitCustomValue !== '' ? $unitCustomValue : 'unidade')
  : ($unitLabelMap[$unitSelectValue] ?? ($unitSelectValue !== '' ? $unitSelectValue : 'unidade'));
$unitLabelDisplay = $unitLabelDisplay !== '' ? $unitLabelDisplay : 'unidade';
$unitValuePlaceholder = trim('Ex.: 1 ' . $unitLabelDisplay);

$costVal = $ingredient['cost'] ?? '';
if ($costVal !== '' && !is_string($costVal)) { $costVal = number_format((float)$costVal, 2, ',', '.'); }
$saleVal = $ingredient['sale_price'] ?? '';
if ($saleVal !== '' && !is_string($saleVal)) { $saleVal = number_format((float)$saleVal, 2, ',', '.'); }
$unitValueVal = $ingredient['unit_value'] ?? '';
if ($unitValueVal !== '' && !is_string($unitValueVal)) { $unitValueVal = rtrim(rtrim(number_format((float)$unitValueVal, 3, ',', '.'), '0'), ','); }

ob_start(); ?>

<!-- ALERTA DE ERRO -->
<?php if (!empty($error)): ?>
  <div class="mb-4 rounded-xl border border-red-200 bg-red-50/90 p-3 text-sm text-red-800 shadow-sm">
    <?= e($error) ?>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data"
      action="<?= e(base_url($action)) ?>"
      class="relative grid max-w-3xl gap-6 rounded-2xl border border-slate-200 bg-white p-4 md:p-6 shadow-sm">

  <!-- CSRF / METHOD -->
  <?php if (function_exists('csrf_field')): ?>
    <?= csrf_field() ?>
  <?php elseif (function_exists('csrf_token')): ?>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php endif; ?>
  <?php if ($editing): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

  <!-- TOOLBAR FIXA (igual ao bloco de Produto) -->
  <div class="sticky top-0 z-20 -m-4 mb-0 border-b bg-white/85 px-4 py-2 backdrop-blur supports-[backdrop-filter]:bg-white/60">
    <div class="mx-auto flex max-w-3xl items-center justify-between">
      <div class="flex items-center gap-2 text-sm text-slate-800">
        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100">
          <svg class="h-4 w-4 text-slate-600" viewBox="0 0 24 24" fill="none">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </span>
        <strong><?= $editing ? 'Editar' : 'Novo' ?> ingrediente</strong>
      </div>
      <div class="flex gap-2">
        <a href="<?= e(base_url("admin/{$slug}/ingredients")) ?>"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
            <path d="M15 6 9 12l6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          Cancelar
        </a>
        <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-1.5 text-sm font-medium text-white shadow hover:opacity-95">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
            <path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Salvar
        </button>
      </div>
    </div>
  </div>

  <!-- CARD: Dados do ingrediente -->
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 8h12M6 12h8M6 16h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Dados do ingrediente
    </legend>

    <label class="grid gap-1 mb-3">
      <span class="text-sm text-slate-700">Nome <span class="text-red-500">*</span></span>
      <input name="name" value="<?= e($ingredient['name'] ?? '') ?>" required autocomplete="off"
             class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400">
    </label>

    <div class="grid gap-3 md:grid-cols-2">
      <label class="grid gap-1">
        <span class="text-sm text-slate-700">Custo <span class="text-red-500">*</span></span>
        <input type="text" name="cost" value="<?= e($costVal) ?>" inputmode="decimal" placeholder="Ex.: 3,50" required
               class="money-input rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
      </label>

      <label class="grid gap-1">
        <span class="text-sm text-slate-700">Valor de venda <span class="text-red-500">*</span></span>
        <input type="text" name="sale_price" value="<?= e($saleVal) ?>" inputmode="decimal" placeholder="Ex.: 5,90" required
               class="money-input rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
      </label>
    </div>

    <div class="mt-3 grid gap-3 md:grid-cols-2">
      <div class="grid gap-1">
        <span class="text-sm text-slate-700">Unidade de medida <span class="text-red-500">*</span></span>
        <div class="grid gap-2">
          <select name="unit_select" id="unit_select"
                  class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400" required>
            <option value="">Selecione</option>
            <?php foreach ($unitOptions as $opt): ?>
              <option value="<?= e($opt['value']) ?>" <?= $unitSelectValue === $opt['value'] ? 'selected' : '' ?>>
                <?= e($opt['label']) ?>
              </option>
            <?php endforeach; ?>
            <option value="custom" <?= $unitSelectValue === 'custom' ? 'selected' : '' ?>>Outra unidade…</option>
          </select>
          <input type="text" name="unit_custom" id="unit_custom" value="<?= e($unitCustomValue) ?>"
                 class="rounded-xl border border-slate-300 bg-white px-3 py-2 <?= $unitSelectValue === 'custom' ? '' : 'hidden' ?>"
                 placeholder="Informe a unidade" maxlength="30">
        </div>
      </div>

      <label class="grid gap-1">
        <span class="text-sm text-slate-700">Valor por <span id="unit_label" data-unit-label><?= e($unitLabelDisplay) ?></span> <span class="text-red-500">*</span></span>
        <input type="text" name="unit_value" id="unit_value" value="<?= e($unitValueVal) ?>" inputmode="decimal"
               placeholder="<?= e($unitValuePlaceholder) ?>" required
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
      </label>
    </div>
  </fieldset>

  <!-- CARD: Imagem -->
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Imagem (opcional)
    </legend>

    <div class="grid items-start gap-3 md:grid-cols-[1fr_auto]">
      <div class="grid gap-2">
        <label for="image" class="text-sm text-slate-700">Upload (jpg/png/webp)</label>
        <label class="inline-flex w-fit cursor-pointer items-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
          <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.webp" class="hidden">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          Selecionar arquivo
        </label>
        <small class="text-xs text-slate-500">Recomendado: 800×800px quadrado. Máx. 5 MB.</small>
      </div>

      <div class="flex flex-col items-center gap-2">
        <span class="text-xs text-slate-500">Pré-visualização</span>
        <img id="image-preview"
             src="<?= $image ? e(base_url($image)) : e(base_url('assets/logo-placeholder.png')) ?>"
             class="h-20 w-20 rounded-xl border border-slate-200 object-cover shadow-sm" alt="Pré-visualização">
      </div>
    </div>
  </fieldset>

<!-- JS: unidade dinâmica, máscara simples e preview -->
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
    if (sel && Object.prototype.hasOwnProperty.call(labelMap, sel)) return labelMap[sel] || sel;
    return sel !== '' ? sel : 'unidade';
  }
  function syncUnit(){
    const isCustom = (select?.value === 'custom');
    if (custom) {
      custom.classList.toggle('hidden', !isCustom);
      isCustom ? custom.setAttribute('required','required') : custom.removeAttribute('required');
    }
    const u = resolveLabel();
    if (labelEl) labelEl.textContent = u;
    if (valueInput) valueInput.setAttribute('placeholder', ('Ex.: 1 ' + u).trim());
  }
  select?.addEventListener('change', syncUnit);
  custom?.addEventListener('input', syncUnit);
  syncUnit();

  function toMoneyBR(raw){
    let s = String(raw || '').replace(/\D+/g,'');
    if (!s) return '';
    if (s.length === 1) s = '0' + s;
    s = s.replace(/^0+(\d)/, '$1');
    const int = s.slice(0, -2) || '0';
    const dec = s.slice(-2);
    const intFmt = int.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return intFmt + ',' + dec;
  }
  document.querySelectorAll('.money-input').forEach(inp=>{
    inp.addEventListener('input', ()=>{
      const digits = inp.value.replace(/\D+/g,'');
      inp.value = toMoneyBR(digits);
    });
    inp.addEventListener('focus', ()=> inp.select());
  });

  const file = document.getElementById('image');
  const prev = document.getElementById('image-preview');
  file?.addEventListener('change', ()=>{
    const f = file.files?.[0];
    if (!f) return;
    const ok = /image\/(png|jpe?g|webp)/i.test(f.type);
    if (!ok) { alert('Formato inválido. Use JPG, PNG ou WEBP.'); file.value=''; return; }
    const reader = new FileReader();
    reader.onload = e => { prev.src = e.target.result; };
    reader.readAsDataURL(f);
  });
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
