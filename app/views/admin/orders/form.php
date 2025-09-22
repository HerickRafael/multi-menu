<?php
// admin/orders/create.php — Novo pedido (versão moderna)

$title = "Novo pedido";
$slug  = rawurlencode((string)($activeSlug ?? ($company['slug'] ?? '')));

// helper de escape (se ainda não existir)
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

ob_start(); ?>
<div class="mx-auto max-w-4xl p-4">

  <!-- HEADER -->
  <header class="mb-5 flex items-center gap-3">
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-emerald-500 text-white shadow">
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
        <path d="M5 7h14M7 12h10M9 17h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </span>
    <h1 class="bg-gradient-to-r from-slate-900 to-slate-600 bg-clip-text text-2xl font-semibold text-transparent">
      Novo pedido
    </h1>

    <div class="ml-auto">
      <a href="<?= e(base_url('admin/' . $slug . '/orders')) ?>"
         class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M15 6 9 12l6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Voltar
      </a>
    </div>
  </header>

  <form method="post"
        action="<?= e(base_url('admin/' . $slug . '/orders')) ?>"
        id="order-form"
        class="grid gap-6">

    <?php if (function_exists('csrf_field')): ?>
      <?= csrf_field() ?>
    <?php elseif (function_exists('csrf_token')): ?>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php endif; ?>

    <!-- CARD: Cliente -->
    <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
      <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm6 7a6 6 0 0 0-12 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Cliente
      </legend>

      <div class="grid gap-3 md:grid-cols-2">
        <label class="grid gap-1">
          <span class="text-sm text-slate-700">Nome</span>
          <input type="text" name="customer_name" value="<?= e($defaults['customer_name'] ?? '') ?>"
                 class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400" required>
        </label>
        <label class="grid gap-1">
          <span class="text-sm text-slate-700">Telefone (WhatsApp)</span>
          <input type="text" name="customer_phone" value="<?= e($defaults['customer_phone'] ?? '') ?>"
                 class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400" inputmode="numeric" placeholder="(51) 92001-7687">
        </label>
      </div>
    </fieldset>

    <!-- CARD: Itens -->
    <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
      <legend class="mb-3 flex items-center justify-between">
        <span class="inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M6 12h12M6 17h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
          Itens do pedido
        </span>
        <button type="button" id="btn-add-item"
                class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
          + Adicionar item
        </button>
      </legend>

      <div id="items" class="space-y-2"><!-- linhas via JS --></div>

      <!-- Template de linha -->
      <template id="tpl-row">
        <div class="grid items-center gap-2 md:grid-cols-[minmax(0,1fr)_110px_140px_auto]">
          <select name="product_id[]" class="product-select rounded-xl border border-slate-300 bg-white px-3 py-2">
            <option value="">Selecione um produto…</option>
            <?php foreach ($products as $pr):
              $pp = (float)($pr['promo_price'] ?: $pr['price']); ?>
              <option value="<?= (int)$pr['id'] ?>" data-price="<?= e((string)$pp) ?>">
                <?= e($pr['name']) ?> — R$ <?= number_format($pp, 2, ',', '.') ?>
              </option>
            <?php endforeach; ?>
          </select>

          <input type="number" min="1" step="1" name="quantity[]" value="1"
                 class="qty-input w-full rounded-xl border border-slate-300 bg-white px-3 py-2">

          <input type="text" class="price-show w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-right font-medium text-slate-700" value="R$ 0,00" disabled>

          <div class="flex justify-end">
            <button type="button" class="btn-del inline-flex items-center gap-2 rounded-xl border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 7h12M9 7v11m6-11v11M8 7l1-2h6l1 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
              Remover
            </button>
          </div>
        </div>
      </template>
    </fieldset>

    <!-- CARD: Totais -->
    <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
      <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M6 12h12M12 6v12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Totais
      </legend>

      <div class="grid items-end gap-3 md:grid-cols-4">
        <label class="grid gap-1">
          <span class="text-sm text-slate-700">Taxa de entrega</span>
          <input type="number" step="0.01" name="delivery_fee" value="<?= e($defaults['delivery_fee'] ?? 0) ?>"
                 class="fee-input rounded-xl border border-slate-300 bg-white px-3 py-2">
        </label>

        <label class="grid gap-1">
          <span class="text-sm text-slate-700">Desconto</span>
          <input type="number" step="0.01" name="discount" value="<?= e($defaults['discount'] ?? 0) ?>"
                 class="disc-input rounded-xl border border-slate-300 bg-white px-3 py-2">
        </label>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
          <div class="text-xs text-slate-500">Subtotal</div>
          <div id="subtot-view" class="text-lg font-semibold text-slate-800">R$ 0,00</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
          <div class="text-xs text-slate-500">Total</div>
          <div id="total-view" class="text-xl font-semibold text-slate-900">R$ 0,00</div>
        </div>
      </div>
    </fieldset>

    <!-- CARD: Observações -->
    <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
      <legend class="mb-3 inline-flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-700">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><path d="M5 7h14M5 12h10M5 17h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Observações
      </legend>
      <textarea name="notes" rows="3"
                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:ring-2 focus:ring-indigo-400"
                placeholder="Ex.: Sem cebola, entregar no portão…"><?= e($defaults['notes'] ?? '') ?></textarea>
    </fieldset>

    <!-- AÇÕES -->
    <div class="flex gap-2">
      <button class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-emerald-600 px-4 py-2 text-sm font-medium text-white shadow hover:opacity-95">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M20 7 9 18l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Salvar pedido
      </button>
      <a href="<?= e(base_url('admin/' . $slug . '/orders')) ?>"
         class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"><path d="M15 6 9 12l6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Cancelar
      </a>
    </div>
  </form>
