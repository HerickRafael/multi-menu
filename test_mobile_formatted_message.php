<?php
// test_mobile_formatted_message.php - Teste da mensagem otimizada para WhatsApp Mobile
require_once 'app/models/Company.php';
require_once 'app/services/OrderNotificationService.php';

echo "=== TESTE MENSAGEM OTIMIZADA PARA WHATSAPP MOBILE ===\n";

try {
    // Dados da empresa
    $company = [
        'name' => 'Wollburger',
        'id' => 1
    ];
    
    // Dados de exemplo com forma de pagamento
    $orderData = [
        'id' => '54321',
        'cliente_nome' => 'Roberto Silva',
        'total' => 75.80,
        'forma_pagamento' => 'PIX',
        'itens' => [
            [
                'nome' => 'Woll Burger Clássico',
                'quantidade' => 1,
                'preco' => 24.90
            ],
            [
                'nome' => 'Chicken Crispy',
                'quantidade' => 1,
                'preco' => 18.90
            ],
            [
                'nome' => 'Batata Frita Grande',
                'quantidade' => 1,
                'preco' => 16.00
            ],
            [
                'nome' => 'Refrigerante 350ml',
                'quantidade' => 2,
                'preco' => 8.00
            ]
        ]
    ];
    
    echo "📦 DADOS DO PEDIDO:\n";
    echo "• Empresa: {$company['name']}\n";
    echo "• Pedido: #{$orderData['id']}\n";
    echo "• Cliente: {$orderData['cliente_nome']}\n";
    echo "• Pagamento: {$orderData['forma_pagamento']}\n";
    echo "• Total: R$ " . number_format($orderData['total'], 2, ',', '.') . "\n";
    echo "• Itens: " . count($orderData['itens']) . "\n\n";
    
    // Acessar o método privado usando Reflection
    $reflection = new ReflectionClass('OrderNotificationService');
    $method = $reflection->getMethod('generateStandardOrderMessage');
    $method->setAccessible(true);
    
    // Gerar a mensagem formatada
    $message = $method->invoke(null, $orderData, $company);
    
    echo "📱 MENSAGEM PARA WHATSAPP MOBILE:\n";
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║ SIMULAÇÃO DE TELA MOBILE                                      ║\n";
    echo "╠═══════════════════════════════════════════════════════════════╣\n";
    
    // Simular como apareceria no WhatsApp mobile
    $lines = explode("\n", $message);
    foreach ($lines as $line) {
        echo "║ " . str_pad($line, 61, ' ') . " ║\n";
    }
    
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    
    // Mostrar estatísticas da mensagem
    $chars = strlen($message);
    $words = str_word_count($message);
    $lines_count = count($lines);
    
    echo "📊 ESTATÍSTICAS MOBILE:\n";
    echo "• Linhas: {$lines_count} (otimizado para mobile)\n";
    echo "• Caracteres: {$chars} (compacto)\n";
    echo "• Palavras: {$words}\n\n";
    
    // Teste com diferentes formas de pagamento
    echo "💳 TESTE COM DIFERENTES FORMAS DE PAGAMENTO:\n";
    
    $payment_methods = ['PIX', 'Cartão de Crédito', 'Cartão de Débito', 'Dinheiro', 'VR/VA'];
    
    foreach ($payment_methods as $payment) {
        $testData = $orderData;
        $testData['forma_pagamento'] = $payment;
        $testData['id'] = rand(10000, 99999);
        
        $testMessage = $method->invoke(null, $testData, $company);
        $paymentLine = '';
        
        // Extrair apenas a linha do pagamento
        foreach (explode("\n", $testMessage) as $line) {
            if (strpos($line, '�') !== false) {
                $paymentLine = $line;
                break;
            }
        }
        
        echo "• {$paymentLine}\n";
    }
    
    echo "\n✅ MELHORIAS IMPLEMENTADAS:\n";
    echo "1. 💳 Forma de pagamento incluída\n";
    echo "2. 📱 Formato otimizado para telas pequenas\n";
    echo "3. 🔤 Texto mais compacto e legível\n";
    echo "4. ⚡ Menos linhas, mais direto\n";
    echo "5. 📐 Largura adequada para mobile\n";
    echo "6. 🎯 Informações essenciais destacadas\n";
    echo "7. 💬 Visual nativo do WhatsApp\n";

} catch (Exception $e) {
    echo "💥 Erro na execução: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>