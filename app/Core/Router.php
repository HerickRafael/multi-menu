<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\DatabaseConnectionException;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class Router
{
    /** @var array<string, array<string, callable|string>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function get(string $pattern, callable|string $handler): void
    {
        $this->routes['GET'][$pattern] = $handler;
    }

    public function post(string $pattern, callable|string $handler): void
    {
        $this->routes['POST'][$pattern] = $handler;
    }

    /**
     * @return array<string, string>|false
     */
    private function match(string $pattern, string $uri): array|false
    {
        $regex = preg_replace('#\{([^}/]+)\}#', '(?P<$1>[^/]+)', $pattern) ?? '';
        $regex = '#^' . rtrim($regex, '/') . '/?$#';

        if (preg_match($regex, $uri, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return false;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri = rtrim($uri, '/') ?: '/';
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $pattern => $handler) {
            $params = $this->match($pattern, $uri);

            if ($params === false) {
                continue;
            }

            try {
                $this->invokeHandler($handler, $params);
            } catch (\Throwable $throwable) {
                $this->logger->error('Erro ao despachar rota', [
                    'exception' => $throwable,
                    'pattern' => $pattern,
                    'uri' => $uri,
                ]);

                if ($throwable instanceof DatabaseConnectionException) {
                    http_response_code(503);
                    echo 'Serviço temporariamente indisponível.';
                } else {
                    http_response_code(500);
                    echo 'Erro interno';
                }
            }

            return;
        }

        http_response_code(404);
        echo '404';
    }

    /**
     * @param callable|string $handler
     * @param array<string, string> $params
     */
    private function invokeHandler(callable|string $handler, array $params): void
    {
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $fqcn = $this->resolveControllerClass($class);

            if (!class_exists($fqcn)) {
                throw new RuntimeException(sprintf('Controller %s não encontrado.', $fqcn));
            }

            $controller = new $fqcn();

            if (!method_exists($controller, $method)) {
                throw new RuntimeException(sprintf('Método %s::%s não encontrado.', $fqcn, $method));
            }

            $controller->$method($params);

            return;
        }

        if (is_callable($handler)) {
            call_user_func($handler, $params);

            return;
        }

        throw new RuntimeException('Handler de rota inválido.');
    }

    private function resolveControllerClass(string $name): string
    {
        $name = trim($name, '\\');

        if (!str_contains($name, '\\')) {
            $name = 'App\\Http\\Controllers\\' . $name;
        }

        return $name;
    }
}
