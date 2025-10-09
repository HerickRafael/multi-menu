<?php
/**
 * Script para detectar e resolver duplicatas em payment_methods.
 *
 * Uso:
 *  - Dry run (apenas relatório):
 *      php scripts/resolve_payment_method_duplicates.php
 *
 *  - Aplicar mudanças (atualiza referências e remove duplicatas):
 *      php scripts/resolve_payment_method_duplicates.php --apply
 *
 * O script segue estas regras:
 *  - Detecta grupos duplicados por (company_id, type, icon) quando icon não está vazio.
 *  - Para cada grupo, escolhe um registro "survivor" (menor id) e atualiza todas as referências
 *    (colunas com FK para payment_methods) para apontar para o survivor.
 *  - Em seguida deleta os registros duplicados (exceto o survivor).
 *  - Cada grupo é processado dentro de uma transação; em caso de erro, o grupo é revertido e o erro é reportado.
 *
 * Atenção:
 *  - Faça backup do banco antes de rodar com --apply.
 *  - O script atualiza apenas colunas que têm FK definidas referenciando payment_methods
 *    (detectadas em INFORMATION_SCHEMA.KEY_COLUMN_USAGE). Isso evita esquecer referências.
 */

chdir(__DIR__ . '/..');
require_once __DIR__ . '/../app/config/db.php';
$pdo = db();

$apply = in_array('--apply', $argv, true);

// modo de detecção: icon | name | both
$mode = 'icon';
foreach ($argv as $a) {
    if (str_starts_with($a, '--mode=')) {
        $mode = strtolower(substr($a, strlen('--mode=')));
    }
}
if (!in_array($mode, ['icon', 'name', 'both'], true)) {
    echo "Modo inválido: $mode. Use --mode=icon|name|both\n";
    exit(2);
}

echo "[resolve_payment_method_duplicates]" . PHP_EOL;
echo $apply ? "Modo: APPLY (alterações serão aplicadas)" . PHP_EOL : "Modo: DRY-RUN (nenhuma alteração será feita)" . PHP_EOL;

// 1) localizar colunas que referenciam payment_methods via FK
$refStmt = $pdo->prepare("SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME
  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
  WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME = 'payment_methods'");
$refStmt->execute();
$refs = $refStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$refs) {
    echo "Nenhuma FK encontrada referenciando payment_methods. O script ainda atualizará tabelas com coluna 'payment_method_id' se forem detectadas.\n";
}

// Além das FKs, detectar colunas nomeadas 'payment_method_id' mesmo sem FK
$colsStmt = $pdo->prepare("SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'payment_method_id'");
$colsStmt->execute();
$cols = $colsStmt->fetchAll(PDO::FETCH_ASSOC);

// Mesclar referências (preferir FK listadas, adicionar colunas diretas se não duplicar)
$refMap = [];
foreach ($refs as $r) {
    $t = $r['TABLE_NAME']; $c = $r['COLUMN_NAME'];
    $refMap[$t][] = $c;
}
foreach ($cols as $c) {
    $t = $c['TABLE_NAME']; $col = $c['COLUMN_NAME'];
    if (!isset($refMap[$t]) || !in_array($col, $refMap[$t], true)) {
        $refMap[$t][] = $col;
    }
}

echo "Tabelas com possíveis referências: " . count($refMap) . PHP_EOL;
foreach ($refMap as $table => $cols) {
    echo " - $table: " . implode(', ', $cols) . PHP_EOL;
}

// 2) localizar grupos duplicados por company_id, type, icon (icone não vazio)

$groups = [];

