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
}
