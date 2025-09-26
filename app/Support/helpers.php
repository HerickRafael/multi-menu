<?php

declare(strict_types=1);

use App\Support\Config;
use App\Support\Env;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $base = (string) config('app.url');

        if ($base !== '') {
            $hostHeader = $_SERVER['HTTP_HOST'] ?? '';

            if ($hostHeader !== '') {
                $parsed = parse_url($base);

                if ($parsed !== false && isset($parsed['host'])) {
                    $headerHost = $hostHeader;
                    $headerPort = null;

                    if (str_contains($hostHeader, ':')) {
                        [$headerHost, $headerPort] = explode(':', $hostHeader, 2);
                    }

                    if ($headerPort !== null
                        && !isset($parsed['port'])
                        && strcasecmp($parsed['host'], $headerHost) === 0) {
                        $scheme = $parsed['scheme'] ?? 'http';
                        $user = $parsed['user'] ?? '';
                        $pass = $parsed['pass'] ?? '';
                        $auth = $user !== '' ? $user . ($pass !== '' ? ':' . $pass : '') . '@' : '';
                        $basePath = $parsed['path'] ?? '';

                        $base = sprintf('%s://%s%s%s', $scheme, $auth, $hostHeader, $basePath);
                    }
                }
            }
        }

        if ($base === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $dir    = rtrim((string) dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
            $base   = $scheme . '://' . $host . ($dir ? $dir : '');
        }

        $base = rtrim($base, '/');
        $path = ltrim($path, '/');

        return $path ? "$base/$path" : $base;
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('normalize_color_hex')) {
    function normalize_color_hex(?string $value, string $default = '#000000'): string
    {
        $value = trim((string) ($value ?? ''));
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
            $value = sprintf('#%1$s%1$s%2$s%2$s%3$s%3$s', $value[1], $value[2], $value[3]);
        }

        return strtoupper($value);
    }
}

if (!function_exists('hex_to_rgba')) {
    function hex_to_rgba(?string $hex, float $alpha = 1.0, string $fallback = '#000000'): string
    {
        $hex = normalize_color_hex($hex, $fallback);
        $alpha = max(0, min(1, $alpha));
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));

        return sprintf('rgba(%d, %d, %d, %.3f)', $r, $g, $b, $alpha);
    }
}

if (!function_exists('admin_theme_primary_color')) {
    function admin_theme_primary_color(?array $company, string $default = '#5B21B6'): string
    {
        $color = $company['menu_header_bg_color'] ?? ($company['menu_logo_bg_color'] ?? $default);

        return normalize_color_hex($color, $default);
    }
}

if (!function_exists('admin_theme_gradient')) {
    function admin_theme_gradient(?array $company, float $opacity = 0.65, string $direction = '135deg'): string
    {
        $base = admin_theme_primary_color($company);
        $soft = hex_to_rgba($base, $opacity, $base);

        return sprintf('linear-gradient(%s, %s 0%%, %s 100%%)', $direction, $base, $soft);
    }
}

if (!function_exists('is_new_product')) {
    function is_new_product(array $product): bool
    {
        $days = (int) (config('app.novidades_days') ?? 14);

        if ($days <= 0) {
            return false;
        }

        if (empty($product['created_at'])) {
            return false;
        }

        return strtotime($product['created_at']) >= strtotime("-{$days} days");
    }
}

if (!function_exists('normalize_whatsapp_e164')) {
    function normalize_whatsapp_e164(string $raw, string $defaultCountry = '55'): string
    {
        $digits = preg_replace('/\D+/', '', $raw);

        if ($digits === null || $digits === '') {
            return '';
        }

        $digits = ltrim($digits, '0');

        if (preg_match('/^55\d{10,11}$/', $digits)) {
            return $digits;
        }

        if (strlen($digits) >= 10 && strlen($digits) <= 11) {
            $digits = $defaultCountry . $digits;
        } elseif (strlen($digits) < 12) {
            $digits = $defaultCountry . $digits;
        }

        if (strlen($digits) > 15) {
            $digits = substr($digits, 0, 15);
        }

        return $digits;
    }
}
