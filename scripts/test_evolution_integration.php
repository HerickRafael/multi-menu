<?php
declare(strict_types=1);
// Script de teste de integração com a API Evolution usando o mock server.
// Ele tentará usar a rota interna do controller (reflection) se possível,
// senão fará chamadas HTTP diretas ao mock server.

require_once __DIR__ . '/../app/controllers/AdminEvolutionController.php';

// conf do mock
$mockHost = '127.0.0.1';
$mockPort = 8081;
$mockBase = "http://{$mockHost}:{$mockPort}";
$apiKey = 'testkey';

echo "Iniciando teste de integração com mock server em {$mockBase}\n";

$company = [
    'evolution_server_url' => $mockBase,
    'evolution_api_key' => $apiKey,
];

// Criar instancia via evolutionApiRequest
$controller = new AdminEvolutionController();

$ref = new ReflectionClass($controller);
if ($ref->hasMethod('evolutionApiRequest')) {
    $m = $ref->getMethod('evolutionApiRequest');
    $m->setAccessible(true);

    echo "Chamando POST /instance/createInstance\n";
    $res = $m->invoke($controller, $company, '/instance/createInstance', 'POST', ['instanceName' => '5511999999999', 'token' => 'testkey']);
    print_r($res);

    if (isset($res['data'])) {
        $id = $res['data']['instance_identifier'] ?? $res['data']['id'] ?? null;
        $qr = $res['data']['qr_code'] ?? null;
        echo "Criado id={$id} qr_len=" . strlen((string)$qr) . "\n";

    echo "Chamando GET /instance/fetchInstances?instanceName={id}\n";
    $res2 = $m->invoke($controller, $company, '/instance/fetchInstances', 'GET', null);
        print_r($res2);

    echo "Chamando POST /instance/deleteInstance\n";
    $res3 = $m->invoke($controller, $company, '/instance/deleteInstance', 'POST', ['instanceName' => $id]);
        print_r($res3);
    } else {
        echo "Falha na criação: " . ($res['error'] ?? 'unknown') . "\n";
    }
} else {
    echo "Método evolutionApiRequest não disponível via reflection. Fazendo requests HTTP direto.\n";
    // POST /instances
    $ch = curl_init($mockBase . '/instances');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authentication-Api-Key: ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['number' => '5511999999999']));
    $r = curl_exec($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "POST -> {$c} {$r}\n";
}

echo "Teste finalizado.\n";
