<?php
if (!function_exists('e')) {
    function e($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('price_br')) {
    function price_br($v)
    {
        return 'R$ ' . number_format((float)$v, 2, ',', '.');
    }
}

$company         = is_array($company ?? null) ? $company : [];
$items           = is_array($items ?? null) ? $items : [];
$totals          = is_array($totals ?? null) ? $totals : [];
$address         = is_array($deliveryAddress ?? null) ? $deliveryAddress : [];
$cities          = is_array($cities ?? null) ? $cities : [];
$zonesByCity     = is_array($zonesByCity ?? null) ? $zonesByCity : [];
$paymentMethods  = is_array($paymentMethods ?? null) ? $paymentMethods : [];

$slug      = isset($slug) ? (string)$slug : (string)($company['slug'] ?? '');
$slugClean = trim($slug, '/');
$cartUrl   = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'cart') : '#';
$submitUrl = function_exists('base_url') ? base_url(($slugClean !== '' ? $slugClean . '/' : '') . 'checkout') : '#';

$subtotal      = (float)($totals['subtotal'] ?? 0.0);
$deliveryFee   = (float)($totals['delivery'] ?? 0.0);
$total         = (float)($totals['total'] ?? ($subtotal + $deliveryFee));
$selectedCityId    = isset($selectedCityId) ? (int)$selectedCityId : (int)($address['city_id'] ?? 0);
$selectedZoneId    = isset($selectedZoneId) ? (int)$selectedZoneId : (int)($address['zone_id'] ?? 0);
$selectedPaymentId = isset($selectedPaymentId) ? (int)$selectedPaymentId : (int)($address['payment_method_id'] ?? 0);

$zonesPresent = false;

foreach ($zonesByCity as $cityZones) {
    if (!empty($cityZones)) {
        $zonesPresent = true;
        break;
    }
}

$deliveryLabel = 'A calcular';

if ($selectedZoneId) {
    $deliveryLabel = $deliveryFee > 0 ? price_br($deliveryFee) : 'Grátis';
} elseif ($zonesPresent) {
    $deliveryLabel = 'Selecione';
} elseif (!$zonesPresent && $deliveryFee <= 0) {
    $deliveryLabel = 'Indisponível';
}

$selectedPayment = null;

foreach ($paymentMethods as $method) {
    if ((int)($method['id'] ?? 0) === $selectedPaymentId) {
        $selectedPayment = $method;
        break;
    }
}

if (!$selectedPayment && $paymentMethods) {
    $selectedPayment = $paymentMethods[0];
    $selectedPaymentId = (int)($selectedPayment['id'] ?? 0);
}
$paymentInstructions = (string)($selectedPayment['instructions'] ?? '');
$flash = is_array($flash ?? null) ? $flash : null;

$addressCityName = (string)($address['city'] ?? '');
$addressNeighborhoodName = (string)($address['neighborhood'] ?? '');
$addressState = (string)($address['state'] ?? '');

$citiesForJs = array_map(static function ($city) {
    return [
      'id'   => (int)($city['id'] ?? 0),
      'name' => (string)($city['name'] ?? ''),
    ];
}, $cities);

$zonesForJs = [];

foreach ($zonesByCity as $cityId => $zoneList) {
    $cityKey = (string)$cityId;
    $zonesForJs[$cityKey] = [];

    foreach ($zoneList as $zone) {
        $zonesForJs[$cityKey][] = [
          'id'        => (int)($zone['id'] ?? 0),
          'city_id'   => (int)($zone['city_id'] ?? 0),
          'name'      => (string)($zone['name'] ?? ''),
          'fee'       => (float)($zone['fee'] ?? 0),
          'city_name' => (string)($zone['city_name'] ?? ''),
        ];
    }
}

