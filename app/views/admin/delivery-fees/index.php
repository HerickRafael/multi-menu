<?php
// admin/delivery-fees/index.php

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$company   = is_array($company ?? null) ? $company : [];
$zones     = is_array($zones ?? null) ? $zones : [];
$errors    = is_array($errors ?? null) ? $errors : [];
$old       = is_array($old ?? null) ? $old : ['city' => '', 'neighborhood' => '', 'fee' => ''];
$title     = 'Taxas de entrega - ' . ($company['name'] ?? '');
$slug      = rawurlencode((string)($company['slug'] ?? ''));

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
    <p class="text-sm text-slate-500">Cadastre bairros e cidades atendidas com os respectivos valores.</p>
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

<?php if ($errors): ?>
  <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
    <strong class="font-semibold">Corrija os campos a seguir:</strong>
    <ul class="mt-2 list-disc space-y-1 pl-4">
      <?php foreach ($errors as $error): ?>
        <li><?= e($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="grid gap-5 lg:grid-cols-[1.1fr_1fr]">
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="mb-4 text-lg font-semibold text-slate-800">Cadastrar nova taxa</h2>
    <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees')) ?>" class="grid gap-4">
      <?php if (function_exists('csrf_field')): ?>
        <?= csrf_field() ?>
      <?php elseif (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <?php endif; ?>

      <div class="grid gap-2">
        <label for="city" class="text-sm font-medium text-slate-700">Cidade <span class="text-red-500">*</span></label>
        <input type="text" id="city" name="city" value="<?= e($old['city'] ?? '') ?>" required
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
               placeholder="Ex.: São Paulo">
      </div>

      <div class="grid gap-2">
        <label for="neighborhood" class="text-sm font-medium text-slate-700">Bairro <span class="text-red-500">*</span></label>
        <input type="text" id="neighborhood" name="neighborhood" value="<?= e($old['neighborhood'] ?? '') ?>" required
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
               placeholder="Ex.: Centro">
      </div>

      <div class="grid gap-2">
        <label for="fee" class="text-sm font-medium text-slate-700">Taxa de entrega (R$) <span class="text-red-500">*</span></label>
        <input type="number" min="0" step="0.01" inputmode="decimal" id="fee" name="fee" value="<?= e($old['fee'] ?? '') ?>" required
               class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
               placeholder="Ex.: 8,00">
      </div>

      <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          Salvar taxa
        </button>
      </div>
    </form>
  </section>

  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
      <h2 class="text-lg font-semibold text-slate-800">Bairros cadastrados</h2>
      <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">Total: <?= count($zones) ?></span>
    </div>

    <?php if (!$zones): ?>
      <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
        Nenhuma taxa cadastrada ainda.
      </div>
    <?php else: ?>
      <div class="max-h-[480px] overflow-auto rounded-xl border border-slate-200">
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
              <tr class="hover:bg-slate-50/70">
                <td class="p-3 align-middle font-medium text-slate-800"><?= e($zone['city'] ?? '') ?></td>
                <td class="p-3 align-middle text-slate-700"><?= e($zone['neighborhood'] ?? '') ?></td>
                <td class="p-3 align-middle text-slate-700">R$ <?= number_format((float)($zone['fee'] ?? 0), 2, ',', '.') ?></td>
                <td class="p-3 align-middle">
                  <form method="post" action="<?= e(base_url('admin/' . $slug . '/delivery-fees/' . (int)($zone['id'] ?? 0) . '/del')) ?>"
                        class="flex justify-end"
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
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
