<?php

/**
 * Application Routes
 */

use App\Core\Router;

$router = app()->router();

// ============================================
// PUBLIC ROUTES (No authentication required)
// ============================================

// Health check
$router->get('/api/v1/health', function() {
    return (new \App\Core\Response())->json(['status' => 'ok'], 200);
});

// Authentication
$router->post('/api/v1/auth/register', 'Auth\AuthController@register');
$router->post('/api/v1/auth/login', 'Auth\AuthController@login');
$router->post('/api/v1/auth/refresh', 'Auth\AuthController@refresh');

// ============================================
// AUTHENTICATED ROUTES
// ============================================

$router->group(['middleware' => ['auth', 'tenant']], function($router) {

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

return $router;
