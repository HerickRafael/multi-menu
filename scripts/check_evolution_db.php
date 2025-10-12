<?php
// scripts/check_evolution_db.php
// Usa a função db() existente em app/config/db.php para checar colunas e tabela evolution

require_once __DIR__ . '/../app/config/db.php';

try {
    $pdo = db();
} catch (Throwable $e) {
    echo "ERROR: não foi possível conectar ao banco via db(): " . $e->getMessage() . PHP_EOL;
    exit(1);
}

$dbname = 'menu'; // conforme app/config/db.php

try {
    // Verifica colunas
    $st = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'companies' AND COLUMN_NAME IN ('evolution_server_url','evolution_api_key')");
    $st->execute([$dbname]);
    $cols = $st->fetchAll(PDO::FETCH_COLUMN);

    echo "Colunas encontradas em companies:\n";
    if (empty($cols)) {
        echo "  (nenhuma das colunas 'evolution_server_url' ou 'evolution_api_key' encontrada)\n";
    } else {
        foreach ($cols as $c) echo "  - $c\n";
    }

    // Verifica existência da tabela evolution_instances
    $st = $pdo->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'evolution_instances'");
    $st->execute([$dbname]);
    $tbl = $st->fetchColumn();

    if ($tbl) {
        echo "\nTabela 'evolution_instances' existe. Estrutura:\n";
        $st = $pdo->query("DESCRIBE {$dbname}.evolution_instances");
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            echo sprintf("  %s  | %s  | %s\n", $r['Field'], $r['Type'], $r['Null']);
        }
    } else {
        echo "\nTabela 'evolution_instances' NÃO encontrada.\n";
    }

    exit(0);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    exit(2);
}
