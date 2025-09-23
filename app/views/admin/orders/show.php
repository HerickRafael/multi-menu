<?php
// admin/orders/show.php — Detalhe do pedido (versão moderna)

$title = "Pedido #" . ($order['id'] ?? '');
$o     = $order ?? [];
$slug  = rawurlencode((string)($activeSlug ?? ($company['slug'] ?? '')));

// helper de escape (se ainda não existir)
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// labels e cores de status
$statusLabels = [
  'pending'   => 'Pendente',
  'paid'      => 'Pago',
  'completed' => 'Concluído',
  'canceled'  => 'Cancelado',
];
$st = (string)($o['status'] ?? 'pending');
$badgeClass = match($st){
  'paid'      => 'bg-blue-50  text-blue-700  ring-blue-200',
  'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  'canceled'  => 'bg-rose-50 text-rose-700 ring-rose-200',
  default     => 'bg-amber-50 text-amber-700 ring-amber-200',
};

// util: montar link do WhatsApp se houver telefone
$wa = null;
if (!empty($o['customer_phone'])) {
  $digits = preg_replace('/\D+/', '', (string)$o['customer_phone']);
  if ($digits) {
    $waText = rawurlencode("Olá! Sobre o pedido #".(int)($o['id'] ?? 0).".");
    $wa = "https://wa.me/{$digits}?text={$waText}";
  }
}

ob_start(); ?>
<div class="admin-print-only">
  <?php
    $companyName    = trim((string)($company['name'] ?? ''));
    $companyAddress = trim((string)($company['address'] ?? ''));
    $companyContact = trim((string)($company['whatsapp'] ?? ($company['phone'] ?? '')));
    $createdAt      = trim((string)($o['created_at'] ?? ''));
    $subtotal       = (float)($o['subtotal'] ?? 0);
    $deliveryFee    = (float)($o['delivery_fee'] ?? 0);
    $discountValue  = (float)($o['discount'] ?? 0);
    $totalValue     = (float)($o['total'] ?? 0);
    $printTitle     = $companyName !== ''
      ? (function_exists('mb_strtoupper') ? mb_strtoupper($companyName, 'UTF-8') : strtoupper($companyName))
      : 'PEDIDO';
    $printStatus    = $statusLabels[$st] ?? ucfirst($st);
    $items          = is_array($o['items'] ?? null) ? $o['items'] : [];
    $discountLabel  = $discountValue > 0
      ? '-R$ ' . number_format($discountValue, 2, ',', '.')
      : 'R$ ' . number_format($discountValue, 2, ',', '.');
  ?>
  <div class="receipt">
    <div class="receipt-header">
      <h1><?= e($printTitle) ?></h1>
      <?php if ($companyAddress !== ''): ?>
        <p class="receipt-text"><?= e($companyAddress) ?></p>
      <?php endif; ?>
      <?php if ($companyContact !== ''): ?>
        <p class="receipt-text">Contato: <?= e($companyContact) ?></p>
      <?php endif; ?>
    </div>
    <hr>
    <div class="receipt-section">
      <div class="receipt-row"><span>Pedido</span><span>#<?= (int)($o['id'] ?? 0) ?></span></div>
      <?php if ($createdAt !== ''): ?>
        <div class="receipt-row"><span>Data</span><span><?= e($createdAt) ?></span></div>
      <?php endif; ?>
      <div class="receipt-row"><span>Status</span><span><?= e($printStatus) ?></span></div>
    </div>
    <hr>
    <div class="receipt-section">
      <div class="receipt-label">Cliente</div>
      <div class="receipt-text"><?= e($o['customer_name'] ?? '-') ?></div>
      <?php if (!empty($o['customer_phone'])): ?>
        <div class="receipt-text">Tel: <?= e($o['customer_phone']) ?></div>
      <?php endif; ?>
      <?php if (!empty($o['customer_address'])): ?>
        <div class="receipt-text receipt-pre"><?= e($o['customer_address']) ?></div>
      <?php endif; ?>
      <?php if (!empty($o['notes'])): ?>
        <div class="receipt-text receipt-pre">Obs.: <?= e($o['notes']) ?></div>
      <?php endif; ?>
    </div>
    <hr>
    <div class="receipt-section">
      <div class="receipt-label">Itens</div>
      <table class="receipt-table">
        <?php foreach ($items as $it): ?>
          <tr>
            <td colspan="3" class="receipt-item-name"><?= e($it['product_name'] ?? '-') ?></td>
          </tr>
          <tr class="receipt-item-row">
            <td class="qty"><?= (int)($it['quantity'] ?? 0) ?>x</td>
            <td class="price">R$ <?= number_format((float)($it['unit_price'] ?? 0), 2, ',', '.') ?></td>
            <td class="total">R$ <?= number_format((float)($it['line_total'] ?? 0), 2, ',', '.') ?></td>
          </tr>
          <?php if (!empty($it['notes'])): ?>
            <tr>
              <td colspan="3" class="receipt-note receipt-pre"><?= e($it['notes']) ?></td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
          <tr>
            <td colspan="3" class="receipt-text">Sem itens neste pedido.</td>
          </tr>
        <?php endif; ?>
      </table>
    </div>
    <hr>
    <div class="receipt-section">
      <div class="receipt-row"><span>Subtotal</span><span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span></div>
      <div class="receipt-row"><span>Entrega</span><span>R$ <?= number_format($deliveryFee, 2, ',', '.') ?></span></div>
      <div class="receipt-row"><span>Desconto</span><span><?= $discountLabel ?></span></div>
      <div class="receipt-total"><span>Total</span><span>R$ <?= number_format($totalValue, 2, ',', '.') ?></span></div>
    </div>
    <hr>
    <div class="receipt-footer">
      <div>Nº do pedido: #<?= (int)($o['id'] ?? 0) ?></div>
      <div>Obrigado pela preferência!</div>
    </div>
  </div>
