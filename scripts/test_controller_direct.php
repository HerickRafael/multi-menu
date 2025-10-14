<?php
/**
 * Teste direto do controller Evolution Instance
 */

// Incluir autoload e dependências
require_once __DIR__ . '/../app/controllers/AdminEvolutionInstanceController.php';

// Simular parâmetros
$params = [
    'slug' => 'teste',
    'instanceName' => 'teste_instance'
];

// Simular dados POST para teste
$_POST = json_encode(['rejectCall' => true]);

echo "=== TESTE DIRETO DO CONTROLLER ===\n\n";

try {
    $controller = new AdminEvolutionInstanceController();
    
    echo "Controller criado com sucesso\n";
    
    // Verificar se os métodos existem
    if (method_exists($controller, 'save_settings')) {
        echo "Método save_settings existe\n";
    } else {
        echo "ERRO: Método save_settings não existe\n";
    }
    
    if (method_exists($controller, 'get_settings')) {
        echo "Método get_settings existe\n";
    } else {
        echo "ERRO: Método get_settings não existe\n";
    }
    
    echo "\n=== TESTANDO MÉTODOS ===\n";
    
    // Como não temos autenticação adequada, só testamos se os métodos existem
    // e têm a estrutura correta
    
    $reflection = new ReflectionClass($controller);
    $saveMethod = $reflection->getMethod('save_settings');
    $getMethod = $reflection->getMethod('get_settings');
    
    echo "save_settings é público: " . ($saveMethod->isPublic() ? 'SIM' : 'NÃO') . "\n";
    echo "get_settings é público: " . ($getMethod->isPublic() ? 'SIM' : 'NÃO') . "\n";
    
    echo "\nParâmetros esperados:\n";
    echo "save_settings: " . $saveMethod->getParameters()[0]->getName() . "\n";
    echo "get_settings: " . $getMethod->getParameters()[0]->getName() . "\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";