<?php
/**
 * Teste de Filtro de Ingredientes Inclusos
 * Verifica se ingredientes "Incluso" são filtrados corretamente
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/services/ThermalReceipt.php';

echo "=== TESTE DE FILTRO DE INGREDIENTES ===\n\n";

// Dados de teste simulando um pedido com ingredientes inclusos
$testOrder = [
    'id' => 999,
    'total' => 35.00,
    'delivery_fee' => 5.00,
    'discount' => 0.00,
    'payment_method' => 'Dinheiro',
    'customer_name' => 'João Teste',
    'customer_phone' => '(11) 99999-9999',
    'customer_address' => 'Rua Teste, 123',
    'notes' => ''
];

$testItems = [
    [
        'id' => 1,
        'quantity' => 1,
        'name' => 'X-Burger Gourmet',
        'price' => 30.00,
        'combo_data' => json_encode([
            [
                'name' => 'Pães',
                'items' => [
                    ['name' => 'Pão Brioche', 'quantity' => 1, 'price' => 0]
                ]
            ],
            [
                'name' => 'Carnes',
                'items' => [
                    ['name' => 'Hambúrguer 180g', 'quantity' => 1, 'price' => 0]
                ]
            ]
        ]),
        'customization_data' => json_encode([
            // Estes NÃO devem aparecer (inclusos)
            ['name' => 'Alface', 'action' => 'add', 'quantity' => 1, 'price' => 0],
            ['name' => 'Tomate', 'action' => 'add', 'quantity' => 1, 'price' => 0],
            ['name' => 'Maionese', 'action' => 'add', 'quantity' => 1, 'price' => 0],
            
            // Estes DEVEM aparecer (extras com preço)
            ['name' => 'Bacon', 'action' => 'add', 'quantity' => 1, 'price' => 3.00],
            ['name' => 'Cheddar', 'action' => 'add', 'quantity' => 1, 'price' => 2.50],
            
            // Este DEVE aparecer (remoção)
            ['name' => 'Cebola', 'action' => 'remove', 'quantity' => 1, 'price' => 0],
        ]),
        'notes' => ''
    ]
];

$testCompany = [
    'name' => 'Restaurante Teste',
    'phone' => '(11) 3333-3333',
    'address' => 'Av. Principal, 456',
    'logo_path' => ''
];

echo "Testando geração de PDF com filtro de ingredientes...\n\n";

try {
    // Gerar PDF
    $pdfContent = ThermalReceipt::generatePdf($testOrder, $testItems, $testCompany);
    
    $pdfSize = strlen($pdfContent);
    echo "✓ PDF gerado com sucesso!\n";
    echo "  Tamanho: " . number_format($pdfSize / 1024, 2) . " KB\n\n";
    
    // Salvar temporariamente para análise
    $tempFile = __DIR__ . '/test_filtered_receipt.pdf';
    file_put_contents($tempFile, $pdfContent);
    echo "✓ PDF salvo em: {$tempFile}\n";
    echo "  Abra o arquivo para verificar se:\n";
    echo "  - Alface, Tomate, Maionese NÃO aparecem\n";
    echo "  - Bacon (+R$ 3,00) e Cheddar (+R$ 2,50) APARECEM\n";
    echo "  - Cebola (removida) APARECE\n\n";
    
    // Análise dos dados de customização
    echo "=== ANÁLISE DOS DADOS DE CUSTOMIZAÇÃO ===\n\n";
    
    $customData = json_decode($testItems[0]['customization_data'], true);
    
    echo "Ingredientes no pedido:\n";
    foreach ($customData as $idx => $item) {
        // Lógica correta: incluso = price 0 E action 'add'
        $isIncluso = ($item['price'] == 0 && $item['action'] === 'add');
        $shouldShow = !$isIncluso;
        
        $status = $shouldShow ? '✓ MOSTRAR' : '✗ OCULTAR';
        $reason = '';
        
        if ($item['action'] === 'remove') {
            $reason = '(remoção - sempre mostra)';
        } elseif ($item['price'] > 0) {
            $reason = '(tem preço - extra)';
        } elseif ($item['price'] == 0 && $item['action'] === 'add') {
            $reason = '(incluso - OCULTAR)';
        }
        
        echo sprintf(
            "%d. %s %-20s - R$ %s %s\n",
            $idx + 1,
            $status,
            $item['name'],
            number_format($item['price'], 2, ',', '.'),
            $reason
        );
    }
    
    echo "\n✓ Teste concluído!\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    echo "  Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
