<?php
// ===== product.php (bolinhas pretas + hero full-bleed) =====
// Variáveis vindas do controller
$product     = $product     ?? [];
$company     = $company     ?? [];
$ingredients = $ingredients ?? [];

$imagePath = $product['image'] ?? null;
$imageUrl  = ($imagePath && is_file($imagePath))
            ? base_url($imagePath)
            : 'https://dummyimage.com/1200x800/f2f4f7/9aa1a9.png&text=Sua+imagem+aqui';
$initial = strtoupper(mb_substr($company['name'] ?? '', 0, 1));
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title><?= e($product['name'] ?? '') ?> - <?= e($company['name'] ?? '') ?></title>
<meta name="description" content="<?= e($product['description'] ?? '') ?>">
<meta property="og:title" content="<?= e($product['name'] ?? '') ?> - <?= e($company['name'] ?? '') ?>">
<meta property="og:description" content="<?= e($product['description'] ?? '') ?>">
<meta property="og:image" content="<?= e($imageUrl) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#f3f4f6;
    --card:#ffffff;
    --txt:#0f172a;
    --muted:#6b7280;
    --border:#ececec;
    --accent:#ef4444;
    --cta:#f59e0b;
    --cta-press:#d97706;
  }
  *{box-sizing:border-box}
  html,body{margin:0;background:var(--bg);color:var(--txt);font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial}
  .app{max-width:375px;margin:0 auto;min-height:100dvh;display:flex;flex-direction:column}

  /* ===== HERO FULL-BLEED (ocupa todo o topo) ===== */
  .hero-wrap{position:relative}
  .nav-btn{position:absolute;top:12px;left:12px;width:36px;height:36px;border-radius:999px;border:1px solid var(--border);background:var(--card);display:grid;place-items:center;box-shadow:0 2px 6px rgba(0,0,0,.08);cursor:pointer;z-index:2}
  .hero{
    width:100%;
    height:360px;                 /* ajuste a altura conforme seu print */
    background:
      radial-gradient(140% 90% at 75% 20%, #fff 0%, #eef2f5 55%, #e7ebee 100%); /* fundo igual ao print */
    display:grid;place-items:center;
  }
  .hero img{
    width:100%; height:100%;
    object-fit:contain;           /* faz a imagem ocupar toda a área sem distorcer */
    filter: drop-shadow(0 18px 34px rgba(0,0,0,.25));
  }

  /* ===== CARD BRANCO ===== */
  .card{background:var(--card);border-radius:26px 26px 0 0;margin-top:-60px;padding:16px 16px 24px;box-shadow:0 -1px 0 var(--border);display:flex;flex-direction:column;gap:16px;position:relative;z-index:1}
  .brand{display:flex;align-items:center;gap:8px;color:#374151;font-size:13px}
  .brand .dot{width:18px;height:18px;border-radius:999px;background:#ffb703;display:grid;place-items:center;color:#7c2d12;font-weight:800;font-size:11px;box-shadow:0 1px 0 rgba(0,0,0,.06)}
  h1{margin:2px 0 0;font-size:20px;line-height:1.25;font-weight:700}

  .price-row{display:flex;align-items:center;justify-content:space-between;margin-top:4px}
  .price{font-size:22px;font-weight:800}

  /* Stepper */
  .qty{display:flex;align-items:center;gap:8px}
  .btn-circ{width:40px;height:40px;border-radius:12px;border:1px solid var(--border);background:var(--card);display:grid;place-items:center;cursor:pointer;box-shadow:0 1px 2px rgba(0,0,0,.04)}
  .btn-circ svg{width:18px;height:18px}
  .btn-red{background:var(--accent);border-color:transparent;color:#fff;box-shadow:0 6px 16px rgba(239,68,68,.32)}
  .qty-badge{min-width:32px;height:32px;border-radius:999px;border:1px solid var(--border);display:grid;place-items:center;font-weight:700}

  .section h3{margin:8px 0 6px;color:var(--muted);font-size:12px;letter-spacing:.08em;text-transform:uppercase}
  .body{font-size:14px;color:#374151;line-height:1.5}

  /* ===== BOLINHAS PRETAS (sem SVG) ===== */
  .checklist{list-style:none;margin:8px 0 0;padding:0;display:grid;gap:10px}
  .checklist li{display:flex;align-items:flex-start;gap:10px;font-size:14px}
  .bullet{
    width:7px;height:7px;border-radius:999px;background:#111; /* bolinha preta */
    margin-top:7px;flex:0 0 7px;
  }

  .customize-wrap{background:var(--card)}
  .customize{padding:24px 16px}
  .btn-outline{
    width:100%;background:#fff;color:#111;border:1px solid #d8d8d8;
    border-radius:12px;padding:18px;font-size:18px;font-weight:500;
    display:flex;align-items:center;justify-content:space-between;text-decoration:none;
  }
  .btn-outline:active{background:#f9f9f9}
  .btn-outline .chev{display:grid;place-items:center}
  .btn-outline .chev svg{width:22px;height:22px}

  .footer{position:sticky;bottom:0;margin-top:auto;background:var(--card);padding:12px 16px 18px;border-top:1px solid var(--border);box-shadow:0 -10px 40px rgba(0,0,0,.06)}
  .cta{width:100%;border:none;border-radius:16px;padding:14px 16px;background:var(--cta);color:#1f2937;font-weight:800;font-size:16px;cursor:pointer}
  .cta:active{background:var(--cta-press)}
</style>
</head>
<body>
<div class="app">

  <!-- botão voltar -->
  <div class="hero-wrap">
    <button class="nav-btn" onclick="history.back()" aria-label="Voltar">
      <svg viewBox="0 0 24 24" fill="none"><path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>

    <!-- HERO: imagem ocupa toda a área -->
    <div class="hero">
      <img src="<?= e($imageUrl) ?>" alt="<?= e($product['name'] ?? 'Produto') ?>">
    </div>
  </div>

  <!-- Card de conteúdo -->
  <main class="card" role="main">
    <div class="brand">
      <span class="dot"><?= e($initial) ?></span>
      <span><?= e($company['name'] ?? '') ?></span>
    </div>

    <h1><?= e($product['name'] ?? '') ?></h1>

    <div class="price-row">
      <div class="price">R$ <?= number_format((float)($product['price'] ?? 0), 2, ',', '.') ?></div>

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

    <section class="section">
      <h3>Sobre</h3>
      <p class="body"><?= e($product['description'] ?? '') ?></p>
    </section>

  <section class="section">
      <h3>Ingredientes</h3>
      <ul class="checklist">
        <?php foreach($ingredients as $ing): ?>
          <li>
            <span class="bullet" aria-hidden="true"></span>
            <span><?= e($ing['name'] ?? '') ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  </main>

  <div class="customize-wrap">
    <div class="customize">
      <a class="btn-outline" href="<?= base_url(rawurlencode((string)($company['slug'] ?? '')) . '/product/' . (int)($product['id'] ?? 0) . '/customize') ?>">
        <span>Personalizar ingredientes</span>
        <span class="chev" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none"><path d="M9 5l7 7-7 7" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </a>
    </div>
  </div>

  <form class="footer" method="post" action="<?= base_url('add_to_cart.php') ?>" onsubmit="return attach(event)">
    <input type="hidden" name="product_id" value="<?= (int)($product['id'] ?? 0) ?>">
    <input type="hidden" name="qty" id="qtyField" value="1">
    <button class="cta" type="submit">Adicionar à Sacola</button>
  </form>
</div>

<script>
  const qval = document.getElementById('qval');
  const qfield = document.getElementById('qtyField');
  const minus = document.getElementById('qminus');
  const plus  = document.getElementById('qplus');

  const clamp = n => Math.max(1, Math.min(99, n|0));
  function setQty(n){
    const v = clamp(n);
    qval.textContent = String(v);
    qfield.value = String(v);
  }
  minus.addEventListener('click', ()=> setQty(parseInt(qval.textContent,10)-1));
  plus .addEventListener('click', ()=> setQty(parseInt(qval.textContent,10)+1));
  function attach(e){ setQty(parseInt(qval.textContent,10)); return true; }
</script>
</body>
</html>
