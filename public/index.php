<?php
// public/index.php

<<<<<<< Updated upstream
if (!defined('APP_WEBROOT')) {
  $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
  $webroot = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
  if ($webroot === '/' || $webroot === '.') {
    $webroot = '';
  }
  define('APP_WEBROOT', $webroot);
}

=======
// ===== Boot básico / includes =====
>>>>>>> Stashed changes
require_once __DIR__ . '/../app/core/Helpers.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/config/db.php';

// ===== Detecta automaticamente o webroot (raiz ou subpasta) =====
if (!defined('APP_WEBROOT')) {
  $sn   = $_SERVER['SCRIPT_NAME'] ?? '/index.php';         // ex.: "/index.php" ou "/multi-menu/public/index.php"
  $base = rtrim(str_replace('\\', '/', dirname($sn)), '/'); // ex.: "" ou "/multi-menu/public"
  define('APP_WEBROOT', ($base === '' || $base === '/') ? '' : $base);
}

// (opcional) se seus helpers dependem de APP_WEBROOT, eles já podem usá-la agora

// ===== Instancia o Router =====
$router = new Router();

// (Opcional) Handlers de erro/404 se o Router suportar
if (method_exists($router, 'setNotFoundHandler')) {
  $router->setNotFoundHandler(function ($uri) {
    http_response_code(404);
    echo "Página não encontrada: " . htmlspecialchars((string)$uri, ENT_QUOTES, 'UTF-8');
  });
}
if (method_exists($router, 'setErrorHandler')) {
  $router->setErrorHandler(function ($e) {
    http_response_code(500);
    $msg = $e instanceof Throwable ? $e->getMessage() : 'Erro interno';
    echo "Erro interno: " . htmlspecialchars((string)$msg, ENT_QUOTES, 'UTF-8');
  });
}

// ===== Carrega as rotas da aplicação =====
require_once __DIR__ . '/../routes/web.php';

<<<<<<< Updated upstream
// --- Normalização robusta da URI/base path ---
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$basePath = defined('APP_WEBROOT') ? (string)APP_WEBROOT : '';
if ($basePath === '/' || $basePath === '.') {
  $basePath = '';
}

if ($basePath !== '' && strpos($uri, $basePath) === 0) {
  // Remove /multi-menu/public (ou pasta equivalente) da URI
  $uri = substr($uri, strlen($basePath));
=======
// ===== Normaliza a URI da requisição =====
$reqUri = $_SERVER['REQUEST_URI'] ?? '/';
$path   = parse_url($reqUri, PHP_URL_PATH) ?? '/';

// Remove o prefixo do diretório onde está o index.php (ex.: "/multi-menu/public")
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($scriptDir && $scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
  $path = substr($path, strlen($scriptDir)); // agora vira "/wollburger" em vez de "/multi-menu/public/wollburger"
>>>>>>> Stashed changes
}

// Fallback: algumas apps passam rota em ?route=...
if (isset($_GET['route']) && $_GET['route'] !== '') {
  $path = '/' . ltrim((string)$_GET['route'], '/');
}

// Limpezas finais
$path = rawurldecode($path);
$path = '/' . ltrim($path, '/');              // sempre começa com "/"
$path = preg_replace('~/{2,}~', '/', $path);  // remove barras duplas
if ($path === '//') $path = '/';

// ===== Despacha =====
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Garanta que a home "/" exista nas suas rotas.
// Se sua app não tiver uma rota explícita para "/", você pode:
// - Renderizar a home aqui, OU
// - Redirecionar para uma página inicial (ex.: "/wollburger").
//
// Exemplo de redirecionamento opcional (descomente se quiser):
// if ($path === '/') {
//   header('Location: /wollburger', true, 302);
//   exit;
// }

$router->dispatch($method, $path);
