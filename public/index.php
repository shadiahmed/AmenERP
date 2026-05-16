<?php

declare(strict_types=1);

/**
 * AmenERP - Front Controller (Entry Point)
 * 
 * This is the single entry point for all HTTP requests to the AmenERP system.
 * It initializes the application, sets up routing, and renders the master layout.
 * 
 * Architecture:
 * - Zero Framework: Pure PHP 8.x with no external dependencies
 * - Secure Session: HTTPOnly, SameSite cookies
 * - Autoloading: SPL autoloader for core classes
 * - Output Buffering: Captures module output for layout injection
 * - Master Layout: Semantic HTML5 with CSS Grid/Flexbox
 * 
 * @package AmenERP
 * @author Bob
 * @version 1.0.0
 */

// ============================================================================
// 1. SYSTEM INITIALIZATION
// ============================================================================

/**
 * Load application configuration FIRST
 * This defines all constants: DB_*, APP_*, paths (CORE_PATH, MODULES_PATH, etc.)
 * IMPORTANT: Must be loaded before session_start() because config.php sets session ini settings
 */
require_once __DIR__ . '/../config/config.php';

/**
 * Start secure session with strict security settings
 * Session configuration has been set in config.php via ini_set
 * Must be called AFTER config.php is loaded
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Register SPL autoloader for core classes
 * Automatically loads classes from the CORE_PATH directory when instantiated
 * 
 * @param string $className The name of the class to load
 */
