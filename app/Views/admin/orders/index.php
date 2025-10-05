<?php
// admin/orders/index.php — Pedidos (versão moderna)

$title = 'Pedidos - ' . ($company['name'] ?? 'Empresa');
$slug  = rawurlencode((string)($activeSlug ?? ($company['slug'] ?? '')));
$backUrl = $slug ? base_url('admin/' . $slug . '/dashboard') : base_url('admin');

// helper de escape (se ainda não existir)
if (!function_exists('e')) {
    function e($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// filtros (status e busca por cliente)
$status = (string)($_GET['status'] ?? '');
$q      = trim((string)($_GET['q'] ?? ''));

// mapeamento de status -> label
$statusLabels = [
  'pending'   => 'Pendente',
  'paid'      => 'Pago',
  'completed' => 'Concluído',
  'canceled'  => 'Cancelado',
];

// se precisar filtrar em memória (caso o controller não filtre)
$filtered = $orders ?? [];

if ($status !== '' && isset($statusLabels[$status])) {
    $filtered = array_filter($filtered, fn ($o) => (string)($o['status'] ?? '') === $status);
}

if ($q !== '') {
    $qNorm = mb_strtolower($q, 'UTF-8');
    $filtered = array_filter($filtered, function ($o) use ($qNorm) {
        $name  = mb_strtolower((string)($o['customer_name'] ?? ''), 'UTF-8');
        $phone = mb_strtolower((string)($o['customer_phone'] ?? ''), 'UTF-8');
        $id    = (string)($o['id'] ?? '');

        return strpos($name, $qNorm) !== false
            || strpos($phone, $qNorm) !== false
            || strpos($id, $qNorm) !== false;
    });
}

ob_start(); ?>
<div class="mx-auto max-w-6xl p-4">
  <!-- HEADER -->
  <header class="mb-5 flex flex-wrap items-center gap-3">
    <div class="flex items-center gap-3">
      <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl admin-gradient-bg text-white shadow">
<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-cart4" viewBox="0 0 16 16">
  <path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .485.379L2.89 4H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.485-.379L1.61 3H.5a.5.5 0 0 1-.5-.5M3.14 5l.5 2H5V5zM6 5v2h2V5zm3 0v2h2V5zm3 0v2h1.36l.5-2zm1.11 3H12v2h.61zM11 8H9v2h2zM8 8H6v2h2zM5 8H3.89l.5 2H5zm0 5a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0m9-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0"></path>
</svg>
      </span>
      <h1 class="admin-gradient-text bg-clip-text text-2xl font-semibold text-transparent">
        Pedidos
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


      <a href="<?= e(base_url('admin/' . $slug . '/orders/create')) ?>"
        class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-3 py-2 text-sm font-medium text-white shadow hover:opacity-95">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        Novo pedido
      </a>

    </div>
  </header>

  <!-- FILTROS -->
  <form class="mb-4 grid gap-2 sm:grid-cols-[220px_minmax(0,1fr)_auto] sm:items-center"
        method="get" action="<?= e(base_url('admin/' . $slug . '/orders')) ?>">
    <label class="grid gap-1">
      <span class="sr-only">Status</span>
      <select name="status"
              class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
        <option value="">Todos</option>
        <?php foreach ($statusLabels as $k => $label): ?>
          <option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label class="relative">
      <span class="sr-only">Buscar</span>
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar por #, cliente ou telefone"
             class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 pl-9 text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-indigo-400">
      <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none">
        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.6"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </label>

    <div class="flex gap-2">
      <button class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
        Filtrar
      </button>
      <?php if ($status !== '' || $q !== ''): ?>
        <a href="<?= e(base_url('admin/' . $slug . '/orders')) ?>"
           class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
          Limpar
        </a>
      <?php endif; ?>
    </div>
  </form>

  <?php if (empty($filtered)): ?>
    <!-- EMPTY STATE -->
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
      <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M5 7h14M7 12h10M9 17h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      </div>
      <h2 class="text-lg font-medium text-slate-800">Nenhum pedido encontrado</h2>
      <p class="mt-1 text-sm text-slate-500">Ajuste os filtros ou crie um novo pedido agora mesmo.</p>
      <div class="mt-4">
        <a href="<?= e(base_url('admin/' . $slug . '/orders/create')) ?>"
          class="inline-flex items-center gap-2 rounded-xl admin-gradient-bg px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          Novo pedido
        </a>
      </div>
    </div>
  <?php else: ?>

    <!-- TABELA -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="max-w-full overflow-x-auto">
        <table class="min-w-[760px] w-full">
          <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
            <tr>
              <th class="p-3">#</th>
              <th class="p-3">Cliente</th>
              <th class="p-3">Status</th>
              <th class="p-3">Total</th>
              <th class="p-3">Criado</th>
              <th class="p-3 text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-sm">
            <?php foreach ($filtered as $o): ?>
              <?php
                $st = (string)($o['status'] ?? 'pending');
                $label = $statusLabels[$st] ?? ucfirst($st);
                $badge = match ($st) {
                    'paid'      => 'bg-blue-50  text-blue-700  ring-blue-200',
                    'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                    'canceled'  => 'bg-rose-50 text-rose-700 ring-rose-200',
                    default     => 'bg-amber-50 text-amber-700 ring-amber-200',
                };
                $dotClass = match ($st) {
                    'paid'      => 'bg-blue-500',
                    'completed' => 'bg-emerald-500',
                    'canceled'  => 'bg-rose-500',
                    default     => 'bg-amber-500',
                };
                ?>
              <tr class="hover:bg-slate-50/60">
                <td class="p-3 align-middle font-medium text-slate-800">#<?= (int)($o['id'] ?? 0) ?></td>

                <td class="p-3 align-middle">
                  <div class="text-slate-800"><?= e($o['customer_name'] ?? '-') ?></div>
                  <?php if (!empty($o['customer_phone'])): ?>
                    <div class="text-xs text-slate-500"><?= e($o['customer_phone']) ?></div>
                  <?php endif; ?>
                </td>

                <td class="p-3 align-middle">
                  <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[12px] font-medium ring-1 <?= $badge ?>">
                    <span class="h-1.5 w-1.5 rounded-full <?= $dotClass ?>"></span>
                    <?= e($label) ?>
                  </span>
                </td>

                <td class="p-3 align-middle whitespace-nowrap font-medium text-slate-800">
                  R$ <?= number_format((float)($o['total'] ?? 0), 2, ',', '.') ?>
                </td>

                <td class="p-3 align-middle text-slate-700 whitespace-nowrap">
                  <?= e($o['created_at'] ?? '') ?>
                </td>

                <td class="p-3 align-middle">
                  <div class="flex justify-end gap-2">
                    <a class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                       href="<?= e(base_url('admin/' . $slug . '/orders/show?id=' . (int)($o['id'] ?? 0))) ?>">
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Zm9.5-3a3 3 0 1 1-3 3 3 3 0 0 1 3-3Z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      Ver
                    </a>
                    <form method="post"
                          action="<?= e(base_url('admin/' . $slug . '/orders/' . (int)($o['id'] ?? 0) . '/del')) ?>"
                          class="inline"
                          onsubmit="return confirm('Excluir pedido?');">
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
