<?php
// admin/delivery-fees/index.php

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$company      = is_array($company ?? null) ? $company : [];
$cities       = is_array($cities ?? null) ? $cities : [];
$zones        = is_array($zones ?? null) ? $zones : [];
$cityErrors   = is_array($cityErrors ?? null) ? $cityErrors : [];
$zoneErrors   = is_array($zoneErrors ?? null) ? $zoneErrors : [];
$optionErrors = is_array($optionErrors ?? null) ? $optionErrors : [];
$bulkErrors   = is_array($bulkErrors ?? null) ? $bulkErrors : [];
$oldCity      = is_array($oldCity ?? null) ? $oldCity : ['name' => ''];
$oldZone      = is_array($oldZone ?? null) ? $oldZone : ['city_id' => '', 'neighborhood' => '', 'fee' => ''];
$optionValues = is_array($optionValues ?? null) ? $optionValues : [];
$bulkValue    = isset($bulkValue) ? (string)$bulkValue : '';
$citySearch   = isset($citySearch) ? trim((string)$citySearch) : '';
$zoneSearch   = isset($zoneSearch) ? trim((string)$zoneSearch) : '';
$editCityId   = isset($editCityId) ? (int)$editCityId : 0;
$editZoneId   = isset($editZoneId) ? (int)$editZoneId : 0;
$flash        = is_array($flash ?? null) ? $flash : [];

$title        = 'Taxas de entrega - ' . ($company['name'] ?? '');
$slug         = rawurlencode((string)($company['slug'] ?? ''));

// Contagem de bairros por cidade
$zoneCountByCity = [];
foreach ($zones as $zone) {
  $cityId = (int)($zone['city_id'] ?? 0);
  if (!isset($zoneCountByCity[$cityId])) {
    $zoneCountByCity[$cityId] = 0;
  }
  $zoneCountByCity[$cityId]++;
}

$basePath   = base_url('admin/' . $slug . '/delivery-fees');
$queryState = [];
if ($citySearch !== '') { $queryState['city_search'] = $citySearch; }
if ($zoneSearch !== '') { $queryState['zone_search'] = $zoneSearch; }

if (!function_exists('delivery_query_suffix')) {
  function delivery_query_suffix(array $current, array $overrides = [], array $remove = []): string {
    foreach ($remove as $key) {
      unset($current[$key]);
    }
    foreach ($overrides as $key => $value) {
      if ($value === null || $value === '') {
        unset($current[$key]);
      } else {
        $current[$key] = $value;
      }
    }
    if (!$current) return '';
    return '?' . http_build_query($current);
  }
}

ob_start();
?>

<div class="mx-auto max-w-6xl p-4">

<header class="mb-6 flex items-center gap-3">
  <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl admin-gradient-bg text-white shadow">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-truck" viewBox="0 0 16 16">
      <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5v7A1.5 1.5 0 0 1 10.5 12H10a2 2 0 1 1-4 0H4a2 2 0 1 1-3.874-.5A1.5 1.5 0 0 1 0 10.5zm1.5-.5a.5.5 0 0 0-.5.5v5.473A2 2 0 0 1 3.874 11H6V3h4.5a.5.5 0 0 0 .5-.5V3h.086a1.5 1.5 0 0 1 1.3.75l1.528 2.75a1.5 1.5 0 0 1 .186.725V9.5A1.5 1.5 0 0 1 12.5 11H12a2 2 0 1 1-4 0H6v1h4.5a.5.5 0 0 0 .5-.5V9h1.5a.5.5 0 0 0 .5-.5v-.525a.5.5 0 0 0-.062-.242l-1.528-2.75A.5.5 0 0 0 11.438 5H11V3.5A1.5 1.5 0 0 0 9.5 2z"/>
    </svg>
  </span>
  <div>
    <h1 class="admin-gradient-text bg-clip-text text-2xl font-semibold text-transparent">Taxas de entrega</h1>
    <p class="text-sm text-slate-500">Cadastre primeiro as cidades atendidas e, depois, os bairros vinculados a cada uma.</p>
  </div>

  <div class="ml-auto flex items-center gap-2">
    <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>"
       class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-house-gear" viewBox="0 0 16 16">
        <path d="M7.293 1.5a1 1 0 0 1 1.414 0L11 3.793V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v3.293l2.354 2.353a.5.5 0 0 1-.708.708L8.207 2.207l-5 5V13.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 1 0 1h-4A1.5 1.5 0 0 1 2 13.5V8.207l-.646.647a.5.5 0 1 1-.708-.708z"/>
        <path d="M11.886 9.46c.18-.613 1.048-.613 1.229 0l.043.148a.64.64 0 0 0 .921.382l.136-.074c.561-.306 1.175.308.87.869l-.075.136a.64.64 0 0 0 .382.92l.149.045c.612.18.612 1.048 0 1.229l-.15.043a.64.64 0 0 0-.38.921l.074.136c.305.561-.309 1.175-.87.87l-.136-.075a.64.64 0 0 0-.92.382l-.045.149c-.18.612-1.048.612-1.229 0l-.043-.15a.64.64 0 0 0-.921-.38l-.136.074c-.561.305-1.175-.309-.87-.87l.075-.136a.64.64 0 0 0 .382-.92l-.148-.044c-.613-.181-.613-1.049 0-1.23l.148-.043a.64.64 0 0 0 .382-.921l-.074-.136c-.306-.561.308-1.175.869-.87l.136.075a.64.64 0 0 0 .92-.382zM14 12.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0"/>
      </svg>
      Dashboard
    </a>
  </div>
