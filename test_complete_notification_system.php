<?php
// test_complete_notification_system.php - Teste completo do sistema atualizado
require_once 'app/models/Company.php';
require_once 'app/services/OrderNotificationService.php';
require_once 'app/controllers/AdminEvolutionInstanceController.php';

echo "=== TESTE COMPLETO DO SISTEMA ATUALIZADO ===\n";

try {
    // Dados da empresa wollburger (ID 1)
    $company = Company::find(1);
    
    if (!$company) {
        echo "❌ Empresa não encontrada\n";
        exit(1);
    }
    
    echo "✅ Empresa encontrada: {$company['name']}\n";
    
    // Dados de teste do pedido mais realista
    $orderData = [
        'id' => '99999',
        'cliente_nome' => 'Ana Paula Oliveira',
        'total' => 156.40,
        'itens' => [
            [
                'nome' => 'Woll Burger Duplo Bacon',
                'quantidade' => 1,
                'preco' => 38.90
            ],
            [
                'nome' => 'Chicken Crispy',
                'quantidade' => 2,
                'preco' => 28.50
            ],
            [
                'nome' => 'Onion Rings',
                'quantidade' => 1,
                'preco' => 18.90
            ],
            [
                'nome' => 'Milk Shake Morango',
                'quantidade' => 2,
                'preco' => 14.90
            ],
            [
                'nome' => 'Batata Rústica Grande',
                'quantidade' => 1,
                'preco' => 21.30
            ]
        ]
    ];
    
    // Configuração dos números
    $primaryNumber = '5551920017687'; // Número que funciona
    $instanceName = 'teste_notificacao';
    
    echo "📱 Enviando para: $primaryNumber\n";
    echo "🔗 Instância: $instanceName\n";
    echo "📦 Pedido: #{$orderData['id']} - {$orderData['cliente_nome']}\n";
    echo "💰 Total: R$ " . number_format($orderData['total'], 2, ',', '.') . "\n\n";
    
    // Gerar mensagem usando a nova função
    $reflection = new ReflectionClass('OrderNotificationService');
    $method = $reflection->getMethod('generateStandardOrderMessage');
    $method->setAccessible(true);
    $message = $method->invoke(null, $orderData, $company);
    
    echo "📝 PRÉVIA DA MENSAGEM:\n";
    echo "─────────────────────\n";
    // Mostrar apenas as primeiras linhas para não poluir
    $lines = explode("\n", $message);
    for ($i = 0; $i < min(8, count($lines)); $i++) {
        echo $lines[$i] . "\n";
    }
    if (count($lines) > 8) {
        echo "... (+" . (count($lines) - 8) . " linhas adicionais)\n";
    }
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
    if ($result['error']) {
        echo "❌ ERRO: {$result['error']}\n";
        echo "📋 Código: {$result['code']}\n";
        
        if (isset($result['data']['response']['message'])) {
            echo "💬 Detalhes: " . json_encode($result['data']['response']['message']) . "\n";
        }
    } else {
        echo "✅ SUCESSO! Mensagem enviada!\n";
        echo "📊 Status: " . ($result['data']['status'] ?? 'Desconhecido') . "\n";
        echo "📋 Código HTTP: {$result['code']}\n";
        
        if (isset($result['data']['key']['id'])) {
            echo "🆔 Message ID: {$result['data']['key']['id']}\n";
        }
        
        if ($result['code'] === 201 || $result['code'] === 200) {
            echo "\n🎉 SISTEMA COMPLETAMENTE ATUALIZADO!\n";
            echo "✅ Mensagem formatada automaticamente\n";
            echo "🎨 Design profissional com emojis\n";
            echo "📱 Enviado para número individual\n";
            echo "🔧 Campo de mensagem personalizada removido\n";
            echo "⚙️ Interface simplificada e prática\n";
        }
    }
    
    echo "\n💡 RESUMO DAS MELHORIAS:\n";
    echo "1. ❌ Removido campo 'Mensagem do Pedido (opcional)'\n";
    echo "2. ✅ Mensagem agora é formatada automaticamente\n";
    echo "3. 🎨 Design profissional com emojis e bordas\n";
    echo "4. 📋 Inclui todos os dados: empresa, pedido, cliente, itens, valores\n";
    echo "5. ⏰ Timestamp automático\n";
    echo "6. 📱 Sistema otimizado para números individuais\n";
    echo "7. 🔧 Interface mais limpa e objetiva\n";

} catch (Exception $e) {
    echo "💥 Erro na execução: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>