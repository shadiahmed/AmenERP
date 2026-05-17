# Inventory Module - Routing Configuration

## Route Registration Order (CRITICAL)

The Inventory module routes **MUST** be registered in this specific order in `public/index.php` to prevent parameter collision and routing conflicts:

```php
// ============================================================================
// INVENTORY MODULE ROUTES
// ============================================================================

// 1. Static GET path - Main inventory dashboard
$router->get('/inventory', 'inventory/index.php');

// 2. Static POST paths - CRUD operations (MUST come before dynamic routes)
$router->post('/inventory/add', 'inventory/controllers/AddProductController.php');
$router->post('/inventory/edit/{id}', 'inventory/controllers/EditProductController.php');
$router->post('/inventory/delete/{id}', 'inventory/controllers/DeleteProductController.php');

// 3. API endpoints - Static paths for AJAX operations
$router->get('/api/inventory/search', 'inventory/api/search.php');
$router->get('/api/inventory/check-sku', 'inventory/api/check-sku.php');

// 4. Dynamic GET paths - View specific product (future enhancement)
// $router->get('/inventory/{id}', 'inventory/controllers/ViewProductController.php');
```

## Why This Order Matters

### Problem: Route Priority Conflicts

If dynamic routes are registered before static routes, the router may incorrectly match:
- `/inventory/add` → Interpreted as `/inventory/{id}` with `id = "add"`
- `/api/inventory/search` → Interpreted as `/api/inventory/{id}` with `id = "search"`
- This causes the wrong controller to execute

### Solution: Static Before Dynamic

By registering static literal paths FIRST, the router checks exact matches before attempting parameter extraction.

**Route Priority Tree:**
```
1. Exact static matches (/inventory/add)
2. Static paths with parameters (/inventory/edit/{id})
3. API endpoints (/api/inventory/*)
4. Dynamic parameter matches (/inventory/{id})
```

## Route Details

### 1. GET /inventory
**Purpose:** Display the main inventory dashboard  
**File:** `modules/inventory/index.php`  
**Access:** Public (requires session)  
**Returns:** HTML view with product table, metrics, and CRUD modals

**Features:**
- Real-time inventory metrics dashboard
- Product listing with search and filtering
- Add/Edit/Delete modals
- SKU validation
- Stock status indicators

### 2. POST /inventory/add
**Purpose:** Process new product creation  
**File:** `modules/inventory/controllers/AddProductController.php`  
**Access:** POST only, CSRF protected  
**Parameters:** 
- `csrf_token` (hidden field)
- `name` (string, 2-100 chars)
- `sku` (string, uppercase alphanumeric with hyphens)
- `category_id` (int)
- `quantity` (int, 0-999999)
- `unit_price` (float, min 0.01)
- `status` (enum: active, inactive, discontinued)

**Returns:** Redirect to `/inventory` with success/error message

**Form Configuration:**
```php
<form method="POST" action="<?php echo BASE_URL; ?>/inventory/add">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <!-- Form fields -->
</form>
```

### 3. POST /inventory/edit/{id}
**Purpose:** Process product update  
**File:** `modules/inventory/controllers/EditProductController.php`  
**Access:** POST only, CSRF protected  
**Parameters:** 
- `id` (route parameter) - Product ID to update
- `csrf_token` (hidden field)
- `product_id` (hidden field, must match route parameter)
- `name`, `sku`, `category_id`, `quantity`, `unit_price`, `status` (same as add)

**Returns:** JSON response for AJAX handling

**Parameter Extraction:**
```php
$productId = (int)($params['id'] ?? 0);
$formProductId = (int)($_POST['product_id'] ?? 0);

// Verify route parameter matches form data
if ($productId !== $formProductId || $productId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}
```

**AJAX Form Submission:**
```javascript
const formData = new FormData(editProductForm);
const productId = document.getElementById('editProductId').value;

const response = await fetch(`${baseUrl}/inventory/edit/${productId}`, {
    method: 'POST',
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: formData
});

const result = await response.json();
```

### 4. POST /inventory/delete/{id}
**Purpose:** Delete a product  
**File:** `modules/inventory/controllers/DeleteProductController.php`  
**Access:** POST only, CSRF protected  
**Parameters:** 
- `id` (route parameter) - Product ID to delete
- `csrf_token` (POST body)

**Returns:** JSON response for AJAX handling

**Parameter Extraction:**
```php
$productId = (int)($params['id'] ?? 0);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}
```

**AJAX Delete Request:**
```javascript
const formData = new FormData();
formData.append('csrf_token', csrfToken);

const response = await fetch(`${baseUrl}/inventory/delete/${productId}`, {
    method: 'POST',
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: formData
});

const result = await response.json();
```