</div>

<script>
(function(){
  const itemsBox = document.getElementById('items');
  const tpl = document.getElementById('tpl-row').content;
  const form = document.getElementById('order-form');

  function formatBR(v){ return 'R$ ' + (Number(v)||0).toFixed(2).replace('.', ','); }
  function getNumber(input){ const n = parseFloat(input?.value?.replace(',', '.') || '0'); return isFinite(n) ? n : 0; }

  function addRow(){
    const node = document.importNode(tpl, true);
    const row  = node.querySelector('div');

    const select = row.querySelector('.product-select');
    const qty    = row.querySelector('.qty-input');
    const show   = row.querySelector('.price-show');
    const btnDel = row.querySelector('.btn-del');

    function updateLine(){
      const opt = select.options[select.selectedIndex];
      const price = parseFloat(opt?.dataset?.price || '0');
      const q = Math.max(1, parseInt(qty.value || '1', 10));
      qty.value = q;
      show.value = formatBR(price * q);
      recalc();
    }

    select.addEventListener('change', updateLine);
    qty.addEventListener('input', updateLine);
    btnDel.addEventListener('click', ()=>{ row.remove(); recalc(); });

    itemsBox.appendChild(row);
    updateLine(); // inicia calculado
  }

  function recalc(){
    let subtotal = 0;
    itemsBox.querySelectorAll('.product-select').forEach((sel, i) => {
      const opt = sel.options[sel.selectedIndex];
      const price = parseFloat(opt?.dataset?.price || '0');
      const qtyInput = itemsBox.querySelectorAll('.qty-input')[i];
      const q = Math.max(0, parseInt(qtyInput.value || '0', 10));
      subtotal += price * q;
    });

    const fee  = getNumber(document.querySelector('.fee-input'));
    const disc = getNumber(document.querySelector('.disc-input'));
    const total = Math.max(0, subtotal + fee - disc);

    document.getElementById('subtot-view').textContent = formatBR(subtotal);
    document.getElementById('total-view').textContent  = formatBR(total);
  }

  // Adicionar linha
  document.getElementById('btn-add-item').addEventListener('click', addRow);

  // Recalcular quando taxa/desconto mudarem
  document.querySelectorAll('.fee-input, .disc-input').forEach(inp=>{
    inp.addEventListener('input', recalc);
  });

  // Validação simples antes de enviar
  form.addEventListener('submit', (e)=>{
    const hasItem = Array.from(itemsBox.querySelectorAll('.product-select'))
      .some(sel => sel.value && sel.value !== '');
    if (!hasItem) {
      e.preventDefault();
      alert('Adicione pelo menos 1 item ao pedido.');
      return false;
    }
  });

  // Primeira linha pronta
  addRow();
})();
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
