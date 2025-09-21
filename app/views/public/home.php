<?php
$title = ($company['name'] ?? 'Cardápio') . ' - Cardápio';
ob_start();

// Helper de escape seguro (fallback caso não exista a função e())
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

/* Helpers de badges */
function badgePromo($p){
  if (!is_array($p)) return false;
  $price = isset($p['price']) ? (float)$p['price'] : 0;
  $promoRaw = $p['promo_price'] ?? null;
  if ($price <= 0 || $promoRaw === null || $promoRaw === '') {
    return false;
  }
  if (is_array($promoRaw)) {
    $promoRaw = reset($promoRaw);
  }
  $promoStr = trim((string)$promoRaw);
  if ($promoStr === '') {
    return false;
  }
  $promoStr = str_replace(' ', '', $promoStr);
  if (strpos($promoStr, ',') !== false && strpos($promoStr, '.') !== false) {
    $promoStr = str_replace('.', '', $promoStr);
  }
  $promoStr = str_replace(',', '.', $promoStr);
  if (!is_numeric($promoStr)) {
    return false;
  }
  $promo = (float)$promoStr;
  return $promo > 0 && $promo < $price;
}
// usa o helper global do seu projeto para novidade
if (!function_exists('badgeNew')) {
  function badgeNew($p){ return is_new_product($p); }
}

if (!function_exists('normalize_color_hex')) {
  function normalize_color_hex($value, $default) {
    $value = trim((string)$value);
    if ($value === '') {
      return strtoupper($default);
    }
    if ($value[0] !== '#') {
      $value = '#' . $value;
    }
    if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
      return strtoupper($default);
    }
    if (strlen($value) === 4) {
      $value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
    }
    return strtoupper($value);
  }
}

$headerColor    = normalize_color_hex($company['menu_header_text_color']      ?? '', '#FFFFFF');
$logoBgColor    = normalize_color_hex($company['menu_logo_bg_color']          ?? '', '#FFFFFF');
$groupBgColor   = normalize_color_hex($company['menu_group_title_bg_color']   ?? '', '#FACC15');
$groupTextColor = normalize_color_hex($company['menu_group_title_text_color'] ?? '', '#000000');
$welcomeBgColor = normalize_color_hex($company['menu_welcome_bg_color']       ?? '', '#6B21A8');
$welcomeText    = normalize_color_hex($company['menu_welcome_text_color']     ?? '', '#FFFFFF');

/* Variáveis vindas do controller (com fallbacks para evitar notices) */
$q              = $q              ?? '';
$novidades      = $novidades      ?? [];
$searchResults  = $searchResults  ?? [];
$categories     = $categories     ?? [];
$products       = $products       ?? [];
$hours          = $hours          ?? [];
$isOpenNow      = $isOpenNow      ?? null;
$todayLabel     = $todayLabel     ?? null;
$company        = $company        ?? [];

/* Flags: controller decide; view só obedece */
$mostraNovidade = isset($mostraNovidade) ? (bool)$mostraNovidade : (count($novidades) > 0);

$bannerUrl = !empty($company['banner']) ? base_url($company['banner']) : null;

