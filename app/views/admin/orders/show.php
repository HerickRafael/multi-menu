<?php
$title = "Pedido #" . ($order['id'] ?? '');
ob_start();
$o = $order;
?>

<div class="max-w-4xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Pedido #<?= (int)$o['id'] ?></h1>
      <a class="px-3 py-2 rounded-xl border" href="<?= e(base_url('admin/' . rawurlencode($activeSlug) . '/orders')) ?>">← Voltar</a>
  </div>

    <form method="post" action="<?= e(base_url('admin/' . rawurlencode($activeSlug) . '/orders/setStatus')) ?>" class="flex items-center gap-2 mb-4">
    <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
    <select name="status" class="border px-3 py-2 rounded">
      <?php foreach (['pending'=>'Pendente','paid'=>'Pago','completed'=>'Concluído','canceled'=>'Cancelado'] as $k=>$label): ?>
        <option value="<?= e($k) ?>" <?= ($o['status']===$k?'selected':'') ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="px-4 py-2 border rounded">Atualizar</button>
  </form>

  <div class="grid md:grid-cols-2 gap-4 mb-6">
    <div class="border rounded p-3">
      <div class="text-sm opacity-70 mb-2">Cliente</div>
      <div><strong><?= e($o['customer_name'] ?? '-') ?></strong></div>
      <div><?= e($o['customer_phone'] ?? '-') ?></div>
    </div>
    <div class="border rounded p-3">
      <div class="text-sm opacity-70 mb-2">Resumo</div>
      <div>Subtotal: R$ <?= number_format((float)$o['subtotal'], 2, ',', '.') ?></div>
      <div>Entrega: R$ <?= number_format((float)$o['delivery_fee'], 2, ',', '.') ?></div>
      <div>Desconto: R$ <?= number_format((float)$o['discount'], 2, ',', '.') ?></div>
      <div class="font-semibold mt-1">Total: R$ <?= number_format((float)$o['total'], 2, ',', '.') ?></div>
      <div class="text-xs opacity-70 mt-2">Status: <?= e($o['status']) ?></div>
    </div>
  </div>

  <div class="border rounded">
    <table class="min-w-full">
      <thead>
        <tr class="bg-gray-50">
          <th class="p-2 text-left">Produto</th>
          <th class="p-2 text-right">Qtde</th>
          <th class="p-2 text-right">Preço</th>
          <th class="p-2 text-right">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($o['items'] as $it): ?>
          <tr class="border-t">
            <td class="p-2"><?= e($it['product_name']) ?></td>
            <td class="p-2 text-right"><?= (int)$it['quantity'] ?></td>
            <td class="p-2 text-right">R$ <?= number_format((float)$it['unit_price'], 2, ',', '.') ?></td>
            <td class="p-2 text-right">R$ <?= number_format((float)$it['line_total'], 2, ',', '.') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($o['items'])): ?>
          <tr><td class="p-4 opacity-70" colspan="4">Sem itens.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($o['notes'])): ?>
    <div class="mt-4 border rounded p-3">
      <div class="text-sm opacity-70 mb-1">Observações</div>
      <div><?= nl2br(e($o['notes'])) ?></div>
    </div>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
