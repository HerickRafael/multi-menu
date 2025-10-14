<?php
/**
 * Teste da nova implementação de configurações
 */

// Simular uma empresa com configuração Evolution
$company = [
    'evolution_server_url' => 'https://evolutionvictor.mlojas.com',
    'evolution_api_key' => '0cdfec38b34fdae0d7624e8e28debd9f'
];

$instanceName = 'teste';

echo "=== TESTE NOVA IMPLEMENTAÇÃO ===\n\n";

// 1. Buscar configurações atuais
echo "1. Buscando configurações atuais...\n";
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

if ($httpCode === 200) {
    $currentSettings = json_decode($response, true);
    echo "Configurações atuais: " . json_encode($currentSettings, JSON_PRETTY_PRINT) . "\n\n";
    
    // 2. Simular mudança (toggle de readMessages)
    echo "2. Simulando mudança (toggle readMessages)...\n";
    
    // Configurações padrão
    $defaultSettings = [
        'rejectCall' => false,
        'msgCall' => '',
        'groupsIgnore' => false,
        'alwaysOnline' => false,
        'readMessages' => false,
        'readStatus' => false,
        'syncFullHistory' => false
    ];
    
    // Nova configuração (apenas readMessages alterado)
    $newInput = ['readMessages' => !$currentSettings['readMessages']];
    
    // Mesclar: padrão + atual + novo
    $finalSettings = array_merge($defaultSettings, $currentSettings, $newInput);
    
    echo "Dados que serão enviados: " . json_encode($finalSettings, JSON_PRETTY_PRINT) . "\n\n";
    
    // 3. Fazer POST com dados completos
    echo "3. Enviando configurações completas...\n";
    $postUrl = $company['evolution_server_url'] . "/settings/set/$instanceName";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $postUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($finalSettings),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'apikey: ' . $company['evolution_api_key']
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
    
} else {
    echo "Erro ao buscar configurações: HTTP $httpCode\n";
    echo "Response: $response\n";
}

echo "\n=== FIM DO TESTE ===\n";