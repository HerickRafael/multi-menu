<?php

declare(strict_types=1);

$publicPath = __DIR__ . '/public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

$requested = realpath($publicPath . $uri);

if ($requested !== false && str_starts_with($requested, $publicPath) && is_file($requested)) {
    return false;
}

require $publicPath . '/index.php';

