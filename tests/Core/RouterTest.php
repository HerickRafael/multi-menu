<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Exceptions\DatabaseConnectionException;
use App\Core\Router;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class RouterTest extends TestCase
{
    public function testResolvesControllerStrings(): void
    {
        $logger = new NullLogger();
        $router = new Router($logger);

        $router->get('/foo/{id}', DummyController::class . '@show');

        ob_start();
        $router->dispatch('GET', '/foo/10');
        $output = trim((string) ob_get_clean());

        self::assertSame('10', $output);
    }

    public function testGracefullyHandlesDatabaseConnectionErrors(): void
    {
        $logger = new NullLogger();
        $router = new Router($logger);

        $router->get('/broken', static function (): void {
            throw new DatabaseConnectionException('Falha de conexão simulada');
        });

        http_response_code(200);
        ob_start();
        $router->dispatch('GET', '/broken');
        $output = trim((string) ob_get_clean());

        self::assertSame(503, http_response_code());
        self::assertSame('Serviço temporariamente indisponível.', $output);
    }
}

final class DummyController
{
    public function show(array $params): void
    {
        echo $params['id'] ?? '';
    }
}
