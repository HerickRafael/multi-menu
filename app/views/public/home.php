<?php
$title = ($company['name'] ?? 'Cardápio') . ' - Cardápio';
ob_start();

/* Helpers de badges */
function badgePromo($p){ return !empty($p['promo_price']); }
// use o helper global:
function badgeNew($p){ return is_new_product($p); }


/* Variáveis vindas do controller */
$q         = $q         ?? '';
$novidades = $novidades ?? [];

/* Flags: controller decide; view só obedece */
$mostraNovidade = isset($mostraNovidade) ? (bool)$mostraNovidade : (count($novidades) > 0);

$bannerUrl = !empty($company['banner']) ? base_url($company['banner']) : null;
?>
<header class="mb-4">
  <div class="rounded-2xl overflow-hidden">
    <?php if ($bannerUrl): ?>
      <div class="relative">
        <img src="<?= $bannerUrl ?>" class="w-full h-36 md:h-48 object-cover" alt="Banner">
        <div class="absolute inset-0 bg-black/30"></div>
      </div>
    <?php else: ?>
      <div class="bg-purple-900 h-24"></div>
    <?php endif; ?>

    <div class="bg-purple-900 text-white p-5 relative -mt-10 rounded-t-2xl">
      <div class="flex items-center gap-3">
        <img src="<?= base_url($company['logo'] ?: 'assets/logo-placeholder.png') ?>"
             class="w-16 h-16 rounded-xl object-cover border-4 border-purple-700 bg-white" alt="<?= e($company['name']) ?>">
        <div class="min-w-0">
          <h1 class="text-2xl font-bold"><?= e($company['name']) ?></h1>

          <!-- Linha de status + horário de hoje + info -->
          <div class="flex flex-wrap items-center gap-2 text-sm mt-1">
            <span class="<?= !empty($isOpenNow) ? 'bg-yellow-400 text-black' : 'bg-gray-300 text-gray-800' ?> px-2 py-0.5 rounded-lg font-semibold">
              <?= !empty($isOpenNow) ? 'Aberto!' : 'Fechado' ?>
            </span>
            <?php if (!empty($todayLabel)): ?>
              <button type="button" id="btn-hours" class="font-semibold"><?= e($todayLabel) ?></button>
              <span id="btn-hours-ico" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-yellow-400 text-black cursor-pointer">i</span>
            <?php endif; ?>

            <?php if (!empty($company['min_order'])): ?>
              <span class="text-sm opacity-90 mt-1">
                Pedido mínimo: <strong>R$ <?= number_format($company['min_order'], 2, ',', '.') ?></strong>
              </span>
            <?php endif; ?>

            <?php if (!empty($company['whatsapp'])): ?>
              <a class="inline-flex items-center gap-1 underline" href="https://wa.me/<?= e($company['whatsapp']) ?>" target="_blank" aria-label="WhatsApp">
                <!-- ícone WhatsApp (SVG) -->
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#FACC14" class="bi bi-whatsapp" viewBox="0 0 16 16">
                  <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/>
                </svg>
                WhatsApp
              </a>
            <?php endif; ?>
          </div>

          <?php if (!empty($company['address'])): ?>
            <div class="text-xs opacity-90 mt-1"><?= e($company['address']) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if (!empty($company['highlight_text'])): ?>
      <div class="bg-purple-100 p-4">
        <p class="bg-purple-700 text-white p-3 rounded-xl text-sm">
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
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-yellow-400 text-black">i</span>
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
              if ($r && !empty($r['is_open']) && $r['open1'] && $r['close1']) {
                $txt = substr($r['open1'],0,5) . ' - ' . substr($r['close1'],0,5);
                if (!empty($r['open2']) && !empty($r['close2'])) {
                  $txt .= ' / ' . substr($r['open2'],0,5) . ' - ' . substr($r['close2'],0,5);
                }
              }
              $rowClass = ($d === 1) ? "border-t border-b" : "border-b";
          ?>
            <tr class="<?= $rowClass ?> border-gray-300">
              <td class="py-2 font-medium <?= ((int)date('N')===$d)?'text-black':'text-gray-700' ?>"><?= $nm ?></td>
              <td class="py-2 text-right"><?= $txt ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</header>

<script>
  (function(){
    const modal = document.getElementById('hours-modal');
    function open(){ modal.classList.remove('hidden'); }
    function close(){ modal.classList.add('hidden'); }
    document.getElementById('btn-hours')?.addEventListener('click', open);
    document.getElementById('btn-hours-ico')?.addEventListener('click', open);
    document.getElementById('hours-close')?.addEventListener('click', close);
    modal?.addEventListener('click', (e)=>{ if (e.target===modal) close(); });
  })();
</script>

<!-- Abas (categorias) -->
<div class="flex flex-wrap gap-2 mb-3">
  <a href="#topo" class="px-3 py-1.5 rounded-xl border bg-yellow-300 font-semibold">Topo</a>
  <?php foreach ($categories as $c): ?>
    <a href="#cat-<?= (int)$c['id'] ?>" class="px-3 py-1.5 rounded-xl border bg-yellow-200"><?= e($c['name']) ?></a>
  <?php endforeach; ?>
</div>

<!-- Busca -->
<form method="get" action="<?= base_url($company['slug'] . '/buscar') ?>" class="mb-4">
  <input type="text" name="q" value="<?= e($q) ?>" placeholder="Digite para buscar um item"
         class="w-full border rounded-xl px-3 py-2" />
</form>

<!-- ======== BLOCOS NO TOPO ======== -->
<?php if ($mostraNovidade): ?>
  <h2 class="text-xl font-bold bg-yellow-400 text-black inline-block px-3 py-1 rounded-lg mb-2">Novidades</h2>
  <div class="grid gap-3 mb-6">
    <?php foreach ($novidades as $p): ?>
      <?php include __DIR__ . '/partials_card.php'; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<!-- ======== /BLOCOS NO TOPO ======== -->

<!-- Seções por categoria -->
<a id="topo"></a>

<?php if (!empty($q)): ?>
  <h2 class="text-xl font-bold mb-2">Resultado da busca</h2>
  <div class="grid gap-3">
    <?php if (!$products): ?>
      <div class="p-4 border bg-white rounded-xl">Nada encontrado para <strong><?= e($q) ?></strong>.</div>
    <?php endif; ?>
    <?php foreach ($products as $p): include __DIR__ . '/partials_card.php'; endforeach; ?>
  </div>
<?php else: ?>
  <?php foreach ($categories as $c): ?>
    <a id="cat-<?= (int)$c['id'] ?>"></a>
    <h2 class="text-xl font-bold bg-yellow-400 inline-block px-3 py-1 rounded-lg mb-2"><?= e($c['name']) ?></h2>
    <?php
      $items = array_values(array_filter($products, fn($p)=> (int)$p['category_id'] === (int)$c['id']));
    ?>
    <div class="grid gap-3 mb-6">
      <?php foreach ($items as $p): include __DIR__ . '/partials_card.php'; endforeach; ?>
      <?php if (!$items): ?>
        <div class="p-4 border bg-white rounded-xl text-sm">Sem itens aqui ainda.</div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
