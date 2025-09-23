<?php
// admin/delivery-fees/index.php

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$company    = is_array($company ?? null) ? $company : [];
$cities     = is_array($cities ?? null) ? $cities : [];
$zones      = is_array($zones ?? null) ? $zones : [];
$cityErrors = is_array($cityErrors ?? null) ? $cityErrors : [];
$zoneErrors = is_array($zoneErrors ?? null) ? $zoneErrors : [];
$settingsErrors = is_array($settingsErrors ?? null) ? $settingsErrors : [];
$oldCity    = is_array($oldCity ?? null) ? $oldCity : ['name' => ''];
$oldZone    = is_array($oldZone ?? null) ? $oldZone : ['city_id' => '', 'neighborhood' => '', 'fee' => ''];
$citySearch = (string)($citySearch ?? '');
$zoneSearch = (string)($zoneSearch ?? '');
$editCityId = (int)($editCityId ?? 0);
$editZoneId = (int)($editZoneId ?? 0);
$afterHoursFee = (string)($afterHoursFee ?? '0.00');
$afterHoursFee = $afterHoursFee !== '' ? str_replace(',', '.', $afterHoursFee) : '';
$isFreeDelivery = (bool)($isFreeDelivery ?? false);
$title      = 'Taxas de entrega - ' . ($company['name'] ?? '');
$slug       = rawurlencode((string)($company['slug'] ?? ''));

$deliveryFeesBase = base_url('admin/' . $slug . '/delivery-fees');
$queryDefaults = [];
if ($citySearch !== '') {
  $queryDefaults['city_search'] = $citySearch;
}
if ($zoneSearch !== '') {
  $queryDefaults['zone_search'] = $zoneSearch;
}

$makeUrl = static function(array $extra = []) use ($deliveryFeesBase, $queryDefaults): string {
  $query = $queryDefaults;
  foreach ($extra as $key => $value) {
    if ($value === null || $value === '') {
      unset($query[$key]);
      continue;
    }
    $query[$key] = $value;
  }

  $qs = $query ? ('?' . http_build_query($query)) : '';
  return $deliveryFeesBase . $qs;
};

$zoneCountByCity = [];
foreach ($zones as $zone) {
  $cityId = (int)($zone['city_id'] ?? 0);
  if (!isset($zoneCountByCity[$cityId])) {
    $zoneCountByCity[$cityId] = 0;
  }
  $zoneCountByCity[$cityId]++;
}

ob_start();
?>

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
        <path d="M11.886 9.46c.18-.613 1.048-.613 1.229 0l.043.148a.64.64 0 0 0 .921.382l.136-.074c.561-.306 1.175.308.87.869l-.075.136a.64.64 0 0 0 .382.92l.149.045c.612.18.612 1.048 0 1.229l-.15.043a.64.64 0 0 0-.38.921l.074.136c.305.561-.309 1.175-.87.87l-.136-.075a.64.64 0 0 0-.92.382l-.045.149c-.18.612-1.048.612-1.229 0l-.043-.15a.64.64 0 0 0-.921-.38l-.136.074c-.561.305-1.175-.309-.87-.87l.075-.136a.64.64 0 0 0-.382-.92l-.148-.044c-.613-.181-.613-1.049 0-1.23l.148-.043a.64.64 0 0 0 .382-.921l-.074-.136c-.306-.561.308-1.175.869-.87l.136.075a.64.64 0 0 0 .92-.382zM14 12.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0"/>
      </svg>
      Dashboard
    </a>
  </div>
