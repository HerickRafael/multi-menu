<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/config/db.php';
$db = db();
$slug = $argv[1] ?? 'wollburger';

$st = $db->prepare('SELECT * FROM companies WHERE slug = ? LIMIT 1');
$st->execute([$slug]);
$company = $st->fetch(PDO::FETCH_ASSOC) ?: null;

$st2 = $db->prepare('SELECT id, company_id, label, number, instance_identifier, qr_code, status, connected_at, created_at FROM evolution_instances WHERE company_id = ? ORDER BY created_at DESC');
$st2->execute([ (int)($company['id'] ?? 0) ]);
$instances = $st2->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['company' => $company, 'instances' => $instances], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
