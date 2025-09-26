<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class Config
{
    /** @var array<string, mixed>|null */
    private static ?array $items = null;

    private static function ensureLoaded(): void
    {
        if (self::$items !== null) {
            return;
        }

        $defaultPath = dirname(__DIR__, 2) . '/config';

        if (is_dir($defaultPath)) {
            self::load($defaultPath);
        }
    }

    public static function load(string $configPath): void
    {
        $items = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($configPath, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace($configPath, '', $file->getPathname());
            $relative = trim($relative, DIRECTORY_SEPARATOR);
            $relative = str_replace(['\\', '/'], '.', $relative);
            $key = preg_replace('/\.php$/', '', $relative);

            if ($key === null || $key === '') {
                continue;
            }

            $items[$key] = require $file->getPathname();
        }

        self::$items = $items;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        self::ensureLoaded();

        return self::$items ?? [];
    }

    /**
     * @param array<string, mixed>|null $items
     */
    public static function replace(?array $items): void
    {
        self::$items = $items;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureLoaded();

        if (self::$items === null) {
            throw new InvalidArgumentException('Configurações não carregadas. Chame Config::load().');
        }

        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
