<?php
/** ============================================================================
 * app/views/public/product.php
 * ----------------------------------------------------------------------------
 * Página pública do produto
 *
 * Controller deve fornecer:
 *   $company (array) -> ['slug'=>..., 'name'=>...]
 *   $product (array) -> ['id'=>..., 'name'=>..., 'price'=>..., 'promo_price'=>?, 'image'=>?, 'type'=>('simple'|'combo')]
 *   $simpleMods (array|null) -> ['items'=>[ ['name','delta','default', 'img', 'min','max','qty'] ]]
 *   $comboGroups (array|null) -> [
 *       ['name'=>'Bebida','items'=>[
 *           ['id'=>123,'name'=>'Coca 350','image'=>'/img/coca.png','delta'=>0,'default'=>true],
 *           ...
 *       ]],
 *       ...
 *   ]
 *   $ingredients (array|null) -> fallback legado: [['name'=>'...'], ...]
 * ============================================================================ */

/** Helpers */
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('price_br')) {
  function price_br($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
}

/** Variáveis básicas */
$company = $company ?? [];
$product = $product ?? [];
$simpleMods = $simpleMods ?? null;
$comboGroups = $comboGroups ?? null;
$ingredients = $ingredients ?? [];

$slug  = (string)($company['slug'] ?? '');
$pId   = (int)($product['id'] ?? 0);

/** Ingredientes a exibir (preferir os marcados como default em $simpleMods) */
$displayIngredients = [];
if (!empty($simpleMods['items']) && is_array($simpleMods['items'])) {
  foreach ($simpleMods['items'] as $it) {
    if (!empty($it['default'])) {
      $displayIngredients[] = (string)($it['name'] ?? '');
    }
  }
}
if (empty($displayIngredients) && !empty($ingredients)) { // fallback legado
  foreach ($ingredients as $ing) {
    $displayIngredients[] = (string)($ing['name'] ?? '');
  }
}

/** É combo? */
$isCombo = (isset($product['type']) && $product['type'] === 'combo' && !empty($comboGroups));

/** URLs (ajuste às suas rotas reais) */
$customizeUrl = base_url($slug . '/produto/' . $pId . '/customizar');          // GET (tela de customização)
$addToCartUrl = base_url($slug . '/orders/add');                                // POST (adiciona ao carrinho)
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title><?= e($product['name'] ?? 'Produto') ?> — <?= e($company['name'] ?? '') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#f3f4f6; --card:#fff; --txt:#0f172a; --muted:#6b7280;
    --border:#e5e7eb; --accent:#ef4444; --ring:#fbbf24;
    --cta:#f59e0b; --cta-press:#d97706;
  }
  *{box-sizing:border-box}
  html,body{margin:0;background:var(--bg);color:var(--txt);font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial}
  .app{width:100%;margin:0 auto;min-height:100dvh;display:flex;flex-direction:column}
  @media (min-width:768px){ .app{max-width:375px} } /* tablet/desktop limitam a 375px */

  /* Hero */
  .hero-wrap{position:relative}
  .nav-btn{
    position:absolute;top:12px;left:12px;z-index:2;width:36px;height:36px;border-radius:999px;border:1px solid var(--border);
    background:var(--card);display:grid;place-items:center;box-shadow:0 2px 6px rgba(0,0,0,.08);cursor:pointer
  }
  .hero{width:100%;height:360px;background:radial-gradient(140% 90% at 75% 20%, #fff 0%, #eef2f5 55%, #e7ebee 100%);display:grid;place-items:center}
  .hero img{width:100%;height:100%;object-fit:contain;filter: drop-shadow(0 18px 34px rgba(0,0,0,.25))}

  /* Card */
  .card{background:var(--card);border-radius:26px 26px 0 0;margin-top:-8px;padding:16px 16px 8px;box-shadow:0 -1px 0 var(--border);display:flex;flex-direction:column;gap:16px}
  .brand{display:flex;align-items:center;gap:8px;color:#374151;font-size:13px}
  .brand .dot{width:18px;height:18px;border-radius:999px;background:#ffb703;display:grid;place-items:center;color:#7c2d12;font-weight:800;font-size:11px}
  h1{margin:2px 0 0;font-size:20px;line-height:1.25;font-weight:700}
  .price-row{display:flex;align-items:center;justify-content:space-between;margin-top:4px}
  .price{font-size:22px;font-weight:800}

  /* Stepper */
  .qty{display:flex;align-items:center;gap:8px}
  .btn-circ{width:40px;height:40px;border-radius:12px;border:1px solid var(--border);background:var(--card);display:grid;place-items:center;cursor:pointer}
  .btn-circ svg{width:18px;height:18px}
  .btn-red{background:var(--accent);border-color:transparent;color:#fff;box-shadow:0 6px 16px rgba(239,68,68,.28)}
  .qty-badge{min-width:32px;height:32px;border-radius:999px;border:1px solid var(--border);display:grid;place-items:center;font-weight:700}

  .section h3{margin:8px 0 6px;color:var(--muted);font-size:12px;letter-spacing:.08em;text-transform:uppercase}
  .body{font-size:14px;color:#374151;line-height:1.5}

  /* Ingredientes (bolinhas pretas) */
  .checklist{list-style:none;margin:8px 0 0;padding:0;display:grid;gap:10px}
  .checklist li{display:flex;align-items:flex-start;gap:10px;font-size:14px}
  .bullet{width:7px;height:7px;border-radius:999px;background:#111;margin-top:7px;flex:0 0 7px}

  /* Botão "Personalizar" */
  .customize-wrap{background:var(--card)}
  .customize{padding:24px 16px}
  .btn-outline{
    width:100%;background:#fff;color:#111;border:1px solid #d8d8d8;border-radius:12px;padding:18px;font-size:18px;font-weight:500;
    display:flex;align-items:center;justify-content:space-between;text-decoration:none
  }
  .btn-outline:active{background:#f9f9f9}
  .btn-outline .chev{display:grid;place-items:center}
  .btn-outline .chev svg{width:22px;height:22px}

  /* Combo (grupos) */
  .combo{background:var(--card);padding:8px 0 8px}
  .combo .group{padding:10px 16px 0}
  .combo h2{font-size:32px;line-height:1.1;margin:12px 0 8px;font-weight:800;letter-spacing:-0.5px}
  .choice-row{display:flex;gap:18px;overflow-x:auto;padding:12px 12px 18px;scroll-snap-type:x mandatory}
  .choice-row::-webkit-scrollbar{height:0}
  .choice{width:128px;flex:0 0 auto;scroll-snap-align:start;text-align:center}
  .ring{width:100px;height:100px;border-radius:999px;border:3px solid var(--border);background:#fff;display:grid;place-items:center;position:relative;margin:0 auto}
  .ring img{width:86px;height:86px;object-fit:contain}
  .mark{position:absolute;right:-6px;top:-6px;width:24px;height:24px;background:var(--ring);border-radius:999px;display:none;place-items:center;box-shadow:0 1px 0 rgba(0,0,0,.06)}
  .mark svg{width:14px;height:14px;color:#111}
  .choice.sel .ring{border-color:var(--ring)}
  .choice.sel .mark{display:grid}
  .choice-name{margin-top:10px;font-weight:700;font-size:15px;color:#1f2937}
  .choice-price{margin-top:4px;color:#374151;font-size:14px}

  /* Footer CTA */
  .footer{position:sticky;bottom:0;background:var(--card);padding:12px 16px 18px;border-top:1px solid var(--border);box-shadow:0 -10px 40px rgba(0,0,0,.06)}
  .cta{width:100%;border:none;border-radius:16px;padding:14px 16px;background:var(--cta);color:#1f2937;font-weight:800;font-size:16px;cursor:pointer}
  .cta:active{background:var(--cta-press)}
</style>
</head>
<body>
<div class="app">

  <!-- Voltar + Hero -->
  <div class="hero-wrap">
    <button class="nav-btn" onclick="history.back()" aria-label="Voltar">
      <svg viewBox="0 0 24 24" fill="none"><path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
    <div class="hero">
      <?php
      $img = (string)($product['image'] ?? '');
      $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
      if ($img && @is_file($docRoot . '/' . ltrim($img,'/'))): ?>
        <img src="<?= e($img) ?>" alt="<?= e($product['name'] ?? 'Produto') ?>">
      <?php else: ?>
        <img src="<?= e(base_url('assets/logo-placeholder.png')) ?>" alt="Imagem do produto">
      <?php endif; ?>
    </div>
  </div>

  <!-- Card de informações -->
  <main class="card" role="main">
    <div class="brand">
      <span class="dot"><?= e(strtoupper(mb_substr($company['name'] ?? 'M', 0, 1))) ?></span>
      <span><?= e($company['name'] ?? '') ?></span>
    </div>

    <h1><?= e($product['name'] ?? '') ?></h1>

    <div class="price-row">
      <div class="price">
        <?php
          $price = (float)($product['price'] ?? 0);
          $promo = (float)($product['promo_price'] ?? 0);
          echo ($promo && $promo < $price) ? price_br($promo) : price_br($price);
        ?>
      </div>

      <!-- Stepper de quantidade -->
      <div class="qty" aria-label="Selecionar quantidade">
        <button type="button" class="btn-circ" id="qminus" aria-label="Diminuir">
          <svg viewBox="0 0 24 24"><path d="M5 12h14" stroke="#111827" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
        <div class="qty-badge" id="qval">1</div>
        <button type="button" class="btn-circ btn-red" id="qplus" aria-label="Aumentar">
          <svg viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </div>
    </div>

    <!-- Sobre -->
    <?php if (!empty($product['description'])): ?>
    <section class="section">
      <h3>Sobre</h3>
      <p class="body"><?= nl2br(e($product['description'])) ?></p>
    </section>
    <?php endif; ?>

    <!-- Ingredientes (padrões, com bolinhas pretas) -->
    <?php if (!empty($displayIngredients)): ?>
    <section class="section">
      <h3>Ingredientes</h3>
      <ul class="checklist">
        <?php foreach ($displayIngredients as $ing): if (!$ing) continue; ?>
          <li><span class="bullet" aria-hidden="true"></span><span><?= e($ing) ?></span></li>
        <?php endforeach; ?>
      </ul>
      <!-- [ADMIN → “Personalização / Ingredientes (simplificado)” → marcar “Padrão” para aparecer aqui] -->
    </section>
    <?php endif; ?>
  </main>

  <!-- Botão PERSONALIZAR: só mostra se houver itens em simpleMods -->
  <?php if (!empty($simpleMods['items'])): ?>
  <div class="customize-wrap">
    <div class="customize">
      <a class="btn-outline" href="<?= e($customizeUrl) ?>">
        <span>Personalizar ingredientes</span>
        <span class="chev" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none"><path d="M9 5l7 7-7 7" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </a>
      <!-- [ADMIN → “Personalização / Ingredientes (simplificado)” → ativar e cadastrar itens] -->
    </div>
  </div>
  <?php endif; ?>

  <!-- ===== BLOCO DE COMBO (real, condicional) ===== -->
  <?php if ($isCombo): ?>
  <section class="combo" aria-label="Montar combo">
    <?php foreach ($comboGroups as $gi => $group): ?>
      <?php
        $gname = (string)($group['name'] ?? ('Etapa '.($gi+1)));
        $items = $group['items'] ?? [];
      ?>
      <div class="group">
        <h2><?= e($gname) ?></h2>
        <div class="choice-row" data-group-index="<?= (int)$gi ?>">
          <?php foreach ($items as $ii => $opt): ?>
            <?php
              $isDefault = !empty($opt['default']);
              $img = (string)($opt['image'] ?? '');
              $optPrice = (isset($opt['delta']) ? (float)$opt['delta'] : 0.0);
              $priceLabel = $optPrice != 0.0 ? price_br($optPrice) : 'Incluído';
            ?>
            <div class="choice <?= $isDefault ? 'sel' : '' ?>" data-group="<?= (int)$gi ?>" data-id="<?= (int)($opt['id'] ?? 0) ?>">
              <button type="button" class="ring" aria-pressed="<?= $isDefault ? 'true':'false' ?>">
                <?php if ($img): ?>
                  <img src="<?= e($img) ?>" alt="<?= e($opt['name'] ?? '') ?>">
                <?php else: ?>
                  <img src="<?= e(base_url('assets/logo-placeholder.png')) ?>" alt="">
                <?php endif; ?>
                <span class="mark" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
              </button>
              <div class="choice-name"><?= e($opt['name'] ?? '') ?></div>
              <div class="choice-price"><?= e($priceLabel) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>
  <!-- ===== FIM BLOCO COMBO ===== -->

  <!-- CTA / Form de Add-to-cart -->
  <form class="footer" method="post" action="<?= e($addToCartUrl) ?>" onsubmit="return attach(event)">
    <input type="hidden" name="product_id" value="<?= $pId ?>">
    <input type="hidden" name="qty" id="qtyField" value="1">

    <?php if ($isCombo): ?>
      <?php foreach ($comboGroups as $gi => $group): ?>
        <?php
          $selId = null;
          foreach (($group['items'] ?? []) as $opt) { if (!empty($opt['default'])) { $selId = (int)$opt['id']; break; } }
          if ($selId === null && !empty($group['items'][0]['id'])) $selId = (int)$group['items'][0]['id'];
        ?>
        <input type="hidden" name="combo[<?= (int)$gi ?>]" id="combo_field_<?= (int)$gi ?>" value="<?= $selId !== null ? (int)$selId : '' ?>">
      <?php endforeach; ?>
    <?php endif; ?>

    <button class="cta" type="submit">Adicionar à Sacola</button>
  </form>
</div>

<script>
  // ===== Qty stepper =====
  const qval   = document.getElementById('qval');
  const qfield = document.getElementById('qtyField');
  const minus  = document.getElementById('qminus');
  const plus   = document.getElementById('qplus');
  const clamp  = n => Math.max(1, Math.min(99, n|0));
  function setQty(n){ const v = clamp(n); qval.textContent = String(v); qfield.value = String(v); }
  minus?.addEventListener('click', ()=> setQty(parseInt(qval.textContent,10)-1));
  plus ?.addEventListener('click', ()=> setQty(parseInt(qval.textContent,10)+1));
  function attach(e){ setQty(parseInt(qval.textContent,10)||1); return true; }

  // ===== Seleção por grupo (Combo) =====
  document.querySelectorAll('.choice-row').forEach(row=>{
    const gi = row.dataset.groupIndex;
    const hidden = document.getElementById('combo_field_' + gi);
    const items = row.querySelectorAll('.choice');

    items.forEach(item=>{
      const ring = item.querySelector('.ring');
      ring?.addEventListener('click', ()=>{
        items.forEach(i=>{
          i.classList.remove('sel');
          i.querySelector('.ring')?.setAttribute('aria-pressed','false');
        });
        item.classList.add('sel');
        ring.setAttribute('aria-pressed','true');
        if (hidden) hidden.value = item.dataset.id || '';
      });
    });
  });
</script>
</body>
</html>
