<?php
// Teste da mensagem WhatsApp formato notinha

require_once __DIR__ . '/app/config/db.php';
require_once __DIR__ . '/app/models/Company.php';
require_once __DIR__ . '/app/models/Order.php';
require_once __DIR__ . '/app/services/OrderNotificationService.php';

echo "=== TESTE DE MENSAGEM WHATSAPP (FORMATO NOTINHA) ===\n\n";

try {
    // Conecta ao banco
    $db = db();
    
    // Busca uma empresa
    $company = Company::findBySlug('wollburger');
    if (!$company) {
        echo "✗ Empresa não encontrada\n";
        exit(1);
    }
    echo "✓ Empresa encontrada: " . $company['name'] . "\n";
    
    // Busca o último pedido
    $stmt = $db->prepare("SELECT * FROM orders WHERE company_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([(int)$company['id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "✗ Nenhum pedido encontrado\n";
        exit(1);
    }
    echo "✓ Pedido encontrado: #" . $order['id'] . "\n";
    
    // Busca os itens do pedido com detalhes
    $orderWithItems = Order::findWithItems($db, (int)$order['id'], (int)$company['id']);
    
    if (!$orderWithItems) {
        echo "✗ Não foi possível carregar pedido com itens\n";
        exit(1);
    }
    echo "✓ Itens do pedido carregados: " . count($orderWithItems['items']) . " itens\n\n";
    
    // Preparar dados do pedido com os itens detalhados
    $orderData = [
        'id' => $orderWithItems['id'],
        'customer_name' => $orderWithItems['customer_name'],
        'customer_phone' => $orderWithItems['customer_phone'],
        'customer_address' => $orderWithItems['customer_address'],
        'payment_method' => $orderWithItems['payment_method'],
        'total' => $orderWithItems['total'],
        'subtotal' => $orderWithItems['subtotal'],
        'delivery_fee' => $orderWithItems['delivery_fee'],
        'discount' => $orderWithItems['discount'] ?? 0,
        'notes' => $orderWithItems['notes'],
        'items' => []
    ];
    
    // Processar itens com combo e personalização
    foreach ($orderWithItems['items'] as $item) {
        $itemData = [
            'quantity' => $item['quantity'],
            'name' => $item['product_name'],
            'price' => $item['price'],
            'notes' => $item['notes'] ?? ''
        ];
        
        // Processar combo
        if (!empty($item['combo_data'])) {
            $comboData = is_string($item['combo_data']) 
                ? json_decode($item['combo_data'], true) 
                : $item['combo_data'];
            
            if (!empty($comboData) && is_array($comboData)) {
                $comboTexts = [];
                foreach ($comboData as $group) {
                    if (!empty($group['items'])) {
                        foreach ($group['items'] as $option) {
                            $optionName = $option['name'] ?? '';
                            $optionQty = (int)($option['quantity'] ?? 1);
                            $optionPrice = (float)($option['price'] ?? 0);
                            
                            $text = ($optionQty > 1 ? $optionQty . 'x ' : '') . $optionName;
                            if ($optionPrice > 0) {
                                $text .= ' (+R$ ' . number_format($optionPrice, 2, ',', '.') . ')';
                            }
                            $comboTexts[] = $text;
                        }
                    }
                }
                if (!empty($comboTexts)) {
                    $itemData['combo'] = implode(', ', $comboTexts);
                }
            }
        }
        
        // Processar personalização
        if (!empty($item['customization_data'])) {
            $customData = is_string($item['customization_data']) 
                ? json_decode($item['customization_data'], true) 
                : $item['customization_data'];
            
            if (!empty($customData) && is_array($customData)) {
                $customTexts = [];
                foreach ($customData as $custom) {
                    $customName = $custom['name'] ?? '';
                    $customAction = $custom['action'] ?? 'add';
                    $customQty = (int)($custom['quantity'] ?? 1);
                    $customPrice = (float)($custom['price'] ?? 0);
                    
                    $prefix = $customAction === 'remove' ? '- ' : '+ ';
                    $text = $prefix . ($customQty > 1 ? $customQty . 'x ' : '') . $customName;
                    
                    if ($customPrice > 0) {
                        $text .= ' (+R$ ' . number_format($customPrice, 2, ',', '.') . ')';
                    } elseif ($customPrice == 0 && $customAction !== 'remove') {
                        $text .= ' (Gratis)';
                    }
                    $customTexts[] = $text;
                }
                if (!empty($customTexts)) {
                    $itemData['personalizacao'] = implode(', ', $customTexts);
                }
            }
        }
        
        $orderData['items'][] = $itemData;
    }
    
    // Usar reflexão para acessar o método privado
    $reflection = new ReflectionClass('OrderNotificationService');
    $method = $reflection->getMethod('generateStandardOrderMessage');
    $method->setAccessible(true);
    
    // Gerar a mensagem
    $message = $method->invoke(null, $orderData, $company);
    
    echo "--- PRÉVIA DA MENSAGEM WHATSAPP ---\n\n";
    echo $message . "\n";
    echo "\n--- FIM DA MENSAGEM ---\n\n";
    
    // Estatísticas
    $lines = substr_count($message, "\n") + 1;
    $chars = strlen($message);
    
    echo "✓ Mensagem gerada com sucesso!\n";
    echo "  Linhas: $lines\n";
    echo "  Caracteres: $chars\n";
    echo "  Formato: Notinha térmica (sem emojis)\n";
    
} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
