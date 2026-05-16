<?php

declare(strict_types=1);

/**
 * EditProductController
 * 
 * Handles POST requests for editing existing products in inventory.
 * Implements strict CSRF validation, input sanitization, type casting,
 * business logic validation, and dynamic ID parameter extraction.
 * 
 * Security Features:
 * - Dynamic route parameter extraction from URL
 * - CSRF token validation (403 on failure)
 * - Input sanitization using native PHP filter functions
 * - Strict type casting for numeric values
 * - Business rule validation (required fields, non-negative values)
 * - Session-based flash messages for user feedback
 * 
 * @package AmenERP\Modules\Inventory\Controllers
 * @author Bob
 * @version 1.0.0
 */

// ============================================================================
// 0. DETECT REQUEST TYPE (AJAX vs Regular Form Submission)
// ============================================================================

/**
 * Check if this is an AJAX request by looking at the X-Requested-With header
 * AJAX requests set this header, regular form submissions don't
 */
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// ============================================================================
// 1. EXTRACT DYNAMIC PRODUCT ID PARAMETER
// ============================================================================

/**
 * Extract the product ID from the route parameters
 * The router extracts {id} from the URL pattern /inventory/edit/{id}
 * and makes it available via $params variable (injected by router)
 */
$productIdRaw = $params['id'] ?? null;

// Cast to integer and validate
$productId = ($productIdRaw !== null) ? (int)$productIdRaw : 0;

// Validate that we have a valid product ID
if ($productId <= 0) {
    if ($isAjaxRequest) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid product ID provided.']);
        exit;
    } else {
        $_SESSION['error'] = 'Invalid product ID provided.';
        header('Location: ' . BASE_URL . '/inventory');
        exit;
    }
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
    if ($isAjaxRequest) {
        // AJAX request - return JSON error
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token. Request rejected for security reasons.']);
        exit;
    } else {
        // Regular form - return plain text error
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        die('403 Forbidden: Invalid CSRF token. Request rejected for security reasons.');
    }
}

// ============================================================================
// 3. DATA SANITIZATION & STRICT TYPE CASTING
// ============================================================================

/**
 * Sanitize text inputs using filter_input with FILTER_SANITIZE_FULL_SPECIAL_CHARS
 * This removes/encodes potentially dangerous characters while preserving valid input
 */
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$sku = filter_input(INPUT_POST, 'sku', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
$status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'active';

/**
 * Sanitize and cast category_id as integer
 * Use FILTER_VALIDATE_INT to ensure it's a valid integer, default to 0 if invalid
 */
$categoryIdRaw = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
$categoryId = ($categoryIdRaw !== false && $categoryIdRaw !== null) ? (int)$categoryIdRaw : 0;

/**
 * Sanitize and cast quantity as integer
 * Use FILTER_VALIDATE_INT to ensure it's a valid integer, default to 0 if invalid
 */
$quantityRaw = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$quantity = ($quantityRaw !== false && $quantityRaw !== null) ? (int)$quantityRaw : 0;

/**
 * Sanitize and cast unit_price as float
 * Use FILTER_VALIDATE_FLOAT to ensure it's a valid decimal number, default to 0.0 if invalid
 */
$unitPriceRaw = filter_input(INPUT_POST, 'unit_price', FILTER_VALIDATE_FLOAT);
$unitPrice = ($unitPriceRaw !== false && $unitPriceRaw !== null) ? (float)$unitPriceRaw : 0.0;

// ============================================================================
// 4. BUSINESS LOGIC VALIDATION
// ============================================================================

/**
 * Validation Rules:
 * 1. All required fields must not be empty
 * 2. Quantity must not be negative
 * 3. Unit price must not be negative
 * 4. Category ID must be valid (greater than 0)
 * 5. SKU must follow expected pattern (uppercase alphanumeric with hyphens)
 * 6. Status must be one of the allowed values
 */

$errors = [];

// Validate required fields are not empty
if (empty(trim($name))) {
    $errors[] = 'Product name is required.';
}

if (empty(trim($sku))) {
    $errors[] = 'SKU is required.';
}

// Validate SKU format (uppercase letters, numbers, and hyphens only)
if (!empty(trim($sku)) && !preg_match('/^[A-Z0-9\-]+$/', $sku)) {
    $errors[] = 'SKU must contain only uppercase letters, numbers, and hyphens.';
}

// Validate category_id is valid
if ($categoryId <= 0) {
    $errors[] = 'Please select a valid category.';
}

// Validate quantity is not negative
if ($quantity < 0) {
    $errors[] = 'Quantity cannot be negative.';
}

// Validate unit_price is not negative
if ($unitPrice < 0) {
    $errors[] = 'Unit price cannot be negative.';
}

// Validate status is one of the allowed values
$allowedStatuses = ['active', 'inactive', 'discontinued'];
if (!in_array($status, $allowedStatuses, true)) {
    $errors[] = 'Invalid product status.';
}

// If validation fails, set error message and redirect back
if (!empty($errors)) {
    $errorMessage = implode(' ', $errors);
    if ($isAjaxRequest) {
        // AJAX request - return JSON error
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $errorMessage]);
        exit;
    } else {
        // Regular form - set session and redirect
        $_SESSION['error'] = $errorMessage;
        header('Location: ' . BASE_URL . '/inventory');
        exit;
    }
}

