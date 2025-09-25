<?php

declare(strict_types=1);

use App\Core\Router;

$logger = require __DIR__ . '/../bootstrap/app.php';

$router = new Router($logger);

require __DIR__ . '/../routes/web.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

if ($basePath && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}

$uri = '/' . ltrim((string) $uri, '/');
if ($uri === '' || $uri === false) {
    $uri = '/';
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$router->dispatch($method, $uri);
