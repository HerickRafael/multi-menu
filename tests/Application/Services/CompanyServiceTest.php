<?php

declare(strict_types=1);

namespace Tests\Application\Services;

use App\Application\Services\CompanyService;
use App\Support\Config;
use PHPUnit\Framework\TestCase;

final class CompanyServiceTest extends TestCase
{
    public function testAllReturnsEmptyArrayWhenDatabaseIsUnavailable(): void
    {
        $service = new CompanyService();
        $previousConfig = Config::all();

        $unreachableConfig = $previousConfig;
        $unreachableConfig['database'] = [
            'default' => 'mysql',
            'connections' => [
                'mysql' => [
                    'driver' => 'mysql',
                    'host' => '127.0.0.1',
                    'port' => 65000,
                    'database' => 'missing',
                    'username' => 'missing',
                    'password' => 'missing',
                    'charset' => 'utf8mb4',
                ],
            ],
        ];

        Config::replace($unreachableConfig);

        try {
            $this->assertSame([], $service->all());
        } finally {
            Config::replace($previousConfig);
        }
    }
}
