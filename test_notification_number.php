<?php
// test_notification_number.php - Teste para número individual
require_once 'app/models/Company.php';
require_once 'app/controllers/AdminEvolutionInstanceController.php';

echo "=== TESTE DE NOTIFICAÇÃO PARA NÚMERO INDIVIDUAL ===\n";

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
        'id' => '99999',
        'cliente_nome' => 'Teste Sistema Individual',
        'total' => 42.90,
        'itens' => [
            [
                'nome' => 'Woll Burger Especial',
                'quantidade' => 1,
                'preco' => 25.90
            ],
            [
                'nome' => 'Coca-Cola 350ml',
                'quantidade' => 1,
                'preco' => 7.00
            ],
            [
                'nome' => 'Batata Grande',
                'quantidade' => 1,
                'preco' => 10.00
            ]
        ]
    ];
    
    // Testar para número individual
    $phoneNumber = '5551920017687';
    $instanceName = 'teste_notificacao';
    
    echo "📱 Enviando para: $phoneNumber\n";
    echo "🔗 Instância: $instanceName\n\n";
    
    // Gerar mensagem
    $message = "🍔 *TESTE WOLL BURGER - Novo Pedido!* #{$orderData['id']}\n\n" .
               "👤 *Cliente:* {$orderData['cliente_nome']}\n" .
               "💰 *Total:* R$ " . number_format($orderData['total'], 2, ',', '.') . "\n\n" .
               "📋 *Itens:*\n";
    
    foreach ($orderData['itens'] as $item) {
        $subtotal = $item['preco'] * $item['quantidade'];
        $message .= "• {$item['quantidade']}x {$item['nome']} - R$ " . number_format($subtotal, 2, ',', '.') . "\n";
    }
    
    $message .= "\n⏰ Sistema funcionando perfeitamente!\n";
    $message .= "🎉 Notificações automáticas ativas!";
    
    echo "📝 Mensagem:\n";
    echo "─────────────────────\n";
    echo $message . "\n";
    echo "─────────────────────\n\n";
    
    // Payload para Evolution API
    $payload = [
        'number' => $phoneNumber,
        'text' => $message
    ];
    
    echo "📤 Enviando via sistema PHP...\n";
    
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
    } else {
        echo "✅ SUCESSO! Mensagem enviada via sistema PHP!\n";
        echo "📊 Status: " . ($result['data']['status'] ?? 'Desconhecido') . "\n";
        
        if (isset($result['data']['key']['id'])) {
            echo "🆔 Message ID: {$result['data']['key']['id']}\n";
        }
        
        if ($result['code'] === 201 || $result['code'] === 200) {
            echo "🎉 SISTEMA DE NOTIFICAÇÕES FUNCIONANDO!\n";
            echo "📱 Confirmado: API Evolution integrada com sucesso!\n";
        }
    }

} catch (Exception $e) {
    echo "💥 Erro na execução: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>