/* Sessão do cliente (se existir) */
if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
$customer = $_SESSION['customer'] ?? null;
$showFooterMenu = true;
?>
<header>
  <style>
    /* Remove a borda/halo azul do bloco roxo sem afetar botões/links internos */
    .no-focus-ring:focus,
    .no-focus-ring:focus-visible,
    .no-focus-ring:focus-within,
    .no-focus-ring:target { outline: none !important; box-shadow: none !important; }
    .no-focus-ring { -webkit-tap-highlight-color: transparent; }
    .menu-header {
      color: <?= e($headerColor) ?>;
      --menu-header-color: <?= e($headerColor) ?>;
    }
    .menu-header .status-badge,
    .menu-header .menu-header-btn,
    .menu-header .menu-header-icon {
      background-color: var(--menu-header-color);
      color: #ffffff;
    }
    .menu-header .status-badge.closed {
      opacity: 0.65;
    }
    .menu-header .menu-header-btn-outline {
      border: 1px solid var(--menu-header-color);
      color: var(--menu-header-color);
      background-color: transparent;
      transition: background-color .2s ease, color .2s ease;
    }
    .menu-header .menu-header-btn-outline:hover,
    .menu-header .menu-header-btn-outline:focus-visible {
      background-color: var(--menu-header-color);
      color: #ffffff;
    }
    .menu-header .menu-header-link {
      color: var(--menu-header-color);
    }
    .menu-header .menu-header-link:hover {
      opacity: .9;
    }
  </style>
  <div class="rounded-2xl overflow-hidden">
    <?php if ($bannerUrl): ?>
      <div class="relative">
        <img src="<?= $bannerUrl ?>" class="w-full h-36 md:h-48 object-cover" alt="Banner">
        <div class="absolute inset-0 bg-black/30"></div>
      </div>
    <?php else: ?>
      <div class="bg-purple-900 h-24"></div>
    <?php endif; ?>

    <div class="bg-purple-900 p-5 relative -mt-10 rounded-t-2xl no-focus-ring menu-header">
      <img src="<?= base_url($company['logo'] ?? 'assets/logo-placeholder.png') ?>"
           class="w-24 h-24 rounded-full object-cover border-4 border-purple-700 absolute -top-10 right-6 pointer-events-none"
           style="background-color: <?= e($logoBgColor) ?>;"
           alt="<?= e($company['name'] ?? 'Logo') ?>">
      <div class="min-w-0 pr-28">
        <h1 class="text-2xl font-bold"><?= e($company['name'] ?? 'Empresa') ?></h1>

        <!-- Linha de status + horário de hoje + info -->
        <div class="flex flex-wrap items-center gap-2 text-sm mt-1">
            <?php $statusClass = !empty($isOpenNow) ? 'open' : 'closed'; ?>
            <span class="status-badge inline-flex items-center px-2 py-0.5 rounded-lg font-semibold <?= $statusClass ?>">
              <?= !empty($isOpenNow) ? 'Aberto!' : 'Fechado' ?>
            </span>

            <?php if (!empty($todayLabel)): ?>
              <button type="button" id="btn-hours" class="font-semibold menu-header-link"><?= e($todayLabel) ?></button>
              <span id="btn-hours-ico" class="menu-header-icon inline-flex items-center justify-center w-5 h-5 rounded-full cursor-pointer" aria-hidden="true">i</span>
            <?php endif; ?>

            <?php if (!empty($company['min_order'])): ?>
              <span class="text-sm opacity-90 mt-1">
                Pedido mínimo: <strong>R$ <?= number_format((float)$company['min_order'], 2, ',', '.') ?></strong>
              </span>
            <?php endif; ?>

            <?php if (!empty($company['whatsapp'])): ?>
              <a class="inline-flex items-center gap-1 underline menu-header-link" href="https://wa.me/<?= e(preg_replace('/\D+/', '', (string)$company['whatsapp'])) ?>" target="_blank" aria-label="WhatsApp">
                <!-- ícone WhatsApp (SVG) -->
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#FACC14" class="bi bi-whatsapp" viewBox="0 0 16 16" aria-hidden="true">
                  <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/>
                </svg>
                WhatsApp
              </a>
            <?php endif; ?>

            <!-- Login ou saudação do cliente -->
            <?php if (!empty($customer) && isset($company['id']) && isset($customer['company_id']) && (int)$customer['company_id'] === (int)$company['id']): ?>
              <div class="flex items-center gap-2 w-full sm:w-auto mt-2 sm:mt-0 self-center">
                <span class="px-2 py-0.5 rounded-lg menu-header-btn font-semibold">
                  Olá, <?= e($customer['name'] ?? 'Cliente') ?>
                </span>
                <form method="post" action="<?= base_url(rawurlencode((string)$company['slug']).'/customer-logout') ?>" onsubmit="return confirm('Sair?')">
                  <?php if (function_exists('csrf_field')) { echo csrf_field(); } ?>
                  <button class="px-2 py-0.5 rounded-lg menu-header-btn-outline font-semibold">Sair</button>
                </form>
              </div>
            <?php else: ?>
              <div class="w-full sm:w-auto mt-2 sm:mt-0 self-center">
                <button
                  type="button"
                  id="btn-open-login"
                  class="px-2 py-0.5 rounded-lg menu-header-btn-outline font-semibold">
                  Entrar
                </button>
              </div>
            <?php endif; ?>
          </div>

          <?php if (!empty($company['address'])): ?>
            <div class="text-xs opacity-90 mt-1"><?= e($company['address']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if (!empty($company['highlight_text'])): ?>
      <div class="pt-3 px-0 ">
        <p class="bg-purple-700 text-white p-5 rounded-xl text-sm">
          <?= nl2br(e($company['highlight_text'])) ?>
        </p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Modal: tabela de horários -->
  <div id="hours-modal" class="fixed inset-0 bg-black/50 hidden">
    <div class="bg-white max-w-md mx-auto mt-16 rounded-2xl overflow-hidden">
      <div class="p-4">
        <div class="flex items-center mb-1">
          <div class="font-semibold flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-yellow-400 text-black" aria-hidden="true">i</span>
            Horários de Funcionamento
          </div>
          <button id="hours-close" class="ml-auto px-3 py-1.5 rounded-xl border">Fechar</button>
        </div>

        <?php if (!empty($company['avg_delivery_min_from']) && !empty($company['avg_delivery_min_to'])): ?>
          <div class="text-sm text-gray-600">
            Tempo médio delivery: <?= (int)$company['avg_delivery_min_from'] ?> - <?= (int)$company['avg_delivery_min_to'] ?> minutos
          </div>
        <?php endif; ?>
      </div>

      <div class="px-4 pb-4">
        <table class="w-full border-collapse">
          <tbody>
          <?php
            $names=[1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta',6=>'Sábado',7=>'Domingo'];
            foreach ($names as $d=>$nm):
              $r = $hours[$d] ?? null;
              $txt = 'Fechado';
              if ($r && !empty($r['is_open']) && !empty($r['open1']) && !empty($r['close1'])) {
                $txt = substr((string)$r['open1'],0,5) . ' - ' . substr((string)$r['close1'],0,5);
                if (!empty($r['open2']) && !empty($r['close2'])) {
                  $txt .= ' / ' . substr((string)$r['open2'],0,5) . ' - ' . substr((string)$r['close2'],0,5);
                }
              }
              $rowClass = ((int)date('N') === $d) ? "border-t border-b bg-yellow-50" : "border-b";
          ?>
            <tr class="<?= $rowClass ?> border-gray-300">
              <td class="py-2 font-medium <?= ((int)date('N')===$d)?'text-black':'text-gray-700' ?>"><?= $nm ?></td>
              <td class="py-2 text-right"><?= e($txt) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal: Login do Cliente -->
  <div id="login-modal" class="fixed inset-0 bg-black/50 hidden z-50">
    <div class="bg-white max-w-sm mx-auto mt-24 rounded-2xl overflow-hidden shadow-xl">
      <div class="p-4 border-b flex items-center">
        <h3 class="font-semibold text-lg">Login do Cliente</h3>
        <button id="login-close" class="ml-auto px-3 py-1.5 rounded-xl border">Fechar</button>
      </div>
      <form id="login-form" class="p-4" method="post" action="<?= base_url(rawurlencode((string)$company['slug']).'/customer-login') ?>">
        <?php if (function_exists('csrf_field')) { echo csrf_field(); } ?>
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
</header>

<!-- MAIN (mesma largura do cabeçalho: p-5) -->
<div class="max-w-5xl mx-auto p-4">

<script>
  // Modal de horários
  (function(){
    const modal = document.getElementById('hours-modal');
    if (!modal) return;
    function open(){ modal.classList.remove('hidden'); }
    function close(){ modal.classList.add('hidden'); }
    document.getElementById('btn-hours')?.addEventListener('click', open);
    document.getElementById('btn-hours-ico')?.addEventListener('click', open);
    document.getElementById('hours-close')?.addEventListener('click', close);
    modal.addEventListener('click', (e)=>{ if (e.target===modal) close(); });
  })();

  // Modal de login
  (function(){
    const modal = document.getElementById('login-modal');
    if (!modal) return;
    function open(){ modal.classList.remove('hidden'); }
    function close(){ modal.classList.add('hidden'); }
    document.getElementById('btn-open-login')?.addEventListener('click', open);
    document.getElementById('login-close')?.addEventListener('click', close);
    modal.addEventListener('click', (e)=>{ if (e.target===modal) close(); });
  })();
</script>

<!-- Abas (categorias) -->
<div class="flex gap-2 overflow-x-auto flex-nowrap mb-3 pb-1">
<?php if ($mostraNovidade): ?>
    <a href="#novidades" class="category-tab shrink-0 px-4 py-1.5 rounded-full bg-orange-400 text-white font-semibold">Novidades</a>
<?php endif; ?>
<?php foreach ($categories as $c): ?>
    <a href="#cat-<?= (int)$c['id'] ?>" class="category-tab shrink-0 px-4 py-1.5 rounded-full border border-gray-300 bg-white text-gray-600"><?= e($c['name'] ?? 'Categoria') ?></a>
<?php endforeach; ?>
</div>

<script>
  // Destaque dinâmico das abas de categoria
  (function(){
    const tabs = Array.from(document.querySelectorAll('.category-tab'));
    if (!tabs.length) return;

    function activate(tab){
      tabs.forEach(t => {
        t.classList.remove('bg-orange-400','text-white','font-semibold');
        t.classList.add('border','border-gray-300','bg-white','text-gray-600');
      });
      tab.classList.add('bg-orange-400','text-white','font-semibold');
      tab.classList.remove('border','border-gray-300','bg-white','text-gray-600');
    }

    tabs.forEach(t => t.addEventListener('click', () => activate(t)));

    function onScroll(){
      let chosen = tabs[0];
      const offset = 80;
      tabs.forEach(t => {
        const id = t.getAttribute('href').slice(1);
        const anchor = document.getElementById(id);
        const target = anchor?.nextElementSibling || anchor;
        if (target && target.getBoundingClientRect().top - offset <= 0) {
          chosen = t;
        }
      });
      activate(chosen);
    }

    window.addEventListener('scroll', onScroll);
    onScroll();
  })();
</script>

<!-- Busca -->
<form method="get" action="<?= e(base_url(rawurlencode((string)$company['slug']))) ?>" class="mb-4">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Digite para buscar um item"
         class="w-full border rounded-xl px-3 py-2" />
</form>

<!-- Resultados da busca (carregados dinamicamente) -->
<div id="search-results" class="mb-4">
  <?php if ($q !== ''): ?>
    <h2 class="text-xl font-bold mb-2">Resultado da busca</h2>
    <div class="grid gap-3">
      <?php if (!$searchResults): ?>
        <div class="p-4 border bg-white rounded-xl">Nada encontrado para <strong><?= e($q) ?></strong>.</div>
      <?php endif; ?>
      <?php foreach ($searchResults as $p): include __DIR__ . '/partials_card.php'; endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
  // Busca instantânea de produtos
  (function(){
    const input   = document.querySelector('input[name="q"]');
    const results = document.getElementById('search-results');
    const url     = '<?= base_url(rawurlencode((string)$company['slug']).'/buscar') ?>';
    let timer;
    input?.addEventListener('input', function(){
      const term = input.value.trim();
      clearTimeout(timer);
      timer = setTimeout(async ()=>{
        if (term === '') { results.innerHTML = ''; return; }
        try {
          const res  = await fetch(url + '?q=' + encodeURIComponent(term), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          const html = await res.text();
          results.innerHTML = html;
        } catch (e) {
          console.error(e);
        }
      }, 300);
    });
  })();
</script>

<!-- ======== BLOCOS NO TOPO ======== -->
<?php if ($mostraNovidade): ?>
  <a id="novidades"></a>
  <h2 class="text-xl font-bold inline-block px-3 py-1 rounded-lg mb-2" style="background-color: <?= e($groupBgColor) ?>; color: <?= e($groupTextColor) ?>;">Novidades</h2>
  <div class="grid gap-3 mb-6">
    <?php foreach ($novidades as $p): ?>
      <?php include __DIR__ . '/partials_card.php'; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<!-- ======== /BLOCOS NO TOPO ======== -->

<!-- Seções por categoria -->
<a id="topo"></a>

<?php foreach ($categories as $c): ?>
  <a id="cat-<?= (int)$c['id'] ?>"></a>
  <h2 class="text-xl font-bold inline-block px-3 py-1 rounded-lg mb-2" style="background-color: <?= e($groupBgColor) ?>; color: <?= e($groupTextColor) ?>;">
    <?= e($c['name'] ?? 'Categoria') ?>
  </h2>
  <?php $items = array_values(array_filter($products, fn($p)=> (int)($p['category_id'] ?? 0) === (int)$c['id'])); ?>
  <div class="grid gap-3 mb-6">
    <?php foreach ($items as $p): include __DIR__ . '/partials_card.php'; endforeach; ?>
    <?php if (!$items): ?>
      <div class="p-4 border bg-white rounded-xl text-sm">Sem itens aqui ainda.</div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
