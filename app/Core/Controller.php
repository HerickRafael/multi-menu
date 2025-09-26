<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

abstract class Controller
{
    protected function view(string $path, array $data = []): void
    {
        $file = dirname(__DIR__) . '/Views/' . $path . '.php';

        if (!file_exists($file)) {
            throw new RuntimeException(sprintf('View não encontrada: %s', $path));
        }

        extract($data, EXTR_SKIP);
        include $file;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR);
    }

    protected function db(): PDO
    {
        return Database::connection();
    }

    /**
     * Garante que o contexto de empresa ativo em sessão siga o acesso atual.
     *
     * Mantém sincronizado o ID e, se informado, o slug da empresa ativa.
     */
    protected function ensureCompanyContext(int $companyId, ?string $slug = null): void
    {
        $currentId = Auth::activeCompanyId();
        $currentSlug = Auth::activeCompanySlug();

        $shouldUpdateId = $currentId !== $companyId;
        $shouldUpdateSlug = $slug !== null && $slug !== '' && $currentSlug !== $slug;

        if ($shouldUpdateId || $shouldUpdateSlug) {
            Auth::setActiveCompany($companyId, $slug);
        }
    }

    /**
     * Retorna o slug atualmente ativo no contexto da empresa logada.
     */
    protected function currentCompanySlug(): ?string
    {
        return Auth::activeCompanySlug();
    }
}
