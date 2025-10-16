<?php

$items  = isset($items) && is_array($items) ? $items : [];
$totals = isset($totals) && is_array($totals) ? $totals : ['subtotal' => 0.0, 'total' => 0.0];
$company = $company ?? [];
$slug = isset($slug) ? (string)$slug : (string)($company['slug'] ?? '');
$customer = $customer ?? null;
$requireLogin = !empty($requireLogin);

$slugClean = trim($slug, '/');
$slugEncoded = $slugClean !== '' ? rawurlencode($slugClean) : '';
$basePath = $slugEncoded !== '' ? $slugEncoded : '';
$homeUrl = function_exists('base_url') ? base_url($basePath) : '#';
$updateUrl = isset($updateUrl) ? (string)$updateUrl : (function_exists('base_url') ? base_url(($basePath !== '' ? $basePath . '/' : '') . 'cart/update') : '#');
$checkoutUrl = function_exists('base_url') ? base_url(($basePath !== '' ? $basePath . '/' : '') . 'checkout') : '#';

if ($requireLogin && !$customer) {
    $checkoutUrl = $homeUrl . '?login=1';
}
$backUrl = $homeUrl;
$formatBrl = static function ($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); };
$uploadSrc = static function (?string $value, string $fallback = 'assets/logo-placeholder.png') {
    $raw = trim((string)($value ?? ''));

    if ($raw === '') {
        return base_url($fallback);
    }
    $path = parse_url($raw, PHP_URL_PATH);

    if ($path && strpos($path, '/uploads/') !== false) {
        return base_url(ltrim($path, '/'));
    }

    if (preg_match('/^https?:\/\//i', $raw)) {
        return $raw;
    }

    if ($path) {
        $raw = $path;
    }
    $raw = ltrim($raw, '/');

    if (strpos($raw, 'uploads/') === 0) {
        return base_url($raw);
    }

    return base_url('uploads/' . basename($raw));
};
$companyName = $company['name'] ?? 'Meu Carrinho';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Sacola — <?= e($companyName) ?></title>
<style>
  :root{ --bg:#F3F4F6; --surface:#FFFFFF; --border:#E5E7EB; --text:#0F172A; --muted:#6B7280; --accent:#F59E0B; --accent-active:#D97706; --accent-ink:#fff; --radius:20px; --shadow:0 1px 2px rgba(16,24,40,.06), 0 1px 3px rgba(16,24,40,.10); }
  *{box-sizing:border-box}
  body{margin:0;font-family:ui-sans-serif,-apple-system,system-ui,Segoe UI,Roboto,Helvetica,Arial;background:var(--bg);color:var(--text)}
  .container{width:100%;max-width:100%;margin:0 auto;min-height:100dvh;display:flex;flex-direction:column}
  .topbar{position:sticky;top:0;background:#fff;border-bottom:1px solid var(--border);z-index:10}
  .topwrap{display:flex;align-items:center;gap:12px;padding:10px 14px}
  .back{width:36px;height:36px;border-radius:999px;border:1px solid var(--border);display:grid;place-items:center;background:#fff;cursor:pointer}
  .back svg{width:18px;height:18px}
  .title{font-weight:800;font-size:18px}
  .empty{margin:40px 16px;padding:40px 24px;border-radius:var(--radius);background:var(--surface);border:1px dashed var(--border);text-align:center;color:var(--muted);font-weight:600}

  /* cartão base */
  .item{display:grid;grid-template-columns:56px 1fr auto;gap:12px;align-items:center;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;margin:10px 12px;box-shadow:var(--shadow)}
  .avatar{width:52px;height:52px;border-radius:12px;background:#F2F4F7;border:1px solid var(--border);overflow:hidden;display:grid;place-items:center}
  .avatar img{width:100%;height:100%;object-fit:cover}
  .info{min-width:0}
  .name{font-weight:800;font-size:16px;margin:0 0 4px}
  .price{font-weight:700;font-size:14px;color:var(--muted)}
  .qty{display:flex;align-items:center;gap:14px;border:1px solid #E6E7EB;background:#fff;border-radius:999px;padding:8px 12px;min-width:114px;justify-content:center}
  .qty form{display:flex;align-items:center;gap:14px}
  .btn{width:24px;height:24px;display:grid;place-items:center;border-radius:999px;border:0;background:transparent;font-size:18px;line-height:1;cursor:pointer;color:#111827}
  .val{min-width:16px;text-align:center;font-weight:700}

  /* ===== BLOCO DE DETALHES (produto simples - como antes) ===== */
  .toggle-row{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:8px;padding-top:10px;border-top:1px solid var(--border);background:transparent;border-radius:0;border:none;cursor:pointer}
  .toggle-left{font-weight:800;font-size:14px}
  .toggle-right{display:flex;align-items:center;gap:8px}
  .note{font-weight:700;font-size:13px;color:#6B7280}
  .chev{width:18px;height:18px;transition:transform .2s ease;color:#111827}
  .toggle-row.open .chev{transform:rotate(90deg)}
  .ext{display:none;background:var(--surface);border:1px solid var(--border);border-top:0;border-radius:0 0 var(--radius) var(--radius);margin:-10px 12px 14px;padding:8px 14px 12px;box-shadow:var(--shadow)}
  .ext.open{display:block}
  .item.open{border-bottom:0;border-bottom-left-radius:0;border-bottom-right-radius:0;margin-bottom:0}
  .section-title{font-weight:700;font-size:13px;color:#4B5563;margin:12px 0 6px;text-transform:uppercase;letter-spacing:.08em}
  .ing{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-top:1px dashed #D1D5DB}
  .ing:first-child{border-top:0}
  .ing-name{font-size:13px;font-weight:600;color:#111827}
  .ing-meta{font-size:12px;color:#6B7280}

  /* ===== Lista interna para itens do combo (reaproveitada dentro de .ext) ===== */
  .linked-list{border-top:1px solid var(--border)}
  .linked{display:grid;grid-template-columns:44px 1fr auto;gap:10px;align-items:center;padding:12px 0}
  .linked + .linked{border-top:1px solid var(--border)}
  .l-ava{width:36px;height:36px;border-radius:999px;background:#F2F4F7;border:1px solid var(--border);display:grid;place-items:center;overflow:hidden}
  .l-ava img{width:100%;height:100%;object-fit:cover}
  .l-name{font-weight:700;font-size:14px}
  .l-meta{font-size:12px;color:#6B7280;margin-top:2px}
  .l-right{display:flex;align-items:center;gap:8px;font-weight:700;font-size:13px;color:#6B7280}
  .linked.toggle{cursor:pointer}
  .linked.toggle .chev{width:18px;height:18px;transition:transform .2s ease;color:#111827}
  .linked.open .chev{transform:rotate(90deg)}
  .nested{display:none;padding:6px 0 0 54px}
  .linked.open + .nested{display:block}

  /* totais */
  .coupon{padding:4px 16px 0}
  .coupon-btn{width:100%;display:flex;align-items:center;gap:10px;background:#fff;border:1px solid var(--border);border-radius:12px;padding:12px 14px;font-weight:700;color:#111827;cursor:pointer;box-shadow:var(--shadow)}
  .coupon-btn svg{width:18px;height:18px}
  .totals{padding:16px 16px 24px}
  .trow{display:flex;justify-content:space-between;align-items:center;margin:18px 0}
  .trow .label{font-size:22px;font-weight:800;letter-spacing:.2px;color:#0F172A}
  .trow .value{font-size:28px;font-weight:800;color:#0F172A}
  .footer{position:fixed;left:50%;transform:translateX(-50%);bottom:0;background:#fff;border-top:1px solid var(--border);padding:12px;z-index:20;width:100%;max-width:100%}
  @media (min-width:768px){
    .container{max-width:420px}
    .footer{max-width:420px}
  }
  .container{padding-bottom:120px}
  .cta{display:flex;align-items:center;justify-content:center;width:100%;min-height:56px;border:none;border-radius:18px;padding:0 24px;background:var(--accent);color:var(--accent-ink);font-weight:800;font-size:16px;text-decoration:none;text-align:center;cursor:pointer}
  .cta:active{background:var(--accent-active)}
  .cta[disabled]{opacity:.6;cursor:not-allowed}
</style>
</head>
<body>
<div class="container">
  <div class="topbar">
    <div class="topwrap">
  <a class="back" href="<?= e($backUrl) ?>" data-action="navigate">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" transform="scale(0.7) translate(5 5)"/></svg>
      </a>
      <div class="title">Minha Sacola</div>
    </div>
  </div>

  <?php if (!$items): ?>
    <div class="empty">Sua sacola está vazia.<br><small>Explore o cardápio e adicione itens.</small></div>
  <?php endif; ?>

  <?php foreach ($items as $index => $item):
      $uid = preg_replace('/[^a-z0-9]/i', '', (string)($item['uid'] ?? 'u'.$index)) ?: 'u'.$index;
      $hasCombo = !empty($item['combo']['groups']);
      $eps = 0.009;

      /* ===================== COMBO (MESMA ESTRUTURA DO SIMPLES) ===================== */
      if ($hasCombo):
          $extId = 'ext-'.$uid;

          $eps = 0.009;
          $comboExtraTotal = 0.0;
          $comboHasPaid = false;
          $componentShouldOpen = [];

          foreach ($item['combo']['groups'] as $group) {
              foreach (($group['items'] ?? []) as $choice) {
                  $delta = isset($choice['delta']) ? (float)$choice['delta'] : 0.0;
                  $basePrice = null;

                  if (array_key_exists('base_price', $choice) && $choice['base_price'] !== null) {
                      $basePrice = (float)$choice['base_price'];
                  } elseif (array_key_exists('price', $choice) && $choice['price'] !== null) {
                      $basePrice = (float)$choice['price'];
                  }
                  $isDefault = !empty($choice['is_default']) || !empty($choice['default']);

                  if (!$isDefault) {
                      $charge = $basePrice !== null ? $basePrice : $delta;
                      $comboExtraTotal += $charge;

                      if (abs($charge) > $eps) {
                          $comboHasPaid = true;
                      }
                  }

                  $simpleId = (int)($choice['simple_id'] ?? 0);

                  if ($simpleId && !empty($item['component_customizations'][$simpleId]['customization']['groups'])) {
                      $componentHasPaid = false;

                      foreach ($item['component_customizations'][$simpleId]['customization']['groups'] as $cg) {
                          foreach (($cg['items'] ?? []) as $opt) {
                              $qty = isset($opt['qty']) ? (int)$opt['qty'] : null;
                              $linePrice = 0.0;

                              if (isset($opt['price'])) {
                                  $linePrice = (float)$opt['price'];
                              } elseif ($qty !== null && isset($opt['unit_price'])) {
                                  $linePrice = (float)$opt['unit_price'] * $qty;
                              }
                              $comboExtraTotal += $linePrice;

                              if (abs($linePrice) > $eps) {
                                  $componentHasPaid = true;
                                  $comboHasPaid = true;
                              }
                          }
                      }

                      if ($componentHasPaid) {
                          $componentShouldOpen[$simpleId] = true;
                      }
                  }
              }
          }

      if (!empty($item['customization']['groups'])) {
          foreach ($item['customization']['groups'] as $g) {
              foreach (($g['items'] ?? []) as $opt) {
                  $qty = isset($opt['qty']) ? (int)$opt['qty'] : null;
                  $linePrice = 0.0;

                  if (isset($opt['price'])) {
                      $linePrice = (float)$opt['price'];
                  } elseif ($qty !== null && isset($opt['unit_price'])) {
                      $linePrice = (float)$opt['unit_price'] * $qty;
                  }
                  $comboExtraTotal += $linePrice;

                  if (abs($linePrice) > $eps) {
                      $comboHasPaid = true;
                  }
              }
          }
      }

      if ($comboHasPaid) {
          if ($comboExtraTotal > $eps) {
              $headerNote = $formatBrl($comboExtraTotal);
          } elseif ($comboExtraTotal < -$eps) {
              $headerNote = '− '.$formatBrl(abs($comboExtraTotal));
          } else {
              $headerNote = $formatBrl(0);
          }
      } else {
          $headerNote = 'Incluso';
      }

      $openCombo = $comboHasPaid;
      $cardClasses = 'item' . ($openCombo ? ' open' : '');
      $buttonClasses = 'toggle-row' . ($openCombo ? ' open' : '');
      $extClasses = 'ext' . ($openCombo ? ' open' : '');
      $ariaExpanded = $openCombo ? 'true' : 'false';
      ?>
    <div class="<?= e($cardClasses) ?>" id="card-<?= e($uid) ?>" aria-controls="<?= e($extId) ?>" aria-expanded="<?= e($ariaExpanded) ?>">
      <div class="avatar">
        <?php if (!empty($item['product']['image'])): ?>
          <img src="<?= e($uploadSrc($item['product']['image'])) ?>" alt="<?= e($item['product']['name'] ?? '') ?>">
        <?php else: ?>
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#94A3B8" stroke-width="1.5" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3-6 8-6s8 2 8 6" stroke-linecap="round"/></svg>
        <?php endif; ?>
      </div>
      <div class="info">
        <div class="name"><?= e($item['product']['name'] ?? 'Combo') ?></div>
        <div class="price"><?= e($formatBrl($item['unit_price'] ?? 0)) ?></div>
      </div>
      <div class="qty" role="group" aria-label="Quantidade de <?= e($item['product']['name'] ?? 'Combo') ?>">
        <form method="post" action="<?= e($updateUrl) ?>">
          <input type="hidden" name="uid" value="<?= e($item['uid']) ?>">
          <button class="btn" type="submit" name="action" value="dec" aria-label="Diminuir">&minus;</button>
          <span class="val"><?= (int)($item['qty'] ?? 1) ?></span>
          <button class="btn" type="submit" name="action" value="inc" aria-label="Aumentar">+</button>
        </form>
      </div>

      <!-- Linha de toggle idêntica ao simples, com nota = valor de adicionais -->
      <button class="<?= e($buttonClasses) ?>" type="button" data-target="<?= e($extId) ?>" aria-expanded="<?= e($ariaExpanded) ?>">
        <span class="toggle-left">Itens do combo</span>
        <span class="toggle-right">
          <span class="note"><?= e($headerNote) ?></span>
          <svg class="chev" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </button>
    </div>

    <!-- Corpo da lista do combo, usando a mesma .ext do simples -->
    <div class="<?= e($extClasses) ?>" id="<?= e($extId) ?>">
      <div class="linked-list">
        <?php foreach ($item['combo']['groups'] as $group):
            foreach (($group['items'] ?? []) as $choice):
                $delta = isset($choice['delta']) ? (float)$choice['delta'] : 0.0;
                $basePrice = null;

                if (array_key_exists('base_price', $choice) && $choice['base_price'] !== null) {
                    $basePrice = (float)$choice['base_price'];
                } elseif (array_key_exists('price', $choice) && $choice['price'] !== null) {
                    $basePrice = (float)$choice['price'];
                }
                $isDefault = !empty($choice['is_default']) || !empty($choice['default']);

                if ($isDefault) {
                    $metaPrice = 'Incluso';
                    $note = 'Incluso';
                } else {
                    $displayValue = $basePrice !== null ? $basePrice : $delta;

                    if ($displayValue > 0.009) {
                        $valueLabel = $formatBrl($displayValue);
                    } elseif ($displayValue < -0.009) {
                        $valueLabel = '− '.$formatBrl(abs($displayValue));
                    } else {
                        $valueLabel = $formatBrl(0);
                    }
                    $metaPrice = $valueLabel;
                    $note = $valueLabel;
                }

                $simpleId = (int)($choice['simple_id'] ?? 0);
                $componentCustomization = null;

                if ($simpleId && !empty($item['component_customizations'][$simpleId]['customization'])) {
                    $componentCustomization = $item['component_customizations'][$simpleId]['customization'];
                }
                $hasChildren = $componentCustomization && !empty($componentCustomization['groups']);
                $showInlineMeta = !$hasChildren;
                ?>
          <?php $componentOpen = $hasChildren && !empty($componentShouldOpen[$simpleId]); ?>
          <div class="linked<?= $hasChildren ? ' toggle' : '' ?><?= $componentOpen ? ' open' : '' ?>"<?= $hasChildren ? ' aria-expanded="'.($componentOpen ? 'true' : 'false').'"' : '' ?>>
            <div class="l-ava">
              <?php if (!empty($choice['image'])): ?>
                <img src="<?= e($uploadSrc($choice['image'])) ?>" alt="<?= e($choice['name'] ?? '') ?>">
              <?php else: ?>
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#94A3B8" stroke-width="1.5" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3-6 8-6s8 2 8 6" stroke-linecap="round"/></svg>
              <?php endif; ?>
            </div>
            <div>
              <div class="l-name"><?= e($choice['name'] ?? '') ?></div>
              <?php if ($showInlineMeta): ?>
                <div class="l-meta"><?= e($metaPrice) ?></div>
              <?php endif; ?>
            </div>
            <div class="l-right">
              <?php if ($hasChildren): ?>
                <span class="l-note"><?= e($note) ?></span>
                <svg class="chev" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($hasChildren): ?>
            <div class="nested">
              <?php foreach ($componentCustomization['groups'] as $cGroup):
                  foreach (($cGroup['items'] ?? []) as $opt):
                      $childName = (string)($opt['name'] ?? '');
                      $childQty  = isset($opt['qty']) ? (int)$opt['qty'] : null;

                      if ($childQty !== null && $childQty > 1) {
                          $childName = $childQty.'x '.$childName;
                      }
                      $childPrice = 0.0;

                      if (isset($opt['price'])) {
                          $childPrice = (float)$opt['price'];
                      } elseif ($childQty !== null && isset($opt['unit_price'])) {
                          $childPrice = (float)$opt['unit_price'] * $childQty;
                      }
                      $childMeta = $childPrice > 0.009 ? '+ '.$formatBrl($childPrice) : ($childPrice < -0.009 ? '− '.$formatBrl(abs($childPrice)) : 'Incluso');
                      ?>
                <div class="ing"><div class="ing-name"><?= e($childName) ?></div><div class="ing-meta"><?= e($childMeta) ?></div></div>
              <?php endforeach; endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endforeach; endforeach; ?>
      </div>

      <?php if (!empty($item['customization']['groups'])): ?>
        <div class="section-title" style="margin-top:10px">Personalizações do combo</div>
        <?php foreach ($item['customization']['groups'] as $group):
            foreach (($group['items'] ?? []) as $opt):
                $name = (string)($opt['name'] ?? '');
                $qty  = isset($opt['qty']) ? (int)$opt['qty'] : null;

                if ($qty !== null && $qty > 1) {
                    $name = $qty.'x '.$name;
                }
                $linePrice = 0.0;

                if (isset($opt['price'])) {
                    $linePrice = (float)$opt['price'];
                } elseif ($qty !== null && isset($opt['unit_price'])) {
                    $linePrice = (float)$opt['unit_price'] * $qty;
                }
                $meta = $linePrice > 0.009 ? '+ '.$formatBrl($linePrice) : ($linePrice < -0.009 ? '− '.$formatBrl(abs($linePrice)) : 'Incluso');
                ?>
          <div class="ing"><div class="ing-name"><?= e($name) ?></div><div class="ing-meta"><?= e($meta) ?></div></div>
        <?php endforeach; endforeach; ?>
      <?php endif; ?>
    </div>
    <?php continue; endif; ?>

    <?php
      /* ===================== PRODUTO SIMPLES (EXATAMENTE COMO ANTES) ===================== */
      $extId = 'ext-'.$uid;
      $hasDetails = (!empty($item['customization']['groups'])) || !empty($item['component_customizations']);
      $extraTotal = 0.0;
      $hasPaidExtra = false;

      if (!empty($item['customization']['groups'])) {
          foreach ($item['customization']['groups'] as $group) {
              foreach (($group['items'] ?? []) as $opt) {
                  $linePrice = 0.0;

                  if (isset($opt['price'])) {
                      $linePrice = (float)$opt['price'];
                  } elseif (isset($opt['unit_price'], $opt['qty'])) {
                      $linePrice = (float)$opt['unit_price'] * (int)$opt['qty'];
                  }
                  $extraTotal += $linePrice;

                  if (abs($linePrice) > $eps) {
                      $hasPaidExtra = true;
                  }
              }
          }
      }

      if (!$hasDetails) {
          $extraTotal = 0.0;
      }
      $headerNote = 'Incluso';

      if (!empty($item['customization']['groups'])) {
          if ($extraTotal > $eps) {
              $headerNote = '+ '.$formatBrl($extraTotal);
          } elseif ($extraTotal < -$eps) {
              $headerNote = '− '.$formatBrl(abs($extraTotal));
          }
      }
      $openSimple = $hasPaidExtra;
      $cardClasses = 'item' . ($openSimple ? ' open' : '');
      $buttonClasses = 'toggle-row' . ($openSimple ? ' open' : '');
      $extClasses = 'ext' . ($openSimple ? ' open' : '');
      $ariaExpanded = $openSimple ? 'true' : 'false';
      ?>
    <div class="<?= e($cardClasses) ?>" id="card-<?= e($uid) ?>" aria-controls="<?= e($extId) ?>" aria-expanded="<?= e($ariaExpanded) ?>">
      <div class="avatar">
        <?php if (!empty($item['product']['image'])): ?>
          <img src="<?= e($uploadSrc($item['product']['image'])) ?>" alt="<?= e($item['product']['name'] ?? '') ?>">
        <?php else: ?>
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#94A3B8" stroke-width="1.5" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3-6 8-6s8 2 8 6" stroke-linecap="round"/></svg>
        <?php endif; ?>
      </div>
      <div class="info">
        <div class="name"><?= e($item['product']['name'] ?? 'Produto') ?></div>
        <div class="price"><?= e($formatBrl($item['unit_price'] ?? 0)) ?></div>
      </div>
      <div class="qty" role="group" aria-label="Quantidade de <?= e($item['product']['name'] ?? 'Produto') ?>">
        <form method="post" action="<?= e($updateUrl) ?>">
          <input type="hidden" name="uid" value="<?= e($item['uid']) ?>">
          <button class="btn" type="submit" name="action" value="dec" aria-label="Diminuir">&minus;</button>
          <span class="val"><?= (int)($item['qty'] ?? 1) ?></span>
          <button class="btn" type="submit" name="action" value="inc" aria-label="Aumentar">+</button>
        </form>
      </div>
      <?php if ($hasDetails): ?>
        <button class="<?= e($buttonClasses) ?>" type="button" data-target="<?= e($extId) ?>" aria-expanded="<?= e($ariaExpanded) ?>">
          <span class="toggle-left">Ingredientes</span>
          <span class="toggle-right">
            <span class="note"><?= e($headerNote) ?></span>
            <svg class="chev" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </span>
        </button>
      <?php endif; ?>
    </div>

    <?php if ($hasDetails): ?>
      <div class="<?= e($extClasses) ?>" id="<?= e($extId) ?>">
        <?php foreach ($item['customization']['groups'] as $group):
            $groupItems = $group['items'] ?? [];

            if (!$groupItems) {
                continue;
            }
            $groupTitle = trim((string)($group['name'] ?? '')); ?>
          <?php if ($groupTitle !== ''): ?><div class="section-title"><?= e($groupTitle) ?></div><?php endif; ?>
          <?php foreach ($groupItems as $opt):
              $name = (string)($opt['name'] ?? '');
              $qty  = isset($opt['qty']) ? (int)$opt['qty'] : null;
              $defaultQty = array_key_exists('default_qty', $opt) && $opt['default_qty'] !== null ? (int)$opt['default_qty'] : null;
              $deltaQty = array_key_exists('delta_qty', $opt) ? (int)$opt['delta_qty'] : null;

              if ($deltaQty === null && $qty !== null) {
                  $deltaQty = $defaultQty !== null ? $qty - $defaultQty : $qty;
              }

              if ($qty !== null && $qty > 1) {
                  $name = $qty.'x '.$name;
              }

              $linePrice = 0.0;

              if (isset($opt['price'])) {
                  $linePrice = (float)$opt['price'];
              } elseif ($deltaQty !== null && isset($opt['unit_price'])) {
                  $linePrice = (float)$opt['unit_price'] * $deltaQty;
              } elseif ($qty !== null && isset($opt['unit_price'])) {
                  $linePrice = (float)$opt['unit_price'] * $qty;
              }

              $meta = 'Incluso';

              if ($deltaQty !== null) {
                  if ($deltaQty > 0 && $linePrice > 0.009) {
                      $meta = '+ '.$formatBrl($linePrice);
                  } elseif ($deltaQty > 0 && $linePrice <= 0.009) {
                      $meta = 'Extra';
                  } elseif ($deltaQty < 0 && $linePrice < -0.009) {
                      $meta = '− '.$formatBrl(abs($linePrice));
                  } elseif ($deltaQty < 0 && $linePrice >= -0.009) {
                      $meta = 'Removido';
                  }
              } else {
                  if ($linePrice > 0.009) {
                      $meta = '+ '.$formatBrl($linePrice);
                  } elseif ($linePrice < -0.009) {
                      $meta = '− '.$formatBrl(abs($linePrice));
                  }
              }
              ?>
            <div class="ing"><div class="ing-name"><?= e($name) ?></div><div class="ing-meta"><?= e($meta) ?></div></div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>

  <div class="coupon">
    <button class="coupon-btn" id="coupon-btn" type="button" aria-label="Aplicar cupom">
      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 7a2 2 0 0 1 2-2h9.2a2 2 0 0 1 1.414.586L20 8.97V17a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z" stroke="currentColor" stroke-width="1.7"/><circle cx="16.5" cy="11.5" r="1" fill="currentColor"/><circle cx="13.5" cy="14.5" r="1" fill="currentColor"/></svg>
      <span>Você tem algum cupom de desconto?</span>
    </button>
  </div>

  <div class="totals">
    <div class="trow"><span class="label">Subtotal</span><span class="value"><?= e($formatBrl($totals['subtotal'] ?? 0)) ?></span></div>
  </div>

  <div class="footer">
    <?php if ($items): ?>
      <a class="cta" href="<?= e($checkoutUrl) ?>">Ir para o checkout</a>
    <?php else: ?>
      <button class="cta" type="button" disabled>Ir para o checkout</button>
    <?php endif; ?>
  </div>
</div>

<script>
  /* Toggle dos blocos (simples e combo compartilham a mesma estrutura) */
  document.querySelectorAll('.toggle-row').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target');
      const ext = targetId ? document.getElementById(targetId) : null;
      const item = btn.closest('.item');
      const open = !(ext && ext.classList.contains('open'));
      if (ext) ext.classList.toggle('open', open);
      if (item) item.classList.toggle('open', open);
      btn.classList.toggle('open', open);
      btn.setAttribute('aria-expanded', String(open));
    });
  });

  /* Abrir filhos do item do combo (mostra ingredientes do simples ligado) */
  document.querySelectorAll('.linked.toggle').forEach(row=>{
    row.addEventListener('click', ()=>{
      const nextState = !row.classList.contains('open');
      row.classList.toggle('open', nextState);
      row.setAttribute('aria-expanded', String(nextState));
    });
  });

  document.getElementById('coupon-btn')?.addEventListener('click', () => {
    const code = prompt('Digite seu cupom de desconto:');
    if (code) alert('Cupom recebido: ' + code + ' (validação no backend).');
  });
</script>
</body>
</html>
