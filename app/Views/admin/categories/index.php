<?php
// admin/categories/index.php — Lista de categorias (versão moderna)

$title = 'Categorias - ' . ($company['name'] ?? '');
$slug  = rawurlencode((string)($company['slug'] ?? ''));

// helper de escape (se ainda não existir)

ob_start(); ?>

<div class="mx-auto max-w-6xl p-4">

<!-- HEADER -->
<header class="mb-6 flex items-center gap-3">
  <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl admin-gradient-bg text-white shadow">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-list-ul" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m-3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2m0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2m0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2"></path>
</svg>
  </span>
  <h1 class="admin-gradient-text bg-clip-text text-2xl font-semibold text-transparent">
    Categorias
  </h1>

  <div class="ml-auto flex items-center gap-2">
    <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>"
       class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-house-gear" viewBox="0 0 16 16">
  <path d="M7.293 1.5a1 1 0 0 1 1.414 0L11 3.793V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v3.293l2.354 2.353a.5.5 0 0 1-.708.708L8 2.207l-5 5V13.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 1 0 1h-4A1.5 1.5 0 0 1 2 13.5V8.207l-.646.647a.5.5 0 1 1-.708-.708z"/>
  <path d="M11.886 9.46c.18-.613 1.048-.613 1.229 0l.043.148a.64.64 0 0 0 .921.382l.136-.074c.561-.306 1.175.308.87.869l-.075.136a.64.64 0 0 0 .382.92l.149.045c.612.18.612 1.048 0 1.229l-.15.043a.64.64 0 0 0-.38.921l.074.136c.305.561-.309 1.175-.87.87l-.136-.075a.64.64 0 0 0-.92.382l-.045.149c-.18.612-1.048.612-1.229 0l-.043-.15a.64.64 0 0 0-.921-.38l-.136.074c-.561.305-1.175-.309-.87-.87l.075-.136a.64.64 0 0 0-.382-.92l-.148-.044c-.613-.181-.613-1.049 0-1.23l.148-.043a.64.64 0 0 0 .382-.921l-.074-.136c-.306-.561.308-1.175.869-.87l.136.075a.64.64 0 0 0 .92-.382zM14 12.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0"/>
</svg>      Dashboard
    </a>

    <a href="<?= e(base_url('admin/' . $slug . '/categories/create')) ?>"
       class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-3 py-2 text-sm font-medium text-white shadow hover:opacity-95">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      Nova
    </a>
  </div>
</header>

<?php if (empty($cats)): ?>
  <!-- EMPTY STATE -->
  <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
    <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
      <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M6 6h12v12H6z" stroke="currentColor" stroke-width="1.6"/></svg>
    </div>
    <h2 class="text-lg font-medium text-slate-800">Nenhuma categoria cadastrada</h2>
    <p class="mt-1 text-sm text-slate-500">Crie a primeira categoria para organizar seus produtos.</p>
    <div class="mt-4">
      <a href="<?= e(base_url('admin/' . $slug . '/categories/create')) ?>"
         class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        Criar categoria
      </a>
    </div>
  </div>
<?php else: ?>

  <!-- TABELA -->
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="max-w-full overflow-x-auto">
      <table class="min-w-[600px] w-full">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
          <tr>
            <th class="p-3">Nome</th>
            <th class="p-3">Ordem</th>
            <th class="p-3">Status</th>
            <th class="p-3 text-right">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <?php foreach ($cats as $c): ?>
            <tr class="hover:bg-slate-50/60">
              <td class="p-3 align-middle">
                <div class="font-medium text-slate-800"><?= e($c['name'] ?? '-') ?></div>
              </td>

              <td class="p-3 align-middle">
                <span class="rounded-lg bg-slate-50 px-2 py-0.5 text-[12px] text-slate-700 ring-1 ring-slate-200">
                  <?= (int)($c['sort_order'] ?? 0) ?>
                </span>
              </td>

              <td class="p-3 align-middle">
                <?php if (!empty($c['active'])): ?>
                  <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[12px] font-medium text-emerald-700 ring-1 ring-emerald-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Ativa
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[12px] font-medium text-slate-600 ring-1 ring-slate-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Inativa
                  </span>
                <?php endif; ?>
              </td>

              <td class="p-3 align-middle">
                <div class="flex justify-end gap-2">
                  <a class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                     href="<?= e(base_url('admin/' . $slug . '/categories/' . (int)$c['id'] . '/edit')) ?>">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    Editar
                  </a>

                  <form method="post"
                        action="<?= e(base_url('admin/' . $slug . '/categories/' . (int)$c['id'] . '/del')) ?>"
                        class="inline"
                        onsubmit="return confirm('Excluir categoria?');">
                    <?php if (function_exists('csrf_field')): ?>
                      <?= csrf_field() ?>
                    <?php elseif (function_exists('csrf_token')): ?>
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <?php endif; ?>
                    <button class="inline-flex items-center gap-1.5 rounded-xl border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-700 shadow-sm hover:bg-red-50">
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M9 7v11m6-11v11M8 7l1-2h6l1 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                      Excluir
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
