<?php
/** ============================================================================
 * app/views/public/product.php
 * Página pública do produto
 * ============================================================================ */

if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('price_br')) { function price_br($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); } }

/** Variáveis básicas */
$company = $company ?? [];
$product = $product ?? [];
$comboGroups = $comboGroups ?? null;
$mods = $mods ?? [];
$hasCustomization = isset($hasCustomization) ? (bool)$hasCustomization : (!empty($mods));

$slug  = (string)($company['slug'] ?? '');
$pId   = (int)($product['id'] ?? 0);
$homeUrl = base_url($slug !== '' ? $slug : '');

/** É combo? */
$isCombo = (isset($product['type']) && $product['type'] === 'combo' && !empty($comboGroups));

/** URLs (ajuste às suas rotas reais) */
$customizeBase = base_url($slug . '/produto/' . $pId . '/customizar');          // GET (tela de customização)
$addToCartUrl  = base_url($slug . '/orders/add');                                // POST (adiciona ao carrinho)
$uploadPlaceholder = base_url('uploads/logo-placeholder.png');
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
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
  @media (min-width:768px){ .app{max-width:375px} }

  .hero-wrap{position:relative;padding:56px 0 120px;display:flex;align-items:flex-end;justify-content:center;min-height:360px}
  .nav-btn{position:absolute;top:12px;left:12px;z-index:3;width:36px;height:36px;border-radius:999px;border:1px solid var(--border);
    background:var(--card);display:grid;place-items:center;box-shadow:0 2px 6px rgba(0,0,0,.08);cursor:pointer}
  .hero-visual{position:absolute;top:72px;left:50%;transform:translateX(-50%);width:min(90%,390px);height:360px;
    border-radius:28px;background:radial-gradient(140% 90% at 75% 20%, #fff 0%, #eef2f5 55%, #e7ebee 100%);
    overflow:hidden;display:flex;align-items:center;justify-content:center;box-shadow:0 18px 38px rgba(15,23,42,0.08);z-index:0;
    pointer-events:none}
  .hero-visual img{width:100%;height:100%;object-position:center;object-fit:var(--hero-fit, contain);
    filter:drop-shadow(0 18px 34px rgba(0,0,0,.22))}
  .hero-visual[data-fit="cover"] img{object-fit:cover}
  .hero-toggle{position:absolute;top:20px;right:16px;z-index:2;display:flex;gap:6px;background:rgba(255,255,255,0.82);
    border:1px solid rgba(15,23,42,0.08);padding:6px 10px;border-radius:999px;font-size:12px;font-weight:600;color:#1f2937;
    backdrop-filter:blur(8px)}
  .hero-toggle button{appearance:none;border:none;background:transparent;padding:4px 10px;border-radius:999px;cursor:pointer;
    color:inherit;font:inherit}
  .hero-toggle button[aria-pressed="true"]{background:#111827;color:#fff}

  .card{position:relative;background:var(--card);border-radius:26px 26px 0 0;margin-top:-8px;padding:16px 16px 8px;box-shadow:0 -1px 0 var(--border);display:flex;flex-direction:column;gap:16px;z-index:1}
  .brand{display:flex;align-items:center;gap:8px;color:#374151;font-size:13px}
  .brand .dot{width:18px;height:18px;border-radius:999px;background:#ffb703;display:grid;place-items:center;color:#7c2d12;font-weight:800;font-size:11px}
  h1{margin:2px 0 0;font-size:20px;line-height:1.25;font-weight:700}
  .price-row{display:flex;align-items:center;justify-content:space-between;margin-top:4px}
  .price{display:flex;flex-direction:column;gap:4px}
  .price-single{font-size:22px;font-weight:800}
  .price-original{font-size:15px;font-weight:600;color:#9ca3af;text-decoration:line-through}
  .price-current-row{display:flex;align-items:baseline;gap:10px}
  .price-current{font-size:24px;font-weight:800}
  .price-discount{font-size:16px;font-weight:700;color:#059669}

  .qty{display:flex;align-items:center;gap:8px}
  .btn-circ{width:40px;height:40px;border-radius:12px;border:1px solid var(--border);background:var(--card);display:grid;place-items:center;cursor:pointer}
  .btn-circ svg{width:18px;height:18px}
  .btn-red{background:var(--accent);border-color:transparent;color:#fff;box-shadow:0 6px 16px rgba(239,68,68,.28)}
  .qty-badge{min-width:32px;height:32px;border-radius:999px;border:1px solid var(--border);display:grid;place-items:center;font-weight:700}

  .section h3{margin:8px 0 6px;color:var(--muted);font-size:12px;letter-spacing:.08em;text-transform:uppercase}
  .body{font-size:14px;color:#374151;line-height:1.5}

  .customize-wrap{background:var(--card)}
  .customize{padding:24px 16px}
  .btn-outline{width:100%;background:#fff;color:#111;border:1px solid #d8d8d8;border-radius:12px;padding:18px;font-size:18px;font-weight:500;
    display:flex;align-items:center;justify-content:space-between;text-decoration:none}
  .btn-outline:active{background:#f9f9f9}
  .btn-outline .chev{display:grid;place-items:center}
  .btn-outline .chev svg{width:22px;height:22px}

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

  .footer{position:sticky;bottom:0;background:var(--card);padding:12px 16px 18px;border-top:1px solid var(--border);box-shadow:0 -10px 40px rgba(0,0,0,.06)}
  .cta{width:100%;border:none;border-radius:16px;padding:14px 16px;background:var(--cta);color:#1f2937;font-weight:800;font-size:16px;cursor:pointer}
  .cta:active{background:var(--cta-press)}
</style>
</head>
<body>
<div class="app">

  <div class="hero-wrap">
    <a class="nav-btn" href="<?= e($homeUrl) ?>" aria-label="Voltar">
      <svg viewBox="0 0 24 24" width="24" height="24" fill="none">
        <path d="M15 19l-7-7 7-7"
              stroke="#111827"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              transform="scale(0.7) translate(5 5)"></path>
      </svg>
    </a>

    <?php
      $imagePath = trim((string)($product['image'] ?? ''));
      $imageFile = basename($imagePath);
      if ($imageFile === '' || $imageFile === '.' || $imageFile === '..') {
        $imgSrc = $uploadPlaceholder;
      } else {
        $imgSrc = base_url('uploads/' . $imageFile);
      }
      $imgAlt = $imagePath !== '' ? ($product['name'] ?? 'Produto') : 'Imagem do produto';
    ?>
    <div class="hero-toggle" role="group" aria-label="Modo de exibição da imagem">
      <button type="button" data-fit="contain" aria-pressed="true">Contain</button>
      <button type="button" data-fit="cover" aria-pressed="false">Cover</button>
    </div>
    <div class="hero-visual" data-fit="contain">
      <img src="<?= e($imgSrc) ?>" alt="<?= e($imgAlt) ?>">
    </div>
  </div>

  <main class="card" role="main">
    <div class="brand">
      <h1><?= e($product['name'] ?? '') ?></h1>
    </div>

    <div class="price-row">
      <div class="price">
        <?php
          $price = (float)($product['price'] ?? 0);
          $rawPromo = $product['promo_price'] ?? null;

          // Parse robusto de preço promocional (suporta "1.234,56" e "1234.56")
          $promo = null;
          if ($rawPromo !== null && $rawPromo !== '') {
            $promoStr = is_array($rawPromo) ? reset($rawPromo) : $rawPromo;
            $promoStr = trim((string)$promoStr);
            if ($promoStr !== '') {
              $promoStr = str_replace(' ', '', $promoStr);
              if (strpos($promoStr, ',') !== false && strpos($promoStr, '.') !== false) {
                $promoStr = str_replace('.', '', $promoStr);
              }
              $promoStr = str_replace(',', '.', $promoStr);
              if (is_numeric($promoStr)) {
                $promo = (float)$promoStr;
              }
            }
          }

          $hasPromo = $price > 0 && $promo !== null && $promo > 0 && $promo < $price;

          if ($hasPromo):
            $discount = $price > 0 ? (int)floor((($price - $promo) / $price) * 100) : 0;
        ?>
          <div class="price-original"><?= price_br($price) ?></div>
          <div class="price-current-row">
            <span class="price-current"><?= price_br($promo) ?></span>
            <?php if ($discount > 0): ?>
              <span class="price-discount"><?= $discount ?>% OFF</span>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="price-single"><?= price_br($price) ?></div>
        <?php endif; ?>
      </div>

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

    <?php if (!empty($product['description'])): ?>
    <section class="section">
      <h3>Sobre</h3>
      <p class="body"><?= nl2br(e($product['description'])) ?></p>
    </section>
    <?php endif; ?>

  </main>

  <!-- Botão PERSONALIZAR: visível se houver personalização disponível -->
  <?php if ($hasCustomization): ?>
  <div class="customize-wrap">
    <div class="customize">
      <?php
        // Construímos a URL com qty atual via JS; deixamos href “limpo” como fallback
        $customizeUrl = $customizeBase;
      ?>
      <a class="btn-outline" id="btn-customize" href="<?= e($customizeUrl) ?>">
        <span>
          <strong>Personalizar</strong>
          <small style="display:block;color:#6b7280;font-size:12px;margin-top:6px">Escolha adicionais ou ajuste seu pedido.</small>
        </span>
        <span class="chev" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none"><path d="M9 5l7 7-7 7" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </a>
    </div>
  </div>
  <?php endif; ?>

  <!-- ===== BLOCO DE COMBO ===== -->
  <?php if ($isCombo): ?>
  <section class="combo" aria-label="Montar combo">
    <?php foreach ($comboGroups as $gi => $group): ?>
      <?php $gname = (string)($group['name'] ?? ('Etapa '.($gi+1))); $items = $group['items'] ?? []; ?>
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
                <?php
                  $comboImgFile = basename($img);
                  if ($comboImgFile === '' || $comboImgFile === '.' || $comboImgFile === '..') {
                    $comboImg = $uploadPlaceholder;
                  } else {
                    $comboImg = base_url('uploads/' . $comboImgFile);
                  }
                ?>
                <img src="<?= e($comboImg) ?>" alt="<?= e($opt['name'] ?? '') ?>">
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
  // ===== Hero fit toggle =====
  const hero = document.querySelector('.hero-visual');
  const toggleButtons = document.querySelectorAll('.hero-toggle button');
  if (hero && toggleButtons.length) {
    toggleButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const fit = btn.getAttribute('data-fit') === 'cover' ? 'cover' : 'contain';
        hero.setAttribute('data-fit', fit);
        toggleButtons.forEach(control => {
          control.setAttribute('aria-pressed', control === btn ? 'true' : 'false');
        });
      });
    });
  }

  // ===== Qty stepper =====
  const qval   = document.getElementById('qval');
  const qfield = document.getElementById('qtyField');
  const minus  = document.getElementById('qminus');
  const plus   = document.getElementById('qplus');
  const clamp  = n => Math.max(1, Math.min(99, n|0));
  function setQty(n){ const v = clamp(n); qval.textContent = String(v); qfield.value = String(v); }
  minus?.addEventListener('click', ()=> setQty(parseInt(qval.textContent,10)-1));
  plus?.addEventListener('click', ()=> setQty(parseInt(qval.textContent,10)+1));
  function attach(e){ setQty(parseInt(qval.textContent,10)||1); return true; }

  // Botão Personalizar: acrescenta qty atual na URL (opcional)
  const btnCust = document.getElementById('btn-customize');
  btnCust?.addEventListener('click', (ev)=>{
    // deixa o link normal funcionar; apenas ajusta o href antes
    const base = btnCust.getAttribute('href') || '<?= e($customizeBase) ?>';
    const qty  = parseInt(qval?.textContent||'1',10) || 1;
    const url  = new URL(base, window.location.origin);
    url.searchParams.set('qty', String(qty));
    btnCust.setAttribute('href', url.toString());
  });

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
