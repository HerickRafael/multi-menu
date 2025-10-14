<?php
/**
 * Script de teste para verificar endpoints de configuração Evolution
 */

// Simular parâmetros
$slug = 'teste';
$instanceName = 'teste_instance';

// Informações de teste
echo "=== TESTE DE ENDPOINTS EVOLUTION SETTINGS ===\n\n";

echo "Slug: $slug\n";
echo "Instance: $instanceName\n\n";

// URLs que serão testadas
$baseUrl = 'http://localhost/multi-menu';
$getUrl = "$baseUrl/admin/$slug/evolution/instance/$instanceName/settings";
$postUrl = "$baseUrl/admin/$slug/evolution/instance/$instanceName/settings";

echo "GET URL: $getUrl\n";
echo "POST URL: $postUrl\n\n";

echo "=== TESTE CURL GET ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $getUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json'
    ],
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
if ($curlError) {
    echo "cURL Error: $curlError\n";
}

echo "\n=== TESTE CURL POST ===\n";

$testData = json_encode(['rejectCall' => true]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $postUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $testData,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
if ($curlError) {
    echo "cURL Error: $curlError\n";
}

echo "\n=== FIM DOS TESTES ===\n";