<?php
// app/services/OrderNotificationService.php
// Serviço para enviar notificações de pedidos para WhatsApp

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
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
                error_log("OrderNotificationService: Empresa não encontrada - company_id: {$companyId}");
                return false;
            }

            // Obter configurações de notificação para todas as instâncias da empresa
            $configs = self::getOrderNotificationConfigs($companyId);
            
            if (empty($configs)) {
                error_log("OrderNotificationService: Nenhuma configuração de notificação encontrada - company_id: {$companyId}");
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
            error_log("OrderNotificationService: Erro ao enviar notificação de pedido - company_id: {$companyId} - " . $e->getMessage());
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
            $config = JsonHelper::decode($row['config_value']);
            if ($config && DataValidator::getBool($config, 'enabled')) {
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

                error_log("OrderNotificationService: Enviando mensagem para número principal - number: {$primaryNumber}, instance: {$instanceName}");

                $result = $method->invoke($controller, $company, "/message/sendText/{$instanceName}", 'POST', $payload);

                if (!$result['error']) {
                    $success = true;
                    error_log("OrderNotificationService: Mensagem enviada com sucesso para número principal - number: {$primaryNumber}");
                } else {
                    error_log("OrderNotificationService: Erro ao enviar para número principal - number: {$primaryNumber}, error: " . $result['error']);
                }
            }
            
            // Enviar para número secundário (se configurado)
            if (!empty($secondaryNumber)) {
                $payload = [
                    'number' => $secondaryNumber,
                    'text' => $message
                ];

                error_log("OrderNotificationService: Enviando mensagem para número secundário - number: {$secondaryNumber}, instance: {$instanceName}");

                $result = $method->invoke($controller, $company, "/message/sendText/{$instanceName}", 'POST', $payload);

                if (!$result['error']) {
                    $success = true;
                    error_log("OrderNotificationService: Mensagem enviada com sucesso para número secundário - number: {$secondaryNumber}");
                } else {
                    error_log("OrderNotificationService: Erro ao enviar para número secundário - number: {$secondaryNumber}, error: " . $result['error']);
                }
            }

            return $success;

        } catch (Exception $e) {
            error_log("OrderNotificationService: Erro ao enviar mensagem - instance: " . ($instanceName ?? 'unknown') . " - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gerar mensagem formatada padrão com todos os dados do pedido para WhatsApp
     * Formato de notinha térmica sem emojis
     */
    private static function generateStandardOrderMessage($orderData, $company)
    {
        // Nome da empresa (se disponível)
        $companyName = strtoupper(DataValidator::getString($company, 'name') ?: 'RESTAURANTE');
        
        // Dados básicos do pedido
        $orderId = DataValidator::getString($orderData, 'id') ?: 'N/A';
        $clientName = DataValidator::getString($orderData, 'cliente_nome', 'customer_name') ?: 'Cliente não informado';
        $customerPhone = DataValidator::getString($orderData, 'customer_phone');
        $customerAddress = DataValidator::getString($orderData, 'customer_address');
        $total = DataValidator::getFloat($orderData, 'total');
        $subtotal = DataValidator::getFloat($orderData, 'subtotal') ?: $total;
        $deliveryFee = DataValidator::getFloat($orderData, 'delivery_fee');
        $discount = DataValidator::getFloat($orderData, 'discount');
        $items = DataValidator::getArray($orderData, 'itens', 'items');
        $paymentMethod = DataValidator::getString($orderData, 'forma_pagamento', 'payment_method', 'pagamento') ?: 'Não informado';
        $notes = DataValidator::getString($orderData, 'notes', 'observacoes');
        
        // Cabeçalho estilo notinha
        $message = "*{$companyName}*\n";
        if (DataValidator::hasValue($company, 'whatsapp')) {
            $message .= "Tel: {$company['whatsapp']}\n";
        }
        $message .= ReceiptFormatter::separator();
        $message .= "\n";
        
        // Número do pedido
        $message .= "*PEDIDO #{$orderId}*\n";
        $message .= date('d/m/Y H:i') . "\n";
        $message .= ReceiptFormatter::separator();
        $message .= "\n";
        
        // Dados do cliente
        $message .= "*CLIENTE*\n";
        $message .= "{$clientName}\n";
        if (!empty($customerPhone)) {
            $message .= "Tel: {$customerPhone}\n";
        }
        
        // Endereço (se houver)
        if (!empty($customerAddress)) {
            $message .= "\n*ENDERECO*\n";
            // Remove quebras de linha e substitui por vírgulas
            $address = str_replace("\n", ', ', $customerAddress);
            $message .= "{$address}\n";
        }
        
        // Forma de pagamento
        $message .= "\n*PAGAMENTO*\n";
        $message .= "{$paymentMethod}\n";
        
        $message .= ReceiptFormatter::separator();
        $message .= "\n";
        
        // Lista de itens - formato notinha
        $message .= "*ITENS*\n\n";
        
        if (!empty($items)) {
            foreach ($items as $item) {
                $quantity = DataValidator::getInt($item, 'quantidade', 'quantity') ?: 1;
                $name = DataValidator::getString($item, 'nome', 'name') ?: 'Item';
                $price = DataValidator::getFloat($item, 'preco', 'price');
                $itemSubtotal = $price * $quantity;
                
                // Nome do produto com valor (padrão igual ao subtotal)
                $itemLine = "{$quantity}x {$name}";
                $message .= ReceiptFormatter::formatItemLine($itemLine, MoneyFormatter::format($itemSubtotal));
                
                // Combo/Grupos de opções (se houver)
                // Combo/Grupos de opções (se houver)
                $combo = DataValidator::getString($item, 'combo');
                if (!empty($combo)) {
                    $comboItems = TextParser::splitItems($combo);
                    foreach ($comboItems as $comboItem) {
                        // Extrair preço e quantidade
                        $parsed = TextParser::extractAll($comboItem);
                        
                        // Montar linha com indentação
                        if ($parsed['price'] > 0) {
                            $comboLine = ReceiptFormatter::indent(
                                ($parsed['qty'] > 1 ? "{$parsed['qty']}x " : "") . $parsed['text']
                            );
                            $message .= ReceiptFormatter::formatItemLine($comboLine, MoneyFormatter::format($parsed['price']));
                        } else {
                            $comboLine = ReceiptFormatter::indent(
                                ($parsed['qty'] > 1 ? "{$parsed['qty']}x " : "") . $parsed['text']
                            );
                            $message .= $comboLine . "\n";
                        }
                    }
                }
                // Personalização/Ingredientes (se houver)
                $customization = DataValidator::getString($item, 'personalizacao', 'customization');
                if (!empty($customization)) {
                    // Separar itens incluindo modificadores (Sem, +, -)
                    $customItems = TextParser::splitItems($customization, true);
                    foreach ($customItems as $customItem) {
                        // Extrair preço, quantidade e texto
                        $parsed = TextParser::extractAll($customItem);
                        
                        // Formato especial: "Sem Nome"
                        if (preg_match('/^Sem\s+(.+)$/i', $parsed['text'], $semMatch)) {
                            $message .= ReceiptFormatter::indent("Sem {$semMatch[1]}") . "\n";
                            continue;
                        }
                        
                        // Montar linha com indentação
                        if ($parsed['prefix'] === '-') {
                            // Item removido
                            $customLine = ReceiptFormatter::indent("{$parsed['qty']}x {$parsed['text']}");
                            $message .= $customLine . "\n";
                        } else {
                            // Item adicionado ou normal
                            if ($parsed['price'] > 0) {
                                // Tem preço - alinhar no final
                                $customLine = ReceiptFormatter::indent(
                                    ($parsed['qty'] > 1 ? "{$parsed['qty']}x " : "") . $parsed['text']
                                );
                                $message .= ReceiptFormatter::formatItemLine($customLine, MoneyFormatter::format($parsed['price']));
                            } else {
                                // Sem preço (incluso/grátis)
                                $customLine = ReceiptFormatter::indent(
                                    ($parsed['qty'] > 1 ? "{$parsed['qty']}x " : "") . $parsed['text']
                                );
                                $message .= $customLine . "\n";
                            }
                        }
                    }
                }
                
                // Observações do item (se houver)
                $itemNotes = DataValidator::getString($item, 'notes');
                if (!empty($itemNotes)) {
                    $message .= ReceiptFormatter::indent("Obs: {$itemNotes}") . "\n";
                }
                
                $message .= "\n";
            }
        }
        
        $message .= ReceiptFormatter::separator();
        $message .= "\n";
        
        // Totais formatados com largura de 32 caracteres
        $message .= ReceiptFormatter::formatMoneyLine('Subtotal:', $subtotal);
        
        if ($deliveryFee > 0) {
            $message .= ReceiptFormatter::formatMoneyLine('Taxa Entrega:', $deliveryFee);
        }
        
        if ($discount > 0) {
            $discountValue = '- ' . MoneyFormatter::format($discount);
            $message .= ReceiptFormatter::alignRight('Desconto:', $discountValue);
        }
        
        $totalValue = MoneyFormatter::format($total);
        $message .= "\n" . ReceiptFormatter::alignRight('*TOTAL:', $totalValue . "*");
        
        // Observações gerais (se houver)
        if (!empty($notes)) {
            $message .= "\n";
            $message .= ReceiptFormatter::separator();
            $message .= "\n";
            $message .= "*OBSERVACOES*\n";
            $message .= "{$notes}\n";
        }
        
        // Rodapé
        $message .= "\n";
        $message .= ReceiptFormatter::separator();
        $message .= "\n";
        $message .= "*Novo pedido recebido!*\n";
        $message .= "Preparar o quanto antes.";
        
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
            $quantity = DataValidator::getInt($item, 'quantidade', 'quantity') ?: 1;
            $name = DataValidator::getString($item, 'nome', 'name') ?: 'Item';
            $price = DataValidator::getFloat($item, 'preco', 'price');
            $itemTotal = $price * $quantity;
            
            $formatted[] = "• {$quantity}x {$name} - " . MoneyFormatter::format($itemTotal);
        }

        return implode("\n", $formatted);
    }
}
?>