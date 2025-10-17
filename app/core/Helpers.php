<?php

declare(strict_types=1);

// Incluir helpers centralizados
require_once __DIR__ . '/CommonHelpers.php';
require_once __DIR__ . '/../helpers/lazy_loading_helper.php';

function config($key = null)
{
    static $cfg = null;

    if (!$cfg) {
        $cfg = require __DIR__ . '/../config/app.php';
    }

    return $key ? ($cfg[$key] ?? null) : $cfg;
}

function is_new_product(array $p): bool
{
    $date = $p['created_at'] ?? $p['date'] ?? null;

    if (!$date) {
        return false;
    }

    $created = strtotime($date);

    if ($created === false) {
        return false;
    }

    return $created > (time() - 7 * 24 * 60 * 60);
}

if (!function_exists('normalize_whatsapp_e164')) {
    /**
     * Normaliza um número de WhatsApp para o formato E.164.
     *
     * @param string $raw
     * @param string $defaultCountry
     * @return string
     */
    function normalize_whatsapp_e164(string $raw, string $defaultCountry = '55'): string
    {
        $digits = preg_replace('/\D+/', '', $raw);

        if (strlen($digits) <= 11 && !str_starts_with($digits, $defaultCountry)) {
            $digits = $defaultCountry . $digits;
        }

        return $digits;
    }
}