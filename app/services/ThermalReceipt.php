<?php
declare(strict_types=1);
// app/services/ThermalReceipt.php

require_once __DIR__ . '/../../vendor/autoload.php';

class ThermalReceipt
{
    public static function generatePdf(array $company, array $orderRow, array $items, string $rawMessage = ''): string
    {
        $width = 58; // mm for 58mm thermal
        $height = 300; // generous height; FPDF will clip but it's fine
        $pdf = new \FPDF('P', 'mm', array($width, $height));
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();
        // helper: convert UTF-8 to ISO-8859-1 for FPDF (fallback to utf8_decode)
        $pdfText = static function ($s) {
            if ($s === null) return '';
            if (!is_string($s)) $s = (string)$s;
            // try iconv first
            if (function_exists('iconv')) {
                $c = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
                if ($c !== false) return $c;
            }
            // fallback
            return @utf8_decode($s);
        };

        // Attempt to include company logo (if exists under public/)
        $logoPath = null;
        if (!empty($company['logo'])) {
            $candidate = __DIR__ . '/../../public/' . ltrim($company['logo'], '/');
            if (file_exists($candidate)) $logoPath = $candidate;
        }
        if (!$logoPath) {
            $placeholder = __DIR__ . '/../../public/assets/logo-placeholder.png';
            if (file_exists($placeholder)) $logoPath = $placeholder;
        }

        // Header
        if ($logoPath) {
            // validate image before using to avoid FPDF parsing errors
            $okImg = false;
            try {
                if (is_readable($logoPath)) {
                    $info = @getimagesize($logoPath);
                    if ($info && isset($info[0]) && isset($info[1])) {
                        $okImg = true;
                    }
                }
            } catch (Throwable $e) {
                $okImg = false;
            }

            if ($okImg) {
                // keep a small logo centered
                $imgW = 22; // mm
                $centerX = ($width - $imgW) / 2;
                try { $pdf->Image($logoPath, $centerX, 4, $imgW); } catch (Throwable $e) { /* ignore image errors */ }
                $pdf->Ln(18);
            } else {
                $pdf->Ln(4);
            }
        } else {
            $pdf->Ln(4);
        }

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 5, $pdfText($company['name'] ?? ''), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 7);
        if (!empty($company['address'])) {
            $pdf->Cell(0, 4, $pdfText($company['address']), 0, 1, 'C');
        }
        $pdf->Ln(2);

    // Order meta
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(0, 4, $pdfText('Pedido: #' . ($orderRow['id'] ?? '')), 0, 1);
        $pdf->Cell(0, 4, $pdfText('Data: ' . date('d/m/Y H:i')), 0, 1);
        $pdf->Cell(0, 4, $pdfText('Cliente: ' . ($orderRow['customer_name'] ?? '')), 0, 1);
        if (!empty($orderRow['customer_phone'])) $pdf->Cell(0, 4, $pdfText('Tel: ' . $orderRow['customer_phone']), 0, 1);
        if (!empty($orderRow['customer_address'])) {
            $pdf->MultiCell(0, 4, $pdfText('End: ' . str_replace("\n", ' / ', $orderRow['customer_address'])));
        }

        // include the raw formatted message if provided (helps match chat caption)
        if (!empty($rawMessage)) {
            $pdf->Ln(2);
            $pdf->SetFont('Courier', '', 7);
            $pdf->MultiCell(0, 4, trim($rawMessage));
            $pdf->Ln(2);
        }

        // separator
        $pdf->Ln(1);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(2, $pdf->GetY(), $width - 2, $pdf->GetY());
        $pdf->Ln(2);

        // Items header
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(12, 5, $pdfText('QTD'), 0, 0);
        $pdf->Cell(28, 5, $pdfText('DESCRIÇÃO'), 0, 0);
        $pdf->Cell(0, 5, $pdfText('VALOR'), 0, 1, 'R');
        $pdf->SetFont('Arial', '', 8);

        foreach ($items as $it) {
            $qty = (int)($it['quantity'] ?? $it['qty'] ?? 0);
            $name = $it['product_name'] ?? $it['name'] ?? '';
            $lineTotal = number_format((float)($it['line_total'] ?? 0), 2, ',', '.');

            // name may wrap; trim in UTF-8 then convert
            $nameShort = mb_strimwidth($name, 0, 60, '...', 'UTF-8');
            $pdf->Cell(12, 5, (string)$qty, 0, 0);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->MultiCell(28, 4, $pdfText($nameShort), 0);
            // align price to right on the same line as last multicell
            $pdf->SetXY($x + 28, $y);
            $pdf->Cell(0, 5, $pdfText('R$ ' . $lineTotal), 0, 1, 'R');
        }

        // totals
        $pdf->Ln(1);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(2, $pdf->GetY(), $width - 2, $pdf->GetY());
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 5, $pdfText('Subtotal: R$ ' . number_format((float)($orderRow['subtotal'] ?? 0), 2, ',', '.')), 0, 1, 'R');
        $pdf->Cell(0, 5, $pdfText('Entrega: R$ ' . number_format((float)($orderRow['delivery_fee'] ?? 0), 2, ',', '.')), 0, 1, 'R');
        if (!empty($orderRow['discount'])) {
            $pdf->Cell(0, 5, $pdfText('Desconto: R$ ' . number_format((float)($orderRow['discount'] ?? 0), 2, ',', '.')), 0, 1, 'R');
        }

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 6, $pdfText('TOTAL: R$ ' . number_format((float)($orderRow['total'] ?? 0), 2, ',', '.')), 0, 1, 'R');

        if (!empty($orderRow['notes'])) {
            $pdf->Ln(2);
            $pdf->SetFont('Arial', '', 7);
            $pdf->MultiCell(0, 4, $pdfText('Observações: ' . $orderRow['notes']));
        }

        $pdf->Ln(4);
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(0, 4, 'Obrigado pela preferência!', 0, 1, 'C');

        // small footer with company contact if present
        if (!empty($company['whatsapp'])) {
            $pdf->Ln(2);
            $pdf->SetFont('Arial', '', 7);
            $pdf->Cell(0, 4, $pdfText('Contato: ' . $company['whatsapp']), 0, 1, 'C');
        }

        // export to temp file
        $tmp = sys_get_temp_dir() . '/receipt_' . uniqid() . '.pdf';
        $pdf->Output('F', $tmp);

        return $tmp;
    }
}
