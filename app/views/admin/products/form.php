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

// Personalização
$customization  = $customization  ?? [];           // ['enabled'=>bool, 'groups'=>[...]]
$custEnabled    = !empty($customization['enabled']);
$custGroups     = $customization['groups'] ?? [];

// Título / Ação
$title   = "Produto - " . ($company['name'] ?? '');
$editing = !empty($p['id']);
$slug    = rawurlencode((string)($company['slug'] ?? ''));
$action  = $editing ? "admin/{$slug}/products/" . (int)$p['id'] : "admin/{$slug}/products";

if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
?>
<?php ob_start(); ?>

<!-- ERROS -->
<?php if (!empty($errors) && is_array($errors)): ?>
  <div class="mb-4 rounded-xl border border-red-200 bg-red-50/90 p-3 text-sm text-red-800 shadow-sm">
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
      class="relative grid max-w-4xl gap-6 rounded-2xl border border-slate-200 bg-white p-4 md:p-6 shadow-sm">

  <!-- CSRF / METHOD -->
  <?php if (function_exists('csrf_field')): ?>
    <?= csrf_field() ?>
  <?php elseif (function_exists('csrf_token')): ?>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php endif; ?>
  <?php if ($editing): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

  <!-- TOOLBAR FIXA -->
  <div class="sticky top-0 z-20 -m-4 mb-0 border-b bg-white/85 px-4 py-2 backdrop-blur supports-[backdrop-filter]:bg-white/60">
    <div class="mx-auto flex max-w-4xl items-center justify-between">
      <div class="flex items-center gap-2 text-sm text-slate-800">
        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-slate-100">
          <svg class="h-4 w-4 text-slate-600" viewBox="0 0 24 24" fill="none">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </span>
        <strong><?= $editing ? 'Editar' : 'Novo' ?> produto</strong>
      </div>
      <div class="flex gap-2">
        <a href="<?= e(base_url("admin/{$slug}/products")) ?>"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 6 9 12l6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          Cancelar
        </a>
        <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-1.5 text-sm font-medium text-white shadow hover:opacity-95">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Salvar
        </button>
      </div>
    </div>
  </div>

  <!-- CARD: Dados básicos -->
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 7h14M5 12h10M5 17h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
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
          <button type="button" class="sku-lock-btn" aria-label="Definido automaticamente em ordem crescente e sem repetições."
                  title="Definido automaticamente em ordem crescente e sem repetições.">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M17 11h-1V9a4 4 0 0 0-8 0v2h-.333A1.667 1.667 0 0 0 6 12.667v6.666C6 20.955 6.746 22 7.667 22h8.666C17.254 22 18 20.955 18 19.333v-6.666A1.667 1.667 0 0 0 16.667 11H17Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
              <path d="M15 11H9V9a3 3 0 1 1 6 0v2Z" fill="currentColor" />
            </svg>
            <span class="sku-lock-tooltip">Definido automaticamente em ordem crescente e sem repetições.</span>
          </button>
        </div>
        <input name="sku" id="sku" value="<?= e($p['sku'] ?? '') ?>" placeholder="Gerado automaticamente" autocomplete="off"
               readonly
               class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400">
        <small class="text-xs text-slate-500">Definido automaticamente em ordem crescente e sem repetições.</small>
      </label>
    </div>

    <label for="description" class="mt-3 grid gap-1">
      <span class="text-sm text-slate-700">Descrição</span>
      <textarea name="description" id="description" rows="3" maxlength="300" placeholder="Até 300 caracteres"
                class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400"><?= e($p['description'] ?? '') ?></textarea>
      <div class="flex justify-between text-xs text-slate-500">
        <span>Mostrada na página do produto.</span>
        <span><span id="desc-count">0</span>/300</span>
      </div>
    </label>
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
          <option value="combo"  <?= $ptype === 'combo'  ? 'selected' : '' ?>>Combo</option>
        </select>
        <small class="text-xs text-slate-500">Combos usam “Grupos de opções”. Produtos simples podem ter Personalização.</small>
      </label>

      <label for="price_mode" class="grid gap-1">
        <span class="text-sm text-slate-700">Modo de preço</span>
        <?php $pmode = $p['price_mode'] ?? 'fixed'; ?>
        <select name="price_mode" id="price_mode" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 focus:ring-2 focus:ring-indigo-400">
          <option value="fixed" <?= $pmode === 'fixed' ? 'selected' : '' ?>>Fixo (preço base)</option>
          <option value="sum"   <?= $pmode === 'sum'   ? 'selected' : '' ?>>Somar itens do grupo</option>
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

  <!-- CARD: Imagem -->
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm">
    <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Imagem
    </legend>

    <div class="grid items-start gap-3 md:grid-cols-[1fr_auto]">
      <label for="image" class="grid gap-1">
        <span class="text-sm text-slate-700">Upload (jpg/png/webp)</span>
        <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.webp"
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
        <small class="text-xs text-slate-500">Recomendado: 1000×750px ou maior (4:3). Máx. 5 MB.</small>
      </label>
      <div class="flex flex-col items-center gap-2">
        <span class="text-xs text-slate-500">Pré-visualização</span>
        <img id="image-preview"
             src="<?= !empty($p['image']) ? e(base_url($p['image'])) : e(base_url('assets/logo-placeholder.png')) ?>"
             alt="Pré-visualização"
             class="h-32 w-32 rounded-xl border border-slate-200 object-cover shadow-sm">
      </div>
    </div>
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
    /* Personalização */
    #cust-groups-container .cust-group{transition:transform .18s ease,box-shadow .18s ease,opacity .18s ease}
    #cust-groups-container .cust-group.dragging{opacity:.85;transform:scale(.985);box-shadow:0 18px 35px -20px rgba(15,23,42,.45)}
    .cust-drag-ghost{box-sizing:border-box;border-radius:.75rem;box-shadow:0 18px 35px -20px rgba(15,23,42,.45)}

    /* Combo */
    #groups-container .group-card{transition:transform .18s ease,box-shadow .18s ease,opacity .18s ease}
    #groups-container .group-card.dragging{opacity:.85;transform:scale(.985);box-shadow:0 18px 35px -20px rgba(15,23,42,.45)}
    .combo-drag-ghost{box-sizing:border-box;border-radius:.75rem;box-shadow:0 18px 35px -20px rgba(15,23,42,.45)}
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
        <?php if (!empty($groups)): foreach ($groups as $gi => $g): $gi=(int)$gi;
          $gItems = $g['items'] ?? [];
          $min    = (int)($g['min_qty'] ?? $g['min'] ?? 0);
          $max    = (int)($g['max_qty'] ?? $g['max'] ?? 1);
          $sort   = isset($g['sort_order']) ? (int)$g['sort_order'] : $gi;
        ?>
        <div class="group-card rounded-2xl border border-slate-200 bg-white shadow-sm" data-index="<?= $gi ?>">
          <div class="flex items-center gap-3 border-b border-slate-200 p-3">
            <button type="button" draggable="true" class="combo-drag-handle inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400 hover:text-slate-600" title="Arrastar">↕</button>
            <input type="text" name="groups[<?= $gi ?>][name]"
                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400"
                   placeholder="Nome do grupo" value="<?= e($g['name'] ?? '') ?>" required />
            <input type="hidden" class="combo-order-input" name="groups[<?= $gi ?>][sort_order]" value="<?= $sort ?>">
            <button type="button" class="remove-group shrink-0 rounded-full p-2 text-slate-400 hover:text-red-600" aria-label="Remover grupo">✕</button>
          </div>

          <?php if (!empty($gItems)): foreach ($gItems as $ii => $it):
            $ii    = (int)$ii;
            $selId = (int)($it['product_id'] ?? 0);
            $isDef = !empty($it['is_default'] ?? $it['default']);
          ?>
          <div class="item-row grid grid-cols-1 gap-3 p-3 md:grid-cols-[minmax(0,1fr)_160px_72px_72px_auto_40px] md:items-center" data-item-index="<?= $ii ?>">
            <div>
              <label class="block text-xs text-slate-500">Produto</label>
              <select name="groups[<?= $gi ?>][items][<?= $ii ?>][product_id]"
                      class="product-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2"
                      title="Selecione um item da lista." required>
                <option value="">— Selecione um produto simples —</option>
                <?php foreach ($simpleProducts as $sp): ?>
                  <option value="<?= (int)$sp['id'] ?>" data-price="<?= e((string)($sp['price'] ?? '0')) ?>" <?= $selId === (int)$sp['id'] ? 'selected' : '' ?>>
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
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
              <input type="checkbox" name="groups[<?= $gi ?>][items][<?= $ii ?>][default]" value="1" <?= $isDef ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-indigo-600">
              <span>Default</span>
            </label>
            <div class="flex justify-end">
              <button type="button" class="remove-item shrink-0 rounded-full p-2 text-slate-400 hover:text-red-600" aria-label="Remover item">✕</button>
            </div>
            <input type="hidden" name="groups[<?= $gi ?>][items][<?= $ii ?>][delta]" value="0">
          </div>
          <?php endforeach; else: ?>
          <div class="item-row grid grid-cols-1 gap-3 p-3 md:grid-cols-[minmax(0,1fr)_160px_72px_72px_auto_40px] md:items-center" data-item-index="0">
            <div>
              <label class="block text-xs text-slate-500">Produto</label>
              <select name="groups[<?= $gi ?>][items][0][product_id]" class="product-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2" title="Selecione um item da lista." required>
                <option value="">— Selecione um produto simples —</option>
                <?php foreach ($simpleProducts as $sp): ?>
                  <option value="<?= (int)$sp['id'] ?>" data-price="<?= e((string)($sp['price'] ?? '0')) ?>">
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
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
              <input type="checkbox" name="groups[<?= $gi ?>][items][0][default]" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600">
              <span>Default</span>
            </label>
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
  <fieldset class="rounded-2xl border border-slate-200 p-4 md:p-5 shadow-sm" aria-labelledby="legend-custom">
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
        <?php if (!empty($custGroups)): foreach ($custGroups as $gi => $cg): $gi=(int)$gi;
          $cgName = $cg['name'] ?? '';
          $cItems = $cg['items'] ?? [[]];
          $gType  = $cg['type'] ?? 'extra';
          $gMode  = in_array($gType, ['single','addon'], true) ? 'choice' : 'extra';
          $gMin   = isset($cg['min']) ? max(0, (int)$cg['min']) : 0;
          $gMax   = isset($cg['max']) ? max($gMin, (int)$cg['max']) : ($gMode === 'choice' ? max(1, count($cItems)) : 99);
          if ($gType === 'single') { $gMax = 1; }
        ?>
        <div class="cust-group rounded-2xl border border-slate-200 bg-white shadow-sm" data-index="<?= $gi ?>" data-mode="<?= e($gMode) ?>">
          <div class="flex flex-col gap-3 border-b border-slate-200 p-3">
            <div class="flex items-center gap-3">
              <button type="button" draggable="true" class="cust-drag-handle inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400 hover:text-slate-600" title="Arrastar">↕</button>
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
          <?php foreach ($cItems as $ii => $ci): $ii=(int)$ii;
            $selId = isset($ci['ingredient_id']) ? (int)$ci['ingredient_id'] : 0;
            $def   = !empty($ci['default']);
            $minQ  = isset($ci['min_qty']) ? (int)$ci['min_qty'] : 0;
            $maxQ  = isset($ci['max_qty']) ? (int)$ci['max_qty'] : 1;
            if ($maxQ < $minQ) { $maxQ = $minQ; }
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
        <?php endforeach; else: ?>
        <!-- grupo vazio inicial -->
        <div class="cust-group rounded-2xl border border-slate-200 bg-white shadow-sm" data-index="0" data-mode="extra">
          <div class="flex flex-col gap-3 border-b border-slate-200 p-3">
            <div class="flex items-center gap-3">
              <button type="button" draggable="true" class="cust-drag-handle inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400 hover:text-slate-600" title="Arrastar">↕</button>
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
              <span class="mb-1 block text-xs text-slate-500">Limites</span>
              <div class="cust-limits grid gap-2 md:grid-cols-2" data-min="0" data-max="1">
                <div>
                  <label class="block text-xs text-slate-500">Quantidade mínima</label>
                  <input type="number" class="cust-min-input w-24 rounded-lg border border-slate-300 px-3 py-2"
                         name="customization[groups][0][items][0][min_qty]" value="0" min="0" step="1">
                </div>
                <div>
                  <label class="block text-xs text-slate-500">Quantidade máxima</label>
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
    <div class="group-card rounded-2xl border border-slate-200 bg-white shadow-sm" data-index="__GI__">
      <div class="flex items-center gap-3 border-b border-slate-200 p-3">
        <button type="button" draggable="true" class="combo-drag-handle inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400 hover:text-slate-600" title="Arrastar">↕</button>
        <input type="text" name="groups[__GI__][name]" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 focus:ring-2 focus:ring-indigo-400" placeholder="Nome do grupo" value="" required />
        <input type="hidden" class="combo-order-input" name="groups[__GI__][sort_order]" value="__GI__">
        <button type="button" class="remove-group shrink-0 rounded-full p-2 text-slate-400 hover:text-red-600" aria-label="Remover grupo">✕</button>
      </div>

      <div class="item-row grid grid-cols-1 gap-3 p-3 md:grid-cols-[minmax(0,1fr)_160px_72px_72px_auto_40px] md:items-center" data-item-index="0">
        <div>
          <label class="block text-xs text-slate-500">Produto</label>
          <select name="groups[__GI__][items][0][product_id]" class="product-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2" title="Selecione um item da lista." required>
            <option value="">— Selecione um produto simples —</option>
            <?php foreach ($simpleProducts as $sp): ?>
              <option value="<?= (int)$sp['id'] ?>" data-price="<?= e((string)($sp['price'] ?? '0')) ?>">
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

        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600" name="groups[__GI__][items][0][default]" value="1">
          <span>Default</span>
        </label>

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
    <div class="item-row grid grid-cols-1 gap-3 p-3 md:grid-cols-[minmax(0,1fr)_160px_72px_72px_auto_40px] md:items-center" data-item-index="__II__">
      <div>
        <label class="block text-xs text-slate-500">Produto</label>
        <select name="groups[__GI__][items][__II__][product_id]" class="product-select w-full rounded-lg border border-slate-300 bg-white px-3 py-2" title="Selecione um item da lista." required>
          <option value="">— Selecione um produto simples —</option>
          <?php foreach ($simpleProducts as $sp): ?>
            <option value="<?= (int)$sp['id'] ?>" data-price="<?= e((string)($sp['price'] ?? '0')) ?>">
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

      <label class="inline-flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600" name="groups[__GI__][items][__II__][default]" value="1">
        <span>Default</span>
      </label>

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
          <button type="button" draggable="true" class="cust-drag-handle inline-flex cursor-move items-center justify-center rounded-full border border-slate-200 bg-slate-50 p-2 text-slate-400 hover:text-slate-600" title="Arrastar">↕</button>
          <input type="text" name="customization[groups][__CGI__][name]"
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
          <span class="mb-1 block text-xs text-slate-500">Limites</span>
          <div class="cust-limits grid gap-2 md:grid-cols-2" data-min="0" data-max="1">
            <div>
              <label class="block text-xs text-slate-500">Quantidade mínima</label>
              <input type="number" class="cust-min-input w-24 rounded-lg border border-slate-300 px-3 py-2" name="customization[groups][__CGI__][items][0][min_qty]" value="0" min="0" step="1">
            </div>
            <div>
              <label class="block text-xs text-slate-500">Quantidade máxima</label>
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
        <span class="mb-1 block text-xs text-slate-500">Limites</span>
        <div class="cust-limits grid gap-2 md:grid-cols-2" data-min="0" data-max="1">
          <div>
            <label class="block text-xs text-slate-500">Quantidade mínima</label>
            <input type="number" class="cust-min-input w-24 rounded-lg border border-slate-300 px-3 py-2" name="customization[groups][__CGI__][items][__CII__][min_qty]" value="0" min="0" step="1">
          </div>
          <div>
            <label class="block text-xs text-slate-500">Quantidade máxima</label>
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

  <!-- ===== SCRIPT ===== -->
  <script>
    // Utils
    function formatMoney(v){ const n=isNaN(v)?0:Number(v); return n.toLocaleString('pt-BR',{style:'currency',currency:'BRL'}); }
    function brToFloat(v){ if(v==null)return 0; const raw=String(v).trim(); return raw.includes(',')?parseFloat(raw.replace(/\./g,'').replace(',','.'))||0:parseFloat(raw)||0; }
    function toggleBlock(el,on){ el.classList.toggle('hidden',!on); el.setAttribute('aria-hidden',String(!on)); }
    function ensureMinMax(scope){
      scope.querySelectorAll('input[name$="[min]"]').forEach(minEl=>{
        const wrap=minEl.closest('.cust-group')||minEl.closest('.group-card')||scope;
        const maxEl=wrap.querySelector('input[name$="[max]"]'); if(!maxEl) return;
        const min=Number(minEl.value||0), max=Number(maxEl.value||0); if(max && max<min) maxEl.value=min;
      });
    }

    // ===== contador descrição & preview imagem (já na Parte 1) =====

    // ===== Visibilidade de Combo =====
    const groupsToggle=document.getElementById('groups-toggle');
    const hiddenUse=document.getElementById('use_groups_hidden');
    const groupsWrap=document.getElementById('groups-wrap');
    function syncGroupsVisibility(){ if(groupsToggle){ toggleBlock(groupsWrap,!!groupsToggle.checked); groupsToggle.setAttribute('aria-expanded', groupsToggle.checked?'true':'false'); } }
    groupsToggle?.addEventListener('change', e=>{ if(hiddenUse) hiddenUse.value=e.target.checked?'1':'0'; syncGroupsVisibility(); });
    syncGroupsVisibility();

    // ===== COMBO wiring =====
    const gContainer=document.getElementById('groups-container'),
          addGroupBtn=document.getElementById('add-group'),
          tplGroup=document.getElementById('tpl-group'),
          tplItem=document.getElementById('tpl-item');

    function updateItemPrice(row){
      const sel=row.querySelector('.product-select');
      const box=row.querySelector('.sp-price');
      const price=sel?.selectedOptions?.[0]?.dataset?.price ?? '0';
      const num=Number(String(price).replace(/\./g,'').replace(',','.'))||0;
      if(box) box.textContent=formatMoney(num);
      return num;
    }
    function updateGroupFooter(groupEl){
      let sum=0;
      groupEl.querySelectorAll('.item-row').forEach(r=>{
        const def=r.querySelector('input[type=checkbox][name*="[default]"]');
        if(def?.checked) sum+=updateItemPrice(r);
      });
      const footer=groupEl.querySelector('.group-base-price');
      if(footer) footer.textContent=`Preço base: ${formatMoney(sum)}`;
    }
    function wireItemRow(row){
      const sel=row.querySelector('.product-select');
      const def=row.querySelector('input[type=checkbox][name*="[default]"]');
      if(sel){ sel.addEventListener('change',()=>{ updateItemPrice(row); updateGroupFooter(row.closest('.group-card')); }); updateItemPrice(row); }
      if(def){ def.addEventListener('change',()=>updateGroupFooter(row.closest('.group-card'))); }
    }
    document.querySelectorAll('.group-card').forEach(g=>{ g.querySelectorAll('.item-row').forEach(wireItemRow); updateGroupFooter(g); });

    let gIndex=gContainer?Array.from(gContainer.children).length:0;
    function addGroup(){
      const gi=gIndex++;
      const html=tplGroup.innerHTML.replaceAll('__GI__',gi);
      const wrap=document.createElement('div'); wrap.innerHTML=html.trim();
      const el=wrap.firstElementChild;
      gContainer.appendChild(el);
      el.querySelectorAll('.item-row').forEach(wireItemRow);
      updateGroupFooter(el);
      refreshComboGroupOrder();
      return el;
    }
    function nextItemIndex(groupEl){
      const idxs=Array.from(groupEl.querySelectorAll('.item-row')).map(r=>Number(r.dataset.itemIndex||0));
      return idxs.length?Math.max(...idxs)+1:0;
    }
    function addItem(groupEl){
      const gi=Number(groupEl.dataset.index);
      const ii=nextItemIndex(groupEl);
      const html=tplItem.innerHTML.replaceAll('__GI__',gi).replaceAll('__II__',ii);
      const wrap=document.createElement('div'); wrap.innerHTML=html.trim();
      const row=wrap.firstElementChild;
      const footer=groupEl.querySelector('.group-base-price')?.parentElement;
      (footer?groupEl.insertBefore(row,footer):groupEl.appendChild(row));
      row.dataset.itemIndex=ii;
      wireItemRow(row);
      updateGroupFooter(groupEl);
      return row;
    }
    addGroupBtn?.addEventListener('click', addGroup);
    gContainer?.addEventListener('click', ev=>{
      const t=ev.target;
      if(t.classList.contains('add-item')){ addItem(t.closest('.group-card')); }
      if(t.classList.contains('remove-group')){ t.closest('.group-card')?.remove(); refreshComboGroupOrder(); }
      if(t.classList.contains('remove-item')){ const g=t.closest('.group-card'); t.closest('.item-row')?.remove(); if(g) updateGroupFooter(g); }
    });

    // ===== DRAG & DROP — COMBO =====
    let comboDragging=null, comboGhost=null;
    function getDragAfterElement(container,y,selector){
      const siblings=Array.from(container.querySelectorAll(selector)).filter(el=>el!==comboDragging);
      let closest={offset:Number.NEGATIVE_INFINITY,element:null};
      for(const child of siblings){
        const box=child.getBoundingClientRect(); const offset=y-(box.top+box.height/2);
        if(offset<0 && offset>closest.offset){ closest={offset,element:child}; }
      }
      return closest.element;
    }
    function refreshComboGroupOrder(){
      gContainer?.querySelectorAll('.group-card').forEach((g,idx)=>{
        g.dataset.index=idx;
        const inp=g.querySelector('.combo-order-input'); if(inp) inp.value=String(idx);
        // renumera nomes para manter índices coerentes (opcional: se não quiser, remova)
      });
    }
    gContainer?.addEventListener('dragstart', e=>{
      const handle=e.target.closest('.combo-drag-handle'); if(!handle){ e.preventDefault(); return; }
      const card=handle.closest('.group-card'); if(!card){ e.preventDefault(); return; }
      comboDragging=card; card.classList.add('dragging');
      if(e.dataTransfer){
        e.dataTransfer.effectAllowed='move'; e.dataTransfer.setData('text/plain','');
        const rect=card.getBoundingClientRect();
        const ghost=card.cloneNode(true);
        ghost.classList.add('combo-drag-ghost'); ghost.style.width=`${rect.width}px`; ghost.style.height=`${rect.height}px`;
        ghost.style.position='fixed'; ghost.style.top='-9999px'; ghost.style.left='-9999px'; ghost.style.opacity='0.85'; ghost.style.pointerEvents='none';
        document.body.appendChild(ghost); comboGhost=ghost;
        const offsetX=(e.clientX-rect.left)||rect.width/2, offsetY=(e.clientY-rect.top)||rect.height/2;
        e.dataTransfer.setDragImage(ghost, offsetX, offsetY);
      }
    });
    gContainer?.addEventListener('dragend', ()=>{
      if(comboDragging){ comboDragging.classList.remove('dragging'); comboDragging=null; refreshComboGroupOrder(); }
      if(comboGhost){ comboGhost.remove(); comboGhost=null; }
    });
    gContainer?.addEventListener('dragover', e=>{
      if(!comboDragging) return; e.preventDefault();
      const after=getDragAfterElement(gContainer, e.clientY, '.group-card');
      if(!after){ gContainer.appendChild(comboDragging); }
      else if(after!==comboDragging){ gContainer.insertBefore(comboDragging, after); }
    });
    gContainer?.addEventListener('drop', e=>{ if(!comboDragging) return; e.preventDefault(); refreshComboGroupOrder(); });

    // ===== PERSONALIZAÇÃO =====
    const custToggle=document.getElementById('customization-enabled');
    const custHidden=document.getElementById('customization-enabled-hidden');
    const custWrap=document.getElementById('customization-wrap');
    const custCont=document.getElementById('cust-groups-container');
    const custAddGrp=document.getElementById('cust-add-group');
    const tplCustGrp=document.getElementById('tpl-cust-group');
    const tplCustItm=document.getElementById('tpl-cust-item');

    function refreshCustGroupOrder(){
      custCont?.querySelectorAll('.cust-group').forEach((g,idx)=>{
        const order=g.querySelector('.cust-order-input'); if(order) order.value=String(idx);
      });
    }
    function updateCustItem(itemEl){
      if(!itemEl) return;
      const groupEl=itemEl.closest('.cust-group');
      const mode=groupEl?.dataset.mode==='choice'?'choice':'extra';
      const limits=itemEl.querySelector('.cust-limits');
      const minInput=itemEl.querySelector('.cust-min-input');
      const maxInput=itemEl.querySelector('.cust-max-input');
      const qtyWrap=itemEl.querySelector('.cust-default-qty-wrap');
      const qtyInput=itemEl.querySelector('.cust-default-qty');
      const checkbox=itemEl.querySelector('.cust-default-toggle');
      const flag=itemEl.querySelector('.cust-default-flag');

      let min=Number(minInput?.value ?? 0), max=Number(maxInput?.value ?? min);
      if(mode==='choice'){
        min=0; max=1;
        if(minInput){ minInput.value='0'; minInput.readOnly=true; }
        if(maxInput){ maxInput.value='1'; maxInput.readOnly=true; }
      }else{
        if(Number.isNaN(min)||min<0) min=0;
        if(Number.isNaN(max)||max<min) max=min;
        if(minInput){ minInput.value=String(min); minInput.readOnly=false; }
        if(maxInput){ maxInput.value=String(max); maxInput.readOnly=false; }
      }
      if(limits){ limits.dataset.min=String(min); limits.dataset.max=String(max); }
      if(qtyInput){ qtyInput.min=String(min); qtyInput.max=String(max); if(qtyInput.value===''||Number(qtyInput.value)<min) qtyInput.value=String(min); if(Number(qtyInput.value)>max) qtyInput.value=String(max); }

      const isActive=!!checkbox?.checked; if(flag) flag.value=isActive?'1':'0';
      if(!isActive && qtyInput){ qtyInput.value=String(min); }
      if(qtyWrap){ qtyWrap.classList.toggle('hidden', mode==='choice' || !isActive); }
    }
    function applyCustMode(groupEl){
      const select=groupEl.querySelector('.cust-mode-select');
      const choiceWrap=groupEl.querySelector('.cust-choice-settings');
      const addItemBtn=groupEl.querySelector('.cust-add-item');
      const addChoiceBtn=groupEl.querySelector('.cust-add-choice');
      const mode=select?.value==='choice'?'choice':'extra'; groupEl.dataset.mode=mode;
      toggleBlock(choiceWrap, mode==='choice');
      if(addItemBtn) addItemBtn.textContent = mode==='choice' ? '+ Opção' : '+ Ingrediente';
      if(addChoiceBtn) addChoiceBtn.classList.toggle('hidden', mode==='choice');
      groupEl.querySelectorAll('.cust-limits-wrap').forEach(w=>w.classList.toggle('hidden', mode==='choice'));
      groupEl.querySelectorAll('.cust-item').forEach(updateCustItem);
    }
    function wireCustItem(itemEl){
      if(!itemEl) return;
      const flag=itemEl.querySelector('.cust-default-flag');
      const checkbox=itemEl.querySelector('.cust-default-toggle');
      if(flag && checkbox){ checkbox.checked = flag.value==='1'; }
      updateCustItem(itemEl);
    }
    function wireCustGroup(groupEl){
      if(!groupEl) return;
      const select=groupEl.querySelector('.cust-mode-select');
      if(select && !groupEl.dataset.mode){ groupEl.dataset.mode = select.value==='choice' ? 'choice' : 'extra'; }
      else if(select){ select.value = groupEl.dataset.mode==='choice' ? 'choice' : 'extra'; }
      groupEl.querySelectorAll('.cust-item').forEach(wireCustItem);
      applyCustMode(groupEl);
    }
    function nextCustGroupIndex(){
      const idxs=Array.from(custCont.querySelectorAll('.cust-group')).map(g=>Number(g.dataset.index||0));
      return idxs.length?Math.max(...idxs)+1:0;
    }
    function nextCustItemIndex(groupEl){
      const idxs=Array.from(groupEl.querySelectorAll('.cust-item')).map(r=>Number(r.dataset.itemIndex||0));
      return idxs.length?Math.max(...idxs)+1:0;
    }
    function addCustGroup(){
      const gi=nextCustGroupIndex();
      const html=tplCustGrp.innerHTML.replaceAll('__CGI__',gi);
      const wrap=document.createElement('div'); wrap.innerHTML=html.trim();
      const node=wrap.firstElementChild;
      custCont.appendChild(node);
      wireCustGroup(node);
      refreshCustGroupOrder();
      return node;
    }
    function addCustItem(groupEl){
      const gi=Number(groupEl.dataset.index);
      const ii=nextCustItemIndex(groupEl);
      const html=tplCustItm.innerHTML.replaceAll('__CGI__',gi).replaceAll('__CII__',ii);
      const wrap=document.createElement('div'); wrap.innerHTML=html.trim();
      const row=wrap.firstElementChild;
      const footer=Array.from(groupEl.children).find(el=>el.matches('.flex.border-t, .border-t'));
      (footer?groupEl.insertBefore(row,footer):groupEl.appendChild(row));
      row.dataset.itemIndex=ii;
      wireCustItem(row); applyCustMode(groupEl);
      return row;
    }
    custAddGrp?.addEventListener('click', addCustGroup);
    custCont?.addEventListener('click', e=>{
      const t=e.target;
      if(t.classList.contains('cust-add-item')){ addCustItem(t.closest('.cust-group')); }
      else if(t.classList.contains('cust-add-choice')){ const g=t.closest('.cust-group'); const sel=g?.querySelector('.cust-mode-select'); if(sel){ sel.value='choice'; } applyCustMode(g); addCustItem(g); }
      else if(t.classList.contains('cust-remove-group')){ t.closest('.cust-group')?.remove(); refreshCustGroupOrder(); }
      else if(t.classList.contains('cust-remove-item')){ t.closest('.cust-item')?.remove(); }
    });

    // DRAG & DROP — PERSONALIZAÇÃO
    let custDragging=null, custGhost=null;
    function getCustAfterElement(container,y){
      const siblings=Array.from(container.querySelectorAll('.cust-group')).filter(el=>el!==custDragging);
      let closest={offset:Number.NEGATIVE_INFINITY,element:null};
      for(const child of siblings){
        const box=child.getBoundingClientRect(); const offset=y-(box.top+box.height/2);
        if(offset<0 && offset>closest.offset){ closest={offset,element:child}; }
      }
      return closest.element;
    }
    custCont?.addEventListener('dragstart', e=>{
      const handle=e.target.closest('.cust-drag-handle'); if(!handle){ e.preventDefault(); return; }
      const group=handle.closest('.cust-group'); if(!group){ e.preventDefault(); return; }
      custDragging=group; group.classList.add('dragging');
      if(e.dataTransfer){
        e.dataTransfer.effectAllowed='move'; e.dataTransfer.setData('text/plain','');
        const rect=group.getBoundingClientRect();
        const ghost=group.cloneNode(true);
        ghost.classList.add('cust-drag-ghost'); ghost.style.width=`${rect.width}px`; ghost.style.height=`${rect.height}px`;
        ghost.style.position='fixed'; ghost.style.top='-9999px'; ghost.style.left='-9999px'; ghost.style.opacity='0.85'; ghost.style.pointerEvents='none';
        document.body.appendChild(ghost); custGhost=ghost;
        const offsetX=(e.clientX-rect.left)||rect.width/2, offsetY=(e.clientY-rect.top)||rect.height/2;
        e.dataTransfer.setDragImage(ghost, offsetX, offsetY);
      }
    });
    custCont?.addEventListener('dragend', ()=>{
      if(custDragging){ custDragging.classList.remove('dragging'); custDragging=null; refreshCustGroupOrder(); }
      if(custGhost){ custGhost.remove(); custGhost=null; }
    });
    custCont?.addEventListener('dragover', e=>{
      if(!custDragging) return; e.preventDefault();
      const after=getCustAfterElement(custCont, e.clientY);
      if(!after){ custCont.appendChild(custDragging); }
      else if(after!==custDragging){ custCont.insertBefore(custDragging, after); }
    });
    custCont?.addEventListener('drop', e=>{ if(!custDragging) return; e.preventDefault(); refreshCustGroupOrder(); });

    // ===== toggle Personalização =====
    function syncCust(){ const on=!!custToggle?.checked; if(custHidden) custHidden.value=on?'1':'0'; toggleBlock(custWrap,on); }
    custToggle?.addEventListener('change', syncCust); syncCust();

    // ===== validação & normalização no submit =====
    document.getElementById('product-form')?.addEventListener('submit', (e)=>{
      const name=document.getElementById('name');
      if(!name.value.trim()){ e.preventDefault(); alert('Informe o nome do produto.'); name.focus(); return; }

      const priceEl=document.getElementById('price'); if(priceEl){ priceEl.value=String(brToFloat(priceEl.value||'0')); }
      const promoEl=document.getElementById('promo_price');
      if(promoEl){ const raw=promoEl.value==null?'':String(promoEl.value).trim(); promoEl.value = raw==='' ? '' : String(brToFloat(raw)); }
      const price=parseFloat((priceEl?.value||'0')); const promoRaw=promoEl?.value ?? ''; const promo = promoRaw==='' ? null : parseFloat(promoRaw||'0');
      if(promoEl && promo!==null && !Number.isNaN(promo)){
        if(price<=0 || promo<=0){ promoEl.value=''; }
        else if(promo>=price){ e.preventDefault(); alert('O preço promocional deve ser menor que o preço base.'); promoEl.focus(); return; }
      }

      if(groupsToggle && groupsToggle.checked){
        const gs=gContainer.querySelectorAll('.group-card');
        if(!gs.length){ e.preventDefault(); alert('Adicione pelo menos um grupo de opções do combo.'); return; }
        for(const g of gs){
          const gname=g.querySelector('input[name^="groups"][name$="[name]"]'); const items=g.querySelectorAll('.item-row');
          ensureMinMax(g);
          const minEl=g.querySelector('input[name$="[min]"]'), maxEl=g.querySelector('input[name$="[max]"]');
          const min=Number(minEl?.value||0), max=Number(maxEl?.value||0);
          if(max && max<min){ e.preventDefault(); alert('No grupo "'+(gname.value||'')+'", o máximo não pode ser menor que o mínimo.'); maxEl.focus(); return; }
          if(!gname.value.trim() || !items.length){ e.preventDefault(); alert('Cada grupo do combo precisa de nome e ao menos um item.'); gname.focus(); return; }
          for(const it of items){ const sel=it.querySelector('select.product-select'); if(!sel.value){ e.preventDefault(); alert('Selecione um produto simples para cada item do combo.'); sel.focus(); return; } }
        }
      }

      if(custToggle && custToggle.checked){
        const cgs=custCont.querySelectorAll('.cust-group');
        if(!cgs.length){ e.preventDefault(); alert('Adicione pelo menos um grupo de personalização.'); return; }
        for(const cg of cgs){
          const nameEl=cg.querySelector('input[name^="customization"][name$="[name]"]'); const items=cg.querySelectorAll('.cust-item');
          if(!nameEl.value.trim()){ e.preventDefault(); alert('Cada grupo de personalização precisa de um nome.'); nameEl.focus(); return; }
          if(!items.length){ e.preventDefault(); alert('Adicione pelo menos um ingrediente no grupo "'+(nameEl.value||'')+'".'); return; }
          for(const it of items){
            const sel=it.querySelector('.cust-ingredient-select'); if(!sel || !sel.value){ e.preventDefault(); alert('Selecione um ingrediente em cada item do grupo "'+(nameEl.value||'')+'".'); sel?.focus(); return; }
            const limits=it.querySelector('.cust-limits'); const min=limits?Number(limits.dataset.min ?? 0):0; const max=limits?Number(limits.dataset.max ?? 1):1;
            const toggleCheckbox=it.querySelector('.cust-default-toggle'); const qty=it.querySelector('.cust-default-qty');
            if(toggleCheckbox?.checked){ const val=qty ? Number(qty.value||min) : min; if(val<min || val>max){ e.preventDefault(); alert('A quantidade padrão precisa estar entre o mínimo e máximo do ingrediente escolhido.'); qty?.focus(); return; } }
          }
        }
      }
    });

    // Inicializações
    document.querySelectorAll('.cust-group').forEach(wireCustGroup);
    refreshCustGroupOrder();
    refreshComboGroupOrder();
  </script>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
