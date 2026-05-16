<?php

declare(strict_types=1);

/**
 * Inventory Search API Endpoint
 *
 * Lightweight, isolated JSON API for product search.
 * Returns pure JSON without any HTML layout or master template markup.
 *
 * @package AmenERP\Modules\Inventory\API
 * @author Bob
 * @version 1.0.0
 */

// Set JSON content type header FIRST to ensure proper response format
header('Content-Type: application/json; charset=utf-8');

// Prevent any HTML output from bleeding into JSON response
ini_set('display_errors', '0');

// Note: This file is loaded through the router, so config.php and autoloader are already loaded
// Constants like MODULES_PATH, ENVIRONMENT, and Database class are available

try {
    // Validate that query parameter exists
    if (!isset($_GET['q'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required parameter: q',
            'data' => []
        ]);
        exit;
    }

    // Sanitize the search query
    $searchQuery = trim($_GET['q']);
    
    // Validate search query is not empty after trimming
    if (empty($searchQuery)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Search query cannot be empty',
            'data' => []
        ]);
        exit;
    }

    // Additional sanitization: remove any potentially harmful characters
    // Use htmlspecialchars for safe output (FILTER_SANITIZE_STRING is deprecated in PHP 8.1+)
    $searchQuery = htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8');
    
    // Limit search query length to prevent abuse
    if (strlen($searchQuery) > 100) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Search query too long (max 100 characters)',
            'data' => []
        ]);
        exit;
    }

    // Load the InventoryModel
    require_once MODULES_PATH . '/inventory/models/InventoryModel.php';
    
    // Instantiate model and perform search
    $inventoryModel = new InventoryModel();
    $results = $inventoryModel->searchProducts($searchQuery);

    // Return successful response with results
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'query' => $searchQuery,
        'count' => count($results),
        'data' => $results
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // Database error
    http_response_code(500);
    $errorResponse = [
        'success' => false,
        'error' => 'Database error occurred',
        'data' => []
    ];
    
    // Include detailed error in development mode
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $errorResponse['debug'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        error_log('Inventory Search API Error: ' . $e->getMessage());
    }
    
    echo json_encode($errorResponse);

} catch (Exception $e) {
    // General error
    http_response_code(500);
    $errorResponse = [
        'success' => false,
        'error' => 'An unexpected error occurred',
        'data' => []
    ];
    
    // Include detailed error in development mode
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $errorResponse['debug'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
        error_log('Inventory Search API Error: ' . $e->getMessage());
    }
    
    echo json_encode($errorResponse);
}

// CRITICAL: Exit immediately to prevent any HTML layout or global master template from rendering
exit;

// Made with Bob