<?php
/**
 * Teste de formatação de mensagem WhatsApp
 * Demonstra a formatação correta de combos e personalizações
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/services/OrderNotificationService.php';

echo "=== TESTE DE FORMATAÇÃO (FORMATO IMAGEM) ===\n\n";

// Dados mock do pedido
$orderData = [
    'id' => 999,
    'total' => 45.90,
    'delivery_fee' => 0,
    'discount' => 0,
    'payment_method' => 'Dinheiro',
    'customer_name' => 'Cliente Teste',
    'customer_phone' => '(11) 99999-9999',
    'customer_address' => '',
    'notes' => ''
];

// Itens com formatação correta
$items = [
    [
        'quantidade' => 4,
        'nome' => 'Cebola',
        'preco' => 0.90,
        'combo' => '',
        'personalizacao' => '',
        'notes' => ''
    ],
    [
        'quantidade' => 2,
        'nome' => 'Queijo Cheddar',
        'preco' => 1.00,
        'combo' => '',
        'personalizacao' => '',
        'notes' => ''
    ]
];

$company = [
    'name' => 'RESTAURANTE TESTE',
    'whatsapp' => '(11) 99999-9999',
    'address' => 'Rua Teste, 123'
];

// Gerar mensagem
$message = OrderNotificationService::generateStandardOrderMessage($orderData, $company, $items);

echo $message;

echo "\n\n=== COMPARAÇÃO COM FORMATO DESEJADO ===\n\n";
echo "FORMATO ESPERADO (da imagem):\n";
echo "4x Cebola                + R\$ 0,90\n";
echo "2x Queijo Cheddar        + R\$ 1,00\n\n";

echo "Observe que os valores estão alinhados à direita!\n";
echo "Largura total: 32 caracteres\n";

echo "\n=== FIM DO TESTE ===\n";
