# Sales Module - Integration Guide for public/index.php

## Quick Integration Steps

Follow these steps to integrate the Sales module into your AmenERP application:

### Step 1: Add Sales Routes to public/index.php

Open `public/index.php` and add the following route registrations in the routing section. **IMPORTANT:** Add these routes in the exact order shown below, after your existing module routes (Home, Inventory, Finance) but before any catch-all or 404 routes.

```php
// ============================================================================
// SALES MODULE ROUTES
// ============================================================================

// Step A: Static GET Paths (Dashboard View)
$router->get('/sales', 'sales/index.php');

// Step B: Static POST Paths (Form Processing - MUST be above wildcards)
$router->post('/sales/checkout', 'sales/controllers/ProcessSaleController.php');

// Step C: Dynamic Parameter Paths (Future: Viewing Individual Invoices)
// Uncomment when ViewInvoiceController.php is implemented
// $router->get('/sales/{id}', 'sales/controllers/ViewInvoiceController.php');
```

### Step 2: Verify File Structure

Ensure your Sales module files are in the correct locations:

```
modules/sales/
├── index.php                                    ✓ Main dashboard view
├── database/
│   └── schema.sql                               ✓ Database tables
├── models/
│   └── SalesModel.php                           ✓ Business logic
└── controllers/
    └── ProcessSaleController.php                ✓ Form handler
```

### Step 3: Import Database Schema

Run the SQL schema to create the required tables:

```bash
# Via MySQL command line
mysql -u your_user -p your_database < modules/sales/database/schema.sql

# Or via phpMyAdmin/Adminer
# Import the file: modules/sales/database/schema.sql
```

This creates two tables:
- `sales_orders` - Master invoice records
- `sales_items` - Line items within each order

### Step 4: Verify Account Configuration

The Sales module expects these financial accounts to exist in your `accounts` table:

| Account ID | Name | Type | Purpose |
|------------|------|------|---------|
| 1 | Cash Safe / Bank Account | asset | Receives sales revenue |
| 3 | General Sales Income | income | Source of sales revenue |

If your account IDs are different, update the constants in `modules/sales/models/SalesModel.php`:

```php
private const SALES_INCOME_ACCOUNT_ID = 3;  // Change to your income account ID
private const CASH_ACCOUNT_ID = 1;          // Change to your cash account ID
```

### Step 5: Test the Integration

1. **Access the Sales Dashboard:**
   - Navigate to: `http://your-domain/sales`
   - You should see the sales form with product dropdowns

2. **Submit a Test Sale:**
   - Select a product from the dropdown
   - Enter a quantity (within available stock)
   - Enter a customer name
   - Click "Complete Sale"
   - You should be redirected back with a success message

3. **Verify Integration:**
   - Check `sales_orders` table - New order should exist
   - Check `sales_items` table - Line items should exist
   - Check `products` table - Stock quantity should be reduced
   - Check `transactions` and `ledger_entries` tables - Financial records should exist
   - Check `accounts` table - Account balances should be updated

## Complete public/index.php Example

Here's how your complete routing section should look with all modules:

```php
<?php
// ... (config and autoload code above)

// Initialize Router
$router = new Router();

// ============================================================================
// HOME MODULE ROUTES
// ============================================================================
$router->get('/', 'home/index.php');
$router->get('/home', 'home/index.php');

// ============================================================================
// INVENTORY MODULE ROUTES
// ============================================================================
$router->get('/inventory', 'inventory/index.php');
$router->get('/inventory/add', 'inventory/add.php');
$router->post('/inventory/add', 'inventory/controllers/AddProductController.php');
$router->post('/inventory/edit', 'inventory/controllers/EditProductController.php');
$router->post('/inventory/delete', 'inventory/controllers/DeleteProductController.php');

// API endpoints
$router->get('/inventory/api/search', 'inventory/api/search.php');
$router->get('/inventory/api/check-sku', 'inventory/api/check-sku.php');

// ============================================================================
// FINANCE MODULE ROUTES
// ============================================================================
$router->get('/finance', 'finance/index.php');
$router->post('/finance/record-income', 'finance/controllers/RecordIncomeController.php');
$router->post('/finance/record-expense', 'finance/controllers/RecordExpenseController.php');

// ============================================================================
// SALES MODULE ROUTES
// ============================================================================

// Step A: Static GET Paths (Dashboard View)
$router->get('/sales', 'sales/index.php');

// Step B: Static POST Paths (Form Processing - MUST be above wildcards)
$router->post('/sales/checkout', 'sales/controllers/ProcessSaleController.php');

// Step C: Dynamic Parameter Paths (Future: Viewing Individual Invoices)
// $router->get('/sales/{id}', 'sales/controllers/ViewInvoiceController.php');

// ============================================================================
// DISPATCH ROUTER
// ============================================================================
$router->dispatch();
```

