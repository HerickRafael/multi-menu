<?php
/**
 * Teste para verificar se a API retorna msgCall
 */

$company = [
    'evolution_server_url' => 'https://evolutionvictor.mlojas.com',
    'evolution_api_key' => '0cdfec38b34fdae0d7624e8e28debd9f'
];

$instanceName = 'teste';

echo "=== TESTE msgCall na resposta da API ===\n\n";

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
curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($httpCode === 200) {
    $settings = json_decode($response, true);
    echo "Configurações completas:\n";
    echo json_encode($settings, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "Campo msgCall presente: " . (isset($settings['msgCall']) ? 'SIM' : 'NÃO') . "\n";
    if (isset($settings['msgCall'])) {
        echo "Valor msgCall: '" . $settings['msgCall'] . "'\n";
    }
} else {
    echo "Erro: $response\n";
}

echo "\n=== FIM DO TESTE ===\n";