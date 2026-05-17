# AmenERP - Enterprise Resource Planning System

## System Overview & Architectural Philosophy

AmenERP is a **zero-framework, pure PHP 8.x enterprise resource planning system** built with absolute architectural discipline and security-first principles. This application represents a complete rejection of bloated frameworks, external dependencies, and third-party platforms in favor of raw, performant, maintainable code.

### Core Architectural Principles

#### 1. Framework-Independent Philosophy

AmenERP is built using **only native PHP 8.x capabilities** with zero external dependencies:

- **No Frameworks**: No Laravel, Symfony, CodeIgniter, or any PHP framework
- **No CMS Platforms**: No WordPress, Drupal, Joomla, or similar platforms
- **No External Libraries**: No Composer packages, no vendor dependencies
- **Pure PDO**: Native PHP Data Objects for database operations
- **Vanilla JavaScript**: Zero JavaScript frameworks (no React, Vue, Angular, jQuery)
- **Native MySQL**: Direct MySQL/MariaDB with no ORM abstraction layers

**Why This Matters:**
- **Performance**: No framework overhead, no autoloader bloat, no unnecessary abstractions
- **Security**: Complete control over every line of code, no hidden vulnerabilities in dependencies
- **Maintainability**: No framework version upgrades, no breaking changes from external sources
- **Longevity**: Code remains functional indefinitely without dependency rot
- **Learning**: Developers understand actual PHP, not framework-specific patterns

#### 2. Security-First Core Design Rules

Every aspect of AmenERP implements defense-in-depth security:

##### Strict Type Safety
```php
declare(strict_types=1);
```
- **Enforced on every PHP file** without exception
- Prevents type coercion vulnerabilities
- Catches type-related bugs at runtime
- Ensures predictable behavior across all operations

##### Native Session Hardening
```php
// In config/config.php
ini_set('session.cookie_httponly', '1');      // Prevent JavaScript access
ini_set('session.cookie_secure', '1');        // HTTPS only (production)
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
ini_set('session.use_strict_mode', '1');      // Reject uninitialized session IDs
ini_set('session.use_only_cookies', '1');     // No URL-based sessions
```

##### Universal XSS Prevention
```php
// Absolute output scrubbing on ALL user-generated content
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```
- **Every output is sanitized** - no exceptions
- `ENT_QUOTES` protects both single and double quotes
- `UTF-8` encoding prevents charset-based attacks
- Applied to database reads, form displays, and API responses

##### Anti-CSRF Token Protection
```php
// Generated via cryptographically secure random bytes
$csrfToken = Csrf::generateToken();

// Validated on every state-changing operation
if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
    die('Invalid security token');
}
```
- **Required on all POST/PUT/DELETE operations**
- Tokens stored in server-side sessions
- Automatic token rotation after validation
- Prevents cross-site request forgery attacks

##### SQL Injection Prevention
```php
// All queries use PDO prepared statements
$stmt = $db->query($sql, ['id' => $productId, 'qty' => $quantity]);
```
- **Zero raw SQL concatenation** anywhere in the codebase
- All parameters bound via PDO placeholders
- Database class enforces prepared statement usage
- No dynamic query building without parameterization

##### Input Validation Layers
1. **Client-Side**: HTML5 validation attributes (type, min, max, pattern, required)
2. **Server-Side**: PHP validation with type casting and range checks
3. **Database-Side**: Column constraints, foreign keys, and triggers

#### 3. ACID-Compliant Transaction Management

All multi-step operations use database transactions:

```php
$db->beginTransaction();
try {
    // Step 1: Validate
    // Step 2: Modify data
    // Step 3: Verify integrity
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}
```

**Guarantees:**
- **Atomicity**: All operations succeed or all fail
- **Consistency**: Database constraints always enforced
- **Isolation**: Concurrent transactions don't interfere
- **Durability**: Committed data persists through crashes

## Global System Directory Layout

