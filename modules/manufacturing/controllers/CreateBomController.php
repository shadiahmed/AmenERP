<?php

declare(strict_types=1);

/**
 * Create Bill of Materials Controller
 *
 * Handles BOM creation operations with strict security controls:
 * - Creating new Bills of Materials with component mappings
 * - Validating finished product and component selections
 * - Recording recipe rules and quantity requirements
 *
 * Security Features:
 * - POST-only request validation (405 Method Not Allowed)
 * - CSRF token validation (403 Forbidden on failure)
 * - Input sanitization and type casting
 * - Transaction-based error handling
 * - Comprehensive error logging
 *
 * @package AmenERP\Modules\Manufacturing\Controllers
 * @author Principal Systems Architect
 * @version 1.0.0
 */

// ============================================================================
// 0. BOOTSTRAP - Load Configuration
// ============================================================================

/**
 * Load application configuration
 */
require_once __DIR__ . '/../../../config/config.php';

// ============================================================================
// 1. REQUEST METHOD VALIDATION - Restrict to POST Only
// ============================================================================

/**
 * Enforce POST-only access
 * Reject all other HTTP methods with 405 Method Not Allowed
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    die('Method Not Allowed - This endpoint only accepts POST requests');
}

// ============================================================================
// 2. SECURITY LAYER - Session Initialization & CSRF Validation
// ============================================================================

/**
 * Ensure session is started for CSRF validation and flash messages
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Load CSRF protection class
 */
require_once __DIR__ . '/../../../core/Csrf.php';

/**
 * Validate CSRF token immediately
 * Return 403 Forbidden if validation fails
 */
$csrfToken = $_POST['csrf_token'] ?? '';

if (!Csrf::validateToken($csrfToken)) {
    http_response_code(403);
    error_log('CSRF validation failed in CreateBomController');
    $_SESSION['error'] = 'Invalid security token. Please refresh the page and try again.';
    header('Location: ' . BASE_URL . '/manufacturing');
    exit;
}

// ============================================================================
// 3. INPUT INTERCEPTION - Secure Variable Casting & Filtering
// ============================================================================

/**
 * Cast and validate finished product ID
 * Must be a positive integer
 */
$rawFinishedProductId = $_POST['finished_product_id'] ?? null;

if ($rawFinishedProductId === null || $rawFinishedProductId === '') {
    error_log('Missing finished_product_id in CreateBomController');
    $_SESSION['error'] = 'Finished product selection is required.';
    header('Location: ' . BASE_URL . '/manufacturing');
    exit;
}

$finishedProductId = filter_var($rawFinishedProductId, FILTER_VALIDATE_INT);

if ($finishedProductId === false || $finishedProductId <= 0) {
    error_log('Invalid finished_product_id in CreateBomController: ' . $rawFinishedProductId);
    $_SESSION['error'] = 'Invalid finished product selected. Please try again.';
    header('Location: ' . BASE_URL . '/manufacturing');
    exit;
}

/**
 * Sanitize and validate BOM name
 * Must be a non-empty string
 */
$rawBomName = $_POST['bom_name'] ?? '';
$bomName = htmlspecialchars(trim($rawBomName), ENT_QUOTES, 'UTF-8');

if ($bomName === '') {
    error_log('Missing or empty bom_name in CreateBomController');
    $_SESSION['error'] = 'BOM name is required.';
    header('Location: ' . BASE_URL . '/manufacturing');
    exit;
}

if (strlen($bomName) > 255) {
    error_log('BOM name exceeds maximum length in CreateBomController: ' . strlen($bomName));
    $_SESSION['error'] = 'BOM name must not exceed 255 characters.';
    header('Location: ' . BASE_URL . '/manufacturing');
    exit;
}

/**
 * Extract and validate components array
 * Must be an array of positive integers
 */
$rawComponents = $_POST['components'] ?? [];

if (!is_array($rawComponents) || empty($rawComponents)) {
    error_log('Missing or invalid components array in CreateBomController');
    $_SESSION['error'] = 'At least one component is required for the BOM.';
    header('Location: ' . BASE_URL . '/manufacturing');
    exit;
}

