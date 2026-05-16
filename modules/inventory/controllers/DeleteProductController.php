<?php

declare(strict_types=1);

/**
 * DeleteProductController
 * 
 * Handles POST requests for deleting products from inventory.
 * Implements strict CSRF validation, dynamic ID parameter extraction,
 * and graceful error handling for database constraints (foreign keys).
 * 
 * Security Features:
 * - Dynamic route parameter extraction from URL
 * - CSRF token validation (403 on failure)
 * - Safe deletion with foreign key constraint handling
 * - Session-based flash messages for user feedback
 * - Error logging for debugging
 * 
 * @package AmenERP\Modules\Inventory\Controllers
 * @author Bob
 * @version 1.0.0
 */

// ============================================================================
// 1. EXTRACT DYNAMIC PRODUCT ID PARAMETER
// ============================================================================

/**
 * Extract the product ID from the route parameters
 * The router extracts {id} from the URL pattern /inventory/delete/{id}
 * and makes it available via $params variable (injected by router)
 */
$productIdRaw = $params['id'] ?? null;

// Cast to integer and validate
$productId = ($productIdRaw !== null) ? (int)$productIdRaw : 0;

// Validate that we have a valid product ID
if ($productId <= 0) {
    $_SESSION['error'] = 'Invalid product ID provided.';
    header('Location: ' . BASE_URL . '/inventory');
    exit;
}

// ============================================================================
// 2. CSRF VERIFICATION LAYER (IMMEDIATE SECURITY CHECK)
// ============================================================================

/**
 * Validate CSRF token before processing any data
 * This is the FIRST security layer - fails fast with 403 if invalid
 */
$submittedToken = $_POST['csrf_token'] ?? '';

if (!Csrf::validateToken($submittedToken)) {
    // CSRF validation failed - terminate immediately with 403 Forbidden
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    die('403 Forbidden: Invalid CSRF token. Request rejected for security reasons.');
}

// ============================================================================
// 3. MODEL EXECUTION WITH GRACEFUL ERROR HANDLING
// ============================================================================

/**
 * Load the InventoryModel to perform database operations
 */
require_once MODULES_PATH . '/inventory/models/InventoryModel.php';

/**
 * Instantiate the model and attempt to delete the product
 */
$inventoryModel = new InventoryModel();

try {
    // Call the model method to delete the product
    $result = $inventoryModel->deleteProduct($productId);
    
    if ($result) {
        // Success - set success message and redirect
        $_SESSION['success'] = 'Product deleted successfully!';
    } else {
        // Delete failed - product might not exist
        $_SESSION['error'] = 'Failed to delete product. Product may not exist.';
    }
} catch (PDOException $e) {
    // Database error occurred (likely foreign key constraint)
    error_log('Database error in DeleteProductController: ' . $e->getMessage());
    
    // Check if it's a foreign key constraint violation
    if (strpos($e->getMessage(), 'foreign key constraint') !== false || 
        strpos($e->getMessage(), 'FOREIGN KEY') !== false ||
        $e->getCode() === '23000') {
        $_SESSION['error'] = 'Cannot delete product: it is referenced by other records (orders, transactions, etc.). Please remove those references first.';
    } else {
        $_SESSION['error'] = 'Database error occurred while deleting product. Please contact system administrator.';
    }
} catch (Exception $e) {
    // General error occurred
    error_log('Error in DeleteProductController: ' . $e->getMessage());
    $_SESSION['error'] = 'An unexpected error occurred while deleting product. Please try again.';
}

// ============================================================================
// 4. REDIRECT BACK TO INVENTORY
// ============================================================================

/**
 * Redirect back to inventory page
 * The layout will display the flash message from session
 */
header('Location: ' . BASE_URL . '/inventory');
exit;

// Made with Bob