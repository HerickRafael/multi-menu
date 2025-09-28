<?php
if (!function_exists('e')) {
    function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
$items  = isset($items) && is_array($items) ? $items : [];
$totals = isset($totals) && is_array($totals) ? $totals : ['subtotal' => 0.0, 'total' => 0.0];
$company = $company ?? [];
$slug = isset($slug) ? (string)$slug : (string)($company['slug'] ?? '');
$updateUrl = isset($updateUrl) ? (string)$updateUrl : base_url(($slug ? $slug.'/' : '') . 'cart/update');
$backUrl = base_url($slug ?: ($company['slug'] ?? ''));
$formatBrl = static function ($value) {
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
};
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
    :root{
      --bg:#F3F4F6; --surface:#FFFFFF; --border:#E5E7EB; --text:#0F172A; --muted:#6B7280;
      --accent:#F4A62A; --accent-ink:#fff; --radius:20px;
      --shadow:0 1px 2px rgba(16,24,40,.06), 0 1px 3px rgba(16,24,40,.10);
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:ui-sans-serif,-apple-system,system-ui,Segoe UI,Roboto,Helvetica,Arial;background:var(--bg);color:var(--text)}
    .container{max-width:430px;margin:0 auto;min-height:100dvh;display:flex;flex-direction:column;background:var(--bg)}
    .topbar{position:sticky;top:0;background:#fff;border-bottom:1px solid var(--border);z-index:10}
    .topwrap{display:flex;align-items:center;gap:12px;padding:10px 14px}
    .back{width:36px;height:36px;border-radius:999px;border:1px solid var(--border);display:grid;place-items:center;background:#fff;cursor:pointer}
    .back svg{width:18px;height:18px}
    .title{font-weight:800;font-size:18px}
    .empty{margin:40px 16px;padding:40px 24px;border-radius:var(--radius);background:var(--surface);border:1px dashed var(--border);text-align:center;color:var(--muted);font-weight:600}
    .section{padding:16px 16px 6px}
    .item{display:grid;grid-template-columns:56px 1fr auto;gap:12px;align-items:center;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;margin:10px 12px;box-shadow:var(--shadow)}
    .avatar{width:44px;height:44px;border-radius:999px;background:#F2F4F7;border:1px solid var(--border);overflow:hidden;display:grid;place-items:center}
    .avatar img{width:100%;height:100%;object-fit:cover}
    .info{min-width:0}
    .name{font-weight:800;font-size:16px;margin:0 0 4px}
    .price{font-weight:700;font-size:14px;color:var(--muted)}
    .qty{display:flex;align-items:center;gap:14px;border:1px solid #E6E7EB;background:#fff;border-radius:999px;padding:8px 12px;min-width:114px;justify-content:center}
    .qty form{display:flex;align-items:center;gap:14px}
    .btn{width:24px;height:24px;display:grid;place-items:center;border-radius:999px;border:0;background:transparent;font-size:18px;line-height:1;cursor:pointer;color:#111827}
    .btn:focus{outline:none}
    .val{min-width:16px;text-align:center;font-weight:700}
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
    .ing{display:flex;align-items:flex-start;justify-content:space-between;padding:10px 0;border-top:1px solid var(--border)}
    .ing:first-child{border-top:0}
    .ing-name{font-size:13px;font-weight:600;color:#111827}
    .ing-meta{font-size:12px;color:#6B7280;text-align:right;max-width:65%}
    .linked-list{border-top:1px solid var(--border)}
    .linked{display:grid;grid-template-columns:44px 1fr auto;gap:10px;align-items:center;padding:12px 0}
    .linked + .linked{border-top:1px solid var(--border)}
    .l-ava{width:36px;height:36px;border-radius:999px;background:#F2F4F7;border:1px solid var(--border);overflow:hidden;display:grid;place-items:center}
    .l-ava img{width:100%;height:100%;object-fit:cover}
    .l-name{font-weight:700;font-size:14px}
    .l-meta{font-size:12px;color:#6B7280}
    .l-right{display:flex;align-items:center;gap:8px;font-weight:700;font-size:13px;color:#6B7280}
    .nested{margin-left:52px;padding:8px 0 0}
    .nested .ing{padding:6px 0;border:none}
    .nested .ing-meta{text-align:left}
    .coupon{padding:4px 16px 0}
    .coupon-btn{width:100%;display:flex;align-items:center;gap:10px;background:#fff;border:1px solid var(--border);border-radius:12px;padding:12px 14px;font-weight:700;color:#111827;cursor:pointer;box-shadow:var(--shadow)}
    .coupon-btn svg{width:18px;height:18px;color:#111827}
    .totals{padding:16px 16px 24px}
    .trow{display:flex;justify-content:space-between;align-items:center;margin:18px 0}
    .trow .label{font-size:20px;font-weight:800;letter-spacing:.2px;color:#0F172A}
    .trow .value{font-size:24px;font-weight:800;color:#0F172A}
    .line{height:0;border:0;border-top:3px dashed #D1D5DB;margin:16px 0}
    .footer{position:sticky;bottom:0;background:#fff;border-top:1px solid var(--border);padding:12px}
    .cta{width:100%;background:#F4A62A;color:#fff;border:none;border-radius:14px;font-weight:800;font-size:16px;padding:14px 18px;cursor:pointer}
  </style>
</head>
<body>
  <div class="container">
    <div class="topbar">
      <div class="topwrap">
        <button class="back" type="button" onclick="window.location.href='<?= e($backUrl) ?>'">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" transform="scale(0.7) translate(5 5)"/></svg>
        </button>
        <div class="title">Minha Sacola</div>
      </div>
    </div>

    <?php if (!$items): ?>
      <div class="empty">Sua sacola está vazia.<br><small>Explore o cardápio e adicione itens.</small></div>
    <?php endif; ?>

    <?php if ($items): ?>
      <div class="section"><div class="h2">Lista do Carrinho</div></div>
    <?php endif; ?>

    <?php foreach ($items as $index => $item):
      $uid = preg_replace('/[^a-z0-9]/i', '', (string)$item['uid']);
      if ($uid === '') { $uid = 'item'.$index; }
      $cardId = 'item-card-' . $uid;
      $extId  = 'item-ext-' . $uid;
      $hasCombo   = !empty($item['combo']['groups']);
      $hasDetails = $hasCombo || (!empty($item['customization']['groups'])) || !empty($item['component_customizations']);
      $toggleLabel = $hasCombo ? 'Itens do combo' : 'Ingredientes';

      $extraTotal = 0.0;
      if (!empty($item['customization']['groups'])) {
        foreach ($item['customization']['groups'] as $group) {
          foreach ($group['items'] as $opt) {
            $linePrice = 0.0;
            if (isset($opt['price'])) {
              $linePrice = (float)$opt['price'];
            } elseif (isset($opt['unit_price']) && isset($opt['qty'])) {
              $linePrice = (float)$opt['unit_price'] * (int)$opt['qty'];
            }
            $extraTotal += $linePrice;
          }
        }
      }

      if (!$hasDetails) {
        $extraTotal = 0.0;
      }

      $headerNote = $hasCombo ? 'Detalhes' : 'Incluso';
      if (!$hasCombo && !empty($item['customization']['groups'])) {
        if ($extraTotal > 0.009) {
          $headerNote = '+ ' . $formatBrl($extraTotal);
        } elseif ($extraTotal < -0.009) {
          $headerNote = '− ' . $formatBrl(abs($extraTotal));
        }
      }

      $autoOpen = !$hasCombo;
      $cardClasses = 'item' . ($autoOpen ? ' open' : '');
      $buttonClasses = 'toggle-row' . ($autoOpen ? ' open' : '');
      $extClasses = 'ext' . ($autoOpen ? ' open' : '');
      $ariaExpanded = $autoOpen ? 'true' : 'false';
    ?>
      <div class="<?= e($cardClasses) ?>" id="<?= e($cardId) ?>" data-item="<?= e($uid) ?>" aria-controls="<?= e($extId) ?>" aria-expanded="<?= $ariaExpanded ?>">
        <div class="avatar">
          <?php if (!empty($item['product']['image'])): ?>
            <img src="<?= e($uploadSrc($item['product']['image'] ?? null)) ?>" alt="<?= e($item['product']['name'] ?? '') ?>">
          <?php else: ?>
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#94A3B8" stroke-width="1.5" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3-6 8-6s8 2 8 6" stroke-linecap="round"/></svg>
          <?php endif; ?>
        </div>
        <div class="info">
          <div class="name"><?= e($item['product']['name'] ?? 'Produto') ?></div>
          <div class="price"><?= e($formatBrl($item['unit_price'])) ?></div>
        </div>
        <div class="qty" role="group" aria-label="Quantidade de <?= e($item['product']['name'] ?? 'Produto') ?>">
          <form method="post" action="<?= e($updateUrl) ?>">
            <input type="hidden" name="uid" value="<?= e($item['uid']) ?>">
            <button class="btn" type="submit" name="action" value="dec" aria-label="Diminuir">&minus;</button>
            <span class="val"><?= $item['qty'] ?></span>
            <button class="btn" type="submit" name="action" value="inc" aria-label="Aumentar">+</button>
          </form>
        </div>
        <?php if ($hasDetails): ?>
          <button class="<?= e($buttonClasses) ?>" type="button" data-target="<?= e($extId) ?>" aria-expanded="<?= $ariaExpanded ?>">
            <span class="toggle-left"><?= e($toggleLabel) ?></span>
            <span class="toggle-right">
              <span class="note"><?= e($headerNote) ?></span>
              <svg class="chev" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
          </button>
        <?php endif; ?>
      </div>

      <?php if ($hasDetails): ?>
        <div class="<?= e($extClasses) ?>" id="<?= e($extId) ?>">
          <?php if ($hasCombo): ?>
            <?php foreach ($item['combo']['groups'] as $gIndex => $group):
              $groupName = trim((string)($group['name'] ?? ''));
              $groupItems = $group['items'] ?? [];
              if (!$groupItems) continue;
            ?>
              <?php if ($groupName !== ''): ?>
                <div class="section-title"><?= e($groupName) ?></div>
              <?php endif; ?>
              <div class="linked-list">
                <?php foreach ($groupItems as $ii => $choice):
                  $delta = isset($choice['delta']) ? (float)$choice['delta'] : 0.0;
                  $simpleId = (int)($choice['simple_id'] ?? 0);
                  $componentCustomization = null;
                  if ($simpleId && !empty($item['component_customizations']) && isset($item['component_customizations'][$simpleId])) {
                    $componentCustomization = $item['component_customizations'][$simpleId]['customization'] ?? null;
                  }
                  $nestedId = 'nested-' . $uid . '-' . $gIndex . '-' . $ii;
                  $toggleNested = $componentCustomization && !empty($componentCustomization['groups']);
                ?>
                  <div class="linked<?= $toggleNested ? ' toggle' : '' ?>"<?= $toggleNested ? ' data-toggle-target="'.e($nestedId).'" aria-expanded="false"' : '' ?>>
                    <div class="l-ava">
                      <?php if (!empty($choice['image'])): ?>
                        <img src="<?= e($uploadSrc($choice['image'] ?? null)) ?>" alt="<?= e($choice['name'] ?? '') ?>">
                      <?php else: ?>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#94A3B8" stroke-width="1.5" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3-6 8-6s8 2 8 6" stroke-linecap="round"/></svg>
                      <?php endif; ?>
                    </div>
                    <div>
                      <div class="l-name"><?= e($choice['name'] ?? '') ?></div>
                      <div class="l-meta">
                        <?php if ($delta > 0): ?>
                          + <?= e($formatBrl($delta)) ?>
                        <?php elseif ($delta < 0): ?>
                          − <?= e($formatBrl(abs($delta))) ?>
                        <?php else: ?>
                          Sem custo
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="l-right">
                      <?php if ($toggleNested): ?>
                        Detalhes
                        <svg class="chev" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 6l6 6-6 6" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      <?php else: ?>
                        Incluso
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php if ($toggleNested): ?>
                    <div class="nested" id="<?= e($nestedId) ?>" style="display:none">
                      <?php foreach ($componentCustomization['groups'] as $cGroup): ?>
                        <div class="ing">
                          <div class="ing-name"><?= e($cGroup['name'] ?? 'Personalização') ?></div>
                          <div class="ing-meta">
                            <?php
                              $parts = [];
                              foreach ($cGroup['items'] as $opt) {
                                $label = $opt['name'] ?? '';
                                if (isset($opt['qty'])) {
                                  $label = $opt['qty'] . 'x ' . $label;
                                }
                                if (isset($opt['price']) && $opt['price'] > 0) {
                                  $label .= ' (' . $formatBrl($opt['price']) . ')';
                                }
                                $parts[] = $label;
                              }
                              echo e(implode(', ', $parts));
                            ?>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <?php if (!empty($item['customization']['groups'])): ?>
            <?php foreach ($item['customization']['groups'] as $group):
              $groupItems = $group['items'] ?? [];
              if (!$groupItems) continue;
              $groupType = $group['type'] ?? '';
              $showTitle = $hasCombo && !empty($group['name']);
            ?>
              <?php if ($showTitle): ?>
                <div class="section-title"><?= e($group['name']) ?></div>
              <?php endif; ?>
              <?php foreach ($groupItems as $opt):
                $name = (string)($opt['name'] ?? '');
                $qty  = isset($opt['qty']) ? (int)$opt['qty'] : null;
                if ($qty !== null && $qty > 1) {
                  $name = $qty . 'x ' . $name;
                }
                $linePrice = 0.0;
                if (isset($opt['price'])) {
                  $linePrice = (float)$opt['price'];
                } elseif ($qty !== null && $qty > 0 && isset($opt['unit_price'])) {
                  $linePrice = (float)$opt['unit_price'] * $qty;
                }
                $meta = 'Incluso';
                if ($linePrice > 0.009) {
                  $meta = '+ ' . $formatBrl($linePrice);
                } elseif ($linePrice < -0.009) {
                  $meta = '− ' . $formatBrl(abs($linePrice));
                }
              ?>
                <div class="ing">
                  <div class="ing-name"><?= e($name) ?></div>
                  <div class="ing-meta"><?= e($meta) ?></div>
                </div>
              <?php endforeach; ?>
            <?php endforeach; ?>
          <?php elseif (!$hasCombo): ?>
            <div class="ing">
              <div class="ing-name">Sem ingredientes adicionais</div>
              <div class="ing-meta">Padrão do produto</div>
            </div>
          <?php endif; ?>
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
      <div class="trow"><span class="label">Subtotal</span><span class="value" id="subtotal"><?= e($formatBrl($totals['subtotal'] ?? 0)) ?></span></div>
      <div class="line"></div>
      <div class="trow"><span class="label">Total</span><span class="value" id="total"><?= e($formatBrl($totals['total'] ?? 0)) ?></span></div>
    </div>

    <div class="footer"><button class="cta" type="button"<?= $items ? '' : ' disabled' ?>>Finalizar</button></div>
  </div>

  <script>
    document.querySelectorAll('.toggle-row').forEach(btn => {
      btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-target');
        const ext = targetId ? document.getElementById(targetId) : null;
        const item = btn.closest('.item');
        const open = !(ext && ext.classList.contains('open'));
        if (ext) {
          ext.classList.toggle('open', open);
        }
        if (item) {
          item.classList.toggle('open', open);
        }
        btn.classList.toggle('open', open);
        btn.setAttribute('aria-expanded', String(open));
      });
    });

    document.querySelectorAll('.linked.toggle').forEach(row => {
      const targetId = row.getAttribute('data-toggle-target');
      if (!targetId) return;
      const nested = document.getElementById(targetId);
      row.addEventListener('click', () => {
        if (!nested) return;
        const open = nested.style.display === 'none' || nested.style.display === '';
        nested.style.display = open ? 'block' : 'none';
        row.classList.toggle('open', open);
        row.setAttribute('aria-expanded', String(open));
        const chev = row.querySelector('.chev');
        if (chev) {
          chev.classList.toggle('open', open);
        }
      });
    });

    document.getElementById('coupon-btn')?.addEventListener('click', () => {
      const code = prompt('Digite seu cupom de desconto:');
      if (code) {
        alert('Cupom recebido: ' + code + ' (validação no backend).');
      }
    });
  </script>
</body>
</html>
