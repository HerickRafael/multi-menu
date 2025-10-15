<?php
// Component: Card de produto
// Espera a variável $p (produto) e $company disponível no escopo
if (!isset($p) || !is_array($p)) {
    return;
}
?>
<a href="<?= base_url(rawurlencode((string)($company['slug'] ?? '')) . '/produto/' . (int)$p['id']) ?>" class="block">
  <div class="ui-card rounded-2xl shadow p-4 bg-white border flex gap-3 hover:bg-gray-50">
    <div class="w-24 h-24 rounded-xl bg-gray-100 overflow-hidden relative">
      <img src="<?= base_url($p['image'] ?: 'assets/logo-placeholder.png') ?>"
           alt="<?= e($p['name']) ?>"
           class="w-full h-full object-cover lazy-load opacity-0 transition-opacity duration-300"
           loading="lazy"
           onload="this.style.opacity='1'"
           onerror="this.src='<?= base_url('assets/logo-placeholder.png') ?>'">
      <!-- Skeleton loader -->
      <div class="absolute inset-0 bg-gradient-to-r from-gray-100 via-gray-200 to-gray-100 animate-pulse skeleton-shimmer"></div>
    </div>

    <div class="flex-1">
      <div class="flex items-center gap-2 mb-1">
        <?php if (badgePromo($p)): ?>
          <span class="ui-badge ui-badge--warning text-xs bg-yellow-300 text-black font-semibold px-2 py-0.5 rounded-lg">Promoção!</span>
        <?php endif; ?>
        <?php if (is_new_product($p)): ?>
          <span class="ui-badge ui-badge--new text-xs bg-blue-600 text-white font-semibold px-2 py-0.5 rounded-lg">Novidade!</span>
        <?php endif; ?>
      </div>

      <h3 class="font-semibold leading-5"><?= e($p['name']) ?></h3>

      <?php if (!empty($p['description'])): ?>
        <p class="text-sm text-gray-600 line-clamp-2">
          <?= e($p['description']) ?> <span class="underline">Ver mais</span>
        </p>
      <?php endif; ?>

      <?php
        $priceVal = isset($p['price']) ? (float)$p['price'] : 0;
$promoRaw = $p['promo_price'] ?? null;
$promoVal = null;

if ($promoRaw !== null && $promoRaw !== '') {
    $promoStr = is_array($promoRaw) ? reset($promoRaw) : $promoRaw;
    $promoStr = trim((string)$promoStr);

    if ($promoStr !== '') {
        $promoStr = str_replace(' ', '', $promoStr);

        if (strpos($promoStr, ',') !== false && strpos($promoStr, '.') !== false) {
            $promoStr = str_replace('.', '', $promoStr);
        }
        $promoStr = str_replace(',', '.', $promoStr);

        if (is_numeric($promoStr)) {
            $promoVal = (float)$promoStr;
        }
    }
}
$hasPromo = $priceVal > 0 && $promoVal !== null && $promoVal > 0 && $promoVal < $priceVal;
?>
      <div class="mt-1">
        <?php if ($hasPromo): ?>
          <span class="text-sm text-gray-400 line-through">
            R$ <?= number_format($priceVal, 2, ',', '.') ?>
          </span>
          <span class="ml-2 text-lg font-bold">
            R$ <?= number_format($promoVal, 2, ',', '.') ?>
          </span>
        <?php else: ?>
          <span class="text-lg font-bold">
            R$ <?= number_format($priceVal, 2, ',', '.') ?>
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</a>
