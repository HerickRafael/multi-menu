<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/models/Order.php';
require_once __DIR__ . '/../app/models/Company.php';

$slug = $argv[1] ?? 'wollburger';
$company = Company::findBySlug($slug);
if (!$company) {
    echo "Company {$slug} not found\n";
    exit(1);
}

$db = db();

// pick a product id that belongs to this company
$prodSt = $db->prepare('SELECT id FROM products WHERE company_id = ? LIMIT 1');
$prodSt->execute([(int)$company['id']]);
$prodRow = $prodSt->fetch(PDO::FETCH_ASSOC);
$sampleProductId = $prodRow ? (int)$prodRow['id'] : 0;

if ($sampleProductId <= 0) {
    echo "No product found for company {$slug}, cannot create order item.\n";
    exit(3);
}
echo "Using product id={$sampleProductId}\n";

// create sample order directly
try {
    $db->beginTransaction();
    $orderId = Order::create($db, [
        'company_id' => (int)$company['id'],
        'customer_name' => 'Teste Bot',
        'customer_phone' => '+559999999999',
        'subtotal' => 20.0,
        'delivery_fee' => 5.0,
        'discount' => 0.0,
        'total' => 25.0,
        'status' => 'pending',
        'notes' => 'Pedido de teste enviado pelo script',
        'customer_address' => "Rua Falsa, 123\nBairro\nCidade",
    ]);

    Order::addItem($db, $orderId, ['product_id' => $sampleProductId, 'quantity' => 1, 'unit_price' => 20.0, 'line_total' => 20.0]);
    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "Erro criando pedido: " . $e->getMessage() . "\n";
    exit(2);
}

echo "Pedido criado id={$orderId}. Emitindo evento order.created...\n";
Order::emitOrderEvent($db, $orderId, (int)$company['id'], 'order.created');
echo "Evento emitido. Verifique logs do servidor ou mock para confirmação.\n";

exit(0);