spl_autoload_register(function (string $className): void {
    $classFile = CORE_PATH . '/' . $className . '.php';
    
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// ============================================================================
// 2. ROUTING SETUP
// ============================================================================

/**
 * Instantiate the Router
 * The Router class handles URL pattern matching and dispatches to module controllers
 */
$router = new Router();

/**
 * Register GET routes
 * Routes point to module view files relative to MODULES_PATH
 */
$router->get('/', 'home/index.php');           // Dashboard overview
$router->get('/home', 'home/index.php');      // Dashboard alias for internal navigation
$router->get('/inventory', 'inventory/index.php'); // Inventory management
$router->post('/inventory/add', 'inventory/controllers/AddProductController.php'); // Add new product
$router->post('/inventory/edit/{id}', 'inventory/controllers/EditProductController.php'); // Edit existing product
$router->post('/inventory/delete/{id}', 'inventory/controllers/DeleteProductController.php'); // Delete product
$router->get('/api/inventory/search', 'inventory/api/search.php'); // Inventory search API
$router->get('/api/inventory/check-sku', 'inventory/api/check-sku.php'); // SKU availability check API

// Finance Module Routes
$router->get('/finance', 'finance/index.php'); // Finance dashboard
$router->post('/finance/record-income', 'finance/controllers/RecordIncomeController.php'); // Record income transaction
$router->post('/finance/record-expense', 'finance/controllers/RecordExpenseController.php'); // Record expense transaction

// Sales Module Routes
$router->get('/sales', 'sales/index.php'); // Main sales dashboard
$router->post('/sales/checkout', 'sales/controllers/ProcessSaleController.php'); // Process sale checkout
$router->get('/sales/orders/{id}', 'sales/controllers/ViewInvoiceController.php'); // View specific invoice

// Procurement Module Routes
$router->get('/procurement', 'procurement/index.php'); // Main procurement dashboard
$router->get('/procurement/orders', 'procurement/controllers/ProcessPurchaseController.php'); // View all procurement orders
$router->get('/procurement/orders/{id}', 'procurement/controllers/ProcessPurchaseController.php'); // View specific procurement order
$router->post('/procurement/orders', 'procurement/controllers/ProcessPurchaseController.php'); // Place a new procurement order
$router->post('/procurement/orders/{id}', 'procurement/controllers/ProcessPurchaseController.php'); // Place a new procurement order

// HR & PAYROLL MODULE ROUTES
$router->get('/hr', 'hr/index.php'); // Main HR dashboard
$router->get('/hr/employees', 'hr/controllers/EmployeesController.php'); // View all employees
$router->get('/hr/employees/new', 'hr/controllers/EmployeesController.php'); // Create new employee
$router->get('/hr/employees/{id}', 'hr/controllers/EmployeesController.php'); // View specific employee
$router->post('/hr/employees', 'hr/controllers/EmployeesController.php'); // Create new employee
$router->post('/hr/employees/{id}', 'hr/controllers/EmployeesController.php'); // Update employee
$router->get('/hr/payroll', 'hr/controllers/PayrollController.php'); // View payroll
$router->get('/hr/payroll/new', 'hr/controllers/PayrollController.php'); // Create new payroll
$router->get('/hr/payroll/{id}', 'hr/controllers/PayrollController.php'); // View specific payroll

$router->post('/hr/payroll/process', 'hr/controllers/ProcessPayrollController.php'); // Process payroll

// B2B CUSTOMER ACCOUNTS & RECEIVABLES ROUTES
$router->get('/customers', 'customers/index.php'); // View all customers
$router->post('/customers/payment/process', 'customers/controllers/ProcessPaymentController.php'); // Process payment
$router->post('/customers/process-payment', 'customers/controllers/ProcessPaymentController.php'); // Process payment alias for customer form

$router->get('/suppliers', 'suppliers/index.php'); // View all suppliers
$router->post('/suppliers/payment/process', 'suppliers/controllers/ProcessPaymentController.php'); // Process payment
$router->post('/suppliers/process-payment', 'suppliers/controllers/ProcessPaymentController.php'); // Process payment alias for supplier form



// ============================================================================
// 3. OUTPUT BUFFERING EXECUTION
// ============================================================================

/**
 * Start output buffering to capture module output
 * This allows the router to process module view files silently,
 * capturing their output into the $content variable for layout injection
 */
ob_start();

/**
 * Dispatch the current request to the appropriate module
 * The router will include the matched module file, which may echo content
 */
$router->dispatch();

/**
 * Capture the buffered output from the module
 * This content will be injected into the main layout below
 */
$content = ob_get_clean();

// ============================================================================
// 4. HTML5 MASTER LAYOUT FRAME (ZERO-FRAMEWORK)
// ============================================================================

/**
 * Determine the current page title based on the request URI
 * This provides context-aware titles for better UX and SEO
 */
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$pageTitle = APP_NAME;

if (strpos($requestUri, '/inventory') !== false) {
    $pageTitle = 'Inventory Management - ' . APP_NAME;
} elseif (strpos($requestUri, '/finance') !== false) {
    $pageTitle = 'Finance Dashboard - ' . APP_NAME;
} elseif ($requestUri === '/' || strpos($requestUri, '/home') !== false) {
    $pageTitle = 'Dashboard - ' . APP_NAME;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?> - Enterprise Resource Planning System">
    <meta name="author" content="Bob">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    
    <!-- Global Stylesheet (Zero-Framework CSS) -->
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/css/dashboard.css">

</head>
<body>
    <!-- Enterprise Admin Interface Layout Grid -->
    <div class="erp-layout">
        
        <!-- Left Sidebar Navigation Panel -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1 class="sidebar-logo"><?php echo htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="sidebar-version">v<?php echo htmlspecialchars(APP_VERSION, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/" 
                           class="nav-link <?php echo ($requestUri === '/' || strpos($requestUri, '/home') !== false) ? 'active' : ''; ?>">
                            <span class="nav-icon">📊</span>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/inventory"
                           class="nav-link <?php echo (strpos($requestUri, '/inventory') !== false) ? 'active' : ''; ?>">
                            <span class="nav-icon">📦</span>
                            <span class="nav-text">Inventory</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/finance"
                           class="nav-link <?php echo (strpos($requestUri, '/finance') !== false) ? 'active' : ''; ?>">
                            <span class="nav-icon">💰</span>
                            <span class="nav-text">Finance</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/sales"
                           class="nav-link <?php echo (strpos($requestUri, '/sales') !== false) ? 'active' : ''; ?>">
                            <span class="nav-icon">💰</span>
                            <span class="nav-text">Sales</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/procurement"
                           class="nav-link <?php echo (strpos($requestUri, '/procurement') !== false) ? 'active' : ''; ?>">
                            <span class="nav-icon">💰</span>
                            <span class="nav-text">Procurement</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/hr"
                           class="nav-link <?php echo (strpos($requestUri, '/hr') !== false) ? 'active' : ''; ?>">
                            <span class="nav-icon">💰</span>
                            <span class="nav-text">HR</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/customers"
                           class="nav-link <?php echo (strpos($requestUri, '/customers') !== false) ? 'active' : ''; ?>">
                            <span class="nav-icon">💰</span>
                            <span class="nav-text">Customers</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/suppliers"
                           class="nav-link <?php echo (strpos($requestUri, '/suppliers') !== false) ? 'active' : ''; ?>">
                            <span class="nav-icon">💰</span>
                            <span class="nav-text">suppliers</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/settings"
                           class="nav-link <?php echo (strpos($requestUri, '/settings') !== false) ? 'active' : ''; ?>">
                            <span class="nav-icon">💰</span>
                            <span class="nav-text">Settings</span>
                        </a>
                    </li>


                </ul>
            </nav>
        </aside>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Mobile Menu Toggle (only visible on mobile) -->
            <button id="mobileMenuToggle" class="mobile-menu-toggle" aria-label="Toggle menu">☰</button>
            
            <?php 
            /**
             * Inject the captured module content
             * The $content variable contains the output from the dispatched module
             * No additional escaping needed as modules are trusted internal code
             */
            echo $content; 
            ?>
        </main>
        
    </div>
    
    <!-- Global JavaScript (Zero-Framework ES6+) -->
    <script src="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/js/main.js" type="module"></script>
    <script src="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/js/hr-payroll.js" type="module"></script>
</body>
</html>
<?php
// Made with Bob
