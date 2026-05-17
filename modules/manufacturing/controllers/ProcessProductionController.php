<?php

declare(strict_types=1);

/**
 * Process Production Controller
 * 
 * Handles production run operations with strict security controls:
 * - Starting new production runs (material consumption)
 * - Completing production runs (finished goods creation)
 * 
 * Security Features:
 * - POST-only request validation (405 Method Not Allowed)
 * - CSRF token validation (403 Forbidden on failure)
 * - Input sanitization and type casting
 * - Transaction-based error handling
 * - Comprehensive error logging
 * 
 * @package AmenERP\Modules\Manufacturing\Controllers
 * @author Security-Focused Systems Architect
 * @version 1.0.0
 */

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
 * Validate CSRF token immediately
 * Return 403 Forbidden if validation fails
 */
$csrfToken = $_POST['csrf_token'] ?? '';

if (!Csrf::validateToken($csrfToken)) {
    http_response_code(403);
    error_log('CSRF validation failed in ProcessProductionController');
    $_SESSION['error'] = 'Invalid security token. Please refresh the page and try again.';
    header('Location: ' . BASE_URL . '/manufacturing');
    exit;
}

// ============================================================================
// 3. INPUT INTERCEPTION - Secure Variable Casting
// ============================================================================

/**
 * Extract and validate action parameter
 * Valid actions: 'start' or 'complete'
 */
$action = strtolower(trim($_POST['action'] ?? ''));

if (!in_array($action, ['start', 'complete'], true)) {
    error_log('Invalid action parameter in ProcessProductionController: ' . $action);
    $_SESSION['error'] = 'Invalid operation requested. Please try again.';
    header('Location: ' . BASE_URL . '/manufacturing');
    exit;
}

/**
 * Cast and validate BOM ID (for 'start' action)
 * Must be a positive integer
 */
$bomId = null;
if ($action === 'start') {
    $rawBomId = $_POST['bom_id'] ?? null;
    
    if ($rawBomId !== null && $rawBomId !== '') {
        $bomId = filter_var($rawBomId, FILTER_VALIDATE_INT);
        
        if ($bomId === false || $bomId <= 0) {
            error_log('Invalid BOM ID in ProcessProductionController: ' . $rawBomId);
            $_SESSION['error'] = 'Invalid Bill of Materials selected. Please try again.';
            header('Location: ' . BASE_URL . '/manufacturing');
            exit;
        }
    } else {
        $_SESSION['error'] = 'Bill of Materials selection is required to start production.';
        header('Location: ' . BASE_URL . '/manufacturing');
        exit;
    }
}

/**
 * Cast and validate quantity (for 'start' action)
 * Must be a positive integer
 */
$quantity = null;
if ($action === 'start') {
    $rawQuantity = $_POST['quantity'] ?? null;
    
    if ($rawQuantity !== null && $rawQuantity !== '') {
        $quantity = filter_var($rawQuantity, FILTER_VALIDATE_INT);
        
        if ($quantity === false || $quantity <= 0) {
            error_log('Invalid quantity in ProcessProductionController: ' . $rawQuantity);
            $_SESSION['error'] = 'Production quantity must be a positive number.';
            header('Location: ' . BASE_URL . '/manufacturing');
            exit;
        }
        
        // Validate reasonable quantity limits (prevent abuse)
        if ($quantity > 10000) {
            error_log('Excessive quantity requested in ProcessProductionController: ' . $quantity);
            $_SESSION['error'] = 'Production quantity exceeds maximum limit of 10,000 units per run.';
            header('Location: ' . BASE_URL . '/manufacturing');
            exit;
        }
    } else {
        $_SESSION['error'] = 'Production quantity is required.';
        header('Location: ' . BASE_URL . '/manufacturing');
        exit;
    }
}

/**
 * Cast and validate run ID (for 'complete' action)
 * Must be a positive integer
 */
