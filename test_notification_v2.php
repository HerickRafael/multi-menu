<?php
// test_notification_v2.php - Teste completo com API v2
// Teste específico usando o endpoint correto da Evolution API v2

require_once 'app/models/Company.php';
require_once 'app/controllers/AdminEvolutionInstanceController.php';

echo "=== TESTE DE NOTIFICAÇÃO COM API V2 ===\n";

try {
    // Dados da empresa wollburger (ID 1)
    $company = Company::find(1);
    
    if (!$company) {
        echo "❌ Empresa não encontrada\n";
        exit(1);
    }
    
    echo "✅ Empresa encontrada: {$company['name']}\n";
    echo "🔗 Evolution Server: {$company['evolution_server_url']}\n";
    echo "🔑 API Key: " . substr($company['evolution_api_key'], 0, 10) . "...\n\n";
    
    // Dados de teste do pedido
    $orderData = [
        'id' => '12345',
        'cliente_nome' => 'João da Silva (TESTE V2)',
        'total' => 35.50,
        'itens' => [
            [
                'nome' => 'Hambúrguer Clássico',
                'quantidade' => 2,
                'preco' => 15.00
            ],
            [
                'nome' => 'Batata Frita',
                'quantidade' => 1,
                'preco' => 5.50
            ]
        ]
    ];
    
    // Configuração do grupo
    $config = [
        'instance_name' => 'teste_notificacao',
        'group_id' => '120363350310820935@g.us',
        'enabled' => true,
        'custom_message' => "🧪 *TESTE API V2 - Novo Pedido!* #{numero_pedido}\n\n👤 *Cliente:* {cliente}\n💰 *Total:* R$ {total}\n\n📋 *Itens:*\n{itens}\n\n⏰ Enviado via Evolution API v2"
    ];
    
    echo "📱 Instância: {$config['instance_name']}\n";
    echo "👥 Grupo: {$config['group_id']}\n\n";
    
    // Gerar mensagem
    $message = generateOrderMessage($orderData, $config['custom_message']);
    echo "📝 Mensagem gerada:\n";
    echo "─────────────────────\n";
    echo $message . "\n";
    echo "─────────────────────\n\n";
    
    // Payload para Evolution API v2
    $payload = [
        'number' => $config['group_id'],
        'text' => $message
    ];
    
    echo "📤 Payload JSON:\n";
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // Enviar via Evolution API
    $controller = new AdminEvolutionInstanceController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('evolutionApiRequest');
    $method->setAccessible(true);
    
    echo "🚀 Enviando mensagem...\n";
    
    $result = $method->invoke(
        $controller, 
        $company, 
        "/message/sendText/{$config['instance_name']}", 
        'POST', 
        $payload
    );
    
    echo "📥 Resposta da API:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    if ($result['error']) {
        echo "❌ ERRO: {$result['error']}\n";
    } else {
        echo "✅ Requisição enviada com sucesso!\n";
        echo "📊 Status: " . ($result['data']['status'] ?? 'Desconhecido') . "\n";
        
        if (isset($result['data']['key']['id'])) {
            echo "🆔 Message ID: {$result['data']['key']['id']}\n";
        }
        
        if ($result['code'] === 201) {
            echo "🎉 MENSAGEM ENVIADA COM SUCESSO!\n";
        }
    }

} catch (Exception $e) {
    echo "💥 Erro na execução: " . $e->getMessage() . "\n";
}

/**
 * Gerar mensagem do pedido
 */
function generateOrderMessage($orderData, $customMessage = '')
{
    if (empty($customMessage)) {
        $customMessage = "🛒 *Novo Pedido Recebido!*\n\n" .
                       "📋 *Pedido:* #{numero_pedido}\n" .
                       "👤 *Cliente:* {cliente}\n" .
                       "💰 *Total:* R$ {total}\n\n" .
                       "*Itens:*\n{itens}\n\n" .
                       "⏰ Recebido em: " . date('d/m/Y H:i');
    }

    $message = str_replace([
        '{numero_pedido}',
        '{cliente}',
        '{total}',
        '{itens}'
    ], [
        $orderData['id'] ?? 'N/A',
        $orderData['cliente_nome'] ?? $orderData['customer_name'] ?? 'Cliente',
        number_format($orderData['total'] ?? 0, 2, ',', '.'),
        formatOrderItems($orderData['itens'] ?? $orderData['items'] ?? [])
    ], $customMessage);

    return $message;
}

/**
 * Formatar itens do pedido
 */
function formatOrderItems($items)
{
    if (empty($items)) {
        return 'Nenhum item';
    }

    $formatted = [];
    foreach ($items as $item) {
        $quantity = $item['quantidade'] ?? $item['quantity'] ?? 1;
        $name = $item['nome'] ?? $item['name'] ?? 'Item';
        $price = $item['preco'] ?? $item['price'] ?? 0;
        
        $formatted[] = "• {$quantity}x {$name} - R$ " . 
                      number_format($price * $quantity, 2, ',', '.');
    }

    return implode("\n", $formatted);
}

echo "\n=== FIM DO TESTE ===\n";
?>