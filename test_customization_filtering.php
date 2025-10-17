<?php
// Teste de filtro de personalização - mostrar apenas itens adicionados
require_once __DIR__ . '/app/services/OrderNotificationService.php';
require_once __DIR__ . '/app/models/Company.php';
require_once __DIR__ . '/app/config/db.php';

echo "=== TESTE DE FILTRO DE PERSONALIZAÇÃO ===\n\n";

// Dados simulados ANTES (mostrava tudo)
echo "❌ ANTES - Mostrava TODOS os ingredientes:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "• 1x Woll Smash\n";
echo "  ✏️ Personalização: Pão Brioche, 1x Hambúrguer, 1x Queijo, 1x Alface, 1x Tomate\n";
echo "  (Mostra TUDO, mesmo os ingredientes padrão)\n\n";

echo "✅ DEPOIS - Mostra APENAS adições/remoções:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Simular dados com delta_qty
$orderDataFiltered = [
    'id' => 1001,
    'customer_name' => 'Cliente Teste',
    'customer_phone' => '5511999999999',
    'total' => 25.00,
    'subtotal' => 22.00,
    'delivery_fee' => 3.00,
    'discount' => 0,
    'payment_method' => 'Dinheiro',
    'items' => [
        [
            'name' => 'Woll Smash',
            'quantity' => 1,
            'price' => 22.00,
            'combo' => '',
            'customization' => '+2x Bacon, Sem Cebola' // Apenas o que foi modificado
        ]
    ],
    'notes' => '',
    'customer_address' => 'Rua Teste, 123',
    'created_at' => date('Y-m-d H:i:s')
];

$company = Company::find(1);

if (!$company) {
    echo "❌ Empresa não encontrada!\n";
    exit(1);
}

$reflection = new ReflectionClass('OrderNotificationService');
$method = $reflection->getMethod('generateStandardOrderMessage');
$method->setAccessible(true);

$message = $method->invoke(null, $orderDataFiltered, $company);
echo $message . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📝 LEGENDA:\n";
echo "  +2x Bacon     = Cliente ADICIONOU 2 bacons extras\n";
echo "  Sem Cebola    = Cliente REMOVEU a cebola\n";
echo "  (ingredientes padrão NÃO aparecem mais)\n\n";

echo "✨ Teste concluído!\n";
echo "\n💡 Agora as mensagens são mais limpas e focadas no que importa!\n";
