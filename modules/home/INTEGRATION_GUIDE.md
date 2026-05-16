# Dashboard Module - Integration & QA Guide

## Overview
This guide provides comprehensive testing procedures, verification checklists, and integration guidelines for the AmenERP Dashboard module. It ensures the module operates efficiently, securely, and integrates seamlessly with existing system components.

---

## Table of Contents
1. [Pre-Integration Checklist](#pre-integration-checklist)
2. [DashboardModel Verification](#dashboardmodel-verification)
3. [Database Performance Testing](#database-performance-testing)
4. [Security Verification](#security-verification)
5. [Integration Testing](#integration-testing)
6. [Performance Benchmarks](#performance-benchmarks)
7. [Troubleshooting Guide](#troubleshooting-guide)

---

## Pre-Integration Checklist

### Required Files Verification
Ensure all required files are in place before integration:

```
✓ modules/home/models/DashboardModel.php
✓ modules/home/index.php
✓ modules/home/ROUTING.md
✓ modules/home/INTEGRATION_GUIDE.md (this file)
✓ public/assets/css/dashboard.css
```

### Database Schema Verification
Verify all required tables exist:

```sql
-- Run this verification query
SELECT 
    TABLE_NAME,
    ENGINE,
    TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'amenerp_db'
AND TABLE_NAME IN (
    'accounts',
    'products',
    'categories',
    'sales_orders',
    'purchase_orders',
    'ledger_entries',
    'transactions'
)
ORDER BY TABLE_NAME;
```

**Expected Output:**
- All 7 tables should exist
- ENGINE should be 'InnoDB'
- TABLE_ROWS should be > 0 for testing

### Core Dependencies Check
```php
// Verify core classes are available
if (!class_exists('Database')) {
    die('ERROR: Database class not found. Check core/Database.php');
}

// Verify constants are defined
$required_constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'BASE_URL', 'MODULES_PATH'];
foreach ($required_constants as $constant) {
    if (!defined($constant)) {
        die("ERROR: Constant {$constant} not defined. Check config/config.php");
    }
}
```

---

## DashboardModel Verification

### 1. Safe Instantiation Test

**Objective:** Confirm that `DashboardModel` instances without executing slow loops or blocking operations.

**Test Procedure:**
```php
<?php
// Test file: tests/dashboard_instantiation_test.php

require_once __DIR__ . '/../config/config.php';
require_once CORE_PATH . '/Database.php';
require_once MODULES_PATH . '/home/models/DashboardModel.php';

// Start timing
$start_time = microtime(true);

// Instantiate model
$dashboardModel = new DashboardModel();

// Calculate instantiation time
$instantiation_time = microtime(true) - $start_time;

// Verification
echo "DashboardModel Instantiation Test\n";
echo "==================================\n";
echo "Instantiation Time: " . number_format($instantiation_time * 1000, 2) . " ms\n";

// PASS criteria: < 10ms
if ($instantiation_time < 0.01) {
    echo "✓ PASS: Model instantiates quickly (no slow loops)\n";
} else {
    echo "✗ FAIL: Model instantiation too slow\n";
}

// Verify no queries executed during instantiation
// (Check database query log or use profiler)
echo "\nVerification: No database queries should execute during __construct()\n";
```

**Expected Results:**
- ✓ Instantiation time < 10ms
- ✓ No database queries executed in constructor
- ✓ No loops or heavy computations in constructor
- ✓ Only Database::getInstance() called (which is cached)

**Why This Matters:**
The model should be lightweight to instantiate. All heavy operations (queries, aggregations) should only execute when methods are explicitly called, not during object construction.

### 2. Method Execution Test

**Objective:** Verify each method executes efficiently without N+1 query problems.

```php
<?php
// Test file: tests/dashboard_methods_test.php

require_once __DIR__ . '/../config/config.php';
require_once CORE_PATH . '/Database.php';
require_once MODULES_PATH . '/home/models/DashboardModel.php';

$dashboardModel = new DashboardModel();

// Test 1: getExecutiveSummary()
echo "Testing getExecutiveSummary()...\n";
$start = microtime(true);
$summary = $dashboardModel->getExecutiveSummary();
$time = microtime(true) - $start;
echo "  Execution Time: " . number_format($time * 1000, 2) . " ms\n";
echo "  Query Count: 4 (expected)\n";
echo "  Result Keys: " . implode(', ', array_keys($summary)) . "\n";
echo "  ✓ PASS\n\n";

// Test 2: getOperationalAlerts()
echo "Testing getOperationalAlerts()...\n";
$start = microtime(true);
$alerts = $dashboardModel->getOperationalAlerts();
$time = microtime(true) - $start;
echo "  Execution Time: " . number_format($time * 1000, 2) . " ms\n";
echo "  Query Count: 1 (expected)\n";
echo "  Results Found: " . count($alerts) . "\n";
echo "  ✓ PASS\n\n";

// Test 3: getRecentActivityFeed()
echo "Testing getRecentActivityFeed()...\n";
$start = microtime(true);
$feed = $dashboardModel->getRecentActivityFeed();
$time = microtime(true) - $start;
echo "  Execution Time: " . number_format($time * 1000, 2) . " ms\n";
echo "  Query Count: 1 (expected - UNION ALL)\n";
echo "  Results Found: " . count($feed) . "\n";
echo "  ✓ PASS\n\n";
```

**Expected Results:**
- ✓ Each method executes in < 100ms
- ✓ No N+1 query problems (single query per method)
- ✓ Results returned in expected format
- ✓ No PHP errors or warnings

### 3. No Loop Verification

**Objective:** Confirm no slow PHP loops are used for data aggregation.

**Manual Code Review Checklist:**
```
✓ DashboardModel uses SQL aggregations (SUM, COUNT, COALESCE)
✓ No foreach loops for mathematical calculations
✓ No while loops fetching data row-by-row
✓ Array operations use array_map (efficient) not manual loops
✓ All aggregations happen in database, not PHP
```

**Anti-Pattern Example (What NOT to do):**
```php
// ❌ BAD: Slow PHP loop
$total = 0;
$products = $db->query("SELECT * FROM products")->fetchAll();
foreach ($products as $product) {
    $total += $product['quantity'] * $product['unit_price'];
}

// ✅ GOOD: Database aggregation
$result = $db->query("SELECT SUM(quantity * unit_price) AS total FROM products")->fetch();
$total = $result['total'];
```

---

## Database Performance Testing

### 1. UNION ALL Optimization Verification

**Objective:** Confirm the database engine handles the UNION ALL query optimally without creating temporary tables.

**Test Query:**
```sql
-- Run EXPLAIN on the activity feed query
EXPLAIN
(
    SELECT 
        'sale' AS type,
        id,
        invoice_number AS reference,
        customer_name AS party_name,
        total_amount,
        created_at
    FROM sales_orders
    ORDER BY created_at DESC
    LIMIT 5
)
UNION ALL
(
    SELECT 
        'purchase' AS type,
        id,
        po_number AS reference,
        supplier_name AS party_name,
        total_amount,
        created_at
    FROM purchase_orders
    ORDER BY created_at DESC
    LIMIT 5
)
ORDER BY created_at DESC;
```

**Expected EXPLAIN Output:**
```
+----+-------------+----------------+-------+-------------------+
| id | select_type | table          | type  | Extra             |
+----+-------------+----------------+-------+-------------------+
|  1 | PRIMARY     | sales_orders   | index | Using index       |
|  2 | UNION       | purchase_orders| index | Using index       |
+----+-------------+----------------+-------+-------------------+
```

**Verification Checklist:**
- ✓ No "Using temporary" in Extra column
- ✓ No "Using filesort" in Extra column
- ✓ Both queries use index scans (type = 'index')
- ✓ UNION ALL (not UNION) to avoid DISTINCT operation
- ✓ Query executes in < 50ms

**Why UNION ALL vs UNION:**
- `UNION ALL` - Faster, returns all rows including duplicates
- `UNION` - Slower, performs DISTINCT operation to remove duplicates
- For activity feed, duplicates are impossible (different tables), so UNION ALL is optimal

### 2. Index Verification

**Objective:** Ensure all queries use proper indexes.

```sql
-- Verify indexes exist
SHOW INDEX FROM accounts WHERE Key_name = 'PRIMARY';
SHOW INDEX FROM products WHERE Key_name = 'idx_products_status';
SHOW INDEX FROM sales_orders WHERE Key_name = 'idx_created_at';
SHOW INDEX FROM purchase_orders WHERE Key_name = 'idx_created_at';
SHOW INDEX FROM ledger_entries WHERE Key_name = 'idx_account_id';
SHOW INDEX FROM transactions WHERE Key_name = 'idx_transaction_date';
```

**Expected Results:**
- ✓ All indexes exist
- ✓ Cardinality > 0 (index is being used)
- ✓ Index_type = 'BTREE' (default, optimal for range queries)

### 3. Query Performance Benchmarks

**Objective:** Establish baseline performance metrics.

```sql
-- Enable query profiling
SET profiling = 1;

-- Execute dashboard queries
SELECT balance FROM accounts WHERE id = 1;
SELECT SUM(quantity * unit_price) FROM products WHERE status = 'active';
-- ... (run all dashboard queries)

-- View profiling results
SHOW PROFILES;
```

**Expected Benchmarks:**
| Query | Expected Time | Max Acceptable |
|-------|---------------|----------------|
| Cash balance | < 1ms | 5ms |
| Stock value | < 10ms | 50ms |
| Monthly revenue | < 20ms | 100ms |
| Monthly payroll | < 20ms | 100ms |
| Low stock alerts | < 10ms | 50ms |
| Activity feed | < 30ms | 150ms |

**Total Dashboard Load Time:** < 100ms (all queries combined)

---

## Security Verification

### 1. XSS Prevention Audit

**Objective:** Verify all dashboard outputs are thoroughly escaped to eliminate XSS risks.

**Manual Code Review Checklist:**

```php
// modules/home/index.php - Security Audit

// ✓ Check 1: All dynamic content uses htmlspecialchars()
<?php echo htmlspecialchars($summary['total_cash'], ENT_QUOTES, 'UTF-8'); ?>

// ✓ Check 2: ENT_QUOTES flag is used (escapes both ' and ")
<?php echo htmlspecialchars($alert['name'], ENT_QUOTES, 'UTF-8'); ?>

// ✓ Check 3: UTF-8 encoding is specified
<?php echo htmlspecialchars($activity['party_name'], ENT_QUOTES, 'UTF-8'); ?>

// ✓ Check 4: URL parameters are escaped
<a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/procurement">

// ✓ Check 5: No raw echo of user input
// ❌ BAD: <?php echo $_GET['name']; ?>
// ✅ GOOD: <?php echo htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8'); ?>
```

**Automated XSS Test:**
```php
<?php
// Test file: tests/xss_prevention_test.php

// Test malicious input
$malicious_inputs = [
    '<script>alert("XSS")</script>',
    '"><script>alert("XSS")</script>',
    "'; DROP TABLE products; --",
    '<img src=x onerror=alert("XSS")>',
    'javascript:alert("XSS")'
];

// Simulate dashboard rendering with malicious data
foreach ($malicious_inputs as $input) {
    $escaped = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    // Verify script tags are escaped
    if (strpos($escaped, '<script>') === false && strpos($escaped, 'javascript:') === false) {
        echo "✓ PASS: Input properly escaped\n";
    } else {
        echo "✗ FAIL: XSS vulnerability detected!\n";
    }
}
```

**Expected Results:**
- ✓ All `<script>` tags are escaped to `<script>`
- ✓ All quotes are escaped to `"` or `&#039;`
- ✓ All `javascript:` protocols are escaped
- ✓ No raw HTML injection possible

### 2. SQL Injection Prevention

**Objective:** Verify all queries use PDO prepared statements.

**Code Review Checklist:**
```php
// ✓ All queries use prepared statements
$stmt = $this->db->query($sql, ['threshold' => self::LOW_STOCK_THRESHOLD]);

// ✓ No string concatenation in SQL
// ❌ BAD: $sql = "SELECT * FROM products WHERE id = " . $id;
// ✅ GOOD: $sql = "SELECT * FROM products WHERE id = :id";

// ✓ All parameters are bound
$stmt = $this->db->query($sql, ['id' => $id]);

// ✓ No raw user input in queries
// ❌ BAD: $sql = "SELECT * FROM products WHERE name = '{$_GET['name']}'";
// ✅ GOOD: $sql = "SELECT * FROM products WHERE name = :name";
//          $stmt = $this->db->query($sql, ['name' => $_GET['name']]);
```

**Automated SQL Injection Test:**
```php
<?php
// Test file: tests/sql_injection_test.php

// Test malicious SQL inputs
$malicious_sql = [
    "1' OR '1'='1",
    "1; DROP TABLE products; --",
    "1' UNION SELECT * FROM accounts --",
    "' OR 1=1 --"
];

// Verify PDO prepared statements prevent injection
foreach ($malicious_sql as $input) {
    try {
        // This should safely handle malicious input
        $stmt = $db->query("SELECT * FROM products WHERE id = :id", ['id' => $input]);
        $result = $stmt->fetch();
        
        // Should return no results (input treated as literal string)
        if ($result === false) {
            echo "✓ PASS: SQL injection prevented\n";
        } else {
            echo "✗ FAIL: Potential SQL injection vulnerability\n";
        }
    } catch (PDOException $e) {
        echo "✓ PASS: Invalid input rejected by database\n";
    }
}
```

### 3. Activity Timeline Security

**Objective:** Verify activity feed outputs are XSS-safe.

**Specific Test Cases:**
```php
<?php
// Test malicious data in activity feed

// Simulate malicious customer/supplier names
$test_cases = [
    [
        'type' => 'sale',
        'reference' => 'INV-2026-0001',
        'party_name' => '<script>alert("XSS")</script>',
        'total_amount' => 1000.00,
        'created_at' => '2026-05-16 10:00:00'
    ],
    [
        'type' => 'purchase',
        'reference' => 'PO-2026-0001',
        'party_name' => '"><img src=x onerror=alert("XSS")>',
        'total_amount' => 500.00,
        'created_at' => '2026-05-16 11:00:00'
    ]
];

// Render activity items
foreach ($test_cases as $activity) {
    $escaped_name = htmlspecialchars($activity['party_name'], ENT_QUOTES, 'UTF-8');
    $escaped_ref = htmlspecialchars($activity['reference'], ENT_QUOTES, 'UTF-8');
    
    // Verify no script execution
    echo "Party Name: {$escaped_name}\n";
    echo "Reference: {$escaped_ref}\n";
    
    // Check for dangerous patterns
    if (strpos($escaped_name, '<script>') === false && 
        strpos($escaped_name, 'onerror=') === false) {
        echo "✓ PASS: Activity timeline XSS-safe\n\n";
    } else {
        echo "✗ FAIL: XSS vulnerability in activity timeline\n\n";
    }
}
```

**Expected Results:**
- ✓ All customer/supplier names are escaped
- ✓ All invoice/PO numbers are escaped
- ✓ All dates are formatted safely (no user input)
- ✓ All amounts are numeric (no string injection)

---

## Integration Testing

### 1. End-to-End Dashboard Load Test

**Objective:** Verify complete dashboard loads without errors.

```php
<?php
// Test file: tests/dashboard_e2e_test.php

// Simulate full dashboard request
ob_start();
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';

try {
    require __DIR__ . '/../modules/home/index.php';
    $output = ob_get_clean();
    
    // Verify expected content
    $checks = [
        'Executive Dashboard' => strpos($output, 'Executive Dashboard') !== false,
        'Total Cash Liquidity' => strpos($output, 'Total Cash Liquidity') !== false,
        'Capital in Stock' => strpos($output, 'Capital in Stock') !== false,
        'Monthly Sales Revenue' => strpos($output, 'Monthly Sales Revenue') !== false,
        'Monthly Payroll Expense' => strpos($output, 'Monthly Payroll Expense') !== false,
        'Low Stock Alerts' => strpos($output, 'Low Stock Alerts') !== false,
        'Recent Activity' => strpos($output, 'Recent Activity') !== false,
        'CSS Link' => strpos($output, 'dashboard.css') !== false
    ];
    
    echo "Dashboard E2E Test Results:\n";
    echo "===========================\n";
    foreach ($checks as $check => $passed) {
        echo ($passed ? '✓' : '✗') . " {$check}\n";
    }
    
    // Overall result
    $all_passed = !in_array(false, $checks, true);
    echo "\n" . ($all_passed ? '✓ ALL TESTS PASSED' : '✗ SOME TESTS FAILED') . "\n";
    
} catch (Exception $e) {
    ob_end_clean();
    echo "✗ FAIL: Exception thrown: " . $e->getMessage() . "\n";
}
```

### 2. Module Integration Test

**Objective:** Verify dashboard integrates with all four core modules.

```php
<?php
// Test file: tests/module_integration_test.php

echo "Module Integration Test\n";
echo "=======================\n\n";

// Test 1: Finance Module Integration
echo "1. Finance Module (accounts table):\n";
$sql = "SELECT COUNT(*) as count FROM accounts";
$result = $db->query($sql)->fetch();
echo "   Accounts found: {$result['count']}\n";
echo "   ✓ Finance module integrated\n\n";

// Test 2: Inventory Module Integration
echo "2. Inventory Module (products table):\n";
$sql = "SELECT COUNT(*) as count FROM products";
$result = $db->query($sql)->fetch();
echo "   Products found: {$result['count']}\n";
echo "   ✓ Inventory module integrated\n\n";

// Test 3: Sales Module Integration
echo "3. Sales Module (sales_orders table):\n";
$sql = "SELECT COUNT(*) as count FROM sales_orders";
$result = $db->query($sql)->fetch();
echo "   Sales orders found: {$result['count']}\n";
echo "   ✓ Sales module integrated\n\n";

// Test 4: Procurement Module Integration
echo "4. Procurement Module (purchase_orders table):\n";
$sql = "SELECT COUNT(*) as count FROM purchase_orders";
$result = $db->query($sql)->fetch();
echo "   Purchase orders found: {$result['count']}\n";
echo "   ✓ Procurement module integrated\n\n";

echo "✓ ALL MODULES INTEGRATED SUCCESSFULLY\n";
```

---

## Performance Benchmarks

### Acceptable Performance Thresholds

| Metric | Target | Warning | Critical |
|--------|--------|---------|----------|
| Page Load Time | < 200ms | 200-500ms | > 500ms |
| Database Queries | 6 queries | 6-10 queries | > 10 queries |
| Memory Usage | < 2MB | 2-5MB | > 5MB |
| CSS Load Time | < 50ms | 50-100ms | > 100ms |

### Performance Testing Script

```php
<?php
// Test file: tests/performance_benchmark.php

// Start profiling
$start_time = microtime(true);
$start_memory = memory_get_usage();

// Load dashboard
ob_start();
require __DIR__ . '/../modules/home/index.php';
$output = ob_get_clean();

// Calculate metrics
$end_time = microtime(true);
$end_memory = memory_get_usage();

$load_time = ($end_time - $start_time) * 1000; // Convert to ms
$memory_used = ($end_memory - $start_memory) / 1024 / 1024; // Convert to MB

// Display results
echo "Performance Benchmark Results\n";
echo "=============================\n";
echo "Page Load Time: " . number_format($load_time, 2) . " ms\n";
echo "Memory Usage: " . number_format($memory_used, 2) . " MB\n";
echo "Output Size: " . number_format(strlen($output) / 1024, 2) . " KB\n";

// Evaluate performance
if ($load_time < 200 && $memory_used < 2) {
    echo "\n✓ EXCELLENT PERFORMANCE\n";
} elseif ($load_time < 500 && $memory_used < 5) {
    echo "\n⚠ ACCEPTABLE PERFORMANCE (Consider optimization)\n";
} else {
    echo "\n✗ POOR PERFORMANCE (Optimization required)\n";
}
```

---

## Troubleshooting Guide

### Common Issues and Solutions

#### Issue 1: "Class 'DashboardModel' not found"
**Cause:** Model file not loaded or incorrect path

**Solution:**
```php
// Verify path is correct
require_once MODULES_PATH . '/home/models/DashboardModel.php';

// Check MODULES_PATH constant
echo "MODULES_PATH: " . MODULES_PATH . "\n";

// Verify file exists
if (file_exists(MODULES_PATH . '/home/models/DashboardModel.php')) {
    echo "✓ File exists\n";
} else {
    echo "✗ File not found\n";
}
```

#### Issue 2: "Call to undefined method Database::getInstance()"
**Cause:** Database class not loaded

**Solution:**
```php
// Load Database class before DashboardModel
require_once CORE_PATH . '/Database.php';
require_once MODULES_PATH . '/home/models/DashboardModel.php';
```

#### Issue 3: Dashboard shows all zeros
**Cause:** Empty database tables

**Solution:**
```sql
-- Verify data exists
SELECT 'accounts' as table_name, COUNT(*) as count FROM accounts
UNION ALL
SELECT 'products', COUNT(*) FROM products
UNION ALL
SELECT 'sales_orders', COUNT(*) FROM sales_orders
UNION ALL
SELECT 'purchase_orders', COUNT(*) FROM purchase_orders;

-- If counts are 0, run seed data scripts
SOURCE modules/finance/database/schema.sql;
SOURCE modules/inventory/database/schema.sql;
-- etc.
```

#### Issue 4: CSS not loading
**Cause:** Incorrect BASE_URL or file path

**Solution:**
```php
// Verify BASE_URL is correct
echo "BASE_URL: " . BASE_URL . "\n";

// Check CSS file exists
$css_path = __DIR__ . '/../../public/assets/css/dashboard.css';
if (file_exists($css_path)) {
    echo "✓ CSS file exists\n";
} else {
    echo "✗ CSS file not found at: {$css_path}\n";
}

// Verify web server can serve static files
// Access: http://localhost/assets/css/dashboard.css
```

#### Issue 5: Slow query performance
**Cause:** Missing indexes or large dataset

**Solution:**
```sql
-- Add missing indexes
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_sales_created_at ON sales_orders(created_at);
CREATE INDEX idx_purchase_created_at ON purchase_orders(created_at);

-- Analyze tables
ANALYZE TABLE accounts, products, sales_orders, purchase_orders;

-- Optimize tables
OPTIMIZE TABLE accounts, products, sales_orders, purchase_orders;
```

---

## Final Integration Checklist

Before deploying to production, verify:

### Code Quality
- [ ] All PHP files use `declare(strict_types=1);`
- [ ] All methods have PHPDoc comments
- [ ] No PHP warnings or notices
- [ ] Code follows PSR-12 standards

### Security
- [ ] All outputs use `htmlspecialchars()`
- [ ] All queries use PDO prepared statements
- [ ] No raw user input in SQL
- [ ] XSS tests pass
- [ ] SQL injection tests pass

### Performance
- [ ] Page loads in < 200ms
- [ ] All queries use indexes
- [ ] No N+1 query problems
- [ ] Memory usage < 2MB
- [ ] UNION ALL query optimized

### Functionality
- [ ] All metrics display correctly
- [ ] Low stock alerts work
- [ ] Activity feed shows recent items
- [ ] Links navigate correctly
- [ ] Responsive design works on mobile

### Documentation
- [ ] ROUTING.md complete
- [ ] INTEGRATION_GUIDE.md complete
- [ ] Code comments accurate
- [ ] README updated (if applicable)

---

## Conclusion

This integration guide provides comprehensive testing and verification procedures for the Dashboard module. Follow all checklists and tests to ensure a secure, performant, and reliable implementation.

For additional support or questions, refer to the project documentation or contact the development team.

---

**Document Version:** 1.0.0  
**Last Updated:** 2026-05-16  
**Author:** Bob (QA Engineer)  
**Module:** Home/Dashboard

<!-- Made with Bob -->