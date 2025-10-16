<?php
// app/services/OrderNotificationService.php
// Serviço para enviar notificações de pedidos para WhatsApp

declare(strict_types=1);

require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../controllers/AdminEvolutionInstanceController.php';

class OrderNotificationService
{
    /**
     * Enviar notificação de novo pedido para os números configurados
     */
    public static function sendOrderNotification($companyId, $orderData)
    {
        try {
            // Obter empresa
            $company = Company::find($companyId);
            if (!$company) {
                error_log("Empresa não encontrada para ID: $companyId");
                return false;
            }

            // Obter configurações de notificação para todas as instâncias da empresa
            $configs = self::getOrderNotificationConfigs($companyId);
            
            if (empty($configs)) {
                error_log("Nenhuma configuração de notificação encontrada para empresa ID: $companyId");
                return false;
            }

            $success = false;
            
            foreach ($configs as $config) {
                if ($config['enabled']) {
                    $sent = self::sendToNumbers($company, $config, $orderData);
                    if ($sent) {
                        $success = true;
                    }
                }
            }

            return $success;

        } catch (Exception $e) {
            error_log("Erro ao enviar notificação de pedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obter configurações de notificação ativas da empresa
     */
    private static function getOrderNotificationConfigs($companyId)
    {
        $pdo = db();
        
        $sql = "SELECT instance_name, config_value 
                FROM instance_configs 
                WHERE company_id = ? AND config_key = 'order_notification'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId]);
        
        $configs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config = json_decode($row['config_value'], true);
            if ($config && $config['enabled']) {
                $config['instance_name'] = $row['instance_name'];
                $configs[] = $config;
            }
        }
        
        return $configs;
    }

    /**
     * Enviar mensagem para os números configurados
     */
    private static function sendToNumbers($company, $config, $orderData)
    {
        try {
            $instanceName = $config['instance_name'];
            $primaryNumber = $config['primary_number'] ?? '';
            $secondaryNumber = $config['secondary_number'] ?? '';

            // Gerar mensagem formatada padrão
            $message = self::generateStandardOrderMessage($orderData, $company);

            // Criar instância do controller para usar o método evolutionApiRequest
            $controller = new AdminEvolutionInstanceController();
            $reflection = new ReflectionClass($controller);
            $method = $reflection->getMethod('evolutionApiRequest');
            $method->setAccessible(true);

            $success = false;
            
            // Enviar para número principal
            if (!empty($primaryNumber)) {
                $payload = [
                    'number' => $primaryNumber,
                    'text' => $message
                ];

                error_log("Enviando mensagem para número principal {$primaryNumber} via instância {$instanceName}");
                error_log("Payload: " . json_encode($payload));

                $result = $method->invoke($controller, $company, "/message/sendText/{$instanceName}", 'POST', $payload);

                if (!$result['error']) {
                    $success = true;
                    error_log("Mensagem enviada com sucesso para número principal: {$primaryNumber}");
                } else {
                    error_log("Erro ao enviar para número principal {$primaryNumber}: " . $result['error']);
                }
            }
            
            // Enviar para número secundário (se configurado)
            if (!empty($secondaryNumber)) {
                $payload = [
                    'number' => $secondaryNumber,
                    'text' => $message
                ];

                error_log("Enviando mensagem para número secundário {$secondaryNumber} via instância {$instanceName}");

                $result = $method->invoke($controller, $company, "/message/sendText/{$instanceName}", 'POST', $payload);

                if (!$result['error']) {
                    $success = true;
                    error_log("Mensagem enviada com sucesso para número secundário: {$secondaryNumber}");
                } else {
                    error_log("Erro ao enviar para número secundário {$secondaryNumber}: " . $result['error']);
                }
            }

            return $success;

        } catch (Exception $e) {
            error_log("Erro ao enviar mensagem: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gerar mensagem formatada padrão com todos os dados do pedido para WhatsApp Mobile
     */
    private static function generateStandardOrderMessage($orderData, $company)
    {
        // Nome da empresa (se disponível)
        $companyName = strtoupper($company['name'] ?? 'RESTAURANTE');
        
        // Dados básicos do pedido
        $orderId = $orderData['id'] ?? 'N/A';
        $clientName = $orderData['cliente_nome'] ?? $orderData['customer_name'] ?? 'Cliente não informado';
        $total = $orderData['total'] ?? 0;
        $items = $orderData['itens'] ?? $orderData['items'] ?? [];
        $paymentMethod = $orderData['forma_pagamento'] ?? $orderData['payment_method'] ?? $orderData['pagamento'] ?? 'Não informado';
        
        // Cabeçalho otimizado para mobile
        $message = "🍔 *{$companyName}*\n";
        $message .= "🔔 *NOVO PEDIDO!*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        // Informações principais - formato compacto
        $message .= "📋 *Pedido:* #{$orderId}\n";
        $message .= "👤 *Cliente:* {$clientName}\n";
        $message .= "💰 *Pagamento:* {$paymentMethod}\n";
        $message .= "💵 *Total:* R$ " . number_format($total, 2, ',', '.') . "\n\n";
        
        // Lista de itens - formato mobile friendly
        if (!empty($items)) {
            $message .= "🛒 *ITENS:*\n";
            
            foreach ($items as $item) {
                $quantity = $item['quantidade'] ?? $item['quantity'] ?? 1;
                $name = $item['nome'] ?? $item['name'] ?? 'Item';
                $price = $item['preco'] ?? $item['price'] ?? 0;
                $subtotal = $price * $quantity;
                
                // Formato compacto para mobile
                $message .= "• {$quantity}x {$name}\n";
                $message .= "  💵 R$ " . number_format($subtotal, 2, ',', '.') . "\n";
            }
            $message .= "\n";
        }
        
        // Informações finais - compactas
        $message .= "⏰ " . date('d/m/Y H:i') . "\n";
        $message .= "📱 Sistema Automático\n\n";
        
        // Call to action motivacional
        $message .= "✨ *Preparar pedido!* 🚀\n";
        $message .= "💪 Vamos lá, equipe!";
        
        return $message;
    }

    /**
     * Gerar mensagem do pedido (função mantida para compatibilidade)
     * @deprecated Use generateStandardOrderMessage instead
     */
    private static function generateOrderMessage($orderData, $customMessage = '')
    {
        // Redirecionar para a função padrão
        $company = ['name' => 'Restaurante']; // Fallback
        return self::generateStandardOrderMessage($orderData, $company);
    }

    /**
     * Formatar itens do pedido para a mensagem
     */
    private static function formatOrderItems($items)
    {
        if (empty($items)) {
            return 'Nenhum item';
        }

        $formatted = [];
        foreach ($items as $item) {
            // Suportar tanto 'quantidade' quanto 'quantity'
            $quantity = $item['quantidade'] ?? $item['quantity'] ?? 1;
            $name = $item['nome'] ?? $item['name'] ?? 'Item';
            $price = $item['preco'] ?? $item['price'] ?? 0;
            
            $formatted[] = "• {$quantity}x {$name} - R$ " . 
                          number_format($price * $quantity, 2, ',', '.');
        }

        return implode("\n", $formatted);
    }
}
?>