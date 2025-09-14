<?php
// public/index.php

require_once __DIR__ . '/../app/core/Helpers.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/config/db.php';

$router = new Router();

// (Opcional) Handlers de erro/404 se o Router suportar
if (method_exists($router, 'setNotFoundHandler')) {
  $router->setNotFoundHandler(function($uri){
    http_response_code(404);
    echo "Página não encontrada: " . htmlspecialchars((string)$uri, ENT_QUOTES, 'UTF-8');
  });
}
if (method_exists($router, 'setErrorHandler')) {
  $router->setErrorHandler(function($e){
    http_response_code(500);
    $msg = $e instanceof Throwable ? $e->getMessage() : 'Erro desconhecido';
    echo "Erro interno: " . htmlspecialchars((string)$msg, ENT_QUOTES, 'UTF-8');
  });
}

// Carrega as rotas (arquivo dedicado)
require_once __DIR__ . '/../routes/web.php';

// --- Normalização robusta da URI/base path ---
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

if ($basePath && strpos($uri, $basePath) === 0) {
  // Remove /multi-menu/public (ou pasta equivalente) da URI
  $uri = substr($uri, strlen($basePath));
}

$uri = '/' . ltrim((string)$uri, '/');
if ($uri === '' || $uri === false) $uri = '/';

// Despacha
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$router->dispatch($method, $uri);
