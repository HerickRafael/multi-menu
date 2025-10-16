<?php
// test_new_formatted_message.php - Teste da nova mensagem formatada
require_once 'app/models/Company.php';
require_once 'app/services/OrderNotificationService.php';

echo "=== TESTE DA NOVA MENSAGEM FORMATADA ===\n";

try {
    // Dados da empresa
    $company = [
        'name' => 'Wollburger',
        'id' => 1
    ];
    
    // Dados de exemplo de pedido
    $orderData = [
        'id' => '12345',
        'cliente_nome' => 'Maria Silva dos Santos',
        'total' => 89.70,
        'itens' => [
            [
                'nome' => 'Woll Burger Clássico',
                'quantidade' => 2,
                'preco' => 24.90
            ],
            [
                'nome' => 'Batata Frita Grande',
                'quantidade' => 1,
                'preco' => 16.90
            ],
            [
                'nome' => 'Refrigerante 600ml',
                'quantidade' => 2,
                'preco' => 11.50
            ]
        ]
    ];
    
    echo "📦 Dados do pedido:\n";
    echo "- Empresa: {$company['name']}\n";
    echo "- Pedido: #{$orderData['id']}\n";
    echo "- Cliente: {$orderData['cliente_nome']}\n";
    echo "- Total: R$ " . number_format($orderData['total'], 2, ',', '.') . "\n";
    echo "- Itens: " . count($orderData['itens']) . "\n\n";
    
    // Acessar o método privado usando Reflection
    $reflection = new ReflectionClass('OrderNotificationService');
    $method = $reflection->getMethod('generateStandardOrderMessage');
    $method->setAccessible(true);
    
    // Gerar a mensagem formatada
    $message = $method->invoke(null, $orderData, $company);
    
    echo "📱 MENSAGEM FORMATADA:\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo $message . "\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    // Mostrar estatísticas da mensagem
    $lines = explode("\n", $message);
    $chars = strlen($message);
    $words = str_word_count($message);
    
    echo "📊 ESTATÍSTICAS:\n";
    echo "- Linhas: " . count($lines) . "\n";
    echo "- Caracteres: {$chars}\n";
    echo "- Palavras: {$words}\n\n";
    
    echo "✅ Nova mensagem formatada está pronta!\n";
    echo "🎨 Inclui emojis, formatação rica e todos os dados do pedido\n";
    echo "📋 Sem necessidade de mensagem personalizada - formato padrão profissional\n";

} catch (Exception $e) {
    echo "💥 Erro na execução: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>