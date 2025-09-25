<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function start(): void
    {
        $timezone = (string) config('app.timezone', 'America/Sao_Paulo');
        date_default_timezone_set($timezone);

        $sessionName = (string) config('app.session_name', 'mm_session');

        if ($sessionName !== '') {
            session_name($sessionName);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function login(array $user): void
    {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'role' => $user['role'],
            'company_id' => $user['company_id'] ?? null,
            'name' => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
        ];
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function checkAdmin(): bool
    {
        $user = self::user();

        return $user !== null && in_array($user['role'], ['root', 'owner', 'staff'], true);
    }

    public static function requireAdmin(): void
    {
        if (!self::checkAdmin()) {
            header('Location: ' . base_url('admin/login'));
            exit;
        }
    }

    public static function companyId(): ?int
    {
        $user = self::user();

        return $user['company_id'] ?? null;
    }

    public static function setActiveCompany(int $companyId, ?string $slug = null): void
    {
        $_SESSION['active_company_id'] = $companyId;

        if ($slug !== null) {
            $_SESSION['active_company_slug'] = $slug;
        }
    }

    public static function activeCompanyId(): ?int
    {
        if (isset($_SESSION['active_company_id'])) {
            return (int) $_SESSION['active_company_id'];
        }

        return self::companyId();
    }

    public static function activeCompanySlug(): ?string
    {
        if (!empty($_SESSION['active_company_slug'])) {
            return (string) $_SESSION['active_company_slug'];
        }

        return null;
    }

    public static function clearActiveCompany(): void
    {
        unset($_SESSION['active_company_id'], $_SESSION['active_company_slug']);
    }
}
