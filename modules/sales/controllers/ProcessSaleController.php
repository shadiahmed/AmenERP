<?php

declare(strict_types=1);

/**
 * Create Sale Controller
 * 
 * Processes sales order form submissions with automated multi-module integration:
 * - Creates sales order and line items
 * - Updates inventory stock levels
 * - Records financial transactions via FinanceModel
 * 
 * Security:
 * - CSRF token validation (403 Forbidden on failure)
 * - Input sanitization and type casting
 * - Stock availability validation
 * - Transaction rollback on any error
 * 
 * @package AmenERP\Modules\Sales\Controllers
 * @author Bob
 * @version 1.0.0
 */

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

// ============================================================================
// 1. SECURITY LAYER - CSRF Token Validation
// ============================================================================

/**
 * Validate CSRF token immediately
 * Throws 403 Forbidden if validation fails
 */
$csrfToken = $_POST['csrf_token'] ?? '';

if (!Csrf::validateToken($csrfToken)) {
    http_response_code(403);
    $_SESSION['error'] = 'Invalid security token. Please try again.';
    header('Location: ' . BASE_URL . '/sales');
    exit;
}

// ============================================================================
// 2. DATA COLLECTION - Sanitize and Cast Input Parameters
// ============================================================================

/**
 * Sanitize customer name
 * Use htmlspecialchars to prevent XSS attacks
 */
$customerName = htmlspecialchars(trim($_POST['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8');

/**
 * Collect and sanitize sale items array
 * Each item should have: product_id, quantity, unit_price
 */
$rawItems = $_POST['items'] ?? [];
$items = [];

foreach ($rawItems as $rawItem) {
    // Sanitize and validate each item
    $productId = filter_var($rawItem['product_id'] ?? 0, FILTER_VALIDATE_INT);
    $quantity = filter_var($rawItem['quantity'] ?? 0, FILTER_VALIDATE_INT);
    $unitPrice = filter_var($rawItem['unit_price'] ?? 0, FILTER_VALIDATE_FLOAT);
    
    // Skip invalid items
    if ($productId === false || $productId <= 0) {
        continue;
    }
    
    if ($quantity === false || $quantity <= 0) {
        continue;
    }
    
    if ($unitPrice === false || $unitPrice <= 0) {
        continue;
    }
    
    // Add validated item to array
    $items[] = [
        'product_id' => $productId,
        'quantity' => $quantity,
        'unit_price' => $unitPrice
    ];
}

// ============================================================================
// 3. INPUT VALIDATION - Strict Parameter Checks
// ============================================================================

/**
 * Validate customer name is not empty
 */
if (empty($customerName)) {
    $_SESSION['error'] = 'Customer name is required. Please provide a customer name.';
    header('Location: ' . BASE_URL . '/sales');
    exit;
}

/**
 * Validate customer name length
 */
if (strlen($customerName) < 2 || strlen($customerName) > 255) {
    $_SESSION['error'] = 'Customer name must be between 2 and 255 characters.';
    header('Location: ' . BASE_URL . '/sales');
    exit;
}

/**
 * Validate that we have at least one item
 */
if (empty($items)) {
    $_SESSION['error'] = 'Please add at least one product to the sale.';
    header('Location: ' . BASE_URL . '/sales');
    exit;
}

/**
 * Validate maximum number of items (prevent abuse)
 */
if (count($items) > 50) {
    $_SESSION['error'] = 'Maximum 50 items per sale. Please split into multiple orders.';
    header('Location: ' . BASE_URL . '/sales');
    exit;
}

// ============================================================================
// 4. EXECUTION - Process Sale with Multi-Module Integration
// ============================================================================

try {
    // Initialize Sales Model
    require_once MODULES_PATH . '/sales/models/SalesModel.php';
    $salesModel = new SalesModel();
    
    /**
     * Create sales order with automated integration
     * 
     * This will:
     * - Insert sales_order and sales_items records
     * - Update inventory stock levels
     * - Record financial transaction
     * - All wrapped in a database transaction
     * 
     * Parameters:
     * - $customerName: Customer or buyer name
     * - $items: Array of items with product_id, quantity, unit_price
     * - $cashAccountId: Optional cash account (defaults to Account ID 1)
     */
    $result = $salesModel->createSalesOrder(
        $customerName,
        $items,
        null // Use default cash account (ID 1)
    );
    
    if ($result['success']) {
        // Success - Set success message with invoice details
        $_SESSION['success'] = sprintf(
            'Sale completed successfully! Invoice: %s | Total: $%s',
            htmlspecialchars($result['invoice_number'], ENT_QUOTES, 'UTF-8'),
            number_format($result['total_amount'], 2)
        );
    } else {
        // Business logic error (e.g., insufficient stock)
        $_SESSION['error'] = 'Sale failed: ' . htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
    }
    
} catch (PDOException $e) {
    // Database error - Log and show user-friendly message
    error_log('Sales Transaction Error: ' . $e->getMessage());
    $_SESSION['error'] = 'Database error occurred. The sale was not completed. Please try again.';
} catch (Exception $e) {
    // General error - Log and show user-friendly message
    error_log('Sales Transaction Error: ' . $e->getMessage());
    $_SESSION['error'] = 'An unexpected error occurred. The sale was not completed. Please try again.';
}

// ============================================================================
// 5. REDIRECT - Return to Sales Dashboard
// ============================================================================

header('Location: ' . BASE_URL . '/sales');
exit;

// Made with Bob