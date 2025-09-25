<?php

declare(strict_types=1);

use App\Support\Config;
use App\Support\Env;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$basePath = dirname(__DIR__);
Env::load($basePath);
Config::load($basePath . '/config');

date_default_timezone_set((string) config('app.timezone', 'UTC'));

$logChannel = config('logging.default', 'stack');
$logger = new Logger($logChannel);
$logger->pushHandler(new StreamHandler(
    $basePath . '/storage/logs/app.log',
    Level::fromName(strtoupper((string) config('logging.level', 'debug')))
));

return $logger;