```
AmenERP/
├── LICENSE                                 # MIT License
├── README.md                               # This file - System documentation
├── project-rules.md                        # Development standards and conventions
├── schema.sql                              # Complete database schema
├── setup-unix.sh                           # Unix/Linux/macOS setup script
├── setup-windows.ps1                       # Windows PowerShell setup script
│
├── config/
│   └── config.php                          # System configuration
│       ├── Database credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS)
│       ├── Application constants (APP_NAME, APP_VERSION, BASE_URL)
│       ├── Path constants (CORE_PATH, MODULES_PATH, PUBLIC_PATH)
│       └── Session security configuration
│
├── core/                                   # Core system classes
│   ├── Database.php                        # Singleton PDO connection manager
│   │   ├── getInstance(): Database         # Get singleton instance
│   │   ├── query($sql, $params): PDOStatement
│   │   ├── beginTransaction(): void
│   │   ├── commit(): void
│   │   ├── rollback(): void
│   │   └── lastInsertId(): string
│   │
│   ├── Router.php                          # URL routing engine
│   │   ├── get($path, $file): void         # Register GET route
│   │   ├── post($path, $file): void        # Register POST route
│   │   └── dispatch(): void                # Execute matched route
│   │
│   └── Csrf.php                            # CSRF token management
│       ├── generateToken(): string         # Create secure token
│       └── validateToken($token): bool     # Verify token validity
│
├── modules/                                # Business logic modules
│   │
│   ├── home/                               # Dashboard module
│   │   ├── index.php                       # Main dashboard view
│   │   ├── ROUTING.md                      # Route documentation
│   │   ├── INTEGRATION_GUIDE.md            # Integration instructions
│   │   └── models/
│   │       └── DashboardModel.php          # Dashboard data aggregation
│   │
│   ├── sales/                              # Sales & invoicing module
│   │   ├── index.php                       # Sales dashboard view
│   │   ├── database/
│   │   │   └── schema.sql                  # Sales tables (sales_orders, sales_items)
│   │   ├── models/
│   │   │   └── SalesModel.php              # Sales business logic
│   │   ├── controllers/
│   │   │   ├── ProcessSaleController.php   # Sale creation handler
│   │   │   └── ViewInvoiceController.php   # Invoice display handler
│   │   └── docs/
│   │       ├── README.md                   # Module documentation
│   │       ├── ROUTING.md                  # Route configuration
│   │       └── INTEGRATION_GUIDE.md        # Integration guide
│   │
│   ├── inventory/                          # Inventory management module
│   │   ├── index.php                       # Inventory dashboard view
│   │   ├── add.php                         # Add product form (legacy)
│   │   ├── database/
│   │   │   └── schema.sql                  # Inventory tables (products, categories)
│   │   ├── models/
│   │   │   └── InventoryModel.php          # Inventory business logic
│   │   ├── controllers/
│   │   │   ├── AddProductController.php    # Product creation handler
│   │   │   ├── EditProductController.php   # Product update handler
│   │   │   └── DeleteProductController.php # Product deletion handler
│   │   ├── api/
│   │   │   ├── search.php                  # Product search API
│   │   │   └── check-sku.php               # SKU validation API
│   │   └── docs/
│   │       ├── README.md                   # Module documentation
│   │       ├── ROUTING.md                  # Route configuration
│   │       └── INTEGRATION_GUIDE.md        # Integration guide
│   │
│   ├── finance/                            # Finance & accounting module
│   │   ├── index.php                       # Finance dashboard view
│   │   ├── database/
│   │   │   └── schema.sql                  # Finance tables (accounts, transactions, ledger_entries)
│   │   ├── models/
│   │   │   ├── FinanceModel.php            # Double-entry accounting logic
│   │   │   └── ReportModel.php             # Financial reporting (future)
│   │   ├── controllers/
│   │   │   ├── RecordIncomeController.php  # Income transaction handler
│   │   │   └── RecordExpenseController.php # Expense transaction handler
│   │   └── docs/
│   │       ├── README.md                   # Module documentation
│   │       ├── ROUTING.md                  # Route configuration
│   │       └── INTEGRATION_GUIDE.md        # Integration guide
│   │
│   ├── procurement/                        # Procurement & purchasing module
│   │   ├── index.php                       # Procurement dashboard view
│   │   ├── database/
│   │   │   ├── schema.sql                  # Procurement tables (purchase_orders, purchase_order_items)
│   │   │   └── migration_add_supplier_integration.sql
│   │   ├── models/
│   │   │   └── ProcurementModel.php        # Procurement business logic
│   │   └── controllers/
│   │       └── ProcessPurchaseController.php # Purchase order handler
│   │
│   ├── hr/                                 # Human resources & payroll module
│   │   ├── index.php                       # HR dashboard view
│   │   ├── database/
│   │   │   └── schema.sql                  # HR tables (employees, payroll_runs, payroll_items)
│   │   ├── models/
│   │   │   └── HRModel.php                 # HR business logic
│   │   └── controllers/
│   │       └── ProcessPayrollController.php # Payroll processing handler
│   │
│   ├── customers/                          # Customer accounts & receivables module
│   │   ├── index.php                       # Customer dashboard view
│   │   ├── database/
│   │   │   └── schema.sql                  # Customer tables (customers, customer_transactions)
│   │   ├── models/
│   │   │   └── CustomerModel.php           # Customer business logic
│   │   └── controllers/
│   │       └── ProcessPaymentController.php # Customer payment handler
│   │
│   ├── suppliers/                          # Supplier management & payables module
│   │   ├── index.php                       # Supplier dashboard view
│   │   ├── database/
│   │   │   ├── schema.sql                  # Supplier tables (suppliers, supplier_transactions)
│   │   │   └── migration_add_supplier_to_products.sql
│   │   ├── models/
│   │   │   └── SupplierModel.php           # Supplier business logic
│   │   └── controllers/
│   │       └── ProcessPaymentController.php # Supplier payment handler
│   │
│   └── manufacturing/                      # Manufacturing & production module
│       ├── index.php                       # Manufacturing dashboard view
│       ├── database/
│       │   └── schema.sql                  # Manufacturing tables (boms, bom_items, production_runs)
│       ├── models/
│       │   └── ManufacturingModel.php      # Manufacturing business logic
│       └── controllers/
│           ├── CreateBomController.php     # Bill of materials creation handler
│           └── ProcessProductionController.php # Production run handler
│
└── public/                                 # Web-accessible directory (document root)
    ├── .htaccess                           # Apache rewrite rules
    ├── index.php                           # Front controller (application entry point)
    │   ├── Loads config/config.php
    │   ├── Starts secure session
    │   ├── Registers SPL autoloader
    │   ├── Initializes Router
    │   ├── Registers all module routes
    │   ├── Dispatches request
    │   └── Renders master layout with module content
    │
    └── assets/                             # Static assets
        ├── css/                            # Stylesheets
        │   ├── design-tokens.css           # CSS variables (colors, spacing, typography)
        │   ├── main-layout.css             # Layout grid, sidebar, navigation
        │   ├── components.css              # Reusable UI components
        │   ├── dashboard.css               # Dashboard-specific styles
        │   ├── main.css                    # Legacy global styles
        │   └── style.css                   # Additional legacy styles
        │
        └── js/                             # JavaScript
            ├── app.js                      # Global application logic
            ├── main.js                     # Legacy global scripts
            ├── hr-payroll.js               # HR module scripts
            └── modules/                    # Module-specific scripts
                └── procurement-form.js     # Procurement form logic
```