</div>
<div class="mx-auto max-w-5xl p-4 admin-screen-only">
  <!-- HEADER -->
  <header class="mb-5 flex flex-wrap items-center gap-3">
    <div class="flex items-center gap-3">
      <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl admin-gradient-bg text-white shadow">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M5 7h14M7 12h10M9 17h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      </span>
      <h1 class="admin-gradient-text bg-clip-text text-2xl font-semibold text-transparent">
        Pedido #<?= (int)($o['id'] ?? 0) ?>
      </h1>
      <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[12px] font-medium ring-1 <?= $badgeClass ?>">
        <?php if ($st === 'completed'): ?>
          <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
        <?php elseif ($st === 'paid'): ?>
          <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
        <?php elseif ($st === 'canceled'): ?>
          <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
        <?php else: ?>
          <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
        <?php endif; ?>
        <?= e($statusLabels[$st] ?? ucfirst($st)) ?>
      </span>
    </div>

    <div class="ml-auto flex items-center gap-2">
      <?php if ($wa): ?>
        <a href="<?= e($wa) ?>" target="_blank" rel="noopener"
           class="inline-flex items-center gap-2 rounded-xl border border-emerald-300 bg-white px-3 py-2 text-sm font-medium text-emerald-700 shadow-sm hover:bg-emerald-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M7 20l1.5-4.5a7 7 0 1 1 2.5 2.5L7 20z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          WhatsApp
        </a>
      <?php endif; ?>
      <form method="post"
            action="<?= e(base_url('admin/' . $slug . '/orders/' . (int)($o['id'] ?? 0) . '/del')) ?>"
            class="inline"
            onsubmit="return confirm('Excluir pedido?');">
        <?php if (function_exists('csrf_field')): ?>
          <?= csrf_field() ?>
        <?php elseif (function_exists('csrf_token')): ?>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php endif; ?>
        <button class="inline-flex items-center gap-2 rounded-xl border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M9 7v11m6-11v11M8 7l1-2h6l1 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
          Excluir
        </button>
      </form>
      <button type="button" onclick="window.print()"
         class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M7 9V4h10v5M7 14H5a2 2 0 0 1-2-2v-1a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2h-2m-10 0h10v6H7v-6Z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Imprimir
      </button>
      <a class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50"
         href="<?= e(base_url('admin/' . $slug . '/orders')) ?>">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 6 9 12l6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Voltar
      </a>
    </div>
  </header>

  <!-- STATUS: formulário -->
  <form method="post" action="<?= e(base_url('admin/' . $slug . '/orders/setStatus')) ?>"
        class="mb-4 inline-flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
    <?php if (function_exists('csrf_field')): ?>
      <?= csrf_field() ?>
    <?php elseif (function_exists('csrf_token')): ?>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php endif; ?>
    <input type="hidden" name="id" value="<?= (int)($o['id'] ?? 0) ?>">
    <label class="text-sm text-slate-700">Atualizar status:</label>
    <select name="status" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400">
      <?php foreach ($statusLabels as $k=>$label): ?>
        <option value="<?= e($k) ?>" <?= ($o['status'] ?? '')===$k ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Aplicar
    </button>
    <?php if (!empty($o['created_at'])): ?>
      <span class="ml-auto text-xs text-slate-500">Criado em: <?= e($o['created_at']) ?></span>
    <?php endif; ?>
  </form>

  <!-- CARDS: Cliente & Resumo -->
  <div class="mb-6 grid gap-4 md:grid-cols-2">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
      <h2 class="mb-2 text-sm font-medium text-slate-700">Cliente</h2>
      <div class="text-lg font-semibold text-slate-900"><?= e($o['customer_name'] ?? '-') ?></div>
      <div class="text-slate-700"><?= e($o['customer_phone'] ?? '-') ?></div>
      <?php if (!empty($o['customer_address'])): ?>
        <div class="mt-1 text-sm text-slate-700"><?= nl2br(e($o['customer_address'])) ?></div>
      <?php endif; ?>
      <?php if (!empty($o['notes'])): ?>
        <div class="mt-3 rounded-xl bg-slate-50 p-3 text-sm">
          <div class="mb-1 text-xs font-medium text-slate-500">Observações</div>
          <div><?= nl2br(e($o['notes'])) ?></div>
        </div>
      <?php endif; ?>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
      <h2 class="mb-2 text-sm font-medium text-slate-700">Resumo</h2>
      <dl class="space-y-1 text-slate-800">
        <div class="flex justify-between"><dt>Subtotal</dt><dd>R$ <?= number_format((float)($o['subtotal'] ?? 0), 2, ',', '.') ?></dd></div>
        <div class="flex justify-between"><dt>Entrega</dt><dd>R$ <?= number_format((float)($o['delivery_fee'] ?? 0), 2, ',', '.') ?></dd></div>
        <div class="flex justify-between"><dt>Desconto</dt><dd>R$ <?= number_format((float)($o['discount'] ?? 0), 2, ',', '.') ?></dd></div>
      </dl>
      <div class="mt-2 flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
        <div class="text-sm text-slate-600">Total</div>
        <div class="text-xl font-semibold text-slate-900">R$ <?= number_format((float)($o['total'] ?? 0), 2, ',', '.') ?></div>
      </div>
      <div class="mt-2 text-xs text-slate-500">Status atual: <?= e($statusLabels[$st] ?? ucfirst($st)) ?></div>
    </div>
  </div>

  <!-- ITENS -->
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="max-w-full overflow-x-auto">
      <table class="min-w-[720px] w-full">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-600">
          <tr>
            <th class="p-3">Produto</th>
            <th class="p-3 text-right">Qtde</th>
            <th class="p-3 text-right">Preço</th>
            <th class="p-3 text-right">Total</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <?php foreach (($o['items'] ?? []) as $it): ?>
            <tr class="hover:bg-slate-50/60">
              <td class="p-3 align-middle">
                <div class="font-medium text-slate-800"><?= e($it['product_name'] ?? '-') ?></div>
                <?php if (!empty($it['notes'])): ?>
                  <div class="mt-0.5 text-xs text-slate-500"><?= nl2br(e($it['notes'])) ?></div>
                <?php endif; ?>
              </td>
              <td class="p-3 align-middle text-right"><?= (int)($it['quantity'] ?? 0) ?></td>
              <td class="p-3 align-middle whitespace-nowrap text-right">R$ <?= number_format((float)($it['unit_price'] ?? 0), 2, ',', '.') ?></td>
              <td class="p-3 align-middle whitespace-nowrap text-right font-medium text-slate-800">
                R$ <?= number_format((float)($it['line_total'] ?? 0), 2, ',', '.') ?>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($o['items'])): ?>
            <tr><td class="p-6 text-center text-slate-500" colspan="4">Sem itens neste pedido.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
