<?php
// admin/products/form.php — Formulário de produtos

/* ===== Guard rails / Vars padrão ===== */
$p              = $p              ?? [];
$company        = $company        ?? [];
$cats           = $cats           ?? [];
$groups         = $groups         ?? [];           // COMPOSIÇÃO (combo)
$simpleProducts = $simpleProducts ?? [];           // para combos
$mods           = $mods           ?? [];           // PERSONALIZAÇÃO
$errors         = $errors         ?? [];

$title   = "Produto - " . ($company['name'] ?? '');
$editing = !empty($p['id']);
$slug    = rawurlencode((string)($company['slug'] ?? ''));
$action  = $editing ? "admin/{$slug}/products/" . (int)$p['id'] : "admin/{$slug}/products";

if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
?>
<?php ob_start(); ?>
<h1 class="text-2xl font-semibold mb-4"><?= $editing ? 'Editar' : 'Novo' ?> produto</h1>

<?php if (!empty($errors) && is_array($errors)): ?>
  <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">
    <strong class="block mb-1">Por favor, corrija os campos abaixo:</strong>
    <ul class="list-disc pl-5 space-y-0.5">
      <?php foreach ($errors as $err): ?>
        <li><?= e($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form id="product-form"
      method="post"
      action="<?= e(base_url($action)) ?>"
      enctype="multipart/form-data"
      class="grid gap-4 max-w-3xl bg-white p-4 md:p-6 rounded-2xl border">

  <?php if (function_exists('csrf_field')): ?>
    <?= csrf_field() ?>
  <?php elseif (function_exists('csrf_token')): ?>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php endif; ?>

  <?php if ($editing): ?>
    <input type="hidden" name="_method" value="PUT">
  <?php endif; ?>

  <!-- Toolbar fixa -->
  <div class="sticky top-0 z-10 -m-4 md:-m-6 mb-2 bg-white/80 backdrop-blur supports-[backdrop-filter]:bg-white/60 border-b px-4 md:px-6 py-2 flex items-center justify-between">
    <span class="text-sm text-gray-600">Empresa: <strong><?= e($company['name'] ?? '—') ?></strong></span>
    <div class="flex gap-2">
      <a href="<?= e(base_url("admin/{$slug}/products")) ?>" class="px-3 py-1.5 rounded-xl border text-sm">Cancelar</a>
      <button type="submit" class="px-4 py-1.5 rounded-xl border bg-gray-900 text-white text-sm">Salvar</button>
    </div>
  </div>

  <!-- Dados básicos -->
  <fieldset class="grid gap-3">
    <legend class="text-base font-medium">Dados básicos</legend>

    <label for="category_id" class="grid gap-1">
      <span class="text-sm">Categoria</span>
      <select name="category_id" id="category_id" class="border rounded-xl p-2" aria-describedby="help-cat">
        <option value="">— sem categoria —</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (isset($p['category_id']) && (int)$p['category_id'] === (int)$c['id']) ? 'selected' : '' ?>>
            <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <small id="help-cat" class="text-xs text-gray-500">Usado para agrupar o cardápio.</small>
    </label>

    <div class="grid md:grid-cols-2 gap-3">
      <label for="name" class="grid gap-1">
        <span class="text-sm">Nome <span class="text-red-500">*</span></span>
        <input required name="name" id="name" value="<?= e($p['name'] ?? '') ?>" class="border rounded-xl p-2" autocomplete="off">
      </label>

      <label for="sku" class="grid gap-1">
        <span class="text-sm">SKU</span>
        <input name="sku" id="sku" value="<?= e($p['sku'] ?? '') ?>" class="border rounded-xl p-2" placeholder="Opcional" autocomplete="off">
      </label>
    </div>

    <label for="description" class="grid gap-1">
      <span class="text-sm">Descrição</span>
      <textarea name="description" id="description" rows="3" maxlength="300" class="border rounded-xl p-2" placeholder="Até 300 caracteres"><?= e($p['description'] ?? '') ?></textarea>
      <div class="flex justify-between text-xs text-gray-500">
        <span>Mostrada na página do produto.</span>
        <span><span id="desc-count">0</span>/300</span>
      </div>
    </label>
  </fieldset>

  <!-- Tipo & Preço -->
  <fieldset class="grid gap-3">
    <legend class="text-base font-medium">Tipo & preço</legend>

    <div class="grid md:grid-cols-2 gap-3">
      <label for="type" class="grid gap-1">
        <span class="text-sm">Tipo</span>
        <?php $ptype = $p['type'] ?? 'simple'; ?>
        <select name="type" id="type" class="border rounded-xl p-2">
          <option value="simple" <?= $ptype === 'simple' ? 'selected' : '' ?>>Simples</option>
          <option value="combo"  <?= $ptype === 'combo'  ? 'selected' : '' ?>>Combo</option>
        </select>
        <small class="text-xs text-gray-500">Combos usam “Grupos de opções”. Produtos simples podem ter Personalização.</small>
      </label>

      <label for="price_mode" class="grid gap-1">
        <span class="text-sm">Modo de preço</span>
        <?php $pmode = $p['price_mode'] ?? 'fixed'; ?>
        <select name="price_mode" id="price_mode" class="border rounded-xl p-2">
          <option value="fixed" <?= $pmode === 'fixed' ? 'selected' : '' ?>>Fixo (preço base)</option>
          <option value="sum"   <?= $pmode === 'sum'   ? 'selected' : '' ?>>Somar itens do grupo</option>
        </select>
        <small class="text-xs text-gray-500">Em “Somar itens”, total = <code>preço base + deltas</code>.</small>
      </label>
    </div>

    <div class="grid md:grid-cols-3 gap-3">
      <label for="price" class="grid gap-1">
        <span class="text-sm">Preço base (R$)</span>
        <input name="price" id="price" type="number" step="0.01" min="0" value="<?= e($p['price'] ?? 0) ?>" class="border rounded-xl p-2" inputmode="decimal" autocomplete="off">
      </label>
      <label for="promo_price" class="grid gap-1">
        <span class="text-sm">Preço promocional (R$)</span>
        <input name="promo_price" id="promo_price" type="number" step="0.01" min="0" value="<?= e($p['promo_price'] ?? '') ?>" class="border rounded-xl p-2" placeholder="Opcional" inputmode="decimal" autocomplete="off">
      </label>
      <label for="sort_order" class="grid gap-1">
        <span class="text-sm">Ordem</span>
        <input name="sort_order" id="sort_order" type="number" step="1" value="<?= e($p['sort_order'] ?? 0) ?>" class="border rounded-xl p-2">
      </label>
    </div>
  </fieldset>

  <!-- Imagem -->
  <fieldset class="grid gap-3">
    <legend class="text-base font-medium">Imagem</legend>
    <div class="grid md:grid-cols-[1fr_auto] gap-3 items-start">
      <label for="image" class="grid gap-1">
        <span class="text-sm">Upload (jpg/png/webp)</span>
        <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.webp" class="border rounded-xl p-2">
        <small class="text-xs text-gray-500">Recomendado: 1000×750px ou maior (4:3). Máx. 5 MB.</small>
      </label>
      <div class="flex flex-col items-center gap-2">
        <span class="text-xs text-gray-500">Pré-visualização</span>
        <img id="image-preview"
             src="<?= !empty($p['image']) ? e(base_url($p['image'])) : e(base_url('assets/logo-placeholder.png')) ?>"
             alt="Pré-visualização"
             class="w-32 h-32 object-cover rounded-lg border">
      </div>
    </div>
  </fieldset>

  <!-- Grupos de opções (COMBO) -->
  <?php $hasGroups = !empty($groups) || (($p['type'] ?? '') !== 'simple'); ?>
  <fieldset class="grid gap-3">
    <legend class="text-base font-medium">Grupos de opções (Combo)</legend>

    <input type="hidden" name="use_groups" value="<?= $hasGroups ? '1' : '0' ?>">

    <label class="inline-flex items-center gap-2">
      <input type="checkbox" id="groups-toggle" name="use_groups" value="1" <?= $hasGroups ? 'checked' : '' ?>>
      <span>Usar grupos de opções (para combos/componentes)</span>
    </label>

    <?php if (empty($simpleProducts)): ?>
      <div class="rounded-lg border border-amber-300 bg-amber-50 p-2 text-sm text-amber-900">
        Nenhum <strong>produto simples</strong> encontrado para esta empresa. Cadastre ao menos um e marque como ativo.
      </div>
    <?php endif; ?>

    <div id="groups-block" class="grid gap-3 <?= $hasGroups ? '' : 'hidden' ?>" aria-hidden="<?= $hasGroups ? 'false' : 'true' ?>">
      <div class="rounded-lg border p-2 text-xs text-gray-600">
        Cada <em>grupo</em> é uma etapa (ex.: “Lanche”, “Acompanhamento”, “Bebida”). Itens são <strong>produtos simples</strong>. Campo <strong>Δ</strong> é o acréscimo.
      </div>

      <div id="groups-container" class="grid gap-2" aria-live="polite">
        <?php if (!empty($groups)): foreach ($groups as $gi => $g): $gi=(int)$gi;
          $gItems= $g['items'] ?? [];
          $curT  = $g['type'] ?? 'single';
          $min   = (int)($g['min_qty'] ?? $g['min'] ?? 0);
          $max   = (int)($g['max_qty'] ?? $g['max'] ?? 1);
        ?>
        <div class="border p-2 rounded-lg group" data-index="<?= $gi ?>">
          <div class="flex flex-wrap gap-2 mb-2">
            <input name="groups[<?= $gi ?>][name]" value="<?= e($g['name'] ?? '') ?>" placeholder="Nome do grupo" class="border rounded p-1 flex-1" required>
            <select name="groups[<?= $gi ?>][type]" class="border rounded p-1">
              <?php foreach (['single','remove','add','swap','component','extra','addon'] as $t): ?>
                <option value="<?= e($t) ?>" <?= $curT === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
              <?php endforeach; ?>
            </select>
            <input type="number" name="groups[<?= $gi ?>][min]" value="<?= $min ?>" placeholder="min" class="border rounded p-1 w-20" min="0">
            <input type="number" name="groups[<?= $gi ?>][max]" value="<?= $max ?>" placeholder="max" class="border rounded p-1 w-20" min="0">
            <button type="button" class="remove-group px-2 border rounded" aria-label="Remover grupo">✕</button>
          </div>

          <div class="items grid gap-2">
            <?php foreach ($gItems as $ii => $it): $ii=(int)$ii;
              $selId = (int)($it['product_id'] ?? 0);
              $delta = (string)($it['delta'] ?? $it['delta_price'] ?? '0');
              $isDef = !empty($it['is_default'] ?? $it['default']);
            ?>
            <div class="flex flex-wrap gap-2 item">
              <div class="min-w-[240px] flex-1">
                <select name="groups[<?= $gi ?>][items][<?= $ii ?>][product_id]" class="border rounded p-1 w-full product-select" required>
                  <option value="">— Selecione um produto simples —</option>
                  <?php foreach ($simpleProducts as $sp): ?>
                    <option value="<?= (int)$sp['id'] ?>" data-price="<?= e((string)($sp['price'] ?? 0)) ?>" <?= $selId === (int)$sp['id'] ? 'selected' : '' ?>>
                      <?= e($sp['name']) ?><?= isset($sp['price']) ? ' — R$ ' . number_format((float)$sp['price'], 2, ',', '.') : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-xs text-gray-500">Preço base: <span class="sp-price">R$ 0,00</span></small>
              </div>
              <input type="text" inputmode="decimal" name="groups[<?= $gi ?>][items][<?= $ii ?>][delta]" value="<?= e($delta) ?>" placeholder="Δ" class="border rounded p-1 w-28 delta-input">
              <label class="flex items-center gap-1 text-xs px-2">
                <input type="checkbox" name="groups[<?= $gi ?>][items][<?= $ii ?>][default]" <?= $isDef ? 'checked' : '' ?>>Default
              </label>
              <button type="button" class="remove-item px-2 border rounded" aria-label="Remover item">✕</button>
            </div>
            <?php endforeach; ?>
          </div>

          <button type="button" class="add-item text-sm mt-2 px-2 py-1 border rounded">+ Item</button>
        </div>
        <?php endforeach; endif; ?>
      </div>

      <button type="button" id="add-group" class="px-2 py-1 border rounded text-sm self-start">+ Grupo</button>
    </div>
  </fieldset>

  <!-- PERSONALIZAÇÃO (MODS) -->
  <?php $hasMods = !empty($mods); ?>
  <fieldset class="grid gap-3">
    <legend class="text-base font-medium">Personalização (tirar/colocar/extra)</legend>

    <!-- Flag -->
    <input type="hidden" name="use_mods" value="<?= $hasMods ? '1' : '0' ?>">

    <label class="inline-flex items-center gap-2">
      <input type="checkbox" id="mods-toggle" name="use_mods" value="1" <?= $hasMods ? 'checked' : '' ?>>
      <span>Permitir personalização de itens</span>
    </label>

    <div id="mods-block" class="grid gap-3 <?= $hasMods ? '' : 'hidden' ?>" aria-hidden="<?= $hasMods ? 'false' : 'true' ?>">
      <div class="rounded-lg border p-2 text-xs text-gray-600">
        Crie grupos como <strong>Itens principais</strong>, <strong>Adicionais</strong>, <strong>Molhos</strong>.<br>
        Tipos: <code>remove</code> (removíveis, Δ=0), <code>add/extra</code> (acrescentam custo), <code>single</code> (escolha única), <code>swap</code> (troca).<br>
      </div>

      <div id="mods-container" class="grid gap-3" aria-live="polite">
        <?php if (!empty($mods)): foreach ($mods as $mi => $mg): $mi=(int)$mi;
          $mItems = $mg['items'] ?? [];
          $mType  = $mg['type'] ?? 'remove';
          $mMin   = (int)($mg['min_qty'] ?? $mg['min'] ?? 0);
          $mMax   = (int)($mg['max_qty'] ?? $mg['max'] ?? 99);
        ?>
        <div class="border p-2 rounded-lg mod-group" data-index="<?= $mi ?>">
          <div class="flex flex-wrap gap-2 mb-2">
            <input name="mods[<?= $mi ?>][name]" value="<?= e($mg['name'] ?? '') ?>" placeholder="Nome do grupo" class="border rounded p-1 flex-1" required>
            <select name="mods[<?= $mi ?>][type]" class="border rounded p-1">
              <?php foreach (['remove','add','extra','swap','single'] as $t): ?>
                <option value="<?= e($t) ?>" <?= $mType === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
              <?php endforeach; ?>
            </select>
            <input type="number" name="mods[<?= $mi ?>][min]" value="<?= $mMin ?>" placeholder="min" class="border rounded p-1 w-20" min="0">
            <input type="number" name="mods[<?= $mi ?>][max]" value="<?= $mMax ?>" placeholder="max" class="border rounded p-1 w-20" min="0">
            <button type="button" class="remove-mod-group px-2 border rounded" aria-label="Remover grupo">✕</button>
          </div>

          <div class="mod-items grid gap-2">
            <?php foreach ($mItems as $ii => $it): $ii=(int)$ii;
              $name   = (string)($it['name'] ?? '');
              $delta  = (string)($it['delta'] ?? '0');
              $isDef  = !empty($it['is_default'] ?? $it['default']);
            ?>
            <div class="flex flex-wrap gap-2 mod-item">
              <input name="mods[<?= $mi ?>][items][<?= $ii ?>][name]" value="<?= e($name) ?>" placeholder="Item (ex.: Cebola, Bacon)" class="border rounded p-1 flex-1" required>
              <input type="text" inputmode="decimal" name="mods[<?= $mi ?>][items][<?= $ii ?>][delta]" value="<?= e($delta) ?>" placeholder="Δ (ex.: 0,00)" class="border rounded p-1 w-28">
              <label class="flex items-center gap-1 text-xs px-2">
                <input type="checkbox" name="mods[<?= $mi ?>][items][<?= $ii ?>][default]" <?= $isDef ? 'checked' : '' ?>>Default
              </label>
              <button type="button" class="remove-mod-item px-2 border rounded" aria-label="Remover item">✕</button>
            </div>
            <?php endforeach; ?>
          </div>

          <button type="button" class="add-mod-item text-sm mt-2 px-2 py-1 border rounded">+ Item</button>
        </div>
        <?php endforeach; endif; ?>
      </div>

      <button type="button" id="add-mod-group" class="px-2 py-1 border rounded text-sm self-start">+ Grupo de personalização</button>
    </div>
  </fieldset>

  <!-- Publicação -->
  <fieldset class="grid gap-3">
    <legend class="text-base font-medium">Publicação</legend>
    <label for="active" class="inline-flex items-center gap-2">
      <input type="checkbox" name="active" id="active" <?= !isset($p['active']) || $p['active'] ? 'checked' : '' ?>>
      <span>Produto ativo</span>
    </label>
  </fieldset>

  <!-- Rodapé de ações -->
  <div class="flex gap-2 pt-2">
    <button class="px-4 py-2 rounded-xl border bg-gray-900 text-white">Salvar</button>
    <a href="<?= e(base_url("admin/{$slug}/products")) ?>" class="px-4 py-2 rounded-xl border">Cancelar</a>
  </div>

  <!-- ===== Templates ===== -->
  <template id="tpl-group">
    <div class="border p-2 rounded-lg group" data-index="__IDX__">
      <div class="flex flex-wrap gap-2 mb-2">
        <input name="groups[__IDX__][name]" placeholder="Nome do grupo" class="border rounded p-1 flex-1" required>
        <select name="groups[__IDX__][type]" class="border rounded p-1">
          <option value="single">Single</option>
          <option value="remove">Remove</option>
          <option value="add">Add</option>
          <option value="swap">Swap</option>
          <option value="component">Component</option>
          <option value="extra">Extra</option>
          <option value="addon">Addon</option>
        </select>
        <input type="number" name="groups[__IDX__][min]" placeholder="min" class="border rounded p-1 w-20" min="0" value="0">
        <input type="number" name="groups[__IDX__][max]" placeholder="max" class="border rounded p-1 w-20" min="0" value="1">
        <button type="button" class="remove-group px-2 border rounded" aria-label="Remover grupo">✕</button>
      </div>
      <div class="items grid gap-2"></div>
      <button type="button" class="add-item text-sm mt-2 px-2 py-1 border rounded">+ Item</button>
    </div>
  </template>

  <template id="tpl-item">
    <div class="flex flex-wrap gap-2 item">
      <div class="min-w-[240px] flex-1">
        <select name="groups[__G__][items][__I__][product_id]" class="border rounded p-1 w-full product-select" required>
          <option value="">— Selecione um produto simples —</option>
          <?php foreach ($simpleProducts as $sp): ?>
            <option value="<?= (int)$sp['id'] ?>" data-price="<?= e((string)($sp['price'] ?? 0)) ?>">
              <?= e($sp['name']) ?><?= isset($sp['price']) ? ' — R$ ' . number_format((float)$sp['price'], 2, ',', '.') : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <small class="text-xs text-gray-500">Preço base: <span class="sp-price">R$ 0,00</span></small>
      </div>
      <input type="text" inputmode="decimal" name="groups[__G__][items][__I__][delta]" placeholder="Δ" class="border rounded p-1 w-28 delta-input" value="0">
      <label class="flex items-center gap-1 text-xs px-2">
        <input type="checkbox" name="groups[__G__][items][__I__][default]">Default
      </label>
      <button type="button" class="remove-item px-2 border rounded" aria-label="Remover item">✕</button>
    </div>
  </template>

  <template id="tpl-mod-group">
    <div class="border p-2 rounded-lg mod-group" data-index="__IDX__">
      <div class="flex flex-wrap gap-2 mb-2">
        <input name="mods[__IDX__][name]" placeholder="Nome do grupo" class="border rounded p-1 flex-1" required>
        <select name="mods[__IDX__][type]" class="border rounded p-1">
          <option value="remove">Remove</option>
          <option value="add">Add</option>
          <option value="extra">Extra</option>
          <option value="swap">Swap</option>
          <option value="single">Single</option>
        </select>
        <input type="number" name="mods[__IDX__][min]" placeholder="min" class="border rounded p-1 w-20" min="0" value="0">
        <input type="number" name="mods[__IDX__][max]" placeholder="max" class="border rounded p-1 w-20" min="0" value="99">
        <button type="button" class="remove-mod-group px-2 border rounded" aria-label="Remover grupo">✕</button>
      </div>
      <div class="mod-items grid gap-2"></div>
      <button type="button" class="add-mod-item text-sm mt-2 px-2 py-1 border rounded">+ Item</button>
    </div>
  </template>

  <template id="tpl-mod-item">
    <div class="flex flex-wrap gap-2 mod-item">
      <input name="mods[__G__][items][__I__][name]" placeholder="Item (ex.: Cebola, Bacon)" class="border rounded p-1 flex-1" required>
      <input type="text" inputmode="decimal" name="mods[__G__][items][__I__][delta]" placeholder="Δ (ex.: 0,00)" class="border rounded p-1 w-28" value="0">
      <label class="flex items-center gap-1 text-xs px-2">
        <input type="checkbox" name="mods[__G__][items][__I__][default]">Default
      </label>
      <button type="button" class="remove-mod-item px-2 border rounded" aria-label="Remover item">✕</button>
    </div>
  </template>

  <!-- Script -->
  <script>
    // ===== Utils =====
    function formatMoney(v){ const n = isNaN(v) ? 0 : Number(v); return n.toLocaleString('pt-BR', { style:'currency', currency:'BRL' }); }
    function brToFloat(v){ if(v==null) return 0; return parseFloat(String(v).replace(/\./g,'').replace(',','.')) || 0; }
    function toggleBlock(block, on){
      block.classList.toggle('hidden', !on);
      block.setAttribute('aria-hidden', String(!on));
      block.classList.toggle('opacity-50', !on);
      block.querySelectorAll('input,select,textarea,button').forEach(n=>{ if(n.type==='hidden') return; n.classList.toggle('pointer-events-none', !on); });
    }
    function ensureMinMax(scope){
      scope.querySelectorAll('input[name$="[min]"]').forEach(minEl=>{
        const maxEl = minEl.parentElement.querySelector('input[name$="[max]"]');
        if(!maxEl) return;
        const min = Number(minEl.value||0), max = Number(maxEl.value||0);
        if(max && max < min) maxEl.value = min;
      });
    }

    // Descrição: contador
    const desc=document.getElementById('description'), count=document.getElementById('desc-count');
    if(desc && count){ const upd=()=>{count.textContent=(desc.value||'').length}; desc.addEventListener('input',upd); upd(); }

    // Imagem: preview
    const inputImg=document.getElementById('image'), preview=document.getElementById('image-preview'); let lastUrl;
    if(inputImg && preview){
      inputImg.addEventListener('change', ()=>{
        const f=inputImg.files && inputImg.files[0]; if(!f) return;
        const ok=/image\/(jpeg|png|webp)/.test(f.type);
        if(!ok || f.size>5*1024*1024){ alert('Formato inválido ou arquivo muito grande. Use JPG/PNG/WEBP até 5MB.'); inputImg.value=''; return; }
        if(lastUrl) URL.revokeObjectURL(lastUrl); lastUrl=URL.createObjectURL(f); preview.src=lastUrl;
      });
    }

    // ===== Grupos (COMPOSIÇÃO) visibilidade por tipo =====
    const typeSel=document.getElementById('type'), groupsToggle=document.getElementById('groups-toggle'), groupsBlock=document.getElementById('groups-block');
    function syncGroupsVisibility(){
      const isComplex = typeSel && typeSel.value !== 'simple';
      if(isComplex){ groupsToggle.checked=true; document.querySelector('input[name="use_groups"][type="hidden"]').value='1'; }
      toggleBlock(groupsBlock, !!groupsToggle?.checked);
    }
    typeSel?.addEventListener('change', syncGroupsVisibility);
    groupsToggle?.addEventListener('change', (e)=>{ document.querySelector('input[name="use_groups"][type="hidden"]').value = e.target.checked?'1':'0'; syncGroupsVisibility(); });
    syncGroupsVisibility();

    // ===== COMBO wiring =====
    const gContainer=document.getElementById('groups-container'), addGroupBtn=document.getElementById('add-group');
    const tplGroup=document.getElementById('tpl-group'), tplItem=document.getElementById('tpl-item');
    function wireProductSelect(selectEl){
      const wrap=selectEl.closest('.item'); const priceL=wrap.querySelector('.sp-price'); const delta=wrap.querySelector('.delta-input');
      const update=()=>{ const opt=selectEl.options[selectEl.selectedIndex]; const baseP=Number(opt?.dataset?.price||0); if(priceL) priceL.textContent=formatMoney(baseP); if(delta && !delta.value) delta.placeholder='Δ (ex.: 0,00)'; };
      selectEl.addEventListener('change', update); update();
    }
    function wireAllProductSelects(ctx){ (ctx||document).querySelectorAll('.product-select').forEach(wireProductSelect); }
    let gIndex=gContainer ? Array.from(gContainer.children).length : 0;
    function addGroup(){ const idx=gIndex++; const html=tplGroup.innerHTML.replace(/__IDX__/g,String(idx)); const div=document.createElement('div'); div.innerHTML=html.trim(); const node=div.firstElementChild; gContainer.appendChild(node); wireAllProductSelects(node); return node; }
    function addItem(groupEl){ const idx=groupEl.dataset.index; const items=groupEl.querySelector('.items'); const iIdx=items.children.length; const html=tplItem.innerHTML.replace(/__G__/g,String(idx)).replace(/__I__/g,String(iIdx)); const wrap=document.createElement('div'); wrap.innerHTML=html.trim(); items.appendChild(wrap.firstElementChild); wireAllProductSelects(groupEl); }
    addGroupBtn?.addEventListener('click', addGroup);
    gContainer?.addEventListener('click', (e)=>{ const t=e.target;
      if(t.classList.contains('add-item')) addItem(t.closest('.group'));
      else if(t.classList.contains('remove-group')) t.closest('.group')?.remove();
      else if(t.classList.contains('remove-item')) t.closest('.item')?.remove();
    });
    wireAllProductSelects(document);

    // ===== PERSONALIZAÇÃO (mods) =====
    const modsToggle=document.getElementById('mods-toggle'), modsBlock=document.getElementById('mods-block'), modsContainer=document.getElementById('mods-container');
    const addModGroup=document.getElementById('add-mod-group'), tplModGroup=document.getElementById('tpl-mod-group'), tplModItem=document.getElementById('tpl-mod-item');
    function syncMods(){ const on=!!modsToggle?.checked; document.querySelector('input[name="use_mods"][type="hidden"]').value=on?'1':'0'; toggleBlock(modsBlock, on); }
    modsToggle?.addEventListener('change', syncMods); syncMods();

    let mIndex=modsContainer ? Array.from(modsContainer.children).length : 0;
    function addModGroupFn(){ const idx=mIndex++; const html=tplModGroup.innerHTML.replace(/__IDX__/g,String(idx)); const div=document.createElement('div'); div.innerHTML=html.trim(); const node=div.firstElementChild; modsContainer.appendChild(node); return node; }
    function addModItemFn(groupEl){ const idx=groupEl.dataset.index; const items=groupEl.querySelector('.mod-items'); const iIdx=items.children.length; const html=tplModItem.innerHTML.replace(/__G__/g,String(idx)).replace(/__I__/g,String(iIdx)); const wrap=document.createElement('div'); wrap.innerHTML=html.trim(); items.appendChild(wrap.firstElementChild); }

    addModGroup?.addEventListener('click', addModGroupFn);
    modsContainer?.addEventListener('click', (e)=>{ const t=e.target;
      if(t.classList.contains('add-mod-item')) addModItemFn(t.closest('.mod-group'));
      else if(t.classList.contains('remove-mod-group')) { t.closest('.mod-group')?.remove(); }
      else if(t.classList.contains('remove-mod-item')) { t.closest('.mod-item')?.remove(); }
    });

    // ===== Validação + normalização =====
    document.getElementById('product-form')?.addEventListener('submit', (e)=>{
      const name=document.getElementById('name');
      if(!name.value.trim()){ e.preventDefault(); alert('Informe o nome do produto.'); name.focus(); return; }

      // Normaliza BR -> float
      ['price','promo_price'].forEach(id=>{ const el=document.getElementById(id); if(!el) return; el.value=String(brToFloat(el.value||'0')); });
      document.querySelectorAll('input[name$="[delta]"]').forEach(el=>{ el.value=String(brToFloat(el.value||'0')); });

      const price=parseFloat(document.getElementById('price').value||'0');
      const promo=parseFloat(document.getElementById('promo_price').value||'0');
      if(promo && price && promo>=price){ e.preventDefault(); alert('O preço promocional deve ser menor que o preço base.'); document.getElementById('promo_price').focus(); return; }

      // COMBO
      if(groupsToggle && groupsToggle.checked){
        const gs=gContainer.querySelectorAll('.group');
        if(!gs.length){ e.preventDefault(); alert('Adicione pelo menos um grupo de opções do combo.'); addGroup(); return; }
        for(const g of gs){
          const gname=g.querySelector('input[name^="groups"][name$="[name]"]');
          const items=g.querySelectorAll('.item');
          ensureMinMax(g);
          const minEl=g.querySelector('input[name$="[min]"]'), maxEl=g.querySelector('input[name$="[max]"]');
          const min=Number(minEl?.value||0), max=Number(maxEl?.value||0);
          if(max && max<min){ e.preventDefault(); alert('No grupo "'+(gname.value||'')+'", o máximo não pode ser menor que o mínimo.'); maxEl.focus(); return; }
          if(!gname.value.trim() || !items.length){ e.preventDefault(); alert('Cada grupo do combo precisa de nome e ao menos um item.'); gname.focus(); return; }
          for(const it of items){ const sel=it.querySelector('select.product-select'); if(!sel.value){ e.preventDefault(); alert('Selecione um produto simples para cada item do combo.'); sel.focus(); return; } }
        }
      }

      // MODS
      if(modsToggle && modsToggle.checked){
        const mgs=modsContainer.querySelectorAll('.mod-group');
        for(const mg of mgs){
          const gname=mg.querySelector('input[name^="mods"][name$="[name]"]');
          const items=mg.querySelectorAll('.mod-item');
          ensureMinMax(mg);
          const minEl=mg.querySelector('input[name$="[min]"]'), maxEl=mg.querySelector('input[name$="[max]"]');
          const min=Number(minEl?.value||0), max=Number(maxEl?.value||0);
          if(max && max<min){ e.preventDefault(); alert('No grupo "'+(gname.value||'')+'", o máximo não pode ser menor que o mínimo.'); maxEl.focus(); return; }
          if(!gname.value.trim()){ e.preventDefault(); alert('Cada grupo de personalização precisa de um nome.'); gname.focus(); return; }
          if(!items.length){ e.preventDefault(); alert('Adicione pelo menos um item no grupo "'+(gname.value||'')+'".'); return; }
          for(const it of items){ const nm=it.querySelector('input[name$="[name]"]'); if(!nm.value.trim()){ e.preventDefault(); alert('Cada item de personalização precisa de um nome.'); nm.focus(); return; } }
        }
      }

    });

    // coerência min/max ao digitar
    ;['groups-container','mods-container'].forEach(id=>{
      const el=document.getElementById(id); if(!el) return;
      el.addEventListener('input', e=>{
        if(e.target.name?.endsWith('[min]') || e.target.name?.endsWith('[max]')) ensureMinMax(el);
      });
    });
  </script>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
