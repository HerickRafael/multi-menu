<?php
function config($key = null) {
  static $cfg = null;
  if (!$cfg) $cfg = require __DIR__ . '/../config/app.php';
  return $key ? ($cfg[$key] ?? null) : $cfg;
}

function base_url(string $path = ''): string {
  $b = rtrim(config('base_url'), '/');
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
