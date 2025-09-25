<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Carrinho - <?= e($slug); ?></title>
    <link rel="stylesheet" href="<?= e(base_url('assets/css/app.css')); ?>">
</head>
<body>
    <main>
        <h1>Carrinho de compras</h1>
        <?php if (empty($items)): ?>
            <p>Seu carrinho está vazio.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($items as $productId => $quantity): ?>
                    <li>Produto #<?= e((string) $productId); ?> &times; <?= e((string) $quantity); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </main>
</body>
</html>
