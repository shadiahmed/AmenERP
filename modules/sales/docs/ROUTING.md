# Sales Module - Routing Configuration

## Route Registration Order (CRITICAL)

The Sales module routes **MUST** be registered in this specific order in `public/index.php` to prevent parameter collision and routing conflicts:

```php
// ============================================================================
// SALES MODULE ROUTES
// ============================================================================

// 1. Static GET path - Main sales dashboard
$router->get('/sales', 'sales/index.php');

// 2. Static POST path - Process sale checkout (MUST come before dynamic routes)
$router->post('/sales/checkout', 'sales/controllers/ProcessSaleController.php');

// 3. Dynamic GET path - View specific invoice (comes last)
// $router->get('/sales/{id}', 'sales/controllers/ViewInvoiceController.php');
```

## Why This Order Matters

### Problem: Route Priority Conflicts

If dynamic routes are registered before static routes, the router may incorrectly match:
- `/sales/checkout` → Interpreted as `/sales/{id}` with `id = "checkout"`
- This causes the wrong controller to execute

### Solution: Static Before Dynamic

By registering static literal paths FIRST, the router checks exact matches before attempting parameter extraction.

## Route Details

### 1. GET /sales
**Purpose:** Display the main sales dashboard with form  
**File:** `modules/sales/index.php`  
**Access:** Public (requires session)  
**Returns:** HTML view with sales form and recent orders

### 2. POST /sales/checkout
**Purpose:** Process new sale submission  
**File:** `modules/sales/controllers/ProcessSaleController.php`  
**Access:** POST only, CSRF protected  
**Parameters:** 
- `csrf_token` (hidden field)
- `customer_name` (string, 2-255 chars)
- `items[]` (array of product_id, quantity, unit_price)

**Returns:** Redirect to `/sales` with success/error message

### 3. GET /sales/{id} (Future Enhancement)
**Purpose:** View specific invoice details  
**File:** `modules/sales/controllers/ViewInvoiceController.php` (not yet implemented)  
**Access:** Public (requires session)  
**Parameters:** 
- `id` (int) - Sales order ID

**Parameter Extraction:**
```php
$orderId = (int)($params['id'] ?? 0);
```

## Form Configuration

The sales form in `modules/sales/index.php` uses the correct action path:

```php
<form id="salesForm" class="sales-form" method="POST" action="<?php echo BASE_URL; ?>/sales/checkout">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <!-- Form fields -->
</form>
```

### Key Points:
1. **Uses BASE_URL constant** - Ensures correct path regardless of installation directory
2. **Action points to `/sales/checkout`** - Matches the POST route registration
3. **CSRF token included** - Security requirement for all POST requests
4. **Token name is `csrf_token`** - Matches controller expectation

## Controller Implementation

### ProcessSaleController.php

The controller does NOT expect route parameters from `$params`. It processes data directly from `$_POST`:

```php
// ✅ CORRECT - Reading from $_POST array
$customerName = htmlspecialchars(trim($_POST['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$rawItems = $_POST['items'] ?? [];

// ❌ INCORRECT - Don't use $params for POST data
// $customerName = $params['customer_name']; // WRONG!
```

### ViewInvoiceController.php (Future)

When implementing invoice viewing, the controller WILL use `$params` for the route parameter:

```php
// ✅ CORRECT - Reading route parameter
$orderId = (int)($params['id'] ?? 0);

if ($orderId <= 0) {
    $_SESSION['error'] = 'Invalid order ID';
    header('Location: ' . BASE_URL . '/sales');
    exit;
}

// Fetch order details
$salesModel = new SalesModel();
$order = $salesModel->getSalesOrderById($orderId);
```

## Testing the Routes

### Test 1: Main Dashboard
```bash
# Navigate to sales dashboard
curl http://localhost/sales
# Expected: HTML form displayed
```

### Test 2: Submit Sale
```bash
# Submit a sale (with CSRF token)
curl -X POST http://localhost/sales/checkout \
  -d "csrf_token=TOKEN" \
  -d "customer_name=John Doe" \
  -d "items[0][product_id]=1" \
  -d "items[0][quantity]=2" \
  -d "items[0][unit_price]=50.00"
# Expected: Redirect to /sales with success message
```

### Test 3: View Invoice (Future)
```bash
# View specific invoice
curl http://localhost/sales/123
# Expected: Invoice details for order ID 123
```

## Common Routing Errors

### Error 1: "Method Not Allowed"
**Cause:** Accessing POST route with GET or vice versa  
**Solution:** Ensure form uses `method="POST"` and route is registered with `$router->post()`

### Error 2: "Invalid security token"
**Cause:** CSRF token missing or incorrect  
**Solution:** Verify form includes hidden `csrf_token` field with correct value

### Error 3: "Route not found"
**Cause:** Route not registered or incorrect path  
**Solution:** Check route registration in `public/index.php` matches form action

### Error 4: Wrong controller executes
**Cause:** Dynamic route registered before static route  
**Solution:** Reorder routes - static paths BEFORE dynamic paths

## Integration with Router.php

The Sales module relies on the Router class methods:

```php
// Register GET route
$router->get($path, $file);

// Register POST route
$router->post($path, $file);

// Router extracts parameters from dynamic segments
// Example: /sales/{id} → $params['id']
```

## Security Considerations

1. **CSRF Protection:** All POST routes require valid CSRF token
2. **Input Validation:** Controller validates all input before processing
3. **SQL Injection Prevention:** All queries use PDO prepared statements
4. **XSS Prevention:** All output uses `htmlspecialchars()`
5. **Session Management:** Routes check for active session

## Future Route Additions

When adding new routes, follow this pattern:

```php
// Static routes first
$router->get('/sales/reports', 'sales/controllers/ReportsController.php');
$router->get('/sales/export', 'sales/controllers/ExportController.php');

// POST routes
$router->post('/sales/refund', 'sales/controllers/RefundController.php');

// Dynamic routes last
$router->get('/sales/{id}', 'sales/controllers/ViewInvoiceController.php');
$router->get('/sales/{id}/print', 'sales/controllers/PrintInvoiceController.php');
```

## Troubleshooting Checklist

- [ ] Routes registered in correct order (static before dynamic)
- [ ] Form action uses `BASE_URL` constant
- [ ] Form method matches route method (GET/POST)
- [ ] CSRF token included in POST forms
- [ ] Controller reads from correct source (`$_POST` vs `$params`)
- [ ] Route paths match exactly (case-sensitive)
- [ ] Controller file exists at specified path

## Related Files

- `public/index.php` - Route registration
- `core/Router.php` - Router implementation
- `modules/sales/index.php` - Main view with form
- `modules/sales/controllers/ProcessSaleController.php` - Form handler
- `core/Csrf.php` - CSRF token generation/validation

---

**Last Updated:** 2026-05-16  
**Module Version:** 1.0.0  
**Author:** Bob