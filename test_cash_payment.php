<?php
// Teste para verificar se métodos de dinheiro estão funcionando

chdir(__DIR__);
require_once 'app/config/db.php';
require_once 'app/models/PaymentMethod.php';
require_once 'app/models/Company.php';

try {
    $company = Company::findBySlug('wollburger');
    if (!$company) {
        echo "Empresa não encontrada\n";
        exit;
    }
    
    echo "Empresa encontrada: " . $company['name'] . "\n";
    echo "ID da empresa: " . $company['id'] . "\n\n";
    
    // Verificar métodos existentes
    $methods = PaymentMethod::allByCompany((int)$company['id']);
    echo "Métodos existentes:\n";
    foreach ($methods as $method) {
        echo "- ID: {$method['id']}, Nome: {$method['name']}, Tipo: {$method['type']}, Ativo: {$method['active']}\n";
    }
    
    // Verificar se já existe método cash
    $cashMethods = array_filter($methods, function($method) {
        return ($method['type'] ?? '') === 'cash';
    });
    
    if ($cashMethods) {
        echo "\n✅ Métodos de dinheiro encontrados:\n";
        foreach ($cashMethods as $method) {
            echo "- ID: {$method['id']}, Nome: {$method['name']}, Ativo: {$method['active']}\n";
        }
    } else {
        echo "\n❌ Nenhum método de dinheiro encontrado\n";
        echo "Criando método de dinheiro de teste...\n";
        
        // Criar método de dinheiro
        $data = [
            'company_id' => (int)$company['id'],
            'name' => 'Dinheiro',
            'type' => 'cash',
            'instructions' => 'Pagamento na entrega em dinheiro',
            'active' => 1,
            'sort_order' => PaymentMethod::nextSortOrder((int)$company['id']),
            'meta' => ['icon' => '/assets/card-brands/cash.svg']
        ];
        
        $newId = PaymentMethod::create($data);
        echo "✅ Método de dinheiro criado com ID: $newId\n";
    }
    
    // Verificar métodos ativos
    $activeMethods = PaymentMethod::activeByCompany((int)$company['id']);
    $activeCashMethods = array_filter($activeMethods, function($method) {
        return ($method['type'] ?? '') === 'cash';
    });
    
    echo "\n📊 Resumo dos métodos ativos:\n";
    echo "Total de métodos ativos: " . count($activeMethods) . "\n";
    echo "Métodos de dinheiro ativos: " . count($activeCashMethods) . "\n";
    
    if ($activeCashMethods) {
        echo "\n🎉 Métodos de dinheiro estão funcionando!\n";
        foreach ($activeCashMethods as $method) {
            echo "- {$method['name']} (ID: {$method['id']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}