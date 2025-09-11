<?php
// r/product/customize.php

$id = (int)($_GET['id'] ?? 0);

// ====== DADOS-DEMO (substitua pelos seus) ======
$product = [
  'id'   => $id ?: 1001,
  'name' => 'McOferta Média Brabo Brabíssimo Carne',
];

$addons = [ // "Deseja adicionar algum ingrediente?"
  ['id'=>'add_tomate', 'name'=>'Adicionar: Tomate',      'price'=>2.00, 'img'=>'assets/tomate.png', 'min'=>0, 'max'=>5, 'qty'=>0],
  ['id'=>'add_tasty',  'name'=>'Adicionar: Molho Tasty', 'price'=>3.00, 'img'=>'assets/molho-tasty.png', 'min'=>0, 'max'=>5, 'qty'=>0],
  ['id'=>'add_maio',   'name'=>'Adicionar: Maionese',    'price'=>3.00, 'img'=>'assets/maionese.png', 'min'=>0, 'max'=>5, 'qty'=>0],
];

$custom = [ // "Personalizar ..."
  // grupo de seleção única (radio)
  'bread' => [
    'type'   => 'single',
    'label'  => 'Pão',
    'value'  => 'pao_brioche', // default
    'options'=> [
      ['id'=>'pao_brioche', 'name'=>'Pão tipo Brioche', 'price'=>0.00, 'img'=>'assets/pao-brioche.png'],
      ['id'=>'pao_trad',    'name'=>'Pão Tradicional',  'price'=>0.00, 'img'=>'assets/pao-trad.png'],
    ]
  ],
  // itens com stepper
  'items' => [
    ['id'=>'molho_cbo',     'name'=>'Molho do CBO',         'price'=>3.00, 'img'=>'assets/molho-cbo.png',   'min'=>0, 'max'=>5, 'qty'=>1],
    ['id'=>'alface',        'name'=>'Alface',               'price'=>2.00, 'img'=>'assets/alface.png',      'min'=>0, 'max'=>5, 'qty'=>1],
    ['id'=>'bacon',         'name'=>'Bacon',                'price'=>3.00, 'img'=>'assets/bacon.png',       'min'=>0, 'max'=>5, 'qty'=>1],
    ['id'=>'carne',         'name'=>'Carne 100% Bovina',    'price'=>9.90, 'img'=>'assets/carne.png',       'min'=>0, 'max'=>5, 'qty'=>2],
    ['id'=>'cheddar',       'name'=>'Fatia Queijo Cheddar', 'price'=>2.00, 'img'=>'assets/cheddar.png',     'min'=>0, 'max'=>5, 'qty'=>2],
    ['id'=>'cebola_crispy', 'name'=>'Cebola Crispy',        'price'=>2.00, 'img'=>'assets/cebola-crispy.png','min'=>0, 'max'=>5, 'qty'=>1],
    ['id'=>'mequinese',     'name'=>'Mequinese',            'price'=>3.00, 'img'=>'assets/mequinese.png',   'min'=>0, 'max'=>5, 'qty'=>1],
  ],
];
// ================================================
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Personalizar — <?= htmlspecialchars($product['name']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#fff;
    --card:#ffffff;
    --txt:#111827;
    --muted:#6b7280;
    --border:#e5e7eb;
    --chip:#f3f4f6;
    --ring:#fbbf24;   /* amarelo da borda e check */
    --cta:#fbbf24;    /* amarelo do confirmar */
    --cta-press:#f59e0b;
  }
  *{box-sizing:border-box}
  html,body{margin:0;background:var(--bg);color:var(--txt);font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial}
  /* MOBILE: ocupa 100% (sem max-width); em tablet/desktop limita 375 */
  .app{width:100%;margin:0 auto;min-height:100dvh;display:flex;flex-direction:column}
  @media (min-width:768px){ .app{max-width:375px} }

  header{position:sticky;top:0;background:#fff;z-index:5}
  .top{display:flex;align-items:center;gap:10px;padding:12px 12px 6px;border-bottom:1px solid var(--border)}
  .back{width:36px;height:36px;border:1px solid var(--border);border-radius:999px;background:#fff;display:grid;place-items:center;cursor:pointer}
  .title{font-weight:600}

  .container{padding:12px 16px 140px} /* espaço pro rodapé */

  .h1{font-size:36px;line-height:1.05;font-weight:800;letter-spacing:-.5px;margin:14px 0 8px}
  .sub{color:var(--muted);margin-top:-6px}

  .group-title{font-size:28px;line-height:1.15;font-weight:800;margin:28px 0 12px;letter-spacing:-.5px}

  .row{display:flex;align-items:center;gap:12px;padding:14px 12px;border-top:1px solid var(--border)}
  .row:first-of-type{border-top:0}
  .thumb{width:52px;height:52px;border-radius:999px;background:var(--chip);display:grid;place-items:center;overflow:hidden}
  .thumb img{width:100%;height:100%;object-fit:contain}
  .info{flex:1 1 auto}
  .name{font-weight:700}
  .price{color:#374151;font-size:14px;margin-top:2px}

  /* Stepper pill */
  .stepper{display:flex;align-items:center;gap:10px;border:1px solid var(--border);border-radius:999px;padding:6px 10px;min-width:104px;justify-content:space-between}
  .st-btn{width:28px;height:28px;border-radius:999px;background:#fff;border:none;display:grid;place-items:center;cursor:pointer}
  .st-btn svg{width:18px;height:18px}
  .st-val{min-width:16px;text-align:center;font-weight:600}

  /* Linha de "radio" (seleção única) com check amarelo */
  .radio-wrap{margin-left:auto}
  .radio-btn{
    width:28px;height:28px;border-radius:999px;border:2px solid var(--ring);display:grid;place-items:center;background:#fff;
  }
  .radio-btn.sel{background:var(--ring);border-color:var(--ring)}
  .radio-btn svg{width:16px;height:16px;color:#111;display:none}
  .radio-btn.sel svg{display:block}

  /* Rodapé com Cancelar/Confirmar (split) */
  .footer{
    position:fixed;left:0;right:0;bottom:0;z-index:6;display:flex;height:64px;border-top:1px solid var(--border);
    background:#fff;
  }
  .btn-cancel,.btn-confirm{flex:1 1 50%;font-size:17px;font-weight:600;border:none;cursor:pointer}
  .btn-cancel{background:#fff;color:#111}
  .btn-confirm{background:var(--cta);color:#111;transition:background .2s}
  .btn-confirm:active{background:var(--cta-press)}
  .homebar{position:absolute;left:50%;transform:translateX(-50%);bottom:8px;width:44%;height:4px;background:#111;border-radius:999px;opacity:.9}

  /* Hint de seção */
  .hint{color:#6b7280;font-size:12px;margin:6px 2px 12px}
</style>
</head>
<body>
<form class="app" method="post" action="save_customization.php">
  <header>
    <div class="top">
      <a class="back" href="product.php?id=<?= (int)$product['id'] ?>" aria-label="Voltar">
        <svg viewBox="0 0 24 24" fill="none"><path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      <div class="title"><?= htmlspecialchars($product['name']) ?></div>
    </div>
  </header>

  <div class="container">
    <h1 class="h1">Deseja adicionar<br>algum ingrediente?</h1>
    <div class="sub"><?= htmlspecialchars($product['name']) ?></div>

    <!-- ADD-ONS (começam em 0) -->
    <div class="list addons" id="list-addons" aria-label="Adicionais">
      <?php foreach($addons as $i=>$it): ?>
        <div class="row" data-id="<?= htmlspecialchars($it['id']) ?>" data-min="<?= (int)$it['min'] ?>" data-max="<?= (int)$it['max'] ?>">
          <div class="thumb">
            <img src="<?= htmlspecialchars($it['img']) ?>" alt="" onerror="this.src='https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+'">
          </div>
          <div class="info">
            <div class="name"><?= htmlspecialchars($it['name']) ?></div>
            <div class="price">R$ <?= number_format($it['price'],2,',','.') ?></div>
          </div>
          <div class="stepper">
            <button class="st-btn" type="button" data-act="dec" aria-label="Diminuir">
              <svg viewBox="0 0 24 24"><path d="M5 12h14" stroke="#111" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
            <div class="st-val" data-role="val"><?= (int)$it['qty'] ?></div>
            <button class="st-btn" type="button" data-act="inc" aria-label="Aumentar">
              <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="#111" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
          </div>
          <input type="hidden" name="addons[<?= htmlspecialchars($it['id']) ?>]" value="<?= (int)$it['qty'] ?>">
        </div>
      <?php endforeach; ?>
    </div>

    <!-- PERSONALIZAR (radio + steppers) -->
    <h2 class="group-title">Personalizar <?= htmlspecialchars($product['name']) ?></h2>

    <!-- Grupo: Pão (seleção única) -->
    <div class="hint">Escolha 1 opção de pão</div>
    <?php foreach($custom['bread']['options'] as $opt): 
      $isSel = ($custom['bread']['value'] === $opt['id']); ?>
      <div class="row radio" data-radio="bread" data-id="<?= htmlspecialchars($opt['id']) ?>">
        <div class="thumb">
          <img src="<?= htmlspecialchars($opt['img']) ?>" alt="" onerror="this.src='https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+'">
        </div>
        <div class="info">
          <div class="name"><?= htmlspecialchars($opt['name']) ?></div>
          <div class="price">R$ <?= number_format($opt['price'],2,',','.') ?></div>
        </div>
        <div class="radio-wrap">
          <div class="radio-btn <?= $isSel?'sel':'' ?>" role="radio" aria-checked="<?= $isSel?'true':'false' ?>" tabindex="0">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <input type="hidden" name="custom[bread]" id="f_bread" value="<?= htmlspecialchars($custom['bread']['value']) ?>">

    <!-- Itens com stepper -->
    <div class="list custom-items" id="list-custom-items" aria-label="Personalizar itens">
      <?php foreach($custom['items'] as $it): ?>
        <div class="row" data-id="<?= htmlspecialchars($it['id']) ?>" data-min="<?= (int)$it['min'] ?>" data-max="<?= (int)$it['max'] ?>">
          <div class="thumb">
            <img src="<?= htmlspecialchars($it['img']) ?>" alt="" onerror="this.src='https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+'">
          </div>
          <div class="info">
            <div class="name"><?= htmlspecialchars($it['name']) ?></div>
            <div class="price">R$ <?= number_format($it['price'],2,',','.') ?></div>
          </div>
          <div class="stepper">
            <button class="st-btn" type="button" data-act="dec" aria-label="Diminuir">
              <svg viewBox="0 0 24 24"><path d="M5 12h14" stroke="#111" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
            <div class="st-val" data-role="val"><?= (int)$it['qty'] ?></div>
            <button class="st-btn" type="button" data-act="inc" aria-label="Aumentar">
              <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="#111" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
          </div>
          <input type="hidden" name="custom_qty[<?= htmlspecialchars($it['id']) ?>]" value="<?= (int)$it['qty'] ?>">
        </div>
      <?php endforeach; ?>
    </div>

    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
  </div>

  <!-- Rodapé -->
  <div class="footer">
    <button type="button" class="btn-cancel" onclick="window.location.href='product.php?id=<?= (int)$product['id'] ?>'">Cancelar</button>
    <button type="submit" class="btn-confirm">Confirmar</button>
    <div class="homebar" aria-hidden="true"></div>
  </div>
</form>

<script>
  // Util
  const clamp = (n,min,max)=> Math.max(min, Math.min(max, n));

  // ===== Stepper (linhas com data-min/max) =====
  document.querySelectorAll('.row').forEach(row=>{
    const min = parseInt(row.dataset.min || '0',10);
    const max = parseInt(row.dataset.max || '99',10);
    const valEl = row.querySelector('.st-val');
    const hidden = row.querySelector('input[type="hidden"]');

    if(!valEl) return; // linhas radio não têm stepper

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

  // ===== Grupo de rádio (pão) =====
  const breadInput = document.getElementById('f_bread');
  document.querySelectorAll('.row.radio').forEach(row=>{
    const id = row.dataset.id;
    const btn = row.querySelector('.radio-btn');
    const mark = ()=> {
      document.querySelectorAll('.row.radio .radio-btn').forEach(b=>{
        b.classList.remove('sel');
        b.setAttribute('aria-checked','false');
      });
      btn.classList.add('sel');
      btn.setAttribute('aria-checked','true');
      breadInput.value = id;
    };
    row.addEventListener('click', mark);
    btn.addEventListener('keydown', (e)=>{ if(e.key==='Enter' || e.key===' '){ e.preventDefault(); mark(); }});
  });
</script>
</body>
</html>
