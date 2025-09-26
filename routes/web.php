<?php

declare(strict_types=1);

$router->get('/', 'WelcomeController@index');

/* ========= Ferramentas ========= */
$router->get('/admin/hash', 'AdminHashController@show');
$router->post('/admin/hash', 'AdminHashController@generate');

/* ========= Rotas admin ========= */
$router->get('/admin/{slug}/login', 'AdminAuthController@loginForm');
$router->post('/admin/{slug}/login', 'AdminAuthController@login');
$router->get('/admin/{slug}/logout', 'AdminAuthController@logout');
$router->get('/admin/{slug}/dashboard', 'AdminDashboardController@index');

$router->get('/admin/{slug}/settings', 'AdminSettingsController@index');
$router->post('/admin/{slug}/settings', 'AdminSettingsController@save');
$router->get('/admin/{slug}/orders', 'AdminOrdersController@index');
$router->get('/admin/{slug}/orders/show', 'AdminOrdersController@show');
$router->get('/admin/{slug}/orders/create', 'AdminOrdersController@create');
$router->post('/admin/{slug}/orders', 'AdminOrdersController@store');
$router->post('/admin/{slug}/orders/setStatus', 'AdminOrdersController@setStatus');
$router->post('/admin/{slug}/orders/{id}/del', 'AdminOrdersController@destroy');

$router->get('/admin/{slug}/categories', 'AdminCategoryController@index');
$router->get('/admin/{slug}/categories/create', 'AdminCategoryController@create');
$router->post('/admin/{slug}/categories', 'AdminCategoryController@store');
$router->get('/admin/{slug}/categories/{id}/edit', 'AdminCategoryController@edit');
$router->post('/admin/{slug}/categories/{id}', 'AdminCategoryController@update');
$router->post('/admin/{slug}/categories/{id}/delete', 'AdminCategoryController@destroy');

$router->get('/admin/{slug}/products', 'AdminProductController@index');
$router->get('/admin/{slug}/products/create', 'AdminProductController@create');
$router->post('/admin/{slug}/products', 'AdminProductController@store');
$router->get('/admin/{slug}/products/{id}/edit', 'AdminProductController@edit');
$router->post('/admin/{slug}/products/{id}', 'AdminProductController@update');
$router->post('/admin/{slug}/products/{id}/delete', 'AdminProductController@destroy');

$router->get('/admin/{slug}/ingredients', 'AdminIngredientController@index');
$router->post('/admin/{slug}/ingredients', 'AdminIngredientController@store');
$router->post('/admin/{slug}/ingredients/{id}/delete', 'AdminIngredientController@destroy');

$router->get('/admin/{slug}/delivery-fees', 'AdminDeliveryFeeController@index');

$router->post('/admin/{slug}/delivery-fees/cities', 'AdminDeliveryFeeController@storeCity');
$router->post('/admin/{slug}/delivery-fees/cities/{id}', 'AdminDeliveryFeeController@updateCity');
$router->post('/admin/{slug}/delivery-fees/cities/{id}/delete', 'AdminDeliveryFeeController@destroyCity');
$router->post('/admin/{slug}/delivery-fees/zones', 'AdminDeliveryFeeController@storeZone');
$router->post('/admin/{slug}/delivery-fees/zones/{id}', 'AdminDeliveryFeeController@updateZone');
$router->post('/admin/{slug}/delivery-fees/zones/{id}/delete', 'AdminDeliveryFeeController@destroyZone');
$router->post('/admin/{slug}/delivery-fees/zones-adjust', 'AdminDeliveryFeeController@adjustZones');
$router->post('/admin/{slug}/delivery-fees/options', 'AdminDeliveryFeeController@updateOptions');

/* ========= Rotas públicas (cardápio) ========= */
$router->get('/{slug}', 'PublicHomeController@index');
$router->get('/{slug}/buscar', 'PublicHomeController@buscar');
$router->get('/{slug}/produto/{id}', 'PublicProductController@show');
$router->get('/{slug}/product/{id}', 'PublicProductController@show');

/* Personalização de produto */
$router->get('/{slug}/produto/{id}/customizar', 'PublicProductController@customize');
$router->post('/{slug}/produto/{id}/customizar', 'PublicProductController@saveCustomization');

/* Carrinho */
$router->get('/{slug}/cart', 'PublicCartController@index');
$router->post('/{slug}/cart/add', 'PublicCartController@add');

/* ========= Rotas cliente ========= */
$router->post('/{slug}/customer-login', 'CustomerAuthController@login');
$router->post('/{slug}/customer-logout', 'CustomerAuthController@logout');
$router->get('/{slug}/customer-me', 'CustomerAuthController@me');
