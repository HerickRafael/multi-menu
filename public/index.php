<?php
// public/index.php

require_once __DIR__ . '/../app/core/Helpers.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/config/db.php';

$router = new Router();

// (Opcional) Handlers
if (method_exists($router, 'setNotFoundHandler')) {
  $router->setNotFoundHandler(function($uri){
    http_response_code(404);
    echo "Página não encontrada: " . htmlspecialchars($uri);
  });
}
if (method_exists($router, 'setErrorHandler')) {
  $router->setErrorHandler(function($e){
    http_response_code(500);
    echo "Erro interno: " . htmlspecialchars($e->getMessage());
  });
}

// Carrega rotas
require_once __DIR__ . '/../routes/web.php';

// --- Normalização robusta da URI/base path ---
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$phpSelf    = $_SERVER['PHP_SELF']    ?? $scriptName;

// basePath vira a pasta onde está o index.php (ex.: /multi-menu/public)
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

if ($basePath && strpos($uri, $basePath) === 0) {
  $uri = substr($uri, strlen($basePath)); // remove /multi-menu/public
}
$uri = '/' . ltrim($uri, '/');
if ($uri === '' || $uri === false) $uri = '/';

// Despacha
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $uri);
