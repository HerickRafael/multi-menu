<?php
// admin/orders/show.php — Detalhe do pedido (versão moderna)

$title = 'Pedido #' . ($order['id'] ?? '');
$o     = $order ?? [];
$slug  = rawurlencode((string)($activeSlug ?? ($company['slug'] ?? '')));

// labels e cores de status
$statusLabels = [
  'pending'   => 'Pendente',
  'paid'      => 'Pago', 
  'completed' => 'Concluído',
  'canceled'  => 'Cancelado',
];
$st = (string)($o['status'] ?? 'pending');

// util: montar link do WhatsApp se houver telefone
$wa = null;

if (!empty($o['customer_phone'])) {
    $digits = preg_replace('/\D+/', '', (string)$o['customer_phone']);

    if ($digits) {
        $waText = rawurlencode('Olá! Sobre o pedido #'.(int)($o['id'] ?? 0).'.');
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
          <?php 
          // Combo
          $comboDataPrint = null;
          if (!empty($it['combo_data'])) {
              $comboDataPrint = is_string($it['combo_data']) ? json_decode($it['combo_data'], true) : $it['combo_data'];
          }
          if ($comboDataPrint && !empty($comboDataPrint['selected_items'])): 
          ?>
            <tr>
              <td colspan="3" class="receipt-note" style="padding-left: 1em;">
                <strong>Opções:</strong>
                <?php 
                $comboNames = [];
                foreach ($comboDataPrint['selected_items'] as $comboItem) {
                    $comboNames[] = $comboItem['simple_name'] ?? $comboItem['name'] ?? '';
                }
                echo e(implode(', ', array_filter($comboNames)));
                ?>
              </td>
            </tr>
          <?php endif; ?>
          
          <?php 
          // Personalização
          $customDataPrint = null;
          if (!empty($it['customization_data'])) {
              $customDataPrint = is_string($it['customization_data']) ? json_decode($it['customization_data'], true) : $it['customization_data'];
          }
          if ($customDataPrint && !empty($customDataPrint['groups'])): 
              $customItemsPrint = [];
              foreach ($customDataPrint['groups'] as $group) {
                  if (!empty($group['items'])) {
                      foreach ($group['items'] as $customItem) {
                          $itemName = $customItem['name'] ?? '';
                          $qty = $customItem['qty'] ?? 1;
                          $deltaQty = $customItem['delta_qty'] ?? null;
                          
                          if ($itemName && ($deltaQty !== 0 || in_array($group['type'] ?? '', ['addon', 'single']))) {
                              if ($deltaQty !== null && $deltaQty > 0) {
                                  $customItemsPrint[] = "+{$deltaQty}x {$itemName}";
                              } elseif ($deltaQty !== null && $deltaQty < 0) {
                                  $customItemsPrint[] = "Sem {$itemName}";
                              } elseif ($qty > 1) {
                                  $customItemsPrint[] = "{$qty}x {$itemName}";
                              } else {
                                  $customItemsPrint[] = $itemName;
                              }
                          }
                      }
                  }
              }
              if ($customItemsPrint):
          ?>
            <tr>
              <td colspan="3" class="receipt-note" style="padding-left: 1em;">
                <strong>Personalização:</strong> <?= e(implode(', ', $customItemsPrint)) ?>
              </td>
            </tr>
          <?php endif; endif; ?>
          
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
      <?= status_pill($st, $statusLabels[$st] ?? ucfirst($st)) ?>
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
  <a href="<?= base_url('admin/' . e($activeSlug) . '/orders/print?id=' . (int)$o['id']) ?>"
     target="_blank"
     class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M7 9V4h10v5M7 14H5a2 2 0 0 1-2-2v-1a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2h-2m-10 0h10v6H7v-6Z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Imprimir
      </a>
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
      <?php foreach ($statusLabels as $k => $label): ?>
        <option value="<?= e($k) ?>" <?= ($o['status'] ?? '') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
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
  <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-4 py-3">
      <h2 class="text-sm font-medium text-slate-700">Itens do Pedido</h2>
    </div>
    <div class="divide-y divide-slate-100">
      <?php foreach (($o['items'] ?? []) as $it): ?>
        <div class="p-4 hover:bg-slate-50/60 transition-colors">
          <!-- Nome e Quantidade -->
          <div class="flex items-start justify-between gap-4 mb-3">
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                  <?= (int)($it['quantity'] ?? 0) ?>x
                </span>
                <h3 class="text-base font-semibold text-slate-900"><?= e($it['product_name'] ?? '-') ?></h3>
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm text-slate-500">R$ <?= number_format((float)($it['unit_price'] ?? 0), 2, ',', '.') ?></div>
              <div class="text-lg font-bold text-slate-900">R$ <?= number_format((float)($it['line_total'] ?? 0), 2, ',', '.') ?></div>
            </div>
          </div>
          
          <?php 
          // Decodificar dados de combo
          $comboData = null;
          if (!empty($it['combo_data'])) {
              $comboData = is_string($it['combo_data']) ? json_decode($it['combo_data'], true) : $it['combo_data'];
          }
          
          // Decodificar dados de personalização
          $customData = null;
          if (!empty($it['customization_data'])) {
              $customData = is_string($it['customization_data']) ? json_decode($it['customization_data'], true) : $it['customization_data'];
          }
          ?>
          
          <!-- Opções do Combo -->
          <?php if ($comboData && !empty($comboData['selected_items'])): ?>
            <div class="mt-3 border-t border-slate-100 pt-3">
              <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                Opções do Combo
              </div>
              <div class="space-y-1.5">
                <?php foreach ($comboData['selected_items'] as $idx => $comboItem): ?>
                  <?php $itemName = $comboItem['simple_name'] ?? $comboItem['name'] ?? ''; ?>
                  <?php if ($itemName): ?>
                    <div class="flex items-center justify-between text-sm">
                      <span class="<?= $idx % 2 == 0 ? 'font-medium text-slate-700' : 'text-slate-600' ?>">
                        <?= e($itemName) ?>
                      </span>
                      <span class="text-slate-400">
                        <?php
                        $delta = (float)($comboItem['delta'] ?? $comboItem['delta_price'] ?? 0);
                        if ($delta > 0) {
                            echo '+ R$ ' . number_format($delta, 2, ',', '.');
                        } else {
                            echo 'Incluso';
                        }
                        ?>
                      </span>
                    </div>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
          
          <!-- Personalização -->
          <?php if ($customData && !empty($customData['groups'])): ?>
            <?php 
            $customItems = [];
            foreach ($customData['groups'] as $group) {
                if (!empty($group['items'])) {
                    foreach ($group['items'] as $customItem) {
                        $itemName = $customItem['name'] ?? '';
                        $qty = $customItem['qty'] ?? 1;
                        $deltaQty = $customItem['delta_qty'] ?? null;
                        $price = $customItem['price'] ?? 0;
                        
                        // Mostrar apenas modificações
                        if ($itemName && ($deltaQty !== 0 || in_array($group['type'] ?? '', ['addon', 'single']))) {
                            $displayText = '';
                            $status = '';
                            
                            if ($deltaQty !== null && $deltaQty > 0) {
                                $displayText = "{$deltaQty}x {$itemName}";
                                $status = $price > 0 ? '+ R$ ' . number_format($price, 2, ',', '.') : 'Extra';
                            } elseif ($deltaQty !== null && $deltaQty < 0) {
                                $displayText = $itemName;
                                $status = 'Sem';
                            } elseif ($qty > 1) {
                                $displayText = "{$qty}x {$itemName}";
                                $status = $price > 0 ? '+ R$ ' . number_format($price, 2, ',', '.') : 'Incluso';
                            } else {
                                $displayText = $itemName;
                                $status = $price > 0 ? '+ R$ ' . number_format($price, 2, ',', '.') : 'Incluso';
                            }
                            
                            $customItems[] = [
                                'text' => $displayText,
                                'status' => $status
                            ];
                        }
                    }
                }
            }
            ?>
            <?php if (!empty($customItems)): ?>
              <div class="mt-3 border-t border-slate-100 pt-3">
                <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                  Personalize os ingredientes
                </div>
                <div class="space-y-1.5">
                  <?php foreach ($customItems as $idx => $custom): ?>
                    <div class="flex items-center justify-between text-sm">
                      <span class="<?= $idx % 2 == 0 ? 'font-medium text-slate-700' : 'text-slate-600' ?>">
                        <?= e($custom['text']) ?>
                      </span>
                      <span class="text-slate-400">
                        <?= e($custom['status']) ?>
                      </span>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          <?php endif; ?>
          
          <!-- Observações do item -->
          <?php if (!empty($it['notes'])): ?>
            <div class="mt-3 border-t border-slate-100 pt-3">
              <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                Observações
              </div>
              <p class="text-sm text-slate-600"><?= nl2br(e($it['notes'])) ?></p>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <?php if (empty($o['items'])): ?>
        <div class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-slate-300" viewBox="0 0 24 24" fill="none">
            <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" stroke="currentColor" stroke-width="1.5"/>
          </svg>
          <p class="mt-2 text-sm text-slate-500">Sem itens neste pedido.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
