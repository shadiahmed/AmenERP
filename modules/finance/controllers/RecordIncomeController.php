<?php

declare(strict_types=1);

/**
 * Record Income Controller
 * 
 * Processes income transaction form submissions with double-entry bookkeeping.
 * Money flows FROM an income category account TO an asset account (Cash/Bank).
 * 
 * Security:
 * - CSRF token validation (403 Forbidden on failure)
 * - Input sanitization and type casting
 * - Amount validation (must be > 0)
 * 
 * @package AmenERP\Modules\Finance\Controllers
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
    header('Location: ' . BASE_URL . '/finance');
    exit;
}

// ============================================================================
// 2. DATA COLLECTION - Sanitize and Cast Input Parameters
// ============================================================================

/**
 * Sanitize and cast amount to float
 * Use filter_var with FILTER_VALIDATE_FLOAT for security
 */
$amount = filter_var($_POST['amount'] ?? 0, FILTER_VALIDATE_FLOAT);

/**
 * Sanitize and cast to_account_id (asset account - where money goes)
 * Use filter_var with FILTER_VALIDATE_INT for security
 */
$toAccountId = filter_var($_POST['to_account_id'] ?? 0, FILTER_VALIDATE_INT);

/**
 * Sanitize and cast from_account_id (income category account - source)
 * Use filter_var with FILTER_VALIDATE_INT for security
 */
$fromAccountId = filter_var($_POST['from_account_id'] ?? 0, FILTER_VALIDATE_INT);

/**
 * Sanitize description string
 * Use htmlspecialchars to prevent XSS attacks
 */
$description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');

/**
 * Sanitize date string
 * Validate date format (YYYY-MM-DD)
 */
$date = trim($_POST['date'] ?? '');

// ============================================================================
// 3. INPUT VALIDATION - Strict Parameter Checks
// ============================================================================

/**
 * Validate amount is greater than zero
 */
if ($amount === false || $amount <= 0) {
    $_SESSION['error'] = 'Amount must be a positive number greater than zero.';
    header('Location: ' . BASE_URL . '/finance');
    exit;
}

/**
 * Validate to_account_id (asset account)
 */
if ($toAccountId === false || $toAccountId <= 0) {
    $_SESSION['error'] = 'Please select a valid account to deposit the income.';
    header('Location: ' . BASE_URL . '/finance');
    exit;
}

/**
 * Validate from_account_id (income category)
 */
if ($fromAccountId === false || $fromAccountId <= 0) {
    $_SESSION['error'] = 'Please select a valid income source category.';
    header('Location: ' . BASE_URL . '/finance');
    exit;
}

/**
 * Validate description is not empty
 */
if (empty($description)) {
    $_SESSION['error'] = 'Description is required. Please provide a brief description of the income.';
    header('Location: ' . BASE_URL . '/finance');
    exit;
}

/**
 * Validate date format (YYYY-MM-DD)
 */
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $_SESSION['error'] = 'Invalid date format. Please use the date picker.';
    header('Location: ' . BASE_URL . '/finance');
    exit;
}

/**
 * Validate date is not in the future
 */
if (strtotime($date) > time()) {
    $_SESSION['error'] = 'Transaction date cannot be in the future.';
    header('Location: ' . BASE_URL . '/finance');
    exit;
}

// ============================================================================
// 4. EXECUTION - Process Transaction with Double-Entry Bookkeeping
// ============================================================================

try {
    // Initialize Finance Model
    require_once MODULES_PATH . '/finance/models/FinanceModel.php';
    $financeModel = new FinanceModel();
    
    /**
     * Record income transaction
     * For income: money flows FROM income category TO asset account
     * 
     * Parameters:
     * - $description: Transaction description
     * - $fromAccountId: Income category account (source)
     * - $toAccountId: Asset account (destination - Cash/Bank)
     * - $amount: Transaction amount
     * - $date: Transaction date
     */
    $result = $financeModel->addSimpleTransaction(
        $description,
        $fromAccountId,
        $toAccountId,
        $amount,
        $date
    );
    
    if ($result) {
        // Success - Set success message and redirect
        $_SESSION['success'] = 'Income recorded successfully!';
    } else {
        // Unexpected failure
        $_SESSION['error'] = 'Failed to record income. Please try again.';
    }
    
} catch (PDOException $e) {
    // Database error - Log and show user-friendly message
    error_log('Income Transaction Error: ' . $e->getMessage());
    $_SESSION['error'] = 'Database error occurred. Please contact support if the problem persists.';
} catch (Exception $e) {
    // General error - Log and show user-friendly message
    error_log('Income Transaction Error: ' . $e->getMessage());
    $_SESSION['error'] = 'An unexpected error occurred. Please try again.';
}

// ============================================================================
// 5. REDIRECT - Return to Finance Dashboard
// ============================================================================

header('Location: ' . BASE_URL . '/finance');
exit;

// Made with Bob