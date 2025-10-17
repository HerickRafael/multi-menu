<?php
// Teste do sistema de impressão de PDF

require_once __DIR__ . '/app/config/db.php';
require_once __DIR__ . '/app/models/Company.php';
require_once __DIR__ . '/app/models/Order.php';
require_once __DIR__ . '/app/services/ThermalReceipt.php';

echo "=== TESTE DO SISTEMA DE IMPRESSÃO ===\n\n";

try {
    // Conecta ao banco
    $db = db();
    echo "✓ Conexão com banco estabelecida\n";
    
    // Busca uma empresa
    $company = Company::findBySlug('wollburger');
    if (!$company) {
        echo "✗ Empresa não encontrada\n";
        exit(1);
    }
    echo "✓ Empresa encontrada: " . $company['name'] . "\n";
    
    // Busca o último pedido
    $stmt = $db->prepare("SELECT * FROM orders WHERE company_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([(int)$company['id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "✗ Nenhum pedido encontrado\n";
        exit(1);
    }
    echo "✓ Pedido encontrado: #" . $order['id'] . "\n";
    
    // Busca os itens do pedido
    $orderWithItems = Order::findWithItems($db, (int)$order['id'], (int)$company['id']);
    
    if (!$orderWithItems) {
        echo "✗ Não foi possível carregar pedido com itens\n";
        exit(1);
    }
    echo "✓ Itens do pedido carregados: " . count($orderWithItems['items']) . " itens\n";
    
    // Tenta gerar o PDF
    echo "\n--- Gerando PDF ---\n";
    $pdfPath = ThermalReceipt::generatePdf(
        $company,
        $orderWithItems,
        $orderWithItems['items'] ?? []
    );
    
    if (file_exists($pdfPath)) {
        $fileSize = filesize($pdfPath);
        echo "✓ PDF gerado com sucesso!\n";
        echo "  Caminho: $pdfPath\n";
        echo "  Tamanho: " . number_format($fileSize / 1024, 2) . " KB\n";
        
        // Remove o arquivo de teste
        unlink($pdfPath);
        echo "✓ Arquivo temporário removido\n";
    } else {
        echo "✗ Arquivo PDF não foi criado\n";
        exit(1);
    }
    
    echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    echo "\n✓ Sistema de impressão está funcionando corretamente!\n";
    echo "✓ O botão de imprimir deve funcionar agora.\n";
    
} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
