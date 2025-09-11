<?php
$title = "Novo pedido";
ob_start();
$slug = $activeSlug ?? ($company['slug'] ?? null);
?>
<div class="max-w-3xl mx-auto p-4">
  <h1 class="text-2xl font-semibold mb-4">Novo pedido</h1>

  <form method="post" action="<?= e(base_url('admin/' . rawurlencode($slug) . '/orders')) ?>" id="order-form" class="space-y-6">
    <!-- Dados do cliente -->
    <div class="rounded-2xl border bg-white p-4">
      <h2 class="font-semibold mb-3">Cliente</h2>
      <div class="grid md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm mb-1">Nome</label>
          <input type="text" name="customer_name" value="<?= e($defaults['customer_name'] ?? '') ?>" class="w-full border rounded-xl px-3 py-2" required>
        </div>
        <div>
          <label class="block text-sm mb-1">Telefone (WhatsApp)</label>
          <input type="text" name="customer_phone" value="<?= e($defaults['customer_phone'] ?? '') ?>" class="w-full border rounded-xl px-3 py-2">
        </div>
      </div>
    </div>

    <!-- Itens -->
    <div class="rounded-2xl border bg-white p-4">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold">Itens do pedido</h2>
        <button type="button" id="btn-add-item" class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50">+ Adicionar item</button>
      </div>

      <div id="items" class="space-y-2">
        <!-- linhas aparecem aqui via JS -->
      </div>

      <template id="tpl-row">
        <div class="flex items-center gap-2">
          <select name="product_id[]" class="border rounded-xl px-3 py-2 flex-1 product-select">
            <option value="">Selecione um produto...</option>
            <?php foreach ($products as $pr): ?>
              <option value="<?= (int)$pr['id'] ?>" data-price="<?= e($pr['promo_price'] ?: $pr['price']) ?>">
                <?= e($pr['name']) ?> — R$ <?= number_format($pr['promo_price'] ?: $pr['price'], 2, ',', '.') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="number" min="1" step="1" name="quantity[]" value="1" class="w-24 border rounded-xl px-3 py-2 qty-input">
          <input type="text" class="w-36 border rounded-xl px-3 py-2 text-right price-show" value="R$ 0,00" disabled>
          <button type="button" class="px-3 py-2 rounded-xl border bg-white hover:bg-slate-50 btn-del">Remover</button>
        </div>
      </template>
    </div>

    <!-- Totais -->
    <div class="rounded-2xl border bg-white p-4">
      <h2 class="font-semibold mb-3">Totais</h2>
      <div class="grid md:grid-cols-3 gap-3 items-end">
        <div>
          <label class="block text-sm mb-1">Taxa de entrega</label>
          <input type="number" step="0.01" name="delivery_fee" value="<?= e($defaults['delivery_fee'] ?? 0) ?>" class="w-full border rounded-xl px-3 py-2">
        </div>
        <div>
          <label class="block text-sm mb-1">Desconto</label>
          <input type="number" step="0.01" name="discount" value="<?= e($defaults['discount'] ?? 0) ?>" class="w-full border rounded-xl px-3 py-2">
        </div>
        <div class="text-right">
          <div class="text-sm text-gray-500">Subtotal</div>
          <div id="subtot-view" class="text-xl font-semibold">R$ 0,00</div>
        </div>
      </div>
    </div>

    <!-- Observações -->
    <div class="rounded-2xl border bg-white p-4">
      <label class="block text-sm mb-1">Observações</label>
      <textarea name="notes" rows="3" class="w-full border rounded-xl px-3 py-2"><?= e($defaults['notes'] ?? '') ?></textarea>
    </div>

    <div class="flex gap-2">
      <button class="px-4 py-2 rounded-xl border bg-white hover:bg-slate-50">Salvar pedido</button>
      <a href="<?= e(base_url('admin/' . rawurlencode($slug) . '/orders')) ?>" class="px-4 py-2 rounded-xl border">Cancelar</a>
    </div>
  </form>
</div>

<script>
(function(){
  const itemsBox = document.getElementById('items');
  const tpl = document.getElementById('tpl-row').content;

  function addRow(){
    const node = document.importNode(tpl, true);
    const row  = node.querySelector('div');
    row.querySelector('.btn-del').addEventListener('click', ()=> {
      row.remove();
      recalc();
    });
    const select = row.querySelector('.product-select');
    const qty    = row.querySelector('.qty-input');
    const show   = row.querySelector('.price-show');

    function updateLine(){
      const opt = select.options[select.selectedIndex];
      const price = parseFloat(opt?.dataset?.price || '0');
      const q = parseInt(qty.value || '0', 10);
      const total = price * q;
      show.value = formatBR(total);
      recalc();
    }
    select.addEventListener('change', updateLine);
    qty.addEventListener('input', updateLine);

    itemsBox.appendChild(row);
  }

  function formatBR(v){
    return 'R$ ' + (v || 0).toFixed(2).replace('.', ',');
  }

  function parseBR(str){
    return parseFloat(String(str).replace(/[^\d,.-]/g,'').replace(',','.')) || 0;
  }

  function recalc(){
    let subtotal = 0;
    itemsBox.querySelectorAll('.product-select').forEach((sel, i) => {
      const opt = sel.options[sel.selectedIndex];
      const price = parseFloat(opt?.dataset?.price || '0');
      const qty = parseInt(itemsBox.querySelectorAll('.qty-input')[i].value || '0', 10);
      subtotal += price * qty;
    });
    document.getElementById('subtot-view').textContent = formatBR(subtotal);
  }

  document.getElementById('btn-add-item').addEventListener('click', addRow);
  // primeira linha pronta pra começar
  addRow();
})();
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>

