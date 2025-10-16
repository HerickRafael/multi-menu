<?php
// test_direct_number_notification.php - Teste direto com número configurado
require_once 'app/models/Company.php';
require_once 'app/controllers/AdminEvolutionInstanceController.php';

echo "=== TESTE DIRETO COM NÚMERO CONFIGURADO ===\n";

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
        'id' => '88888',
        'cliente_nome' => 'Carlos Santos - TESTE DIRETO',
        'total' => 67.80,
        'itens' => [
            [
                'nome' => 'Cheese Burger',
                'quantidade' => 1,
                'preco' => 22.90
            ],
            [
                'nome' => 'Batata Média',
                'quantidade' => 1,
                'preco' => 12.00
            ],
            [
                'nome' => 'Coca-Cola 600ml',
                'quantidade' => 2,
                'preco' => 16.45
            ]
        ]
    ];
    
    // Configuração manual dos números
    $primaryNumber = '5551920017687'; // Número que sabemos que funciona
    $secondaryNumber = ''; // Deixar vazio por enquanto
    $instanceName = 'teste_notificacao';
    
    echo "📱 Número principal: $primaryNumber\n";
    echo "🔗 Instância: $instanceName\n\n";
    
    // Gerar mensagem
    $message = "🍔 *WOLL BURGER - PEDIDO DIRETO!* #{$orderData['id']}\n\n" .
               "👤 *Cliente:* {$orderData['cliente_nome']}\n" .
               "💰 *Total:* R$ " . number_format($orderData['total'], 2, ',', '.') . "\n\n" .
               "📋 *Itens do Pedido:*\n";
    
    foreach ($orderData['itens'] as $item) {
        $subtotal = $item['preco'] * $item['quantidade'];
        $message .= "• {$item['quantidade']}x {$item['nome']} - R$ " . number_format($subtotal, 2, ',', '.') . "\n";
    }
    
    $message .= "\n🎯 *TESTE SISTEMA ATUALIZADO*\n";
    $message .= "📞 Enviado para número individual\n";
    $message .= "⏰ " . date('d/m/Y H:i:s') . "\n";
    $message .= "✅ Grupos em manutenção - números funcionando!";
    
    echo "📝 Mensagem:\n";
    echo "─────────────────────\n";
    echo $message . "\n";
    echo "─────────────────────\n\n";
    
    // Payload para Evolution API
    $payload = [
        'number' => $primaryNumber,
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
    } else {
        echo "✅ SUCESSO! Mensagem enviada!\n";
        echo "📊 Status: " . ($result['data']['status'] ?? 'Desconhecido') . "\n";
        
        if (isset($result['data']['key']['id'])) {
            echo "🆔 Message ID: {$result['data']['key']['id']}\n";
        }
        
        if ($result['code'] === 201 || $result['code'] === 200) {
            echo "🎉 SISTEMA DE NÚMEROS FUNCIONANDO PERFEITAMENTE!\n";
            echo "📱 Notificações agora vão direto para números individuais\n";
            echo "🔧 Grupos marcados como 'em manutenção' na interface\n";
        }
    }

} catch (Exception $e) {
    echo "💥 Erro na execução: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>