<?php
// create_test_config.php - Criar configuração de teste

require_once 'app/config/db.php';

$pdo = db();

// Criar configuração de teste
$config = [
    'enabled' => true,
    'group_id' => '120363418875892746@g.us', // ID do grupo "Grupo de teste"
    'custom_message' => '🛒 *Novo Pedido!* #{numero_pedido}\n👤 Cliente: {cliente}\n💰 Total: R$ {total}\n\n{itens}',
    'updated_at' => date('Y-m-d H:i:s')
];

$stmt = $pdo->prepare("INSERT INTO instance_configs (company_id, instance_name, config_key, config_value, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = NOW()");

$stmt->execute([
    1, // company_id da wollburger
    'fdfgdf', // instance name
    'order_notification',
    json_encode($config)
]);

echo "✅ Configuração de teste criada!\n";
echo "Company ID: 1\n";
echo "Instance: fdfgdf\n";
echo "Group ID: {$config['group_id']}\n";
echo "Message: {$config['custom_message']}\n";