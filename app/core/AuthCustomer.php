<?php

declare(strict_types=1);
class AuthCustomer
{
    public static function start(): void
    {
        if (class_exists('Auth') && method_exists('Auth', 'start')) {
            Auth::start();

            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            $name = function_exists('config') ? (config('session_name') ?? 'mm_session') : 'mm_session';

            if ($name && session_name() !== $name) {
                session_name($name);
            }
            session_start();
        }
    }

    public static function current(?string $slug = null): ?array
    {
        self::start();
        $c = $_SESSION['customer'] ?? null;

        if (!$c) {
            return null;
        }

        if ($slug !== null && isset($c['company_slug']) && $c['company_slug'] !== $slug) {
            return null;
        }

        return $c;
    }

    public static function require(?string $slug = null): bool
    {
        return self::current($slug) !== null;
    }
}
