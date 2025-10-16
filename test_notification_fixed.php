<?php
// Arquivo de teste para notificação de pedidos
require_once __DIR__ . '/app/config/db.php';
require_once __DIR__ . '/app/services/OrderNotificationService.php';

// Dados de teste do pedido
$testOrderData = [
    'id' => 999,
    'cliente_nome' => 'Cliente Teste',
    'cliente_telefone' => '5511999999999',
    'total' => 35.90,
    'itens' => [
        [
            'quantidade' => 1,
            'nome' => 'Classic Burger',
            'preco' => 25.90
        ],
        [
            'quantidade' => 1,
            'nome' => 'Batata Frita',
            'preco' => 10.00
        ]
    ]
];

echo "=== Teste de Notificação de Pedido ===\n\n";

// Buscar company_id da empresa 'wollburger'
$pdo = db();
$stmt = $pdo->prepare("SELECT id FROM companies WHERE slug = ?");
$stmt->execute(['wollburger']);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    die("Empresa 'wollburger' não encontrada\n");
}

$companyId = $company['id'];
echo "Company ID: $companyId\n";

// Verificar configurações existentes
$stmt = $pdo->prepare("
    SELECT ic.*, c.slug as company_slug 
    FROM instance_configs ic 
    JOIN companies c ON c.id = ic.company_id 
    WHERE ic.company_id = ? AND ic.config_key = 'order_notification'
");
$stmt->execute([$companyId]);
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Configurações encontradas: " . count($configs) . "\n";
foreach ($configs as $config) {
    $configValue = json_decode($config['config_value'], true);
    echo "- Instância: {$config['instance_name']}\n";
    echo "  Ativada: " . ($configValue['enabled'] ? 'SIM' : 'NÃO') . "\n";
    echo "  Grupo: {$configValue['group_id']}\n";
    echo "  Mensagem customizada: " . ($configValue['custom_message'] ? 'SIM' : 'NÃO') . "\n\n";
}

// Testar envio da notificação
echo "Enviando notificação de teste...\n";
$result = OrderNotificationService::sendOrderNotification($companyId, $testOrderData);

echo "Resultado: " . ($result ? 'SUCESSO' : 'FALHA') . "\n";
echo "Verifique os logs para detalhes.\n";