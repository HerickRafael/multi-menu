<?php
/**
 * Teste de mapeamento de configurações JavaScript
 */

echo "=== TESTE MAPEAMENTO CONFIGURAÇÕES ===\n\n";

// Dados que vêm da Evolution API (baseado no teste anterior)
$evolutionApiResponse = [
    "rejectCall" => true,  // ATIVO
    "msgCall" => "Não posso atender agora",
    "groupsIgnore" => false,
    "alwaysOnline" => true,  // ATIVO  
    "readMessages" => false, // INATIVO
    "readStatus" => false,
    "syncFullHistory" => false
];

echo "Resposta da Evolution API:\n";
echo json_encode($evolutionApiResponse, JSON_PRETTY_PRINT) . "\n\n";

// Mapeamento JavaScript correto
$toggleMappings = [
    ['settingKey' => 'rejectCall', 'toggleId' => 'toggleRejectCalls', 'statusId' => 'statusRejectCalls'],
    ['settingKey' => 'readMessages', 'toggleId' => 'toggleReadMessages', 'statusId' => 'statusReadMessages'],
    ['settingKey' => 'alwaysOnline', 'toggleId' => 'toggleAlwaysOnline', 'statusId' => 'statusAlwaysOnline']
];

echo "Processamento JavaScript esperado:\n";
foreach ($toggleMappings as $mapping) {
    $settingKey = $mapping['settingKey'];
    $toggleId = $mapping['toggleId'];
    $isEnabled = $evolutionApiResponse[$settingKey] === true;
    
    echo "- {$settingKey}: {$toggleId} = " . ($isEnabled ? 'ATIVO' : 'INATIVO') . "\n";
}

echo "\nResultado Visual Esperado:\n";
echo "- Rejeitar chamadas: ATIVO (toggle verde) + campo mensagem visível\n";
echo "- Ler mensagens: INATIVO (toggle cinza)\n";
echo "- Sempre online: ATIVO (toggle verde)\n";

echo "\n=== CÓDIGO JAVASCRIPT CORRETO ===\n";
echo "const settings = result.data; // {rejectCall: true, readMessages: false, alwaysOnline: true}\n";
echo "const isEnabled = settings['rejectCall'] === true; // true\n";
echo "toggle.dataset.enabled = isEnabled.toString(); // 'true'\n";
echo "updateToggleState(toggle, isEnabled); // toggle verde\n";

echo "\n=== FIM DO TESTE ===\n";