### 5. GET /api/inventory/search
**Purpose:** Real-time product search API  
**File:** `modules/inventory/api/search.php`  
**Access:** GET, AJAX only  
**Parameters:** 
- `q` (query string) - Search term

**Returns:** JSON array of matching products

**Example Request:**
```javascript
const response = await fetch(`${baseUrl}/api/inventory/search?q=${encodeURIComponent(searchTerm)}`);
const products = await response.json();
```

**Example Response:**
```json
[
    {
        "id": 1,
        "name": "Wireless Mouse",
        "sku": "ELEC-MOUSE-001",
        "quantity": 45,
        "unit_price": 15.99,
        "category_name": "Electronics"
    }
]
```

### 6. GET /api/inventory/check-sku
**Purpose:** Real-time SKU validation API  
**File:** `modules/inventory/api/check-sku.php`  
**Access:** GET, AJAX only  
**Parameters:** 
- `sku` (query string) - SKU to check
- `exclude_id` (optional) - Product ID to exclude from check (for edit operations)

**Returns:** JSON object with existence status

**Example Request:**
```javascript
const response = await fetch(`${baseUrl}/api/inventory/check-sku?sku=${encodeURIComponent(sku)}`);
const result = await response.json();
```

**Example Response:**
```json
{
    "exists": true,
    "message": "SKU already in use"
}
```

## Form Configuration

### Add Product Form

The add product form in `modules/inventory/index.php` uses the correct action path:

```php
<form id="addProductForm" method="POST" action="<?php echo BASE_URL; ?>/inventory/add">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    
    <input type="text" name="name" required>
    <input type="text" name="sku" required pattern="[A-Z0-9\-]+">
    <select name="category_id" required>
        <!-- Options -->
    </select>
    <input type="number" name="quantity" required min="0">
    <input type="number" name="unit_price" required min="0.01" step="0.01">
    <select name="status">
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
        <option value="discontinued">Discontinued</option>
    </select>
    
    <button type="submit">Add Product</button>
</form>
```

### Key Points:
1. **Uses BASE_URL constant** - Ensures correct path regardless of installation directory
2. **Action points to `/inventory/add`** - Matches the POST route registration
3. **CSRF token included** - Security requirement for all POST requests
4. **Token name is `csrf_token`** - Matches controller expectation
5. **Pattern validation on SKU** - Enforces uppercase alphanumeric format

### Edit Product Form

The edit form uses AJAX submission with dynamic route parameter:

```php
<form id="editProductForm" method="POST" action="<?php echo BASE_URL; ?>/inventory/edit">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" id="editProductId" name="product_id" value="">
    
    <!-- Same fields as add form -->
</form>
```

**JavaScript handles dynamic URL:**
```javascript
const productId = document.getElementById('editProductId').value;
const response = await fetch(`${baseUrl}/inventory/edit/${productId}`, {
    method: 'POST',
    body: formData
});
```

## Controller Implementation Patterns

### AddProductController.php

The controller reads data directly from `$_POST`:

```php
// ✅ CORRECT - Reading from $_POST array
$name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$sku = strtoupper(trim($_POST['sku'] ?? ''));
$categoryId = (int)($_POST['category_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 0);
$unitPrice = (float)($_POST['unit_price'] ?? 0);
$status = $_POST['status'] ?? 'active';

// ❌ INCORRECT - Don't use $params for POST data
// $name = $params['name']; // WRONG!
```

### EditProductController.php

The controller uses BOTH `$params` (for route parameter) and `$_POST` (for form data):

```php
// ✅ CORRECT - Reading route parameter from $params
$productId = (int)($params['id'] ?? 0);

// ✅ CORRECT - Reading form data from $_POST
$formProductId = (int)($_POST['product_id'] ?? 0);
$name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');

// Verify consistency
if ($productId !== $formProductId) {
    echo json_encode(['success' => false, 'error' => 'Product ID mismatch']);
    exit;
}
```

### DeleteProductController.php

The controller uses `$params` for the product ID:

```php
// ✅ CORRECT - Reading route parameter
$productId = (int)($params['id'] ?? 0);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

// CSRF token comes from POST body
$csrfToken = $_POST['csrf_token'] ?? '';
```

## Testing the Routes

### Test 1: Main Dashboard
```bash
# Navigate to inventory dashboard
curl http://localhost/inventory
# Expected: HTML form displayed with product table
```

### Test 2: Add Product
```bash
# Submit a new product (with CSRF token)
curl -X POST http://localhost/inventory/add \
  -d "csrf_token=TOKEN" \
  -d "name=Test Product" \
  -d "sku=TEST-001" \
  -d "category_id=1" \
  -d "quantity=100" \
  -d "unit_price=49.99" \
  -d "status=active"
# Expected: Redirect to /inventory with success message
```

