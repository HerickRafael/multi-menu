<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config/db.php';
$db = db();
$st = $db->prepare('SELECT * FROM companies WHERE slug = ? LIMIT 1');
$st->execute(['wollburger']);
$company = $st->fetch(PDO::FETCH_ASSOC);
if (!$company) { echo "company not found\n"; exit(1); }
try {
    $client = new \EvolutionApiPlugin\EvolutionApi($company['evolution_api_key'], rtrim($company['evolution_server_url'], '/'), 'v2');
    $ref = new ReflectionClass($client);
    $methods = array_map(fn($m)=>$m->getName(), $ref->getMethods(ReflectionMethod::IS_PUBLIC));
    echo "Methods: \n" . implode('\n', $methods) . "\n\n";
    if ($ref->hasMethod('sendMediaMessage')) {
        $m = $ref->getMethod('sendMediaMessage');
        echo "sendMediaMessage params: ";
        foreach ($m->getParameters() as $p) echo $p->getName() . ' '; echo "\n";
    } else {
        echo "sendMediaMessage not found\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
