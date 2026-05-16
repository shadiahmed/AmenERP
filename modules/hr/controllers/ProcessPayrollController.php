<?php
declare(strict_types=1);

/**
 * AmenERP HR & Payroll - Process Payroll Controller
 * 
 * Secure form submission handler for monthly payroll processing.
 * Implements strict security controls including CSRF validation,
 * HTTP method enforcement, and comprehensive input sanitization.
 * 
 * @package AmenERP
 * @subpackage HR
 * @author Security Systems Architect
 * @version 1.0.0
 * @created 2026-05-16
 */

// Start session for flash messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bootstrap core configurations
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Csrf.php';
require_once __DIR__ . '/../models/HRModel.php';

/**
 * Security Layer 1: HTTP Method Enforcement
 * Hard-block all non-POST requests with HTTP 405 Method Not Allowed
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Method Not Allowed',
        'message' => 'This endpoint only accepts POST requests.'
    ]);
    exit;
}

/**
 * Security Layer 2: CSRF Token Validation
 * Validate incoming CSRF token against session token
 * Terminate with HTTP 403 Forbidden on mismatch
 */
$csrfToken = $_POST['csrf_token'] ?? '';

if (!Csrf::validateToken($csrfToken)) {
    http_response_code(403);
    $_SESSION['error'] = 'Security validation failed. Invalid CSRF token. Please refresh the page and try again.';
    error_log('CSRF validation failed in ProcessPayrollController from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    header('Location: ' . BASE_URL . '/hr');
    exit;
}

/**
 * Security Layer 3: Input Sanitization and Validation
 */
try {
    // Extract and sanitize payroll month
    $payrollMonth = filter_var(
        $_POST['payroll_month'] ?? '',
        FILTER_SANITIZE_FULL_SPECIAL_CHARS
    );

    // Validate payroll month format (YYYY-MM)
    if (empty($payrollMonth) || !preg_match('/^\d{4}-\d{2}$/', $payrollMonth)) {
        throw new Exception('Invalid payroll month format. Expected YYYY-MM (e.g., 2026-05).');
    }

    // Extract and sanitize employee adjustments
    $allowances = $_POST['allowances'] ?? [];
    $deductions = $_POST['deductions'] ?? [];

    // Build adjustments array with sanitized values
    $adjustments = [];

    // Process allowances
    if (is_array($allowances)) {
        foreach ($allowances as $employeeId => $allowanceValue) {
            $employeeId = filter_var($employeeId, FILTER_VALIDATE_INT);
            $allowanceValue = filter_var($allowanceValue, FILTER_VALIDATE_FLOAT);

            if ($employeeId !== false && $allowanceValue !== false) {
                if (!isset($adjustments[$employeeId])) {
                    $adjustments[$employeeId] = [
                        'allowances' => 0.00,
                        'deductions' => 0.00
                    ];
                }
                $adjustments[$employeeId]['allowances'] = max(0.00, $allowanceValue);
            }
        }
    }

    // Process deductions
    if (is_array($deductions)) {
        foreach ($deductions as $employeeId => $deductionValue) {
            $employeeId = filter_var($employeeId, FILTER_VALIDATE_INT);
            $deductionValue = filter_var($deductionValue, FILTER_VALIDATE_FLOAT);

            if ($employeeId !== false && $deductionValue !== false) {
                if (!isset($adjustments[$employeeId])) {
                    $adjustments[$employeeId] = [
                        'allowances' => 0.00,
                        'deductions' => 0.00
                    ];
                }
                $adjustments[$employeeId]['deductions'] = max(0.00, $deductionValue);
            }
        }
    }

    /**
     * Business Logic Execution
     * Instantiate HRModel and process payroll within try-catch scope
     */
    $hrModel = new HRModel();
    $result = $hrModel->processMonthlyPayroll($payrollMonth, $adjustments);

    // Handle result
    if ($result['success']) {
        $_SESSION['success'] = $result['message'] . ' Total paid: $' . number_format($result['total_net_paid'], 2) . 
                               ' to ' . $result['employees_processed'] . ' employees.';
        
        // Log successful payroll processing
        error_log(
            "Payroll processed successfully: Month={$payrollMonth}, " .
            "Total={$result['total_net_paid']}, " .
            "Employees={$result['employees_processed']}, " .
            "RunID={$result['payroll_run_id']}"
        );
    } else {
        $_SESSION['error'] = $result['message'];
        
        // Log payroll processing failure
        error_log(
            "Payroll processing failed: Month={$payrollMonth}, " .
            "Error={$result['message']}"
        );
    }

} catch (Exception $e) {
    // Catch any unexpected errors
    $_SESSION['error'] = 'An unexpected error occurred while processing payroll: ' . htmlspecialchars($e->getMessage());
    
    // Log exception details
    error_log(
        'Exception in ProcessPayrollController: ' . $e->getMessage() . 
        ' | File: ' . $e->getFile() . 
        ' | Line: ' . $e->getLine()
    );
} catch (PDOException $e) {
    // Catch database-specific errors
    $_SESSION['error'] = 'Database error occurred. Please contact system administrator.';
    
    // Log database exception (don't expose details to user)
    error_log(
        'Database exception in ProcessPayrollController: ' . $e->getMessage() . 
        ' | Code: ' . $e->getCode()
    );
}

/**
 * Final Step: HTTP Redirect
 * Redirect back to HR dashboard with session messages
 */
header('Location: ' . BASE_URL . '/hr');
exit;

// Made with Bob
