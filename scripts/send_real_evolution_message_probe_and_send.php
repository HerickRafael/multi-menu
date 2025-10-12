<?php
declare(strict_types=1);
require __DIR__ . '/../app/config/db.php';
require __DIR__ . '/../app/models/Company.php';
require __DIR__ . '/../app/models/EvolutionInstance.php';
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/controllers/AdminEvolutionController.php';

$slug = $argv[1] ?? 'wollburger';
$to = $argv[2] ?? '5551920017687'; // without + preferred
$text = $argv[3] ?? "Mensagem de teste (probe send)";

$company = Company::findBySlug($slug);
if (!$company) { echo "Empresa {$slug} não encontrada\n"; exit(1); }

$controller = new AdminEvolutionController();
$ref = new ReflectionClass($controller);
$method = $ref->getMethod('evolutionApiRequest');
$method->setAccessible(true);

echo "Probing remote instances via /instance/fetchInstances...\n";
$res = $method->invoke($controller, $company, '/instance/fetchInstances', 'GET', null);
if (isset($res['error'])) { echo "Probe error: " . $res['error'] . "\n"; exit(2); }
$remote = $res['data'] ?? [];
if (!is_array($remote) || !$remote) { echo "Nenhuma instância remota encontrada\n"; exit(3); }

echo "Found " . count($remote) . " remote instances\n";

// pick candidate instance (prefer name matching 'Woll' or ownerJid containing target)
$chosenRemote = null;
foreach ($remote as $r) {
    $name = strtolower($r['name'] ?? $r['profileName'] ?? '');
    if (strpos($name, 'woll') !== false || strpos($r['ownerJid'] ?? '', str_replace('+','',$to)) !== false) {
        $chosenRemote = $r; break;
    }
}
if (!$chosenRemote) { $chosenRemote = $remote[0]; }

echo "Chosen remote instance: \n";
print_r($chosenRemote);

// Candidate instance names to try
$candidates = [];
if (!empty($chosenRemote['name'])) $candidates[] = $chosenRemote['name'];
if (!empty($chosenRemote['id'])) $candidates[] = $chosenRemote['id'];
if (!empty($chosenRemote['ownerJid'])) $candidates[] = preg_replace('/@.*$/','',$chosenRemote['ownerJid']);
if (!empty($chosenRemote['profileName'])) $candidates[] = $chosenRemote['profileName'];

$candidates = array_values(array_unique($candidates));

echo "Will try candidates: \n"; print_r($candidates);

// instantiate client
try {
    $apiKey = $company['evolution_api_key'] ?? null;
    $apiUrl = rtrim($company['evolution_server_url'] ?? '', '/');
    if (!$apiKey || !$apiUrl) { echo "Configuração Evolution ausente\n"; exit(4); }
    $client = new \EvolutionApiPlugin\EvolutionApi($apiKey, $apiUrl, 'v2');
} catch (Throwable $e) {
    echo "Erro ao instanciar client: " . $e->getMessage() . "\n"; exit(5);
}

foreach ($candidates as $instName) {
    echo "Trying to send using instanceName='{$instName}' number={$to}\n";
    try {
        $resp = $client->sendTextMessage($instName, $to, $text);
        echo "Success response:\n"; print_r($resp); exit(0);
    } catch (Throwable $e) {
        echo "Error for instanceName={$instName}: " . $e->getMessage() . "\n";
        // continue trying
    }
}

echo "All candidates tried and failed.\n";
exit(6);
