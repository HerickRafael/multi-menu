<?php
$title = 'Buscar - ' . e($company['name']);
ob_start();
?>

<h1 class="text-xl font-semibold mb-2">Buscar</h1>
<?php if (!empty($q)): ?>
  <div class="text-sm opacity-70 mb-4">Você buscou: <strong><?= e($q) ?></strong></div>
<?php endif; ?>

<?php if ($temAbas): ?>
  <div class="tabs w-full mt-2">
    <ul class="flex gap-2 border-b">
      <?php if ($mostraNovidade): ?>
        <li><a href="#tab-novidade" class="px-3 py-2 block">Novidades</a></li>
      <?php endif; ?>
      <?php if ($mostraMaisPedidos): ?>
        <li><a href="#tab-mais" class="px-3 py-2 block">Mais pedidos</a></li>
      <?php endif; ?>
    </ul>

    <?php if ($mostraNovidade): ?>
      <div id="tab-novidade" class="py-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <?php foreach ($novidades as $p): ?>
            <?php include __DIR__ . '/partials_card.php'; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($mostraMaisPedidos): ?>
      <div id="tab-mais" class="py-4">
        <?php if (!empty($topMaisPedido)): ?>
          <div class="mb-3 text-sm">
            <span class="px-2 py-1 rounded bg-black/5">Top 1:</span>
            <strong><?= e($topMaisPedido['name']) ?></strong>
            <span class="opacity-70">— Pedidos: <?= (int)$topMaisPedido['total_pedidos'] ?></span>
          </div>
        <?php endif; ?>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <?php foreach ($maisPedidos as $p): ?>
            <div class="text-xs opacity-70 mb-1">Pedidos: <?= (int)$p['total_pedidos'] ?></div>
            <?php include __DIR__ . '/partials_card.php'; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
