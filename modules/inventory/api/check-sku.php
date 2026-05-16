<?php

declare(strict_types=1);

/**
 * SKU Availability Check API
 * 
 * Lightweight JSON endpoint to verify if a SKU already exists in the database.
 * Used for real-time client-side validation to prevent duplicate SKU entries.
 * 
 * Security & Error Handling:
 * - Sets JSON header immediately to prevent HTML error output
 * - Clears any buffered output before returning responses
 * - Validates request method
 * - Sanitizes input parameters
 * - Catches all exceptions and returns JSON errors
 * 
 * @package AmenERP\Modules\Inventory\API
 * @author Bob
 * @version 1.0.0
 */

// ============================================================================
// 1. HEADERS & OUTPUT BUFFERING (MUST BE FIRST)
// ============================================================================

/**
 * Set JSON response header BEFORE any potential errors
 * This ensures the browser recognizes the response as JSON
 */
header('Content-Type: application/json; charset=UTF-8');

/**
 * Start output buffering to capture any unexpected output
 * This allows us to clean the buffer if errors occur
 */
ob_start();

// ============================================================================
// 2. LOAD DEPENDENCIES
// ============================================================================

try {
    // Load the InventoryModel
    require_once __DIR__ . '/../models/InventoryModel.php';
} catch (Exception $e) {
    // Clear any buffered output
    ob_end_clean();
    
    // Log the error
    error_log('Failed to load InventoryModel: ' . $e->getMessage());
    
    // Return JSON error
    http_response_code(500);
    echo json_encode([
        'exists' => false,
        'error' => 'Failed to load dependencies'
    ]);
    exit;
}

// ============================================================================
// 3. REQUEST VALIDATION
// ============================================================================

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    // Clear any buffered output
    ob_end_clean();
    
    http_response_code(405);
    echo json_encode([
        'exists' => false,
        'error' => 'Method not allowed. Use GET.'
    ]);
    exit;
}

// Get SKU from query parameter
$sku = $_GET['sku'] ?? '';

// Validate SKU parameter
if (empty($sku)) {
    // Clear any buffered output
    ob_end_clean();
    
    http_response_code(400);
    echo json_encode([
        'exists' => false,
        'error' => 'SKU parameter is required'
    ]);
    exit;
}

// Sanitize SKU input
$sku = filter_var($sku, FILTER_SANITIZE_STRING);

// ============================================================================
// 4. DATABASE CHECK
// ============================================================================

try {
    // Clear any buffered output before returning success
    ob_end_clean();
    
    // Initialize model and check SKU existence
    $inventoryModel = new InventoryModel();
    $exists = $inventoryModel->skuExists($sku);
    
    // Return JSON response with HTTP 200
    http_response_code(200);
    echo json_encode([
        'exists' => (bool)$exists
    ]);
} catch (PDOException $e) {
    // Clear any buffered output
    ob_end_clean();
    
    // Log database error
    error_log('Database error in SKU check: ' . $e->getMessage());
    
    // Return JSON error
    http_response_code(500);
    echo json_encode([
        'exists' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    // Clear any buffered output
    ob_end_clean();
    
    // Log general error
    error_log('Error in SKU check: ' . $e->getMessage());
    
    // Return JSON error
    http_response_code(500);
    echo json_encode([
        'exists' => false,
        'error' => 'An unexpected error occurred'
    ]);
}

exit;

// Made with Bob