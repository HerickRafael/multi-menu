<?php
/**
 * XSS Protection Helper Functions
 * 
 * Funções auxiliares globais para proteção contra XSS
 */

use App\Middleware\XssProtection;

/**
 * Função global helper para XSS escape
 * 
 * @param mixed $value Valor a escapar
 * @return string Valor escapado
 */
if (!function_exists('e')) {
    function e($value): string
    {
        return XssProtection::escape($value);
    }
}

/**
 * Função global helper para sanitização
 * 
 * @param string $input Input a sanitizar
 * @return string Input sanitizado
 */
if (!function_exists('sanitize')) {
    function sanitize(string $input): string
    {
        return XssProtection::sanitize($input);
    }
}
