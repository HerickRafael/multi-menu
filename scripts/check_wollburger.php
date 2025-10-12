<?php
require __DIR__ . '/../app/config/db.php';
$st = db()->prepare('SELECT id, slug, evolution_server_url, evolution_api_key FROM companies WHERE slug = ?');
$st->execute(['wollburger']);
$r = $st->fetch(PDO::FETCH_ASSOC);
var_export($r);
