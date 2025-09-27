<?php
session_start();

if (!isset($_SESSION['customizations'])) {
    $_SESSION['customizations'] = [];
}

require_once __DIR__ . '/../app/models/ProductCustomization.php';

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$mods = [];
if ($productId > 0) {
    try {
        $mods = ProductCustomization::loadForPublic($productId);
    } catch (Throwable $e) {
        $mods = [];
    }
}

$customSingle = [];
if (isset($_POST['custom_single']) && is_array($_POST['custom_single'])) {
    foreach ($_POST['custom_single'] as $g => $idx) {
        $gi = (int)$g;
        $sel = (int)$idx;
        if (!isset($mods[$gi]['items']) || !is_array($mods[$gi]['items'])) {
            continue;
        }
        $maxIdx = count($mods[$gi]['items']) - 1;
        if ($sel < 0 || $sel > $maxIdx) {
            continue;
        }
        $customSingle[$gi] = $sel;
    }
}

$customQty = [];
if (isset($_POST['custom_qty']) && is_array($_POST['custom_qty'])) {
    foreach ($_POST['custom_qty'] as $g => $items) {
        if (is_array($items)) {
            foreach ($items as $i => $qty) {
                $gi = (int)$g;
                $ii = (int)$i;
                if (!isset($mods[$gi]) || ($mods[$gi]['type'] ?? 'extra') === 'single') {
                    continue;
                }
                if (!isset($mods[$gi]['items'][$ii])) {
                    continue;
                }

                $item = $mods[$gi]['items'][$ii];
                $min = isset($item['min']) ? (int)$item['min'] : 0;
                $max = isset($item['max']) ? (int)$item['max'] : $min;
                if ($max <= 0) {
                    $max = max($min, 99);
                }

                $val = (int)$qty;
                if ($val < $min) {
                    $val = $min;
                }
                if ($max > 0 && $val > $max) {
                    $val = $max;
                }

                $customQty[$gi][$ii] = $val;
            }
        }
    }
}

if ($productId > 0) {
    $_SESSION['customizations'][$productId] = [
        'single' => $customSingle,
        'qty'    => $customQty,
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
