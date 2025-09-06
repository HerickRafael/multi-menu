<?php
if (!function_exists('normalize_whatsapp_e164')) {
    /**
     * Normaliza para E.164; default Brasil (55).
     */
    function normalize_whatsapp_e164(string $raw, string $defaultCountry = '55'): string {
        $digits = preg_replace('/\D+/', '', $raw ?? '');
        if ($digits === '') return '';
        if (preg_match('/^55\d{10,11}$/', $digits)) return $digits;
        $digits = ltrim($digits, '0');
        if (strlen($digits) >= 10 && strlen($digits) <= 11) return $defaultCountry.$digits;
        if (strlen($digits) < 12) return $defaultCountry.$digits;
        return $digits;
    }
}
