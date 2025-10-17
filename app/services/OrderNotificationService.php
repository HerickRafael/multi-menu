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
     * Gerar mensagem formatada padrão com todos os dados do pedido para WhatsApp
     * Formato de notinha térmica sem emojis
     */
    private static function generateStandardOrderMessage($orderData, $company)
    {
        // Nome da empresa (se disponível)
        $companyName = strtoupper($company['name'] ?? 'RESTAURANTE');
        
        // Dados básicos do pedido
        $orderId = $orderData['id'] ?? 'N/A';
        $clientName = $orderData['cliente_nome'] ?? $orderData['customer_name'] ?? 'Cliente não informado';
        $customerPhone = $orderData['customer_phone'] ?? '';
        $customerAddress = $orderData['customer_address'] ?? '';
        $total = (float)($orderData['total'] ?? 0);
        $subtotal = (float)($orderData['subtotal'] ?? $total);
        $deliveryFee = (float)($orderData['delivery_fee'] ?? 0);
        $discount = (float)($orderData['discount'] ?? 0);
        $items = $orderData['itens'] ?? $orderData['items'] ?? [];
        $paymentMethod = $orderData['forma_pagamento'] ?? $orderData['payment_method'] ?? $orderData['pagamento'] ?? 'Não informado';
        $notes = $orderData['notes'] ?? $orderData['observacoes'] ?? '';
        
        // Cabeçalho estilo notinha
        $message = "*{$companyName}*\n";
        if (!empty($company['whatsapp'])) {
            $message .= "Tel: {$company['whatsapp']}\n";
        }
        $message .= "- - - - - - - - - - - - - - - -\n\n";
        
        // Número do pedido
        $message .= "*PEDIDO #{$orderId}*\n";
        $message .= date('d/m/Y H:i') . "\n";
        $message .= "- - - - - - - - - - - - - - - -\n\n";
        
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
        
        $message .= "- - - - - - - - - - - - - - - -\n\n";
        
        // Lista de itens - formato notinha
        $message .= "*ITENS*\n\n";
        
        if (!empty($items)) {
            foreach ($items as $item) {
                $quantity = (int)($item['quantidade'] ?? $item['quantity'] ?? 1);
                $name = $item['nome'] ?? $item['name'] ?? 'Item';
                $price = (float)($item['preco'] ?? $item['price'] ?? 0);
                $itemSubtotal = $price * $quantity;
                
                // Nome do produto com valor (padrão igual ao subtotal)
                $itemValue = 'R$ ' . number_format($itemSubtotal, 2, ',', '.');
                $itemLine = "{$quantity}x {$name}";
                $message .= str_pad($itemLine, 32 - strlen($itemValue), ' ') . $itemValue . "\n";
                
                // Combo/Grupos de opções (se houver)
                $combo = $item['combo'] ?? '';
                if (!empty($combo)) {
                    // Separar por vírgula, mas não dentro de parênteses (preços)
                    $comboItems = preg_split('/,\s+(?=\d|[A-Z])/i', $combo);
                    foreach ($comboItems as $comboItem) {
                        $comboItem = trim($comboItem);
                        
                        // Extrair preço do final: "(+ R$ X,XX)"
                        $comboPrice = 0;
                        $comboText = $comboItem;
                        
                        if (preg_match('/\(\+\s*R\$\s*([\d,\.]+)\)\s*$/', $comboItem, $priceMatch)) {
                            $comboPrice = floatval(str_replace(',', '.', $priceMatch[1]));
                            $comboText = trim(preg_replace('/\s*\(\+\s*R\$\s*[\d,\.]+\)\s*$/', '', $comboItem));
                        }
                        
                        // Extrair quantidade: "2x Nome"
                        $comboQty = '';
                        $comboName = $comboText;
                        
                        if (preg_match('/^(\d+)x\s+(.+)$/', $comboText, $qtyMatch)) {
                            $comboQty = $qtyMatch[1];
                            $comboName = $qtyMatch[2];
                        }
                        
                        // Montar linha
                        if ($comboPrice > 0) {
                            $comboValue = 'R$ ' . number_format($comboPrice, 2, ',', '.');
                            $comboLine = "  " . ($comboQty ? "{$comboQty}x " : "") . $comboName;
                            
                            // Garantir que a linha cabe (máximo 32 chars)
                            $availableSpace = 32 - strlen($comboValue);
                            if (strlen($comboLine) >= $availableSpace) {
                                // Nome muito longo - truncar deixando espaço para o valor
                                $maxNameLength = $availableSpace - 1;
                                if (strlen($comboLine) > $maxNameLength) {
                                    $comboLine = substr($comboLine, 0, $maxNameLength);
                                }
                            }
                            
                            $message .= str_pad($comboLine, 32 - strlen($comboValue), ' ') . $comboValue . "\n";
                        } else {
                            $comboLine = "  " . ($comboQty ? "{$comboQty}x " : "") . $comboName;
                            $message .= $comboLine . "\n";
                        }
                    }
                }
                
                // Personalização/Ingredientes (se houver)
                $customization = $item['personalizacao'] ?? $item['customization'] ?? '';
                if (!empty($customization)) {
                    // Separar por vírgula, mas não dentro de parênteses
                    // Regex: vírgula seguida de espaço e dígito ou letra (próximo item)
                    $customItems = preg_split('/,\s+(?=\d|[A-Z]|Sem|[\+\-])/i', $customization);
                    foreach ($customItems as $customItem) {
                        $customItem = trim($customItem);
                        
                        // Extrair preço do final: "(+ R$ X,XX)" ou "(+ R$ X.XX)"
                        $itemPrice = 0;
                        $itemName = $customItem;
                        
                        // Tentar encontrar preço no formato (+ R$ X,XX) no final da string
                        if (preg_match('/\(\+\s*R\$\s*([\d,\.]+)\)\s*$/', $customItem, $priceMatch)) {
                            $itemPrice = floatval(str_replace(',', '.', $priceMatch[1]));
                            // Remover o preço da string para pegar só o nome
                            $itemName = trim(preg_replace('/\s*\(\+\s*R\$\s*[\d,\.]+\)\s*$/', '', $customItem));
                        }
                        
                        // Verificar se tem quantidade no início
                        $qty = '';
                        $prefix = '';
                        
                        // Formato: "+1x Nome" ou "-1x Nome" ou "1x Nome"
                        if (preg_match('/^([+\-])?(\d+)x\s+(.+)$/', $itemName, $qtyMatch)) {
                            $prefix = $qtyMatch[1] ?? '';
                            $qty = $qtyMatch[2];
                            $itemName = $qtyMatch[3];
                        }
                        
                        // Formato: "Sem Nome"
                        if (preg_match('/^Sem\s+(.+)$/i', $itemName, $semMatch)) {
                            $message .= "  Sem {$semMatch[1]}\n";
                            continue;
                        }
                        
                        // Montar linha com indentação
                        if ($prefix === '-') {
                            // Item removido
                            $customLine = "  {$qty}x {$itemName}";
                            $message .= $customLine . "\n";
                        } else {
                            // Item adicionado ou normal
                            if ($itemPrice > 0) {
                                // Tem preço - alinhar no final
                                $customValue = 'R$ ' . number_format($itemPrice, 2, ',', '.');
                                $customLine = "  " . ($qty ? "{$qty}x " : "") . $itemName;
                                
                                // Garantir que a linha cabe (máximo 32 chars)
                                $availableSpace = 32 - strlen($customValue);
                                if (strlen($customLine) >= $availableSpace) {
                                    // Nome muito longo - garantir pelo menos 1 espaço
                                    $maxNameLength = $availableSpace - 1;
                                    if (strlen($customLine) > $maxNameLength) {
                                        $customLine = substr($customLine, 0, $maxNameLength);
                                    }
                                }
                                
                                $message .= str_pad($customLine, 32 - strlen($customValue), ' ') . $customValue . "\n";
                            } else {
                                // Sem preço (incluso/grátis)
                                $customLine = "  " . ($qty ? "{$qty}x " : "") . $itemName;
                                $message .= $customLine . "\n";
                            }
                        }
                    }
                }
                
                // Observações do item (se houver)
                if (!empty($item['notes'])) {
                    $message .= "  Obs: {$item['notes']}\n";
                }
                
                $message .= "\n";
            }
        }
        
        $message .= "- - - - - - - - - - - - - - - -\n\n";
        
        // Totais formatados com largura de 32 caracteres
        $subtotalStr = 'R$ ' . number_format($subtotal, 2, ',', '.');
        $message .= str_pad('Subtotal:', 32 - strlen($subtotalStr), ' ') . $subtotalStr . "\n";
        
        if ($deliveryFee > 0) {
            $deliveryStr = 'R$ ' . number_format($deliveryFee, 2, ',', '.');
            $message .= str_pad('Taxa Entrega:', 32 - strlen($deliveryStr), ' ') . $deliveryStr . "\n";
        }
        
        if ($discount > 0) {
            $discountStr = '- R$ ' . number_format($discount, 2, ',', '.');
            $message .= str_pad('Desconto:', 32 - strlen($discountStr), ' ') . $discountStr . "\n";
        }
        
        $totalStr = 'R$ ' . number_format($total, 2, ',', '.');
        $message .= "\n" . str_pad('*TOTAL:', 32 - strlen($totalStr) - 1, ' ') . $totalStr . "*\n";
        
        // Observações gerais (se houver)
        if (!empty($notes)) {
            $message .= "\n- - - - - - - - - - - - - - - -\n\n";
            $message .= "*OBSERVACOES*\n";
            $message .= "{$notes}\n";
        }
        
        // Rodapé
        $message .= "\n- - - - - - - - - - - - - - - -\n\n";
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