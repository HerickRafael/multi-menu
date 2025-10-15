#!/usr/bin/env php
<?php
/**
 * 🧹 SCRIPT DE LIMPEZA - Remove duplicações de helpers
 * 
 * Este script remove todas as declarações duplicadas de funções helper
 * que agora estão centralizadas em CommonHelpers.php
 */

echo "🧹 Iniciando limpeza de duplicações...\n\n";

$rootDir = dirname(__DIR__);
$viewsDir = $rootDir . '/app/Views';

// Padrões de função para remover
$patterns = [
    '/if\s*\(\s*!function_exists\s*\(\s*[\'"]e[\'"]\s*\)\s*\)\s*{[^}]*function\s+e\s*\([^}]*}\s*}/s',
    '/if\s*\(\s*!function_exists\s*\(\s*[\'"]price_br[\'"]\s*\)\s*\)\s*{[^}]*function\s+price_br\s*\([^}]*}\s*}/s',
    '/if\s*\(\s*!function_exists\s*\(\s*[\'"]base_url[\'"]\s*\)\s*\)\s*{[^}]*function\s+base_url\s*\([^}]*}\s*}/s',
    '/if\s*\(\s*!function_exists\s*\(\s*[\'"]badgeNew[\'"]\s*\)\s*\)\s*{[^}]*function\s+badgeNew\s*\([^}]*}\s*}/s',
    '/if\s*\(\s*!function_exists\s*\(\s*[\'"]normalize_color_hex[\'"]\s*\)\s*\)\s*{[^}]*function\s+normalize_color_hex\s*\([^}]*}\s*}/s',
];

// Função recursiva para encontrar arquivos PHP
function findPhpFiles($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

// Processa arquivo
function cleanFile($filePath, $patterns) {
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    foreach ($patterns as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }
    
    // Remove linhas vazias excessivas
    $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
    
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        return true;
    }
    
    return false;
}

// Encontra todos os arquivos PHP
$phpFiles = findPhpFiles($viewsDir);

$cleanedCount = 0;
$totalFiles = count($phpFiles);

echo "📁 Encontrados $totalFiles arquivos PHP\n";
echo "🔍 Procurando duplicações...\n\n";

foreach ($phpFiles as $file) {
    if (cleanFile($file, $patterns)) {
        $relativePath = str_replace($rootDir . '/', '', $file);
        echo "✅ Limpo: $relativePath\n";
        $cleanedCount++;
    }
}

echo "\n🎉 Limpeza concluída!\n";
echo "📊 Arquivos processados: $totalFiles\n";
echo "🧹 Arquivos limpos: $cleanedCount\n";
echo "💾 Duplicações removidas com sucesso!\n";