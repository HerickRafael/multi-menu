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

// Auto-select PIX if no method is pre-selected or if PIX is available
if (!$selectedPayment && $paymentMethods) {
    // First try to find PIX method
    $pixMethod = null;
    foreach ($paymentMethods as $method) {
        if (($method['type'] ?? '') === 'pix') {
            $pixMethod = $method;
            break;
        }
    }
    
    if ($pixMethod) {
        $selectedPayment = $pixMethod;
        $selectedPaymentId = (int)($selectedPayment['id'] ?? 0);
    } else {
        // Fallback to first available method
        $selectedPayment = $paymentMethods[0];
        $selectedPaymentId = (int)($selectedPayment['id'] ?? 0);
    }
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
  .payment-methods{display:grid;gap:12px;}
  .payment-type-btn{border:1px solid var(--border);border-radius:12px;padding:16px;font-size:16px;font-weight:600;background:#f9fafb;cursor:pointer;display:flex;align-items:center;justify-content:space-between;text-align:left;transition:all 0.2s ease;}
  .payment-type-btn:hover{background:#f1f5f9;}
  .payment-type-btn.active{border-color:var(--accent);background:#fef3c7;}
  .payment-type-btn .payment-info{display:flex;align-items:center;gap:12px;}
  .payment-type-btn .payment-icon{width:24px;height:24px;flex-shrink:0;object-fit:contain;}
  .payment-type-btn .payment-text{display:flex;flex-direction:column;gap:2px;}
  .payment-type-btn .payment-title{font-size:16px;font-weight:600;}
  .payment-type-btn .payment-subtitle{font-size:13px;color:#64748b;font-weight:400;}
  .payment-type-btn .arrow{width:20px;height:20px;opacity:0.5;transition:transform 0.2s ease;}
  .payment-type-btn.active .arrow{transform:rotate(90deg);opacity:1;}
  .card-brands{margin-top:12px;display:none;grid-template-columns:repeat(auto-fit,minmax(80px,1fr));gap:8px;padding:12px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;}
  .card-brands.show{display:grid;}
  .brand-btn{border:1px solid var(--border);border-radius:8px;padding:12px;background:white;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:6px;transition:all 0.2s ease;}
  .brand-btn:hover{background:#f1f5f9;border-color:#94a3b8;}
  .brand-btn.active{border-color:var(--accent);background:#fef3c7;box-shadow:0 0 0 2px rgba(245,158,11,.2);}
  .brand-btn img{width:32px;height:20px;object-fit:contain;}
  .brand-btn span{font-size:11px;font-weight:500;color:#64748b;}
  .payment-note{font-size:13px;color:#334155;background:#f8fafc;border:1px solid var(--border);border-radius:12px;padding:12px;line-height:1.45;}
  .payment-note.hidden{display:none;}
  .pix-key-section{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;}
  .pix-key-value{flex:1;font-family:monospace;font-size:13px;font-weight:600;color:#334155;word-break:break-all;}
  .copy-btn{background:var(--accent);color:white;border:none;border-radius:6px;padding:4px 8px;font-size:11px;font-weight:600;cursor:pointer;transition:background 0.2s ease;min-width:50px;}
  .copy-btn:hover{background:var(--accent-active);}
  .copy-btn:active{background:#b45309;}
  .copy-btn.copied{background:#059669;}
  .pix-detail{margin-top:6px;font-size:13px;color:#6b7280;}
  .pix-detail strong{color:#334155;}
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
  <a class="back" href="<?= e($cartUrl) ?>" data-action="navigate" aria-label="Voltar para a sacola">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" transform="scale(0.7) translate(5 5)"/></svg>
      </a>
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
        <div class="payment-methods">
          <?php 
          $pixMethods = [];
          $creditMethods = [];
          $debitMethods = [];
          $voucherMethods = [];
          $otherMethods = [];
          
          foreach ($paymentMethods as $method) {
            $type = $method['type'] ?? 'others';
            if ($type === 'pix') {
              $pixMethods[] = $method;
            } elseif ($type === 'credit') {
              $creditMethods[] = $method;
            } elseif ($type === 'debit') {
              $debitMethods[] = $method;
            } elseif ($type === 'voucher') {
              $voucherMethods[] = $method;
            } else {
              $otherMethods[] = $method;
            }
          }
          ?>
          
          <?php if ($pixMethods): ?>
            <!-- PIX Payment Option -->
            <div class="payment-type-btn<?= ($selectedPayment && ($selectedPayment['type'] ?? '') === 'pix') ? ' active' : '' ?>" data-type="pix" onclick="selectPaymentType('pix')">
              <div class="payment-info">
                <img src="<?= function_exists('base_url') ? base_url('assets/card-brands/pix.svg') : '/assets/card-brands/pix.svg' ?>" alt="PIX" class="payment-icon">
                <div class="payment-text">
                  <div class="payment-title">PIX</div>
                  <div class="payment-subtitle">Aprovação instantânea</div>
                </div>
              </div>
            </div>
            
            <!-- Payment Instructions Block -->
            <div id="payment-instructions" class="payment-note<?= $paymentInstructions ? '' : ' hidden' ?>">
              <?= $paymentInstructions ? nl2br(e($paymentInstructions)) : '' ?>
            </div>
          <?php endif; ?>
          
          <?php if ($creditMethods): ?>
            <!-- Credit Card Payment Option -->
            <div class="payment-type-btn" data-type="credit" onclick="selectPaymentType('credit')">
              <div class="payment-info">
                <img src="<?= function_exists('base_url') ? base_url('assets/card-brands/credit.svg') : '/assets/card-brands/credit.svg' ?>" alt="Cartão de Crédito" class="payment-icon">
                <div class="payment-text">
                  <div class="payment-title">Cartão de crédito</div>
                  <div class="payment-subtitle">
                    <?php 
                    $creditNames = array_map(function($method) { return $method['name'] ?? 'Cartão'; }, $creditMethods);
                    echo e(implode(', ', array_slice($creditNames, 0, 3)) . (count($creditNames) > 3 ? ' e mais' : ''));
                    ?>
                  </div>
                </div>
              </div>
              <svg class="arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            
            <!-- Credit Card Brands Selection -->
            <div class="card-brands" id="credit-brands">
              <?php foreach ($creditMethods as $creditMethod): ?>
                <?php
                $methodId = (int)($creditMethod['id'] ?? 0);
                $methodName = $creditMethod['name'] ?? 'Cartão';
                $metaArr = [];
                if (!empty($creditMethod['meta'])) {
                  $metaArr = is_string($creditMethod['meta']) ? json_decode($creditMethod['meta'], true) : (is_array($creditMethod['meta']) ? $creditMethod['meta'] : []);
                }
                $iconUrl = $metaArr['icon'] ?? '';
                
                // Se não tiver ícone personalizado, tentar mapear baseado no nome
                if (empty($iconUrl)) {
                  $nameLower = strtolower($methodName);
                  $brandMapping = [
                    'visa' => 'visa.svg',
                    'mastercard' => 'mastercard.svg', 
                    'master' => 'mastercard.svg',
                    'elo' => 'elo.svg',
                    'hipercard' => 'hipercard.svg',
                    'hiper' => 'hipercard.svg',
                    'diners' => 'diners.svg',
                    'american express' => 'others.svg',
                    'amex' => 'others.svg'
                  ];
                  
                  $detectedBrand = 'credit.svg';
                  foreach ($brandMapping as $keyword => $brandFile) {
                    if (strpos($nameLower, $keyword) !== false) {
                      $detectedBrand = $brandFile;
                      break;
                    }
                  }
                  $iconUrl = 'assets/card-brands/' . $detectedBrand;
                }
                
                // Converter path relativo para URL completa se necessário
                if (!preg_match('/^https?:\/\//i', $iconUrl) && !str_starts_with($iconUrl, '/')) {
                  $iconUrl = (function_exists('base_url') ? base_url($iconUrl) : '/' . $iconUrl);
                } elseif (str_starts_with($iconUrl, '/assets/')) {
                  $iconUrl = (function_exists('base_url') ? base_url(ltrim($iconUrl, '/')) : $iconUrl);
                }
                ?>
                <div class="brand-btn" data-brand="<?= e(strtolower(str_replace(' ', '', $methodName))) ?>" data-method-id="<?= $methodId ?>" onclick="selectCardBrand('credit', '<?= e(strtolower(str_replace(' ', '', $methodName))) ?>', <?= $methodId ?>)">
                  <img src="<?= e($iconUrl) ?>" alt="<?= e($methodName) ?>" onerror="this.src='<?= function_exists('base_url') ? base_url('assets/card-brands/credit.svg') : '/assets/card-brands/credit.svg' ?>'">
                  <span><?= e($methodName) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          
          <?php if ($debitMethods): ?>
            <!-- Debit Card Payment Option -->
            <div class="payment-type-btn" data-type="debit" onclick="selectPaymentType('debit')">
              <div class="payment-info">
                <img src="<?= function_exists('base_url') ? base_url('assets/card-brands/debit.svg') : '/assets/card-brands/debit.svg' ?>" alt="Cartão de Débito" class="payment-icon">
                <div class="payment-text">
                  <div class="payment-title">Cartão de débito</div>
                  <div class="payment-subtitle">
                    <?php 
                    $debitNames = array_map(function($method) { return $method['name'] ?? 'Débito'; }, $debitMethods);
                    echo e(implode(', ', array_slice($debitNames, 0, 3)) . (count($debitNames) > 3 ? ' e mais' : ''));
                    ?>
                  </div>
                </div>
              </div>
              <svg class="arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            
            <!-- Debit Card Brands Selection -->
            <div class="card-brands" id="debit-brands">
              <?php foreach ($debitMethods as $debitMethod): ?>
                <?php
                $methodId = (int)($debitMethod['id'] ?? 0);
                $methodName = $debitMethod['name'] ?? 'Débito';
                $metaArr = [];
                if (!empty($debitMethod['meta'])) {
                  $metaArr = is_string($debitMethod['meta']) ? json_decode($debitMethod['meta'], true) : (is_array($debitMethod['meta']) ? $debitMethod['meta'] : []);
                }
                $iconUrl = $metaArr['icon'] ?? '';
                
                // Se não tiver ícone personalizado, tentar mapear baseado no nome
                if (empty($iconUrl)) {
                  $nameLower = strtolower($methodName);
                  $brandMapping = [
                    'visa' => 'visa.svg',
                    'mastercard' => 'mastercard.svg', 
                    'master' => 'mastercard.svg',
                    'elo' => 'elo.svg',
                    'hipercard' => 'hipercard.svg',
                    'hiper' => 'hipercard.svg',
                    'diners' => 'diners.svg'
                  ];
                  
                  $detectedBrand = 'debit.svg';
                  foreach ($brandMapping as $keyword => $brandFile) {
                    if (strpos($nameLower, $keyword) !== false) {
                      $detectedBrand = $brandFile;
                      break;
                    }
                  }
                  $iconUrl = 'assets/card-brands/' . $detectedBrand;
                }
                
                // Converter path relativo para URL completa se necessário
                if (!preg_match('/^https?:\/\//i', $iconUrl) && !str_starts_with($iconUrl, '/')) {
                  $iconUrl = (function_exists('base_url') ? base_url($iconUrl) : '/' . $iconUrl);
                } elseif (str_starts_with($iconUrl, '/assets/')) {
                  $iconUrl = (function_exists('base_url') ? base_url(ltrim($iconUrl, '/')) : $iconUrl);
                }
                ?>
                <div class="brand-btn" data-brand="<?= e(strtolower(str_replace(' ', '', $methodName))) ?>" data-method-id="<?= $methodId ?>" onclick="selectCardBrand('debit', '<?= e(strtolower(str_replace(' ', '', $methodName))) ?>', <?= $methodId ?>)">
                  <img src="<?= e($iconUrl) ?>" alt="<?= e($methodName) ?>" onerror="this.src='<?= function_exists('base_url') ? base_url('assets/card-brands/debit.svg') : '/assets/card-brands/debit.svg' ?>'">
                  <span><?= e($methodName) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          
          <?php if ($voucherMethods): ?>
            <!-- Voucher Payment Option -->
            <div class="payment-type-btn" data-type="voucher" onclick="selectPaymentType('voucher')">
              <div class="payment-info">
                <img src="<?= function_exists('base_url') ? base_url('assets/card-brands/voucher.svg') : '/assets/card-brands/voucher.svg' ?>" alt="Vale-refeição" class="payment-icon">
                <div class="payment-text">
                  <div class="payment-title">Vale-refeição</div>
                  <div class="payment-subtitle">
                    <?php 
                    $voucherNames = array_map(function($method) { return $method['name'] ?? 'Vale'; }, $voucherMethods);
                    echo e(implode(', ', array_slice($voucherNames, 0, 3)) . (count($voucherNames) > 3 ? ' e mais' : ''));
                    ?>
                  </div>
                </div>
              </div>
              <svg class="arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            
            <!-- Voucher Brands Selection -->
            <div class="card-brands" id="voucher-brands">
              <?php foreach ($voucherMethods as $voucherMethod): ?>
                <?php
                $methodId = (int)($voucherMethod['id'] ?? 0);
                $methodName = $voucherMethod['name'] ?? 'Vale';
                $metaArr = [];
                if (!empty($voucherMethod['meta'])) {
                  $metaArr = is_string($voucherMethod['meta']) ? json_decode($voucherMethod['meta'], true) : (is_array($voucherMethod['meta']) ? $voucherMethod['meta'] : []);
                }
                $iconUrl = $metaArr['icon'] ?? '';
                
                // Se não tiver ícone personalizado, usar ícone genérico
                if (empty($iconUrl)) {
                  $iconUrl = 'assets/card-brands/voucher.svg';
                }
                
                // Converter path relativo para URL completa se necessário
                if (!preg_match('/^https?:\/\//i', $iconUrl) && !str_starts_with($iconUrl, '/')) {
                  $iconUrl = (function_exists('base_url') ? base_url($iconUrl) : '/' . $iconUrl);
                } elseif (str_starts_with($iconUrl, '/assets/')) {
                  $iconUrl = (function_exists('base_url') ? base_url(ltrim($iconUrl, '/')) : $iconUrl);
                }
                ?>
                <div class="brand-btn" data-brand="<?= e(strtolower(str_replace(' ', '', $methodName))) ?>" data-method-id="<?= $methodId ?>" onclick="selectCardBrand('voucher', '<?= e(strtolower(str_replace(' ', '', $methodName))) ?>', <?= $methodId ?>)">
                  <img src="<?= e($iconUrl) ?>" alt="<?= e($methodName) ?>" onerror="this.src='<?= function_exists('base_url') ? base_url('assets/card-brands/voucher.svg') : '/assets/card-brands/voucher.svg' ?>'">
                  <span><?= e($methodName) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          
          <?php if ($otherMethods): ?>
            <!-- Other Payment Option -->
            <div class="payment-type-btn" data-type="others" onclick="selectPaymentType('others')">
              <div class="payment-info">
                <img src="<?= function_exists('base_url') ? base_url('assets/card-brands/others.svg') : '/assets/card-brands/others.svg' ?>" alt="Outros" class="payment-icon">
                <div class="payment-text">
                  <div class="payment-title">Outros</div>
                  <div class="payment-subtitle">
                    <?php 
                    $otherNames = array_map(function($method) { return $method['name'] ?? 'Outros'; }, $otherMethods);
                    echo e(implode(', ', array_slice($otherNames, 0, 3)) . (count($otherNames) > 3 ? ' e mais' : ''));
                    ?>
                  </div>
                </div>
              </div>
              <svg class="arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            
            <!-- Other Methods Selection -->
            <div class="card-brands" id="others-brands">
              <?php foreach ($otherMethods as $otherMethod): ?>
                <?php
                $methodId = (int)($otherMethod['id'] ?? 0);
                $methodName = $otherMethod['name'] ?? 'Outros';
                $metaArr = [];
                if (!empty($otherMethod['meta'])) {
                  $metaArr = is_string($otherMethod['meta']) ? json_decode($otherMethod['meta'], true) : (is_array($otherMethod['meta']) ? $otherMethod['meta'] : []);
                }
                $iconUrl = $metaArr['icon'] ?? '';
                
                // Se não tiver ícone personalizado, usar ícone genérico
                if (empty($iconUrl)) {
                  $iconUrl = 'assets/card-brands/others.svg';
                }
                
                // Converter path relativo para URL completa se necessário
                if (!preg_match('/^https?:\/\//i', $iconUrl) && !str_starts_with($iconUrl, '/')) {
                  $iconUrl = (function_exists('base_url') ? base_url($iconUrl) : '/' . $iconUrl);
                } elseif (str_starts_with($iconUrl, '/assets/')) {
                  $iconUrl = (function_exists('base_url') ? base_url(ltrim($iconUrl, '/')) : $iconUrl);
                }
                ?>
                <div class="brand-btn" data-brand="<?= e(strtolower(str_replace(' ', '', $methodName))) ?>" data-method-id="<?= $methodId ?>" onclick="selectCardBrand('others', '<?= e(strtolower(str_replace(' ', '', $methodName))) ?>', <?= $methodId ?>)">
                  <img src="<?= e($iconUrl) ?>" alt="<?= e($methodName) ?>" onerror="this.src='<?= function_exists('base_url') ? base_url('assets/card-brands/others.svg') : '/assets/card-brands/others.svg' ?>'">
                  <span><?= e($methodName) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        
        <!-- Hidden inputs for payment method data -->
        <input type="hidden" name="payment[method_id]" id="payment-method-id" value="<?= $selectedPaymentId ?>">
        <input type="hidden" name="payment[type]" id="payment-type" value="">
        <input type="hidden" name="payment[brand]" id="payment-brand" value="">
      <?php else: ?>
        <div class="payment-note">Nenhum método de pagamento cadastrado. Entre em contato com a loja para mais informações.</div>
        <input type="hidden" name="payment[method_id]" id="payment-method-id" value="0">
        <input type="hidden" name="payment[type]" id="payment-type" value="">
        <input type="hidden" name="payment[brand]" id="payment-brand" value="">
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
  const paymentInput = document.getElementById('payment-method-id');
  const paymentTypeInput = document.getElementById('payment-type');
  const paymentBrandInput = document.getElementById('payment-brand');
  const paymentBox = document.getElementById('payment-instructions');

  // Payment data
  const paymentMethods = {
    <?php 
    $jsPaymentMethods = [];
    foreach ($paymentMethods as $method) {
      $methodId = (int)($method['id'] ?? 0);
      $type = $method['type'] ?? 'others';
      $metaArr = [];
      if (!empty($method['meta'])) {
        $metaArr = is_string($method['meta']) ? json_decode($method['meta'], true) : (is_array($method['meta']) ? $method['meta'] : []);
      }
      $pxKey = $method['pix_key'] ?? ($metaArr['px_key'] ?? null);
      
      $jsPaymentMethods[] = $methodId . ': {
        id: ' . $methodId . ',
        name: "' . addslashes($method['name'] ?? 'Pagamento') . '",
        type: "' . $type . '",
        instructions: "' . addslashes($method['instructions'] ?? '') . '",
        pix_key: "' . addslashes($pxKey ?? '') . '",
        meta: ' . json_encode($metaArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '
      }';
    }
    echo implode(',', $jsPaymentMethods);
    ?>
  };

  let selectedPaymentType = '';
  let selectedCardBrand = '';
  let selectedMethodId = <?= (int)$selectedPaymentId ?>;

  // Payment functions
  window.selectPaymentType = function(type) {
    const selectedBtn = document.querySelector(`.payment-type-btn[data-type="${type}"]`);
    const isCurrentlyActive = selectedBtn && selectedBtn.classList.contains('active');
    
    // Remove active state from all payment type buttons
    document.querySelectorAll('.payment-type-btn').forEach(btn => btn.classList.remove('active'));
    
    // Hide all brand sections
    document.querySelectorAll('.card-brands').forEach(brands => brands.classList.remove('show'));
    
    // Hide PIX instructions when switching to other methods
    if (type !== 'pix' && paymentBox) {
      paymentBox.innerHTML = '';
      paymentBox.classList.add('hidden');
    }
    
    // If clicking the same button that was active, just close it (toggle behavior)
    if (isCurrentlyActive) {
      selectedPaymentType = '';
      selectedMethodId = 0;
      selectedCardBrand = '';
      // Hide payment instructions when deselecting
      if (paymentBox) {
        paymentBox.innerHTML = '';
        paymentBox.classList.add('hidden');
      }
      updatePaymentData();
      return;
    }
    
    // Add active state to selected type
    if (selectedBtn) {
      selectedBtn.classList.add('active');
    }
    
    selectedPaymentType = type;
    
    if (type === 'credit') {
      // Show credit card brands
      const cardBrandsDiv = document.getElementById('credit-brands');
      if (cardBrandsDiv) {
        cardBrandsDiv.classList.add('show');
      }
      selectedCardBrand = '';
      selectedMethodId = 0;
      updatePaymentData();
    } else if (type === 'debit') {
      // Show debit card brands
      const debitBrandsDiv = document.getElementById('debit-brands');
      if (debitBrandsDiv) {
        debitBrandsDiv.classList.add('show');
      }
      selectedCardBrand = '';
      selectedMethodId = 0;
      updatePaymentData();
    } else if (type === 'voucher') {
      // Show voucher brands
      const voucherBrandsDiv = document.getElementById('voucher-brands');
      if (voucherBrandsDiv) {
        voucherBrandsDiv.classList.add('show');
      }
      selectedCardBrand = '';
      selectedMethodId = 0;
      updatePaymentData();
    } else if (type === 'others') {
      // Show other methods
      const otherBrandsDiv = document.getElementById('others-brands');
      if (otherBrandsDiv) {
        otherBrandsDiv.classList.add('show');
      }
      selectedCardBrand = '';
      selectedMethodId = 0;
      updatePaymentData();
    } else if (type === 'pix') {
      // Find first PIX method and select it
      const pixMethod = Object.values(paymentMethods).find(method => method.type === 'pix');
      if (pixMethod) {
        selectedMethodId = pixMethod.id;
        updatePaymentData();
        showPaymentInstructions(pixMethod);
      }
    }
  };

  window.selectCardBrand = function(paymentType, brand, methodId) {
    // Check if this brand is currently active
    const currentBrandsDiv = document.getElementById(paymentType + '-brands');
    const selectedBtn = currentBrandsDiv?.querySelector(`.brand-btn[data-brand="${brand}"]`);
    const isCurrentlyActive = selectedBtn && selectedBtn.classList.contains('active');
    
    // Remove active state from all brand buttons in the current type
    if (currentBrandsDiv) {
      currentBrandsDiv.querySelectorAll('.brand-btn').forEach(btn => btn.classList.remove('active'));
    }
    
    // If clicking the same brand that was active, just deselect it (toggle behavior)
    if (isCurrentlyActive) {
      selectedCardBrand = '';
      selectedMethodId = 0;
      // Hide payment instructions when deselecting
      if (paymentBox) {
        paymentBox.innerHTML = '';
        paymentBox.classList.add('hidden');
      }
      updatePaymentData();
      return;
    }
    
    // Add active state to selected brand
    if (selectedBtn) {
      selectedBtn.classList.add('active');
    }
    
    selectedCardBrand = brand;
    
    // Use the specific method ID if provided
    if (methodId) {
      selectedMethodId = methodId;
    } else {
      // Find first method of the current type
      const typeMethod = Object.values(paymentMethods).find(method => method.type === paymentType);
      if (typeMethod) {
        selectedMethodId = typeMethod.id;
      }
    }
    
    updatePaymentData();
    
    const method = paymentMethods[selectedMethodId];
    if (method) {
      showPaymentInstructions(method);
    }
  };

  function updatePaymentData() {
    if (paymentInput) paymentInput.value = selectedMethodId || '';
    if (paymentTypeInput) paymentTypeInput.value = selectedPaymentType || '';
    if (paymentBrandInput) paymentBrandInput.value = selectedCardBrand || '';
  }

  function showPaymentInstructions(method) {
    if (!paymentBox) return;
    
    const instructions = method.instructions || '';
    const type = method.type || '';
    const pxKey = method.pix_key || '';
    
    if (type === 'pix' && pxKey) {
      // Parse meta data for PIX
      let metaData = {};
      if (method.meta) {
        try {
          metaData = typeof method.meta === 'string' ? JSON.parse(method.meta) : method.meta;
        } catch (e) {
          metaData = {};
        }
      }
      
      const holderName = metaData.px_holder_name || '';
      const provider = metaData.px_provider || '';
      
      let pixHtml = `<strong>Chave Pix:</strong>
        <div class="pix-key-section">
          <div class="pix-key-value" id="pix-key-value">${pxKey}</div>
          <button type="button" class="copy-btn" onclick="copyPixKey('${pxKey}')" id="copy-pix-btn">Copiar</button>
        </div>`;
      
      if (holderName) {
        pixHtml += `<div class="pix-detail"><strong>Titular:</strong> ${holderName}</div>`;
      }
      
      if (provider) {
        pixHtml += `<div class="pix-detail"><strong>Instituição:</strong> ${provider}</div>`;
      }
      
      if (instructions) {
        pixHtml += `<div style="margin-top:8px;">${instructions.replace(/\n/g, '<br>')}</div>`;
      }
      
      paymentBox.innerHTML = pixHtml;
      paymentBox.classList.remove('hidden');
    } else if (instructions) {
      paymentBox.innerHTML = instructions.replace(/\n/g, '<br>');
      paymentBox.classList.remove('hidden');
    } else {
      paymentBox.innerHTML = '';
      paymentBox.classList.add('hidden');
    }
  }

  // Function to copy PIX key
  window.copyPixKey = function(pixKey) {
    const copyBtn = document.getElementById('copy-pix-btn');
    
    // Try to use the Clipboard API
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(pixKey).then(() => {
        showCopySuccess(copyBtn);
      }).catch(() => {
        fallbackCopyTextToClipboard(pixKey, copyBtn);
      });
    } else {
      // Fallback for older browsers or non-HTTPS
      fallbackCopyTextToClipboard(pixKey, copyBtn);
    }
  };

  function fallbackCopyTextToClipboard(text, button) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
      document.execCommand('copy');
      showCopySuccess(button);
    } catch (err) {
      console.error('Erro ao copiar:', err);
      if (button) {
        button.textContent = 'Erro';
        setTimeout(() => {
          button.textContent = 'Copiar';
        }, 2000);
      }
    }
    
    document.body.removeChild(textArea);
  }

  function showCopySuccess(button) {
    if (button) {
      button.textContent = 'Copiado!';
      button.classList.add('copied');
      setTimeout(() => {
        button.textContent = 'Copiar';
        button.classList.remove('copied');
      }, 2000);
    }
  }

  // Initialize payment selection based on PHP selection
  if (selectedMethodId && paymentMethods[selectedMethodId]) {
    const method = paymentMethods[selectedMethodId];
    selectedPaymentType = method.type;
    selectedCardBrand = '';
    
    // Show PIX instructions if PIX is selected - don't call selectPaymentType to avoid removing active class
    if (method.type === 'pix') {
      updatePaymentData();
      showPaymentInstructions(method);
    } else {
      updatePaymentData();
    }
  } else {
    // No method available, start with clean state
    selectedPaymentType = '';
    selectedMethodId = 0;
    selectedCardBrand = '';
    updatePaymentData();
  }

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

  // Initialize payment selection
  if (selectedMethodId && paymentMethods[selectedMethodId]) {
    const method = paymentMethods[selectedMethodId];
    selectedPaymentType = method.type;
    
    // Show PIX instructions if needed without triggering selectPaymentType
    if (method.type === 'pix') {
      showPaymentInstructions(method);
    }
  }
})();
</script>
</body>
</html>
