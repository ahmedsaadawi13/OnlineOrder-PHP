<?php

/**
 * Application Routes
 */

use App\Core\Router;

$router = app()->router();

// ============================================
// PUBLIC ROUTES (No authentication required)
// ============================================

// Health check (no rate limiting for monitoring)
$router->get('/api/v1/health', function() {
    return (new \App\Core\Response())->json(['status' => 'ok'], 200);
});

// Authentication (with rate limiting to prevent brute force)
$router->group(['middleware' => ['ratelimit']], function($router) {
    $router->post('/api/v1/auth/register', 'Auth\AuthController@register');
    $router->post('/api/v1/auth/login', 'Auth\AuthController@login');
    $router->post('/api/v1/auth/refresh', 'Auth\AuthController@refresh');

    // Customer authentication (guest checkout)
    $router->post('/api/v1/customers/register', 'Customer\CustomerController@register');
    $router->post('/api/v1/customers/login', 'Customer\CustomerController@login');
});

// ============================================
// AUTHENTICATED ROUTES
// ============================================

$router->group(['middleware' => ['ratelimit', 'auth', 'tenant']], function($router) {

    // Auth endpoints
    $router->post('/api/v1/auth/logout', 'Auth\AuthController@logout');
    $router->get('/api/v1/auth/me', 'Auth\AuthController@me');

    // Categories
    $router->get('/api/v1/categories', 'Menu\CategoryController@index');
    $router->get('/api/v1/categories/{id}', 'Menu\CategoryController@show');
    $router->post('/api/v1/categories', 'Menu\CategoryController@store');
    $router->put('/api/v1/categories/{id}', 'Menu\CategoryController@update');
    $router->delete('/api/v1/categories/{id}', 'Menu\CategoryController@destroy');

    // Menu Items
    $router->get('/api/v1/menu-items', 'Menu\MenuItemController@index');
    $router->get('/api/v1/menu-items/{id}', 'Menu\MenuItemController@show');
    $router->post('/api/v1/menu-items', 'Menu\MenuItemController@store');
    $router->put('/api/v1/menu-items/{id}', 'Menu\MenuItemController@update');
    $router->delete('/api/v1/menu-items/{id}', 'Menu\MenuItemController@destroy');

    // Orders
    $router->get('/api/v1/orders', 'Order\OrderController@index');
    $router->get('/api/v1/orders/{id}', 'Order\OrderController@show');
    $router->post('/api/v1/orders', 'Order\OrderController@store');
    $router->put('/api/v1/orders/{id}/status', 'Order\OrderController@updateStatus');
    $router->delete('/api/v1/orders/{id}', 'Order\OrderController@cancel');
});

// ============================================
// CUSTOMER ROUTES (Session-based for guest checkout)
// ============================================

$router->group(['middleware' => ['ratelimit']], function($router) {
    // Customer profile
    $router->get('/api/v1/customers/me', 'Customer\CustomerController@me');
    $router->put('/api/v1/customers/me', 'Customer\CustomerController@update');
    $router->post('/api/v1/customers/logout', 'Customer\CustomerController@logout');

    // Customer addresses
    $router->get('/api/v1/customers/addresses', 'Customer\CustomerController@listAddresses');
    $router->post('/api/v1/customers/addresses', 'Customer\CustomerController@addAddress');
    $router->put('/api/v1/customers/addresses/{id}', 'Customer\CustomerController@updateAddress');
    $router->delete('/api/v1/customers/addresses/{id}', 'Customer\CustomerController@deleteAddress');
});

return $router;
