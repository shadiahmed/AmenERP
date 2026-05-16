<?php

declare(strict_types=1);

/**
 * Add Product Handler
 * 
 * Processes the add product form submission with CSRF validation
 * and redirects back to inventory page with success/error message.
 * 
 * @package AmenERP\Modules\Inventory
 * @author Bob
 * @version 1.0.0
 */

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/inventory');
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !Csrf::validateToken($_POST['csrf_token'])) {
    $_SESSION['error'] = 'Invalid security token. Please try again.';
    header('Location: ' . BASE_URL . '/inventory');
    exit;
}

// Validate required fields
$requiredFields = ['name', 'sku', 'category_id', 'quantity', 'unit_price'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    $_SESSION['error'] = 'Missing required fields: ' . implode(', ', $missingFields);
    header('Location: ' . BASE_URL . '/inventory');
    exit;
}

// Sanitize and validate inputs
$name = trim($_POST['name']);
$sku = strtoupper(trim($_POST['sku']));
$categoryId = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
$quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
$unitPrice = filter_var($_POST['unit_price'], FILTER_VALIDATE_FLOAT);
$status = isset($_POST['status']) ? trim($_POST['status']) : 'active';

// Validate data types
if ($categoryId === false || $categoryId <= 0) {
    $_SESSION['error'] = 'Invalid category selected.';
    header('Location: ' . BASE_URL . '/inventory');
    exit;
}

if ($quantity === false || $quantity < 0) {
    $_SESSION['error'] = 'Invalid quantity. Must be a positive number.';
    header('Location: ' . BASE_URL . '/inventory');
    exit;
}

if ($unitPrice === false || $unitPrice < 0) {
    $_SESSION['error'] = 'Invalid unit price. Must be a positive number.';
    header('Location: ' . BASE_URL . '/inventory');
    exit;
}

// Validate status
$validStatuses = ['active', 'inactive', 'discontinued'];
if (!in_array($status, $validStatuses)) {
    $status = 'active';
}

// Validate SKU format (alphanumeric and hyphens only)
if (!preg_match('/^[A-Z0-9\-]+$/', $sku)) {
    $_SESSION['error'] = 'Invalid SKU format. Use only uppercase letters, numbers, and hyphens.';
    header('Location: ' . BASE_URL . '/inventory');
    exit;
}

// Validate name length
if (strlen($name) < 2 || strlen($name) > 100) {
    $_SESSION['error'] = 'Product name must be between 2 and 100 characters.';
    header('Location: ' . BASE_URL . '/inventory');
    exit;
}

try {
    // Get database instance
    $db = Database::getInstance();
    
    // Check if SKU already exists
    $checkSql = "SELECT id FROM products WHERE sku = :sku";
    $checkStmt = $db->query($checkSql, ['sku' => $sku]);
    
    if ($checkStmt->fetch()) {
        $_SESSION['error'] = 'SKU already exists. Please use a unique SKU.';
        header('Location: ' . BASE_URL . '/inventory');
        exit;
    }
    
    // Insert new product
    $insertSql = "
        INSERT INTO products (name, sku, category_id, quantity, unit_price, status, created_at, updated_at)
        VALUES (:name, :sku, :category_id, :quantity, :unit_price, :status, NOW(), NOW())
    ";
    
    $params = [
        'name' => $name,
        'sku' => $sku,
        'category_id' => $categoryId,
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'status' => $status
    ];
    
    $db->query($insertSql, $params);
    
    // Success message
    $_SESSION['success'] = "Product '{$name}' added successfully!";
    header('Location: ' . BASE_URL . '/inventory');
    exit;
    
} catch (PDOException $e) {
    // Log error in development mode
    if (ENVIRONMENT === 'development') {
        error_log('Add Product Error: ' . $e->getMessage());
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    } else {
        $_SESSION['error'] = 'An error occurred while adding the product. Please try again.';
    }
    
    header('Location: ' . BASE_URL . '/inventory');
    exit;
}

// Made with Bob