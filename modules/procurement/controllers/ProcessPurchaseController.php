<?php

declare(strict_types=1);

/**
 * ProcessPurchaseController
 * 
 * Handles the submission and processing of purchase orders.
 * Implements strict security measures including:
 * - HTTP method validation (POST only)
 * - CSRF token validation
 * - Input sanitization and validation
 * - Transaction-based database operations
 * 
 * @package AmenERP\Modules\Procurement\Controllers
 * @author Bob
 * @version 1.0.0
 */

// Start session for flash messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bootstrap core application
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Csrf.php';
require_once __DIR__ . '/../models/ProcurementModel.php';
require_once __DIR__ . '/../../suppliers/models/SupplierModel.php';

// ============================================================================
// SECURITY: Block non-POST requests with HTTP 405 Method Not Allowed
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    $_SESSION['error'] = 'Invalid request method. Only POST requests are allowed.';
    header('Location: ' . BASE_URL . '/procurement');
    exit;
}

// ============================================================================
// SECURITY: Validate CSRF token
// ============================================================================
$csrfToken = $_POST['csrf_token'] ?? '';

if (!Csrf::validateToken($csrfToken)) {
    http_response_code(403);
    $_SESSION['error'] = 'Security validation failed. Please refresh the page and try again.';
    error_log('CSRF validation failed in ProcessPurchaseController');
    header('Location: ' . BASE_URL . '/procurement');
    exit;
}

// ============================================================================
// INPUT SANITIZATION AND VALIDATION
// ============================================================================

// Sanitize payment type (cash or credit)
$paymentType = isset($_POST['payment_type'])
    ? strtolower(trim($_POST['payment_type']))
    : 'cash';

// Validate payment type
if (!in_array($paymentType, ['cash', 'credit'], true)) {
    $_SESSION['error'] = 'Invalid payment type. Must be either "cash" or "credit".';
    header('Location: ' . BASE_URL . '/procurement');
    exit;
}

// Sanitize and validate supplier_id for credit purchases
$supplierId = null;
if ($paymentType === 'credit') {
    $supplierId = isset($_POST['supplier_id'])
        ? filter_var($_POST['supplier_id'], FILTER_VALIDATE_INT)
        : false;
    
    if ($supplierId === false || $supplierId <= 0) {
        $_SESSION['error'] = 'A valid supplier must be selected for credit purchases.';
        header('Location: ' . BASE_URL . '/procurement');
        exit;
    }
    
    // Verify supplier exists and is active
    $supplierModel = new SupplierModel();
    $supplier = $supplierModel->getSupplierById($supplierId);
    
    if (!$supplier) {
        $_SESSION['error'] = 'Selected supplier not found. Please select a valid supplier.';
        header('Location: ' . BASE_URL . '/procurement');
        exit;
    }
    
    if ($supplier['status'] !== 'active') {
        $_SESSION['error'] = 'Cannot create credit purchase: Supplier account is ' . htmlspecialchars($supplier['status'], ENT_QUOTES, 'UTF-8') . '.';
        header('Location: ' . BASE_URL . '/procurement');
        exit;
    }
}

// Sanitize supplier name (optional for cash, required for credit if supplier_id not provided)
$supplierName = isset($_POST['supplier_name'])
    ? htmlspecialchars(trim($_POST['supplier_name']), ENT_QUOTES, 'UTF-8')
    : '';

// For credit purchases, use the supplier name from the database if available
if ($paymentType === 'credit' && $supplierId && isset($supplier)) {
    $supplierName = $supplier['supplier_name'];
} elseif (empty($supplierName)) {
    $_SESSION['error'] = 'Supplier name is required.';
    header('Location: ' . BASE_URL . '/procurement');
    exit;
}

// Validate and sanitize items array
$items = $_POST['items'] ?? [];

if (!is_array($items) || empty($items)) {
    $_SESSION['error'] = 'At least one item is required for the purchase order.';
    header('Location: ' . BASE_URL . '/procurement');
    exit;
}

