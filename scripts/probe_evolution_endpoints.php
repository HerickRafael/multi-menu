<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/models/Company.php';
require_once __DIR__ . '/../app/controllers/AdminEvolutionController.php';

$slug = $argv[1] ?? null;
if (!$slug) {
    echo "Usage: php probe_evolution_endpoints.php {slug}\n";
    exit(1);
}

$company = Company::findBySlug($slug);
if (!$company) {
    echo "Company with slug '{$slug}' not found\n";
    exit(1);
}

echo "Probing Evolution API for company={$company['id']} slug={$slug} server={$company['evolution_server_url']}\n\n";

$controller = new AdminEvolutionController();
$ref = new ReflectionClass($controller);
$useController = $ref->hasMethod('evolutionApiRequest');
if ($useController) {
    $method = $ref->getMethod('evolutionApiRequest');
    $method->setAccessible(true);
}

$paths = [
    '/instance/fetchInstances',
    '/instance/getAll',
    '/instances',
    '/api/instances',
    '/v2/instances',
    '/api/v2/instances',
    '/sessions',
    '/api/sessions',
    '/api/v2/sessions',
    '/whatsapp/instances',
    '/api/whatsapp/instances',
    '/wa/instances',
];

$headerNames = [
    'Authentication-Api-Key',
    'Authorization',
    'X-API-KEY',
    'X-Api-Key',
];

foreach ($paths as $p) {
    echo "--- TRY PATH: {$p} (GET) ---\n";
    if ($useController) {
        $r = $method->invoke($controller, $company, $p, 'GET', null);
        echo "via controller: ";
        print_r($r);
    }

    // try raw curl with different header names
    foreach ($headerNames as $h) {
        $ch = curl_init(rtrim($company['evolution_server_url'], '/') . '/' . ltrim($p, '/'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["{$h}: {$company['evolution_api_key']}", 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "curl header={$h} -> HTTP {$code} ";
        if ($err) echo "ERR: {$err}\n";
        else echo "body: " . substr($resp ?? '', 0, 400) . (strlen($resp ?? '') > 400 ? '... (truncated)' : '') . "\n";
    }
    echo "\n";
}

// also try POST /instances with number sample
$postPaths = ['/instance/createInstance', '/instances', '/api/instances', '/v2/instances', '/api/v2/instances'];
foreach ($postPaths as $p) {
    echo "--- TRY PATH: {$p} (POST create) ---\n";
    // try official payload shape
    $body = json_encode(['instanceName' => '5511999999999', 'token' => $company['evolution_api_key']]);
    foreach ($headerNames as $h) {
        $ch = curl_init(rtrim($company['evolution_server_url'], '/') . '/' . ltrim($p, '/'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["{$h}: {$company['evolution_api_key']}", 'Accept: application/json', 'Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "curl header={$h} -> HTTP {$code} ";
        if ($err) echo "ERR: {$err}\n";
        else echo "body: " . substr($resp ?? '', 0, 400) . (strlen($resp ?? '') > 400 ? '... (truncated)' : '') . "\n";
    }
    echo "\n";
}

echo "Probe finalizado.\n";
