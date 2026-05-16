<?php

declare(strict_types=1);

/**
 * Process Payment Controller (Vendor/Supplier Payments)
 * 
 * Handles vendor payment processing with strict security enforcement and
 * comprehensive validation. Implements ACID-compliant transactions through
 * the SupplierModel with automated multi-module integration:
 * - Updates supplier outstanding balances (what we OWE them)
 * - Records payment disbursements
 * - Creates financial transactions (double-entry bookkeeping)
 * - Updates account balances in the general ledger
 * 
 * Security Features:
 * - HTTP 405 Method Not Allowed for non-POST requests
 * - CSRF token validation (403 Forbidden on failure)
 * - Input sanitization and strict type casting
 * - Payment amount validation against outstanding balance
 * - Transaction rollback on any error
 * 
 * @package AmenERP\Modules\Suppliers\Controllers
 * @author Bob
 * @version 1.0.0
 */

// ============================================================================
// 0. BOOTSTRAP - Load Core Configuration and Dependencies
// ============================================================================

/**
 * Load application configuration
 * Defines BASE_URL, database credentials, and security settings
 */
require_once __DIR__ . '/../../../config/config.php';

/**
 * Load CSRF protection class
 * Provides token generation and validation for form security
 */
require_once CORE_PATH . '/Csrf.php';

/**
 * Load Database class
 * Singleton pattern for PDO database connections
 */
require_once CORE_PATH . '/Database.php';

/**
 * Start session for flash messages and CSRF token storage
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================================
// 1. HTTP METHOD VALIDATION - Block Non-POST Requests
// ============================================================================

/**
 * Enforce POST-only access
 * Returns HTTP 405 Method Not Allowed for GET, PUT, DELETE, etc.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    die('HTTP 405 Method Not Allowed - This endpoint only accepts POST requests');
}

// ============================================================================
// 2. SECURITY LAYER - CSRF Token Validation
// ============================================================================

/**
 * Extract CSRF token from POST data
 * Token should be included as a hidden field in the payment form
 */
$csrfToken = $_POST['csrf_token'] ?? '';

/**
 * Validate CSRF token against session token
 * Throws HTTP 403 Forbidden if validation fails
 */
if (!Csrf::validateToken($csrfToken)) {
    http_response_code(403);
    $_SESSION['error'] = 'Security validation failed. Invalid CSRF token. Please refresh the page and try again.';
    header('Location: ' . BASE_URL . '/suppliers');
    exit;
}

// ============================================================================
// 3. DATA COLLECTION - Sanitize and Cast Input Parameters
// ============================================================================

/**
 * Extract and validate supplier_id (INT)
 * Must be a positive integer
 */
$supplierId = filter_var($_POST['supplier_id'] ?? 0, FILTER_VALIDATE_INT);

if ($supplierId === false || $supplierId <= 0) {
    $_SESSION['error'] = 'Invalid supplier ID. Please select a valid supplier.';
    header('Location: ' . BASE_URL . '/suppliers');
    exit;
}

/**
 * Extract and validate amount_paid (FLOAT)
 * Must be a positive decimal number
 */
$amountPaid = filter_var($_POST['amount_paid'] ?? 0, FILTER_VALIDATE_FLOAT);

if ($amountPaid === false || $amountPaid <= 0) {
    $_SESSION['error'] = 'Invalid payment amount. Amount must be greater than zero.';
    header('Location: ' . BASE_URL . '/suppliers');
    exit;
}

/**
 * Sanitize payment_method (VARCHAR/String)
 * Remove HTML tags and special characters to prevent XSS
 */
$paymentMethod = htmlspecialchars(trim($_POST['payment_method'] ?? ''), ENT_QUOTES, 'UTF-8');

if (empty($paymentMethod)) {
    $_SESSION['error'] = 'Payment method is required. Please select a payment method.';
    header('Location: ' . BASE_URL . '/suppliers');
    exit;
}

/**
 * Validate payment method against allowed values
 * Prevents injection of arbitrary payment types
 */
$allowedMethods = ['cash', 'check', 'wire_transfer', 'bank_transfer', 'credit_card', 'debit_card', 'ach', 'online_payment'];

if (!in_array($paymentMethod, $allowedMethods, true)) {
    $_SESSION['error'] = 'Invalid payment method selected. Please choose a valid payment method.';
    header('Location: ' . BASE_URL . '/suppliers');
    exit;
}

/**
 * Optional: Sanitize payment reference (check number, wire confirmation, etc.)
 */
$paymentReference = isset($_POST['payment_reference']) && !empty(trim($_POST['payment_reference']))
    ? htmlspecialchars(trim($_POST['payment_reference']), ENT_QUOTES, 'UTF-8')
    : null;

/**
 * Optional: Sanitize payment notes
 */