// Process and validate each item
$sanitizedItems = [];
$hasValidationErrors = false;
$validationErrorMessage = '';

foreach ($items as $index => $item) {
    // Validate that index is numeric
    if (!is_numeric($index)) {
        $hasValidationErrors = true;
        $validationErrorMessage = 'Invalid item data structure.';
        break;
    }

    // Validate required fields exist
    if (!isset($item['product_id'], $item['quantity'], $item['unit_cost'])) {
        $hasValidationErrors = true;
        $validationErrorMessage = 'Missing required fields in item #' . ($index + 1);
        break;
    }

    // Sanitize and validate product_id
    $productId = filter_var($item['product_id'], FILTER_VALIDATE_INT);
    if ($productId === false || $productId <= 0) {
        $hasValidationErrors = true;
        $validationErrorMessage = 'Invalid product ID in item #' . ($index + 1);
        break;
    }

    // Sanitize and validate quantity
    $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT);
    if ($quantity === false || $quantity <= 0) {
        $hasValidationErrors = true;
        $validationErrorMessage = 'Invalid quantity in item #' . ($index + 1) . '. Must be a positive integer.';
        break;
    }

    // Sanitize and validate unit_cost
    $unitCost = filter_var($item['unit_cost'], FILTER_VALIDATE_FLOAT);
    if ($unitCost === false || $unitCost <= 0) {
        $hasValidationErrors = true;
        $validationErrorMessage = 'Invalid unit cost in item #' . ($index + 1) . '. Must be a positive number.';
        break;
    }

    // Add sanitized item to array
    $sanitizedItems[] = [
        'product_id' => $productId,
        'quantity' => $quantity,
        'unit_cost' => $unitCost
    ];
}

// Check for validation errors
if ($hasValidationErrors) {
    $_SESSION['error'] = $validationErrorMessage;
    header('Location: ' . BASE_URL . '/procurement');
    exit;
}

// ============================================================================
// DATABASE TRANSACTION PROCESSING
// ============================================================================

try {
    // Instantiate the procurement model
    $procurementModel = new ProcurementModel();

    // Execute the purchase order creation with payment type and supplier integration
    $result = $procurementModel->createPurchaseOrder(
        $supplierName,
        $sanitizedItems,
        null, // cashAccountId (use default)
        $paymentType,
        $supplierId
    );

    // Check if the operation was successful
    if ($result['success']) {
        $successMsg = sprintf(
            'Purchase order %s created successfully! Total amount: $%s',
            htmlspecialchars($result['po_number'], ENT_QUOTES, 'UTF-8'),
            number_format($result['total_amount'], 2)
        );
        
        if ($paymentType === 'credit' && isset($result['supplier_name'])) {
            $successMsg .= sprintf(
                ' (Credit purchase recorded for %s)',
                htmlspecialchars($result['supplier_name'], ENT_QUOTES, 'UTF-8')
            );
        }
        
        $_SESSION['success'] = $successMsg;
    } else {
        $_SESSION['error'] = 'Failed to create purchase order: ' . htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
        error_log('Purchase order creation failed: ' . $result['message']);
    }

} catch (PDOException $e) {
    // Log the error silently for security
    error_log('Database error in ProcessPurchaseController: ' . $e->getMessage());
    
    // Display user-friendly error message
    $_SESSION['error'] = 'A database error occurred. Please try again or contact support if the problem persists.';
    
} catch (Exception $e) {
    // Log unexpected errors
    error_log('Unexpected error in ProcessPurchaseController: ' . $e->getMessage());
    
    // Display generic error message
    $_SESSION['error'] = 'An unexpected error occurred. Please try again.';
}

// ============================================================================
// REDIRECT TO PROCUREMENT PAGE
// ============================================================================
header('Location: ' . BASE_URL . '/procurement');
exit;

// Made with Bob