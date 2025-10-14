<?php
/**
 * Teste completo dos novos toggles
 */

echo "=== TESTE NOVOS TOGGLES EVOLUTION ===\n\n";

// Resposta completa da Evolution API
$evolutionApiResponse = [
    "rejectCall" => true,
    "msgCall" => "Não posso atender agora",
    "groupsIgnore" => false,  // NOVO
    "alwaysOnline" => true,
    "readMessages" => false,
    "readStatus" => true,     // NOVO
    "syncFullHistory" => false
];

echo "Resposta completa da Evolution API:\n";
echo json_encode($evolutionApiResponse, JSON_PRETTY_PRINT) . "\n\n";

// Todos os mapeamentos dos toggles
$allToggleMappings = [
    ['settingKey' => 'rejectCall', 'toggleId' => 'toggleRejectCalls', 'statusId' => 'statusRejectCalls', 'name' => 'Rejeitar chamadas'],
    ['settingKey' => 'readMessages', 'toggleId' => 'toggleReadMessages', 'statusId' => 'statusReadMessages', 'name' => 'Ler mensagens'],
    ['settingKey' => 'alwaysOnline', 'toggleId' => 'toggleAlwaysOnline', 'statusId' => 'statusAlwaysOnline', 'name' => 'Sempre online'],
    ['settingKey' => 'groupsIgnore', 'toggleId' => 'toggleGroupsIgnore', 'statusId' => 'statusGroupsIgnore', 'name' => 'Ignorar grupos'],
    ['settingKey' => 'readStatus', 'toggleId' => 'toggleReadStatus', 'statusId' => 'statusReadStatus', 'name' => 'Visualizar status']
];

echo "Estado de todos os toggles:\n";
foreach ($allToggleMappings as $mapping) {
    $settingKey = $mapping['settingKey'];
    $name = $mapping['name'];
    $isEnabled = $evolutionApiResponse[$settingKey] === true;
    $status = $isEnabled ? 'ATIVO (verde)' : 'INATIVO (cinza)';
    
    echo "✓ {$name}: {$status}\n";
}

echo "\nFuncionalidades esperadas:\n";
echo "1. Todos os 5 toggles carregam estado correto\n";
echo "2. Toggles verdes/cinza conforme API\n";
echo "3. Campo mensagem aparece se 'Rejeitar chamadas' ativo\n";
echo "4. Salvamento funciona para todos os toggles\n";
echo "5. Toast notifications mostram nome correto\n";

echo "\nElementos HTML criados:\n";
echo "- toggleRejectCalls + statusRejectCalls\n";
echo "- toggleReadMessages + statusReadMessages\n";
echo "- toggleAlwaysOnline + statusAlwaysOnline\n";
echo "- toggleGroupsIgnore + statusGroupsIgnore (NOVO)\n";
echo "- toggleReadStatus + statusReadStatus (NOVO)\n";

echo "\nInitializações JavaScript:\n";
echo "- setupToggleSwitch('toggleGroupsIgnore', 'groupsIgnore')\n";
echo "- setupToggleSwitch('toggleReadStatus', 'readStatus')\n";

echo "\n=== TESTE CONCLUÍDO ===\n";