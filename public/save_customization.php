<?php

declare(strict_types=1);

use App\Application\Services\ProductCustomizationService;

session_start();

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap/app.php';

if (!isset($_SESSION['customizations'])) {
    $_SESSION['customizations'] = [];
}

$service = new ProductCustomizationService();

$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$mods = [];

if ($productId > 0) {
    try {
        $mods = $service->loadForPublic($productId);
    } catch (Throwable) {
        $mods = [];
    }
}

$customSingle = [];
if (isset($_POST['custom_single']) && is_array($_POST['custom_single'])) {
    foreach ($_POST['custom_single'] as $groupIndex => $selectedIndex) {
        $groupKey = (int) $groupIndex;
        $selectedKey = (int) $selectedIndex;
        if (!isset($mods[$groupKey]['items']) || !is_array($mods[$groupKey]['items'])) {
            continue;
        }

        $maxIndex = count($mods[$groupKey]['items']) - 1;
        if ($selectedKey < 0 || $selectedKey > $maxIndex) {
            continue;
        }

        $customSingle[$groupKey] = $selectedKey;
    }
}

$customQty = [];
if (isset($_POST['custom_qty']) && is_array($_POST['custom_qty'])) {
    foreach ($_POST['custom_qty'] as $groupIndex => $items) {
        if (!is_array($items)) {
            continue;
        }

        $groupKey = (int) $groupIndex;
        if (!isset($mods[$groupKey]) || ($mods[$groupKey]['type'] ?? 'extra') === 'single') {
            continue;
        }

        foreach ($items as $itemIndex => $qty) {
            $itemKey = (int) $itemIndex;
            if (!isset($mods[$groupKey]['items'][$itemKey])) {
                continue;
            }

            $item = $mods[$groupKey]['items'][$itemKey];
            $min = isset($item['min']) ? (int) $item['min'] : 0;
            $max = isset($item['max']) ? (int) $item['max'] : $min;
            if ($max <= 0) {
                $max = max($min, 99);
            }

            $value = (int) $qty;
            if ($value < $min) {
                $value = $min;
            }
            if ($max > 0 && $value > $max) {
                $value = $max;
            }

            $customQty[$groupKey][$itemKey] = $value;
        }
    }
}

if ($productId > 0) {
    $_SESSION['customizations'][$productId] = [
        'single' => $customSingle,
        'qty' => $customQty,
    ];
    $message = 'Personalização salva!';
} else {
    $message = 'Produto inválido.';
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="pt-br">
<head><meta charset="utf-8"><title>Personalização</title></head>
<body>
<p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<a href="index.php">Voltar</a>
</body>
</html>
