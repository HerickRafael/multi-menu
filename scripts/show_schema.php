<?php
require __DIR__ . '/../app/config/db.php';
$pdo = db();
foreach (['products','order_items'] as $t) {
    echo "--- SHOW CREATE TABLE {$t} ---\n";
    $st = $pdo->query('SHOW CREATE TABLE ' . $t);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    var_export($r);
    echo "\n\n";
}
