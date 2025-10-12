<?php
declare(strict_types=1);
// scripts/test_send_pdf.php

require __DIR__ . '/../app/config/db.php';
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/models/Company.php';
require __DIR__ . '/../app/services/ThermalReceipt.php';

$slug = $argv[1] ?? 'wollburger';
$instanceName = $argv[2] ?? 'WollBurger';
$to = $argv[3] ?? '5551920017687';
$shouldSend = (isset($argv[4]) && in_array(strtolower($argv[4]), ['send', 'true', '1'], true));

$company = Company::findBySlug($slug);
if (!$company) {
    fwrite(STDERR, "Empresa '{$slug}' nao encontrada\n");
    exit(1);
}

// build a sample order for testing
$orderId = 'TESTPDF-' . date('YmdHis');
$order = [
    'id' => $orderId,
    'customer_name' => 'Cliente Teste',
    'customer_phone' => '+559999999999',
    'customer_address' => "Rua Teste, 123\nBairro",
    'subtotal' => 25.0,
    'delivery_fee' => 5.0,
    'discount' => 0.0,
    'total' => 30.0,
    'notes' => 'Observação de teste',
];

$items = [
    ['quantity' => 1, 'product_name' => 'Pizza Teste', 'line_total' => 20.0, 'modifiers' => [['name' => 'Sem cebola']]],
    ['quantity' => 1, 'product_name' => 'Bebida', 'line_total' => 5.0],
];

echo "Gerando PDF de teste para empresa '{$slug}'...\n";
$pdfPath = ThermalReceipt::generatePdf($company, $order, $items);

if (!file_exists($pdfPath)) {
    fwrite(STDERR, "Falha: PDF nao foi gerado.\n");
    exit(2);
}

$size = filesize($pdfPath);
echo "PDF gerado: {$pdfPath} (" . number_format($size / 1024, 2) . " KB)\n";

if (!$shouldSend) {
    echo "Modo: somente-geração. Para enviar, rode: php scripts/test_send_pdf.php {$slug} {$instanceName} {$to} send\n";
    exit(0);
}

// envio via Evolution API
echo "Enviando via Evolution (instance='{$instanceName}', to='{$to}')...\n";

$apiKey = $company['evolution_api_key'] ?? null;
$apiUrl = rtrim($company['evolution_server_url'] ?? '', '/');
if (!$apiKey || !$apiUrl) {
    fwrite(STDERR, "Config Evolution ausente na empresa '{$slug}'.\n");
    unlink($pdfPath);
    exit(3);
}

// instantiate client
$client = new \EvolutionApiPlugin\EvolutionApi($apiKey, $apiUrl, 'v2');

$b64 = base64_encode(file_get_contents($pdfPath));
$fileName = "pedido_{$orderId}.pdf";
$media = $client->createMediaStructure('document', 'application/pdf', 'Recibo Pedido ' . $orderId, $b64, $fileName);

try {
    $resp = $client->sendMediaMessage($instanceName, $to, $media);
    echo "Resposta do provedor:\n";
    var_export($resp);
    echo "\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Erro ao enviar: " . $e->getMessage() . "\n");
    unlink($pdfPath);
    exit(4);
}

// cleanup
unlink($pdfPath);
echo "PDF temporário removido.\n";

exit(0);
