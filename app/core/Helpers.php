<?php
function config($key = null) {
  static $cfg = null;
  if (!$cfg) $cfg = require __DIR__ . '/../config/app.php';
  return $key ? ($cfg[$key] ?? null) : $cfg;
}

function base_url(string $path = ''): string {
  $b = config('base_url');
  if (!$b) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $b      = $scheme . '://' . $host . ($dir ? $dir : '');
  }
  $b = rtrim($b, '/');
  $p = ltrim($path, '/');
  return $p ? "$b/$p" : $b;
}

function e($s) {
  return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

if (!function_exists('normalize_color_hex')) {
  /**
   * Normaliza um valor de cor hexadecimal para o formato completo #RRGGBB.
   *
   * @param string|null $value    Valor informado (com ou sem #, 3 ou 6 dígitos).
   * @param string      $default  Cor padrão caso $value seja vazio ou inválido.
   */
  function normalize_color_hex(?string $value, string $default = '#000000'): string {
    $value = trim((string)($value ?? ''));
    $default = strtoupper($default);
    if ($value === '') {
      return $default;
    }

    if ($value[0] !== '#') {
      $value = '#' . $value;
    }

    if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
      return $default;
    }

    if (strlen($value) === 4) {
      $value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
    }

    return strtoupper($value);
  }
}

if (!function_exists('hex_to_rgba')) {
  /**
   * Converte uma cor hexadecimal para rgba(R,G,B,A).
   */
  function hex_to_rgba(?string $hex, float $alpha = 1.0, string $fallback = '#000000'): string {
    $hex = normalize_color_hex($hex, $fallback);
    $alpha = max(0, min(1, $alpha));
    $r = hexdec(substr($hex, 1, 2));
    $g = hexdec(substr($hex, 3, 2));
    $b = hexdec(substr($hex, 5, 2));
    return sprintf('rgba(%d, %d, %d, %.3f)', $r, $g, $b, $alpha);
  }
}

if (!function_exists('admin_theme_primary_color')) {
  /**
   * Retorna a cor principal do painel admin baseada na configuração da empresa.
   */
  function admin_theme_primary_color(?array $company, string $default = '#5B21B6'): string {
    $color = $company['menu_header_bg_color'] ?? ($company['menu_logo_bg_color'] ?? $default);
    return normalize_color_hex($color, $default);
  }
}

if (!function_exists('admin_theme_gradient')) {
  /**
   * Cria um gradiente suave usando a cor principal da empresa.
   */
  function admin_theme_gradient(?array $company, float $opacity = 0.65, string $direction = '135deg'): string {
    $base = admin_theme_primary_color($company);
    $soft = hex_to_rgba($base, $opacity, $base);
    return sprintf('linear-gradient(%s, %s 0%%, %s 100%%)', $direction, $base, $soft);
  }
}

/**
 * Verifica se o produto ainda é considerado "Novidade"
 * conforme a config 'novidades_days'.
 *
 * - Se novidades_days <= 0 → nunca mostra
 * - Se created_at for vazio → nunca mostra
 */
function is_new_product(array $p): bool {
  $dias = (int)(config('novidades_days') ?? 14);
  if ($dias <= 0) return false;
  if (empty($p['created_at'])) return false;
  return strtotime($p['created_at']) >= strtotime("-{$dias} days");
}

if (!function_exists('normalize_whatsapp_e164')) {
  /**
   * Normaliza número de WhatsApp para o formato E.164.
   * Por padrão assume o código do Brasil (55).
   * Exemplos:
   *   "(11) 90000-0000"  -> "5511900000000"
   *   "009119000000000"  -> "9119000000000"  (zeros à esquerda são removidos; se tiver 10-11 dígitos, prefixa 55)
   */
  function normalize_whatsapp_e164(string $raw, string $defaultCountry = '55'): string {
    // Mantém só dígitos
    $digits = preg_replace('/\D+/', '', $raw ?? '');
    if ($digits === '') return '';

    // Remove zeros à esquerda (DDD/nacionais às vezes vêm com 0 inicial)
    $digits = ltrim($digits, '0');

    // Se já está no padrão BR (55 + 10 ou 11 dígitos), retorna
    if (preg_match('/^55\d{10,11}$/', $digits)) {
      return $digits;
    }

    // 10–11 dígitos → assume país padrão (BR = 55)
    if (strlen($digits) >= 10 && strlen($digits) <= 11) {
      $digits = $defaultCountry . $digits;
    } elseif (strlen($digits) < 12) {
      // Menos que 12 (e não 10–11) ainda assim prefixa país (cobre casos sem DDI)
      $digits = $defaultCountry . $digits;
    }

    // E.164 permite no máximo 15 dígitos
    if (strlen($digits) > 15) {
      $digits = substr($digits, 0, 15);
    }

    return $digits;
  }
}
