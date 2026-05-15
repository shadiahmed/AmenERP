<?php

declare(strict_types=1);

/**
 * AmenERP Entry Point
 *
 * This is the main entry point for the AmenERP application.
 * All requests are routed through this file via .htaccess.
 *
 * @package AmenERP
 * @author Bob
 * @version 1.0.0
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Load core classes
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Router.php';

// Initialize Database connection
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    // Handle database connection errors gracefully
    if (ENVIRONMENT === 'development') {
        die('Database Error: ' . $e->getMessage());
    } else {
        die('Application Error: Unable to connect to database.');
    }
}

// Initialize Router
$router = new Router();

// ============================================================================
// ROUTE DEFINITIONS
// ============================================================================

// Home route
$router->get('/', 'home/index.php');

// Example module routes (uncomment and adjust as needed)
// $router->get('/inventory', 'inventory/list.php');
// $router->get('/inventory/{id}', 'inventory/view.php');
// $router->post('/inventory', 'inventory/create.php');
// $router->put('/inventory/{id}', 'inventory/update.php');
// $router->delete('/inventory/{id}', 'inventory/delete.php');

// $router->get('/finance', 'finance/dashboard.php');
// $router->get('/finance/transactions', 'finance/transactions.php');

// ============================================================================
// DISPATCH REQUEST
// ============================================================================

$router->dispatch();

// Made with Bob