?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Checkout — <?= e($company['name'] ?? 'Cardápio') ?></title>
<style>
  :root{ --bg:#f3f4f6; --card:#ffffff; --border:#e5e7eb; --muted:#6b7280; --text:#0f172a; --accent:#f59e0b; --accent-active:#d97706; --accent-ink:#ffffff; }
  *{box-sizing:border-box;font-family:"Inter",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}
  body{margin:0;background:var(--bg);color:var(--text);}
  .app{width:100%;max-width:100%;margin:0 auto;min-height:100dvh;display:flex;flex-direction:column;padding-bottom:120px;background:var(--bg);}
  .topbar{position:sticky;top:0;background:var(--card);border-bottom:1px solid var(--border);z-index:10;}
  .topwrap{display:flex;align-items:center;gap:12px;padding:12px 16px;}
  .back{width:36px;height:36px;border-radius:999px;border:1px solid var(--border);display:grid;place-items:center;background:var(--card);cursor:pointer;}
  .back svg{width:18px;height:18px;}
  .title{font-weight:800;font-size:18px;}
  .content{flex:1;padding:16px;display:grid;gap:16px;}
  .card{background:var(--card);border-radius:18px;border:1px solid var(--border);padding:18px;display:grid;gap:14px;box-shadow:0 10px 30px -18px rgba(15,23,42,.35);}
  .card h2{margin:0;font-size:18px;font-weight:700;}
  label.field{display:grid;gap:6px;font-size:13px;color:#111827;}
  label.field span{font-weight:600;}
  .field input,.field select,.field textarea{width:100%;border:1px solid var(--border);border-radius:12px;padding:12px 14px;font-size:15px;background:#f9fafb;transition:border-color .2s ease,box-shadow .2s ease;}
  .field input:focus,.field select:focus,.field textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(245,158,11,.2);outline:none;background:#fff;}
  textarea{min-height:64px;resize:vertical;}
  .summary-items{display:grid;gap:10px;}
  .summary-item{display:flex;align-items:center;justify-content:space-between;font-size:14px;color:#1f2937;}
  .summary-item span:last-child{font-weight:600;}
  .summary-total{border-top:1px dashed var(--border);padding-top:12px;display:flex;flex-direction:column;gap:8px;}
  .summary-total .row{display:flex;justify-content:space-between;font-weight:700;font-size:15px;}
  .summary-total .grand{font-size:18px;}
  .badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:12px;background:#fef3c7;color:#92400e;font-size:12px;font-weight:600;align-self:flex-start;}
  .methods{display:flex;gap:10px;flex-wrap:wrap;}
  .method-btn{flex:1 1 140px;border:1px solid var(--border);border-radius:12px;padding:12px;font-size:14px;font-weight:600;background:#f9fafb;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;text-align:center;}
  .method-btn.active{border-color:#1d4ed8;background:#e0e7ff;color:#1d4ed8;}
  .payment-note{font-size:13px;color:#334155;background:#f8fafc;border:1px solid var(--border);border-radius:12px;padding:12px;line-height:1.45;}
  .payment-note.hidden{display:none;}
  .checkout-footer{position:fixed;left:50%;transform:translateX(-50%);bottom:0;width:100%;max-width:100%;background:var(--card);border-top:1px solid var(--border);padding:12px 16px 18px;box-shadow:0 -10px 30px -18px rgba(15,23,42,.35);}
  @media (min-width:768px){
    .app{max-width:420px}
    .checkout-footer{max-width:420px}
  }
  .cta{display:flex;align-items:center;justify-content:center;width:100%;min-height:56px;border:none;border-radius:18px;padding:0 24px;background:var(--accent);color:var(--accent-ink);font-weight:800;font-size:16px;text-decoration:none;text-align:center;cursor:pointer;}
  .cta:active{background:var(--accent-active);}
  .cta[disabled]{opacity:.6;cursor:not-allowed;}
  .note-muted{font-size:12px;color:var(--muted);}
</style>
</head>
<body>
<div class="app">
  <div class="topbar">
    <div class="topwrap">
  <button class="back" type="button" data-action="navigate" data-href="<?= e($cartUrl) ?>" aria-label="Voltar para a sacola">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" transform="scale(0.7) translate(5 5)"/></svg>
      </button>
      <div class="title">Checkout</div>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="mx-4 mt-4 rounded-xl border <?= ($flash['type'] ?? '') === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' ?> px-4 py-3 text-sm">
      <?= e($flash['message'] ?? '') ?>
    </div>
  <?php endif; ?>

  <form id="checkout-form" class="content" method="post" action="<?= e($submitUrl) ?>">
    <?php if (function_exists('csrf_field')): ?>
      <?= csrf_field() ?>
    <?php elseif (function_exists('csrf_token')): ?>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php endif; ?>

    <section class="card">
      <div class="flex items-center justify-between">
        <h2>Endereço de entrega</h2>
        <span class="badge">Entrega padrão</span>
      </div>
      <label class="field">
        <span>Nome do destinatário</span>
        <input type="text" name="address[name]" placeholder="Quem vai receber" value="<?= e($address['name'] ?? '') ?>" required>
      </label>
      <label class="field">
        <span>Telefone / WhatsApp</span>
        <input type="tel" name="address[phone]" placeholder="(11) 99999-0000" value="<?= e($address['phone'] ?? '') ?>" required>
      </label>
      <label class="field">
        <span>Cidade atendida</span>
        <select id="checkout-city" name="address[city_id]">
          <option value="">Selecione a cidade</option>
          <?php foreach ($cities as $city): $cityId = (int)($city['id'] ?? 0); ?>
            <option value="<?= $cityId ?>"<?= $cityId === $selectedCityId ? ' selected' : '' ?>><?= e($city['name'] ?? '') ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php $initialZones = ($selectedCityId && isset($zonesByCity[$selectedCityId])) ? $zonesByCity[$selectedCityId] : []; ?>
      <label class="field">
        <span>Bairro</span>
        <select id="checkout-zone" name="address[zone_id]"<?= $selectedCityId ? '' : ' disabled' ?>>
          <option value=""><?= $selectedCityId ? 'Selecione o bairro' : 'Escolha a cidade primeiro' ?></option>
          <?php foreach ($initialZones as $zone): $zoneId = (int)($zone['id'] ?? 0); ?>
            <option value="<?= $zoneId ?>" data-fee="<?= e(number_format((float)($zone['fee'] ?? 0), 2, '.', '')) ?>" data-city-name="<?= e($zone['city_name'] ?? '') ?>" data-zone-name="<?= e($zone['name'] ?? '') ?>"<?= $zoneId === $selectedZoneId ? ' selected' : '' ?>>
              <?= e($zone['name'] ?? '') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span>Rua / Avenida</span>
        <input type="text" name="address[street]" placeholder="Nome da rua" value="<?= e($address['street'] ?? '') ?>" required>
      </label>
      <label class="field">
        <span>Número</span>
        <input type="text" name="address[number]" placeholder="123" value="<?= e($address['number'] ?? '') ?>" required>
      </label>
      <label class="field">
        <span>Complemento</span>
        <input type="text" name="address[complement]" placeholder="Apto, bloco, casa" value="<?= e($address['complement'] ?? '') ?>">
      </label>
      <label class="field">
        <span>Estado (UF)</span>
        <input type="text" name="address[state]" placeholder="SP" maxlength="2" value="<?= e($addressState) ?>">
      </label>
      <label class="field">
        <span>Ponto de referência</span>
        <textarea name="address[reference]" placeholder="Ajude o entregador a encontrar mais rápido"><?= e($address['reference'] ?? '') ?></textarea>
      </label>

      <input type="hidden" name="address[city]" id="address-city-name" value="<?= e($addressCityName) ?>">
      <input type="hidden" name="address[neighborhood]" id="address-zone-name" value="<?= e($addressNeighborhoodName) ?>">
      <input type="hidden" name="order[delivery_fee]" id="delivery-fee-input" value="<?= e(number_format($deliveryFee, 2, '.', '')) ?>">
    </section>

    <section class="card" id="checkout-summary" data-subtotal="<?= e(number_format($subtotal, 2, '.', '')) ?>">
      <h2>Resumo do pedido</h2>
      <div class="summary-items">
        <?php foreach ($items as $item): ?>
          <div class="summary-item">
            <span><?= e($item['qty'] ?? 1) ?>x <?= e($item['product']['name'] ?? 'Produto') ?></span>
            <span><?= price_br($item['line_total'] ?? 0) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="summary-total">
        <div class="row"><span>Subtotal</span><span><?= price_br($subtotal) ?></span></div>
        <div class="row"><span>Entrega</span><span id="delivery-amount"><?= e($deliveryLabel) ?></span></div>
        <div class="row grand"><span>Total</span><span id="total-amount"><?= price_br($total) ?></span></div>
      </div>
      <p class="note-muted">O valor de entrega será atualizado automaticamente após escolher o bairro.</p>
    </section>

    <section class="card">
      <h2>Pagamento</h2>
      <?php if ($paymentMethods): ?>
        <div class="methods">
          <?php foreach ($paymentMethods as $method):
              $methodId = (int)($method['id'] ?? 0);
              $isActive = $methodId === $selectedPaymentId;
              ?>
            <button type="button" class="method-btn<?= $isActive ? ' active' : '' ?>" data-id="<?= $methodId ?>" data-instructions="<?= e($method['instructions'] ?? '') ?>">
              <?= e($method['name'] ?? 'Pagamento') ?>
            </button>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="payment[method_id]" id="payment-method-id" value="<?= $selectedPaymentId ?>">
        <div id="payment-instructions" class="payment-note<?= $paymentInstructions ? '' : ' hidden' ?>">
          <?= $paymentInstructions ? nl2br(e($paymentInstructions)) : '' ?>
        </div>
      <?php else: ?>
        <div class="payment-note">Nenhum método de pagamento cadastrado. Entre em contato com a loja para mais informações.</div>
        <input type="hidden" name="payment[method_id]" id="payment-method-id" value="0">
        <div id="payment-instructions" class="payment-note hidden"></div>
      <?php endif; ?>
      <label class="field">
        <span>Observações para o pedido</span>
        <textarea name="order[notes]" placeholder="Troco, detalhes do pagamento, etc."><?= e($address['notes'] ?? '') ?></textarea>
      </label>
    </section>
  </form>
</div>

<div class="checkout-footer">
  <button class="cta" type="submit" form="checkout-form">Confirmar pedido</button>
</div>

<script>
(() => {
  const data = {
    subtotal: parseFloat('<?= e(number_format($subtotal, 2, '.', '')) ?>') || 0,
    cities: <?= json_encode($citiesForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    zonesByCity: <?= json_encode($zonesForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    selectedCityId: <?= (int)$selectedCityId ?>,
    selectedZoneId: <?= (int)$selectedZoneId ?>,
    zonesPresent: <?= $zonesPresent ? 'true' : 'false' ?>
  };

  const citySelect = document.getElementById('checkout-city');
  const zoneSelect = document.getElementById('checkout-zone');
  const cityNameInput = document.getElementById('address-city-name');
  const zoneNameInput = document.getElementById('address-zone-name');
  const deliveryInput = document.getElementById('delivery-fee-input');
  const deliveryAmount = document.getElementById('delivery-amount');
  const totalAmount = document.getElementById('total-amount');
  const paymentButtons = document.querySelectorAll('.method-btn');
  const paymentInput = document.getElementById('payment-method-id');
  const paymentBox = document.getElementById('payment-instructions');

  const formatBRL = (value) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);

  const syncCityName = () => {
    if (!citySelect) return;
    const opt = citySelect.options[citySelect.selectedIndex];
    cityNameInput.value = opt ? opt.textContent : '';
  };

  const syncZoneName = () => {
    if (!zoneSelect) return;
    const opt = zoneSelect.options[zoneSelect.selectedIndex];
    zoneNameInput.value = opt ? opt.textContent : '';
  };

  const updateSummary = (fee, zoneSelected) => {
    if (typeof fee !== 'number' || Number.isNaN(fee)) fee = 0;
    if (zoneSelected) {
      deliveryInput.value = fee.toFixed(2);
      deliveryAmount.textContent = fee > 0 ? formatBRL(fee) : 'Grátis';
    } else {
      deliveryInput.value = '';
      deliveryAmount.textContent = data.zonesPresent ? 'Selecione' : 'A calcular';
    }
    const finalTotal = data.subtotal + (zoneSelected ? fee : 0);
    totalAmount.textContent = formatBRL(finalTotal);
  };

  const populateZones = (cityId) => {
    if (!zoneSelect) return [];
    const key = String(cityId);
    const zones = data.zonesByCity[key] || [];
    zoneSelect.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = cityId ? 'Selecione o bairro' : 'Escolha a cidade primeiro';
    zoneSelect.appendChild(placeholder);
    zones.forEach(zone => {
      const option = document.createElement('option');
      option.value = zone.id;
      option.textContent = zone.name;
      option.dataset.fee = zone.fee;
      option.dataset.cityName = zone.city_name || '';
      option.dataset.zoneName = zone.name;
      zoneSelect.appendChild(option);
    });
    zoneSelect.disabled = !cityId || zones.length === 0;
    return zones;
  };

  if (citySelect) {
    citySelect.addEventListener('change', () => {
      const cityId = parseInt(citySelect.value, 10) || 0;
      syncCityName();
      const zones = populateZones(cityId);
      if (zones.length === 1) {
        zoneSelect.value = String(zones[0].id);
      } else {
        zoneSelect.value = '';
      }
      syncZoneName();
      const opt = zoneSelect.options[zoneSelect.selectedIndex];
      const fee = opt && opt.dataset.fee ? parseFloat(opt.dataset.fee) : NaN;
      updateSummary(fee, !!opt && zoneSelect.value !== '');
    });
  }

  if (zoneSelect) {
    zoneSelect.addEventListener('change', () => {
      const opt = zoneSelect.options[zoneSelect.selectedIndex];
      syncZoneName();
      const fee = opt && opt.dataset.fee ? parseFloat(opt.dataset.fee) : NaN;
      updateSummary(fee, !!opt && zoneSelect.value !== '');
    });
  }

  if (citySelect) {
    if (!citySelect.value && data.cities.length === 1) {
      citySelect.value = String(data.cities[0].id);
    }
    syncCityName();
    const zones = populateZones(parseInt(citySelect.value || '0', 10));
    if (!zoneSelect.value && data.selectedZoneId) {
      zoneSelect.value = String(data.selectedZoneId);
    } else if (!zoneSelect.value && zones.length === 1) {
      zoneSelect.value = String(zones[0].id);
    }
    syncZoneName();
    const opt = zoneSelect.options[zoneSelect.selectedIndex];
    const fee = opt && opt.dataset.fee ? parseFloat(opt.dataset.fee) : NaN;
    updateSummary(fee, !!opt && zoneSelect.value !== '');
  }

  if (paymentButtons.length > 0) {
    paymentButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const id = parseInt(btn.dataset.id || '0', 10) || 0;
        if (!id) return;
        paymentButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        if (paymentInput) paymentInput.value = id;
        const instructions = btn.dataset.instructions || '';
        if (paymentBox) {
          if (instructions) {
            paymentBox.innerHTML = instructions.replace(/\n/g, '<br>');
            paymentBox.classList.remove('hidden');
          } else {
            paymentBox.innerHTML = '';
            paymentBox.classList.add('hidden');
          }
        }
      });
    });

    if (paymentInput && paymentInput.value) {
      const active = Array.from(paymentButtons).find(btn => parseInt(btn.dataset.id || '0', 10) === parseInt(paymentInput.value, 10));
      if (active) {
        active.classList.add('active');
        const instructions = active.dataset.instructions || '';
        if (paymentBox) {
          if (instructions) {
            paymentBox.innerHTML = instructions.replace(/\n/g, '<br>');
            paymentBox.classList.remove('hidden');
          } else {
            paymentBox.innerHTML = '';
            paymentBox.classList.add('hidden');
          }
        }
      }
    }
  }
})();
</script>
</body>
</html>
