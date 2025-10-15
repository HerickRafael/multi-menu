<?php

declare(strict_types=1);

/**
 * 🔧 HELPERS CENTRALIZADOS - Sistema Multi-Menu
 * 
 * Este arquivo centraliza todas as funções helper usadas em todo o sistema,
 * eliminando duplicações e garantindo consistência.
 * 
 * @version 2.0
 * @author Sistema Multi-Menu
 */

// ============================================================================
// 🔒 SEGURANÇA E SANITIZAÇÃO
// ============================================================================

/**
 * Escape HTML para prevenir XSS
 * @param mixed $value Valor a ser escapado
 * @return string Valor escapado
 */
if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

// ============================================================================
// 🌐 URL E NAVEGAÇÃO
// ============================================================================

/**
 * Gera URL base do sistema
 * @param string $path Caminho adicional
 * @return string URL completa
 */
if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $config = config('base_url');

        if (!$config) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            $config = $scheme . '://' . $host . ($dir ? $dir : '');
        }

        $base = rtrim($config, '/');
        $path = ltrim($path, '/');

        return $path ? "$base/$path" : $base;
    }
}

// ============================================================================
// 💰 FORMATAÇÃO DE VALORES
// ============================================================================

/**
 * Formata valor para Real Brasileiro
 * @param float|string $value Valor numérico
 * @return string Valor formatado (ex: R$ 123,45)
 */
if (!function_exists('price_br')) {
    function price_br($value): string
    {
        $number = (float)$value;
        return 'R$ ' . number_format($number, 2, ',', '.');
    }
}

/**
 * Formata valor monetário BRL usando Intl se disponível
 * @param float|string $value Valor numérico
 * @return string Valor formatado
 */
if (!function_exists('format_currency_br')) {
    function format_currency_br($value): string
    {
        $number = (float)$value;
        
        if (class_exists('NumberFormatter')) {
            $formatter = new NumberFormatter('pt_BR', NumberFormatter::CURRENCY);
            return $formatter->formatCurrency($number, 'BRL');
        }
        
        return price_br($number);
    }
}

// ============================================================================
// 🎨 UI E COMPONENTES
// ============================================================================

/**
 * Gera badge "Novo" para produtos
 * @param string $date Data de criação
 * @param int $days Dias para considerar novo (padrão: 7)
 * @return string HTML do badge ou string vazia
 */
if (!function_exists('badge_new')) {
    function badge_new(string $date, int $days = 7): string
    {
        if (!$date) return '';
        
        $created = strtotime($date);
        $limit = time() - ($days * 24 * 60 * 60);
        
        if ($created > $limit) {
            return '<span class="badge badge-new">Novo</span>';
        }
        
        return '';
    }
}

/**
 * Gera badge "Promoção" para produtos
 * @param float $price Preço normal
 * @param float $promoPrice Preço promocional
 * @return string HTML do badge ou string vazia
 */
if (!function_exists('badge_promo')) {
    function badge_promo($price, $promoPrice): string
    {
        $price = (float)$price;
        $promoPrice = (float)$promoPrice;
        
        if ($promoPrice > 0 && $promoPrice < $price) {
            $discount = round((($price - $promoPrice) / $price) * 100);
            return "<span class=\"badge badge-promo\">-{$discount}%</span>";
        }
        
        return '';
    }
}

/**
 * Normaliza cor hexadecimal
 * @param string $color Cor em formato hex
 * @return string Cor normalizada (#RRGGBB)
 */
if (!function_exists('normalize_color_hex')) {
    function normalize_color_hex(?string $color, string $default = '#000000'): string
    {
        if (empty($color)) {
            return $default;
        }
        
        $color = trim($color);
        
        // Remove # se existir
        $color = ltrim($color, '#');
        
        // Se tem 3 dígitos, expande para 6
        if (strlen($color) === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }
        
        // Valida se é hexadecimal válido
        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $color)) {
            return $default;
        }
        
        return '#' . strtoupper($color);
    }
}

// ============================================================================
// 📊 STATUS E ESTADOS
// ============================================================================

/**
 * Gera pill de status unificado
 * @param string $status Status do item
 * @param string|null $text Texto personalizado
 * @param bool $showDot Mostrar dot indicador
 * @return string HTML do status pill
 */
if (!function_exists('status_pill')) {
    function status_pill(string $status, ?string $text = null, bool $showDot = true): string
    {
        $statusMap = [
            // Evolution / Conexão
            'open' => 'connected',
            'connecting' => 'connecting',
            'disconnected' => 'disconnected',
            'close' => 'disconnected',
            
            // Pedidos
            'concluido' => 'connected',
            'concluded' => 'connected',
            'completed' => 'connected',
            'cancelado' => 'disconnected',
            'cancelled' => 'disconnected',
            'canceled' => 'disconnected',
            'pendente' => 'pending',
            'pending' => 'pending',
            'preparando' => 'connecting',
            'preparing' => 'connecting',
            'paid' => 'connecting',
            'erro' => 'error',
            'error' => 'error',
            'failed' => 'error'
        ];
        
        $statusClass = $statusMap[strtolower($status)] ?? 'pending';
        $displayText = $text ?? ucfirst($status);
        $dot = $showDot ? '<span class="status-dot"></span>' : '';
        
        return '<span class="status-pill status-' . $statusClass . '">' . $dot . e($displayText) . '</span>';
    }
}