## Troubleshooting

### Issue: "Route not found" when accessing /sales

**Possible Causes:**
1. Routes not registered in `public/index.php`
2. Routes registered in wrong order
3. `.htaccess` not configured correctly

**Solution:**
- Verify routes are added to `public/index.php`
- Ensure static routes come before dynamic routes
- Check `.htaccess` exists in `public/` directory

### Issue: "Method Not Allowed" when submitting form

**Possible Causes:**
1. POST route not registered
2. Form method doesn't match route method

**Solution:**
- Verify `$router->post('/sales/checkout', ...)` is registered
- Ensure form has `method="POST"` attribute

### Issue: "Invalid security token" error

**Possible Causes:**
1. CSRF token not generated
2. Token name mismatch
3. Session not started

**Solution:**
- Verify `$csrfToken = Csrf::generateToken();` in `modules/sales/index.php`
- Check token field name is `csrf_token`
- Ensure session is started in `public/index.php`

### Issue: "Insufficient stock" error

**Possible Causes:**
1. Product stock is actually insufficient
2. Product status is not 'active'

**Solution:**
- Check product quantity in `products` table
- Verify product status is 'active'
- Add more stock via Inventory module

### Issue: "Failed to record financial transaction"

**Possible Causes:**
1. Account IDs don't exist
2. FinanceModel not loaded
3. Database transaction error

**Solution:**
- Verify accounts with IDs 1 and 3 exist in `accounts` table
- Check `modules/finance/models/FinanceModel.php` exists
- Review error logs for specific database errors

## Verification Checklist

Before going live, verify:

- [ ] Routes registered in `public/index.php` in correct order
- [ ] Database schema imported successfully
- [ ] Required accounts (IDs 1 and 3) exist
- [ ] Products exist with status 'active' and quantity > 0
- [ ] Form action uses `BASE_URL` constant
- [ ] CSRF token field name is `csrf_token`
- [ ] Controller file exists at correct path
- [ ] Test sale completes successfully
- [ ] Inventory stock updates correctly
- [ ] Financial transaction records correctly
- [ ] Account balances update correctly

## Security Notes

The Sales module implements multiple security layers:

1. **CSRF Protection:** All POST requests require valid CSRF token
2. **Input Validation:** All inputs validated and sanitized
3. **SQL Injection Prevention:** All queries use PDO prepared statements
4. **XSS Prevention:** All output uses `htmlspecialchars()`
5. **Transaction Safety:** Database transactions ensure data integrity
6. **Stock Validation:** Prevents overselling with pre and post checks

## Performance Notes

The Sales module is optimized for performance:

1. **Single Transaction:** All operations in one database transaction
2. **Efficient Queries:** Proper indexes on all foreign keys
3. **Minimal Round-trips:** Batch operations where possible
4. **Stock Validation:** Early validation prevents unnecessary processing

## Support

For additional help:
- Review `modules/sales/README.md` for detailed documentation
- Review `modules/sales/ROUTING.md` for routing specifics
- Check error logs in your PHP error log file
- Verify database structure matches schema

---

**Module Version:** 1.0.0  
**Last Updated:** 2026-05-16  
**Author:** Bob