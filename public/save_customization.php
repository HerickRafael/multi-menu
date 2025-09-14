<?php
session_start();

if (!isset($_SESSION['customizations'])) {
    $_SESSION['customizations'] = [];
}

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

$addons = [];
if (isset($_POST['addons']) && is_array($_POST['addons'])) {
    foreach ($_POST['addons'] as $k => $qty) {
        $addons[(string)$k] = (int)$qty;
    }
}

$customSingle = [];
if (isset($_POST['custom_single']) && is_array($_POST['custom_single'])) {
    foreach ($_POST['custom_single'] as $g => $idx) {
        $customSingle[(int)$g] = (int)$idx;
    }
}

$customQty = [];
if (isset($_POST['custom_qty']) && is_array($_POST['custom_qty'])) {
    foreach ($_POST['custom_qty'] as $g => $items) {
        if (is_array($items)) {
            foreach ($items as $i => $qty) {
                $customQty[(int)$g][(int)$i] = (int)$qty;
            }
        }
    }
}

if ($productId > 0) {
    $_SESSION['customizations'][$productId] = [
        'addons' => $addons,
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