// ============================================================================
// 🔧 UTILITÁRIOS
// ============================================================================

/**
 * Gera src para upload de arquivo
 * @param string|null $value Caminho do arquivo
 * @param string $fallback Imagem padrão
 * @return string URL completa do arquivo
 */
if (!function_exists('upload_src')) {
    function upload_src(?string $value, string $fallback = 'assets/logo-placeholder.png'): string
    {
        if (!$value || trim($value) === '') {
            return base_url($fallback);
        }
        
        $value = trim($value);
        
        // Se já é uma URL completa, retorna como está
        if (preg_match('/^https?:\/\//', $value)) {
            return $value;
        }
        
        // Se começa com uploads/, adiciona base_url
        if (strpos($value, 'uploads/') === 0) {
            return base_url($value);
        }
        
        // Se não tem uploads/, adiciona o prefixo
        if (strpos($value, '/') !== 0) {
            $value = 'uploads/' . $value;
        }
        
        return base_url($value);
    }
}

/**
 * Trunca texto para exibição
 * @param string $text Texto original
 * @param int $limit Limite de caracteres
 * @param string $suffix Sufixo para texto truncado
 * @return string Texto truncado
 */
if (!function_exists('truncate_text')) {
    function truncate_text(string $text, int $limit = 100, string $suffix = '...'): string
    {
        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }
        
        return mb_substr($text, 0, $limit, 'UTF-8') . $suffix;
    }
}

/**
 * Converte hex para rgba
 * @param string $hex Cor hexadecimal
 * @param float $alpha Valor alpha (0-1)
 * @param string $fallback Cor de fallback
 * @return string Valor rgba
 */
if (!function_exists('hex_to_rgba')) {
    function hex_to_rgba(string $hex, float $alpha = 1.0, string $fallback = '#000000'): string
    {
        $hex = normalize_color_hex($hex);
        
        if ($hex === '#000000' && $hex !== $fallback) {
            $hex = normalize_color_hex($fallback);
        }
        
        $hex = ltrim($hex, '#');
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "rgba($r, $g, $b, $alpha)";
    }
}

// ============================================================================
// 🎨 TEMAS E CORES
// ============================================================================

/**
 * Obtém cor primária do tema admin
 * @param array|null $company Dados da empresa
 * @return string Cor hexadecimal
 */
if (!function_exists('admin_theme_primary_color')) {
    function admin_theme_primary_color(?array $company = null, string $default = '#4F46E5'): string
    {
        if ($company) {
            // Verificar diferentes campos possíveis para a cor principal
            $color = $company['primary_color'] ?? 
                    $company['menu_header_bg_color'] ?? 
                    $company['menu_logo_bg_color'] ?? 
                    $company['brand_color'] ?? 
                    null;
            
            if (!empty($color)) {
                return normalize_color_hex($color);
            }
        }
        
        return $default;
    }
}

/**
 * Gera gradiente do tema admin
 * @param array|null $company Dados da empresa
 * @return string CSS gradient
 */
if (!function_exists('admin_theme_gradient')) {
    function admin_theme_gradient(?array $company = null): string
    {
        $primary = admin_theme_primary_color($company);
        
        // Gera uma versão mais clara do primary para o gradiente
        $r = hexdec(substr(ltrim($primary, '#'), 0, 2));
        $g = hexdec(substr(ltrim($primary, '#'), 2, 2));
        $b = hexdec(substr(ltrim($primary, '#'), 4, 2));
        
        // Adiciona 30 em cada canal (máximo 255)
        $r2 = min(255, $r + 30);
        $g2 = min(255, $g + 30);
        $b2 = min(255, $b + 30);
        
        $secondary = sprintf('#%02X%02X%02X', $r2, $g2, $b2);
        
        return "linear-gradient(135deg, $primary 0%, $secondary 100%)";
    }
}

// ============================================================================
// 🔐 CSRF E SEGURANÇA
// ============================================================================

/**
 * Gera campo CSRF se a função existir
 * @return string HTML do campo CSRF ou string vazia
 */
if (!function_exists('csrf_field_safe')) {
    function csrf_field_safe(): string
    {
        if (function_exists('csrf_field')) {
            return csrf_field();
        }
        
        if (function_exists('csrf_token')) {
            return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
        }
        
        return '';
    }
}

// ============================================================================
// 📱 RESPONSIVIDADE E DEVICE
// ============================================================================

/**
 * Detecta se é dispositivo móvel
 * @return bool True se for mobile
 */
if (!function_exists('is_mobile')) {
    function is_mobile(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return preg_match('/Mobile|Android|iPhone|iPad|BlackBerry|Windows Phone/i', $userAgent);
    }
}

/**
 * Gera classes responsivas baseadas no contexto
 * @param string $mobile Classes para mobile
 * @param string $desktop Classes para desktop  
 * @return string Classes CSS combinadas
 */
if (!function_exists('responsive_classes')) {
    function responsive_classes(string $mobile, string $desktop): string
    {
        return "$mobile md:$desktop";
    }
}