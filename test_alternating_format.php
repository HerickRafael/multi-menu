<?php
// Teste de formatação alternada (negrito/normal)
require_once __DIR__ . '/app/services/OrderNotificationService.php';
require_once __DIR__ . '/app/models/Company.php';
require_once __DIR__ . '/app/config/db.php';

echo "=== TESTE DE FORMATAÇÃO ALTERNADA ===\n\n";

$orderDataAlternating = [
    'id' => 1002,
    'customer_name' => 'Cliente Teste',
    'customer_phone' => '5511999999999',
    'total' => 45.00,
    'subtotal' => 42.00,
    'delivery_fee' => 3.00,
    'discount' => 0,
    'payment_method' => 'Cartão',
    'items' => [
        [
            'name' => 'Burger Premium',
            'quantity' => 1,
            'price' => 28.00,
            'combo' => 'Coca-Cola 350ml, Batata Frita Grande, Molho Extra',
            'customization' => '+2x Bacon, +1x Queijo, Sem Cebola, +1x Picles'
        ],
        [
            'name' => 'Hot Dog Especial',
            'quantity' => 1,
            'price' => 14.00,
            'combo' => 'Suco de Laranja, Batata Palito',
            'customization' => 'Sem Mostarda, +1x Milho, +1x Purê'
        ]
    ],
    'notes' => '',
    'customer_address' => 'Rua Teste, 456',
    'created_at' => date('Y-m-d H:i:s')
];

$company = Company::find(1);

if (!$company) {
    echo "❌ Empresa não encontrada!\n";
    exit(1);
}

echo "🏢 Empresa: {$company['name']}\n\n";

$reflection = new ReflectionClass('OrderNotificationService');
$method = $reflection->getMethod('generateStandardOrderMessage');
$method->setAccessible(true);

echo "📱 MENSAGEM FORMATADA:\n";
echo str_repeat("=", 60) . "\n";
$message = $method->invoke(null, $orderDataAlternating, $company);
echo $message . "\n";
echo str_repeat("=", 60) . "\n\n";

echo "✅ Verificações:\n";
echo "- Itens em linhas separadas? " . (substr_count($message, "\n     ") > 0 ? 'SIM ✓' : 'NÃO ✗') . "\n";
echo "- Alternância de negrito? " . (substr_count($message, "*") >= 8 ? 'SIM ✓' : 'NÃO ✗') . "\n";

echo "\n📝 LEGENDA DO FORMATO:\n";
echo "  Linha 1: *Negrito*\n";
echo "  Linha 2: Normal\n";
echo "  Linha 3: *Negrito*\n";
echo "  Linha 4: Normal\n";
echo "  ... e assim por diante\n\n";

echo "✨ Teste concluído!\n";
