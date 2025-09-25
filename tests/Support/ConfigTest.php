<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testLoadsDefaultConfigValues(): void
    {
        $appName = Config::get('app.name');

        self::assertSame('Multi Menu', $appName);
        self::assertTrue(Config::get('app.debug'));
    }
}
