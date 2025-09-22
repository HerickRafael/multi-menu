<?php
function config($key = null) {
  static $cfg = null;
  if (!$cfg) $cfg = require __DIR__ . '/../config/app.php';
  return $key ? ($cfg[$key] ?? null) : $cfg;
}

/**
 * Gera URL absoluta.
 * - Usa config('base_url') se preenchido (ex.: https://meudominio.com/app)
 * - Caso contrário, detecta scheme/host atrás de proxy/CDN (Cloudflare, etc.)
 * - Respeita APP_WEBROOT ('' na raiz ou '/subpasta' quando aplicável)
 */
function base_url(string $path = ''): string {
  // 1) Se o app.php define base_url, priorize
  $configured = config('base_url');
  $base = is_string($configured) && trim($configured) !== ''
    ? rtrim($configured, '/')
    : '';

  if ($base === '') {
    // 2) Detecta HTTPS atrás de proxies
    $https = false;

    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
      // pode vir "https, http" (lista). Pegue o primeiro.
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
      // RFC 7239
      $forwardedEntries = preg_split('/,\s*/', (string)$_SERVER['HTTP_FORWARDED']);
      foreach ($forwardedEntries as $entry) {
        $parts = explode(';', $entry);
        foreach ($parts as $part) {
          [$k, $v] = array_map('trim', array_pad(explode('=', $part, 2), 2, ''));
          if (strtolower($k) === 'proto' && strtolower(trim($v, '"')) === 'https') {
            $https = true;
            break 2;
          }
        }
      }
    } else {
      $https = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    }

    $scheme = $https ? 'https' : 'http';

    // 3) Host (prioriza cabeçalhos de proxy)
    $host = '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
      $host = trim(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_HOST'])[0]);
    }
    if ($host === '' && !empty($_SERVER['HTTP_FORWARDED'])) {
      $forwardedEntries = preg_split('/,\s*/', (string)$_SERVER['HTTP_FORWARDED']);
      foreach ($forwardedEntries as $entry) {
        $parts = explode(';', $entry);
        foreach ($parts as $part) {
          [$k, $v] = array_map('trim', array_pad(explode('=', $part, 2), 2, ''));
          if (strtolower($k) === 'host') {
            $v = trim($v, '"');
            if ($v !== '') { $host = $v; break 2; }
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

    // 4) Base path a partir do APP_WEBROOT
    $root = '';
    if (defined('APP_WEBROOT')) {
      $root = rtrim((string)APP_WEBROOT, '/');
      if ($root === '/') $root = '';
    } else {
      // fallback seguro (não deve acontecer se você definiu no index.php)
      $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
      $root = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
      if ($root === '/' || $root === '.') $root = '';
      // remove "/public" terminal se existir
      if ($root !== '' && substr($root, -7) === '/public') {
        $root = substr($root, 0, -7);
      }
    }

    $base = $scheme . '://' . $host . ($root !== '' ? $root : '');
  }

  $base = rtrim($base, '/');
  $path = ltrim($path, '/');
  return $path === '' ? $base : $base . '/' . $path;
}

/**
 * Caminho web relativo (prefixado pelo APP_WEBROOT).
 * Ex.: webroot_path('uploads/img.png') -> '/app/uploads/img.png' (se APP_WEBROOT='/app')
 */
function webroot_path(string $path = ''): string {
  $root = defined('APP_WEBROOT') ? rtrim((string)APP_WEBROOT, '/') : '';
  if ($root === '/') $root = '';
  $path = ltrim($path, '/');
  $prefix = $root !== '' ? $root : '';
  if ($path === '') {
    return $prefix !== '' ? $prefix : '/';
  }
  return ($prefix !== '' ? $prefix : '') . '/' . $path;
}

/**
 * Monta URL pública de uma imagem enviada (fallback seguro).
 */
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

/** Escape HTML seguro */
function e($s) {
  return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Verifica se o produto é "Novidade" conforme config('novidades_days').
 * - novidades_days <= 0 => nunca mostra
 * - created_at vazio     => nunca mostra
 */
function is_new_product(array $p): bool {
  $dias = (int)(config('novidades_days') ?? 14);
  if ($dias <= 0) return false;
  if (empty($p['created_at'])) return false;
  return strtotime($p['created_at']) >= strtotime("-{$dias} days");
}

/**
 * Normaliza número de WhatsApp para E.164 (default Brasil 55).
 * Exemplos:
 *   "(11) 90000-0000"  -> "5511900000000"
 *   "009119000000000"  -> "9119000000000" (remove zeros à esquerda; com 10-11 dígitos prefixa 55)
 */
if (!function_exists('normalize_whatsapp_e164')) {
  function normalize_whatsapp_e164(string $raw, string $defaultCountry = '55'): string {
    $digits = preg_replace('/\D+/', '', $raw ?? '');
    if ($digits === '') return '';

    // remove zeros à esquerda
    $digits = ltrim($digits, '0');

    // já no padrão BR
    if (preg_match('/^55\d{10,11}$/', $digits)) {
      return $digits;
    }

    // 10–11 dígitos -> assume país padrão
    if (strlen($digits) >= 10 && strlen($digits) <= 11) {
      $digits = $defaultCountry . $digits;
    } elseif (strlen($digits) < 12) {
      // casos sem DDI/DDD completos: ainda prefixa país
      $digits = $defaultCountry . $digits;
    }

    // limite E.164 = 15 dígitos
    if (strlen($digits) > 15) {
      $digits = substr($digits, 0, 15);
    }

    return $digits;
  }
}