## Database Architecture

### Core Database Design Principles

1. **InnoDB Engine**: All tables use InnoDB for ACID compliance and foreign key support
2. **UTF-8MB4 Charset**: Full Unicode support including emojis and special characters
3. **Timestamp Tracking**: Every table has `created_at` and `updated_at` columns
4. **Foreign Key Constraints**: Referential integrity enforced at database level
5. **Strategic Indexing**: Indexes on foreign keys, search fields, and filter columns

### Database Schema Overview

```sql
-- Core Tables
categories              -- Product categorization
products                -- Master product inventory (SKU, quantity, pricing)
accounts                -- Chart of accounts (assets, liabilities, equity, income, expenses)
transactions            -- Financial transaction headers
ledger_entries          -- Double-entry ledger lines

-- Sales Module
sales_orders            -- Sales order headers (invoice_number, customer_name, total_amount)
sales_items             -- Sales order line items (product_id, quantity, unit_price)

-- Procurement Module
purchase_orders         -- Purchase order headers (po_number, supplier_id, total_amount)
purchase_order_items    -- Purchase order line items (product_id, quantity, unit_price)

-- HR Module
employees               -- Employee master data (name, position, salary)
payroll_runs            -- Payroll batch headers (period, total_amount)
payroll_items           -- Individual employee payroll entries

-- Customer Module
customers               -- Customer master data (name, email, credit_limit)
customer_transactions   -- Customer account activity (invoices, payments)

-- Supplier Module
suppliers               -- Supplier master data (name, email, payment_terms)
supplier_transactions   -- Supplier account activity (purchases, payments)

-- Manufacturing Module
boms                    -- Bill of materials headers (finished_product_id)
bom_items               -- BOM line items (raw_material_id, quantity)
production_runs         -- Production batch records (bom_id, quantity_produced)
```

