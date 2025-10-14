<?php
/**
 * Teste dos endpoints de configuração Evolution com dados reais
 */

// Simular uma empresa com configuração Evolution
$company = [
    'evolution_server_url' => 'https://evolutionvictor.mlojas.com',
    'evolution_api_key' => '0cdfec38b34fdae0d7624e8e28debd9f'
];

$instanceName = 'teste';

echo "=== TESTE EVOLUTION API SETTINGS ===\n\n";

// Teste 1: GET settings/find
echo "1. Testando GET /settings/find/$instanceName\n";
$getUrl = $company['evolution_server_url'] . "/settings/find/$instanceName";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $getUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'apikey: ' . $company['evolution_api_key']
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: " . substr($response, 0, 500) . "\n";
if ($curlError) {
    echo "cURL Error: $curlError\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Teste 2: POST settings/set
echo "2. Testando POST /settings/set/$instanceName\n";
$postUrl = $company['evolution_server_url'] . "/settings/set/$instanceName";

$testData = [
    'rejectCall' => true,
    'readMessages' => true,
    'alwaysOnline' => false
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $postUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'apikey: ' . $company['evolution_api_key']
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Data sent: " . json_encode($testData) . "\n";
echo "Response: " . substr($response, 0, 500) . "\n";
if ($curlError) {
    echo "cURL Error: $curlError\n";
}

echo "\n=== FIM DOS TESTES ===\n";