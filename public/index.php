<?php

/**
 * Application Entry Point
 */

// Start session
session_start();

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Create application instance
$app = new App\Core\Application(__DIR__ . '/..');

// Make app globally accessible
function app() {
    global $app;
    return $app;
}

// Load routes
require_once __DIR__ . '/../src/routes.php';

// Run application
$app->run();
