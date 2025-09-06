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

/**
 * Verifica se o produto ainda é considerado "Novidade"
 * conforme a config 'novidades_days'.
 *
 * - Se novidades_days <= 0 → nunca mostra
 * - Se created_at for vazio → nunca mostra
 */
function is_new_product(array $p): bool {
  $dias = (int)(config('novidades_days') ?? 14);
  if ($dias <= 0) return false;                 // desliga completamente
  if (empty($p['created_at'])) return false;    // sem data não dá pra marcar
  return strtotime($p['created_at']) >= strtotime("-{$dias} days");
}

if (!function_exists('normalize_whatsapp_e164')) {
  /**
   * Normaliza WhatsApp para E.164 (padrão BR 55).
   * Ex.: "(11) 90000-0000" → "5511900000000"
   */
  function normalize_whatsapp_e164(string $raw, string $defaultCountry='55'): string {
    $digits = preg_replace('/\D+/', '', $raw ?? '');
    if ($digits === '') return '';
    if (preg_match('/^55\d{10,11}$/', $digits)) return $digits;
    $digits = ltrim($digits, '0');
    if (strlen($digits) >= 10 && strlen($digits) <= 11) return $defaultCountry.$digits;
    if (strlen($digits) < 12) return $defaultCountry.$digits;
    return $digits;
  }
}

