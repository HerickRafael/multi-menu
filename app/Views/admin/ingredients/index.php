<?php
// admin/ingredients/index.php — Lista de ingredientes (versão moderna, sem coluna de Produtos)

// Helpers (caso a view seja renderizada isolada)
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('base_url')) {
  function base_url($p=''){
    $b = rtrim($_SERVER['BASE_URL'] ?? '/', '/');
    return $b . '/' . ltrim((string)$p, '/');
  }
}
if (!function_exists('price_br')) {
  function price_br($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
}

$title = "Ingredientes - " . ($company['name'] ?? '');
$slug  = rawurlencode((string)($company['slug'] ?? ''));
$selectedProduct = $productId ?? null;
$search = trim((string)($q ?? ''));

// Normaliza lista de produtos (para filtro)
$products = $products ?? [];
$items    = $items    ?? [];

ob_start(); ?>

<div class="mx-auto max-w-6xl p-4">

<!-- HEADER -->
<header class="mb-5 flex flex-wrap items-center gap-3">
  <div class="flex items-center gap-3">
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl admin-gradient-bg text-white shadow">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cup-straw" viewBox="0 0 16 16">
  <path d="M13.902.334a.5.5 0 0 1-.28.65l-2.254.902-.4 1.927c.376.095.715.215.972.367.228.135.56.396.56.82q0 .069-.011.132l-.962 9.068a1.28 1.28 0 0 1-.524.93c-.488.34-1.494.87-3.01.87s-2.522-.53-3.01-.87a1.28 1.28 0 0 1-.524-.93L3.51 5.132A1 1 0 0 1 3.5 5c0-.424.332-.685.56-.82.262-.154.607-.276.99-.372C5.824 3.614 6.867 3.5 8 3.5c.712 0 1.389.045 1.985.127l.464-2.215a.5.5 0 0 1 .303-.356l2.5-1a.5.5 0 0 1 .65.278M9.768 4.607A14 14 0 0 0 8 4.5c-1.076 0-2.033.11-2.707.278A3.3 3.3 0 0 0 4.645 5c.146.073.362.15.648.222C5.967 5.39 6.924 5.5 8 5.5c.571 0 1.109-.03 1.588-.085zm.292 1.756C9.445 6.45 8.742 6.5 8 6.5c-1.133 0-2.176-.114-2.95-.308a6 6 0 0 1-.435-.127l.838 8.03c.013.121.06.186.102.215.357.249 1.168.69 2.438.69s2.081-.441 2.438-.69c.042-.029.09-.094.102-.215l.852-8.03a6 6 0 0 1-.435.127 9 9 0 0 1-.89.17zM4.467 4.884s.003.002.005.006zm7.066 0-.005.006zM11.354 5a3 3 0 0 0-.604-.21l-.099.445.055-.013c.286-.072.502-.149.648-.222"></path>
</svg>
    </span>
    <h1 class="admin-gradient-text bg-clip-text text-2xl font-semibold text-transparent">
      Ingredientes
    </h1>
  </div>

  <div class="ml-auto flex items-center gap-2">


    <a href="<?= e(base_url('admin/' . $slug . '/dashboard')) ?>"
       class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-house-gear" viewBox="0 0 16 16">
  <path d="M7.293 1.5a1 1 0 0 1 1.414 0L11 3.793V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v3.293l2.354 2.353a.5.5 0 0 1-.708.708L8 2.207l-5 5V13.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 1 0 1h-4A1.5 1.5 0 0 1 2 13.5V8.207l-.646.647a.5.5 0 1 1-.708-.708z"/>
  <path d="M11.886 9.46c.18-.613 1.048-.613 1.229 0l.043.148a.64.64 0 0 0 .921.382l.136-.074c.561-.306 1.175.308.87.869l-.075.136a.64.64 0 0 0 .382.92l.149.045c.612.18.612 1.048 0 1.229l-.15.043a.64.64 0 0 0-.38.921l.074.136c.305.561-.309 1.175-.87.87l-.136-.075a.64.64 0 0 0-.92.382l-.045.149c-.18.612-1.048.612-1.229 0l-.043-.15a.64.64 0 0 0-.921-.38l-.136.074c-.561.305-1.175-.309-.87-.87l.075-.136a.64.64 0 0 0-.382-.92l-.148-.044c-.613-.181-.613-1.049 0-1.23l.148-.043a.64.64 0 0 0 .382-.921l-.074-.136c-.306-.561.308-1.175.869-.87l.136.075a.64.64 0 0 0 .92-.382zM14 12.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0"/>
</svg>     Dashboard
    </a>

    <a href="<?= e(base_url('admin/' . $slug . '/ingredients/create')) ?>"
       class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-3 py-2 text-sm font-medium text-white shadow hover:opacity-95">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      Novo
    </a>
  </div>
</header>

<!-- ALERTA DE ERRO -->
<?php if (!empty($error)): ?>
  <div class="mb-4 rounded-xl border border-red-200 bg-red-50/90 p-3 text-sm text-red-800 shadow-sm">
    <?= e($error) ?>
  </div>
<?php endif; ?>

<!-- FILTROS -->
<form method="get" class="mb-4 grid gap-2 sm:grid-cols-[minmax(220px,280px)_1fr_auto]">
  <label class="grid">
    <span class="sr-only">Produto</span>
    <select name="product_id"
            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
      <option value="">Todos os produtos</option>
      <?php foreach ($products as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= ($selectedProduct === (int)$p['id']) ? 'selected' : '' ?>>
          <?= e($p['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label class="relative">
    <span class="sr-only">Buscar</span>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar por nome do ingrediente"
           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 pl-9 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400">
    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none">
      <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.6"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
    </svg>
  </label>

  <div class="flex gap-2">
    <button class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
      Filtrar
    </button>
    <?php if ($search !== '' || $selectedProduct): ?>
      <a href="<?= e(base_url('admin/' . $slug . '/ingredients')) ?>"
         class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
        Limpar
      </a>
    <?php endif; ?>
  </div>
</form>

<?php if (!empty($items)): ?>
  <!-- TABELA -->
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="max-w-full overflow-x-auto">
      <table class="min-w-[820px] w-full">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
          <tr>
            <th class="p-3">Ingrediente</th>
            <th class="p-3">Custo</th>
            <th class="p-3">Valor de venda</th>
            <th class="p-3">Unidade</th>
            <th class="p-3 text-right">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
        <?php foreach ($items as $item): ?>
          <tr class="hover:bg-slate-50/60">
            <!-- Ingrediente + imagem -->
            <td class="p-3">
              <div class="flex items-center gap-3">
                <?php if (!empty($item['image_path'])): ?>
                  <img src="<?= e(base_url($item['image_path'])) ?>" alt=""
                       class="h-12 w-12 rounded-lg object-cover ring-1 ring-slate-200">
                <?php else: ?>
                  <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-100 text-slate-400 ring-1 ring-slate-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM8 10l3 3 2-2 3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </div>
                <?php endif; ?>

                <div>
                  <div class="font-medium text-slate-800"><?= e($item['name'] ?? '') ?></div>
                  <?php $created = !empty($item['created_at']) ? date('d/m/Y', strtotime((string)$item['created_at'])) : null; ?>
                  <?php if ($created): ?>
                    <div class="text-xs text-slate-500">Criado em <?= e($created) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </td>

            <!-- Custo -->
            <td class="p-3 align-middle text-slate-700">
              <?= price_br($item['cost'] ?? 0) ?>
            </td>

            <!-- Venda -->
            <td class="p-3 align-middle text-slate-700">
              <?= price_br($item['sale_price'] ?? 0) ?>
            </td>

            <!-- Unidade -->
            <td class="p-3 align-middle text-slate-700">
              <?php
                $uVal = $item['unit_value'] ?? null;
                if ($uVal !== null && $uVal !== '') {
                  if (!is_string($uVal)) {
                    $uVal = rtrim(rtrim(number_format((float)$uVal, 3, ',', '.'), '0'), ',');
                  }
                }
                $uTxt = trim((string)($item['unit'] ?? ''));
                $unitDisplay = trim(($uVal !== null && $uVal !== '' ? $uVal : '1') . ' ' . $uTxt);
              ?>
              <?= e($unitDisplay) ?>
            </td>

            <!-- Ações -->
            <td class="p-3 align-middle">
              <div class="flex justify-end gap-2">
                <a class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                   href="<?= e(base_url('admin/' . $slug . '/ingredients/' . (int)$item['id'] . '/edit')) ?>">
                  <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l10-10-4-4L4 16v4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                  Editar
                </a>

                <form method="post"
                      action="<?= e(base_url('admin/' . $slug . '/ingredients/' . (int)$item['id'] . '/del')) ?>"
                      class="inline"
                      onsubmit="return confirm('Excluir ingrediente?');">
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
<?php else: ?>
  <!-- EMPTY STATE -->
  <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
    <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
      <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M7 12h10M10 17h7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    </div>
    <h2 class="text-lg font-medium text-slate-800">Nenhum ingrediente encontrado</h2>
    <p class="mt-1 text-sm text-slate-500">Ajuste os filtros ou crie um novo ingrediente.</p>
    <div class="mt-4">
      <a href="<?= e(base_url('admin/' . $slug . '/ingredients/create')) ?>"
         class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        Criar ingrediente
      </a>
    </div>
  </div>
<?php endif; ?>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
