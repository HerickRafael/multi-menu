<a href="<?= base_url(rawurlencode((string)($company['slug'] ?? '')) . '/produto/' . (int)$p['id']) ?>" class="block">
  <div class="rounded-2xl shadow p-4 bg-white border flex gap-3 hover:bg-gray-50">
    <img src="<?= base_url($p['image'] ?: 'assets/logo-placeholder.png') ?>"
         alt="<?= e($p['name']) ?>"
         class="w-24 h-24 object-cover rounded-xl">

    <div class="flex-1">
      <div class="flex items-center gap-2 mb-1">
        <?php if (badgePromo($p)): ?>
          <span class="text-xs bg-yellow-300 text-black font-semibold px-2 py-0.5 rounded-lg">Promoção!</span>
        <?php endif; ?>
        <?php if (is_new_product($p)): ?>
          <span class="text-xs bg-blue-600 text-white font-semibold px-2 py-0.5 rounded-lg">Novidade!</span>
        <?php endif; ?>
      </div>

      <h3 class="font-semibold leading-5"><?= e($p['name']) ?></h3>

      <?php if (!empty($p['description'])): ?>
        <p class="text-sm text-gray-600 line-clamp-2">
          <?= e($p['description']) ?> <span class="underline">Ver mais</span>
        </p>
      <?php endif; ?>

      <div class="mt-1">
        <?php if (!empty($p['promo_price'])): ?>
          <span class="text-sm text-gray-400 line-through">
            R$ <?= number_format($p['price'], 2, ',', '.') ?>
          </span>
          <span class="ml-2 text-lg font-bold">
            R$ <?= number_format($p['promo_price'], 2, ',', '.') ?>
          </span>
        <?php else: ?>
          <span class="text-lg font-bold">
            R$ <?= number_format($p['price'], 2, ',', '.') ?>
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</a>
