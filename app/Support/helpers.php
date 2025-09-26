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
        $parseHostPort = static function (?string $value): array {
            $value = trim((string) ($value ?? ''));

            if ($value === '') {
                return ['', null];
            }

            $value = trim($value, " \t\n\r\0\x0B\"'");

            if ($value === '') {
                return ['', null];
            }

            $value = explode(',', $value, 2)[0];
            $value = trim($value);

            if ($value === '') {
                return ['', null];
            }

            if (strncmp($value, '//', 2) !== 0) {
                $value = '//' . ltrim($value, '/');
            }

            $parsed = parse_url($value);

            if ($parsed === false) {
                return ['', null];
            }

            $host = $parsed['host'] ?? '';

            if ($host === '' && isset($parsed['path'])) {
                $host = $parsed['path'];
            }

            $port = isset($parsed['port']) ? (int) $parsed['port'] : null;

            return [$host, $port];
        };

        $formatHost = static function (string $host): string {
            $host = trim($host);

            if ($host === '') {
                return '';
            }

            if (strncmp($host, '[', 1) === 0 && substr($host, -1) === ']') {
                return $host;
            }

            if (str_contains($host, ':')) {
                return '[' . trim($host, '[]') . ']';
            }

            return $host;
        };

        $formatPort = static function (?int $port, string $scheme): string {
            if ($port === null) {
                return '';
            }

            $normalizedScheme = strtolower($scheme);

            if (($normalizedScheme === 'http' && $port === 80)
                || ($normalizedScheme === 'https' && $port === 443)) {
                return '';
            }

            return ':' . $port;
        };

        $detectedScheme = 'http';
        $detectedHost = '';
        $detectedPort = null;

        if (!empty($_SERVER['HTTP_FORWARDED'])) {
            $forwardedValues = explode(',', (string) $_SERVER['HTTP_FORWARDED']);
            $firstForwarded = trim($forwardedValues[0]);

            if ($firstForwarded !== '') {
                $pairs = preg_split('/;\s*/', $firstForwarded);

                foreach ($pairs as $pair) {
                    if ($pair === null || $pair === '') {
                        continue;
                    }

                    $kv = explode('=', $pair, 2);

                    if (count($kv) !== 2) {
                        continue;
                    }

                    $key = strtolower(trim($kv[0]));
                    $value = trim($kv[1], " \t\n\r\0\x0B\"'");

                    if ($value === '') {
                        continue;
                    }

                    if ($key === 'proto') {
                        $detectedScheme = strtolower($value);
                    } elseif ($key === 'host') {
                        [$forwardHost, $forwardPort] = $parseHostPort($value);

                        if ($forwardHost !== '') {
                            $detectedHost = $forwardHost;
                        }

                        if ($forwardPort !== null) {
                            $detectedPort = $forwardPort;
                        }
                    }
                }
            }
        }

        if ($detectedHost === '' && !empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            [$candidateHost, $candidatePort] = $parseHostPort((string) $_SERVER['HTTP_X_FORWARDED_HOST']);

            if ($candidateHost !== '') {
                $detectedHost = $candidateHost;

                if ($detectedPort === null && $candidatePort !== null) {
                    $detectedPort = $candidatePort;
                }
            }
        }

        if ($detectedHost === '' && !empty($_SERVER['HTTP_HOST'])) {
            [$candidateHost, $candidatePort] = $parseHostPort((string) $_SERVER['HTTP_HOST']);

            if ($candidateHost !== '') {
                $detectedHost = $candidateHost;

                if ($detectedPort === null && $candidatePort !== null) {
                    $detectedPort = $candidatePort;
                }
            }
        }

        if ($detectedHost === '' && !empty($_SERVER['SERVER_NAME'])) {
            $detectedHost = (string) $_SERVER['SERVER_NAME'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $candidate = trim((string) $_SERVER['HTTP_X_FORWARDED_PROTO']);

            if ($candidate !== '') {
                $detectedScheme = strtolower(explode(',', $candidate, 2)[0]);
            }
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_SCHEME'])) {
            $candidate = trim((string) $_SERVER['HTTP_X_FORWARDED_SCHEME']);

            if ($candidate !== '') {
                $detectedScheme = strtolower(explode(',', $candidate, 2)[0]);
            }
        } elseif (!empty($_SERVER['REQUEST_SCHEME'])) {
            $detectedScheme = strtolower((string) $_SERVER['REQUEST_SCHEME']);
        } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $detectedScheme = 'https';
        }

        if ($detectedPort === null && !empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
            $detectedPort = (int) $_SERVER['HTTP_X_FORWARDED_PORT'];
        }

        if ($detectedPort === null && !empty($_SERVER['SERVER_PORT'])) {
            $detectedPort = (int) $_SERVER['SERVER_PORT'];
        }

        $configUrl = (string) config('app.url');
        $parsedConfig = $configUrl !== '' ? parse_url($configUrl) : false;

        $scheme = $detectedScheme ?: 'http';
        $host = $detectedHost;
        $port = $detectedPort;
        $auth = '';
        $basePath = '';

        if ($parsedConfig !== false) {
            if (!empty($parsedConfig['scheme'])) {
                $scheme = $parsedConfig['scheme'];
            }

            if (!empty($parsedConfig['host'])) {
                $host = $parsedConfig['host'];
            }

            if (isset($parsedConfig['port'])) {
                $port = (int) $parsedConfig['port'];
            } elseif (!empty($parsedConfig['host'])
                && $detectedHost !== ''
                && strcasecmp($parsedConfig['host'], $detectedHost) === 0
                && $detectedPort !== null) {
                $port = $detectedPort;
            }

            if (!empty($parsedConfig['user'])) {
                $auth = $parsedConfig['user'];

                if (!empty($parsedConfig['pass'])) {
                    $auth .= ':' . $parsedConfig['pass'];
                }

                $auth .= '@';
            }

            if (isset($parsedConfig['path'])) {
                $basePath = (string) $parsedConfig['path'];
            }
        } else {
            if ($configUrl !== '') {
                $basePath = $configUrl;
            }
        }

        if ($host === '') {
            $host = $detectedHost !== '' ? $detectedHost : 'localhost';

            if ($port === null && $detectedPort !== null) {
                $port = $detectedPort;
            }
        }

        if ($basePath === '') {
            $dir = rtrim((string) dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

            if ($dir === '.') {
                $dir = '';
            }

            if ($configUrl === '' && $dir !== '') {
                $basePath = $dir;
            }
        }

        $basePath = trim($basePath);

        if ($basePath !== '') {
            $basePath = '/' . ltrim($basePath, '/');
            $basePath = rtrim($basePath, '/');
        }

        $scheme = $scheme !== '' ? strtolower($scheme) : 'http';

        $formattedHost = $formatHost($host);

        if ($formattedHost === '') {
            $formattedHost = 'localhost';
        }

        $portSegment = $formatPort($port, $scheme);

        $base = sprintf('%s://%s%s%s', $scheme, $auth, $formattedHost, $portSegment);

        if ($basePath !== '') {
            $base .= $basePath;
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
