<?php
// routes/web.php

require_once __DIR__ . '/../app/models/Company.php';

/** Resolve o slug padrão configurado ou a primeira empresa ativa */
if (!function_exists('resolve_default_company_slug')) {
  function resolve_default_company_slug(): ?string {
    return Company::defaultSlug();
  }
}

/** Renderiza o cardápio público para a empresa padrão */
if (!function_exists('render_default_menu')) {
  function render_default_menu(): void {
    $slug = resolve_default_company_slug();
    if (!$slug) {
      http_response_code(404);
      echo 'Cardápio não configurado.';
      return;
    }

    require_once __DIR__ . '/../app/controllers/PublicHomeController.php';
    $controller = new PublicHomeController();
    $controller->index(['slug' => $slug]);
  }
}

/* ========= Rotas sem slug explícito ========= */
$router->get('/', function () {
  render_default_menu();
});

$router->get('/cardapio', function () {
  render_default_menu();
});

$router->get('/dashboard', function () {
  $slug = resolve_default_company_slug();
  if (!$slug) {
    http_response_code(404);
    echo 'Empresa não configurada.';
    return;
  }
  header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/dashboard'));
  exit;
});

$router->get('/admin', function () {
  $slug = resolve_default_company_slug();
  if (!$slug) {
    http_response_code(404);
    echo 'Empresa não configurada.';
    return;
  }
  header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
  exit;
});

$router->get('/admin/login', function () {
  $slug = resolve_default_company_slug();
  if (!$slug) {
    http_response_code(404);
    echo 'Empresa não configurada.';
    return;
  }
  header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
  exit;
});

/* ========= Rotas públicas (cardápio) ========= */
$router->get('/{slug}',                       'PublicHomeController@index');
$router->get('/{slug}/buscar',                'PublicHomeController@buscar');
$router->get('/{slug}/produto/{id}',          'PublicProductController@show');
$router->get('/{slug}/product/{id}',          'PublicProductController@show');

/* Personalização de produto */
$router->get('/{slug}/produto/{id}/customizar', 'PublicProductController@customize');
$router->post('/{slug}/produto/{id}/customizar','PublicProductController@saveCustomization');

/* Carrinho */
$router->get('/{slug}/cart',                  'PublicCartController@index');
$router->post('/{slug}/cart/add',             'PublicCartController@add');

/* ========= Rotas cliente ========= */
$router->post('/{slug}/customer-login',       'CustomerAuthController@login');
$router->post('/{slug}/customer-logout',      'CustomerAuthController@logout');
$router->get('/{slug}/customer-me',           'CustomerAuthController@me');

/* ========= Rotas admin ========= */
// Auth + Dashboard
$router->get('/admin/{slug}/login',           'AdminAuthController@loginForm');
$router->post('/admin/{slug}/login',          'AdminAuthController@login');
$router->get('/admin/{slug}/logout',          'AdminAuthController@logout');
$router->get('/admin/{slug}/dashboard',       'AdminDashboardController@index');

// Configurações
$router->get('/admin/{slug}/settings',        'AdminSettingsController@index');
$router->post('/admin/{slug}/settings',       'AdminSettingsController@save');

// Pedidos
$router->get('/admin/{slug}/orders',          'AdminOrdersController@index');
$router->get('/admin/{slug}/orders/show',     'AdminOrdersController@show');
$router->get('/admin/{slug}/orders/create',   'AdminOrdersController@create');
$router->post('/admin/{slug}/orders',         'AdminOrdersController@store');
$router->post('/admin/{slug}/orders/setStatus','AdminOrdersController@setStatus');

// Categorias (CRUD)
$router->get('/admin/{slug}/categories',            'AdminCategoryController@index');
$router->get('/admin/{slug}/categories/create',     'AdminCategoryController@create');
$router->post('/admin/{slug}/categories',           'AdminCategoryController@store');
$router->get('/admin/{slug}/categories/{id}/edit',  'AdminCategoryController@edit');
$router->post('/admin/{slug}/categories/{id}',      'AdminCategoryController@update');
$router->post('/admin/{slug}/categories/{id}/del',  'AdminCategoryController@destroy');

// Produtos (CRUD)
$router->get('/admin/{slug}/products',              'AdminProductController@index');
$router->get('/admin/{slug}/products/create',       'AdminProductController@create');
$router->post('/admin/{slug}/products',             'AdminProductController@store');
$router->get('/admin/{slug}/products/{id}/edit',    'AdminProductController@edit');
$router->post('/admin/{slug}/products/{id}',        'AdminProductController@update');
$router->post('/admin/{slug}/products/{id}/del',    'AdminProductController@destroy');

// Ingredientes (CRUD)
$router->get('/admin/{slug}/ingredients',              'AdminIngredientController@index');
$router->get('/admin/{slug}/ingredients/create',       'AdminIngredientController@create');
$router->post('/admin/{slug}/ingredients',             'AdminIngredientController@store');
$router->get('/admin/{slug}/ingredients/{id}/edit',    'AdminIngredientController@edit');
$router->post('/admin/{slug}/ingredients/{id}',        'AdminIngredientController@update');
$router->post('/admin/{slug}/ingredients/{id}/del',    'AdminIngredientController@destroy');

/* ========= Constraints globais ========= */
if (method_exists($router, 'where')) {
  $router->where('slug', '[a-z0-9\-]+');
  $router->where('id',   '\d+');
}
