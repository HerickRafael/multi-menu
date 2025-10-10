<?php
// admin/products/form.php — Formulário de produtos (cards + drag)

// ===== Guard rails / Vars padrão =====
$p              = $p              ?? [];
$company        = $company        ?? [];
$cats           = $cats           ?? [];
$groups         = $groups         ?? [];           // COMBO
$simpleProducts = $simpleProducts ?? [];           // p/ combos
$ingredients    = $ingredients    ?? [];
$errors         = $errors         ?? [];

$simpleMap = [];

foreach ($simpleProducts as $spMeta) {
    $sid = isset($spMeta['id']) ? (int)$spMeta['id'] : null;

    if ($sid) {
        $simpleMap[$sid] = $spMeta;
    }
}

// Personalização
$customization  = $customization  ?? [];           // ['enabled'=>bool, 'groups'=>[...]]
$custEnabled    = !empty($customization['enabled']);
$custGroups     = $customization['groups'] ?? [];

// Título / Ação
$title   = 'Produto - ' . ($company['name'] ?? '');
$editing = !empty($p['id']);
$slug    = rawurlencode((string)($company['slug'] ?? ''));
$action  = $editing ? "admin/{$slug}/products/" . (int)$p['id'] : "admin/{$slug}/products";

if (!function_exists('e')) {
    function e($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}
?>
<?php ob_start(); ?>

<div class="mx-auto max-w-4xl p-4 space-y-4">

<!-- ERROS -->
<?php if (!empty($errors) && is_array($errors)): ?>
  <div class="rounded-xl border border-red-200 bg-red-50/90 p-3 text-sm text-red-800 shadow-sm">
    <strong class="mb-1 block">Por favor, corrija os campos abaixo:</strong>
    <ul class="list-disc space-y-0.5 pl-5">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form id="product-form"
      method="post"
      action="<?= e(base_url($action)) ?>"
      enctype="multipart/form-data"
      class="relative grid gap-6 rounded-2xl border border-slate-200 bg-white p-4 md:p-6 shadow-sm">

  <!-- CSRF / METHOD -->
  <?php if (function_exists('csrf_field')): ?>
    <?= csrf_field() ?>
  <?php elseif (function_exists('csrf_token')): ?>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php endif; ?>
  <?php if ($editing): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

  <!-- TOOLBAR FIXA -->
  <div class="form-toolbar sticky top-0 z-20 mb-0 border-b bg-white/85 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-white/60 sm:-m-4 sm:px-4 sm:py-2">
    <div class="mx-auto flex w-full max-w-4xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-2 text-sm text-slate-800">
        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100">
          <svg class="h-4 w-4 text-slate-600" viewBox="0 0 24 24" fill="none">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </span>
        <strong><?= $editing ? 'Editar' : 'Novo' ?> produto</strong>
      </div>
      <div class="form-toolbar-actions flex flex-col gap-2 sm:flex-row">
        <a href="<?= e(base_url("admin/{$slug}/products")) ?>"
           class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-sm hover:bg-slate-50 sm:w-auto">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 6 9 12l6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          Cancelar
        </a>
        <button type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl admin-gradient-bg px-4 py-1.5 text-sm font-medium text-white shadow hover:opacity-95 sm:w-auto">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Salvar
        </button>
      </div>
    </div>
  </div>

 <!-- CARD: Dados básicos -->
<fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
  <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
      <path d="M5 7h14M5 12h10M5 17h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
    </svg>
    Dados básicos
  </legend>

  <label for="category_id" class="grid gap-1 mb-3">
    <span class="text-sm text-slate-700">Categoria</span>
    <select name="category_id" id="category_id" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 focus:ring-2 focus:ring-indigo-400" aria-describedby="help-cat">
      <option value="">— sem categoria —</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= (isset($p['category_id']) && (int)$p['category_id'] === (int)$c['id']) ? 'selected' : '' ?>>
          <?= e($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <small id="help-cat" class="text-xs text-slate-500">Usado para agrupar o cardápio.</small>
  </label>

  <div class="grid gap-3 md:grid-cols-2">
    <label for="name" class="grid gap-1">
      <span class="text-sm text-slate-700">Nome <span class="text-red-500">*</span></span>
      <input required name="name" id="name" value="<?= e($p['name'] ?? '') ?>" autocomplete="off"
             class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400">
    </label>

    <label for="sku" class="grid gap-1">
      <span class="text-sm text-slate-700">SKU</span>
      <div class="sku-lock relative">
        <input name="sku" id="sku" value="<?= e($p['sku'] ?? '') ?>" placeholder="Gerado automaticamente" autocomplete="off"
               readonly
               class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 pr-12 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400">
<button type="button" 
  class="sku-lock-btn focus:outline-none focus:ring-0">
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-lock-fill" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M8 0a4 4 0 0 1 4 4v2.05a2.5 2.5 0 0 1 2 2.45v5a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 2 13.5v-5a2.5 2.5 0 0 1 2-2.45V4a4 4 0 0 1 4-4m0 1a3 3 0 0 0-3 3v2h6V4a3 3 0 0 0-3-3"/>
</svg>
  <span class="sku-lock-tooltip">Definido automaticamente em ordem crescente e sem repetições.</span>
</button>

      </div>
    </label>
  </div>
</fieldset>


  <!-- CARD: Tipo & Preço -->
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M7 12h10M12 7v10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Tipo & Preço
    </legend>

    <div class="grid gap-3 md:grid-cols-2">
      <label for="type" class="grid gap-1">
        <span class="text-sm text-slate-700">Tipo</span>
        <?php $ptype = $p['type'] ?? 'simple'; ?>
        <select name="type" id="type" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 focus:ring-2 focus:ring-indigo-400">
          <option value="simple" <?= $ptype === 'simple' ? 'selected' : '' ?>>Simples</option>
          <option value="combo"  <?= $ptype === 'combo' ? 'selected' : '' ?>>Combo</option>
        </select>
        <small class="text-xs text-slate-500">Combos usam Grupos. Simples têm Personalização.</small>
      </label>

      <label for="price_mode" class="grid gap-1">
        <span class="text-sm text-slate-700">Modo de preço</span>
        <?php $pmode = $p['price_mode'] ?? 'fixed'; ?>
        <select name="price_mode" id="price_mode" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 focus:ring-2 focus:ring-indigo-400">
          <option value="fixed" <?= $pmode === 'fixed' ? 'selected' : '' ?>>Fixo (preço base)</option>
          <option value="sum"   <?= $pmode === 'sum' ? 'selected' : '' ?>>Somar itens do grupo</option>
        </select>
        <small class="text-xs text-slate-500">Em “Somar itens”, total = <code class="rounded bg-slate-100 px-1">preço base + deltas</code>.</small>
      </label>
    </div>

    <div class="mt-3 grid gap-3 md:grid-cols-3">
      <label for="price" class="grid gap-1">
        <span class="text-sm text-slate-700">Preço base (R$)</span>
        <input name="price" id="price" type="number" step="0.01" min="0" value="<?= e($p['price'] ?? 0) ?>"
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400" inputmode="decimal" autocomplete="off">
      </label>
      <label for="promo_price" class="grid gap-1">
        <span class="text-sm text-slate-700">Preço promocional (R$)</span>
        <input name="promo_price" id="promo_price" type="number" step="0.01" min="0" value="<?= e($p['promo_price'] ?? '') ?>"
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400"
               placeholder="Opcional" inputmode="decimal" autocomplete="off">
      </label>
      <label for="sort_order" class="grid gap-1">
        <span class="text-sm text-slate-700">Ordem</span>
        <input name="sort_order" id="sort_order" type="number" step="1" value="<?= e($p['sort_order'] ?? 0) ?>"
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
      </label>
    </div>
  </fieldset>

  <!-- CARD: Descrição -->
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 7h14M5 12h14M5 17h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Descrição
    </legend>

    <label for="description" class="grid gap-2">
      <span class="text-sm text-slate-700">Conteúdo exibido na página do produto</span>
      <textarea name="description" id="description" rows="5" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400" placeholder="Ex.: Pão artesanal, burger 180g, queijo prato e molho especial."><?= e($p['description'] ?? '') ?></textarea>
      <div class="flex items-center justify-between text-xs text-slate-500">
        <span>Use este campo para destacar ingredientes, diferenciais ou modo de preparo.</span>
        <span id="description-counter"></span>
      </div>
    </label>
  </fieldset>

  <!-- CARD: Imagem -->
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Imagem
    </legend>

    <div class="grid items-start gap-3 md:grid-cols-[1fr_auto]">
      <?php $prodImagePreview = !empty($p['image']) ? e(base_url($p['image'])) : ''; ?>
      <label class="grid gap-1">
        <span class="text-sm text-slate-700">Upload (jpg/png/webp)</span>
        <div id="product-image-dropzone" class="rounded-xl border-2 border-dashed bg-white p-4 relative admin-primary-border" style="min-height:96px;">
          <input id="product-image-input" type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="sr-only">

          <div id="product-image-drop-hint" class="flex flex-col items-center justify-center text-center py-6">
            <div class="text-slate-600 mb-2">Arraste arquivos para cá ou se preferir</div>
            <button type="button" id="product-image-choose" class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm admin-primary-text admin-primary-border">anexar arquivos</button>
            <div class="text-xs text-slate-400 mt-2">Recomendado: 1000×750px ou maior (4:3). Máx. 5 MB.</div>
          </div>

          <img id="product-image-preview" <?= $prodImagePreview ? 'src="'.e($prodImagePreview).'"' : '' ?> alt="preview" class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 rounded-xl <?= $prodImagePreview === '' ? 'hidden' : '' ?>" style="max-width:calc(100% - 12px); max-height:calc(100% - 12px); width:auto; height:auto;" />

          <button type="button" id="product-image-clear" class="absolute top-3 right-3 <?= $prodImagePreview === '' ? 'hidden' : '' ?> rounded-full bg-white text-slate-700 shadow-sm px-2 py-0.5 border admin-primary-border">✕</button>
        </div>
      </label>
    </div>
    <script>
      (function(){
        function wireDropzoneLocal(opts){
          const dz = document.getElementById(opts.dropzone);
          const input = document.getElementById(opts.input);
          const preview = document.getElementById(opts.preview);
          const thumb = document.getElementById(opts.thumb);
          const choose = document.getElementById(opts.choose);
          const clearBtn = document.getElementById(opts.clear);
          const hint = dz ? dz.querySelector('[id$="-drop-hint"]') : null;
          if (!dz || !input) return;
            function showPreviewFile(file){
              try{
                const url = URL.createObjectURL(file);
                if (preview) { preview.setAttribute('src', url); preview.classList.remove('hidden'); }
                if (thumb) { thumb.src = url; }
                if (hint) hint.classList.add('hidden');
                if (clearBtn) clearBtn.classList.remove('hidden');
                if (preview) preview.onload = ()=>{ try{ URL.revokeObjectURL(url);}catch(_){} };
              }catch(_){}
            }
          function clearSelection(){ try{ input.value = ''; }catch(_){} if (preview){ preview.removeAttribute('src'); preview.classList.add('hidden'); } if (thumb){ thumb.src='<?= e(base_url('assets/logo-placeholder.png')) ?>'; } if (hint) hint.classList.remove('hidden'); if (clearBtn) clearBtn.classList.add('hidden'); }
          if (choose) choose.addEventListener('click', e=>{ e.preventDefault(); input.click(); });
          if (clearBtn) clearBtn.addEventListener('click', e=>{ e.preventDefault(); clearSelection(); });
          input.addEventListener('change', function(){ if (this.files && this.files.length>0) { showPreviewFile(this.files[0]); } else { clearSelection(); } });
          dz.addEventListener('dragover', function(e){ e.preventDefault(); dz.classList.add('opacity-80'); });
          dz.addEventListener('dragleave', function(e){ dz.classList.remove('opacity-80'); });
          dz.addEventListener('drop', function(e){ e.preventDefault(); dz.classList.remove('opacity-80'); const dt = e.dataTransfer; if (dt && dt.files && dt.files.length>0) { input.files = dt.files; input.dispatchEvent(new Event('change',{bubbles:true})); } });
        }
        wireDropzoneLocal({ dropzone:'product-image-dropzone', input:'product-image-input', preview:'product-image-preview', choose:'product-image-choose', clear:'product-image-clear' });
      })();
    </script>
  </fieldset>

  <!-- ===== DRAG ESTILOS ===== -->
  <style>
    .sku-lock-btn{
      position:absolute;
      right:.75rem;
      top:50%;
      transform:translateY(-50%);
      display:inline-flex;
      align-items:center;
      justify-content:center;
      height:2rem;
      width:2rem;
      border-radius:9999px;
      color:rgba(71,85,105,1);
      background-color:transparent;
      border:none;
      cursor:help;
      padding:0;
    }
    .sku-lock-btn:hover,
    .sku-lock-btn:focus{
      color:rgba(30,41,59,1);
    }
    .sku-lock-btn:focus{
      outline:2px solid rgba(99,102,241,.4);
      outline-offset:2px;
    }
    .sku-lock-tooltip{
      position:absolute;
      bottom:-0.5rem;
      right:2.5rem;
      transform:translateY(100%);
      display:none;
      max-width:16rem;
      padding:.5rem .75rem;
      border-radius:.5rem;
      background-color:rgba(15,23,42,.92);
      color:white;
      font-size:.75rem;
      line-height:1.1;
      box-shadow:0 10px 30px -15px rgba(15,23,42,.55);
      text-align:left;
      pointer-events:none;
      z-index:30;
    }
    .sku-lock-btn:hover .sku-lock-tooltip,
    .sku-lock-btn:focus-visible .sku-lock-tooltip,
    .sku-lock-btn:active .sku-lock-tooltip{
      display:block;
    }
    .form-toolbar{
      position:sticky;
    }
    @media (max-width: 639px){
      .form-toolbar{
        margin:0;
        border-radius:1rem 1rem 0 0;
      }
      .form-toolbar-actions > *{
        width:100%;
      }
    }
    /* Personalização */
    #cust-groups-container .cust-group{transition:transform .18s ease,box-shadow .18s ease,opacity .18s ease}
    #cust-groups-container .cust-group.dragging{opacity:.85;transform:scale(.985);box-shadow:0 18px 35px -20px rgba(15,23,42,.45)}
    .cust-drag-ghost{box-sizing:border-box;border-radius:.75rem;box-shadow:0 18px 35px -20px rgba(15,23,42,.45)}

    /* Combo */
    #groups-container .group-card{transition:transform .18s ease,box-shadow .18s ease,opacity .18s ease}
    #groups-container .group-card.dragging{opacity:.85;transform:scale(.985);box-shadow:0 18px 35px -20px rgba(15,23,42,.45)}
    .combo-drag-ghost{box-sizing:border-box;border-radius:.75rem;box-shadow:0 18px 35px -20px rgba(15,23,42,.45)}
    .combo-default-toggle.is-active{background:#eef2ff;border-color:#4f46e5;color:#312e81;font-weight:600}
    .combo-custom-toggle.is-active{background:#dbeafe;border-color:#2563eb;color:#1d4ed8;font-weight:600}
    .combo-custom-toggle.hidden{display:none}
    .combo-group-customizable{margin:12px 18px;padding:12px 16px;border-radius:12px;border:1px dashed #c7d2fe;background:#eef2ff;color:#3730a3;display:flex;flex-direction:column;gap:6px}
    .combo-group-customizable.hidden{display:none}
    .combo-group-custom-label{font-weight:600}
    .combo-group-custom-help{color:#475569}
    .combo-custom-wrapper.hidden{display:none}
  </style>

  <!-- CARD: Grupos (Combo) -->
  <?php $hasGroups = !empty($groups); ?>
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm" aria-labelledby="legend-groups">
    <legend id="legend-groups" class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M6 12h12M6 17h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Grupos de opções (Combo)
    </legend>

    <input type="hidden" id="use_groups_hidden" name="use_groups" value="<?= $hasGroups ? '1' : '0' ?>">

    <label class="mb-2 inline-flex items-center gap-2 text-sm text-slate-700">
      <input type="checkbox" id="groups-toggle" name="use_groups" value="1"
             class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
             <?= $hasGroups ? 'checked' : '' ?> aria-controls="groups-wrap" aria-expanded="<?= $hasGroups ? 'true' : 'false' ?>">
      <span>Usar grupos de opções (para combos/componentes)</span>
    </label>

    <div id="groups-wrap" class="<?= $hasGroups ? '' : 'hidden' ?>" aria-hidden="<?= $hasGroups ? 'false' : 'true' ?>">
      <?php if (empty($simpleProducts)): ?>
        <div class="mb-2 rounded-lg border border-amber-300 bg-amber-50 p-2 text-sm text-amber-900">
          Nenhum <strong>produto simples</strong> encontrado para esta empresa. Cadastre ao menos um e marque como ativo.
        </div>
      <?php endif; ?>

      <div class="mb-2 rounded-lg bg-slate-50 p-3 text-sm leading-relaxed text-slate-700">
        Cada <em>grupo</em> é uma etapa (ex.: “Lanche”, “Acompanhamento”, “Bebida”). Itens são
        <strong>produtos simples</strong>.
      </div>

      <div id="groups-container" class="grid gap-3">
        <?php if (!empty($groups)): foreach ($groups as $gi => $g): $gi = (int)$gi;
            $gItems = $g['items'] ?? [];
            $min    = (int)($g['min_qty'] ?? $g['min'] ?? 0);
            $max    = (int)($g['max_qty'] ?? $g['max'] ?? 1);
            $sort   = isset($g['sort_order']) ? (int)$g['sort_order'] : $gi;
            $groupHasCustom = false;

            foreach ($gItems as $itCheck) {
                if (!empty($itCheck['customizable']) || !empty($itCheck['allow_customize'])) {
                    $groupHasCustom = true;
                    break;
                }
            }
            ?>
        <div class="group-card rounded-2xl border border-slate-200 bg-white shadow-sm" data-index="<?= $gi ?>" data-custom-group="<?= $groupHasCustom ? '1' : '0' ?>">
          <div class="flex items-center gap-3 border-b border-slate-200 p-3">
<button 
  type="button" 
  draggable="true" 
  class="combo-drag-handle inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400 hover:text-slate-600" 
  title="Arrastar"
>
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrows-move" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M7.646.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 1.707V5.5a.5.5 0 0 1-1 0V1.707L6.354 2.854a.5.5 0 1 1-.708-.708zM8 10a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 0 1 .708-.708L7.5 14.293V10.5A.5.5 0 0 1 8 10M.146 8.354a.5.5 0 0 1 0-.708l2-2a.5.5 0 1 1 .708.708L1.707 7.5H5.5a.5.5 0 0 1 0 1H1.707l1.147 1.146a.5.5 0 0 1-.708.708zM10 8a.5.5 0 0 1 .5-.5h3.793l-1.147-1.146a.5.5 0 0 1 .708-.708l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L14.293 8.5H10.5A.5.5 0 0 1 10 8"/>
</svg>
</button>            <input type="text" name="groups[<?= $gi ?>][name]"
                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400"
                   placeholder="Nome do grupo" value="<?= e($g['name'] ?? '') ?>" required />
            <input type="hidden" class="combo-order-input" name="groups[<?= $gi ?>][sort_order]" value="<?= $sort ?>">
            <button type="button" class="remove-group shrink-0 rounded-full p-2 text-slate-400 hover:text-red-600" aria-label="Remover grupo">✕</button>
          </div>

          <div class="combo-group-customizable <?= $ptype === 'combo' ? '' : 'hidden' ?>">
            <label class="combo-group-custom-label inline-flex items-center gap-2 text-sm text-indigo-700">
              <input type="checkbox" class="combo-group-custom-switch h-4 w-4 rounded border-indigo-300 text-indigo-600" <?= $groupHasCustom ? 'checked' : '' ?>>
              <span>Grupo personalizável</span>
            </label>
            <p class="combo-group-custom-help mt-1 text-xs text-slate-500">
              Ative para que o cliente personalize o item escolhido deste grupo (quando o produto simples permitir).
            </p>
          </div>

          <?php if (!empty($gItems)): foreach ($gItems as $ii => $it):
              $ii    = (int)$ii;
              $selId = (int)($it['product_id'] ?? $it['simple_id'] ?? $it['simple_product_id'] ?? 0);
              $isDef = !empty($it['is_default'] ?? $it['default']);
              ?>
          <?php
                $spInfo = $simpleMap[(int)$selId] ?? null;
              $canCustom = !empty($spInfo['allow_customize']) && (int)($spInfo['ingredient_count'] ?? 0) > 2;
              $isCustomizable = $canCustom && !empty($it['customizable']);
              ?>
          <div class="item-row grid grid-cols-1 gap-3 p-3 md:grid-cols-[minmax(0,1fr)_160px_72px_72px_minmax(0,180px)_40px] md:items-center" data-item-index="<?= $ii ?>">
            <div>
              <label class="block text-xs text-slate-500">Produto</label>
              <select name="groups[<?= $gi ?>][items][<?= $ii ?>][product_id]"
                      class="product-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2"
                      title="Selecione um item da lista." required>
                <option value="">— Selecione um produto simples —</option>
                <?php foreach ($simpleProducts as $sp): ?>
                  <option value="<?= (int)$sp['id'] ?>"
                          data-price="<?= e((string)($sp['price'] ?? '0')) ?>"
                          data-allow-customize="<?= !empty($sp['allow_customize']) ? '1' : '0' ?>"
                          data-ingredients="<?= (int)($sp['ingredient_count'] ?? 0) ?>"
                          <?= $selId === (int)$sp['id'] ? 'selected' : '' ?>>
                    <?= e($sp['name']) ?><?= isset($sp['price']) ? ' — R$ ' . number_format((float)$sp['price'], 2, ',', '.') : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="text-sm text-slate-600">
              <label class="block text-xs text-slate-500">Preço base</label>
              <div class="sp-price rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">R$ 0,00</div>
            </div>
            <div>
              <label class="block text-xs text-slate-500">Mín</label>
              <input type="number" min="0" name="groups[<?= $gi ?>][min]" value="<?= $min ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2"/>
            </div>
            <div>
              <label class="block text-xs text-slate-500">Máx</label>
              <input type="number" min="1" name="groups[<?= $gi ?>][max]" value="<?= $max ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2"/>
            </div>
            <div class="flex flex-col gap-2">
              <input type="hidden" class="combo-default-flag" name="groups[<?= $gi ?>][items][<?= $ii ?>][default]" value="<?= $isDef ? '1' : '0' ?>">
              <button type="button" class="combo-default-toggle rounded-lg border border-slate-300 px-3 py-2 text-sm <?= $isDef ? 'is-active admin-gradient-border' : '' ?>">
                Acompanhamento padrão
              </button>
              <div class="combo-custom-wrapper <?= $canCustom ? '' : 'hidden' ?>">
                <input type="hidden" class="combo-custom-flag" name="groups[<?= $gi ?>][items][<?= $ii ?>][customizable]" value="<?= $isCustomizable ? '1' : '0' ?>">
                <button type="button" class="combo-custom-toggle rounded-lg border border-indigo-300 px-3 py-2 text-sm text-indigo-700 <?= $isCustomizable ? 'is-active admin-gradient-border' : '' ?>">
                  Produto Personalizável
                </button>
              </div>
            </div>
            <div class="flex justify-end">
              <button type="button" class="remove-item shrink-0 rounded-full p-2 text-slate-400 hover:text-red-600" aria-label="Remover item">✕</button>
            </div>
            <input type="hidden" name="groups[<?= $gi ?>][items][<?= $ii ?>][delta]" value="<?= e($it['delta'] ?? $it['delta_price'] ?? 0) ?>">
          </div>
          <?php endforeach;
          else: ?>
          <div class="item-row grid grid-cols-1 gap-3 p-3 md:grid-cols-[minmax(0,1fr)_160px_72px_72px_minmax(0,180px)_40px] md:items-center" data-item-index="0">
            <div>
              <label class="block text-xs text-slate-500">Produto</label>
              <select name="groups[<?= $gi ?>][items][0][product_id]" class="product-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2" title="Selecione um item da lista." required>
                <option value="">— Selecione um produto simples —</option>
                <?php foreach ($simpleProducts as $sp): ?>
                  <option value="<?= (int)$sp['id'] ?>"
                          data-price="<?= e((string)($sp['price'] ?? '0')) ?>"
                          data-allow-customize="<?= !empty($sp['allow_customize']) ? '1' : '0' ?>"
                          data-ingredients="<?= (int)($sp['ingredient_count'] ?? 0) ?>">
                    <?= e($sp['name']) ?><?= isset($sp['price']) ? ' — R$ ' . number_format((float)$sp['price'], 2, ',', '.') : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="text-sm text-slate-600">
              <label class="block text-xs text-slate-500">Preço base</label>
              <div class="sp-price rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">R$ 0,00</div>
            </div>
            <div>
              <label class="block text-xs text-slate-500">Mín</label>
              <input type="number" min="0" name="groups[<?= $gi ?>][min]" value="<?= $min ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2"/>
            </div>
            <div>
              <label class="block text-xs text-slate-500">Máx</label>
              <input type="number" min="1" name="groups[<?= $gi ?>][max]" value="<?= $max ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2"/>
            </div>
            <div class="flex flex-col gap-2">
              <input type="hidden" class="combo-default-flag" name="groups[<?= $gi ?>][items][0][default]" value="0">
              <button type="button" class="combo-default-toggle rounded-lg border border-slate-300 px-3 py-2 text-sm">
                Acompanhamento padrão
              </button>
              <div class="combo-custom-wrapper hidden">
                <input type="hidden" class="combo-custom-flag" name="groups[<?= $gi ?>][items][0][customizable]" value="0">
                <button type="button" class="combo-custom-toggle rounded-lg border border-indigo-300 px-3 py-2 text-sm text-indigo-700">
                  Produto Personalizável
                </button>
              </div>
            </div>
            <div class="flex justify-end">
              <button type="button" class="remove-item shrink-0 rounded-full p-2 text-slate-400 hover:text-red-600" aria-label="Remover item">✕</button>
            </div>
            <input type="hidden" name="groups[<?= $gi ?>][items][0][delta]" value="0">
          </div>
          <?php endif; ?>

          <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
            <button type="button" class="add-item rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Item</button>
            <div class="group-base-price text-sm text-slate-600">Preço base: R$ 0,00</div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>

      <div class="mt-1">
        <button type="button" id="add-group" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Grupo</button>
      </div>
    </div>
  </fieldset>

  <!-- CARD: Personalização -->
  <fieldset id="customization-card" class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm" aria-labelledby="legend-custom">
    <legend id="legend-custom" class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 8h12M6 12h8M6 16h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Personalização
    </legend>

    <input type="hidden" id="customization-enabled-hidden" name="customization[enabled]" value="<?= $custEnabled ? '1' : '0' ?>">
    <label class="mb-2 inline-flex items-center gap-2 text-sm text-slate-700">
      <input type="checkbox" id="customization-enabled" name="customization[enabled]" value="1"
             class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" <?= $custEnabled ? 'checked' : '' ?>>
      <span>Permitir personalização de itens</span>
    </label>

    <div id="customization-wrap" class="<?= $custEnabled ? '' : 'hidden' ?>" aria-hidden="<?= $custEnabled ? 'false' : 'true' ?>">
      <div class="mb-2 rounded-lg bg-slate-50 p-3 text-sm leading-relaxed text-slate-700">
        Crie grupos (ex.: <strong>Ingredientes</strong>, <strong>Molhos</strong>) e escolha os ingredientes já cadastrados.
        Ative <strong>Ingrediente padrão</strong> para definir a quantidade exibida ao cliente.
      </div>

      <div id="cust-groups-container" class="grid gap-3">
        <?php if (!empty($custGroups)): foreach ($custGroups as $gi => $cg): $gi = (int)$gi;
            $cgName = $cg['name'] ?? '';
            $cItems = $cg['items'] ?? [[]];
            $gType  = $cg['type'] ?? 'extra';
            $gMode  = in_array($gType, ['single','addon'], true) ? 'choice' : 'extra';
            $gMin   = isset($cg['min']) ? max(0, (int)$cg['min']) : 0;
            $gMax   = isset($cg['max']) ? max($gMin, (int)$cg['max']) : ($gMode === 'choice' ? max(1, count($cItems)) : 99);

            if ($gType === 'single') {
                $gMax = 1;
            }
            ?>
        <div class="cust-group rounded-2xl border border-slate-200 bg-white shadow-sm" data-index="<?= $gi ?>" data-mode="<?= e($gMode) ?>">
          <div class="flex flex-col gap-3 border-b border-slate-200 p-3">
            <div class="flex items-center gap-3">
              <button 
  type="button" 
  draggable="true" 
                class="combo-drag-handle inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400 hover:text-slate-600" 
  title="Arrastar"
>
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrows-move" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M7.646.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 1.707V5.5a.5.5 0 0 1-1 0V1.707L6.354 2.854a.5.5 0 1 1-.708-.708zM8 10a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 0 1 .708-.708L7.5 14.293V10.5A.5.5 0 0 1 8 10M.146 8.354a.5.5 0 0 1 0-.708l2-2a.5.5 0 1 1 .708.708L1.707 7.5H5.5a.5.5 0 0 1 0 1H1.707l1.147 1.146a.5.5 0 0 1-.708.708zM10 8a.5.5 0 0 1 .5-.5h3.793l-1.147-1.146a.5.5 0 0 1 .708-.708l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L14.293 8.5H10.5A.5.5 0 0 1 10 8"/>
</svg>
</button>
              <input type="text" name="customization[groups][<?= $gi ?>][name]"
                     class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400"
                     placeholder="Nome do grupo" value="<?= e($cgName) ?>"/>
              <input type="hidden" class="cust-order-input" name="customization[groups][<?= $gi ?>][sort_order]" value="<?= isset($cg['sort_order']) ? (int)$cg['sort_order'] : $gi ?>">
              <button type="button" class="cust-remove-group rounded-full p-2 text-slate-400 hover:text-red-600" title="Remover grupo">✕</button>
            </div>
            <div class="grid items-start gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
              <label class="grid gap-1 text-sm">
                <span class="text-xs text-slate-500">Modo de seleção</span>
                <select name="customization[groups][<?= $gi ?>][mode]" class="cust-mode-select rounded-lg border border-slate-300 bg-white px-3 py-2">
                  <option value="extra" <?= $gMode === 'extra' ? 'selected' : '' ?>>Adicionar ingredientes livremente</option>
                  <option value="choice" <?= $gMode === 'choice' ? 'selected' : '' ?>>Escolher ingrediente</option>
                </select>
              </label>
              <div class="cust-choice-settings <?= $gMode === 'choice' ? '' : 'hidden' ?>">
                <div class="grid gap-2 md:grid-cols-2">
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Seleções mínimas</span>
                    <input type="number" class="cust-choice-min rounded-lg border border-slate-300 px-3 py-2"
                           name="customization[groups][<?= $gi ?>][choice][min]" value="<?= $gMode === 'choice' ? $gMin : 0 ?>" min="0" step="1">
                  </label>
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Seleções máximas</span>
                    <input type="number" class="cust-choice-max rounded-lg border border-slate-300 px-3 py-2"
                           name="customization[groups][<?= $gi ?>][choice][max]" value="<?= $gMode === 'choice' ? $gMax : 1 ?>" min="1" step="1">
                  </label>
                </div>
                <p class="mt-1 text-xs text-slate-500">Defina quantas opções o cliente pode marcar.</p>
              </div>
            </div>
          </div>
          <?php foreach ($cItems as $ii => $ci): $ii = (int)$ii;
              $selId = isset($ci['ingredient_id']) ? (int)$ci['ingredient_id'] : 0;
              $def   = !empty($ci['default']);
              $minQ  = isset($ci['min_qty']) ? (int)$ci['min_qty'] : 0;
              $maxQ  = isset($ci['max_qty']) ? (int)$ci['max_qty'] : 1;

              if ($maxQ < $minQ) {
                  $maxQ = $minQ;
              }
              $defQty = $def ? (int)($ci['default_qty'] ?? $minQ) : $minQ;
              ?>
          <div class="cust-item grid items-center gap-3 p-3 md:grid-cols-[minmax(0,1fr)_auto_auto_auto_40px]" data-item-index="<?= $ii ?>">
            <div>
              <label class="block text-xs text-slate-500">Ingrediente</label>
              <select name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][ingredient_id]"
                      class="cust-ingredient-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2"
                      data-default-min="<?= $minQ ?>" data-default-max="<?= $maxQ ?>">
                <option value="">Selecione</option>
                <?php foreach ($ingredients as $ing): ?>
                  <option value="<?= (int)$ing['id'] ?>"
                          data-min="<?= (int)($ing['min_qty'] ?? 0) ?>"
                          data-max="<?= (int)($ing['max_qty'] ?? 1) ?>"
                          data-img="<?= e($ing['image_path'] ?? '') ?>"
                          <?= $selId === (int)$ing['id'] ? 'selected' : '' ?>>
                    <?= e($ing['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="cust-limits-wrap self-start md:self-center">
              <div class="cust-limits grid gap-2 md:grid-cols-2" data-min="<?= $minQ ?>" data-max="<?= $maxQ ?>">
                <div>
                  <label class="block text-xs text-slate-500">Quantidade mínima</label>
                  <input type="number" class="cust-min-input w-24 rounded-lg border border-slate-300 px-3 py-2"
                         name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][min_qty]" value="<?= $minQ ?>" min="0" step="1">
                </div>
                <div>
                  <label class="block text-xs text-slate-500">Quantidade máxima</label>
                  <input type="number" class="cust-max-input w-24 rounded-lg border border-slate-300 px-3 py-2"
                         name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][max_qty]" value="<?= $maxQ ?>" min="0" step="1">
                </div>
              </div>
            </div>
            <div class="flex flex-col items-start gap-2">
              <input type="hidden" class="cust-default-flag" name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][default]" value="<?= $def ? '1' : '0' ?>">
              <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" class="cust-default-toggle h-4 w-4 rounded border-slate-300 text-indigo-600" <?= $def ? 'checked' : '' ?>>
                <span>Ingrediente padrão</span>
              </label>
            </div>
            <div class="cust-default-qty-wrap <?= $def ? '' : 'hidden' ?>">
              <label class="block text-xs text-slate-500">Quantidade padrão</label>
              <input type="number" class="cust-default-qty rounded-lg border border-slate-300 px-3 py-2"
                     name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][default_qty]"
                     value="<?= $defQty ?>" min="<?= $minQ ?>" max="<?= $maxQ ?>" step="1">
            </div>
            <button type="button" class="cust-remove-item justify-self-end rounded-full p-2 text-slate-400 hover:text-red-600" title="Remover ingrediente">✕</button>
          </div>
          <?php endforeach; ?>

          <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
            <button type="button" class="cust-add-item rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Ingrediente</button>
            <button type="button" class="cust-add-choice rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Escolher ingrediente</button>
          </div>
        </div>
        <?php endforeach;
        else: ?>
        <!-- grupo vazio inicial -->
        <div class="cust-group rounded-2xl border border-slate-200 bg-white shadow-sm" data-index="0" data-mode="extra">
          <div class="flex flex-col gap-3 border-b border-slate-200 p-3">
            <div class="flex items-center gap-3">
<button 
  type="button" 
  draggable="true" 
  class="combo-drag-handle inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400 hover:text-slate-600" 
  title="Arrastar"
>
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrows-move" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M7.646.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 1.707V5.5a.5.5 0 0 1-1 0V1.707L6.354 2.854a.5.5 0 1 1-.708-.708zM8 10a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 0 1 .708-.708L7.5 14.293V10.5A.5.5 0 0 1 8 10M.146 8.354a.5.5 0 0 1 0-.708l2-2a.5.5 0 1 1 .708.708L1.707 7.5H5.5a.5.5 0 0 1 0 1H1.707l1.147 1.146a.5.5 0 0 1-.708.708zM10 8a.5.5 0 0 1 .5-.5h3.793l-1.147-1.146a.5.5 0 0 1 .708-.708l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L14.293 8.5H10.5A.5.5 0 0 1 10 8"/>
</svg>
</button>
              <input type="text" name="customization[groups][0][name]"
                     class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400"
                     placeholder="Nome do grupo" value=""/>
              <input type="hidden" class="cust-order-input" name="customization[groups][0][sort_order]" value="0">
              <button type="button" class="cust-remove-group rounded-full p-2 text-slate-400 hover:text-red-600" title="Remover grupo">✕</button>
            </div>
            <div class="grid items-start gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
              <label class="grid gap-1 text-sm">
                <span class="text-xs text-slate-500">Modo de seleção</span>
                <select name="customization[groups][0][mode]" class="cust-mode-select rounded-lg border border-slate-300 bg-white px-3 py-2">
                  <option value="extra" selected>Adicionar ingredientes livremente</option>
                  <option value="choice">Escolher ingrediente</option>
                </select>
              </label>
              <div class="cust-choice-settings hidden">
                <div class="grid gap-2 md:grid-cols-2">
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Seleções mínimas</span>
                    <input type="number" class="cust-choice-min rounded-lg border border-slate-300 px-3 py-2"
                           name="customization[groups][0][choice][min]" value="0" min="0" step="1">
                  </label>
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Seleções máximas</span>
                    <input type="number" class="cust-choice-max rounded-lg border border-slate-300 px-3 py-2"
                           name="customization[groups][0][choice][max]" value="1" min="1" step="1">
                  </label>
                </div>
                <p class="mt-1 text-xs text-slate-500">Defina quantas opções o cliente pode marcar.</p>
              </div>
            </div>
          </div>

          <div class="cust-item grid items-center gap-3 p-3 md:grid-cols-[minmax(0,1fr)_auto_auto_auto_40px]" data-item-index="0">
            <div>
              <label class="block text-xs text-slate-500">Ingrediente</label>
              <select name="customization[groups][0][items][0][ingredient_id]"
                      class="cust-ingredient-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2"
                      data-default-min="0" data-default-max="1">
                <option value="">Selecione</option>
                <?php foreach ($ingredients as $ing): ?>
                  <option value="<?= (int)$ing['id'] ?>"
                          data-min="<?= (int)($ing['min_qty'] ?? 0) ?>"
                          data-max="<?= (int)($ing['max_qty'] ?? 1) ?>"
                          data-img="<?= e($ing['image_path'] ?? '') ?>">
                    <?= e($ing['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="cust-limits-wrap self-start md:self-center">
              <div class="cust-limits grid gap-2 md:grid-cols-2" data-min="0" data-max="1">
                <div>
                  <label class="block text-xs text-slate-500">Quantidade mín</label>
                  <input type="number" class="cust-min-input w-24 rounded-lg border border-slate-300 px-3 py-2"
                         name="customization[groups][0][items][0][min_qty]" value="0" min="0" step="1">
                </div>
                <div>
                  <label class="block text-xs text-slate-500">Quantidade máx</label>
                  <input type="number" class="cust-max-input w-24 rounded-lg border border-slate-300 px-3 py-2"
                         name="customization[groups][0][items][0][max_qty]" value="1" min="0" step="1">
                </div>
              </div>
            </div>
            <div class="flex flex-col items-start gap-2">
              <input type="hidden" class="cust-default-flag" name="customization[groups][0][items][0][default]" value="0">
              <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" class="cust-default-toggle h-4 w-4 rounded border-slate-300 text-indigo-600" aria-label="Definir ingrediente padrão">
                <span>Ingrediente padrão</span>
              </label>
            </div>
            <div class="cust-default-qty-wrap hidden">
              <label class="block text-xs text-slate-500">Quantidade padrão</label>
              <input type="number" class="cust-default-qty rounded-lg border border-slate-300 px-3 py-2"
                     name="customization[groups][0][items][0][default_qty]" value="0" min="0" max="1" step="1">
            </div>
            <button type="button" class="cust-remove-item justify-self-end rounded-full p-2 text-slate-400 hover:text-red-600" title="Remover ingrediente">✕</button>
          </div>

          <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
            <button type="button" class="cust-add-item rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Ingrediente</button>
            <button type="button" class="cust-add-choice rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Escolher ingrediente</button>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="mt-1">
        <button type="button" id="cust-add-group" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">
          + Grupo de personalização
        </button>
      </div>
    </div>
  </fieldset>

  <!-- CARD: Publicação -->
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 12h12M12 6v12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Publicação
    </legend>
    <label for="active" class="inline-flex items-center gap-2 text-slate-700">
      <input type="checkbox" name="active" id="active" <?= !isset($p['active']) || $p['active'] ? 'checked' : '' ?>
             class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
      <span>Produto ativo</span>
    </label>
  </fieldset>

  <!-- ===== Templates (Combo) ===== -->
  <template id="tpl-group">
    <div class="group-card rounded-2xl border border-slate-200 bg-white shadow-sm" data-index="__GI__" data-custom-group="0">
      <div class="flex items-center gap-3 border-b border-slate-200 p-3">
<button 
  type="button" 
  draggable="true" 
  class="combo-drag-handle inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400 hover:text-slate-600" 
  title="Arrastar"
>
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrows-move" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M7.646.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 1.707V5.5a.5.5 0 0 1-1 0V1.707L6.354 2.854a.5.5 0 1 1-.708-.708zM8 10a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 0 1 .708-.708L7.5 14.293V10.5A.5.5 0 0 1 8 10M.146 8.354a.5.5 0 0 1 0-.708l2-2a.5.5 0 1 1 .708.708L1.707 7.5H5.5a.5.5 0 0 1 0 1H1.707l1.147 1.146a.5.5 0 0 1-.708.708zM10 8a.5.5 0 0 1 .5-.5h3.793l-1.147-1.146a.5.5 0 0 1 .708-.708l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L14.293 8.5H10.5A.5.5 0 0 1 10 8"/>
</svg>
</button>        <input type="text" name="groups[__GI__][name]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400" placeholder="Nome do grupo" value="" required />
        <input type="hidden" class="combo-order-input" name="groups[__GI__][sort_order]" value="__GI__">
        <button type="button" class="remove-group shrink-0 rounded-full p-2 text-slate-400 hover:text-red-600" aria-label="Remover grupo">✕</button>
      </div>

      <div class="combo-group-customizable <?= $ptype === 'combo' ? '' : 'hidden' ?>">
        <label class="combo-group-custom-label inline-flex items-center gap-2 text-sm text-indigo-700">
          <input type="checkbox" class="combo-group-custom-switch h-4 w-4 rounded border-indigo-300 text-indigo-600">
          <span>Grupo personalizável</span>
        </label>
        <p class="combo-group-custom-help mt-1 text-xs text-slate-500">
          Ative para liberar personalização dos itens selecionados neste grupo.
        </p>
      </div>

      <div class="item-row grid grid-cols-1 gap-3 p-3 md:grid-cols-[minmax(0,1fr)_160px_72px_72px_minmax(0,180px)_40px] md:items-center" data-item-index="0">
        <div>
          <label class="block text-xs text-slate-500">Produto</label>
          <select name="groups[__GI__][items][0][product_id]" class="product-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2" title="Selecione um item da lista." required>
            <option value="">— Selecione um produto simples —</option>
            <?php foreach ($simpleProducts as $sp): ?>
              <option value="<?= (int)$sp['id'] ?>"
                      data-price="<?= e((string)($sp['price'] ?? '0')) ?>"
                      data-allow-customize="<?= !empty($sp['allow_customize']) ? '1' : '0' ?>"
                      data-ingredients="<?= (int)($sp['ingredient_count'] ?? 0) ?>">
                <?= e($sp['name']) ?><?= isset($sp['price']) ? ' — R$ ' . number_format((float)$sp['price'], 2, ',', '.') : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="text-sm text-slate-600">
          <label class="block text-xs text-slate-500">Preço base</label>
          <div class="sp-price rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">R$ 0,00</div>
        </div>

        <div>
          <label class="block text-xs text-slate-500">Mín</label>
          <input type="number" min="0" name="groups[__GI__][min]" value="0" class="w-full rounded-lg border border-slate-300 px-3 py-2"/>
        </div>

        <div>
          <label class="block text-xs text-slate-500">Máx</label>
          <input type="number" min="1" name="groups[__GI__][max]" value="1" class="w-full rounded-lg border border-slate-300 px-3 py-2"/>
        </div>
        <div class="flex flex-col gap-2">
          <input type="hidden" class="combo-default-flag" name="groups[__GI__][items][0][default]" value="0">
          <button type="button" class="combo-default-toggle rounded-lg border border-slate-300 px-3 py-2 text-sm">
            Acompanhamento padrão
          </button>
          <div class="combo-custom-wrapper hidden">
            <input type="hidden" class="combo-custom-flag" name="groups[__GI__][items][0][customizable]" value="0">
            <button type="button" class="combo-custom-toggle rounded-lg border border-indigo-300 px-3 py-2 text-sm text-indigo-700">
              Produto Personalizável
            </button>
          </div>
        </div>

        <div class="flex justify-end">
          <button type="button" class="remove-item shrink-0 rounded-full p-2 text-slate-400 hover:text-red-600" aria-label="Remover item">✕</button>
        </div>

        <input type="hidden" name="groups[__GI__][items][0][delta]" value="0">
      </div>

      <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
        <button type="button" class="add-item rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Item</button>
        <div class="group-base-price text-sm text-slate-600">Preço base: R$ 0,00</div>
      </div>
    </div>
  </template>

  <template id="tpl-item">
    <div class="item-row grid grid-cols-1 gap-3 p-3 md:grid-cols-[minmax(0,1fr)_160px_72px_72px_minmax(0,180px)_40px] md:items-center" data-item-index="__II__">
      <div>
        <label class="block text-xs text-slate-500">Produto</label>
        <select name="groups[__GI__][items][__II__][product_id]" class="product-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2" title="Selecione um item da lista." required>
          <option value="">— Selecione um produto simples —</option>
          <?php foreach ($simpleProducts as $sp): ?>
            <option value="<?= (int)$sp['id'] ?>"
                    data-price="<?= e((string)($sp['price'] ?? '0')) ?>"
                    data-allow-customize="<?= !empty($sp['allow_customize']) ? '1' : '0' ?>"
                    data-ingredients="<?= (int)($sp['ingredient_count'] ?? 0) ?>">
              <?= e($sp['name']) ?><?= isset($sp['price']) ? ' — R$ ' . number_format((float)$sp['price'], 2, ',', '.') : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="text-sm text-slate-600">
        <label class="block text-xs text-slate-500">Preço base</label>
        <div class="sp-price rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">R$ 0,00</div>
      </div>

      <div>
        <label class="block text-xs text-slate-500">Mín</label>
        <input type="number" min="0" name="groups[__GI__][min]" value="0" class="w-full rounded-lg border border-slate-300 px-3 py-2"/>
      </div>

      <div>
        <label class="block text-xs text-slate-500">Máx</label>
        <input type="number" min="1" name="groups[__GI__][max]" value="1" class="w-full rounded-lg border border-slate-300 px-3 py-2"/>
      </div>

      <div class="flex flex-col gap-2">
        <input type="hidden" class="combo-default-flag" name="groups[__GI__][items][__II__][default]" value="0">
        <button type="button" class="combo-default-toggle rounded-lg border border-slate-300 px-3 py-2 text-sm">
          Acompanhamento padrão
        </button>
        <div class="combo-custom-wrapper hidden">
          <input type="hidden" class="combo-custom-flag" name="groups[__GI__][items][__II__][customizable]" value="0">
          <button type="button" class="combo-custom-toggle rounded-lg border border-indigo-300 px-3 py-2 text-sm text-indigo-700">
            Produto Personalizável
          </button>
        </div>
      </div>

      <div class="flex justify-end">
        <button type="button" class="remove-item shrink-0 rounded-full p-2 text-slate-400 hover:text-red-600" aria-label="Remover item">✕</button>
      </div>

      <input type="hidden" name="groups[__GI__][items][__II__][delta]" value="0">
    </div>
  </template>

  <!-- ===== Templates (Personalização) ===== -->
  <template id="tpl-cust-group">
    <div class="cust-group rounded-2xl border border-slate-200 bg-white shadow-sm" data-index="__CGI__" data-mode="extra">
      <div class="flex flex-col gap-3 border-b border-slate-200 p-3">
        <div class="flex items-center gap-3">
<button 
  type="button" 
  draggable="true" 
  class="cust-drag-handle inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400 hover:text-slate-600" 
  title="Arrastar"
>
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrows-move" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M7.646.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 1.707V5.5a.5.5 0 0 1-1 0V1.707L6.354 2.854a.5.5 0 1 1-.708-.708zM8 10a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 0 1 .708-.708L7.5 14.293V10.5A.5.5 0 0 1 8 10M.146 8.354a.5.5 0 0 1 0-.708l2-2a.5.5 0 1 1 .708.708L1.707 7.5H5.5a.5.5 0 0 1 0 1H1.707l1.147 1.146a.5.5 0 0 1-.708.708zM10 8a.5.5 0 0 1 .5-.5h3.793l-1.147-1.146a.5.5 0 0 1 .708-.708l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L14.293 8.5H10.5A.5.5 0 0 1 10 8"/>
</svg>
</button>          <input type="text" name="customization[groups][__CGI__][name]"
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400"
                 placeholder="Nome do grupo" value=""/>
          <input type="hidden" class="cust-order-input" name="customization[groups][__CGI__][sort_order]" value="0">
          <button type="button" class="cust-remove-group rounded-full p-2 text-slate-400 hover:text-red-600" title="Remover grupo">✕</button>
        </div>
        <div class="grid items-start gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
          <label class="grid gap-1 text-sm">
            <span class="text-xs text-slate-500">Modo de seleção</span>
            <select name="customization[groups][__CGI__][mode]" class="cust-mode-select rounded-lg border border-slate-300 bg-white px-3 py-2">
              <option value="extra" selected>Adicionar ingredientes livremente</option>
              <option value="choice">Escolher ingrediente</option>
            </select>
          </label>
          <div class="cust-choice-settings hidden">
            <div class="grid gap-2 md:grid-cols-2">
              <label class="grid gap-1 text-xs text-slate-500">
                <span>Seleções mínimas</span>
                <input type="number" class="cust-choice-min rounded-lg border border-slate-300 px-3 py-2" name="customization[groups][__CGI__][choice][min]" value="0" min="0" step="1">
              </label>
              <label class="grid gap-1 text-xs text-slate-500">
                <span>Seleções máximas</span>
                <input type="number" class="cust-choice-max rounded-lg border border-slate-300 px-3 py-2" name="customization[groups][__CGI__][choice][max]" value="1" min="1" step="1">
              </label>
            </div>
            <p class="mt-1 text-xs text-slate-500">Defina quantas opções o cliente pode marcar.</p>
          </div>
        </div>
      </div>

      <div class="cust-item grid items-center gap-3 p-3 md:grid-cols-[minmax(0,1fr)_auto_auto_auto_40px]" data-item-index="0">
        <div>
          <label class="block text-xs text-slate-500">Ingrediente</label>
          <select name="customization[groups][__CGI__][items][0][ingredient_id]" class="cust-ingredient-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2" data-default-min="0" data-default-max="1">
            <option value="">Selecione</option>
            <?php foreach ($ingredients as $ing): ?>
              <option value="<?= (int)$ing['id'] ?>" data-min="<?= (int)($ing['min_qty'] ?? 0) ?>" data-max="<?= (int)($ing['max_qty'] ?? 1) ?>" data-img="<?= e($ing['image_path'] ?? '') ?>">
                <?= e($ing['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="cust-limits-wrap self-start md:self-center">
          <div class="cust-limits grid gap-2 md:grid-cols-2" data-min="0" data-max="1">
            <div>
              <label class="block text-xs text-slate-500">Quantidade mín</label>
              <input type="number" class="cust-min-input w-24 rounded-lg border border-slate-300 px-3 py-2" name="customization[groups][__CGI__][items][0][min_qty]" value="0" min="0" step="1">
            </div>
            <div>
              <label class="block text-xs text-slate-500">Quantidade máx</label>
              <input type="number" class="cust-max-input w-24 rounded-lg border border-slate-300 px-3 py-2" name="customization[groups][__CGI__][items][0][max_qty]" value="1" min="0" step="1">
            </div>
          </div>
        </div>
        <div class="flex flex-col items-start gap-2">
          <input type="hidden" class="cust-default-flag" name="customization[groups][__CGI__][items][0][default]" value="0">
          <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" class="cust-default-toggle h-4 w-4 rounded border-slate-300 text-indigo-600" aria-label="Definir ingrediente padrão">
            <span>Ingrediente padrão</span>
          </label>
        </div>
        <div class="cust-default-qty-wrap hidden">
          <label class="block text-xs text-slate-500">Quantidade padrão</label>
          <input type="number" class="cust-default-qty rounded-lg border border-slate-300 px-3 py-2" name="customization[groups][__CGI__][items][0][default_qty]" value="0" min="0" max="1" step="1">
        </div>
        <button type="button" class="cust-remove-item justify-self-end rounded-full p-2 text-slate-400 hover:text-red-600" title="Remover ingrediente">✕</button>
      </div>

      <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
        <button type="button" class="cust-add-item rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Ingrediente</button>
        <button type="button" class="cust-add-choice rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Escolher ingrediente</button>
      </div>
    </div>
  </template>

  <template id="tpl-cust-item">
    <div class="cust-item grid items-center gap-3 p-3 md:grid-cols-[minmax(0,1fr)_auto_auto_auto_40px]" data-item-index="__CII__">
      <div>
        <label class="block text-xs text-slate-500">Ingrediente</label>
        <select name="customization[groups][__CGI__][items][__CII__][ingredient_id]" class="cust-ingredient-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2" data-default-min="0" data-default-max="1">
          <option value="">Selecione</option>
          <?php foreach ($ingredients as $ing): ?>
            <option value="<?= (int)$ing['id'] ?>" data-min="<?= (int)($ing['min_qty'] ?? 0) ?>" data-max="<?= (int)($ing['max_qty'] ?? 1) ?>" data-img="<?= e($ing['image_path'] ?? '') ?>">
              <?= e($ing['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="cust-limits-wrap self-start md:self-center">
        <div class="cust-limits grid gap-2 md:grid-cols-2" data-min="0" data-max="1">
          <div>
            <label class="block text-xs text-slate-500">Quantidade mín</label>
            <input type="number" class="cust-min-input w-24 rounded-lg border border-slate-300 px-3 py-2" name="customization[groups][__CGI__][items][__CII__][min_qty]" value="0" min="0" step="1">
          </div>
          <div>
            <label class="block text-xs text-slate-500">Quantidade máx</label>
            <input type="number" class="cust-max-input w-24 rounded-lg border border-slate-300 px-3 py-2" name="customization[groups][__CGI__][items][__CII__][max_qty]" value="1" min="0" step="1">
          </div>
        </div>
      </div>
      <div class="flex flex-col items-start gap-2">
        <input type="hidden" class="cust-default-flag" name="customization[groups][__CGI__][items][__CII__][default]" value="0">
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" class="cust-default-toggle h-4 w-4 rounded border-slate-300 text-indigo-600" aria-label="Definir ingrediente padrão">
          <span>Ingrediente padrão</span>
        </label>
      </div>
      <div class="cust-default-qty-wrap hidden">
        <label class="block text-xs text-slate-500">Quantidade padrão</label>
        <input type="number" class="cust-default-qty rounded-lg border border-slate-300 px-3 py-2" name="customization[groups][__CGI__][items][__CII__][default_qty]" value="0" min="0" max="1" step="1">
      </div>
      <button type="button" class="cust-remove-item justify-self-end rounded-full p-2 text-slate-400 hover:text-red-600" title="Remover ingrediente">✕</button>
    </div>
  </template>

  <!-- script externalizado: admin-products.js -->
  <script src="<?= base_url('assets/js/admin-products.js') ?>"></script>
</form>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
