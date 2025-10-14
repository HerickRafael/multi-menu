<?php
/**
 * Teste direto do endpoint local de configurações
 */

// Definir constantes necessárias
define('BASE_PATH', '/Applications/XAMPP/xamppfiles/htdocs/multi-menu');

// Incluir dependências
require_once BASE_PATH . '/app/controllers/AdminEvolutionInstanceController.php';

echo "=== TESTE ENDPOINT LOCAL DE CONFIGURAÇÕES ===\n\n";

// Simular parâmetros
$params = [
    'slug' => 'wellburger',
    'instanceName' => 'teste'
];

try {
    $controller = new AdminEvolutionInstanceController();
    
    echo "Testando método get_settings...\n";
    
    // Capturar output
    ob_start();
    $controller->get_settings($params);
    $output = ob_get_clean();
    
    echo "Output do método:\n";
    echo $output . "\n\n";
    
    // Tentar decodificar JSON
    $data = json_decode($output, true);
    if ($data) {
        echo "JSON válido:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
        
        if (isset($data['success']) && $data['success']) {
            echo "\nConfiguração encontrada:\n";
            foreach ($data['data'] as $key => $value) {
                echo "- $key: " . ($value ? 'true' : 'false') . "\n";
            }
        }
    } else {
        echo "Erro: Resposta não é JSON válido\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";