<?php
/**
 * Layout base para páginas admin com sistemas centralizados
 * Inclui automaticamente skeleton loading, toast notifications e utilitários comuns
 */

// Verificar se as variáveis necessárias estão definidas
$title = $title ?? 'Admin - ' . ($company['name'] ?? 'Sistema');
$content = $content ?? '';
$activeSlug = $activeSlug ?? ($slug ?? ($company['slug'] ?? ''));

// Helper de escape se não existir
if (!function_exists('e')) {
    function e($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Sistemas centralizados - CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/ui.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/skeleton.css') ?>">
    
    <!-- Meta para sistemas centralizados -->
    <meta name="admin-base-url" content="<?= base_url() ?>">
    <meta name="admin-slug" content="<?= e($activeSlug) ?>">
</head>
<body data-skeleton-auto class="bg-gray-50">

    <!-- Conteúdo da página -->
    <?= $content ?>

    <!-- Toast container (será criado dinamicamente pelo ToastSystem) -->
    <div id="toast-fallback" class="fixed top-4 right-4 z-50 space-y-2 opacity-0 transition-opacity duration-300 pointer-events-none"></div>

    <!-- Sistemas centralizados - JavaScript -->
    <script src="<?= base_url('assets/js/toast-system.js') ?>"></script>
    <script src="<?= base_url('assets/js/skeleton-system.js') ?>"></script>
    <script src="<?= base_url('assets/js/admin-common.js') ?>"></script>
    
    <!-- Inicialização automática -->
    <script>
        // Configurar sistemas após carregamento
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar base URL para requisições
            if (window.AdminCommon) {
                window.AdminCommon.baseUrl = document.querySelector('meta[name="admin-base-url"]')?.content || '';
                window.AdminCommon.slug = document.querySelector('meta[name="admin-slug"]')?.content || '';
            }
            
            // Auto-enhance para páginas com data-skeleton-auto
            if (window.SkeletonSystem && document.body.hasAttribute('data-skeleton-auto')) {
                window.SkeletonSystem.VisualStates.enhanceButtons();
            }
            
            // Log de inicialização (apenas em desenvolvimento)
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.log('✅ Sistemas centralizados carregados:', {
                    ToastSystem: !!window.ToastSystem,
                    SkeletonSystem: !!window.SkeletonSystem,
                    AdminCommon: !!window.AdminCommon
                });
            }
        });
        
        // Função global de utilitário para seleção de elementos
        window.el = function(id) {
            return typeof id === 'string' ? document.getElementById(id) : id;
        };
        
        // Compatibilidade com toast() global
        if (!window.toast && window.ToastSystem) {
            window.toast = window.ToastSystem.toast;
        }
    </script>
</body>
</html>