$components = [];
foreach ($rawComponents as $rawComponent) {
    $componentId = filter_var($rawComponent, FILTER_VALIDATE_INT);
    
    if ($componentId === false || $componentId <= 0) {
        error_log('Invalid component ID in CreateBomController: ' . $rawComponent);
        $_SESSION['error'] = 'Invalid component selected. Please verify all components.';
        header('Location: ' . BASE_URL . '/manufacturing');
        exit;
    }
    
    $components[] = $componentId;
}

/**
 * Extract and validate quantities array
 * Must be an array of positive floats matching components count
 */
$rawQuantities = $_POST['quantities'] ?? [];

if (!is_array($rawQuantities) || count($rawQuantities) !== count($components)) {
    error_log('Quantities array mismatch in CreateBomController - Components: ' . count($components) . ', Quantities: ' . count($rawQuantities));
    $_SESSION['error'] = 'Component quantities mismatch. Please verify all quantities are provided.';
    header('Location: ' . BASE_URL . '/manufacturing');
    exit;
}

$quantities = [];
foreach ($rawQuantities as $index => $rawQuantity) {
    $quantity = filter_var($rawQuantity, FILTER_VALIDATE_FLOAT);
    
    if ($quantity === false || $quantity <= 0) {
        error_log('Invalid quantity in CreateBomController at index ' . $index . ': ' . $rawQuantity);
        $_SESSION['error'] = 'All component quantities must be positive numbers greater than zero.';
        header('Location: ' . BASE_URL . '/manufacturing');
        exit;
    }
    
    // Validate reasonable quantity limits (prevent abuse)
    if ($quantity > 999999.9999) {
        error_log('Excessive quantity in CreateBomController at index ' . $index . ': ' . $quantity);
        $_SESSION['error'] = 'Component quantity exceeds maximum limit.';
        header('Location: ' . BASE_URL . '/manufacturing');
        exit;
    }
    
    $quantities[] = $quantity;
}

// ============================================================================
// 4. EXECUTION BLOCK - Isolated Try-Catch System
// ============================================================================

try {
    // Initialize Manufacturing Model
    require_once __DIR__ . '/../models/ManufacturingModel.php';
    $manufacturingModel = new ManufacturingModel();
    
    /**
     * Execute BOM creation with transaction handling
     * 
     * Creates BOM header record and inserts all component line items
     * with their required quantities in a single atomic transaction
     */
    $result = $manufacturingModel->createBillOfMaterials(
        $finishedProductId,
        $bomName,
        $components,
        $quantities
    );
    
    if ($result['success']) {
        // Success - Set success message with BOM details
        $_SESSION['success'] = sprintf(
            'Bill of Materials created successfully! BOM Code: %s | Product: %s | Components: %d',
            htmlspecialchars($result['data']['bom_code'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($result['data']['product_name'], ENT_QUOTES, 'UTF-8'),
            $result['data']['component_count']
        );
        
        // Log successful operation
        error_log(sprintf(
            'BOM created - Code: %s, Product ID: %d, Components: %d',
            $result['data']['bom_code'],
            $finishedProductId,
            $result['data']['component_count']
        ));
    } else {
        // Business logic error (e.g., duplicate BOM, invalid product)
        $_SESSION['error'] = 'BOM creation failed: ' . htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
        
        // Log business logic failure
        error_log('BOM creation failed - Product ID: ' . $finishedProductId . ' - Error: ' . $result['message']);
    }
    
} catch (PDOException $e) {
    /**
     * Database Error Handler
     * Log detailed error and show user-friendly message
     */
    error_log('BOM Creation Database Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
    $_SESSION['error'] = 'Database error occurred. The BOM was not created. Please contact system administrator.';
    
} catch (Exception $e) {
    /**
     * General Error Handler
     * Log detailed error and show user-friendly message
     */
    error_log('BOM Creation System Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
    $_SESSION['error'] = 'An unexpected error occurred. The BOM was not created. Please try again or contact support.';
}

// ============================================================================
// 5. REDIRECT - Return to Manufacturing Dashboard
// ============================================================================

/**
 * Redirect back to manufacturing module dashboard
 * Flash messages will be displayed via session
 */
header('Location: ' . BASE_URL . '/manufacturing');
exit;

// Made with Bob