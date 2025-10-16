<?php
// test_grupo_novo.php - Teste para o grupo "gruponovo"
require_once 'app/models/Company.php';
require_once 'app/controllers/AdminEvolutionInstanceController.php';

echo "=== TESTE PARA GRUPO NOVO ===\n";

try {
    // Dados da empresa wollburger (ID 1)
    $company = Company::find(1);
    
    if (!$company) {
        echo "❌ Empresa não encontrada\n";
        exit(1);
    }
    
    echo "✅ Empresa encontrada: {$company['name']}\n";
    
    // Dados de teste do pedido
    $orderData = [
        'id' => '55555',
        'cliente_nome' => 'Maria dos Santos - TESTE GRUPO NOVO',
        'total' => 89.90,
        'itens' => [
            [
                'nome' => 'Woll Burger Especial',
                'quantidade' => 2,
                'preco' => 28.90
            ],
            [
                'nome' => 'Onion Rings',
                'quantidade' => 1,
                'preco' => 15.00
            ],
            [
                'nome' => 'Milk Shake Chocolate',
                'quantidade' => 2,
                'preco' => 9.00
            ]
        ]
    ];
    
    // Configuração para o grupo novo
    $groupId = '120363420497882598@g.us'; // grupo "gruponovo"
    $instanceName = 'teste_notificacao';
    
    echo "📱 Enviando para grupo: gruponovo\n";
    echo "🆔 Group ID: $groupId\n";
    echo "🔗 Instância: $instanceName\n\n";
    
    // Gerar mensagem personalizada
    $message = "🍔 *WOLL BURGER - NOVO PEDIDO!* #{$orderData['id']}\n\n" .
               "👤 *Cliente:* {$orderData['cliente_nome']}\n" .
               "💰 *Total:* R$ " . number_format($orderData['total'], 2, ',', '.') . "\n\n" .
               "📋 *Itens do Pedido:*\n";
    
    foreach ($orderData['itens'] as $item) {
        $subtotal = $item['preco'] * $item['quantidade'];
        $message .= "• {$item['quantidade']}x {$item['nome']} - R$ " . number_format($subtotal, 2, ',', '.') . "\n";
    }
    
    $message .= "\n🎯 *TESTE PARA GRUPO NOVO*\n";
    $message .= "⏰ Enviado em: " . date('d/m/Y H:i:s') . "\n";
    $message .= "🚀 Sistema de notificações funcionando!";
    
    echo "📝 Mensagem:\n";
    echo "─────────────────────\n";
    echo $message . "\n";
    echo "─────────────────────\n\n";
    
    // Payload para Evolution API
    $payload = [
        'number' => $groupId,
        'text' => $message
    ];
    
    echo "📤 Enviando via Evolution API...\n";
    
    // Enviar via Evolution API
    $controller = new AdminEvolutionInstanceController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('evolutionApiRequest');
    $method->setAccessible(true);
    
    $result = $method->invoke(
        $controller, 
        $company, 
        "/message/sendText/$instanceName", 
        'POST', 
        $payload
    );
    
    echo "📥 Resposta da API:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    if ($result['error']) {
        echo "❌ ERRO: {$result['error']}\n";
        
        // Se falhou para grupo, tentar número individual como fallback
        echo "\n🔄 Tentando fallback para número individual...\n";
        
        $fallbackNumber = '5551920017687';
        $fallbackMessage = "📱 *NOTIFICAÇÃO DE PEDIDO (FALLBACK)*\n" .
                         "(Grupo indisponível - enviado para número individual)\n\n" . $message;
        
        $payloadFallback = [
            'number' => $fallbackNumber,
            'text' => $fallbackMessage
        ];
        
        $resultFallback = $method->invoke(
            $controller, 
            $company, 
            "/message/sendText/$instanceName", 
            'POST', 
            $payloadFallback
        );
        
        if (!$resultFallback['error']) {
            echo "✅ SUCESSO! Mensagem enviada para número individual: $fallbackNumber\n";
            echo "📊 Status: " . ($resultFallback['data']['status'] ?? 'Desconhecido') . "\n";
            if (isset($resultFallback['data']['key']['id'])) {
                echo "🆔 Message ID: {$resultFallback['data']['key']['id']}\n";
            }
        } else {
            echo "❌ Fallback também falhou: {$resultFallback['error']}\n";
        }
        
    } else {
        echo "✅ SUCESSO! Mensagem enviada para o grupo!\n";
        echo "📊 Status: " . ($result['data']['status'] ?? 'Desconhecido') . "\n";
        
        if (isset($result['data']['key']['id'])) {
            echo "🆔 Message ID: {$result['data']['key']['id']}\n";
        }
        
        if ($result['code'] === 201 || $result['code'] === 200) {
            echo "🎉 GRUPO NOVO FUNCIONANDO PERFEITAMENTE!\n";
        }
    }

} catch (Exception $e) {
    echo "💥 Erro na execução: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>