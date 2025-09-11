<?php
$title = "Pedidos - " . ($company['name'] ?? 'Empresa');
ob_start();

/** Resolve URL de voltar (dashboard) */
$slug = $activeSlug ?? ($company['slug'] ?? null);
$backUrl = $slug ? base_url('admin/' . rawurlencode($slug) . '/dashboard') : base_url('admin');
?>
<div class="max-w-5xl mx-auto p-4">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold">Pedidos</h1>
    <div class="flex items-center gap-2">
        <a href="<?= e(base_url('admin/' . rawurlencode($slug) . '/orders/create')) ?>"
         class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50">+ Novo pedido</a>
        <a href="<?= e($backUrl) ?>"
         class="px-3 py-2 rounded-xl border">← Voltar</a>
    </div>
  </div>

    <form class="mb-4 flex gap-2" method="get" action="<?= e(base_url('admin/' . rawurlencode($slug) . '/orders')) ?>">
    <select name="status" class="border px-3 py-2 rounded">
      <option value="">Todos</option>
      <?php foreach (['pending'=>'Pendente','paid'=>'Pago','completed'=>'Concluído','canceled'=>'Cancelado'] as $k=>$label): ?>
        <option value="<?= e($k) ?>" <?= ($status===$k?'selected':'') ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="px-4 py-2 border rounded">Filtrar</button>
  </form>

  <div class="overflow-x-auto">
    <table class="min-w-full border">
      <thead>
        <tr class="bg-gray-50">
          <th class="p-2 text-left">#</th>
          <th class="p-2 text-left">Cliente</th>
          <th class="p-2 text-left">Status</th>
          <th class="p-2 text-left">Total</th>
          <th class="p-2 text-left">Criado</th>
          <th class="p-2"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr class="border-t">
            <td class="p-2"><?= (int)$o['id'] ?></td>
            <td class="p-2"><?= e($o['customer_name'] ?? '-') ?></td>
            <td class="p-2"><?= e($o['status']) ?></td>
            <td class="p-2">R$ <?= number_format((float)$o['total'], 2, ',', '.') ?></td>
            <td class="p-2"><?= e($o['created_at']) ?></td>
            <td class="p-2">
              <a class="underline" href="<?= e(base_url('admin/' . rawurlencode($slug) . '/orders/show?id=' . (int)$o['id'])) ?>">ver</a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
          <tr><td class="p-4 opacity-70" colspan="6">Nenhum pedido encontrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
