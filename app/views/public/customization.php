<?php
/** ============================================================================
 * app/views/public/customization.php
 * TELA DE PERSONALIZAÇÃO DO PRODUTO
 * ============================================================================
 */

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('price_br')) {
  function price_br($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
}

$slug    = $company['slug'] ?? '';
$pName   = $product['name'] ?? 'Produto';
$pId     = (int)($product['id'] ?? 0);

// lemos qty opcional da querystring (vinda do botão Personalizar)
$qtyGet = isset($_GET['qty']) ? max(1, min(99, (int)$_GET['qty'])) : null;

$groups = [];
foreach (($mods ?? []) as $gIndex => $g) {
  if (empty($g['items']) || !is_array($g['items'])) {
    continue;
  }

  $items = [];
  foreach ($g['items'] as $item) {
    if (!is_array($item)) {
      continue;
    }
    $items[] = $item;
  }

  if (!$items) {
    continue;
  }

  $g['items'] = array_values($items);
  $groups[] = $g;
}

// URLs
$backUrl = base_url($slug . '/produto/' . $pId);
$saveUrl = base_url($slug . '/produto/' . $pId . '/customizar/salvar');
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Personalizar — <?= e($pName) ?> | <?= e($company['name'] ?? '') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#fff; --card:#ffffff; --txt:#111827; --muted:#6b7280; --border:#e5e7eb; --chip:#f3f4f6;
    --ring:#fbbf24; --cta:#fbbf24; --cta-press:#f59e0b;
  }
  *{box-sizing:border-box}
  html,body{margin:0;background:var(--bg);color:var(--txt);font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial}
  .app{width:100%;margin:0 auto;min-height:100dvh;display:flex;flex-direction:column}
  @media (min-width:768px){ .app{max-width:375px} }

  header{position:sticky;top:0;background:#fff;z-index:5}
  .top{display:flex;align-items:center;gap:10px;padding:12px 12px 6px;border-bottom:1px solid var(--border)}
  .back{width:36px;height:36px;border:1px solid var(--border);border-radius:999px;background:#fff;display:grid;place-items:center;cursor:pointer;text-decoration:none}
  .title{font-weight:600}

  .container{padding:12px 16px 140px}
  .h1{font-size:32px;line-height:1.1;font-weight:800;letter-spacing:-.5px;margin:14px 0 8px}
  .sub{color:var(--muted);margin-top:-6px}

  .group-title{font-size:22px;line-height:1.2;font-weight:800;margin:22px 0 12px;letter-spacing:-.3px}

  .row{display:flex;align-items:center;gap:12px;padding:14px 12px;border-top:1px solid var(--border)}
  .row:first-of-type{border-top:0}
  .thumb{width:52px;height:52px;border-radius:999px;background:var(--chip);display:grid;place-items:center;overflow:hidden}
  .thumb img{width:100%;height:100%;object-fit:contain}
  .info{flex:1 1 auto}
  .name{font-weight:700}
  .price{color:#374151;font-size:14px;margin-top:2px}

  .stepper{display:flex;align-items:center;gap:10px;border:1px solid var(--border);border-radius:999px;padding:6px 10px;min-width:104px;justify-content:space-between}
  .st-btn{width:28px;height:28px;border-radius:999px;background:#fff;border:none;display:grid;place-items:center;cursor:pointer}
  .st-btn svg{width:18px;height:18px}
  .st-val{min-width:16px;text-align:center;font-weight:600}

  .radio-wrap{margin-left:auto}
  .radio-btn{width:28px;height:28px;border-radius:999px;border:2px solid var(--ring);display:grid;place-items:center;background:#fff;}
  .radio-btn.sel{background:var(--ring);border-color:var(--ring)}
  .radio-btn svg{width:16px;height:16px;color:#111;display:none}
  .radio-btn.sel svg{display:block}

  .footer{position:fixed;left:0;right:0;bottom:0;z-index:6;display:flex;height:64px;border-top:1px solid var(--border);background:#fff;}
  .btn-cancel,.btn-confirm{flex:1 1 50%;font-size:17px;font-weight:600;border:none;cursor:pointer}
  .btn-cancel{background:#fff;color:#111}
  .btn-confirm{background:var(--cta);color:#111;transition:background .2s}
  .btn-confirm:active{background:var(--cta-press)}

  .hint{color:#6b7280;font-size:12px;margin:6px 2px 12px;display:none;} /* deixado por compat, porém oculto */
</style>
</head>
<body>

<form class="app" method="post" action="<?= e($saveUrl) ?>">
  <header>
    <div class="top">
      <a class="back" href="<?= e($backUrl) ?>" aria-label="Voltar">
        <svg viewBox="0 0 24 24" fill="none"><path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      <div class="title"><?= e($pName) ?></div>
    </div>
  </header>

  <div class="container">

    <?php if (!empty($groups)): ?>
      <?php foreach ($groups as $gi => $g):
        $gName = (string)($g['name'] ?? ('Grupo '.($gi+1)));
        $gType = (string)($g['type'] ?? 'extra');
        $gMin  = (int)($g['min'] ?? 0); // mantidos para regras JS/servidor, mas não exibidos
        $gMax  = (int)($g['max'] ?? 0);
        $items = $g['items'] ?? [];
      ?>

        <!-- Título grande: nome do grupo -->
        <h2 class="group-title"><?= e($gName) ?></h2>

        <?php if ($gType === 'single'): ?>
          <?php
            $selectedIndex = 0;
            foreach ($items as $ii => $it) {
              if (!empty($it['default'])) { $selectedIndex = $ii; break; }
            }
          ?>
          <?php foreach ($items as $ii => $it):
            $isSel = ($ii === $selectedIndex);
            $img   = $it['img'] ?? null; ?>
            <div class="row radio" data-radio="g<?= (int)$gi ?>" data-id="<?= (int)$ii ?>">
              <div class="thumb">
                <img src="<?= e($img ?: 'https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+') ?>" alt="" onerror="this.src='https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+'">
              </div>
              <div class="info">
                <?php $optName = $it['name'] ?? $it['label'] ?? ('Opção '.($ii+1)); ?>
                <div class="name"><?= e($optName) ?></div>
                <?php $sale = isset($it['sale_price']) ? (float)$it['sale_price'] : 0.0; ?>
                <?php if ($sale > 0): ?>
                  <div class="price"><?= price_br($sale) ?></div>
                <?php endif; ?>
              </div>
              <div class="radio-wrap">
                <div class="radio-btn <?= $isSel?'sel':'' ?>" role="radio" aria-checked="<?= $isSel?'true':'false' ?>" tabindex="0">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          <input type="hidden" name="custom_single[<?= (int)$gi ?>]" id="f_single_<?= (int)$gi ?>" value="<?= (int)$selectedIndex ?>">

        <?php else: ?>
          <div class="list" aria-label="<?= e($gName) ?>">
            <?php foreach ($items as $ii => $it):
              $img   = $it['img'] ?? null;
              $min   = isset($it['min']) ? (int)$it['min'] : 0;
              $max   = isset($it['max']) ? (int)$it['max'] : 5;
              $qty   = isset($it['qty']) ? (int)$it['qty'] : (!empty($it['default']) ? (int)($it['default_qty'] ?? $min) : $min);
              $sale = isset($it['sale_price']) ? (float)$it['sale_price'] : (float)($it['delta'] ?? 0);
            ?>
              <div class="row" data-id="<?= (int)$ii ?>" data-min="<?= $min ?>" data-max="<?= $max ?>">
                <div class="thumb">
                  <img src="<?= e($img ?: 'https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+') ?>" alt="" onerror="this.src='https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+'">
                </div>
                <div class="info">
                  <?php $itemName = $it['name'] ?? $it['label'] ?? ('Item '.($ii+1)); ?>
                  <div class="name"><?= e($itemName) ?></div>
                  <?php if ($sale > 0): ?>
                    <div class="price"><?= price_br($sale) ?></div>
                  <?php endif; ?>
                </div>
                <div class="stepper">
                  <button class="st-btn" type="button" data-act="dec" aria-label="Diminuir">
                    <svg viewBox="0 0 24 24"><path d="M5 12h14" stroke="#111" stroke-width="2" stroke-linecap="round"/></svg>
                  </button>
                  <div class="st-val" data-role="val"><?= $qty ?></div>
                  <button class="st-btn" type="button" data-act="inc" aria-label="Aumentar">
                    <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="#111" stroke-width="2" stroke-linecap="round"/></svg>
                  </button>
                </div>
                <input type="hidden" name="custom_qty[<?= (int)$gi ?>][<?= (int)$ii ?>]" value="<?= $qty ?>">
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php endforeach; ?>
    <?php endif; ?>

    <input type="hidden" name="product_id" value="<?= $pId ?>">
    <?php if ($qtyGet !== null): ?>
      <input type="hidden" name="qty" value="<?= (int)$qtyGet ?>">
    <?php endif; ?>
  </div>

  <div class="footer">
    <button type="button" class="btn-cancel" onclick="window.location.href='<?= e($backUrl) ?>'">Cancelar</button>
    <button type="submit" class="btn-confirm">Confirmar</button>
  </div>
</form>

<script>
  const clamp = (n,min,max)=> Math.max(min, Math.min(max, n));

  // Stepper (linhas com data-min/max)
  document.querySelectorAll('.row').forEach(row=>{
    const min = parseInt(row.dataset.min || '0',10);
    const max = parseInt(row.dataset.max || '99',10);
    const valEl = row.querySelector('.st-val');
    const hidden = row.querySelector('input[type="hidden"]');

    if(!valEl || !row.querySelector('.st-btn')) return;

    row.querySelectorAll('.st-btn').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const act = btn.dataset.act;
        const cur = parseInt(valEl.textContent || '0', 10);
        const next = clamp(cur + (act==='inc'?1:-1), min, max);
        valEl.textContent = String(next);
        if(hidden) hidden.value = String(next);
      });
    });
  });

  // Grupos 'single' (rádio)
  document.querySelectorAll('.row.radio').forEach(row=>{
    const groupKey = row.getAttribute('data-radio');
    const id       = row.getAttribute('data-id');
    const btn      = row.querySelector('.radio-btn');
    const hidden   = document.getElementById('f_single_' + groupKey.replace('g',''));

    const mark = ()=> {
      document.querySelectorAll('.row.radio[data-radio="'+groupKey+'"] .radio-btn').forEach(b=>{
        b.classList.remove('sel'); b.setAttribute('aria-checked','false');
      });
      btn.classList.add('sel'); btn.setAttribute('aria-checked','true');
      if (hidden) hidden.value = String(id);
    };
    row.addEventListener('click', mark);
    btn.addEventListener('keydown', (e)=>{ if(e.key==='Enter' || e.key===' '){ e.preventDefault(); mark(); }});
  });
</script>
</body>
</html>
