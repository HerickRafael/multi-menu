<?php
// public/index.php

// ===============================================================
// 1) Define APP_WEBROOT corretamente (sem o "/public" no final)
//    - Se a app estiver na raiz, APP_WEBROOT = ''
//    - Se estiver em subpasta (ex.: /multi-menu/public), APP_WEBROOT = '/multi-menu'
// ===============================================================
if (!defined('APP_WEBROOT')) {
  $sn  = $_SERVER['SCRIPT_NAME'] ?? '/index.php';                 // ex.: "/index.php" ou "/multi-menu/public/index.php"
  $dir = rtrim(str_replace('\\', '/', dirname($sn)), '/');        // ex.: "" ou "/multi-menu/public"
  // remove o sufixo "/public" se existir
  if ($dir !== '' && $dir !== '/' && substr($dir, -7) === '/public') {
    $dir = substr($dir, 0, -7);                                   // ex.: "/multi-menu"
  }
  if ($dir === '/' || $dir === '.' ) $dir = '';
  define('APP_WEBROOT', $dir);                                    // '' ou '/multi-menu'
}

// ===============================================================
// 2) Bootstrap básico / includes
//    (Helpers podem usar APP_WEBROOT se precisarem)
// ===============================================================
require_once __DIR__ . '/../app/core/Helpers.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/config/db.php';

// ===============================================================
// 3) Instancia o Router e (opcional) handlers de erro
// ===============================================================
$router = new Router();

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

// ===============================================================
// 4) Carrega as rotas da aplicação
// ===============================================================
require_once __DIR__ . '/../routes/web.php';

// ===============================================================
// 5) Normaliza a URI da requisição para extrair o path de roteamento
//    - Remove prefixos do diretório onde está o index.php (ex.: "/multi-menu/public")
//    - Também remove APP_WEBROOT do início, se presente
//    - Aceita fallback via ?route=/algo
// ===============================================================
$reqUri = $_SERVER['REQUEST_URI'] ?? '/';
$path   = parse_url($reqUri, PHP_URL_PATH) ?? '/';

// Remove diretório do script (ex.: "/multi-menu/public")
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($scriptDir && $scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
  $path = substr($path, strlen($scriptDir));
}

// Remove APP_WEBROOT do início (ex.: "/multi-menu")
$basePath = (string) APP_WEBROOT;
if ($basePath === '/' || $basePath === '.') $basePath = '';
if ($basePath !== '' && strpos($path, $basePath) === 0) {
  $path = substr($path, strlen($basePath));
}

// Fallback: rota em ?route=/...
if (isset($_GET['route']) && $_GET['route'] !== '') {
  $path = '/' . ltrim((string)$_GET['route'], '/');
}

// Limpezas finais
$path = rawurldecode($path);
$path = '/' . ltrim($path, '/');             // garante início com "/"
$path = preg_replace('~/{2,}~', '/', $path); // remove barras duplas
if ($path === '//') $path = '/';

// ===============================================================
// 6) Despacha
// ===============================================================
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Exemplo de redirecionamento opcional da home para outra página:
// if ($path === '/') {
//   header('Location: ' . (APP_WEBROOT ? APP_WEBROOT : '') . '/wollburger', true, 302);
//   exit;
// }

$router->dispatch($method, $path);
