<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Config;

use function base_url;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class BaseUrlHelperTest extends TestCase
{
    private array $originalServer = [];
    private array $originalEnv = [];
    /** @var array<string, mixed> */
    private array $originalConfig = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalServer = $_SERVER;
        $this->originalEnv = $_ENV;
        $this->originalConfig = $this->getConfigItems();
    }

    protected function tearDown(): void
    {
        $this->setConfigItems($this->originalConfig);
        $_SERVER = $this->originalServer;
        $_ENV = $this->originalEnv;

        parent::tearDown();
    }

    public function testKeepsPortFromHostHeader(): void
    {
        $_SERVER = [
            'HTTP_HOST' => 'localhost:8000',
            'SERVER_PORT' => '8000',
            'SCRIPT_NAME' => '/index.php',
        ];

        self::assertSame(
            'http://localhost:8000/admin/acme/dashboard',
            base_url('admin/acme/dashboard')
        );
    }

    public function testPrefersRuntimeHostOverConfiguredWhenAvailable(): void
    {
        $this->setAppUrl('https://example.test');

        $_SERVER = [
            'HTTP_HOST' => 'tenant.local:9000',
            'SERVER_PORT' => '9000',
            'SCRIPT_NAME' => '/index.php',
        ];

        self::assertSame(
            'https://tenant.local:9000/admin/acme/dashboard',
            base_url('admin/acme/dashboard')
        );
    }

    public function testFallsBackToConfiguredUrlWhenNoServerData(): void
    {
        $this->setAppUrl('https://example.test:8443/base');

        $_SERVER = [
            'SCRIPT_NAME' => '/public/index.php',
        ];

        self::assertSame('https://example.test:8443/base', base_url());
    }

    public function testHonorsForwardedProto(): void
    {
        $_SERVER = [
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_HOST' => 'tenant.local',
            'SERVER_PORT' => '443',
            'SCRIPT_NAME' => '/index.php',
        ];

        self::assertSame('https://tenant.local', base_url());
    }

    private function setAppUrl(?string $url): void
    {
        $items = $this->getConfigItems();

        if (!isset($items['app']) || !is_array($items['app'])) {
            $items['app'] = [];
        }

        if ($url === null) {
            unset($items['app']['url']);
        } else {
            $items['app']['url'] = $url;
        }

        $this->setConfigItems($items);
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfigItems(): array
    {
        $configRef = new ReflectionClass(Config::class);
        $itemsProp = $configRef->getProperty('items');
        $itemsProp->setAccessible(true);
        $items = $itemsProp->getValue();

        return is_array($items) ? $items : [];
    }

    /**
     * @param array<string, mixed> $items
     */
    private function setConfigItems(array $items): void
    {
        $configRef = new ReflectionClass(Config::class);
        $itemsProp = $configRef->getProperty('items');
        $itemsProp->setAccessible(true);
        $itemsProp->setValue($items);
    }
}