### Key Foreign Key Relationships

```
products.category_id → categories.id
sales_items.sales_order_id → sales_orders.id
sales_items.product_id → products.id
ledger_entries.transaction_id → transactions.id
ledger_entries.account_id → accounts.id
purchase_order_items.purchase_order_id → purchase_orders.id
purchase_order_items.product_id → products.id
payroll_items.payroll_run_id → payroll_runs.id
payroll_items.employee_id → employees.id
bom_items.bom_id → boms.id
bom_items.product_id → products.id
production_runs.bom_id → boms.id
```

## Installation & Setup

### Prerequisites

- **PHP 8.0 or higher** with extensions:
  - PDO
  - pdo_mysql
  - mbstring
  - session
- **MySQL 5.7+ or MariaDB 10.3+**
- **Apache 2.4+ or Nginx** with URL rewriting enabled
- **Git** (for cloning repository)

### Quick Start

#### Option 1: Automated Setup (Recommended)

**For Unix/Linux/macOS:**
```bash
# Clone repository
git clone https://github.com/yourusername/AmenERP.git
cd AmenERP

# Run setup script
chmod +x setup-unix.sh
./setup-unix.sh
```

**For Windows:**
```powershell
# Clone repository
git clone https://github.com/yourusername/AmenERP.git
cd AmenERP

# Run setup script
powershell -ExecutionPolicy Bypass -File setup-windows.ps1
```

#### Option 2: Manual Setup

**Step 1: Clone Repository**
```bash
git clone https://github.com/yourusername/AmenERP.git
cd AmenERP
```

**Step 2: Configure Database**

Edit `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'amenerp_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

**Step 3: Create Database**
```bash
mysql -u root -p -e "CREATE DATABASE amenerp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**Step 4: Import Schema**
```bash
mysql -u root -p amenerp_db < schema.sql
```

**Step 5: Import Module Schemas**
```bash
mysql -u root -p amenerp_db < modules/sales/database/schema.sql
mysql -u root -p amenerp_db < modules/inventory/database/schema.sql
mysql -u root -p amenerp_db < modules/finance/database/schema.sql
mysql -u root -p amenerp_db < modules/procurement/database/schema.sql
mysql -u root -p amenerp_db < modules/hr/database/schema.sql
mysql -u root -p amenerp_db < modules/customers/database/schema.sql
mysql -u root -p amenerp_db < modules/suppliers/database/schema.sql
mysql -u root -p amenerp_db < modules/manufacturing/database/schema.sql
```

**Step 6: Configure Web Server**

