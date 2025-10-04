<?php
$name = 'mm_session';
if (function_exists('config')) {
    $name = config('session_name') ?? $name;
}
if ($name && session_name() !== $name) {
    session_name($name);
}
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$qty = isset($_POST['qty']) ? max(1, (int)$_POST['qty']) : 1;
$combo = [];
if (isset($_POST['combo']) && is_array($_POST['combo'])) {
    foreach ($_POST['combo'] as $gi => $id) {
        $combo[(int)$gi] = (int)$id;
    }
}

if ($productId > 0) {
    $_SESSION['cart'][] = [
        'product_id' => $productId,
        'qty' => $qty,
        'combo' => $combo,
    ];
    $message = 'Item adicionado à sacola!';
} else {
    $message = 'Produto inválido.';
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="pt-br">
<head><meta charset="utf-8"><title>Sacola</title></head>
<body>
<p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
<a href="index.php">Voltar</a>
</body>
</html>
