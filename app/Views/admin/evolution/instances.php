<?php
$title = 'Evolution - Instâncias';
$slug  = rawurlencode((string)($company['slug'] ?? ''));
if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
ob_start(); ?>
<div class="mx-auto max-w-4xl p-4">
  <h2 class="mb-4 text-lg font-semibold">Instâncias conectadas</h2>
  <ul class="space-y-3">
    <?php foreach ($instances as $i): ?>
      <li class="rounded-xl border p-3 bg-white">
        <div class="flex items-center justify-between">
          <div>
            <strong><?= e($i['label'] ?: $i['number']) ?></strong>
            <div class="text-xs text-slate-500"><?= e($i['status']) ?> • <?= e($i['instance_identifier']) ?></div>
          </div>
          <div>
            <?php if ($i['qr_code']): ?>
              <img src="data:image/png;base64,<?= e($i['qr_code']) ?>" class="h-20" alt="QR">
            <?php endif; ?>
          </div>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layout.php';
