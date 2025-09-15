<?php
/** ============================================================================
 * app/views/public/customize.php
 * ----------------------------------------------------------------------------
 * TELA DE PERSONALIZAÇÃO DO PRODUTO (sem dados de demo)
 *
 * Esta view renderiza:
 *  - "Adicionais" (itens pagos / delta > 0) com stepper iniciando em 0
 *  - "Personalizar ..." (ingredientes/remover/extra) por GRUPOS:
 *      * type = 'single' -> radios (ex.: escolher 1 tipo de pão)
 *      * outros tipos -> itens com stepper (min/max por item; qty default)
 *
 * DE ONDE VEM CADA COISA?
 *  - Tudo vem do que você cadastrou no Admin > Produto > "Personalização (Ingredientes)"
 *    (aquele bloco que você pediu unificado — sem "Remove/Add/Swap" como campos visíveis
 *    pro operador; aqui a view só respeita o que estiver salvo).
 *
 * O CONTROLLER deve preparar e enviar para esta view:
 *   $company  (array) -> ['slug'=>..., 'name'=>...]
 *   $product  (array) -> ['id'=>..., 'name'=>..., ...]
 *   $mods     (array) -> lista de grupos (cada grupo com suas 'items')
 *
 * Ex. estrutura mínima de $mods:
 * $mods = [
 *   [
 *     'name' => 'Pão', 'type' => 'single', 'min' => 1, 'max' => 1,
 *     'items' => [
 *       ['name'=>'Brioche','delta'=>0,'default'=>true,'img'=>null],
 *       ['name'=>'Tradicional','delta'=>0,'default'=>false,'img'=>null],
 *     ]
 *   ],
 *   [
 *     'name'=>'Ingredientes','type'=>'extra','min'=>0,'max'=>99,
 *     'items'=>[
 *       ['name'=>'Bacon','delta'=>3.00,'default'=>0,'min'=>0,'max'=>5,'qty'=>0,'img'=>null],
 *       ['name'=>'Cebola','delta'=>0.00,'default'=>1,'min'=>0,'max'=>5,'qty'=>1,'img'=>null],
 *     ]
 *   ]
 * ];
 * ============================================================================ */

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('price_br')) {
  function price_br($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
}

// Helpers locais de UI
$slug    = $company['slug'] ?? '';
$pName   = $product['name'] ?? 'Produto';
$pId     = (int)($product['id'] ?? 0);

// Se quiser separar "Adicionais" pagos (delta > 0) como uma PRIMEIRA SEÇÃO:
$addons = [];
$groups = [];

// Percorre $mods vindo do Admin e separa:
//  - $addons: itens com delta > 0 e grupo não-single (mostramos como “Deseja adicionar...”)
//  - $groups: todos os grupos (inclusive single) para a área "Personalizar ..."
foreach (($mods ?? []) as $gIndex => $g) {
  $gType = $g['type'] ?? 'extra';
  $items = $g['items'] ?? [];

  // Copiamos o grupo para $groups (será exibido na seção de personalização)
  $groups[] = $g;

  // itens pagos viram "addons" (iniciam em 0) — isso é opcional:
  if ($gType !== 'single') {
    foreach ($items as $it) {
      $delta = (float)($it['delta'] ?? 0);
      if ($delta > 0) {
        $addons[] = [
          'id'   => md5($g['name'].'|'.$it['name']),         // id estável gerado (ou use id do BD se houver)
          'name' => 'Adicionar: ' . (string)($it['name'] ?? ''),
          'price'=> $delta,
          'img'  => $it['img'] ?? null,
          // quantidade default para add-on é 0
          'min'  => isset($it['min']) ? (int)$it['min'] : 0,
          'max'  => isset($it['max']) ? (int)$it['max'] : 5,
          'qty'  => 0,
        ];
      }
    }
  }
}
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
  .app{width:100%;margin:0 auto;min-height:100dvh;display:flex;flex-direction:column}
  @media (min-width:768px){ .app{max-width:375px} }

  header{position:sticky;top:0;background:#fff;z-index:5}
  .top{display:flex;align-items:center;gap:10px;padding:12px 12px 6px;border-bottom:1px solid var(--border)}
  .back{width:36px;height:36px;border:1px solid var(--border);border-radius:999px;background:#fff;display:grid;place-items:center;cursor:pointer;text-decoration:none}
  .title{font-weight:600}

  .container{padding:12px 16px 140px} /* espaço pro rodapé */

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

  /* Stepper pill */
  .stepper{display:flex;align-items:center;gap:10px;border:1px solid var(--border);border-radius:999px;padding:6px 10px;min-width:104px;justify-content:space-between}
  .st-btn{width:28px;height:28px;border-radius:999px;background:#fff;border:none;display:grid;place-items:center;cursor:pointer}
  .st-btn svg{width:18px;height:18px}
  .st-val{min-width:16px;text-align:center;font-weight:600}

  /* Rádio */
  .radio-wrap{margin-left:auto}
  .radio-btn{
    width:28px;height:28px;border-radius:999px;border:2px solid var(--ring);display:grid;place-items:center;background:#fff;
  }
  .radio-btn.sel{background:var(--ring);border-color:var(--ring)}
  .radio-btn svg{width:16px;height:16px;color:#111;display:none}
  .radio-btn.sel svg{display:block}

  /* Rodapé */
  .footer{
    position:fixed;left:0;right:0;bottom:0;z-index:6;display:flex;height:64px;border-top:1px solid var(--border);background:#fff;
  }
  .btn-cancel,.btn-confirm{flex:1 1 50%;font-size:17px;font-weight:600;border:none;cursor:pointer}
  .btn-cancel{background:#fff;color:#111}
  .btn-confirm{background:var(--cta);color:#111;transition:background .2s}
  .btn-confirm:active{background:var(--cta-press)}
  .homebar{position:absolute;left:50%;transform:translateX(-50%);bottom:8px;width:44%;height:4px;background:#111;border-radius:999px;opacity:.9}

  .hint{color:#6b7280;font-size:12px;margin:6px 2px 12px}
</style>
</head>
<body>

<?php
// URL de retorno para a página do produto público
$backUrl = base_url($slug . '/produto/' . $pId);
// Endpoint que receberá o POST desta tela (ajuste conforme sua rota real)
$saveUrl = base_url($slug . '/produto/' . $pId . '/customizar/salvar');
?>

<form class="app" method="post" action="<?= e($saveUrl) ?>">
  <header>
    <div class="top">
      <!-- Voltar para a página do produto -->
      <a class="back" href="<?= e($backUrl) ?>" aria-label="Voltar">
        <svg viewBox="0 0 24 24" fill="none"><path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
      <div class="title"><?= e($pName) ?></div>
    </div>
  </header>

  <div class="container">
    <h1 class="h1">Deseja adicionar<br>algum ingrediente?</h1>
    <div class="sub"><?= e($pName) ?></div>

    <!-- ======================================================================
         SEÇÃO 1 — ADICIONAIS (itens pagos) 
         ----------------------------------------------------------------------
         * Compostos automaticamente a partir de itens com delta > 0 nos grupos 
           não-single (o que você marcou no Admin).
         * Começam com qty = 0 e têm stepper (min/max).
         * Envio no POST: addons[<hash_id>] = quantidade
         =================================================================== -->
    <?php if (!empty($addons)): ?>
      <div class="list addons" id="list-addons" aria-label="Adicionais">
        <?php foreach($addons as $i=>$it): 
          $rowId = 'addon_' . $i; 
          $img   = $it['img'] ?: 'https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+'; ?>
          <div class="row" id="<?= e($rowId) ?>"
               data-min="<?= (int)$it['min'] ?>" data-max="<?= (int)$it['max'] ?>">
            <div class="thumb">
              <img src="<?= e($img) ?>" alt="" onerror="this.src='https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+'">
            </div>
            <div class="info">
              <div class="name"><?= e($it['name']) ?></div>
              <div class="price"><?= price_br($it['price']) ?></div>
            </div>
            <div class="stepper">
              <button class="st-btn" type="button" data-act="dec" aria-label="Diminuir">
                <svg viewBox="0 0 24 24"><path d="M5 12h14" stroke="#111" stroke-width="2" stroke-linecap="round"/></svg>
              </button>
              <div class="st-val" data-role="val"><?= (int)($it['qty'] ?? 0) ?></div>
              <button class="st-btn" type="button" data-act="inc" aria-label="Aumentar">
                <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="#111" stroke-width="2" stroke-linecap="round"/></svg>
              </button>
            </div>
            <input type="hidden" name="addons[<?= e($it['id']) ?>]" value="<?= (int)($it['qty'] ?? 0) ?>">
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- ======================================================================
         SEÇÃO 2 — PERSONALIZAR (todos os grupos do Admin)
         ----------------------------------------------------------------------
         * Para cada grupo de $mods:
           - type = 'single' => render como RÁDIO (escolha única; usa 'default')
           - outros tipos    => render itens com STEPPER (min/max/qty/default)
         * POST:
           - Grupos 'single':   custom_single[<group_index>] = <item_index>
           - Grupos com stepper custom_qty[<group_index>][<item_index>] = quantidade
         =================================================================== -->
    <?php if (!empty($groups)): ?>
      <h2 class="group-title">Personalizar <?= e($pName) ?></h2>

      <?php foreach ($groups as $gi => $g): 
        $gName = (string)($g['name'] ?? ('Grupo '.($gi+1)));
        $gType = (string)($g['type'] ?? 'extra');
        $gMin  = (int)($g['min'] ?? 0);
        $gMax  = (int)($g['max'] ?? 0); // pode ser 0 = "sem limite"
        $items = $g['items'] ?? [];
      ?>

        <!-- Dica de regras do grupo -->
        <div class="hint">
          <?= e($gName) ?> 
          <?php if ($gType === 'single'): ?>
            — escolha 1 opção
          <?php else: ?>
            <?php
              $range = [];
              if ($gMin > 0) $range[] = "mín. $gMin";
              if ($gMax > 0) $range[] = "máx. $gMax";
            ?>
            <?= !empty($range) ? '— ' . e(implode(' | ', $range)) : '' ?>
          <?php endif; ?>
        </div>

        <?php if ($gType === 'single'): ?>
          <!-- ===========================
               GRUPO 'single' (rádio)
               - Um dos itens deve vir com 'default' = true (opcional)
               - POST: custom_single[gi] = ii
               =========================== -->
          <?php
            // Define o item selecionado (o primeiro default=true ou 0)
            $selectedIndex = 0;
            foreach ($items as $ii => $it) {
              if (!empty($it['default'])) { $selectedIndex = $ii; break; }
            }
          ?>
          <?php foreach ($items as $ii => $it): 
            $isSel = ($ii === $selectedIndex);
            $img   = $it['img'] ?? null;
          ?>
            <div class="row radio" data-radio="g<?= (int)$gi ?>" data-id="<?= (int)$ii ?>">
              <div class="thumb">
                <img src="<?= e($img ?: 'https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+') ?>" alt="" onerror="this.src='https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+'">
              </div>
              <div class="info">
                <div class="name"><?= e($it['name'] ?? ('Opção '.($ii+1))) ?></div>
                <?php if (!empty($it['delta'])): ?>
                  <div class="price">+ <?= price_br((float)$it['delta']) ?></div>
                <?php else: ?>
                  <div class="price"><?= price_br(0) ?></div>
                <?php endif; ?>
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
          <input type="hidden" name="custom_single[<?= (int)$gi ?>]" id="f_single_<?= (int)$gi ?>" value="<?= (int)$selectedIndex ?>">

        <?php else: ?>
          <!-- ===========================
               GRUPOS com STEPPER (remove/add/extra/swap…)
               Cada item:
               - min/max (fallback 0..5)
               - qty default = it['qty'] || (it['default']?1:0)
               - POST: custom_qty[gi][ii] = quantidade
               =========================== -->
          <div class="list" aria-label="<?= e($gName) ?>">
            <?php foreach ($items as $ii => $it): 
              $img   = $it['img'] ?? null;
              $min   = isset($it['min']) ? (int)$it['min'] : 0;
              $max   = isset($it['max']) ? (int)$it['max'] : 5;
              $qty   = isset($it['qty']) ? (int)$it['qty'] : (!empty($it['default']) ? 1 : 0);
              $delta = (float)($it['delta'] ?? 0);
            ?>
              <div class="row" data-id="<?= (int)$ii ?>" data-min="<?= $min ?>" data-max="<?= $max ?>">
                <div class="thumb">
                  <img src="<?= e($img ?: 'https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+') ?>" alt="" onerror="this.src='https://dummyimage.com/80x80/f3f4f6/aaa.png&text=+'">
                </div>
                <div class="info">
                  <div class="name"><?= e($it['name'] ?? ('Item '.($ii+1))) ?></div>
                  <div class="price"><?= $delta>0 ? '+ '.price_br($delta) : price_br(0) ?></div>
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

    <!-- Campo oculto com o ID do produto -->
    <input type="hidden" name="product_id" value="<?= $pId ?>">
  </div>

  <!-- Rodapé com ações -->
  <div class="footer">
    <button type="button" class="btn-cancel" onclick="window.location.href='<?= e($backUrl) ?>'">Cancelar</button>
    <button type="submit" class="btn-confirm">Confirmar</button>
    <div class="homebar" aria-hidden="true"></div>
  </div>
</form>

<script>
  // ===== Util =====
  const clamp = (n,min,max)=> Math.max(min, Math.min(max, n));

  // ===== Stepper (linhas com data-min/max) =====
  document.querySelectorAll('.row').forEach(row=>{
    const min = parseInt(row.dataset.min || '0',10);
    const max = parseInt(row.dataset.max || '99',10);
    const valEl = row.querySelector('.st-val');
    const hidden = row.querySelector('input[type="hidden"]');

    // Linhas de RÁDIO não têm stepper
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

  // ===== Grupos 'single' (rádio) =====
  document.querySelectorAll('.row.radio').forEach(row=>{
    const groupKey = row.getAttribute('data-radio'); // ex.: g0, g1...
    const id       = row.getAttribute('data-id');    // índice do item
    const btn      = row.querySelector('.radio-btn');
    const hidden   = document.getElementById('f_single_' + groupKey.replace('g','')); // input hidden do grupo

    const mark = ()=> {
      document.querySelectorAll('.row.radio[data-radio="'+groupKey+'"] .radio-btn').forEach(b=>{
        b.classList.remove('sel');
        b.setAttribute('aria-checked','false');
      });
      btn.classList.add('sel');
      btn.setAttribute('aria-checked','true');
      if (hidden) hidden.value = String(id);
    };
    row.addEventListener('click', mark);
    btn.addEventListener('keydown', (e)=>{ if(e.key==='Enter' || e.key===' '){ e.preventDefault(); mark(); }});
  });
</script>
</body>
</html>
