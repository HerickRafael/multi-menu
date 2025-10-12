<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config/db.php';

$company = null;
$db = db();
$st = $db->prepare('SELECT * FROM companies WHERE slug = ? LIMIT 1');
$st->execute(['wollburger']);
$company = $st->fetch(PDO::FETCH_ASSOC);

if (!$company) { echo "company not found\n"; exit(1); }

$apiKey = $company['evolution_api_key'] ?? '';
$apiUrl = rtrim($company['evolution_server_url'] ?? '', '/');

if (!class_exists('EvolutionApiPlugin\\EvolutionApi')) {
    echo "Class EvolutionApiPlugin\\EvolutionApi not found via double-escaped name.\n";
}
if (class_exists('\\EvolutionApiPlugin\\EvolutionApi')) {
    echo "Class found with leading backslash.\n";
}
if (class_exists('\\EvolutionApiPlugin\\EvolutionApi')) {
    echo "Found (escaped).\n";
}

$found = false;
foreach (['EvolutionApiPlugin\\EvolutionApi', '\\EvolutionApiPlugin\\EvolutionApi', 'EvolutionApiPlugin\\\\EvolutionApi'] as $name) {
    if (class_exists($name)) {
        echo "class_exists(\"$name\") = true\n";
        $found = true;
        try {
            $ref = new ReflectionClass($name);
            $methods = array_map(fn($m) => $m->getName(), $ref->getMethods(ReflectionMethod::IS_PUBLIC));
            echo "Public methods: " . implode(', ', $methods) . "\n";
        } catch (Throwable $e) {
            echo "Reflection error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "class_exists(\"$name\") = false\n";
    }
}

// try to instantiate
try {
    if ($found) {
        $client = new \EvolutionApiPlugin\EvolutionApi($apiKey, $apiUrl, 'v2');
        echo "Instantiated client class OK\n";
        echo "Has sendTextMessage: " . (method_exists($client, 'sendTextMessage') ? 'yes' : 'no') . "\n";
        echo "Has sendMessage: " . (method_exists($client, 'sendMessage') ? 'yes' : 'no') . "\n";
    }
} catch (Throwable $e) {
    echo "Instantiation error: " . $e->getMessage() . "\n";
}
