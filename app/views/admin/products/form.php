<?php
// admin/products/form.php — Formulário de produtos

/* ===== Guard rails / Vars padrão ===== */
$p              = $p              ?? [];
$company        = $company        ?? [];
$cats           = $cats           ?? [];
$groups         = $groups         ?? [];           // COMPOSIÇÃO (combo)
$simpleProducts = $simpleProducts ?? [];           // para combos
$ingredients    = $ingredients    ?? [];
$errors         = $errors         ?? [];

// NOVO: estrutura opcional para o layout de Personalização
$customization  = $customization  ?? [];           // ['enabled'=>bool, 'groups'=>[...]]
$custEnabled    = !empty($customization['enabled']); // padrão agora: false
$custGroups     = $customization['groups'] ?? [];

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
  <div class="sticky top-0 z-10 -m-4 mb-2 bg-white/80 backdrop-blur supports-[backdrop-filter]:bg-white/60 border-b px-4  py-2 flex items-center justify-between">
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

  <!-- Grupos de opções (COMBO) – LAYOUT NOVO -->
  <?php $hasGroups = !empty($groups); // <- NÃO marca por padrão ?>
  <fieldset class="grid gap-3" aria-labelledby="legend-groups">
    <legend id="legend-groups" class="text-base font-medium">Grupos de opções (Combo)</legend>

    <!-- espelho do checkbox -->
    <input type="hidden" id="use_groups_hidden" name="use_groups" value="<?= $hasGroups ? '1' : '0' ?>">

    <label class="inline-flex items-center gap-2">
      <input
        type="checkbox"
        id="groups-toggle"
        name="use_groups"
        value="1"
        <?= $hasGroups ? 'checked' : '' ?>
        aria-controls="groups-wrap"
        aria-expanded="<?= $hasGroups ? 'true' : 'false' ?>"
      >
      <span>Usar grupos de opções (para combos/componentes)</span>
    </label>

    <!-- WRAP DOS GRUPOS (tudo some se toggle off) -->
    <div id="groups-wrap" class="<?= $hasGroups ? '' : 'hidden' ?>" aria-hidden="<?= $hasGroups ? 'false' : 'true' ?>">

      <?php if (empty($simpleProducts)): ?>
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-2 text-sm text-amber-900 mb-2">
          Nenhum <strong>produto simples</strong> encontrado para esta empresa. Cadastre ao menos um e marque como ativo.
        </div>
      <?php endif; ?>

      <!-- dica curta -->
      <div class="rounded-lg bg-slate-50 text-slate-700 text-sm p-3 leading-relaxed mb-2">
        Cada <em>grupo</em> é uma etapa (ex.: “Lanche”, “Acompanhamento”, “Bebida”). Itens são
        <strong>produtos simples</strong>. Campo Δ é o acréscimo.
      </div>

      <div id="groups-container" class="grid gap-3">
        <?php if (!empty($groups)): foreach ($groups as $gi => $g): $gi=(int)$gi;
          $gItems = $g['items'] ?? [];
          $min    = (int)($g['min_qty'] ?? $g['min'] ?? 0);
          $max    = (int)($g['max_qty'] ?? $g['max'] ?? 1);
        ?>
        <!-- CARTÃO DE GRUPO -->
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm group-card" data-index="<?= $gi ?>">
          <div class="flex items-center gap-3 border-b border-slate-200 p-3">
            <input
              type="text"
              name="groups[<?= $gi ?>][name]"
              class="w-full rounded-lg border border-slate-300 px-3 py-2"
              placeholder="Nome do grupo"
              value="<?= e($g['name'] ?? '') ?>"
              required
            />
            <button type="button" class="shrink-0 rounded-full p-2 text-slate-400 hover:text-slate-600 remove-group" aria-label="Remover grupo">&#x2715;</button>
          </div>

          <?php if (!empty($gItems)): foreach ($gItems as $ii => $it):
            $ii    = (int)$ii;
            $selId = (int)($it['product_id'] ?? 0);
            $isDef = !empty($it['is_default'] ?? $it['default']);
            $delta = (string)($it['delta'] ?? $it['delta_price'] ?? '0'); // Δ escondido
          ?>
          <!-- LINHA DE ITEM -->
          <div class="grid grid-cols-1 gap-3 p-3 md:grid-cols-[1fr_160px_72px_72px_auto_40px] md:items-center item-row" data-item-index="<?= $ii ?>">
            <div>
              <label class="block text-xs text-slate-500">Produto</label>
              <select
                name="groups[<?= $gi ?>][items][<?= $ii ?>][product_id]"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 product-select"
                title="Selecione um item da lista."
                required
              >
                <option value="">— Selecione um produto simples —</option>
                <?php foreach ($simpleProducts as $sp): ?>
                  <option
                    value="<?= (int)$sp['id'] ?>"
                    data-price="<?= e((string)($sp['price'] ?? '0')) ?>"
                    <?= $selId === (int)$sp['id'] ? 'selected' : '' ?>
                  ><?= e($sp['name']) ?><?= isset($sp['price']) ? ' — R$ ' . number_format((float)$sp['price'], 2, ',', '.') : '' ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="text-sm text-slate-600">
              <label class="block text-xs text-slate-500">Preço base</label>
              <div class="rounded-lg border border-slate-200 px-3 py-2 bg-slate-50 sp-price">R$ 0,00</div>
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
              <input type="checkbox" name="groups[<?= $gi ?>][items][<?= $ii ?>][default]" value="1" <?= $isDef ? 'checked' : '' ?>>
              <span>Default</span>
            </label>

            <div class="flex justify-end">
              <button type="button" class="remove-item shrink-0 rounded-full p-2 text-slate-400 hover:text-slate-600" aria-label="Remover item">&#x2715;</button>
            </div>

            <input type="hidden" name="groups[<?= $gi ?>][items][<?= $ii ?>][delta]" value="<?= e($delta) ?>">
          </div>
          <?php endforeach; else: ?>
          <!-- Se não havia itens, inicia com um vazio -->
          <div class="grid grid-cols-1 gap-3 p-3 md:grid-cols-[1fr_160px_72px_72px_auto_40px] md:items-center item-row" data-item-index="0">
            <div>
              <label class="block text-xs text-slate-500">Produto</label>
              <select
                name="groups[<?= $gi ?>][items][0][product_id]"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 product-select"
                title="Selecione um item da lista."
                required
              >
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
              <div class="rounded-lg border border-slate-200 px-3 py-2 bg-slate-50 sp-price">R$ 0,00</div>
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
              <input type="checkbox" name="groups[<?= $gi ?>][items][0][default]" value="1">
              <span>Default</span>
            </label>

            <div class="flex justify-end">
              <button type="button" class="remove-item shrink-0 rounded-full p-2 text-slate-400 hover:text-slate-600" aria-label="Remover item">&#x2715;</button>
            </div>

            <input type="hidden" name="groups[<?= $gi ?>][items][0][delta]" value="0">
          </div>
          <?php endif; ?>

          <!-- AÇÕES DO GRUPO -->
          <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 add-item">+ Item</button>
            <div class="text-sm text-slate-500 group-base-price">Preço base: R$ 0,00</div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- botão adicionar grupo -->
      <div class="mt-1">
        <button type="button" id="add-group" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">+ Grupo</button>
      </div>
    </div>
  </fieldset>

  <!-- ===== Personalização (NOVO LAYOUT) ===== -->
  <fieldset class="grid gap-3" aria-labelledby="legend-custom">
    <legend id="legend-custom" class="text-base font-medium">Personalização</legend>

    <!-- toggle -->
    <input type="hidden" id="customization-enabled-hidden" name="customization[enabled]" value="<?= $custEnabled ? '1' : '0' ?>">
    <label class="inline-flex items-center gap-2">
      <input type="checkbox" id="customization-enabled" name="customization[enabled]" value="1" <?= $custEnabled ? 'checked' : '' ?>>
      <span>Permitir personalização de itens</span>
    </label>

    <!-- bloco controlado -->
    <div id="customization-wrap" class="<?= $custEnabled ? '' : 'hidden' ?>" aria-hidden="<?= $custEnabled ? 'false' : 'true' ?>">
      <!-- ajuda -->
      <div class="rounded-lg bg-slate-50 text-slate-700 text-sm p-3 leading-relaxed">
        Crie grupos (ex.: <strong>Ingredientes</strong>, <strong>Molhos</strong>) e escolha os ingredientes já cadastrados.
        Ative <strong>Ingrediente padrão</strong> para definir a quantidade exibida ao cliente.
      </div>

      <div id="cust-groups-container" class="grid gap-3">
        <?php if (!empty($custGroups)): foreach ($custGroups as $gi => $cg): $gi=(int)$gi;
          $cgName = $cg['name'] ?? '';
          $cItems = $cg['items'] ?? [[]];
        ?>
        <?php
          $gType  = $cg['type'] ?? 'extra';
          $gMode  = in_array($gType, ['single','addon'], true) ? 'choice' : 'extra';
          $gMin   = isset($cg['min']) ? max(0, (int)$cg['min']) : 0;
          $gMax   = isset($cg['max']) ? max($gMin, (int)$cg['max']) : ($gMode === 'choice' ? max(1, count($cItems)) : 99);
          if ($gType === 'single') {
            $gMax = 1;
          }
        ?>
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm cust-group" data-index="<?= $gi ?>" data-mode="<?= e($gMode) ?>">
          <div class="flex flex-col gap-3 p-3 border-b border-slate-200">
            <div class="flex items-center gap-3">
            <input
              type="text"
              name="customization[groups][<?= $gi ?>][name]"
              class="w-full rounded-lg border border-slate-300 px-3 py-2"
              placeholder="Nome do grupo"
              value="<?= e($cgName) ?>"
            />
            <button type="button" class="rounded-full p-2 text-slate-400 hover:text-red-600 cust-remove-group" title="Remover grupo">✕</button>
            </div>
            <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-start">
              <label class="grid gap-1 text-sm">
                <span class="text-xs text-slate-500">Modo de seleção</span>
                <select name="customization[groups][<?= $gi ?>][mode]" class="rounded-lg border border-slate-300 px-3 py-2 cust-mode-select">
                  <option value="extra" <?= $gMode === 'extra' ? 'selected' : '' ?>>Adicionar ingredientes livremente</option>
                  <option value="choice" <?= $gMode === 'choice' ? 'selected' : '' ?>>Escolher ingrediente</option>
                </select>
              </label>
              <div class="cust-choice-settings <?= $gMode === 'choice' ? '' : 'hidden' ?>">
                <div class="grid gap-2 md:grid-cols-2">
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Seleções mínimas</span>
                    <input
                      type="number"
                      class="rounded-lg border border-slate-300 px-3 py-2 cust-choice-min"
                      name="customization[groups][<?= $gi ?>][choice][min]"
                      value="<?= $gMode === 'choice' ? $gMin : 0 ?>"
                      min="0"
                      step="1"
                    >
                  </label>
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Seleções máximas</span>
                    <input
                      type="number"
                      class="rounded-lg border border-slate-300 px-3 py-2 cust-choice-max"
                      name="customization[groups][<?= $gi ?>][choice][max]"
                      value="<?= $gMode === 'choice' ? $gMax : 1 ?>"
                      min="1"
                      step="1"
                    >
                  </label>
                </div>
                <p class="text-xs text-slate-500 mt-1">Defina quantas opções o cliente pode marcar.</p>
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
          <div class="grid gap-3 p-3 md:grid-cols-[minmax(0,1fr)_auto_auto_auto_40px] md:items-center cust-item" data-item-index="<?= $ii ?>">
            <div>
              <label class="block text-xs text-slate-500">Ingrediente</label>
              <select
                name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][ingredient_id]"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 cust-ingredient-select"
                data-default-min="<?= $minQ ?>"
                data-default-max="<?= $maxQ ?>"
              >
                <option value="">Selecione</option>
                <?php foreach ($ingredients as $ing): ?>
                  <option
                    value="<?= (int)$ing['id'] ?>"
                    data-min="<?= (int)($ing['min_qty'] ?? 0) ?>"
                    data-max="<?= (int)($ing['max_qty'] ?? 1) ?>"
                    data-img="<?= e($ing['image_path'] ?? '') ?>"
                    <?= $selId === (int)$ing['id'] ? 'selected' : '' ?>
                  ><?= e($ing['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="self-start md:self-center cust-limits-wrap">
              <div class="grid gap-2 cust-limits md:grid-cols-2" data-min="<?= $minQ ?>" data-max="<?= $maxQ ?>">
                <div>
                  <label class="block text-xs text-slate-500">Quantidade mínima</label>
                  <input
                    type="number"
                    class="w-24 rounded-lg border border-slate-300 px-3 py-2 cust-min-input"
                    name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][min_qty]"
                    value="<?= $minQ ?>"
                    min="0"
                    step="1"
                  >
                </div>
                <div>
                  <label class="block text-xs text-slate-500">Quantidade máxima</label>
                  <input
                    type="number"
                    class="w-24 rounded-lg border border-slate-300 px-3 py-2 cust-max-input"
                    name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][max_qty]"
                    value="<?= $maxQ ?>"
                    min="0"
                    step="1"
                  >
                </div>
              </div>
            </div>
            <div class="flex flex-col items-start gap-2">
              <input type="hidden" class="cust-default-flag" name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][default]" value="<?= $def ? '1' : '0' ?>">
              <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" class="cust-default-toggle" <?= $def ? 'checked' : '' ?> aria-label="Definir ingrediente padrão">
                <span>Ingrediente padrão</span>
              </label>
            </div>
            <div class="cust-default-qty-wrap <?= $def ? '' : 'hidden' ?>">
              <label class="block text-xs text-slate-500">Quantidade padrão</label>
              <input
                type="number"
                class="rounded-lg border border-slate-300 px-3 py-2 cust-default-qty"
                name="customization[groups][<?= $gi ?>][items][<?= $ii ?>][default_qty]"
                value="<?= $defQty ?>"
                min="<?= $minQ ?>"
                max="<?= $maxQ ?>"
                step="1"
              >
            </div>
            <button type="button" class="justify-self-end rounded-full p-2 text-slate-400 hover:text-red-600 cust-remove-item" title="Remover ingrediente">✕</button>
          </div>
          <?php endforeach; ?>

          <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 cust-add-item">+ Ingrediente</button>
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 cust-add-choice">+ Escolher ingrediente</button>
          </div>
        </div>
        <?php endforeach; else: ?>
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm cust-group" data-index="0" data-mode="extra">
          <div class="flex flex-col gap-3 p-3 border-b border-slate-200">
            <div class="flex items-center gap-3">
            <input
              type="text"
              name="customization[groups][0][name]"
              class="w-full rounded-lg border border-slate-300 px-3 py-2"
              placeholder="Nome do grupo"
              value=""
            />
            <button type="button" class="rounded-full p-2 text-slate-400 hover:text-red-600 cust-remove-group" title="Remover grupo">✕</button>
            </div>
            <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-start">
              <label class="grid gap-1 text-sm">
                <span class="text-xs text-slate-500">Modo de seleção</span>
                <select name="customization[groups][0][mode]" class="rounded-lg border border-slate-300 px-3 py-2 cust-mode-select">
                  <option value="extra" selected>Adicionar ingredientes livremente</option>
                  <option value="choice">Escolher ingrediente</option>
                </select>
              </label>
              <div class="cust-choice-settings hidden">
                <div class="grid gap-2 md:grid-cols-2">
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Seleções mínimas</span>
                    <input
                      type="number"
                      class="rounded-lg border border-slate-300 px-3 py-2 cust-choice-min"
                      name="customization[groups][0][choice][min]"
                      value="0"
                      min="0"
                      step="1"
                    >
                  </label>
                  <label class="grid gap-1 text-xs text-slate-500">
                    <span>Seleções máximas</span>
                    <input
                      type="number"
                      class="rounded-lg border border-slate-300 px-3 py-2 cust-choice-max"
                      name="customization[groups][0][choice][max]"
                      value="1"
                      min="1"
                      step="1"
                    >
                  </label>
                </div>
                <p class="text-xs text-slate-500 mt-1">Defina quantas opções o cliente pode marcar.</p>
              </div>
            </div>
          </div>

          <div class="grid gap-3 p-3 md:grid-cols-[minmax(0,1fr)_auto_auto_auto_40px] md:items-center cust-item" data-item-index="0">
            <div>
              <label class="block text-xs text-slate-500">Ingrediente</label>
              <select
                name="customization[groups][0][items][0][ingredient_id]"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 cust-ingredient-select"
                data-default-min="0"
                data-default-max="1"
              >
                <option value="">Selecione</option>
                <?php foreach ($ingredients as $ing): ?>
                  <option
                    value="<?= (int)$ing['id'] ?>"
                    data-min="<?= (int)($ing['min_qty'] ?? 0) ?>"
                    data-max="<?= (int)($ing['max_qty'] ?? 1) ?>"
                    data-img="<?= e($ing['image_path'] ?? '') ?>"
                  ><?= e($ing['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
        <div class="self-start md:self-center cust-limits-wrap">
          <span class="block text-xs text-slate-500 mb-1">Limites</span>
          <div class="grid gap-2 cust-limits md:grid-cols-2" data-min="0" data-max="1">
            <div>
              <label class="block text-xs text-slate-500">Quantidade mínima</label>
              <input
                type="number"
                class="w-24 rounded-lg border border-slate-300 px-3 py-2 cust-min-input"
                name="customization[groups][0][items][0][min_qty]"
                value="0"
                min="0"
                step="1"
              >
            </div>
            <div>
              <label class="block text-xs text-slate-500">Quantidade máxima</label>
              <input
                type="number"
                class="w-24 rounded-lg border border-slate-300 px-3 py-2 cust-max-input"
                name="customization[groups][0][items][0][max_qty]"
                value="1"
                min="0"
                step="1"
              >
            </div>
          </div>
        </div>
            <div class="flex flex-col items-start gap-2">
              <input type="hidden" class="cust-default-flag" name="customization[groups][0][items][0][default]" value="0">
              <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" class="cust-default-toggle" aria-label="Definir ingrediente padrão">
                <span>Ingrediente padrão</span>
              </label>
            </div>
            <div class="cust-default-qty-wrap hidden">
              <label class="block text-xs text-slate-500">Quantidade padrão</label>
              <input
                type="number"
                class="rounded-lg border border-slate-300 px-3 py-2 cust-default-qty"
                name="customization[groups][0][items][0][default_qty]"
                value="0"
                min="0"
                max="1"
                step="1"
              >
            </div>
            <button type="button" class="justify-self-end rounded-full p-2 text-slate-400 hover:text-red-600 cust-remove-item" title="Remover ingrediente">✕</button>
          </div>

          <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 cust-add-item">+ Ingrediente</button>
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 cust-add-choice">+ Escolher ingrediente</button>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- adicionar novo grupo -->
      <div class="mt-1">
        <button type="button" id="cust-add-group" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">
          + Grupo de personalização
        </button>
      </div>
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

  <!-- ===== Templates (Combo) ===== -->
  <template id="tpl-group">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm group-card" data-index="__GI__">
      <div class="flex items-center gap-3 border-b border-slate-200 p-3">
        <input type="text" name="groups[__GI__][name]" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Nome do grupo" value="" required />
        <button type="button" class="shrink-0 rounded-full p-2 text-slate-400 hover:text-slate-600 remove-group" aria-label="Remover grupo">&#x2715;</button>
      </div>

      <div class="grid grid-cols-1 gap-3 p-3 md:grid-cols-[1fr_160px_72px_72px_auto_40px] md:items-center item-row" data-item-index="0">
        <div>
          <label class="block text-xs text-slate-500">Produto</label>
          <select name="groups[__GI__][items][0][product_id]" class="w-full rounded-lg border border-slate-300 px-3 py-2 product-select" title="Selecione um item da lista." required>
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
          <div class="rounded-lg border border-slate-200 px-3 py-2 bg-slate-50 sp-price">R$ 0,00</div>
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
          <input type="checkbox" name="groups[__GI__][items][0][default]" value="1">
          <span>Default</span>
        </label>

        <div class="flex justify-end">
          <button type="button" class="remove-item shrink-0 rounded-full p-2 text-slate-400 hover:text-slate-600" aria-label="Remover item">&#x2715;</button>
        </div>

        <input type="hidden" name="groups[__GI__][items][0][delta]" value="0">
      </div>

      <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
        <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 add-item">+ Item</button>
        <div class="text-sm text-slate-500 group-base-price">Preço base: R$ 0,00</div>
      </div>
    </div>
  </template>

  <template id="tpl-item">
    <div class="grid grid-cols-1 gap-3 p-3 md:grid-cols-[1fr_160px_72px_72px_auto_40px] md:items-center item-row" data-item-index="__II__">
      <div>
        <label class="block text-xs text-slate-500">Produto</label>
        <select name="groups[__GI__][items][__II__][product_id]" class="w-full rounded-lg border border-slate-300 px-3 py-2 product-select" title="Selecione um item da lista." required>
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
        <div class="rounded-lg border border-slate-200 px-3 py-2 bg-slate-50 sp-price">R$ 0,00</div>
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
        <input type="checkbox" name="groups[__GI__][items][__II__][default]" value="1">
        <span>Default</span>
      </label>

      <div class="flex justify-end">
        <button type="button" class="remove-item shrink-0 rounded-full p-2 text-slate-400 hover:text-slate-600" aria-label="Remover item">&#x2715;</button>
      </div>

      <input type="hidden" name="groups[__GI__][items][__II__][delta]" value="0">
    </div>
  </template>

  <!-- ===== Templates (Personalização novo layout) ===== -->
  <template id="tpl-cust-group">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm cust-group" data-index="__CGI__" data-mode="extra">
      <div class="flex flex-col gap-3 p-3 border-b border-slate-200">
        <div class="flex items-center gap-3">
          <input
            type="text"
            name="customization[groups][__CGI__][name]"
            class="w-full rounded-lg border border-slate-300 px-3 py-2"
            placeholder="Nome do grupo"
            value=""
          />
          <button type="button" class="rounded-full p-2 text-slate-400 hover:text-red-600 cust-remove-group" title="Remover grupo">✕</button>
        </div>
        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-start">
          <label class="grid gap-1 text-sm">
            <span class="text-xs text-slate-500">Modo de seleção</span>
            <select name="customization[groups][__CGI__][mode]" class="rounded-lg border border-slate-300 px-3 py-2 cust-mode-select">
              <option value="extra" selected>Adicionar ingredientes livremente</option>
              <option value="choice">Escolher ingrediente</option>
            </select>
          </label>
          <div class="cust-choice-settings hidden">
            <div class="grid gap-2 md:grid-cols-2">
              <label class="grid gap-1 text-xs text-slate-500">
                <span>Seleções mínimas</span>
                <input
                  type="number"
                  class="rounded-lg border border-slate-300 px-3 py-2 cust-choice-min"
                  name="customization[groups][__CGI__][choice][min]"
                  value="0"
                  min="0"
                  step="1"
                >
              </label>
              <label class="grid gap-1 text-xs text-slate-500">
                <span>Seleções máximas</span>
                <input
                  type="number"
                  class="rounded-lg border border-slate-300 px-3 py-2 cust-choice-max"
                  name="customization[groups][__CGI__][choice][max]"
                  value="1"
                  min="1"
                  step="1"
                >
              </label>
            </div>
            <p class="text-xs text-slate-500 mt-1">Defina quantas opções o cliente pode marcar.</p>
          </div>
        </div>
      </div>

      <div class="grid gap-3 p-3 md:grid-cols-[minmax(0,1fr)_auto_auto_auto_40px] md:items-center cust-item" data-item-index="0">
        <div>
          <label class="block text-xs text-slate-500">Ingrediente</label>
          <select
            name="customization[groups][__CGI__][items][0][ingredient_id]"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 cust-ingredient-select"
            data-default-min="0"
            data-default-max="1"
          >
            <option value="">Selecione</option>
            <?php foreach ($ingredients as $ing): ?>
              <option
                value="<?= (int)$ing['id'] ?>"
                data-min="<?= (int)($ing['min_qty'] ?? 0) ?>"
                data-max="<?= (int)($ing['max_qty'] ?? 1) ?>"
                data-img="<?= e($ing['image_path'] ?? '') ?>"
              ><?= e($ing['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="self-start md:self-center cust-limits-wrap">
          <span class="block text-xs text-slate-500 mb-1">Limites</span>
          <div class="grid gap-2 cust-limits md:grid-cols-2" data-min="0" data-max="1">
            <div>
              <label class="block text-xs text-slate-500">Quantidade mínima</label>
              <input
                type="number"
                class="w-24 rounded-lg border border-slate-300 px-3 py-2 cust-min-input"
                name="customization[groups][__CGI__][items][0][min_qty]"
                value="0"
                min="0"
                step="1"
              >
            </div>
            <div>
              <label class="block text-xs text-slate-500">Quantidade máxima</label>
              <input
                type="number"
                class="w-24 rounded-lg border border-slate-300 px-3 py-2 cust-max-input"
                name="customization[groups][__CGI__][items][0][max_qty]"
                value="1"
                min="0"
                step="1"
              >
            </div>
          </div>
        </div>
        <div class="flex flex-col items-start gap-2">
          <input type="hidden" class="cust-default-flag" name="customization[groups][__CGI__][items][0][default]" value="0">
          <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" class="cust-default-toggle" aria-label="Definir ingrediente padrão">
            <span>Ingrediente padrão</span>
          </label>
        </div>
        <div class="cust-default-qty-wrap hidden">
          <label class="block text-xs text-slate-500">Quantidade padrão</label>
          <input
            type="number"
            class="rounded-lg border border-slate-300 px-3 py-2 cust-default-qty"
            name="customization[groups][__CGI__][items][0][default_qty]"
            value="0"
            min="0"
            max="1"
            step="1"
          >
        </div>
        <button type="button" class="justify-self-end rounded-full p-2 text-slate-400 hover:text-red-600 cust-remove-item" title="Remover ingrediente">✕</button>
      </div>

      <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-3">
        <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 cust-add-item">+ Ingrediente</button>
        <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50 cust-add-choice">+ Escolher ingrediente</button>
      </div>
    </div>
  </template>

  <template id="tpl-cust-item">
    <div class="grid gap-3 p-3 md:grid-cols-[minmax(0,1fr)_auto_auto_auto_40px] md:items-center cust-item" data-item-index="__CII__">
      <div>
        <label class="block text-xs text-slate-500">Ingrediente</label>
        <select
          name="customization[groups][__CGI__][items][__CII__][ingredient_id]"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 cust-ingredient-select"
          data-default-min="0"
          data-default-max="1"
        >
          <option value="">Selecione</option>
          <?php foreach ($ingredients as $ing): ?>
            <option
              value="<?= (int)$ing['id'] ?>"
              data-min="<?= (int)($ing['min_qty'] ?? 0) ?>"
              data-max="<?= (int)($ing['max_qty'] ?? 1) ?>"
              data-img="<?= e($ing['image_path'] ?? '') ?>"
            ><?= e($ing['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="self-start md:self-center cust-limits-wrap">
        <span class="block text-xs text-slate-500 mb-1">Limites</span>
        <div class="grid gap-2 cust-limits md:grid-cols-2" data-min="0" data-max="1">
          <div>
            <label class="block text-xs text-slate-500">Quantidade mínima</label>
            <input
              type="number"
              class="w-24 rounded-lg border border-slate-300 px-3 py-2 cust-min-input"
              name="customization[groups][__CGI__][items][__CII__][min_qty]"
              value="0"
              min="0"
              step="1"
            >
          </div>
          <div>
            <label class="block text-xs text-slate-500">Quantidade máxima</label>
            <input
              type="number"
              class="w-24 rounded-lg border border-slate-300 px-3 py-2 cust-max-input"
              name="customization[groups][__CGI__][items][__CII__][max_qty]"
              value="1"
              min="0"
              step="1"
            >
          </div>
        </div>
      </div>
      <div class="flex flex-col items-start gap-2">
        <input type="hidden" class="cust-default-flag" name="customization[groups][__CGI__][items][__CII__][default]" value="0">
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" class="cust-default-toggle" aria-label="Definir ingrediente padrão">
          <span>Ingrediente padrão</span>
        </label>
      </div>
      <div class="cust-default-qty-wrap hidden">
        <label class="block text-xs text-slate-500">Quantidade padrão</label>
        <input
          type="number"
          class="rounded-lg border border-slate-300 px-3 py-2 cust-default-qty"
          name="customization[groups][__CGI__][items][__CII__][default_qty]"
          value="0"
          min="0"
          max="1"
          step="1"
        >
      </div>
      <button type="button" class="justify-self-end rounded-full p-2 text-slate-400 hover:text-red-600 cust-remove-item" title="Remover ingrediente">✕</button>
    </div>
  </template>

  <!-- Script -->
  <script>
    // ===== Utils =====
    function formatMoney(v){ const n = isNaN(v) ? 0 : Number(v); return n.toLocaleString('pt-BR', { style:'currency', currency:'BRL' }); }
    function brToFloat(v){
      if(v==null) return 0;
      const raw = String(v).trim();
      if(raw.includes(',')){
        return parseFloat(raw.replace(/\./g,'').replace(',','.')) || 0;
      }
      return parseFloat(raw) || 0;
    }
    function toggleBlock(block, on){
      block.classList.toggle('hidden', !on);
      block.setAttribute('aria-hidden', String(!on));
    }
    function ensureMinMax(scope){
      scope.querySelectorAll('input[name$="[min]"]').forEach(minEl=>{
        const wrap = minEl.closest('.cust-group') || minEl.closest('.group-card') || scope;
        const maxEl = wrap.querySelector('input[name$="[max]"]');
        if(!maxEl) return;
        const min = Number(minEl.value||0), max = Number(maxEl.value||0);
        if(max && max < min) maxEl.value = min;
      });
    }
    function numberFromBR(n){ return Number(String(n).replace(/\./g,'').replace(',','.')) || 0; }

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

    // ===== Grupos (COMBO) visibilidade =====
    const typeSel=document.getElementById('type'),
          groupsToggle=document.getElementById('groups-toggle'),
          hiddenUse=document.getElementById('use_groups_hidden'),
          groupsWrap=document.getElementById('groups-wrap');

    function syncGroupsVisibility(){
      // NÃO força marcar baseado no tipo
      toggleBlock(groupsWrap, !!groupsToggle?.checked);
      if(groupsToggle) groupsToggle.setAttribute('aria-expanded', groupsToggle.checked ? 'true' : 'false');
    }
    typeSel?.addEventListener('change', syncGroupsVisibility); // opcional manter, não altera estado
    groupsToggle?.addEventListener('change', (e)=>{ if(hiddenUse) hiddenUse.value = e.target.checked?'1':'0'; syncGroupsVisibility(); });
    syncGroupsVisibility();

    // ===== COMBO wiring =====
    const gContainer=document.getElementById('groups-container'),
          addGroupBtn=document.getElementById('add-group'),
          tplGroup=document.getElementById('tpl-group'),
          tplItem=document.getElementById('tpl-item');

    function updateItemPrice(row){
      const sel = row.querySelector('.product-select');
      const box = row.querySelector('.sp-price');
      const price = sel?.selectedOptions?.[0]?.dataset?.price ?? '0';
      if (box) box.textContent = formatMoney(Number(String(price).replace(/\./g,'').replace(',','.')) || 0);
      return Number(String(price).replace(/\./g,'').replace(',','.')) || 0;
    }

    function updateGroupFooter(groupEl){
      const rows = groupEl.querySelectorAll('.item-row');
      let sum = 0;
      rows.forEach(r => {
        const def = r.querySelector('input[type=checkbox][name*="[default]"]');
        if (def?.checked) sum += updateItemPrice(r);
      });
      const footer = groupEl.querySelector('.group-base-price');
      if (footer) footer.textContent = `Preço base: ${formatMoney(sum)}`;
    }

    function wireItemRow(row){
      const sel = row.querySelector('.product-select');
      const def = row.querySelector('input[type=checkbox][name*="[default]"]');
      if (sel) {
        sel.addEventListener('change', () => { updateItemPrice(row); updateGroupFooter(row.closest('.group-card')); });
        updateItemPrice(row);
      }
      if (def) {
        def.addEventListener('change', () => updateGroupFooter(row.closest('.group-card')));
      }
    }

    // inicializa os já renderizados
    document.querySelectorAll('.group-card').forEach(g => {
      g.querySelectorAll('.item-row').forEach(wireItemRow);
      updateGroupFooter(g);
    });

    let gIndex = gContainer ? Array.from(gContainer.children).length : 0;

    function addGroup(){
      const gi = gIndex++;
      const html = tplGroup.innerHTML.replaceAll('__GI__', gi);
      const wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      const el = wrap.firstElementChild;
      gContainer.appendChild(el);
      el.querySelectorAll('.item-row').forEach(wireItemRow);
      updateGroupFooter(el);
      return el;
    }

    function nextItemIndex(groupEl){
      const idxs = Array.from(groupEl.querySelectorAll('.item-row')).map(r => Number(r.dataset.itemIndex||0));
      return idxs.length ? Math.max(...idxs)+1 : 0;
    }

    function addItem(groupEl){
      const gi = Number(groupEl.dataset.index);
      const ii = nextItemIndex(groupEl);
      const html = tplItem.innerHTML.replaceAll('__GI__', gi).replaceAll('__II__', ii);
      const wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      const row = wrap.firstElementChild;

      // inserir sempre antes do footer (container do .group-base-price)
      const footer = groupEl.querySelector('.group-base-price')?.parentElement;
      if (footer) groupEl.insertBefore(row, footer); else groupEl.appendChild(row);

      wireItemRow(row);
      updateGroupFooter(groupEl);
      return row;
    }

    addGroupBtn?.addEventListener('click', addGroup);

    gContainer?.addEventListener('click', (ev) => {
      const t = ev.target;
      if (t.classList.contains('add-item')) {
        const groupEl = t.closest('.group-card');
        addItem(groupEl);
      }
      if (t.classList.contains('remove-group')) {
        t.closest('.group-card')?.remove();
      }
      if (t.classList.contains('remove-item')) {
        const groupEl = t.closest('.group-card');
        t.closest('.item-row')?.remove();
        if (groupEl) updateGroupFooter(groupEl);
      }
    });

    // ===== Personalização (novo layout) =====
    const custToggle = document.getElementById('customization-enabled');
    const custHidden = document.getElementById('customization-enabled-hidden');
    const custWrap   = document.getElementById('customization-wrap');
    const custCont   = document.getElementById('cust-groups-container');
    const custAddGrp = document.getElementById('cust-add-group');
    const tplCustGrp = document.getElementById('tpl-cust-group');
    const tplCustItm = document.getElementById('tpl-cust-item');

    function syncCust(){
      const on = !!custToggle?.checked;
      if (custHidden) custHidden.value = on ? '1' : '0';
      toggleBlock(custWrap, on);
    }
    custToggle?.addEventListener('change', syncCust); syncCust();

    function nextCustGroupIndex(){
      const idxs = Array.from(custCont.querySelectorAll('.cust-group')).map(g => Number(g.dataset.index||0));
      return idxs.length ? Math.max(...idxs) + 1 : 0;
    }
    function nextCustItemIndex(groupEl){
      const idxs = Array.from(groupEl.querySelectorAll('.cust-item')).map(r => Number(r.dataset.itemIndex||0));
      return idxs.length ? Math.max(...idxs) + 1 : 0;
    }

    function updateCustItem(itemEl) {
      if (!itemEl) return;
      const groupEl = itemEl.closest('.cust-group');
      const mode = groupEl?.dataset.mode === 'choice' ? 'choice' : 'extra';
      const limits = itemEl.querySelector('.cust-limits');
      const minInput = itemEl.querySelector('.cust-min-input');
      const maxInput = itemEl.querySelector('.cust-max-input');
      const qtyWrap = itemEl.querySelector('.cust-default-qty-wrap');
      const qtyInput = itemEl.querySelector('.cust-default-qty');
      const checkbox = itemEl.querySelector('.cust-default-toggle');
      const flag = itemEl.querySelector('.cust-default-flag');

      let min = Number(minInput?.value ?? 0);
      let max = Number(maxInput?.value ?? min);

      if (mode === 'choice') {
        min = 0;
        max = 1;
        if (minInput) {
          minInput.value = '0';
          minInput.readOnly = true;
        }
        if (maxInput) {
          maxInput.value = '1';
          maxInput.readOnly = true;
        }
      } else {
        if (Number.isNaN(min) || min < 0) min = 0;
        if (minInput) {
          minInput.value = String(min);
          minInput.readOnly = false;
        }
        if (Number.isNaN(max) || max < min) max = min;
        if (maxInput) {
          maxInput.value = String(max);
          maxInput.readOnly = false;
        }
      }

      if (limits) {
        limits.dataset.min = String(min);
        limits.dataset.max = String(max);
      }

      if (qtyInput) {
        qtyInput.min = String(min);
        qtyInput.max = String(max);
        if (qtyInput.value === '' || Number(qtyInput.value) < min) qtyInput.value = String(min);
        if (Number(qtyInput.value) > max) qtyInput.value = String(max);
      }

      const isActive = !!checkbox?.checked;
      if (flag) {
        flag.value = isActive ? '1' : '0';
      }
      if (!isActive && qtyInput) {
        qtyInput.value = String(min);
      }

      if (qtyWrap) {
        const hideQty = mode === 'choice' || !isActive;
        qtyWrap.classList.toggle('hidden', hideQty);
      }
    }

    function applyCustMode(groupEl) {
      if (!groupEl) return;
      const select = groupEl.querySelector('.cust-mode-select');
      const choiceWrap = groupEl.querySelector('.cust-choice-settings');
      const addItemBtn = groupEl.querySelector('.cust-add-item');
      const addChoiceBtn = groupEl.querySelector('.cust-add-choice');
      const mode = select?.value === 'choice' ? 'choice' : 'extra';
      groupEl.dataset.mode = mode;
      toggleBlock(choiceWrap, mode === 'choice');
      if (addItemBtn) addItemBtn.textContent = mode === 'choice' ? '+ Opção' : '+ Ingrediente';
      if (addChoiceBtn) addChoiceBtn.classList.toggle('hidden', mode === 'choice');
      groupEl.querySelectorAll('.cust-limits-wrap').forEach(wrap => {
        wrap.classList.toggle('hidden', mode === 'choice');
      });
      groupEl.querySelectorAll('.cust-item').forEach(updateCustItem);
    }

    function wireCustItem(itemEl) {
      if (!itemEl) return;
      const flag = itemEl.querySelector('.cust-default-flag');
      const checkbox = itemEl.querySelector('.cust-default-toggle');
      if (flag && checkbox) {
        checkbox.checked = flag.value === '1';
      }
      updateCustItem(itemEl);
    }

    function wireCustGroup(groupEl) {
      if (!groupEl) return;
      const select = groupEl.querySelector('.cust-mode-select');
      if (select && !groupEl.dataset.mode) {
        groupEl.dataset.mode = select.value === 'choice' ? 'choice' : 'extra';
      } else if (select) {
        select.value = groupEl.dataset.mode === 'choice' ? 'choice' : 'extra';
      }
      groupEl.querySelectorAll('.cust-item').forEach(wireCustItem);
      applyCustMode(groupEl);
    }

    function addCustGroup(){
      const gi = nextCustGroupIndex();
      const html = tplCustGrp.innerHTML.replaceAll('__CGI__', gi);
      const wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      const node = wrap.firstElementChild;
      custCont.appendChild(node);
      wireCustGroup(node);
      return node;
    }

    function addCustItem(groupEl){
      const gi = Number(groupEl.dataset.index);
      const ii = nextCustItemIndex(groupEl);
      const html = tplCustItm.innerHTML.replaceAll('__CGI__', gi).replaceAll('__CII__', ii);
      const wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      const row = wrap.firstElementChild;

      const footerBar = Array.from(groupEl.children).find(el => el.matches('.flex.border-t, .border-t'));
      if (footerBar) groupEl.insertBefore(row, footerBar); else groupEl.appendChild(row);
      wireCustItem(row);
      applyCustMode(groupEl);
      return row;
    }

    custAddGrp?.addEventListener('click', addCustGroup);

    custCont?.addEventListener('click', (e) => {
      const t = e.target;
      if (t.classList.contains('cust-add-item')) {
        const groupEl = t.closest('.cust-group');
        addCustItem(groupEl);
      } else if (t.classList.contains('cust-add-choice')) {
        const groupEl = t.closest('.cust-group');
        const sel = groupEl?.querySelector('.cust-mode-select');
        if (sel) sel.value = 'choice';
        applyCustMode(groupEl);
        addCustItem(groupEl);
      } else if (t.classList.contains('cust-remove-group')) {
        t.closest('.cust-group')?.remove();
      } else if (t.classList.contains('cust-remove-item')) {
        t.closest('.cust-item')?.remove();
      }
    });

    custCont?.addEventListener('change', (e) => {
      const t = e.target;
      if (t.classList.contains('cust-ingredient-select')) {
        const itemEl = t.closest('.cust-item');
        updateCustItem(itemEl);
      } else if (t.classList.contains('cust-mode-select')) {
        const groupEl = t.closest('.cust-group');
        applyCustMode(groupEl);
      } else if (t.classList.contains('cust-choice-min') || t.classList.contains('cust-choice-max')) {
        const groupEl = t.closest('.cust-group');
        const minInput = groupEl?.querySelector('.cust-choice-min');
        const maxInput = groupEl?.querySelector('.cust-choice-max');
        let min = Number(minInput?.value || 0);
        let max = Number(maxInput?.value || 1);
        if (Number.isNaN(min) || min < 0) min = 0;
        if (Number.isNaN(max) || max < 1) max = 1;
        if (max < min) max = min;
        if (minInput) minInput.value = String(min);
        if (maxInput) maxInput.value = String(max);
      } else if (t.classList.contains('cust-default-toggle')) {
        const itemEl = t.closest('.cust-item');
        updateCustItem(itemEl);
      }
    });

    custCont?.addEventListener('input', (e) => {
      const t = e.target;
      if (t.classList.contains('cust-default-qty')) {
        const min = Number(t.min || 0);
        let val = Number(t.value || min);
        if (Number.isNaN(val) || val < min) val = min;
        t.value = String(val);
      } else if (t.classList.contains('cust-choice-min') || t.classList.contains('cust-choice-max')) {
        const groupEl = t.closest('.cust-group');
        const minInput = groupEl?.querySelector('.cust-choice-min');
        const maxInput = groupEl?.querySelector('.cust-choice-max');
        let min = Number(minInput?.value || 0);
        let max = Number(maxInput?.value || 1);
        if (Number.isNaN(min) || min < 0) min = 0;
        if (Number.isNaN(max) || max < 1) max = 1;
        if (max < min) max = min;
        if (minInput) minInput.value = String(min);
        if (maxInput) maxInput.value = String(max);
      } else if (t.classList.contains('cust-min-input') || t.classList.contains('cust-max-input')) {
        const itemEl = t.closest('.cust-item');
        updateCustItem(itemEl);
      }
    });

    custCont?.querySelectorAll('.cust-group').forEach(wireCustGroup);

    // ===== Validação + normalização =====
    document.getElementById('product-form')?.addEventListener('submit', (e)=>{
      const name=document.getElementById('name');
      if(!name.value.trim()){ e.preventDefault(); alert('Informe o nome do produto.'); name.focus(); return; }

      // Normaliza BR -> float
      const priceEl = document.getElementById('price');
      if(priceEl){ priceEl.value = String(brToFloat(priceEl.value||'0')); }

      const promoEl = document.getElementById('promo_price');
      if(promoEl){
        const rawPromo = promoEl.value == null ? '' : String(promoEl.value).trim();
        promoEl.value = rawPromo === '' ? '' : String(brToFloat(rawPromo));
      }

      const price = parseFloat((priceEl?.value || '0'));
      const promoRaw = promoEl?.value ?? '';
      const promo = promoRaw === '' ? null : parseFloat(promoRaw || '0');

      // Promo: se inválida (<=0) ou preço base <=0, limpa; se >= preço base, bloqueia submit
      if (promoEl && promo !== null && !Number.isNaN(promo)) {
        if (price <= 0 || promo <= 0) {
          promoEl.value = '';
        } else if (promo >= price) {
          e.preventDefault();
          alert('O preço promocional deve ser menor que o preço base.');
          document.getElementById('promo_price').focus();
          return;
        }
      }

      // COMBO
      const groupsToggle=document.getElementById('groups-toggle');
      const gContainer=document.getElementById('groups-container');

      if(groupsToggle && groupsToggle.checked){
        const gs=gContainer.querySelectorAll('.group-card');
        if(!gs.length){ e.preventDefault(); alert('Adicione pelo menos um grupo de opções do combo.'); return; }
        for(const g of gs){
          const gname=g.querySelector('input[name^="groups"][name$="[name]"]');
          const items=g.querySelectorAll('.item-row');
          ensureMinMax(g);
          const minEl=g.querySelector('input[name$="[min]"]'), maxEl=g.querySelector('input[name$="[max]"]');
          const min=Number(minEl?.value||0), max=Number(maxEl?.value||0);
          if(max && max<min){ e.preventDefault(); alert('No grupo "'+(gname.value||'')+'", o máximo não pode ser menor que o mínimo.'); maxEl.focus(); return; }
          if(!gname.value.trim() || !items.length){ e.preventDefault(); alert('Cada grupo do combo precisa de nome e ao menos um item.'); gname.focus(); return; }
          for(const it of items){ const sel=it.querySelector('select.product-select'); if(!sel.value){ e.preventDefault(); alert('Selecione um produto simples para cada item do combo.'); sel.focus(); return; } }
        }
      }

      // PERSONALIZAÇÃO (novo layout)
      const custToggle = document.getElementById('customization-enabled');
      const custCont   = document.getElementById('cust-groups-container');

      if(custToggle && custToggle.checked){
        const cgs = custCont.querySelectorAll('.cust-group');
        if(!cgs.length){ e.preventDefault(); alert('Adicione pelo menos um grupo de personalização.'); return; }
        for (const cg of cgs){
          const nameEl = cg.querySelector('input[name^="customization"][name$="[name]"]');
          const items  = cg.querySelectorAll('.cust-item');
          if(!nameEl.value.trim()){ e.preventDefault(); alert('Cada grupo de personalização precisa de um nome.'); nameEl.focus(); return; }
          if(!items.length){ e.preventDefault(); alert('Adicione pelo menos um ingrediente no grupo "'+(nameEl.value||'')+'".'); return; }
          for (const it of items){
            const sel = it.querySelector('.cust-ingredient-select');
            if (!sel || !sel.value) {
              e.preventDefault();
              alert('Selecione um ingrediente em cada item do grupo "'+(nameEl.value||'')+'".');
              sel?.focus();
              return;
            }
            const limits = it.querySelector('.cust-limits');
            const min = limits ? Number(limits.dataset.min ?? 0) : 0;
            const max = limits ? Number(limits.dataset.max ?? 1) : 1;
            const toggleCheckbox = it.querySelector('.cust-default-toggle');
            const qty = it.querySelector('.cust-default-qty');
            if (toggleCheckbox?.checked) {
              const val = qty ? Number(qty.value || min) : min;
              if (val < min || val > max) {
                e.preventDefault();
                alert('A quantidade padrão precisa estar entre o mínimo e máximo do ingrediente escolhido.');
                qty?.focus();
                return;
              }
            }
          }
        }
      }
    });

    // coerência min/max ao digitar
    ;['groups-container'].forEach(id=>{
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
