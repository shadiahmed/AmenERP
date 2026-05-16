<?php

declare(strict_types=1);

/**
 * AddProductController
 * 
 * Handles POST requests for adding new products to inventory.
 * Implements strict CSRF validation, input sanitization, type casting,
 * and business logic validation before database insertion.
 * 
 * Security Features:
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
// 1. CSRF VERIFICATION LAYER (IMMEDIATE SECURITY CHECK)
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
// 2. DATA SANITIZATION & STRICT TYPE CASTING
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
// 3. BUSINESS LOGIC VALIDATION
// ============================================================================

/**
 * Validation Rules:
 * 1. All required fields must not be empty
 * 2. Quantity must not be negative
 * 3. Unit price must not be negative
 * 4. Category ID must be valid (greater than 0)
 * 5. SKU must follow expected pattern (uppercase alphanumeric with hyphens)
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
    $_SESSION['error'] = implode(' ', $errors);
    header('Location: ' . BASE_URL . '/inventory');
    exit;
}

// ============================================================================
// 4. DATABASE EXECUTION & REDIRECT
// ============================================================================

/**
 * Load the InventoryModel to perform database operations
 */
require_once MODULES_PATH . '/inventory/models/InventoryModel.php';

/**
 * Prepare data array for insertion
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
 * Instantiate the model and attempt to add the product
 */
$inventoryModel = new InventoryModel();

try {
    // Call the model method to insert the product
    $result = $inventoryModel->addProduct($productData);
    
    if ($result) {
        // Success - set success message and redirect
        $_SESSION['success'] = 'Product "' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" added successfully!';
    } else {
        // Insert failed for some reason
        $_SESSION['error'] = 'Failed to add product. Please try again.';
    }
} catch (PDOException $e) {
    // Database error occurred
    error_log('Database error in AddProductController: ' . $e->getMessage());
    $_SESSION['error'] = 'Database error occurred. Please contact system administrator.';
} catch (Exception $e) {
    // General error occurred
    error_log('Error in AddProductController: ' . $e->getMessage());
    $_SESSION['error'] = 'An unexpected error occurred. Please try again.';
}

/**
 * Redirect back to inventory page
 * The layout will display the flash message from session
 */
header('Location: ' . BASE_URL . '/inventory');
exit;

// Made with Bob