$paymentNotes = isset($_POST['payment_notes']) && !empty(trim($_POST['payment_notes']))
    ? htmlspecialchars(trim($_POST['payment_notes']), ENT_QUOTES, 'UTF-8')
    : null;

// ============================================================================
// 4. INPUT VALIDATION - Additional Business Logic Checks
// ============================================================================

/**
 * Validate payment amount precision (max 2 decimal places)
 * Prevents floating-point precision issues
 */
if (round($amountPaid, 2) !== $amountPaid) {
    $_SESSION['error'] = 'Payment amount can only have up to 2 decimal places.';
    header('Location: ' . BASE_URL . '/suppliers');
    exit;
}

/**
 * Validate payment amount maximum (prevent abuse)
 * Set reasonable upper limit for single payment
 */
if ($amountPaid > 10000000.00) {
    $_SESSION['error'] = 'Payment amount exceeds maximum allowed limit of $10,000,000.00.';
    header('Location: ' . BASE_URL . '/suppliers');
    exit;
}

/**
 * Validate payment reference length if provided
 */
if ($paymentReference !== null && strlen($paymentReference) > 100) {
    $_SESSION['error'] = 'Payment reference is too long. Maximum 100 characters allowed.';
    header('Location: ' . BASE_URL . '/suppliers');
    exit;
}

/**
 * Validate payment notes length if provided
 */
if ($paymentNotes !== null && strlen($paymentNotes) > 500) {
    $_SESSION['error'] = 'Payment notes are too long. Maximum 500 characters allowed.';
    header('Location: ' . BASE_URL . '/suppliers');
    exit;
}

// ============================================================================
// 5. EXECUTION - Process Payment with Transactional Integrity
// ============================================================================

try {
    /**
     * Load SupplierModel for payment processing
     * Model handles all database operations with ACID compliance
     */
    require_once MODULES_PATH . '/suppliers/models/SupplierModel.php';
    $supplierModel = new SupplierModel();
    
    /**
     * Execute payment settlement with full transactional support
     * 
     * This method will:
     * - Validate payment amount against outstanding balance
     * - Update supplier outstanding_balance (reduce what we owe)
     * - Insert payment disbursement record for audit trail
     * - Create financial transaction in general ledger
     * - Update account balances (Cash and Accounts Payable)
     * - All operations wrapped in database transaction
     * 
     * Parameters:
     * @param int $supplierId - Supplier receiving the payment
     * @param float $amountPaid - Payment amount disbursed
     * @param string $paymentMethod - Payment method used
     * @param string|null $paymentReference - Optional payment reference
     * @param string|null $paymentNotes - Optional payment notes
     * 
     * @return array Result with success status and details
     */
    $result = $supplierModel->settleVendorBalance(
        supplierId: $supplierId,
        amount: $amountPaid,
        method: $paymentMethod,
        reference: $paymentReference,
        notes: $paymentNotes
    );
    
    /**
     * Process result and set appropriate flash message
     */
    if ($result['success']) {
        // Success - Payment processed successfully
        $_SESSION['success'] = sprintf(
            'Payment processed successfully! Disbursement: %s | Supplier: %s | Amount: $%s | New Balance: $%s',
            htmlspecialchars($result['disbursement_number'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($result['supplier_name'], ENT_QUOTES, 'UTF-8'),
            number_format($result['amount_paid'], 2),
            number_format($result['new_balance'], 2)
        );
    } else {
        // Business logic error (e.g., payment exceeds balance, supplier not found)
        $_SESSION['error'] = 'Payment processing failed: ' . htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
        
        // Log the error for debugging
        error_log(sprintf(
            'Vendor Payment Failed - Supplier ID: %d, Amount: %.2f, Reason: %s',
            $supplierId,
            $amountPaid,
            $result['message']
        ));
    }
    
} catch (PDOException $e) {
    /**
     * Database error - Log detailed error and show user-friendly message
     * Never expose database structure or query details to users
     */
    error_log(sprintf(
        'Vendor Payment Database Error - Supplier ID: %d, Amount: %.2f, Error: %s',
        $supplierId,
        $amountPaid,
        $e->getMessage()
    ));
    
    $_SESSION['error'] = 'Database error occurred. The payment was not processed. Please contact system administrator.';
    
} catch (Exception $e) {
    /**
     * General error - Log and show user-friendly message
     * Catch any unexpected errors to prevent application crash
     */
    error_log(sprintf(
        'Vendor Payment System Error - Supplier ID: %d, Amount: %.2f, Error: %s',
        $supplierId,
        $amountPaid,
        $e->getMessage()
    ));
    
    $_SESSION['error'] = 'An unexpected error occurred. The payment was not processed. Please try again.';
}

// ============================================================================
// 6. REDIRECT - Return to Supplier Management Dashboard
// ============================================================================

/**
 * Redirect back to suppliers dashboard
 * Flash messages will be displayed on the next page load
 */
header('Location: ' . BASE_URL . '/suppliers');
exit;

// Made with Bob