$runId = null;
if ($action === 'complete') {
    $rawRunId = $_POST['run_id'] ?? null;
    
    if ($rawRunId !== null && $rawRunId !== '') {
        $runId = filter_var($rawRunId, FILTER_VALIDATE_INT);
        
        if ($runId === false || $runId <= 0) {
            error_log('Invalid run ID in ProcessProductionController: ' . $rawRunId);
            $_SESSION['error'] = 'Invalid production run selected. Please try again.';
            header('Location: ' . BASE_URL . '/manufacturing');
            exit;
        }
    } else {
        $_SESSION['error'] = 'Production run selection is required to complete production.';
        header('Location: ' . BASE_URL . '/manufacturing');
        exit;
    }
}

// ============================================================================
// 4. EXECUTION BLOCK - Isolated Try-Catch System
// ============================================================================

try {
    // Initialize Manufacturing Model
    require_once MODULES_PATH . '/manufacturing/models/ManufacturingModel.php';
    $manufacturingModel = new ManufacturingModel();
    
    /**
     * Route to appropriate action handler
     */
    if ($action === 'start') {
        /**
         * START PRODUCTION RUN
         * 
         * Validates inventory, deducts raw materials, creates production run,
         * and records financial journal entries (Raw Materials → WIP)
         */
        $result = $manufacturingModel->startProductionRun($bomId, $quantity);
        
        if ($result['success']) {
            // Success - Set success message with batch details
            $_SESSION['success'] = sprintf(
                'Production run started successfully! Batch: %s | Quantity: %d units | Status: In Progress',
                htmlspecialchars($result['data']['batch_number'], ENT_QUOTES, 'UTF-8'),
                $result['data']['quantity_to_produce']
            );
            
            // Log successful operation
            error_log(sprintf(
                'Production run started - Batch: %s, BOM ID: %d, Quantity: %d',
                $result['data']['batch_number'],
                $bomId,
                $quantity
            ));
        } else {
            // Business logic error (e.g., insufficient materials, invalid BOM)
            $_SESSION['error'] = 'Production start failed: ' . htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
            
            // Log business logic failure
            error_log('Production start failed - BOM ID: ' . $bomId . ' - Error: ' . $result['message']);
        }
        
    } elseif ($action === 'complete') {
        /**
         * COMPLETE PRODUCTION RUN
         * 
         * Validates run status, increments finished goods inventory,
         * updates run status, and records financial journal entries (WIP → Finished Goods)
         */
        $result = $manufacturingModel->completeProductionRun($runId);
        
        if ($result['success']) {
            // Success - Set success message with completion details
            $_SESSION['success'] = sprintf(
                'Production run completed successfully! Batch: %s | Product: %s | Quantity: %d units',
                htmlspecialchars($result['data']['batch_number'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($result['data']['product_name'], ENT_QUOTES, 'UTF-8'),
                $result['data']['quantity_produced']
            );
            
            // Log successful operation
            error_log(sprintf(
                'Production run completed - Batch: %s, Run ID: %d, Quantity: %d',
                $result['data']['batch_number'],
                $runId,
                $result['data']['quantity_produced']
            ));
        } else {
            // Business logic error (e.g., invalid status, run not found)
            $_SESSION['error'] = 'Production completion failed: ' . htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8');
            
            // Log business logic failure
            error_log('Production completion failed - Run ID: ' . $runId . ' - Error: ' . $result['message']);
        }
    }
    
} catch (PDOException $e) {
    /**
     * Database Error Handler
     * Log detailed error and show user-friendly message
     */
    error_log('Manufacturing Database Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
    $_SESSION['error'] = 'Database error occurred. The operation was not completed. Please contact system administrator.';
    
} catch (Exception $e) {
    /**
     * General Error Handler
     * Log detailed error and show user-friendly message
     */
    error_log('Manufacturing System Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
    $_SESSION['error'] = 'An unexpected error occurred. The operation was not completed. Please try again or contact support.';
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