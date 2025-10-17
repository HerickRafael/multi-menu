<?php
declare(strict_types=1);
// app/services/ThermalReceipt.php
// Otimizado para Mini Impressora Bluetooth Térmica 58mm

require_once __DIR__ . '/../../vendor/autoload.php';

class ThermalReceipt
{
    // Configurações para impressora térmica 58mm
    private const WIDTH = 58;        // Largura em mm
    private const MARGIN = 2;        // Margem lateral
    
    /**
     * Desenha uma linha tracejada
     */
    private static function drawDashedLine($pdf, $x1, $y, $x2, $y2): void
    {
        $pdf->SetLineWidth(0.1);
        $pdf->SetDrawColor(100, 100, 100);
        
        $dashLength = 2;
        $gapLength = 1;
        $currentX = $x1;
        
        while ($currentX < $x2) {
            $endX = min($currentX + $dashLength, $x2);
            $pdf->Line($currentX, $y, $endX, $y2);
            $currentX += $dashLength + $gapLength;
        }
        
        $pdf->SetDrawColor(0, 0, 0);
    }
    
    /**
     * Gera PDF otimizado para impressora térmica 58mm
     */
    public static function generatePdf(array $company, array $orderRow, array $items, string $rawMessage = ''): string
    {
        $pdf = new \FPDF('P', 'mm', array(self::WIDTH, 297));
        $pdf->SetAutoPageBreak(true, 5);
        $pdf->SetMargins(self::MARGIN, self::MARGIN, self::MARGIN);
        $pdf->AddPage();
        
        // Helper: convert UTF-8 to ISO-8859-1 for FPDF
        $pdfText = static function ($s) {
            if ($s === null) return '';
            if (!is_string($s)) $s = (string)$s;
            // Remove emojis e caracteres especiais
            $s = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $s);
            $s = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $s);
            $s = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $s);
            
            if (function_exists('iconv')) {
                $c = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
                if ($c !== false) return $c;
            }
            return @utf8_decode($s);
        };

        // Logo
        $logoPath = null;
        if (!empty($company['logo'])) {
            $candidate = __DIR__ . '/../../public/' . ltrim($company['logo'], '/');
            if (file_exists($candidate)) $logoPath = $candidate;
        }

        // CABEÇALHO
        $pdf->Ln(2);
        
        if ($logoPath && is_readable($logoPath)) {
            try {
                $info = @getimagesize($logoPath);
                if ($info) {
                    $imgW = 20;
                    $centerX = (self::WIDTH - $imgW) / 2;
                    $pdf->Image($logoPath, $centerX, $pdf->GetY(), $imgW);
                    $pdf->Ln(16);
                }
            } catch (Throwable $e) {}
        }

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 5, $pdfText(strtoupper($company['name'] ?? '')), 0, 1, 'C');
        
        if (!empty($company['address'])) {
            $pdf->SetFont('Arial', '', 7);
            $pdf->MultiCell(0, 3, $pdfText($company['address']), 0, 'C');
        }
        
        if (!empty($company['whatsapp'])) {
            $pdf->SetFont('Arial', '', 7);
            $pdf->Cell(0, 3, $pdfText('Tel: ' . $company['whatsapp']), 0, 1, 'C');
        }
        
        $pdf->Ln(2);
        self::drawDashedLine($pdf, self::MARGIN, $pdf->GetY(), self::WIDTH - self::MARGIN, $pdf->GetY());
        $pdf->Ln(3);

        // DADOS DO PEDIDO
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 5, $pdfText('PEDIDO #' . ($orderRow['id'] ?? '')), 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 8);
        $dateTime = !empty($orderRow['created_at']) 
            ? date('d/m/Y H:i', strtotime($orderRow['created_at'])) 
            : date('d/m/Y H:i');
        $pdf->Cell(0, 4, $pdfText($dateTime), 0, 1, 'C');
        
        $pdf->Ln(2);
        self::drawDashedLine($pdf, self::MARGIN, $pdf->GetY(), self::WIDTH - self::MARGIN, $pdf->GetY());
        $pdf->Ln(3);

        // Cliente
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(0, 4, $pdfText('CLIENTE'), 0, 1);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 4, $pdfText($orderRow['customer_name'] ?? 'Nao informado'), 0, 1);
        
        if (!empty($orderRow['customer_phone'])) {
            $pdf->Cell(0, 4, $pdfText('Tel: ' . $orderRow['customer_phone']), 0, 1);
        }
        
        if (!empty($orderRow['customer_address'])) {
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(0, 4, $pdfText('ENDERECO'), 0, 1);
            $pdf->SetFont('Arial', '', 7);
            $address = str_replace("\n", ', ', $orderRow['customer_address']);
            $pdf->MultiCell(0, 3, $pdfText($address), 0);
        }
        
        if (!empty($orderRow['payment_method'])) {
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(0, 4, $pdfText('PAGAMENTO'), 0, 1);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(0, 4, $pdfText($orderRow['payment_method']), 0, 1);
        }
        
        $pdf->Ln(2);
        self::drawDashedLine($pdf, self::MARGIN, $pdf->GetY(), self::WIDTH - self::MARGIN, $pdf->GetY());
        $pdf->Ln(3);

        // ITENS DO PEDIDO
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 4, $pdfText('ITENS'), 0, 1);
        $pdf->Ln(1);

        foreach ($items as $idx => $it) {
            $qty = (int)($it['quantity'] ?? $it['qty'] ?? 1);
            $name = $it['product_name'] ?? $it['name'] ?? 'Produto';
            $price = (float)($it['price'] ?? 0);
            $lineTotal = (float)($it['line_total'] ?? ($price * $qty));

            // Nome do produto
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(7, 4, $pdfText($qty . 'x'), 0, 0);
            $pdf->MultiCell(0, 4, $pdfText($name), 0);
            
            // Combo
            $comboData = null;
            if (!empty($it['combo_data'])) {
                $comboData = is_string($it['combo_data']) 
                    ? json_decode($it['combo_data'], true) 
                    : $it['combo_data'];
            }
            
            if (!empty($comboData) && is_array($comboData)) {
                $pdf->SetFont('Arial', '', 7);
                foreach ($comboData as $group) {
                    if (!empty($group['name'])) {
                        $pdf->Cell(4, 3, '', 0, 0);
                        $pdf->Cell(0, 3, $pdfText('> ' . $group['name'] . ':'), 0, 1);
                    }
                    
                    if (!empty($group['items']) && is_array($group['items'])) {
                        foreach ($group['items'] as $option) {
                            $optionName = $option['name'] ?? '';
                            $optionQty = (int)($option['quantity'] ?? 1);
                            $optionPrice = (float)($option['price'] ?? 0);
                            
                            $pdf->Cell(6, 3, '', 0, 0);
                            $optionText = '  ' . ($optionQty > 1 ? $optionQty . 'x ' : '') . $optionName;
                            
                            if ($optionPrice > 0) {
                                $optionText .= ' (+R$ ' . number_format($optionPrice, 2, ',', '.') . ')';
                            }
                            
                            $pdf->MultiCell(0, 3, $pdfText($optionText), 0);
                        }
                    }
                }
                $pdf->Ln(1);
            }
            
            // Personalização (não mostrar ingredientes inclusos)
            $customData = null;
            if (!empty($it['customization_data'])) {
                $customData = is_string($it['customization_data']) 
                    ? json_decode($it['customization_data'], true) 
                    : $it['customization_data'];
            }
            
            if (!empty($customData) && is_array($customData)) {
                // Filtrar apenas items que NÃO são inclusos
                $customItemsToShow = [];
                
                foreach ($customData as $custom) {
                    $customName = $custom['name'] ?? '';
                    $customAction = $custom['action'] ?? 'add';
                    $customQty = (int)($custom['quantity'] ?? 1);
                    $customPrice = (float)($custom['price'] ?? 0);
                    
                    // Verificar se é incluso (sem preço e ação de adicionar, mas não é remoção)
                    // Inclusos: price = 0 e action = 'add'
                    // Devem aparecer: price > 0 (extras) OU action = 'remove' (removidos)
                    $isIncluso = ($customPrice == 0 && $customAction === 'add');
                    
                    // Mostrar apenas se NÃO for incluso
                    if (!$isIncluso) {
                        $customItemsToShow[] = [
                            'name' => $customName,
                            'action' => $customAction,
                            'qty' => $customQty,
                            'price' => $customPrice
                        ];
                    }
                }
                
                // Mostrar seção apenas se houver itens para exibir
                if (!empty($customItemsToShow)) {
                    $pdf->SetFont('Arial', 'I', 7);
                    $pdf->Cell(4, 3, '', 0, 0);
                    $pdf->Cell(0, 3, $pdfText('> Personalizacao:'), 0, 1);
                    $pdf->SetFont('Arial', '', 7);
                    
                    foreach ($customItemsToShow as $custom) {
                        $customName = $custom['name'];
                        $customAction = $custom['action'];
                        $customQty = $custom['qty'];
                        $customPrice = $custom['price'];
                        
                        $prefix = $customAction === 'remove' ? '- ' : '+ ';
                        $pdf->Cell(6, 3, '', 0, 0);
                        
                        $customText = '  ' . $prefix . ($customQty > 1 ? $customQty . 'x ' : '') . $customName;
                        
                        if ($customPrice > 0) {
                            $customText .= ' (+R$ ' . number_format($customPrice, 2, ',', '.') . ')';
                        } elseif ($customPrice == 0 && $customAction !== 'remove') {
                            $customText .= ' (Gratis)';
                        }
                        
                        $pdf->MultiCell(0, 3, $pdfText($customText), 0);
                    }
                    $pdf->Ln(1);
                }
            }
            
            // Observações do item
            if (!empty($it['notes'])) {
                $pdf->SetFont('Arial', 'I', 7);
                $pdf->Cell(4, 3, '', 0, 0);
                $pdf->MultiCell(0, 3, $pdfText('> Obs: ' . $it['notes']), 0);
                $pdf->Ln(1);
            }
            
            // Valor do item
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(0, 4, $pdfText('R$ ' . number_format($lineTotal, 2, ',', '.')), 0, 1, 'R');
            
            if ($idx < count($items) - 1) {
                $pdf->Ln(2);
            }
        }

        // TOTAIS
        $pdf->Ln(3);
        self::drawDashedLine($pdf, self::MARGIN, $pdf->GetY(), self::WIDTH - self::MARGIN, $pdf->GetY());
        $pdf->Ln(3);

        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 4, $pdfText('Subtotal:'), 0, 0);
        $pdf->Cell(0, 4, $pdfText('R$ ' . number_format((float)($orderRow['subtotal'] ?? 0), 2, ',', '.')), 0, 1, 'R');
        
        if (!empty($orderRow['delivery_fee']) && (float)$orderRow['delivery_fee'] > 0) {
            $pdf->Cell(0, 4, $pdfText('Taxa de Entrega:'), 0, 0);
            $pdf->Cell(0, 4, $pdfText('R$ ' . number_format((float)$orderRow['delivery_fee'], 2, ',', '.')), 0, 1, 'R');
        }
        
        if (!empty($orderRow['discount']) && (float)$orderRow['discount'] > 0) {
            $pdf->Cell(0, 4, $pdfText('Desconto:'), 0, 0);
            $pdf->Cell(0, 4, $pdfText('- R$ ' . number_format((float)$orderRow['discount'], 2, ',', '.')), 0, 1, 'R');
        }
        
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 5, $pdfText('TOTAL:'), 0, 0);
        $pdf->Cell(0, 5, $pdfText('R$ ' . number_format((float)($orderRow['total'] ?? 0), 2, ',', '.')), 0, 1, 'R');

        // Observações gerais
        if (!empty($orderRow['notes'])) {
            $pdf->Ln(3);
            self::drawDashedLine($pdf, self::MARGIN, $pdf->GetY(), self::WIDTH - self::MARGIN, $pdf->GetY());
            $pdf->Ln(3);
            
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(0, 4, $pdfText('OBSERVACOES'), 0, 1);
            $pdf->SetFont('Arial', '', 7);
            $pdf->MultiCell(0, 3, $pdfText($orderRow['notes']), 0);
        }

        // RODAPÉ
        $pdf->Ln(4);
        self::drawDashedLine($pdf, self::MARGIN, $pdf->GetY(), self::WIDTH - self::MARGIN, $pdf->GetY());
        $pdf->Ln(3);
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 4, $pdfText('Obrigado pela preferencia!'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(0, 3, $pdfText('Volte sempre!'), 0, 1, 'C');
        
        $pdf->Ln(3);
        self::drawDashedLine($pdf, self::MARGIN, $pdf->GetY(), self::WIDTH - self::MARGIN, $pdf->GetY());
        $pdf->Ln(8);

        // Export
        $tmp = sys_get_temp_dir() . '/receipt_' . uniqid() . '.pdf';
        $pdf->Output('F', $tmp);

        return $tmp;
    }
}
