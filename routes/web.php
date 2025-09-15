<?php
// routes/web.php

/* ========= Rotas públicas (cardápio) ========= */
$router->get('/{slug}',                       'PublicHomeController@index');
$router->get('/{slug}/buscar',                'PublicHomeController@buscar');
$router->get('/{slug}/produto/{id}',          'PublicProductController@show');

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

/* ========= Constraints globais ========= */
if (method_exists($router, 'where')) {
  $router->where('slug', '[a-z0-9\-]+');
  $router->where('id',   '\d+');
}
