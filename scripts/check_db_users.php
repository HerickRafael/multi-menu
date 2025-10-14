<?php
// Script para verificar dados do banco
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config/db.php';

try {
    $pdo = db();
    echo "✅ Conectado ao banco\n\n";
    
    // Verificar usuários
    echo "=== USUÁRIOS ===\n";
    $stmt = $pdo->query('SELECT id, name, email, role, company_id FROM users');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Nome: {$row['name']} | Email: {$row['email']} | Role: {$row['role']} | Company: {$row['company_id']}\n";
    }
    
    // Verificar empresas  
    echo "\n=== EMPRESAS ===\n";
    $stmt = $pdo->query('SELECT id, name, slug FROM companies');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Nome: {$row['name']} | Slug: {$row['slug']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>