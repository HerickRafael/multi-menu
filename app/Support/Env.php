<?php

declare(strict_types=1);

namespace App\Support;

final class Env
{
    /** @var array<string, mixed> */
    private static array $cache = [];

    public static function load(string $basePath): void
    {
        if (!class_exists(\Dotenv\Dotenv::class)) {
            return;
        }

        $dotEnv = \Dotenv\Dotenv::createImmutable($basePath);
        $dotEnv->safeLoad();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        if (array_key_exists($key, $_ENV)) {
            return self::$cache[$key] = self::cast($_ENV[$key]);
        }

        if (array_key_exists($key, $_SERVER)) {
            return self::$cache[$key] = self::cast($_SERVER[$key]);
        }

        return $default;
    }

    private static function cast(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $normalized = strtolower($value);

        return match ($normalized) {
            'true', '(true)', 'on' => true,
            'false', '(false)', 'off' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}
