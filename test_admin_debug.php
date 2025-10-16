<?php
// Teste simples para debug do admin de métodos de pagamento

chdir(__DIR__);
require_once 'app/controllers/AdminPaymentMethodController.php';

$controller = new AdminPaymentMethodController();

echo "🔧 Testando biblioteca de ícones...\n\n";

// Usar reflection para acessar método privado
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('listBrandLibrary');
$method->setAccessible(true);

$brandLibrary = $method->invoke($controller);

echo "📁 Ícones encontrados na biblioteca:\n";
foreach ($brandLibrary as $brand) {
    echo "- {$brand['label']} ({$brand['slug']}) - {$brand['value']}\n";
}

echo "\n🔍 Procurando por cash.svg:\n";
$cashBrand = array_filter($brandLibrary, function($brand) {
    return $brand['slug'] === 'cash';
});

if ($cashBrand) {
    echo "✅ cash.svg encontrado na biblioteca!\n";
    foreach ($cashBrand as $brand) {
        echo "   Label: {$brand['label']}\n";
        echo "   Slug: {$brand['slug']}\n";
        echo "   URL: {$brand['url']}\n";
        echo "   Value: {$brand['value']}\n";
    }
} else {
    echo "❌ cash.svg NÃO encontrado na biblioteca\n";
    echo "📋 Verificando arquivo no diretório...\n";
    
    $cashFile = 'public/assets/card-brands/cash.svg';
    if (file_exists($cashFile)) {
        echo "✅ Arquivo $cashFile existe\n";
        echo "📊 Tamanho: " . filesize($cashFile) . " bytes\n";
        echo "🕐 Modificado: " . date('Y-m-d H:i:s', filemtime($cashFile)) . "\n";
    } else {
        echo "❌ Arquivo $cashFile NÃO existe\n";
    }
}

echo "\n🎯 Tipos permitidos:\n";
$allowedTypes = ['credit', 'debit', 'others', 'voucher', 'pix', 'cash'];
foreach ($allowedTypes as $type) {
    echo "- $type\n";
}