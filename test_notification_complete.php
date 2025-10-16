<?php
// test_notification_complete.php - Teste completo com fallback
require_once 'app/services/OrderNotificationService.php';

echo "=== TESTE COMPLETO DO SISTEMA DE NOTIFICAÇÕES ===\n";

// Simular dados de um pedido real
$orderData = [
    'id' => '12345',
    'cliente_nome' => 'João Silva dos Santos',
    'total' => 67.80,
    'itens' => [
        [
            'nome' => 'Woll Burger Duplo',
            'quantidade' => 2,
            'preco' => 22.90
        ],
        [
            'nome' => 'Batata Frita Grande',
            'quantidade' => 1,
            'preco' => 12.00
        ],
        [
            'nome' => 'Refrigerante 500ml',
            'quantidade' => 2,
            'preco' => 5.00
        ]
    ]
];

echo "🛒 Simulando novo pedido...\n";
echo "📋 Pedido ID: {$orderData['id']}\n";
echo "👤 Cliente: {$orderData['cliente_nome']}\n";
echo "💰 Total: R$ " . number_format($orderData['total'], 2, ',', '.') . "\n\n";

// Enviar notificação (empresa wollburger = ID 1)
echo "📤 Enviando notificação...\n";
$result = OrderNotificationService::sendOrderNotification(1, $orderData);

if ($result) {
    echo "✅ SUCESSO! Notificação enviada com sucesso!\n";
    echo "🎉 Sistema de notificações funcionando perfeitamente!\n";
    echo "📱 A mensagem foi entregue para o destinatário configurado.\n";
} else {
    echo "❌ Falha ao enviar notificação.\n";
    echo "📋 Verifique os logs para mais detalhes.\n";
}

echo "\n=== VERIFICAÇÃO DOS LOGS ===\n";
echo "Últimas linhas do log de erros:\n";
echo "─────────────────────────────\n";

// Mostrar últimas linhas do log
$logFile = '/Applications/XAMPP/xamppfiles/logs/php_error_log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -10);
    foreach ($lastLines as $line) {
        if (strpos($line, 'Evolution') !== false || strpos($line, 'Enviando') !== false || strpos($line, 'Notificação') !== false) {
            echo trim($line) . "\n";
        }
    }
} else {
    echo "Log não encontrado.\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>