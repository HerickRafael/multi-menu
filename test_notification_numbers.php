<?php
// test_notification_numbers.php - Teste do novo sistema com números individuais
require_once 'app/models/Company.php';
require_once 'app/services/OrderNotificationService.php';

echo "=== TESTE SISTEMA DE NOTIFICAÇÃO COM NÚMEROS ===\n";

try {
    // Simular dados de pedido
    $orderData = [
        'id' => '77777',
        'cliente_nome' => 'João Silva - TESTE NÚMEROS',
        'total' => 125.50,
        'itens' => [
            [
                'nome' => 'Big Woll Burger',
                'quantidade' => 2,
                'preco' => 35.00
            ],
            [
                'nome' => 'Batata Frita Grande',
                'quantidade' => 1,
                'preco' => 18.00
            ],
            [
                'nome' => 'Refrigerante 350ml',
                'quantidade' => 3,
                'preco' => 12.50
            ]
        ]
    ];
    
    echo "📦 Dados do pedido:\n";
    echo "- ID: {$orderData['id']}\n";
    echo "- Cliente: {$orderData['cliente_nome']}\n";
    echo "- Total: R$ " . number_format($orderData['total'], 2, ',', '.') . "\n";
    echo "- Itens: " . count($orderData['itens']) . "\n\n";
    
    echo "🚀 Enviando notificação via OrderNotificationService...\n";
    
    // Usar o serviço de notificação
    $result = OrderNotificationService::sendOrderNotification(1, $orderData); // Company ID 1 = Wollburger
    
    if ($result) {
        echo "✅ SUCESSO! Notificação enviada com sucesso!\n";
        echo "📱 Sistema funcionando com números individuais\n";
    } else {
        echo "❌ FALHA! Não foi possível enviar a notificação\n";
        echo "⚠️ Verifique os logs para mais detalhes\n";
    }
    
    echo "\n💡 DICA: Para configurar os números:\n";
    echo "1. Acesse a página de configuração da instância\n";
    echo "2. Ative a opção 'Notificação de Pedido'\n";
    echo "3. Configure o número principal (obrigatório)\n";
    echo "4. Configure o número secundário (opcional)\n";
    echo "5. Salve a configuração\n";

} catch (Exception $e) {
    echo "💥 Erro na execução: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>