<?php
/**
 * Gera um relatório CSV com candidatos a duplicatas de payment_methods.
 * - duplicatas por icon exato
 * - duplicatas por name exato (normalizado)
 * - pares com nome semelhante (Levenshtein <= threshold)
 *
 * Saída: scripts/payment_method_duplicates_report.csv
 * Uso: php scripts/generate_payment_method_duplicates_report.php
 */

chdir(__DIR__ . '/..');
require_once __DIR__ . '/../app/config/db.php';
$pdo = db();

$outFile = __DIR__ . '/payment_method_duplicates_report.csv';
$fp = fopen($outFile, 'w');
if (!$fp) {
    echo "Erro ao abrir $outFile para escrita\n";
    exit(1);
}

fputcsv($fp, ['type', 'company_id', 'check_type', 'id_a', 'name_a', 'icon_a', 'id_b', 'name_b', 'icon_b', 'distance']);

// 1) icon exact duplicates
$iconQ = <<<SQL
SELECT company_id, `type`, JSON_UNQUOTE(JSON_EXTRACT(meta,'$.icon')) AS icon, COUNT(*) AS cnt
FROM payment_methods
WHERE (icon IS NOT NULL AND TRIM(icon) <> '') OR (meta IS NOT NULL AND JSON_EXTRACT(meta, '$.icon') IS NOT NULL)
GROUP BY company_id, `type`, icon
HAVING cnt > 1
SQL;
$iconGroups = $pdo->query($iconQ)->fetchAll(PDO::FETCH_ASSOC);
foreach ($iconGroups as $g) {
    $company = $g['company_id']; $type = $g['type']; $icon = $g['icon'];
    $stmt = $pdo->prepare("SELECT id, name, icon, meta FROM payment_methods WHERE company_id = ? AND `type` = ? AND (icon = ? OR (meta IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(meta,'$.icon')) = ?)) ORDER BY id ASC");
    $stmt->execute([$company, $type, $icon, $icon]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    for ($i = 0; $i < count($rows); $i++) {
        for ($j = $i+1; $j < count($rows); $j++) {
            $a = $rows[$i]; $b = $rows[$j];
            fputcsv($fp, [$type, $company, 'icon_exact', $a['id'], $a['name'], $a['icon'] ?? '', $b['id'], $b['name'], $b['icon'] ?? '', '0']);
        }
    }
}

// 2) name exact duplicates (normalized)
$nameQ = <<<SQL
SELECT company_id, `type`, LOWER(TRIM(name)) AS name_norm, COUNT(*) AS cnt
FROM payment_methods
WHERE name IS NOT NULL AND TRIM(name) <> ''
GROUP BY company_id, `type`, name_norm
HAVING cnt > 1
SQL;
$nameGroups = $pdo->query($nameQ)->fetchAll(PDO::FETCH_ASSOC);
foreach ($nameGroups as $g) {
    $company = $g['company_id']; $type = $g['type']; $name_norm = $g['name_norm'];
    $stmt = $pdo->prepare("SELECT id, name, icon FROM payment_methods WHERE company_id = ? AND `type` = ? AND LOWER(TRIM(name)) = ? ORDER BY id ASC");
    $stmt->execute([$company, $type, $name_norm]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    for ($i = 0; $i < count($rows); $i++) {
        for ($j = $i+1; $j < count($rows); $j++) {
            $a = $rows[$i]; $b = $rows[$j];
            fputcsv($fp, [$type, $company, 'name_exact', $a['id'], $a['name'], $a['icon'] ?? '', $b['id'], $b['name'], $b['icon'] ?? '', '0']);
        }
    }
}

// 3) fuzzy name pairs within same company/type
$threshold = 2; // Levenshtein distance threshold
$allStmt = $pdo->query("SELECT id, company_id, `type`, name, icon FROM payment_methods WHERE name IS NOT NULL AND TRIM(name) <> '' ORDER BY company_id, `type`, id");
$all = $allStmt->fetchAll(PDO::FETCH_ASSOC);

// group by company/type
$groups = [];
foreach ($all as $r) {
    $key = $r['company_id'] . '|' . $r['type'];
    $groups[$key][] = $r;
}

foreach ($groups as $key => $items) {
    $n = count($items);
    for ($i = 0; $i < $n; $i++) {
        for ($j = $i+1; $j < $n; $j++) {
            $a = $items[$i]; $b = $items[$j];
            $na = strtolower(trim($a['name']));
            $nb = strtolower(trim($b['name']));
            // ignore very short names
            if (strlen($na) < 3 || strlen($nb) < 3) continue;
            $dist = levenshtein($na, $nb);
            if ($dist <= $threshold) {
                fputcsv($fp, [$a['type'], $a['company_id'], 'name_fuzzy', $a['id'], $a['name'], $a['icon'] ?? '', $b['id'], $b['name'], $b['icon'] ?? '', (string)$dist]);
            }
        }
    }
}

fclose($fp);

echo "Relatório gerado em: $outFile\n";