// ============================================================================
// 5. DATABASE EXECUTION & REDIRECT
// ============================================================================

/**
 * Load the InventoryModel to perform database operations
 */
require_once MODULES_PATH . '/inventory/models/InventoryModel.php';

/**
 * Prepare data array for update
 * All values are sanitized and type-cast at this point
 */
$productData = [
    'name' => trim($name),
    'sku' => trim($sku),
    'category_id' => $categoryId,
    'quantity' => $quantity,
    'unit_price' => $unitPrice,
    'status' => $status
];

/**
 * Instantiate the model and attempt to update the product
 */
$inventoryModel = new InventoryModel();

try {
    // Call the model method to update the product
    $result = $inventoryModel->updateProduct($productId, $productData);
    
    if ($result) {
        $successMessage = 'Product "' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" updated successfully!';
        
        if ($isAjaxRequest) {
            // AJAX request - return JSON success
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(200);
            echo json_encode([
                'success' => true, 
                'message' => $successMessage,
                'product_name' => $name,
                'product_id' => $productId
            ]);
            exit;
        } else {
            // Regular form - set session and redirect
            $_SESSION['success'] = $successMessage;
            header('Location: ' . BASE_URL . '/inventory');
            exit;
        }
    } else {
        // Update failed - product might not exist
        $errorMessage = 'Failed to update product. Product may not exist.';
        
        if ($isAjaxRequest) {
            // AJAX request - return JSON error
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $errorMessage]);
            exit;
        } else {
            // Regular form - set session and redirect
            $_SESSION['error'] = $errorMessage;
            header('Location: ' . BASE_URL . '/inventory');
            exit;
        }
    }
} catch (PDOException $e) {
    // Database error occurred
    error_log('Database error in EditProductController: ' . $e->getMessage());
    $errorMessage = 'Database error occurred. Please contact system administrator.';
    
    if ($isAjaxRequest) {
        // AJAX request - return JSON error
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $errorMessage]);
        exit;
    } else {
        // Regular form - set session and redirect
        $_SESSION['error'] = $errorMessage;
        header('Location: ' . BASE_URL . '/inventory');
        exit;
    }
} catch (Exception $e) {
    // General error occurred
    error_log('Error in EditProductController: ' . $e->getMessage());
    $errorMessage = 'An unexpected error occurred. Please try again.';
    
    if ($isAjaxRequest) {
        // AJAX request - return JSON error
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $errorMessage]);
        exit;
    } else {
        // Regular form - set session and redirect
        $_SESSION['error'] = $errorMessage;
        header('Location: ' . BASE_URL . '/inventory');
        exit;
    }
}

/**
 * Redirect back to inventory page (fallback, should not reach here)
 * The layout will display the flash message from session
 */
header('Location: ' . BASE_URL . '/inventory');
exit;

// Made with Bob