</header>

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

    <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/cities')) ?>" class="grid gap-4">
      <?php if (function_exists('csrf_field')): ?>
        <?= csrf_field() ?>
      <?php elseif (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <?php endif; ?>
      <input type="hidden" name="city_search" value="<?= e($citySearch) ?>">
      <input type="hidden" name="zone_search" value="<?= e($zoneSearch) ?>">

      <div class="grid gap-2">
        <label for="city-name" class="text-sm font-medium text-slate-700">Nome da cidade <span class="text-red-500">*</span></label>
        <input type="text" id="city-name" name="name" value="<?= e($oldCity['name'] ?? '') ?>" required
               maxlength="120"
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
               placeholder="Ex.: São Paulo">
      </div>

      <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          Salvar cidade
        </button>
      </div>
    </form>

    <div class="mt-6 space-y-4">
      <form method="get" action="<?= e($deliveryFeesBase) ?>" class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm sm:flex-row sm:items-end sm:justify-between">
        <div class="flex flex-1 flex-col gap-2 sm:max-w-sm">
          <label for="city-search" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pesquisar cidade</label>
          <input type="text" id="city-search" name="city_search" value="<?= e($citySearch) ?>"
                 class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                 placeholder="Digite o nome da cidade">
          <input type="hidden" name="zone_search" value="<?= e($zoneSearch) ?>">
        </div>
        <div class="flex gap-2">
          <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="m21 21-5.2-5.2M10 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            Buscar
          </button>
          <?php if ($citySearch !== ''): ?>
            <a href="<?= e($makeUrl(['city_search' => null])) ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50">
              Limpar
            </a>
          <?php endif; ?>
        </div>
      </form>

      <div>
        <div class="mb-3 flex items-center justify-between">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Cidades cadastradas</h3>
          <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Total: <?= count($cities) ?></span>
        </div>

        <?php if (!$cities): ?>
          <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
            Nenhuma cidade cadastrada ainda.
          </div>
        <?php else: ?>
          <ul class="space-y-2">
            <?php foreach ($cities as $city): ?>
              <?php
                $cityId = (int)($city['id'] ?? 0);
                $isEditingCity = $editCityId === $cityId;
                $cityName = (string)($city['name'] ?? '');
                $cityCount = (int)($zoneCountByCity[$cityId] ?? 0);
                $cityInputValue = $isEditingCity ? ($oldCity['name'] ?? $cityName) : $cityName;
              ?>
              <li class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm shadow-sm">
                <?php if ($isEditingCity): ?>
                  <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/cities/' . $cityId)) ?>" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <?php if (function_exists('csrf_field')): ?>
                      <?= csrf_field() ?>
                    <?php elseif (function_exists('csrf_token')): ?>
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="city_search" value="<?= e($citySearch) ?>">
                    <input type="hidden" name="zone_search" value="<?= e($zoneSearch) ?>">
                    <div class="flex-1">
                      <label for="city-edit-<?= $cityId ?>" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Renomear cidade</label>
                      <input type="text" id="city-edit-<?= $cityId ?>" name="name" value="<?= e($cityInputValue) ?>" required maxlength="120"
                             class="w-full rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                      <p class="mt-1 text-xs text-slate-500"><?= $cityCount ?> bairro(s) cadastrados</p>
                    </div>
                    <div class="flex gap-2">
                      <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 12.5 9 16l10-9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Salvar
                      </button>
                      <a href="<?= e($makeUrl(['edit_city' => null])) ?>" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-600 shadow-sm hover:bg-slate-50">
                        Cancelar
                      </a>
                    </div>
                  </form>
                <?php else: ?>
                  <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                      <div class="font-medium text-slate-800"><?= e($cityName) ?></div>
                      <div class="text-xs text-slate-500">
                        <?= $cityCount ?> bairro(s) cadastrados
                      </div>
                    </div>
                    <div class="flex items-center gap-2">
                      <a href="<?= e($makeUrl(['edit_city' => $cityId])) ?>" class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-white px-3 py-1.5 text-xs font-medium text-indigo-600 shadow-sm hover:bg-indigo-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 21v-3.4a2 2 0 0 1 .586-1.414L15.728 4.043a2 2 0 0 1 2.828 0l1.401 1.4a2 2 0 0 1 0 2.829L8.814 19.815A2 2 0 0 1 7.4 20.4L4 21Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Editar
                      </a>
                      <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/cities/' . $cityId . '/del')) ?>"
                            onsubmit="return confirm('Remover esta cidade? Bairros vinculados também serão excluídos.');">
                        <?php if (function_exists('csrf_field')): ?>
                          <?= csrf_field() ?>
                        <?php elseif (function_exists('csrf_token')): ?>
                          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <?php endif; ?>
                        <input type="hidden" name="city_search" value="<?= e($citySearch) ?>">
                        <input type="hidden" name="zone_search" value="<?= e($zoneSearch) ?>">
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-700 shadow-sm hover:bg-red-50">
                          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M9 7v11m6-11v11M8 7l1-2h6l1 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                          Excluir
                        </button>
                      </form>
                    </div>
                  </div>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
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

    <?php if ($settingsErrors): ?>
      <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <strong class="font-semibold">Revise as configurações adicionais:</strong>
        <ul class="mt-2 list-disc space-y-1 pl-4">
          <?php foreach ($settingsErrors as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/zones')) ?>" class="grid gap-4">
      <?php if (function_exists('csrf_field')): ?>
        <?= csrf_field() ?>
      <?php elseif (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <?php endif; ?>
      <input type="hidden" name="city_search" value="<?= e($citySearch) ?>">
      <input type="hidden" name="zone_search" value="<?= e($zoneSearch) ?>">

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

      <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95" <?= $cities ? '' : 'disabled' ?>>
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          Salvar taxa
        </button>
      </div>
    </form>

    <div class="mt-6 space-y-4">
      <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-2">
          <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/zones/adjust')) ?>" class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <?php if (function_exists('csrf_field')): ?>
              <?= csrf_field() ?>
            <?php elseif (function_exists('csrf_token')): ?>
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php endif; ?>
            <input type="hidden" name="city_search" value="<?= e($citySearch) ?>">
            <input type="hidden" name="zone_search" value="<?= e($zoneSearch) ?>">
            <div class="flex items-center justify-between gap-3">
              <label for="adjust-amount" class="text-sm font-medium text-slate-700">Aumentar/diminuir todos bairros em R$</label>
              <select name="operation" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <option value="increase">Aumentar</option>
                <option value="decrease">Diminuir</option>
              </select>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
              <input type="number" min="0" step="0.01" inputmode="decimal" id="adjust-amount" name="amount"
                     class="flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                     placeholder="Ex.: 2,00" required>
              <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M12 5v14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                Aplicar
              </button>
            </div>
            <p class="text-xs text-slate-500">As taxas de todos os bairros serão atualizadas em massa.</p>
          </form>

          <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/settings/after-hours')) ?>" class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <?php if (function_exists('csrf_field')): ?>
              <?= csrf_field() ?>
            <?php elseif (function_exists('csrf_token')): ?>
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php endif; ?>
            <input type="hidden" name="city_search" value="<?= e($citySearch) ?>">
            <input type="hidden" name="zone_search" value="<?= e($zoneSearch) ?>">
            <label for="after-hours-fee" class="text-sm font-medium text-slate-700">Adicional taxa de entrega após as 18:00</label>
            <input type="number" min="0" step="0.01" inputmode="decimal" id="after-hours-fee" name="after_hours_fee"
                   value="<?= e($afterHoursFee) ?>"
                   class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                   placeholder="Ex.: 3,50" required>
            <div class="flex justify-end">
              <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 12.5 9 16l10-9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Salvar adicional
              </button>
            </div>
            <p class="text-xs text-slate-500">Será somado automaticamente às entregas realizadas após as 18h.</p>
          </form>
        </div>

        <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/settings/free-toggle')) ?>" class="mt-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
          <?php if (function_exists('csrf_field')): ?>
            <?= csrf_field() ?>
          <?php elseif (function_exists('csrf_token')): ?>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <?php endif; ?>
          <input type="hidden" name="city_search" value="<?= e($citySearch) ?>">
          <input type="hidden" name="zone_search" value="<?= e($zoneSearch) ?>">
          <input type="hidden" name="free_delivery" value="<?= $isFreeDelivery ? '0' : '1' ?>">
          <div>
            <p class="text-sm font-medium text-slate-700">Taxa gratuita</p>
            <p class="text-xs text-slate-500">Ative para oferecer entregas gratuitas sem considerar as taxas cadastradas.</p>
          </div>
          <button type="submit" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold shadow-sm <?= $isFreeDelivery ? 'bg-emerald-500 text-white hover:bg-emerald-400' : 'border border-slate-300 bg-white text-slate-600 hover:bg-slate-50' ?>">
            <?php if ($isFreeDelivery): ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-toggle-on" viewBox="0 0 16 16">
                <path d="M5 3a5 5 0 0 0 0 10h6a5 5 0 0 0 0-10zm6 9a4 4 0 1 1 0-8 4 4 0 0 1 0 8"/>
              </svg>
              Ativado
            <?php else: ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-toggle-off" viewBox="0 0 16 16">
                <path d="M11 4a4 4 0 0 1 0 8H8a5 5 0 0 0 2-4 5 5 0 0 0-2-4zm-6 8a4 4 0 1 1 0-8 4 4 0 0 1 0 8M0 8a5 5 0 0 0 5 5h6a5 5 0 0 0 0-10H5a5 5 0 0 0-5 5"/>
              </svg>
              Desativado
            <?php endif; ?>
          </button>
        </form>
      </div>

      <form method="get" action="<?= e($deliveryFeesBase) ?>" class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm sm:flex-row sm:items-end sm:justify-between">
        <div class="flex flex-1 flex-col gap-2 sm:max-w-md">
          <label for="zone-search" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pesquisar bairros cadastrados</label>
          <input type="text" id="zone-search" name="zone_search" value="<?= e($zoneSearch) ?>"
                 class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                 placeholder="Busque por bairro ou cidade">
          <input type="hidden" name="city_search" value="<?= e($citySearch) ?>">
        </div>
        <div class="flex gap-2">
          <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="m21 21-5.2-5.2M10 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            Buscar
          </button>
          <?php if ($zoneSearch !== ''): ?>
            <a href="<?= e($makeUrl(['zone_search' => null])) ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm hover:bg-slate-50">
              Limpar
            </a>
          <?php endif; ?>
        </div>
      </form>

      <div>
        <div class="mb-3 flex items-center justify-between">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Bairros cadastrados</h3>
          <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Total: <?= count($zones) ?></span>
        </div>

        <?php if (!$zones): ?>
          <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
            Nenhuma taxa cadastrada ainda.
          </div>
        <?php else: ?>
          <div class="max-h-[520px] overflow-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
                <tr>
                  <th class="p-3">Cidade</th>
                  <th class="p-3">Bairro</th>
                  <th class="p-3">Taxa</th>
                  <th class="p-3 text-right">Ações</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <?php foreach ($zones as $zone): ?>
                  <?php
                    $zoneId = (int)($zone['id'] ?? 0);
                    $isEditingZone = $editZoneId === $zoneId;
                    $zoneCityName = (string)($zone['city_name'] ?? '');
                    $zoneNeighborhood = (string)($zone['neighborhood'] ?? '');
                    $zoneFee = number_format((float)($zone['fee'] ?? 0), 2, ',', '.');
                    $editCityValue = $isEditingZone ? (($oldZone['city_id'] ?? '') !== '' ? (int)$oldZone['city_id'] : (int)($zone['city_id'] ?? 0)) : (int)($zone['city_id'] ?? 0);
                    $editNeighborhoodValue = $isEditingZone ? ($oldZone['neighborhood'] ?? $zoneNeighborhood) : $zoneNeighborhood;
                    $editFeeValue = $isEditingZone ? ($oldZone['fee'] ?? number_format((float)($zone['fee'] ?? 0), 2, '.', '')) : '';
                  ?>
                  <?php if ($isEditingZone): ?>
                    <tr class="bg-indigo-50/70">
                      <td colspan="4" class="p-3">
                        <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/zones/' . $zoneId)) ?>" class="grid gap-3 md:grid-cols-[1fr_1fr_1fr_auto] md:items-end">
                          <?php if (function_exists('csrf_field')): ?>
                            <?= csrf_field() ?>
                          <?php elseif (function_exists('csrf_token')): ?>
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                          <?php endif; ?>
                          <input type="hidden" name="city_search" value="<?= e($citySearch) ?>">
                          <input type="hidden" name="zone_search" value="<?= e($zoneSearch) ?>">
                          <div class="flex flex-col gap-2">
                            <label for="zone-edit-city-<?= $zoneId ?>" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cidade</label>
                            <select id="zone-edit-city-<?= $zoneId ?>" name="city_id" class="rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200" required>
                              <option value="">Selecione</option>
                              <?php foreach ($cities as $city): ?>
                                <option value="<?= (int)($city['id'] ?? 0) ?>" <?= ((string)$editCityValue === (string)($city['id'] ?? '')) ? 'selected' : '' ?>><?= e($city['name'] ?? '') ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="flex flex-col gap-2">
                            <label for="zone-edit-neighborhood-<?= $zoneId ?>" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bairro</label>
                            <input type="text" id="zone-edit-neighborhood-<?= $zoneId ?>" name="neighborhood" value="<?= e($editNeighborhoodValue) ?>" maxlength="120" required
                                   class="rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                          </div>
                          <div class="flex flex-col gap-2">
                            <label for="zone-edit-fee-<?= $zoneId ?>" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Taxa (R$)</label>
                            <input type="number" min="0" step="0.01" inputmode="decimal" id="zone-edit-fee-<?= $zoneId ?>" name="fee" value="<?= e($editFeeValue) ?>" required
                                   class="rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                          </div>
                          <div class="flex items-center gap-2 justify-end">
                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 12.5 9 16l10-9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                              Salvar
                            </button>
                            <a href="<?= e($makeUrl(['edit_zone' => null])) ?>" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-600 shadow-sm hover:bg-slate-50">
                              Cancelar
                            </a>
                          </div>
                        </form>
                      </td>
                    </tr>
                  <?php else: ?>
                    <tr class="hover:bg-slate-50/70">
                      <td class="p-3 align-middle font-medium text-slate-800"><?= e($zoneCityName) ?></td>
                      <td class="p-3 align-middle text-slate-700"><?= e($zoneNeighborhood) ?></td>
                      <td class="p-3 align-middle text-slate-700">R$ <?= $zoneFee ?></td>
                      <td class="p-3 align-middle">
                        <div class="flex justify-end gap-2">
                          <a href="<?= e($makeUrl(['edit_zone' => $zoneId])) ?>" class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-white px-3 py-1.5 text-xs font-medium text-indigo-600 shadow-sm hover:bg-indigo-50">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 21v-3.4a2 2 0 0 1 .586-1.414L15.728 4.043a2 2 0 0 1 2.828 0l1.401 1.4a2 2 0 0 1 0 2.829L8.814 19.815A2 2 0 0 1 7.4 20.4L4 21Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Editar
                          </a>
                          <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/zones/' . $zoneId . '/del')) ?>" onsubmit="return confirm('Remover esta taxa de entrega?');">
                            <?php if (function_exists('csrf_field')): ?>
                              <?= csrf_field() ?>
                            <?php elseif (function_exists('csrf_token')): ?>
                              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <?php endif; ?>
                            <input type="hidden" name="city_search" value="<?= e($citySearch) ?>">
                            <input type="hidden" name="zone_search" value="<?= e($zoneSearch) ?>">
                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-700 shadow-sm hover:bg-red-50">
                              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M9 7v11m6-11v11M8 7l1-2h6l1 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                              Excluir
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endif; ?>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
