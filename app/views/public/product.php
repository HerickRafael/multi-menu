<?php
// ===== produto.php =====
$product = [
  'id'          => 1001,
  'restaurant'  => "FSW Donald’s",
  'name'        => 'McOferta Média Big Mac Duplo',
  'price'       => 39.90,
  'image'       => 'assets/bigmac-meal.png', // troque para sua imagem
  'description' => 'Quatro hambúrgueres (100% carne bovina), alface americana, queijo fatiado sabor cheddar, molho especial, cebola, picles e pão com gergelim, acompanhamento e bebida.',
  'ingredients' => [
    'Quatro hambúrgueres de carne bovina 100%',
    'Alface americana',
    'Queijo processado sabor cheddar',
    'Molho especial',
    'Cebola',
    'Picles',
    'Pão com gergelim',
  ],
];
function price_br(float $v){ return 'R$ '.number_format($v,2,',','.'); }

/* ====== BLOCO DE COMBO — LAYOUT DEMO ======
   Em produção, use algo como:
   $showComboLayoutDemo = ($product['type'] ?? '') === 'combo';
   Aqui deixei true só para você ver o layout.
*/
$showComboLayoutDemo = true; // << troque para condição real (ex.: is_combo)
$comboData = [
  'mains' => [
    ['id'=>'tasty1','name'=>'Tasty Turbo Bacon 1 Carne','price'=>25.25,'img'=>'assets/combo-main-1.png'],
    ['id'=>'tasty2','name'=>'Tasty Turbo Bacon 2 Carnes','price'=>29.90,'img'=>'assets/combo-main-2.png'],
  ],
  'sides' => [
    ['id'=>'fries_m','name'=>'McFritas Média','price'=>4.45,'img'=>'assets/side-fries-m.png'],
    ['id'=>'fries_g','name'=>'McFritas Grande','price'=>7.45,'img'=>'assets/side-fries-g.png'],
    ['id'=>'fries_ch','name'=>'McFritas Cheddar e Bacon','price'=>8.45,'img'=>'assets/side-fries-ch.png'],
  ],
  'drinks' => [
    ['id'=>'coke','name'=>'Coca-Cola 500ml','price'=>16.90,'img'=>'assets/drink-coke.png'],
    ['id'=>'coke0','name'=>'Coca-Cola Sem Açúcar 500ml','price'=>16.90,'img'=>'assets/drink-coke-zero.png'],
    ['id'=>'fanta_gua','name'=>'Fanta Guaraná 500ml','price'=>16.90,'img'=>'assets/drink-fanta-guarana.png'],
  ],
];
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title><?= htmlspecialchars($product['name']) ?> — <?= htmlspecialchars($product['restaurant']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#f3f4f6;
    --card:#ffffff;
    --txt:#0f172a;
    --muted:#6b7280;
    --border:#e5e7eb;
    --accent:#ef4444;
    --ring:#fbbf24;     /* amarelo da seleção */
    --cta:#f59e0b;
    --cta-press:#d97706;
  }
  *{ box-sizing:border-box }
  html,body{ margin:0; background:var(--bg); color:var(--txt); font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial }

  /* MOBILE: 100% (sem max-width) */
  .app{
    width:100%;
    margin:0 auto;
    min-height:100dvh;
    display:flex;
    flex-direction:column;
  }
  /* TABLET/DESKTOP: limita a 375px */
  @media (min-width: 768px){
    .app{ max-width:375px; }
  }

  /* ===== HERO FULL-BLEED ===== */
  .hero-wrap{ position:relative }
  .nav-btn{
    position:absolute; top:12px; left:12px; z-index:2;
    width:36px; height:36px; border-radius:999px; border:1px solid var(--border);
    background:var(--card); display:grid; place-items:center; box-shadow:0 2px 6px rgba(0,0,0,.08); cursor:pointer;
  }
  .hero{
    width:100%; height:360px;
    background:radial-gradient(140% 90% at 75% 20%, #fff 0%, #eef2f5 55%, #e7ebee 100%);
    display:grid; place-items:center;
  }
  .hero img{
    width:100%; height:100%; object-fit:contain;
    filter: drop-shadow(0 18px 34px rgba(0,0,0,.25));
  }

  /* ===== CARD ===== */
  .card{
    background:var(--card); border-radius:26px 26px 0 0; margin-top:-8px;
    padding:16px 16px 8px; box-shadow:0 -1px 0 var(--border);
    display:flex; flex-direction:column; gap:16px;
  }
  .brand{ display:flex; align-items:center; gap:8px; color:#374151; font-size:13px }
  .brand .dot{ width:18px; height:18px; border-radius:999px; background:#ffb703; display:grid; place-items:center; color:#7c2d12; font-weight:800; font-size:11px }
  h1{ margin:2px 0 0; font-size:20px; line-height:1.25; font-weight:700 }

  .price-row{ display:flex; align-items:center; justify-content:space-between; margin-top:4px }
  .price{ font-size:22px; font-weight:800 }

  /* Stepper */
  .qty{ display:flex; align-items:center; gap:8px }
  .btn-circ{ width:40px; height:40px; border-radius:12px; border:1px solid var(--border); background:var(--card); display:grid; place-items:center; cursor:pointer }
  .btn-circ svg{ width:18px; height:18px }
  .btn-red{ background:var(--accent); border-color:transparent; color:#fff; box-shadow:0 6px 16px rgba(239,68,68,.28) }
  .qty-badge{ min-width:32px; height:32px; border-radius:999px; border:1px solid var(--border); display:grid; place-items:center; font-weight:700 }

  .section h3{ margin:8px 0 6px; color:var(--muted); font-size:12px; letter-spacing:.08em; text-transform:uppercase }
  .body{ font-size:14px; color:#374151; line-height:1.5 }

  /* Ingredientes com bolinhas pretas */
  .checklist{ list-style:none; margin:8px 0 0; padding:0; display:grid; gap:10px }
  .checklist li{ display:flex; align-items:flex-start; gap:10px; font-size:14px }
  .bullet{ width:7px; height:7px; border-radius:999px; background:#111; margin-top:7px; flex:0 0 7px }

  /* Botão "Personalizar ingredientes" */
  .customize-wrap{ background:var(--card) }
  .customize{ padding:24px 16px }
  .btn-outline{
    width:100%; background:#fff; color:#111; border:1px solid #d8d8d8;
    border-radius:12px; padding:18px; font-size:18px; font-weight:500;
    display:flex; align-items:center; justify-content:space-between; text-decoration:none;
  }
  .btn-outline:active{ background:#f9f9f9 }
  .btn-outline .chev{ display:grid; place-items:center }
  .btn-outline .chev svg{ width:22px; height:22px }

  /* ===== BLOCO DE COMBO (LAYOUT DEMO) ===== */
  .combo{ background:var(--card); padding:8px 0 8px; }
  .combo .group{ padding:10px 16px 0; }
  .combo h2{ font-size:32px; line-height:1.1; margin:12px 0 8px; font-weight:800; letter-spacing:-0.5px; }
  .choice-row{ display:flex; gap:18px; overflow-x:auto; padding:12px 12px 18px; scroll-snap-type:x mandatory; }
  .choice-row::-webkit-scrollbar{ height:0 }
  .choice{ width:128px; flex:0 0 auto; scroll-snap-align:start; text-align:center; }
  .ring{
    width:100px; height:100px; border-radius:999px; border:3px solid var(--border);
    background:#fff; display:grid; place-items:center; position:relative; margin:0 auto;
  }
  .ring img{ width:86px; height:86px; object-fit:contain; }
  .mark{
    position:absolute; right:-6px; top:-6px; width:24px; height:24px;
    background:var(--ring); border-radius:999px; display:none; place-items:center;
    box-shadow:0 1px 0 rgba(0,0,0,.06);
  }
  .mark svg{ width:14px; height:14px; color:#111 }
  .choice.sel .ring{ border-color:var(--ring) }
  .choice.sel .mark{ display:grid }
  .choice-name{ margin-top:10px; font-weight:700; font-size:15px; color:#1f2937 }
  .choice-price{ margin-top:4px; color:#374151; font-size:14px }

  /* CTA antigo (preservado) */
  .footer{
    position:sticky; bottom:0; background:var(--card);
    padding:12px 16px 18px; border-top:1px solid var(--border);
    box-shadow:0 -10px 40px rgba(0,0,0,.06);
  }
  .cta{
    width:100%; border:none; border-radius:16px; padding:14px 16px;
    background:var(--cta); color:#1f2937; font-weight:800; font-size:16px; cursor:pointer;
  }
  .cta:active{ background:var(--cta-press) }
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
      <?php if(is_file($product['image'])): ?>
        <img src="<?= htmlspecialchars($product['image']) ?>" alt="Produto">
      <?php else: ?>
        <img src="https://dummyimage.com/1200x800/f2f4f7/9aa1a9.png&text=Sua+imagem+aqui" alt="Placeholder">
      <?php endif; ?>
    </div>
  </div>

  <!-- Card de informações -->
  <main class="card" role="main">
    <div class="brand">
      <span class="dot">M</span>
      <span><?= htmlspecialchars($product['restaurant']) ?></span>
    </div>

    <h1><?= htmlspecialchars($product['name']) ?></h1>

    <div class="price-row">
      <div class="price"><?= price_br($product['price']) ?></div>

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
      <p class="body"><?= htmlspecialchars($product['description']) ?></p>
    </section>

    <section class="section">
      <h3>Ingredientes</h3>
      <ul class="checklist">
        <?php foreach($product['ingredients'] as $ing): ?>
          <li><span class="bullet" aria-hidden="true"></span><span><?= htmlspecialchars($ing) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </section>
  </main>

  <!-- Botão PERSONALIZAR (acima do CTA) -->
  <div class="customize-wrap">
    <div class="customize">
      <a class="btn-outline" href="personalizar.php?id=<?= (int)$product['id'] ?>">
        <span>Personalizar ingredientes</span>
        <span class="chev" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none"><path d="M9 5l7 7-7 7" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
      </a>
    </div>
  </div>

  <!-- ===== BLOCO EXCLUSIVO PARA PRODUTOS COMBO (LAYOUT DEMO) ===== -->
  <?php if ($showComboLayoutDemo): ?>
  <section class="combo" aria-label="Montar combo">
    <!-- Grupo 1 -->
    <div class="group">
      <h2>Escolha o produto</h2>
      <div class="choice-row" id="grp-main">
        <?php foreach($comboData['mains'] as $i => $opt): ?>
          <div class="choice <?= $i===0?'sel':'' ?>" data-group="main" data-id="<?= htmlspecialchars($opt['id']) ?>">
            <button type="button" class="ring" aria-pressed="<?= $i===0?'true':'false' ?>">
              <img src="<?= htmlspecialchars($opt['img']) ?>" alt="">
              <span class="mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </span>
            </button>
            <div class="choice-name"><?= htmlspecialchars($opt['name']) ?></div>
            <div class="choice-price"><?= price_br($opt['price']) ?></div>
            <input type="radio" name="combo_main" value="<?= htmlspecialchars($opt['id']) ?>" <?= $i===0?'checked':'' ?> hidden>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Grupo 2 -->
    <div class="group">
      <h2>Escolha o acompanhamento</h2>
      <div class="choice-row" id="grp-side">
        <?php foreach($comboData['sides'] as $i => $opt): ?>
          <div class="choice <?= $i===0?'sel':'' ?>" data-group="side" data-id="<?= htmlspecialchars($opt['id']) ?>">
            <button type="button" class="ring" aria-pressed="<?= $i===0?'true':'false' ?>">
              <img src="<?= htmlspecialchars($opt['img']) ?>" alt="">
              <span class="mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </span>
            </button>
            <div class="choice-name"><?= htmlspecialchars($opt['name']) ?></div>
            <div class="choice-price"><?= price_br($opt['price']) ?></div>
            <input type="radio" name="combo_side" value="<?= htmlspecialchars($opt['id']) ?>" <?= $i===0?'checked':'' ?> hidden>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Grupo 3 -->
    <div class="group">
      <h2>Escolha a bebida</h2>
      <div class="choice-row" id="grp-drink">
        <?php foreach($comboData['drinks'] as $i => $opt): ?>
          <div class="choice <?= $i===0?'sel':'' ?>" data-group="drink" data-id="<?= htmlspecialchars($opt['id']) ?>">
            <button type="button" class="ring" aria-pressed="<?= $i===0?'true':'false' ?>">
              <img src="<?= htmlspecialchars($opt['img']) ?>" alt="">
              <span class="mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </span>
            </button>
            <div class="choice-name"><?= htmlspecialchars($opt['name']) ?></div>
            <div class="choice-price"><?= price_br($opt['price']) ?></div>
            <input type="radio" name="combo_drink" value="<?= htmlspecialchars($opt['id']) ?>" <?= $i===0?'checked':'' ?> hidden>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>
  <!-- ===== FIM BLOCO COMBO (LAYOUT DEMO) ===== -->

  <!-- CTA ANTIGO -->
  <form class="footer" method="post" action="add_to_cart.php" onsubmit="return attach(event)">
    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
    <input type="hidden" name="qty" id="qtyField" value="1">
    <!-- Inputs do combo (se quiser enviar junto no POST) -->
    <input type="hidden" name="combo_main" id="f_combo_main" value="<?= htmlspecialchars($comboData['mains'][0]['id']) ?>">
    <input type="hidden" name="combo_side" id="f_combo_side" value="<?= htmlspecialchars($comboData['sides'][0]['id']) ?>">
    <input type="hidden" name="combo_drink" id="f_combo_drink" value="<?= htmlspecialchars($comboData['drinks'][0]['id']) ?>">
    <button class="cta" type="submit">Adicionar à Sacola</button>
  </form>
</div>

<script>
  // Stepper qty
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
  minus?.addEventListener('click', ()=> setQty(parseInt(qval.textContent,10)-1));
  plus ?.addEventListener('click', ()=> setQty(parseInt(qval.textContent,10)+1));
  function attach(e){ setQty(parseInt(qval.textContent,10)||1); return true; }

  // ===== Seleção única por grupo (COMBO LAYOUT DEMO) =====
  function setupComboGroup(groupName, hiddenFieldId){
    const items = document.querySelectorAll(`.choice[data-group="${groupName}"]`);
    const hidden = document.getElementById(hiddenFieldId);
    items.forEach(item=>{
      item.querySelector('.ring').addEventListener('click', ()=>{
        items.forEach(i=>{
          i.classList.remove('sel');
          i.querySelector('.ring').setAttribute('aria-pressed','false');
          const r = i.querySelector('input[type="radio"]'); if (r) r.checked=false;
        });
        item.classList.add('sel');
        item.querySelector('.ring').setAttribute('aria-pressed','true');
        const radio = item.querySelector('input[type="radio"]');
        if(radio){ radio.checked=true; hidden && (hidden.value = radio.value); }
      });
    });
  }
  // Ative apenas se o bloco de combo existe
  if (document.querySelector('.combo')){
    setupComboGroup('main',  'f_combo_main');
    setupComboGroup('side',  'f_combo_side');
    setupComboGroup('drink', 'f_combo_drink');
  }
</script>
</body>
</html>