</header>

<?php if ($flash): ?>
  <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
    <?= e($flash['message'] ?? '') ?>
  </div>
<?php endif; ?>

<!-- ===== AJUSTES RÁPIDOS (TOPO) ===== -->
<div class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
  <h2 class="mb-3 text-lg font-semibold text-slate-800">Ajustes rápidos</h2>

  <?php if ($bulkErrors): ?>
    <div class="mb-3 rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-xs text-red-700">
      <?php foreach ($bulkErrors as $error): ?>
        <div><?= e($error) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/zones/adjust')) ?>" class="mb-4 grid gap-3 rounded-xl border border-indigo-100 bg-white px-4 py-3 shadow-sm">
    <div class="flex flex-wrap items-end gap-3">
      <label class="flex-1 min-w-[180px]">
        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Aumentar/diminuir todas as taxas</span>
        <input type="number" step="0.01" name="delta" value="<?= e($bulkValue) ?>" placeholder="Ex.: 2,00" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
      </label>
      <?php if (function_exists('csrf_field')): ?>
        <?= csrf_field() ?>
      <?php elseif (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <?php endif; ?>
      <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-indigo-300 bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-500">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 12h16M12 4v16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        Aplicar ajuste
      </button>
    </div>
    <p class="text-xs text-slate-500">Informe um valor positivo para aumentar ou negativo para diminuir todas as taxas atuais.</p>
  </form>

  <?php if ($optionErrors): ?>
    <div class="mb-3 rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-xs text-red-700">
      <?php foreach ($optionErrors as $error): ?>
        <div><?= e($error) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
    <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/options')) ?>" class="grid gap-2 rounded-xl border border-emerald-100 bg-white px-4 py-3 shadow-sm">
      <?php if (function_exists('csrf_field')): ?>
        <?= csrf_field() ?>
      <?php elseif (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <?php endif; ?>
      <input type="hidden" name="free_delivery" value="<?= (int)($optionValues['free_delivery'] ?? 0) ?>">
      <label class="grid gap-1">
        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Adicional após as 18h (R$)</span>
        <input type="number" step="0.01" min="0" name="after_hours_fee" value="<?= e($optionValues['after_hours_fee'] ?? '0.00') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
      </label>
      <button type="submit" class="inline-flex items-center gap-2 justify-self-start rounded-xl border border-emerald-300 bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-emerald-500">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Salvar adicional
      </button>
      <p class="text-xs text-slate-500">Esse valor será somado automaticamente às entregas realizadas após as 18h.</p>
    </form>

    <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/options')) ?>" class="justify-self-start">
      <?php if (function_exists('csrf_field')): ?>
        <?= csrf_field() ?>
      <?php elseif (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <?php endif; ?>
      <input type="hidden" name="after_hours_fee" value="<?= e($optionValues['after_hours_fee'] ?? '0.00') ?>">
      <input type="hidden" name="free_delivery" value="<?= (int)($optionValues['free_delivery'] ?? 0) ? 0 : 1 ?>">
      <button type="submit" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
        <?php if ((int)($optionValues['free_delivery'] ?? 0)): ?>
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-toggle-on text-emerald-600" viewBox="0 0 16 16">
            <path d="M5 3a5 5 0 0 0 0 10h6a5 5 0 0 0 0-10zm6 9a4 4 0 1 1 0-8 4 4 0 0 1 0 8"/>
          </svg>
          Desativar taxa gratuita
        <?php else: ?>
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-toggle-off text-slate-500" viewBox="0 0 16 16">
            <path d="M11 4a4 4 0 0 1 0 8H8a5 5 0 0 0 2-4 5 5 0 0 0-2-4zm-6 8a4 4 0 1 1 0-8 4 4 0 0 1 0 8M0 8a5 5 0 0 0 5 5h6a5 5 0 0 0 0-10H5a5 5 0 0 0-5 5"/>
          </svg>
          Ativar taxa gratuita
        <?php endif; ?>
      </button>
      <p class="mt-2 max-w-xs text-xs text-slate-500">Quando ativado, todas as entregas serão tratadas como gratuitas independentemente da taxa cadastrada.</p>
    </form>
  </div>
</div>
<!-- ===== /AJUSTES RÁPIDOS ===== -->

<div class="grid gap-6 xl:grid-cols-[1fr_1.1fr]">
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4">
      <h2 class="text-lg font-semibold text-slate-800">1. Cadastrar cidades atendidas</h2>
      <p class="text-sm text-slate-500">As taxas de bairro ficam vinculadas a uma das cidades abaixo.</p>
    </div>

    <?php if ($cityErrors): ?>
      <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <strong class="font-semibold">Corrija os campos da cidade:</strong>
        <ul class="mt-2 list-disc space-y-1 pl-4">
          <?php foreach ($cityErrors as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php
      $cityFormAction = $editCityId && !empty($oldCity['id'])
        ? base_url('admin/' . $slug . '/delivery-fees/cities/' . (int)$oldCity['id'])
        : base_url('admin/' . $slug . '/delivery-fees/cities');
    ?>
    <form method="post" action="<?= e($cityFormAction) ?>" class="grid gap-4">
      <?php if (function_exists('csrf_field')): ?>
        <?= csrf_field() ?>
      <?php elseif (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <?php endif; ?>

      <div class="grid gap-2">
        <label for="city-name" class="text-sm font-medium text-slate-700">Nome da cidade <span class="text-red-500">*</span></label>
        <input type="text" id="city-name" name="name" value="<?= e($oldCity['name'] ?? '') ?>" required
               maxlength="120"
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
               placeholder="Ex.: São Paulo">
      </div>

      <div class="flex items-center justify-end gap-3">
        <?php if ($editCityId && !empty($oldCity['id'])): ?>
          <a href="<?= e($basePath . delivery_query_suffix($queryState, [], ['edit_city'])) ?>"
             class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
            Cancelar
          </a>
        <?php endif; ?>

        <button type="submit" class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          <?= $editCityId && !empty($oldCity['id']) ? 'Atualizar cidade' : 'Salvar cidade' ?>
        </button>
      </div>
    </form>

    <div class="mt-6">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Cidades cadastradas</h3>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Total: <?= count($cities) ?></span>
      </div>

      <form method="get" action="<?= e($basePath) ?>" class="mb-3 flex items-center gap-2 text-sm" data-js="city-search-form">
        <input type="search" name="city_search" value="<?= e($citySearch) ?>" placeholder="Buscar cidade..."
               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
               data-js="city-search-input">
        <?php if ($zoneSearch !== ''): ?>
          <input type="hidden" name="zone_search" value="<?= e($zoneSearch) ?>">
        <?php endif; ?>
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-700 shadow-sm hover:bg-slate-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="m11 4-7 8h8l-1 8 7-8h-8l1-8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
          Buscar
        </button>
        <?php if ($citySearch !== ''): ?>
          <a href="<?= e($basePath . delivery_query_suffix($queryState, ['city_search' => null])) ?>"
             class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-500 hover:bg-slate-50">Limpar</a>
        <?php endif; ?>
      </form>

      <?php if (!$cities): ?>
        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
          Nenhuma cidade cadastrada ainda.
        </div>
      <?php else: ?>
        <ul class="space-y-2" data-js="city-list">
          <?php foreach ($cities as $city): ?>
            <li class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                data-js="city-item"
                data-city-name="<?= e(strtolower($city['name'] ?? '')) ?>">
              <div>
                <div class="font-medium text-slate-800"><?= e($city['name'] ?? '') ?></div>
                <div class="text-xs text-slate-500">
                  <?= (int)($zoneCountByCity[(int)($city['id'] ?? 0)] ?? 0) ?> bairro(s) cadastrados
                </div>
              </div>
              <div class="flex items-center gap-2">
                <a href="<?= e($basePath . delivery_query_suffix($queryState, ['edit_city' => (int)($city['id'] ?? 0)], ['edit_zone'])) ?>"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                  <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 16.5 16.5 5 19 7.5 7.5 19H5v-2.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  Editar
                </a>
                <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/cities/' . (int)($city['id'] ?? 0) . '/del')) ?>"
                      onsubmit="return confirm('Remover esta cidade? Bairros vinculados também serão excluídos.');">
                  <?php if (function_exists('csrf_field')): ?>
                    <?= csrf_field() ?>
                  <?php elseif (function_exists('csrf_token')): ?>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <?php endif; ?>
                  <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-700 shadow-sm hover:bg-red-50">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M9 7v11m6-11v11M8 7l1-2h6l1 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    Excluir
                  </button>
                </form>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="hidden rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500"
             data-js="city-empty">
          Nenhuma cidade encontrada para a busca atual.
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4">
      <h2 class="text-lg font-semibold text-slate-800">2. Cadastrar bairros e taxas</h2>
      <p class="text-sm text-slate-500">Selecione a cidade e informe o bairro com a taxa correspondente.</p>
    </div>

    <?php if ($zoneErrors): ?>
      <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <strong class="font-semibold">Corrija os campos do bairro:</strong>
        <ul class="mt-2 list-disc space-y-1 pl-4">
          <?php foreach ($zoneErrors as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php
      $zoneFormAction = $editZoneId && !empty($oldZone['id'])
        ? base_url('admin/' . $slug . '/delivery-fees/zones/' . (int)$oldZone['id'])
        : base_url('admin/' . $slug . '/delivery-fees/zones');
    ?>
    <form method="post" action="<?= e($zoneFormAction) ?>" class="grid gap-4">
      <?php if (function_exists('csrf_field')): ?>
        <?= csrf_field() ?>
      <?php elseif (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <?php endif; ?>

      <div class="grid gap-2">
        <label for="zone-city" class="text-sm font-medium text-slate-700">Cidade <span class="text-red-500">*</span></label>
        <select id="zone-city" name="city_id" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200" <?= $cities ? '' : 'disabled' ?> required>
          <option value="">Selecione uma cidade</option>
          <?php foreach ($cities as $city): ?>
            <option value="<?= (int)($city['id'] ?? 0) ?>" <?= ((string)($oldZone['city_id'] ?? '') === (string)($city['id'] ?? '')) ? 'selected' : '' ?>><?= e($city['name'] ?? '') ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!$cities): ?>
          <span class="text-xs text-amber-600">Cadastre ao menos uma cidade antes de registrar bairros.</span>
        <?php endif; ?>
      </div>

      <div class="grid gap-2">
        <label for="zone-neighborhood" class="text-sm font-medium text-slate-700">Bairro <span class="text-red-500">*</span></label>
        <input type="text" id="zone-neighborhood" name="neighborhood" value="<?= e($oldZone['neighborhood'] ?? '') ?>" required maxlength="120"
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
               placeholder="Ex.: Centro">
      </div>

      <div class="grid gap-2">
        <label for="zone-fee" class="text-sm font-medium text-slate-700">Taxa de entrega (R$) <span class="text-red-500">*</span></label>
        <input type="number" min="0" step="0.01" inputmode="decimal" id="zone-fee" name="fee" value="<?= e($oldZone['fee'] ?? '') ?>" required
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
               placeholder="Ex.: 8,00">
      </div>

      <div class="flex items-center justify-end gap-3">
        <?php if ($editZoneId && !empty($oldZone['id'])): ?>
          <a href="<?= e($basePath . delivery_query_suffix($queryState, [], ['edit_zone'])) ?>"
             class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
            Cancelar
          </a>
        <?php endif; ?>
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95" <?= $cities ? '' : 'disabled' ?>>
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          <?= $editZoneId && !empty($oldZone['id']) ? 'Atualizar taxa' : 'Salvar taxa' ?>
        </button>
      </div>
    </form>

    <div class="mt-6">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Bairros cadastrados</h3>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Total: <?= count($zones) ?></span>
      </div>

      <form method="get" action="<?= e($basePath) ?>" class="mb-3 flex items-center gap-2 text-sm" data-js="zone-search-form">
        <?php if ($citySearch !== ''): ?>
          <input type="hidden" name="city_search" value="<?= e($citySearch) ?>">
        <?php endif; ?>
        <input type="search" name="zone_search" value="<?= e($zoneSearch) ?>" placeholder="Buscar por bairro ou cidade..."
               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
               data-js="zone-search-input">
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-700 shadow-sm hover:bg-slate-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="m11 4-7 8h8l-1 8 7-8h-8l1-8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
          Buscar
        </button>
        <?php if ($zoneSearch !== ''): ?>
          <a href="<?= e($basePath . delivery_query_suffix($queryState, ['zone_search' => null])) ?>"
             class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-500 hover:bg-slate-50">Limpar</a>
        <?php endif; ?>
      </form>

      <?php if (!$zones): ?>
        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
          Nenhuma taxa cadastrada ainda.
        </div>
      <?php else: ?>
        <div class="max-h-[520px] overflow-auto rounded-xl border border-slate-200" data-js="zone-table-wrapper">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
              <tr>
                <th class="p-3">Cidade</th>
                <th class="p-3">Bairro</th>
                <th class="p-3">Taxa</th>
                <th class="p-3 text-right">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100" data-js="zone-body">
              <?php foreach ($zones as $zone): ?>
                <tr class="hover:bg-slate-50/70"
                    data-js="zone-row"
                    data-zone-search="<?= e(strtolower(($zone['city_name'] ?? '') . ' ' . ($zone['neighborhood'] ?? ''))) ?>">
                  <td class="p-3 align-middle font-medium text-slate-800"><?= e($zone['city_name'] ?? '') ?></td>
                  <td class="p-3 align-middle text-slate-700"><?= e($zone['neighborhood'] ?? '') ?></td>
                  <td class="p-3 align-middle text-slate-700">R$ <?= number_format((float)($zone['fee'] ?? 0), 2, ',', '.') ?></td>
                  <td class="p-3 align-middle">
                    <div class="flex justify-end gap-2">
                      <a href="<?= e($basePath . delivery_query_suffix($queryState, ['edit_zone' => (int)($zone['id'] ?? 0)], ['edit_city'])) ?>"
                         class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 16.5 16.5 5 19 7.5 7.5 19H5v-2.5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Editar
                      </a>
                      <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/zones/' . (int)($zone['id'] ?? 0) . '/del')) ?>"
                            onsubmit="return confirm('Remover esta taxa de entrega?');">
                        <?php if (function_exists('csrf_field')): ?>
                          <?= csrf_field() ?>
                        <?php elseif (function_exists('csrf_token')): ?>
                          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <?php endif; ?>
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-700 shadow-sm hover:bg-red-50">
                          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M9 7v11m6-11v11M8 7l1-2h6l1 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                          Excluir
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <tr class="hidden" data-js="zone-empty">
                <td colspan="4" class="p-4 text-center text-sm text-slate-500">Nenhum bairro encontrado para a busca atual.</td>
              </tr>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Busca de cidades (client-side)
  var cityForm  = document.querySelector('[data-js="city-search-form"]');
  var cityInput = document.querySelector('[data-js="city-search-input"]');
  var cityList  = document.querySelector('[data-js="city-list"]');
  var cityItems = cityList ? Array.prototype.slice.call(cityList.querySelectorAll('[data-js="city-item"]')) : [];
  var cityEmpty = document.querySelector('[data-js="city-empty"]');

  function filterCities() {
    if (!cityList) return;
    var term = (cityInput && cityInput.value ? cityInput.value : '').toLowerCase().trim();
    var visible = 0;
    cityItems.forEach(function (item) {
      var haystack = (item.dataset && item.dataset.cityName ? item.dataset.cityName : '').toLowerCase();
      var match = term === '' || haystack.indexOf(term) !== -1;
      item.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    if (cityList) cityList.style.display = visible === 0 ? 'none' : '';
    if (cityEmpty) cityEmpty.classList.toggle('hidden', visible !== 0);
  }

  if (cityForm && cityInput && cityList) {
    cityForm.addEventListener('submit', function (event) {
      event.preventDefault();
      filterCities();
    });
    cityInput.addEventListener('input', filterCities);
    filterCities();
  }

  // Busca de bairros/zonas (client-side)
  var zoneForm  = document.querySelector('[data-js="zone-search-form"]');
  var zoneInput = document.querySelector('[data-js="zone-search-input"]');
  var zoneBody  = document.querySelector('[data-js="zone-body"]');
  var zoneRows  = zoneBody ? Array.prototype.slice.call(zoneBody.querySelectorAll('[data-js="zone-row"]')) : [];
  var zoneEmpty = document.querySelector('[data-js="zone-empty"]');

  function filterZones() {
    if (!zoneBody) return;
    var term = (zoneInput && zoneInput.value ? zoneInput.value : '').toLowerCase().trim();
    var visible = 0;
    zoneRows.forEach(function (row) {
      var haystack = (row.dataset && row.dataset.zoneSearch ? row.dataset.zoneSearch : '').toLowerCase();
      var match = term === '' || haystack.indexOf(term) !== -1;
      row.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    if (zoneEmpty) zoneEmpty.classList.toggle('hidden', visible !== 0);
  }

  if (zoneForm && zoneInput && zoneBody) {
    zoneForm.addEventListener('submit', function (event) {
      event.preventDefault();
      filterZones();
    });
    zoneInput.addEventListener('input', filterZones);
    filterZones();
  }
});
</script>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
