<?php
function config($key = null) {
  static $cfg = null;
  if (!$cfg) $cfg = require __DIR__ . '/../config/app.php';
  return $key ? ($cfg[$key] ?? null) : $cfg;
}

function base_url(string $path = ''): string {
  $configured = config('base_url');
  $base = is_string($configured) && trim($configured) !== ''
    ? rtrim($configured, '/')
    : '';

  if ($base === '') {
    // Detect HTTPS even quando a aplicação está atrás de proxies/CDNs.
    $https = false;
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
      $https = strtolower(trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_PROTO'])[0])) === 'https';
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_SCHEME'])) {
      $https = strtolower(trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_SCHEME'])[0])) === 'https';
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_SSL'])) {
      $https = strtolower((string)$_SERVER['HTTP_X_FORWARDED_SSL']) === 'on';
    } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS'])) {
      $https = strtolower((string)$_SERVER['HTTP_FRONT_END_HTTPS']) === 'on';
    } elseif (!empty($_SERVER['HTTP_CF_VISITOR'])) {
      $cfVisitor = json_decode((string)$_SERVER['HTTP_CF_VISITOR'], true);
      if (is_array($cfVisitor) && isset($cfVisitor['scheme'])) {
        $https = strtolower((string)$cfVisitor['scheme']) === 'https';
      }
    } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
      $forwardedEntries = preg_split('/,\s*/', (string)$_SERVER['HTTP_FORWARDED']);
      foreach ($forwardedEntries as $entry) {
        $parts = explode(';', $entry);
        foreach ($parts as $part) {
          [$key, $value] = array_map('trim', array_pad(explode('=', $part, 2), 2, ''));
          if (strtolower($key) === 'proto' && strtolower(trim($value, '"')) === 'https') {
            $https = true;
            break 2;
          }
        }
      }
    } else {
      $https = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    }

    $scheme = $https ? 'https' : 'http';

    // Host (prioriza valores enviados por proxies)
    $host = '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
      $host = trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_HOST'])[0]);
    }
    if ($host === '' && !empty($_SERVER['HTTP_FORWARDED'])) {
      $forwardedEntries = preg_split('/,\s*/', (string)$_SERVER['HTTP_FORWARDED']);
      foreach ($forwardedEntries as $entry) {
        $parts = explode(';', $entry);
        foreach ($parts as $part) {
          [$key, $value] = array_map('trim', array_pad(explode('=', $part, 2), 2, ''));
          if (strtolower($key) === 'host') {
            $value = trim($value, '"');
            if ($value !== '') {
              $host = $value;
              break 2;
            }
          }
        }
      }
    }
    if ($host === '' && !empty($_SERVER['HTTP_X_FORWARDED_SERVER'])) {
      $host = trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_SERVER'])[0]);
    }
    if ($host === '') {
      $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
    }
    if ($host === '' || strtolower($host) === 'localhost') {
      $host = 'devkkkk.shop';
    }

    // Base path configurável via constante (ex.: aplicação em subpasta)
    $root = '';
    if (defined('APP_WEBROOT')) {
      $root = rtrim((string)APP_WEBROOT, '/');
    } else {
      $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
      $root = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
      if ($root === '/') {
        $root = '';
      }
    }

    $base = $scheme . '://' . $host . ($root !== '' ? $root : '');
  }

  $base = rtrim($base, '/');
  $path = ltrim($path, '/');
  return $path === '' ? $base : $base . '/' . $path;
}

function webroot_path(string $path = ''): string {
  $root = defined('APP_WEBROOT') ? rtrim((string)APP_WEBROOT, '/') : '';
  if ($root === '/') {
    $root = '';
  }
  $path = ltrim($path, '/');
  $prefix = $root !== '' ? $root : '';
  if ($path === '') {
    return $prefix !== '' ? $prefix : '/';
  }
  return ($prefix !== '' ? $prefix : '') . '/' . $path;
}

function upload_image_url($value, string $fallback = 'logo-placeholder.png'): string {
  $filename = '';

  if (is_string($value) || is_numeric($value)) {
    $raw = trim((string)$value);
    if ($raw !== '') {
      $raw = str_replace('\\', '/', $raw);
      $raw = explode('?', $raw, 2)[0];
      $raw = explode('#', $raw, 2)[0];
      $filename = basename($raw);
    }
  }

  if ($filename === '' || $filename === '.' || $filename === '..') {
    $fallback = trim((string)$fallback);
    if ($fallback !== '') {
      $fallback = str_replace('\\', '/', $fallback);
      $fallback = explode('?', $fallback, 2)[0];
      $fallback = explode('#', $fallback, 2)[0];
      $filename = basename($fallback);
    }
  }

  if ($filename === '' || $filename === '.' || $filename === '..') {
    $filename = 'logo-placeholder.png';
  }

  return base_url('uploads/' . ltrim($filename, '/'));
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
