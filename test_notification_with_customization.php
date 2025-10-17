<?php
// Teste de notificação com personalização e combos
require_once __DIR__ . '/app/services/OrderNotificationService.php';
require_once __DIR__ . '/app/models/Company.php';
require_once __DIR__ . '/app/config/db.php';

echo "=== TESTE DE NOTIFICAÇÃO COM PERSONALIZAÇÃO E COMBOS ===\n\n";

// Dados simulados de um pedido com personalização e combos
$orderDataWithCustomization = [
    'id' => 999,
    'customer_name' => 'Cliente Teste',
    'customer_phone' => '5511999999999',
    'total' => 53.50,
    'subtotal' => 50.00,
    'delivery_fee' => 3.50,
    'discount' => 0,
    'payment_method' => 'Dinheiro',
    'items' => [
        [
            'name' => 'Woll Smash',
            'quantity' => 1,
            'price' => 16.00,
            'combo' => '', // Sem combo
            'customization' => 'Pão Brioche, 6x Bled Costela 90 (carne), Maionese, Cebola, Queijo Cheddar'
        ],
        [
            'name' => 'Combo Burger Completo',
            'quantity' => 2,
            'price' => 32.00,
            'combo' => 'Coca-Cola 350ml, Batata Frita Grande',
            'customization' => '2x Bacon, Molho Especial'
        ]
    ],
    'notes' => 'Pedido com personalização completa',
    'customer_address' => 'Rua Teste, 123 - Centro',
    'created_at' => date('Y-m-d H:i:s')
];

echo "📋 Dados do Pedido:\n";
echo json_encode($orderDataWithCustomization, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Obter company
$company = Company::find(1); // Wollburger

if (!$company) {
    echo "❌ Empresa não encontrada!\n";
    exit(1);
}

echo "🏢 Empresa: {$company['name']}\n\n";

// Teste de geração de mensagem padrão
$reflection = new ReflectionClass('OrderNotificationService');
$method = $reflection->getMethod('generateStandardOrderMessage');
$method->setAccessible(true);

echo "📱 MENSAGEM GERADA (Formato Padrão):\n";
echo str_repeat("=", 60) . "\n";
$message = $method->invoke(null, $orderDataWithCustomization, $company);
echo $message . "\n";
echo str_repeat("=", 60) . "\n\n";

// Verificar se os dados estão aparecendo
echo "✅ Verificações:\n";
echo "- Combo apareceu? " . (strpos($message, 'Coca-Cola') !== false ? 'SIM ✓' : 'NÃO ✗') . "\n";
echo "- Personalização apareceu? " . (strpos($message, 'Bled Costela') !== false ? 'SIM ✓' : 'NÃO ✗') . "\n";
echo "- Ícones corretos? " . (strpos($message, '🍱') !== false || strpos($message, '✏️') !== false ? 'SIM ✓' : 'NÃO ✗') . "\n";

echo "\n✨ Teste concluído!\n";
echo "\n💡 Para testar com envio real:\n";
echo "1. Configure uma instância Evolution\n";
echo "2. Ative a notificação de pedido\n";
echo "3. Faça um pedido real com personalização\n";
