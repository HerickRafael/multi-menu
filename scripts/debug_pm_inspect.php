<?php
require_once __DIR__ . '/../app/config/db.php';
// lista últimos 30 registros de payment_methods
try {
    $db = db();
    $st = $db->query("SELECT id, name, type, icon, meta, pix_key, active, sort_order FROM payment_methods ORDER BY id DESC LIMIT 30");
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "DB_ERROR:" . $e->getMessage();
    exit(1);
}
$uploads = [];
$upDir = __DIR__ . '/../public/uploads';
if (is_dir($upDir)) {
    $uploads = array_values(array_filter(scandir($upDir), function($f){ return !in_array($f, ['.', '..']); }));
}
$brands = [];
$bd = __DIR__ . '/../public/assets/card-brands';
if (is_dir($bd)) {
    $brands = array_values(array_filter(scandir($bd), function($f){ return !in_array($f, ['.', '..']); }));
}
$out = [
    'rows' => $rows,
    'uploads' => $uploads,
    'card_brands' => $brands,
];
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