// query por icon
if ($mode === 'icon' || $mode === 'both') {
    $dupQuery = <<<SQL
SELECT company_id, `type`, JSON_UNQUOTE(JSON_EXTRACT(meta, '$.icon')) AS icon, COUNT(*) AS cnt
FROM payment_methods
WHERE (icon IS NOT NULL AND TRIM(icon) <> '') OR (meta IS NOT NULL AND JSON_EXTRACT(meta, '$.icon') IS NOT NULL)
GROUP BY company_id, `type`, icon
HAVING cnt > 1
ORDER BY cnt DESC
SQL;
    try {
        $stmt = $pdo->query($dupQuery);
        $iconGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        echo "Erro ao buscar duplicatas por icon: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
    foreach ($iconGroups as $ig) {
        $ig['kind'] = 'icon';
        $groups[] = $ig;
    }
}

// query por name (normalizado)
if ($mode === 'name' || $mode === 'both') {
    $dupQueryName = <<<SQL
SELECT company_id, `type`, LOWER(TRIM(name)) AS name_norm, COUNT(*) AS cnt
FROM payment_methods
WHERE name IS NOT NULL AND TRIM(name) <> ''
GROUP BY company_id, `type`, name_norm
HAVING cnt > 1
ORDER BY cnt DESC
SQL;
    try {
        $stmt = $pdo->query($dupQueryName);
        $nameGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        echo "Erro ao buscar duplicatas por name: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
    foreach ($nameGroups as $ng) {
        $ng['kind'] = 'name';
        $groups[] = $ng;
    }
}

if (!$groups) {
    echo "Nenhuma duplicata encontrada (modo={$mode}).\n";
    exit(0);
}

echo "Grupos duplicados encontrados: " . count($groups) . PHP_EOL;

foreach ($groups as $gIdx => $g) {
    $company = $g['company_id'];
    $type = $g['type'];
    $kind = $g['kind'] ?? 'icon';

    if ($kind === 'icon') {
        $icon = $g['icon'];
        echo PHP_EOL . "Grupo #" . ($gIdx+1) . ": [icon] company_id={$company} type={$type} icon=" . ($icon ?? 'NULL') . PHP_EOL;
        $idsStmt = $pdo->prepare("SELECT id, name, active, icon, meta FROM payment_methods WHERE company_id = ? AND `type` = ? AND (icon = ? OR (meta IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(meta,'$.icon')) = ?)) ORDER BY id ASC");
        $idsStmt->execute([$company, $type, $icon, $icon]);
    } else {
        $name_norm = $g['name_norm'];
        echo PHP_EOL . "Grupo #" . ($gIdx+1) . ": [name] company_id={$company} type={$type} name_norm=" . ($name_norm ?? 'NULL') . PHP_EOL;
        $idsStmt = $pdo->prepare("SELECT id, name, active, icon, meta FROM payment_methods WHERE company_id = ? AND `type` = ? AND LOWER(TRIM(name)) = ? ORDER BY id ASC");
        $idsStmt->execute([$company, $type, $name_norm]);
    }

    $rows = $idsStmt->fetchAll(PDO::FETCH_ASSOC);
    $ids = array_column($rows, 'id');
    echo " - IDs: " . implode(', ', $ids) . PHP_EOL;

    // escolher survivor (menor id que esteja ativo preferencialmente)
    $survivor = null;
    foreach ($rows as $r) {
        if (!empty($r['active'])) { $survivor = (int)$r['id']; break; }
    }
    if ($survivor === null) $survivor = (int)$rows[0]['id'];

    $duplicates = array_filter($ids, fn($i) => (int)$i !== (int)$survivor);
    echo " - Survivor: $survivor; Duplicates to remove: " . implode(', ', $duplicates) . PHP_EOL;

    // mostrar contagem de referências para cada tabela/coluna
    $totalRefs = 0;
    $refCounts = [];
    foreach ($refMap as $table => $columns) {
        foreach ($columns as $col) {
            $q = $pdo->prepare("SELECT COUNT(*) as cnt FROM `$table` WHERE `$col` IN (" . implode(',', array_map('intval', $duplicates)) . ")");
            $q->execute();
            $c = (int)($q->fetchColumn() ?? 0);
            if ($c > 0) {
                $refCounts[] = [ 'table' => $table, 'column' => $col, 'count' => $c ];
                $totalRefs += $c;
            }
        }
    }

    if ($refCounts) {
        echo " - Referências encontradas (total: $totalRefs):\n";
        foreach ($refCounts as $rc) echo "    * {$rc['table']}.{$rc['column']}: {$rc['count']} rows\n";
    } else {
        echo " - Nenhuma referência encontrada para esses ids.\n";
    }

    if (!$apply) continue; // no changes in dry-run

    // Aplica para o grupo: atualizar referências e deletar duplicatas
    echo " Aplicando mudanças para o grupo...\n";
    try {
        $pdo->beginTransaction();

        // Atualizar todas as referências detectadas
        foreach ($refCounts as $rc) {
            $table = $rc['table']; $col = $rc['column'];
            $upd = $pdo->prepare("UPDATE `$table` SET `$col` = ? WHERE `$col` IN (" . implode(',', array_map('intval', $duplicates)) . ")");
            $upd->execute([$survivor]);
            echo "  - Atualizado $table.$col -> $survivor\n";
        }

        // Por segurança, re-verificar se há referências restantes
        $remainingRefs = false;
        foreach ($refMap as $table => $columns) {
            foreach ($columns as $col) {
                $q = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$col` IN (" . implode(',', array_map('intval', $duplicates)) . ")");
                $q->execute();
                if ((int)$q->fetchColumn() > 0) { $remainingRefs = true; break 2; }
            }
        }

        if ($remainingRefs) {
            throw new RuntimeException('Ainda existem referências para os ids duplicados após atualização. Abortando.');
        }

        // Deletar duplicatas
        $del = $pdo->prepare("DELETE FROM payment_methods WHERE id IN (" . implode(',', array_map('intval', $duplicates)) . ")");
        $del->execute();
        echo "  - Duplicatas removidas: " . implode(', ', $duplicates) . PHP_EOL;

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        echo "  Erro ao processar grupo: " . $e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL . "Processamento concluído." . PHP_EOL;

