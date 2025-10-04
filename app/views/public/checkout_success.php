<?php
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('price_br')) { function price_br($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); } }

$company = is_array($company ?? null) ? $company : [];
$order   = is_array($order ?? null) ? $order : [];
$items   = isset($order['items']) && is_array($order['items']) ? $order['items'] : [];

$slug      = isset($slug) ? trim((string)$slug, '/') : (string)($company['slug'] ?? '');
$baseLink  = function_exists('base_url') ? base_url($slug !== '' ? $slug : '') : '#';
$cartLink  = function_exists('base_url') ? base_url(($slug !== '' ? $slug . '/' : '') . 'cart') : '#';
$homeName  = (string)($company['name'] ?? '');
$orderId   = (int)($order['order_id'] ?? 0);
$total     = (float)($order['total'] ?? 0);
$delivery  = (float)($order['delivery_fee'] ?? 0);
$subtotal  = (float)($order['subtotal'] ?? 0);
$payment   = trim((string)($order['payment_method'] ?? ''));
$instructions = trim((string)($order['payment_instructions'] ?? ''));
$address   = trim((string)($order['address'] ?? ''));
$notes     = trim((string)($order['notes'] ?? ''));
$customer  = trim((string)($order['customer_name'] ?? ''));
$phone     = trim((string)($order['customer_phone'] ?? ''));
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>Pedido confirmado — <?= e($homeName ?: 'Checkout') ?></title>
  <style>
    :root{
      --bg:#f3f4f6;--card:#ffffff;--border:#e5e7eb;--text:#0f172a;--muted:#6b7280;--accent:#10b981;
    }
    *{box-sizing:border-box;font-family:"Inter",system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}
    body{margin:0;background:var(--bg);color:var(--text);}
    .app{max-width:430px;margin:0 auto;min-height:100dvh;display:flex;flex-direction:column;padding:20px 16px 60px;}
    .card{background:var(--card);border-radius:18px;border:1px solid var(--border);padding:20px;margin-bottom:18px;box-shadow:0 12px 40px -22px rgba(15,23,42,0.45);}
    .hero{display:flex;flex-direction:column;align-items:center;text-align:center;gap:12px;padding:0 8px 24px;}
    .hero-icon{width:70px;height:70px;border-radius:50%;background:rgba(16,185,129,0.12);display:grid;place-items:center;color:#059669;}
    .hero-icon svg{width:34px;height:34px;}
    h1{margin:0;font-size:22px;font-weight:800;}
    .subtitle{font-size:14px;color:var(--muted);line-height:1.5;}
    .summary{display:grid;gap:8px;font-size:15px;}
    .summary-row{display:flex;justify-content:space-between;align-items:center;}
    .summary-row.total{font-size:17px;font-weight:700;}
    .pill{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:10px 14px;background:#f1f5f9;color:#0f172a;font-weight:600;font-size:14px;margin:0 auto 12px;}
    .section-title{font-size:15px;font-weight:700;margin:0 0 10px;}
    .items{display:grid;gap:10px;margin-top:12px;}
    .item-row{display:flex;justify-content:space-between;gap:10px;font-size:14px;background:#f9fafb;border:1px solid var(--border);border-radius:14px;padding:10px 14px;}
    .item-row span:first-child{font-weight:600;flex:1;}
    .block{margin-top:18px;}
    .block p{margin:0 0 6px;font-size:14px;color:var(--muted);}
    .note{white-space:pre-line;background:#f9fafb;border:1px solid var(--border);border-radius:12px;padding:12px;font-size:14px;color:#1f2937;}
    .actions{margin-top:auto;display:flex;flex-direction:column;gap:12px;padding-bottom:32px;}
    .btn{display:inline-flex;justify-content:center;align-items:center;gap:8px;padding:14px 18px;border-radius:14px;font-weight:700;font-size:15px;text-decoration:none;}
    .btn-primary{background:var(--accent);color:#fff;}
    .btn-secondary{background:var(--card);color:var(--text);border:1px solid var(--border);}
  </style>
</head>
<body>
  <div class="app">
    <div class="card">
      <div class="hero">
        <div class="hero-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4 4L19 7"/></svg>
        </div>
        <div class="pill">
          Pedido <?= $orderId > 0 ? '#'.e($orderId) : 'confirmado' ?>
        </div>
        <h1>Pedido confirmado!</h1>
        <p class="subtitle">
          Recebemos seu pedido<?= $customer !== '' ? ' para ' . e($customer) : '' ?>.
          Assim que for aceito pela loja você será avisado.
        </p>
      </div>

      <div class="summary">
        <div class="summary-row"><span>Subtotal</span><span><?= price_br($subtotal) ?></span></div>
        <div class="summary-row"><span>Entrega</span><span><?= price_br($delivery) ?></span></div>
        <div class="summary-row total"><span>Total</span><span><?= price_br($total) ?></span></div>
      </div>

      <?php if ($payment !== ''): ?>
        <div class="block">
          <span class="section-title">Pagamento</span>
          <p><strong><?= e($payment) ?></strong></p>
          <?php if ($instructions !== ''): ?>
            <div class="note"><?= nl2br(e($instructions)) ?></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($address !== ''): ?>
        <div class="block">
          <span class="section-title">Endereço de entrega</span>
          <div class="note"><?= nl2br(e($address)) ?></div>
        </div>
      <?php endif; ?>

      <?php if ($notes !== ''): ?>
        <div class="block">
          <span class="section-title">Observações do cliente</span>
          <div class="note"><?= nl2br(e($notes)) ?></div>
        </div>
      <?php endif; ?>

      <?php if ($phone !== ''): ?>
        <div class="block">
          <span class="section-title">Contato</span>
          <p><?= e($phone) ?></p>
        </div>
      <?php endif; ?>

      <?php if ($items): ?>
        <div class="block">
          <span class="section-title">Itens</span>
          <div class="items">
            <?php foreach ($items as $it): ?>
              <div class="item-row">
                <span><?= e(($it['quantity'] ?? 1) . 'x ' . ($it['name'] ?? 'Item')) ?></span>
                <span><?= price_br($it['line_total'] ?? 0) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="actions">
      <a class="btn btn-primary" href="<?= e($baseLink) ?>">Voltar ao cardápio</a>
      <a class="btn btn-secondary" href="<?= e($cartLink) ?>">Ver carrinho novamente</a>
    </div>
  </div>
</body>
</html>
