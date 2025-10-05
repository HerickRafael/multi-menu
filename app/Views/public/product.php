<?php
/** ============================================================================
 * app/views/public/product.php
 * Página pública do produto
 * ============================================================================ */

if (!function_exists('e')) {
    function e($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('price_br')) {
    function price_br($v)
    {
        return 'R$ ' . number_format((float)$v, 2, ',', '.');
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessName = function_exists('config') ? (config('session_name') ?? 'mm_session') : 'mm_session';

    if ($sessName && session_name() !== $sessName) {
        session_name($sessName);
    }
    @session_start();
}

/** Variáveis básicas */
$company      = $company      ?? [];
$product      = $product      ?? [];
$comboGroups  = $comboGroups  ?? null;
$mods         = $mods         ?? [];
$hasCustomization = isset($hasCustomization) ? (bool)$hasCustomization : (!empty($mods));

$slug     = (string)($company['slug'] ?? '');
$pId      = (int)($product['id'] ?? 0);
$homeUrl  = base_url($slug !== '' ? $slug : '');
$priceMode = $product['price_mode'] ?? 'fixed';

/** URLs */
$customizeBase = base_url($slug . '/produto/' . $pId . '/customizar');
$addToCartUrl  = base_url($slug . '/cart/add');
$requireLogin  = (bool)(config('login_required') ?? false);
$isLogged      = isset($_SESSION['customer']) && (!isset($_SESSION['customer']['company_slug']) || $_SESSION['customer']['company_slug'] === $slug);
$forceLoginModal = !empty($forceLoginModal);

/** Helper para forçar caminho local em /uploads a partir de URL ou nome */
if (!function_exists('local_upload_src')) {
    function local_upload_src(?string $maybeUrlOrName, string $fallback = 'assets/logo-placeholder.png'): string
    {
        $raw = trim((string)($maybeUrlOrName ?? ''));

        if ($raw === '') {
            return base_url($fallback);
        }
        $path = parse_url($raw, PHP_URL_PATH);
        $base = basename($path ?: $raw);

        if ($base === '' || $base === '/') {
            return base_url($fallback);
        }

        return base_url('uploads/' . $base);
    }
}

/** Normaliza grupos de combo vindos do backend */
$comboGroupsRaw = is_array($comboGroups) ? $comboGroups : [];
$comboGroups    = [];

foreach ($comboGroupsRaw as $gIndex => $group) {
    if (!is_array($group)) {
        continue;
    }

    $itemsRaw = $group['items'] ?? [];

    if (!is_array($itemsRaw) || !$itemsRaw) {
        continue;
    }

    $items = [];

    foreach ($itemsRaw as $item) {
        if (!is_array($item)) {
            continue;
        }

        $simpleId = isset($item['simple_id'])
          ? (int)$item['simple_id']
          : (int)($item['simple_product_id'] ?? $item['product_id'] ?? 0);

        if ($simpleId <= 0) {
            continue;
        }

        $comboItemId = isset($item['id']) ? (int)$item['id'] : $simpleId;
        $basePrice   = null;

        if (isset($item['base_price'])) {
            $basePrice = (float)$item['base_price'];
        } elseif (isset($item['price'])) {
            $basePrice = (float)$item['price'];
        }

        $delta = 0.0;

        if (isset($item['delta'])) {
            $delta = (float)$item['delta'];
        } elseif (isset($item['delta_price'])) {
            $delta = (float)$item['delta_price'];
        }

        $isDefault      = !empty($item['default']) || !empty($item['is_default']);
        $allowCustomize = !empty($item['customizable']) || !empty($item['allow_customize']);

        $items[] = [
          'id'           => $comboItemId,
          'simple_id'    => $simpleId,
          'name'         => (string)($item['name'] ?? ''),
          'image'        => $item['image'] ?? null,
          'base_price'   => $basePrice,
          'delta'        => $delta,
          'default'      => $isDefault,
          'customizable' => $allowCustomize,
        ];
    }

    if (!$items) {
        continue;
    }

    $minQty = isset($group['min']) ? (int)$group['min'] : (int)($group['min_qty'] ?? 0);
    $maxQty = isset($group['max']) ? (int)$group['max'] : (int)($group['max_qty'] ?? 1);
    $type   = isset($group['type']) && $group['type'] !== '' ? (string)$group['type'] : 'single';
    $name   = trim((string)($group['name'] ?? ''));

    if ($name === '') {
        $name = 'Grupo ' . ((int)$gIndex + 1);
    }

    $comboGroups[] = [
      'id'        => isset($group['id']) ? (int)$group['id'] : null,
      'name'      => $name,
      'type'      => $type,
      'min'       => $minQty,
      'max'       => $maxQty,
      'items'     => array_values($items),
    ];
}

/** É combo? */
$isCombo = (isset($product['type']) && $product['type'] === 'combo' && !empty($comboGroups));
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title><?= e($product['name'] ?? 'Produto') ?> — <?= e($company['name'] ?? '') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  :root{
    --bg:#f3f4f6; --card:#fff; --txt:#0f172a; --muted:#6b7280;
    --border:#e5e7eb; --accent:#f59e0b; --accent-active:#d97706; --accent-ink:#ffffff;
    --ring:#fbbf24;
    --hero-h: 360px;
  }
  *{box-sizing:border-box}
  html,body{margin:0;background:var(--bg);color:var(--txt);font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial}
  .app{width:100%;margin:0 auto;min-height:100dvh;display:flex;flex-direction:column;background:var(--card);padding-bottom:96px;position:relative}
  @media (min-width:768px){ .app{max-width:420px} }

  /* ===== HERO ===== */
  .hero-wrap{position:relative;height:var(--hero-h);overflow:hidden}
  .nav-btn{position:absolute;top:12px;left:12px;z-index:3;width:36px;height:36px;border-radius:999px;border:1px solid var(--border);background:var(--card);display:grid;place-items:center;box-shadow:0 2px 6px rgba(0,0,0,.08);cursor:pointer}
  .hero{position:absolute;inset:0;background:radial-gradient(140% 90% at 75% 20%, #fff 0%, #eef2f5 55%, #e7ebee 100%);z-index:0;}
  .hero-product{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:100%;height:auto;display:block;z-index:1;filter:drop-shadow(0 18px 34px rgba(0,0,0,.25));pointer-events:none;user-select:none}

  /* ===== CARD ===== */
  .card{position:relative;z-index:4;background:var(--card);border-radius:26px 26px 0 0;margin-top:-18px;padding:16px 16px 8px;box-shadow:0 -1px 0 var(--border);display:flex;flex-direction:column;gap:16px}
  .brand{display:flex;align-items:center;gap:8px;color:#374151;font-size:13px}
  h1{margin:2px 0 0;font-size:20px;line-height:1.25;font-weight:700}
  .price-row{display:flex;align-items:center;justify-content:space-between;margin-top:4px}
  .price{display:flex;flex-direction:column;gap:4px}
  .price-single{font-size:22px;font-weight:800}
  .price-original{font-size:15px;font-weight:600;color:#9ca3af;text-decoration:line-through}
  .price-current-row{display:flex;align-items:baseline;gap:10px}
  .price-current{font-size:24px;font-weight:800}
  .price-discount{font-size:16px;font-weight:700;color:#059669}
  .stepper{display:flex;align-items:center;gap:10px;border:1px solid var(--border);border-radius:999px;padding:6px 10px;min-width:104px;justify-content:space-between}
  .st-btn{width:32px;height:32px;border-radius:999px;background:#fff;border:none;display:grid;place-items:center;cursor:pointer}
  .st-btn svg{width:18px;height:18px}
  .st-val{min-width:20px;text-align:center;font-weight:700}
  .section h3{margin:8px 0 6px;color:var(--muted);font-size:12px;letter-spacing:.08em;text-transform:uppercase}
  .body{font-size:14px;color:#374151;line-height:1.5}

  /* ===== PERSONALIZAR ===== */
  .customize-wrap{background:var(--card)}
  .customize{padding:24px 16px}
  .btn-outline{width:100%;background:#fff;color:#111;border:1px solid #d8d8d8;border-radius:12px;padding:18px;font-size:18px;font-weight:500;display:flex;align-items:center;justify-content:space-between;text-decoration:none}
  .btn-outline:active{background:#f9f9f9}
  .btn-outline .chev{display:grid;place-items:center}
  .btn-outline .chev svg{width:22px;height:22px}

  /* ===== COMBO ===== */
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
  .choice-customize{display:inline-flex;align-items:center;justify-content:center;margin-top:10px;padding:7px 18px;border:1px solid var(--border);border-radius:999px;font-size:13px;font-weight:600;color:#111827;text-decoration:none;background:#fff;transition:background .18s ease,color .18s ease,border-color .18s ease}
  .choice-customize:hover{background:#111827;color:#fff}
  .choice-customize:active{background:#0f172a;color:#fff;border-color:#0f172a}
  .choice-customize.hidden{display:none}
  .ring:focus{outline:none}
  .ring:focus-visible{outline:none;box-shadow:none}

  /* ===== FOOTER/CTA ===== */
  .footer{position:fixed;bottom:0;left:50%;transform:translateX(-50%);background:var(--card);padding:12px 16px 18px;border-top:1px solid var(--border);box-shadow:0 -10px 40px rgba(0,0,0,.06);width:100%;max-width:100%}
  @media (min-width:768px){ .footer{max-width:420px} }
  .card{padding-bottom:82px}
  .cta{display:flex;align-items:center;justify-content:center;width:100%;min-height:56px;border:none;border-radius:18px;padding:0 24px;background:var(--accent);color:var(--accent-ink);font-weight:800;font-size:16px;text-decoration:none;cursor:pointer;text-align:center}
  .cta:active{background:var(--accent-active)}
  .cta[disabled]{opacity:.6;cursor:not-allowed}
</style>
</head>
<body>
<div class="app">

  <div class="hero-wrap">
    <a class="nav-btn" href="<?= e($homeUrl) ?>" aria-label="Voltar">
      <svg viewBox="0 0 24 24" width="24" height="24" fill="none">
        <path d="M15 19l-7-7 7-7" stroke="#111827" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" transform="scale(0.7) translate(5 5)"></path>
      </svg>
    </a>

    <!-- Fundo/gradiente -->
    <div class="hero" aria-hidden="true"></div>

    <!-- Imagem do produto (sempre do /uploads) -->
    <?php
      $imgSrc = local_upload_src($product['image'] ?? null);
$imgAlt = !empty($product['name']) ? $product['name'] : 'Imagem do produto';
?>
    <img class="hero-product" src="<?= e($imgSrc) ?>" alt="<?= e($imgAlt) ?>">
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

// Parse robusto de preço promocional
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

      <div class="stepper" aria-label="Selecionar quantidade">
        <button class="st-btn" type="button" data-act="dec" aria-label="Diminuir">
          <svg viewBox="0 0 24 24"><path d="M5 12h14" stroke="#111" stroke-width="2" stroke-linecap="round"></path></svg>
        </button>
        <div class="st-val" id="qval" data-role="val">1</div>
        <button class="st-btn" type="button" data-act="inc" aria-label="Aumentar">
          <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="#111" stroke-width="2" stroke-linecap="round"></path></svg>
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

  <!-- Botão PERSONALIZAR -->
  <?php if ($hasCustomization): ?>
  <div class="customize-wrap">
    <div class="customize">
      <?php $customizeUrl = $customizeBase; ?>
      <a class="btn-outline" id="btn-customize" href="<?= e($customizeUrl) ?>" data-requires-login="<?= $requireLogin ? '1' : '0' ?>">
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
      <?php
    $gname = (string)($group['name'] ?? ('Etapa '.($gi + 1)));
        $items = $group['items'] ?? [];
        $gType = $group['type'] ?? 'single';
        $gMin  = isset($group['min']) ? (int)$group['min'] : 0;
        $gMax  = isset($group['max']) ? (int)$group['max'] : 1;
        ?>
      <div class="group">
        <h2><?= e($gname) ?></h2>
        <div class="choice-row"
             data-group-index="<?= (int)$gi ?>"
             data-group-type="<?= e($gType) ?>"
             data-min="<?= $gMin ?>"
             data-max="<?= $gMax ?>">
          <?php foreach ($items as $ii => $opt): ?>
            <?php
                $isDefault = !empty($opt['default']);
              $optDelta  = isset($opt['delta']) ? (float)$opt['delta'] : 0.0;
              $basePrice = isset($opt['base_price']) && $opt['base_price'] !== null ? (float)$opt['base_price'] : null;

              if ($isDefault) {
                  $priceLabel = 'Incluído';
              } else {
                  if ($basePrice !== null) {
                      $priceLabel = price_br($basePrice);
                  } elseif ($priceMode === 'sum') {
                      $priceLabel = price_br($optDelta);
                  } else {
                      if ($optDelta > 0) {
                          $priceLabel = '+ ' . price_br($optDelta);
                      } elseif ($optDelta < 0) {
                          $priceLabel = '− ' . price_br(abs($optDelta));
                      } else {
                          $priceLabel = price_br(0);
                      }
                  }
              }

              $comboImg = local_upload_src($opt['image'] ?? null);
              $simpleId = (int)($opt['simple_id'] ?? 0);
              $canCustomizeChoice = !empty($opt['customizable']) && $simpleId > 0;
              $parentQuery = http_build_query(['parent_id' => $pId]);
              $choiceCustomUrl = $canCustomizeChoice
                ? base_url($slug . '/produto/' . $simpleId . '/customizar?' . $parentQuery)
                : null;
              ?>
            <div class="choice <?= $isDefault ? 'sel' : '' ?>"
                 data-group="<?= (int)$gi ?>"
                 data-id="<?= (int)($opt['id'] ?? 0) ?>"
                 data-simple="<?= $simpleId ?>"
                 data-delta="<?= e(number_format($optDelta, 2, '.', '')) ?>"
                 data-default="<?= $isDefault ? '1' : '0' ?>"
                 <?php if ($basePrice !== null): ?>data-base-price="<?= e(number_format($basePrice, 2, '.', '')) ?>"<?php endif; ?>
                 data-customizable="<?= $canCustomizeChoice ? '1' : '0' ?>"
                 <?php if ($choiceCustomUrl): ?>data-custom-url="<?= e($choiceCustomUrl) ?>"<?php endif; ?>>
              <button type="button" class="ring" aria-pressed="<?= $isDefault ? 'true' : 'false' ?>">
                <img src="<?= e($comboImg) ?>" alt="<?= e($opt['name'] ?? '') ?>">
                <span class="mark" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
              </button>
              <div class="choice-name"><?= e($opt['name'] ?? '') ?></div>
              <div class="choice-price"><?= e($priceLabel) ?></div>
              <?php if ($canCustomizeChoice): ?>
                <a class="choice-customize <?= $isDefault ? '' : 'hidden' ?>" data-base-url="<?= e($choiceCustomUrl) ?>" href="<?= e($choiceCustomUrl) ?>">Personalizar</a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>

  <form class="footer" method="post" action="<?= e($addToCartUrl) ?>" onsubmit="return attach(event)" data-requires-login="<?= $requireLogin ? '1' : '0' ?>">
    <input type="hidden" name="product_id" value="<?= $pId ?>">
    <input type="hidden" name="qty" id="qtyField" value="1">

    <?php if ($isCombo): ?>
      <?php foreach ($comboGroups as $gi => $group): ?>
        <?php
          $selId = null;

          foreach (($group['items'] ?? []) as $opt) {
              if (!empty($opt['default'])) {
                  $selId = isset($opt['id']) ? (int)$opt['id'] : null;
                  break;
              }
          }
          ?>
        <input type="hidden" name="combo[<?= (int)$gi ?>]" id="combo_field_<?= (int)$gi ?>" value="<?= $selId !== null ? (int)$selId : '' ?>">
      <?php endforeach; ?>
    <?php endif; ?>

    <button class="cta" type="submit">Adicionar à Sacola</button>
  </form>
</div>

<?php if ($requireLogin && !$isLogged): ?>
<div id="login-modal" class="fixed inset-0 bg-black/50 hidden z-50">
  <div class="bg-white max-w-sm mx-auto mt-24 rounded-2xl overflow-hidden shadow-xl">
    <div class="p-4 border-b flex items-center">
      <h3 class="font-semibold text-lg">Login do Cliente</h3>
      <button type="button" id="login-close" class="ml-auto px-3 py-1.5 rounded-xl border">Fechar</button>
    </div>
    <form id="login-form" class="p-4" method="post" action="<?= base_url(rawurlencode((string)$company['slug']).'/customer-login') ?>">
      <?php if (function_exists('csrf_field')) {
          echo csrf_field();
      } ?>
      <input type="hidden" name="redirect_to" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
      <div class="mb-3">
        <label class="block text-sm font-medium mb-1">Nome</label>
        <input type="text" name="name" required class="w-full border rounded-lg px-3 py-2" />
      </div>
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1">WhatsApp</label>
        <input type="tel" name="whatsapp" required placeholder="(11) 90000-0000" class="w-full border rounded-lg px-3 py-2" />
        <p class="text-xs text-gray-500 mt-1">Somente números; inclua DDD.</p>
      </div>
      <button type="submit" class="w-full bg-yellow-400 text-black font-semibold py-2 rounded-lg hover:bg-yellow-300">
        Entrar
      </button>
      <div id="login-msg" class="text-sm mt-3 hidden"></div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
  const requiresLogin = <?= $requireLogin ? 'true' : 'false' ?>;
  let userLogged = <?= $isLogged ? 'true' : 'false' ?>;

  let loginModal = null;
  let loginClose = null;
  let loginForm = null;

  if (requiresLogin && !userLogged) {
    loginModal = document.getElementById('login-modal');
    loginClose = document.getElementById('login-close');
    loginForm  = document.getElementById('login-form');
    loginClose?.addEventListener('click', closeLoginModal);
    loginModal?.addEventListener('click', (ev)=>{ if (ev.target === loginModal) closeLoginModal(); });
    loginForm?.addEventListener('submit', ()=>{ userLogged = true; closeLoginModal(); });
  }

  if (<?= $forceLoginModal ? 'true' : 'false' ?> && requiresLogin && !userLogged) {
    openLoginModal();
  }

  const loginRedirect = document.getElementById('login-form')?.querySelector('input[name="redirect_to"]');

  function openLoginModal(){
    if (loginRedirect) {
      loginRedirect.value = window.location.pathname + window.location.search;
    }
    if (loginModal) loginModal.classList.remove('hidden');
  }
  function closeLoginModal(){ if (loginModal) loginModal.classList.add('hidden'); }

  function allowAction(){
    if (!requiresLogin) return true;
    if (userLogged) return true;
    openLoginModal();
    return false;
  }

  const stepper = document.querySelector('.stepper');
  const qval   = document.getElementById('qval');
  const qfield = document.getElementById('qtyField');
  const minus  = stepper?.querySelector('[data-act="dec"]');
  const plus   = stepper?.querySelector('[data-act="inc"]');
  const clamp  = n => Math.max(1, Math.min(99, n|0));
  function setQty(n){ const v = clamp(n); if(qval) qval.textContent = String(v); if(qfield) qfield.value = String(v); }
  minus?.addEventListener('click', ()=> setQty(parseInt(qval?.textContent||'1',10)-1));
  plus?.addEventListener('click', ()=> setQty(parseInt(qval?.textContent||'1',10)+1));

  function attach(ev){
    setQty(parseInt(qval?.textContent||'1',10)||1);
    if (!allowAction()) {
      ev?.preventDefault();
      return false;
    }
    return true;
  }

  const btnCust = document.getElementById('btn-customize');
  btnCust?.addEventListener('click', (ev)=>{
    if (!allowAction()) { ev.preventDefault(); return; }
    const base = btnCust.getAttribute('href') || '<?= e($customizeBase) ?>';
    const qty  = parseInt(qval?.textContent||'1',10) || 1;
    const url  = new URL(base, window.location.origin);
    url.searchParams.set('qty', String(qty));
    btnCust.setAttribute('href', url.toString());
  });

  document.querySelectorAll('.choice-row').forEach(row=>{
    const gi = row.dataset.groupIndex;
    const hidden = document.getElementById('combo_field_' + gi);
    const items = row.querySelectorAll('.choice');
    function revealCustomize(target){
      items.forEach(it=>{
        const link=it.querySelector('.choice-customize');
        if(link){
          link.classList.add('hidden');
          const base=link.dataset.baseUrl || link.getAttribute('href');
          if(base) link.setAttribute('href', base);
        }
      });
      if(target){
        const link=target.querySelector('.choice-customize');
        if(link){ link.classList.remove('hidden'); }
      }
    }
    const selectChoice = target => {
      items.forEach(i=>{
        i.classList.remove('sel');
        i.querySelector('.ring')?.setAttribute('aria-pressed','false');
      });
      target.classList.add('sel');
      target.querySelector('.ring')?.setAttribute('aria-pressed','true');
      if (hidden) hidden.value = target.dataset.id || '';
      revealCustomize(target);
    };

    const clearSelection = () => {
      items.forEach(i=>{
        i.classList.remove('sel');
        i.querySelector('.ring')?.setAttribute('aria-pressed','false');
      });
      if (hidden) hidden.value = '';
      revealCustomize(null);
    };

    const defaultChoice = row.querySelector('.choice[data-default="1"]');

    items.forEach(item=>{
      const ring = item.querySelector('.ring');
      ring?.addEventListener('click', ()=>{
        const isDefault = item.dataset.default === '1';
        if (item.classList.contains('sel')) {
          if (!isDefault) {
            if (defaultChoice && defaultChoice !== item) {
              selectChoice(defaultChoice);
            } else if (row.dataset.min === '0') {
              clearSelection();
            }
          }
          return;
        }
        selectChoice(item);
      });
    });

    const initial = row.querySelector('.choice.sel');
    if(initial){
      if (hidden) hidden.value = initial.dataset.id || '';
      revealCustomize(initial);
    } else if (defaultChoice) {
      selectChoice(defaultChoice);
    }
  });

  document.querySelectorAll('.choice-customize').forEach(link=>{
    link.addEventListener('click', (ev)=>{
      if (!allowAction()) { ev.preventDefault(); return; }
      const base=link.dataset.baseUrl || link.getAttribute('href');
      if(!base) return;
      const qty=parseInt(qval?.textContent||'1',10)||1;
      const url=new URL(base, window.location.origin);
      url.searchParams.set('qty', String(qty));
      link.setAttribute('href', url.toString());
    });
  });
</script>
</body>
</html>
