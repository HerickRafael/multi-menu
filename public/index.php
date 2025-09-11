<?php
require_once __DIR__ . '/../app/core/Helpers.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/config/db.php';

$router = new Router();

/* Rotas públicas (cardápio) */
$router->get('/{slug}', 'PublicHomeController@index');
$router->get('/{slug}/buscar', 'PublicHomeController@buscar');
$router->get('/{slug}/produto/{id}', 'PublicProductController@show');

/* Rotas cliente (login por nome + WhatsApp) */
$router->post('/{slug}/customer-login',  'CustomerAuthController@login');
$router->post('/{slug}/customer-logout', 'CustomerAuthController@logout');
$router->get('/{slug}/customer-me',      'CustomerAuthController@me');

/* Rotas admin por empresa */
$router->get('/admin/{slug}/login', 'AdminAuthController@loginForm');
$router->post('/admin/{slug}/login', 'AdminAuthController@login');
$router->get('/admin/{slug}/dashboard', 'AdminDashboardController@index');
$router->get('/admin/{slug}/logout', 'AdminAuthController@logout');

// ADMIN – Configurações gerais
$router->get('/admin/{slug}/settings',  'AdminSettingsController@index');
$router->post('/admin/{slug}/settings', 'AdminSettingsController@save');

// ADMIN – Pedidos
$router->get('/admin/{slug}/orders',            'AdminOrdersController@index');
$router->get('/admin/{slug}/orders/show',       'AdminOrdersController@show');
$router->post('/admin/{slug}/orders/setStatus', 'AdminOrdersController@setStatus');
$router->get('/admin/{slug}/orders/create',     'AdminOrdersController@create');
$router->post('/admin/{slug}/orders',           'AdminOrdersController@store');

// ADMIN – Categorias (CRUD)
$router->get('/admin/{slug}/categories',            'AdminCategoryController@index');
$router->get('/admin/{slug}/categories/create',     'AdminCategoryController@create');
$router->post('/admin/{slug}/categories',           'AdminCategoryController@store');
$router->get('/admin/{slug}/categories/{id}/edit',  'AdminCategoryController@edit');
$router->post('/admin/{slug}/categories/{id}',      'AdminCategoryController@update');
$router->post('/admin/{slug}/categories/{id}/del',  'AdminCategoryController@destroy');

// ADMIN – Produtos (CRUD)
$router->get('/admin/{slug}/products',              'AdminProductController@index');
$router->get('/admin/{slug}/products/create',       'AdminProductController@create');
$router->post('/admin/{slug}/products',             'AdminProductController@store');
$router->get('/admin/{slug}/products/{id}/edit',    'AdminProductController@edit');
$router->post('/admin/{slug}/products/{id}',        'AdminProductController@update');
$router->post('/admin/{slug}/products/{id}/del',    'AdminProductController@destroy');

/* Normaliza a URI removendo o base path */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($basePath !== '' && strpos($uri, $basePath) === 0) {
  $uri = substr($uri, strlen($basePath));
}
if ($uri === '' || $uri === false) $uri = '/';

/* Despacha */
$router->dispatch($_SERVER['REQUEST_METHOD'], $uri);