**Apache (.htaccess already included):**
```apache
<VirtualHost *:80>
    ServerName amenerp.local
    DocumentRoot /path/to/AmenERP/public
    
    <Directory /path/to/AmenERP/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx:**
```nginx
server {
    listen 80;
    server_name amenerp.local;
    root /path/to/AmenERP/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

**Step 7: Set Permissions**
```bash
# Unix/Linux/macOS
chmod -R 755 AmenERP/
chmod -R 775 AmenERP/public/assets/

# Ensure web server can write to session directory
sudo chown -R www-data:www-data /var/lib/php/sessions
```

**Step 8: Access Application**

Navigate to: `http://amenerp.local` or `http://localhost/AmenERP/public`

## Module Documentation

Each module includes comprehensive documentation:

### Sales Module
- **README.md**: Complete module overview, database schema, API reference
- **ROUTING.md**: Route configuration, form setup, controller patterns
- **INTEGRATION_GUIDE**: Step-by-step integration with Inventory and Finance modules

### Inventory Module
- **README.md**: Product master architecture, stock tracking, threshold logic
- **ROUTING.md**: CRUD routes, API endpoints, SKU validation
- **INTEGRATION_GUIDE**: Stock reservation patterns, race condition prevention

### Finance Module
- **README.md**: Double-entry bookkeeping, chart of accounts, transaction balancing
- **ROUTING.md**: Income/expense recording, ledger audit views
- **INTEGRATION_GUIDE**: Journal entry recording, account integration patterns

### Other Modules
Documentation for Procurement, HR, Customers, Suppliers, and Manufacturing modules is available in their respective `docs/` directories.

## Routing System

### Route Registration

Routes are registered in `public/index.php` using the Router class:

```php
$router = new Router();

// Static routes
$router->get('/inventory', 'inventory/index.php');
$router->post('/inventory/add', 'inventory/controllers/AddProductController.php');

// Dynamic routes with parameters
$router->get('/sales/orders/{id}', 'sales/controllers/ViewInvoiceController.php');
$router->post('/inventory/edit/{id}', 'inventory/controllers/EditProductController.php');
```

### Route Priority Rules

**CRITICAL**: Routes must be registered in this order to prevent conflicts:

1. **Static GET paths** (e.g., `/inventory`, `/finance`)
2. **Static POST paths** (e.g., `/inventory/add`, `/finance/record-income`)
3. **API endpoints** (e.g., `/api/inventory/search`)
4. **Dynamic parameter paths** (e.g., `/inventory/{id}`, `/sales/orders/{id}`)

**Why**: The router matches routes in registration order. If dynamic routes are registered first, they may incorrectly match static paths.

### Parameter Extraction

Controllers receive route parameters via the `$params` array:

```php
// Route: /sales/orders/{id}
// URL: /sales/orders/123

// In controller:
$orderId = (int)($params['id'] ?? 0);
```

## Security Best Practices

### 1. CSRF Protection

**Every state-changing form must include a CSRF token:**

```php
// Generate token
$csrfToken = Csrf::generateToken();

// Include in form
<form method="POST" action="/inventory/add">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <!-- Other fields -->
</form>

// Validate in controller
if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid security token';
    header('Location: ' . BASE_URL . '/inventory');
    exit;
}
```

### 2. Input Validation

**Always validate and sanitize user input:**

```php
// Validate
$productName = trim($_POST['name'] ?? '');
if (strlen($productName) < 2 || strlen($productName) > 100) {
    throw new Exception('Product name must be 2-100 characters');
}

// Sanitize for output
echo htmlspecialchars($productName, ENT_QUOTES, 'UTF-8');

// Type cast for database
$quantity = (int)($_POST['quantity'] ?? 0);
$price = (float)($_POST['unit_price'] ?? 0);
```

### 3. SQL Injection Prevention

**Always use prepared statements:**

```php
// ✅ CORRECT
$sql = "SELECT * FROM products WHERE id = :id AND status = :status";
$stmt = $db->query($sql, ['id' => $productId, 'status' => 'active']);

// ❌ INCORRECT - Never concatenate user input
$sql = "SELECT * FROM products WHERE id = $productId"; // DANGEROUS!
```

### 4. XSS Prevention

**Always escape output:**

```php
// ✅ CORRECT
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// ❌ INCORRECT - Never output raw user data
echo $userInput; // DANGEROUS!
```

### 5. Session Security

**Session configuration in config/config.php:**

```php
ini_set('session.cookie_httponly', '1');      // Prevent JavaScript access
ini_set('session.cookie_secure', '1');        // HTTPS only (production)
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
ini_set('session.use_strict_mode', '1');      // Reject uninitialized IDs
ini_set('session.use_only_cookies', '1');     // No URL-based sessions
```

## Development Guidelines

### Code Standards

1. **Strict Types**: Every PHP file must start with `declare(strict_types=1);`
2. **Type Hints**: Use type hints for all function parameters and return types
3. **Documentation**: PHPDoc blocks for all classes and public methods
4. **Naming Conventions**:
   - Classes: PascalCase (e.g., `SalesModel`, `InventoryController`)
   - Methods: camelCase (e.g., `createSalesOrder`, `getProductById`)
   - Variables: camelCase (e.g., `$productId`, `$totalAmount`)
   - Constants: UPPER_SNAKE_CASE (e.g., `DB_HOST`, `BASE_URL`)
5. **File Organization**: One class per file, filename matches class name

### Database Transaction Pattern

**All multi-step operations must use transactions:**

```php
try {
    $db->beginTransaction();
    
    // Step 1: Validate
    // Step 2: Modify data
    // Step 3: Verify integrity
    
    $db->commit();
    
    return ['success' => true];
    
} catch (Exception $e) {
    $db->rollback();
    
    return [
        'success' => false,
        'message' => $e->getMessage()
    ];
}
```

### Error Handling

**Use try-catch blocks and provide user-friendly messages:**

```php
try {
    $result = $model->performOperation($data);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Operation completed successfully';
    } else {
        $_SESSION['error'] = $result['message'];
    }
    
} catch (Exception $e) {
    error_log($e->getMessage()); // Log technical details
    $_SESSION['error'] = 'An error occurred. Please try again.'; // User-friendly message
}

header('Location: ' . BASE_URL . '/module');
exit;
```

### Flash Messages

**Display one-time messages using session variables:**

```php
// Set message
$_SESSION['success'] = 'Product added successfully';
$_SESSION['error'] = 'Failed to add product';

// Display in view
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8');
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>
```

## Module Integration Patterns

### Inventory Integration

**Other modules must verify stock availability before transactions:**

```php
// Load InventoryModel
require_once __DIR__ . '/../../inventory/models/InventoryModel.php';
$inventoryModel = new InventoryModel();

// Phase 1: Pre-transaction validation
$product = $inventoryModel->getProductById($productId);
if ($product['quantity'] < $requestedQuantity) {
    throw new Exception('Insufficient stock');
}

// Phase 2: Update stock within transaction
$db->beginTransaction();
$sql = "UPDATE products SET quantity = quantity - :qty WHERE id = :id";
$db->query($sql, ['qty' => $requestedQuantity, 'id' => $productId]);

// Phase 3: Post-transaction verification
$sql = "SELECT quantity FROM products WHERE id = :id";
$result = $db->query($sql, ['id' => $productId])->fetch();
if ($result['quantity'] < 0) {
    throw new Exception('Stock went negative - race condition detected');
}

$db->commit();
```

### Finance Integration

**All monetary transactions must record double-entry journal entries:**

```php
// Load FinanceModel
require_once __DIR__ . '/../../finance/models/FinanceModel.php';
$financeModel = new FinanceModel();

// Record transaction
$result = $financeModel->addSimpleTransaction(
    'Sales Invoice: INV-2026-0001',
    3,  // FROM: General Sales Income (income account)
    1,  // TO: Cash Safe (asset account)
    1000.00,
    date('Y-m-d')
);

if (!$result) {
    throw new Exception('Failed to record financial transaction');
}
```

## Performance Optimization

### Database Optimization

1. **Use Indexes**: All foreign keys and frequently queried columns are indexed
2. **Limit Result Sets**: Use `LIMIT` clauses for pagination
3. **Avoid N+1 Queries**: Use JOINs to fetch related data in single query
4. **Cache Aggregations**: Store calculated values (e.g., account balances) for quick access

### Application Optimization

1. **Output Buffering**: Capture module output for layout injection
2. **Singleton Pattern**: Database connection reused across requests
3. **Lazy Loading**: Load models only when needed
4. **Minimal Dependencies**: No framework overhead

### Frontend Optimization

1. **CSS Variables**: Centralized design tokens for consistency
2. **Vanilla JavaScript**: No framework overhead
3. **Minimal HTTP Requests**: Consolidated CSS and JS files
4. **Semantic HTML**: Proper structure for accessibility and SEO

## Testing

### Manual Testing Checklist

- [ ] All forms submit successfully
- [ ] CSRF tokens validate correctly
- [ ] Input validation catches invalid data
- [ ] Database transactions rollback on errors
- [ ] Flash messages display correctly
- [ ] Routes resolve to correct controllers
- [ ] Foreign key constraints prevent orphaned records
- [ ] Account balances update correctly
- [ ] Stock levels update correctly
- [ ] Concurrent operations don't cause race conditions

### Database Integrity Checks

```sql
-- Verify all transactions balance
SELECT 
    transaction_id,
    SUM(amount) as balance
FROM ledger_entries
GROUP BY transaction_id
HAVING SUM(amount) != 0;
-- Should return no rows

-- Verify account balances match ledger
SELECT 
    a.id,
    a.name,
    a.balance as cached_balance,
    COALESCE(SUM(le.amount), 0) as calculated_balance,
    a.balance - COALESCE(SUM(le.amount), 0) as difference
FROM accounts a
LEFT JOIN ledger_entries le ON a.id = le.account_id
GROUP BY a.id, a.name, a.balance
HAVING ABS(a.balance - COALESCE(SUM(le.amount), 0)) > 0.01;
-- Should return no rows

-- Check for negative stock
SELECT id, name, sku, quantity
FROM products
WHERE quantity < 0;
-- Should return no rows
```

## Troubleshooting

### Common Issues

#### "Route not found" Error
**Cause**: Route not registered or incorrect path  
**Solution**: Check route registration in `public/index.php`, verify path matches exactly

#### "Invalid security token" Error
**Cause**: CSRF token missing or expired  
**Solution**: Ensure form includes `csrf_token` field, check session is active

#### "Database connection failed" Error
**Cause**: Incorrect database credentials or server not running  
**Solution**: Verify credentials in `config/config.php`, ensure MySQL is running

#### "Class not found" Error
**Cause**: Autoloader can't find class file  
**Solution**: Verify class file exists in `core/` directory, filename matches class name

#### "Transaction failed to balance" Error
**Cause**: Double-entry accounting logic error  
**Solution**: Check `addSimpleTransaction()` calls, verify debit/credit amounts match

## Contributing

### Development Workflow

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Follow code standards and security guidelines
4. Test thoroughly
5. Commit changes (`git commit -m 'Add amazing feature'`)
6. Push to branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Code Review Checklist

- [ ] Strict types declared
- [ ] Type hints on all functions
- [ ] CSRF protection on forms
- [ ] Input validation and sanitization
- [ ] Output escaping with htmlspecialchars()
- [ ] Prepared statements for SQL
- [ ] Database transactions for multi-step operations
- [ ] Error handling with try-catch
- [ ] PHPDoc comments
- [ ] No external dependencies added

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For issues, questions, or contributions:

- **Documentation**: Review module-specific docs in `modules/*/docs/`
- **Issues**: Submit via GitHub Issues
- **Discussions**: Use GitHub Discussions for questions

## Acknowledgments

- Built with pure PHP 8.x and native MySQL
- Zero external dependencies by design
- Security-first architecture
- ACID-compliant transaction management
- Double-entry bookkeeping implementation

---

**Made with Bob** - A framework-free, dependency-free, pure PHP enterprise system.

**Version**: 1.0.0  
**Last Updated**: 2026-05-17  
**PHP Version**: 8.0+  
**Database**: MySQL 5.7+ / MariaDB 10.3+