### Test 3: Edit Product
```bash
# Update product ID 5 (with CSRF token)
curl -X POST http://localhost/inventory/edit/5 \
  -H "X-Requested-With: XMLHttpRequest" \
  -d "csrf_token=TOKEN" \
  -d "product_id=5" \
  -d "name=Updated Product" \
  -d "sku=TEST-001-V2" \
  -d "category_id=1" \
  -d "quantity=150" \
  -d "unit_price=59.99" \
  -d "status=active"
# Expected: JSON response {"success": true}
```

### Test 4: Delete Product
```bash
# Delete product ID 5 (with CSRF token)
curl -X POST http://localhost/inventory/delete/5 \
  -H "X-Requested-With: XMLHttpRequest" \
  -d "csrf_token=TOKEN"
# Expected: JSON response {"success": true}
```

### Test 5: Search API
```bash
# Search for products
curl http://localhost/api/inventory/search?q=mouse
# Expected: JSON array of matching products
```

### Test 6: SKU Validation API
```bash
# Check if SKU exists
curl http://localhost/api/inventory/check-sku?sku=TEST-001
# Expected: JSON {"exists": true/false}
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

### Error 5: "Invalid product ID"
**Cause:** Route parameter not extracted correctly  
**Solution:** Verify controller reads from `$params['id']` for route parameters

### Error 6: SKU validation not working
**Cause:** API endpoint not registered or incorrect URL  
**Solution:** Verify `/api/inventory/check-sku` route is registered and accessible

## Integration with Router.php

The Inventory module relies on the Router class methods:

```php
// Register GET route
$router->get($path, $file);

// Register POST route
$router->post($path, $file);

// Router extracts parameters from dynamic segments
// Example: /inventory/edit/{id} → $params['id']
```

**Router Parameter Extraction:**
```php
// In Router.php dispatch() method
if (preg_match($pattern, $requestUri, $matches)) {
    array_shift($matches); // Remove full match
    $params = $matches;    // Remaining matches are parameters
    
    // Include the matched file with $params available
    include $file;
}
```

## Security Considerations

1. **CSRF Protection:** All POST routes require valid CSRF token
2. **Input Validation:** Controllers validate all input before processing
3. **SQL Injection Prevention:** All queries use PDO prepared statements
4. **XSS Prevention:** All output uses `htmlspecialchars()`
5. **Session Management:** Routes check for active session
6. **SKU Format Enforcement:** Pattern validation prevents injection attempts
7. **Numeric Type Casting:** All IDs cast to integers before use
8. **AJAX Header Verification:** API endpoints check `X-Requested-With` header

## Future Route Additions

When adding new routes, follow this pattern:

```php
// Static routes first
$router->get('/inventory/reports', 'inventory/controllers/ReportsController.php');
$router->get('/inventory/export', 'inventory/controllers/ExportController.php');
$router->get('/inventory/low-stock', 'inventory/controllers/LowStockController.php');

// POST routes
$router->post('/inventory/adjust', 'inventory/controllers/AdjustStockController.php');
$router->post('/inventory/transfer', 'inventory/controllers/TransferStockController.php');

// API endpoints
$router->get('/api/inventory/categories', 'inventory/api/categories.php');
$router->get('/api/inventory/stats', 'inventory/api/stats.php');

// Dynamic routes last
$router->get('/inventory/{id}', 'inventory/controllers/ViewProductController.php');
$router->get('/inventory/{id}/history', 'inventory/controllers/ProductHistoryController.php');
```

## Troubleshooting Checklist

- [ ] Routes registered in correct order (static before dynamic)
- [ ] Form action uses `BASE_URL` constant
- [ ] Form method matches route method (GET/POST)
- [ ] CSRF token included in POST forms
- [ ] Controller reads from correct source (`$_POST` vs `$params`)
- [ ] Route paths match exactly (case-sensitive)
- [ ] Controller file exists at specified path
- [ ] API endpoints return proper JSON responses
- [ ] AJAX requests include `X-Requested-With` header
- [ ] Route parameters properly extracted and validated

## Related Files

- `public/index.php` - Route registration
- `core/Router.php` - Router implementation
- `modules/inventory/index.php` - Main view with forms
- `modules/inventory/controllers/AddProductController.php` - Add handler
- `modules/inventory/controllers/EditProductController.php` - Edit handler
- `modules/inventory/controllers/DeleteProductController.php` - Delete handler
- `modules/inventory/api/search.php` - Search API
- `modules/inventory/api/check-sku.php` - SKU validation API
- `core/Csrf.php` - CSRF token generation/validation

---

**Last Updated:** 2026-05-17  
**Module Version:** 2.0.0  
**Author:** Bob