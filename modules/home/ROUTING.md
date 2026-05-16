# Dashboard Module - Routing Configuration

## Overview
The Dashboard module serves as the executive landing page for AmenERP, providing real-time business intelligence and operational metrics. This document details the routing configuration and integration requirements.

## Route Definition

### Primary Route
```
Path: /
Method: GET
Handler: modules/home/index.php
Priority: HIGH (Must be first in router stack)
```

### Route Registration in Central Router
```php
// In public/index.php - Router Configuration
// CRITICAL: Dashboard route MUST be registered FIRST
$router->add('GET', '/', function() {
    require MODULES_PATH . '/home/index.php';
});

// Other module routes follow...
$router->add('GET', '/inventory', function() { ... });
$router->add('GET', '/sales', function() { ... });
// etc.
```

## Critical Routing Considerations

### 1. Router Stack Priority
**The Dashboard route MUST sit at the top of the central router stack.**

**Reason:** The root path (`/`) is the most generic route pattern. If placed after other routes with wildcard patterns or dynamic segments, it may never be matched due to route precedence rules.

**Example of Incorrect Ordering:**
```php
// ❌ WRONG - Dashboard will never match
$router->add('GET', '/:module', function($module) { ... });  // Catches everything
$router->add('GET', '/', function() { ... });                // Never reached
```

**Correct Ordering:**
```php
// ✅ CORRECT - Dashboard matches first
$router->add('GET', '/', function() { ... });                // Matches root
$router->add('GET', '/:module', function($module) { ... });  // Matches other paths
```

### 2. Wildcard Collision Prevention
The Dashboard route has no dynamic segments or wildcards, making it a **literal match**. However, it must be evaluated before any catch-all or parameterized routes to prevent false matches.

**Router Evaluation Order:**
1. Exact literal matches (e.g., `/`, `/inventory`, `/sales`)
2. Parameterized routes (e.g., `/sales/:id`)
3. Wildcard routes (e.g., `/:module/:action`)

### 3. No Sub-Routes
The Dashboard module is intentionally flat with no sub-routes. It serves as a read-only overview and does not handle POST requests or dynamic actions.

**Supported:**
- `GET /` - Dashboard view

**Not Supported:**
- `POST /` - No form submissions
- `GET /dashboard/settings` - No sub-pages
- `GET /dashboard/:action` - No dynamic actions

## Data Flow Architecture

### Read-Only Operational Overview
The Dashboard acts as a **read-only aggregation layer** that pulls data from four core ledger tables:

```
┌─────────────────────────────────────────────────────────────┐
│                    Dashboard Module                          │
│                  (Read-Only Analytics)                       │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ SELECT queries only
                            │ (No INSERT/UPDATE/DELETE)
                            ▼
        ┌───────────────────────────────────────────┐
        │         Core Ledger Tables                │
        ├───────────────────────────────────────────┤
        │  1. accounts      (Finance Module)        │
        │  2. products      (Inventory Module)      │
        │  3. sales_orders  (Sales Module)          │
        │  4. purchase_orders (Procurement Module)  │
        └───────────────────────────────────────────┘
```

### Data Sources

#### 1. Finance Module (`accounts` table)
- **Metric:** Total Cash Liquidity
- **Query:** `SELECT balance FROM accounts WHERE id = 1`
- **Purpose:** Display current liquid assets in Cash Safe

#### 2. Inventory Module (`products` table)
- **Metric:** Stock Capital Asset Value
- **Query:** `SELECT SUM(quantity * unit_price) FROM products WHERE status = 'active'`
- **Purpose:** Calculate total inventory value
- **Alert:** Low stock products (quantity < 5)

#### 3. Sales Module (`sales_orders` table)
- **Metric:** Monthly Sales Revenue
- **Query:** Aggregate sales via ledger_entries for current month
- **Activity Feed:** 5 most recent sales invoices

#### 4. Procurement Module (`purchase_orders` table)
- **Activity Feed:** 5 most recent purchase orders
- **Integration:** UNION ALL with sales for unified timeline

### Query Characteristics
- **Type:** SELECT only (read-only)
- **Performance:** Optimized aggregations with indexes
- **Transactions:** No write operations, no transaction locks
- **Caching:** Suitable for query result caching (future enhancement)

## Security Considerations

### 1. No Authentication Required (Current Implementation)
The Dashboard is accessible without authentication in the current implementation. For production deployments, consider adding authentication middleware:

```php
// Future enhancement - Add authentication check
$router->add('GET', '/', function() {
    // Check if user is authenticated
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }
    require MODULES_PATH . '/home/index.php';
});
```

### 2. XSS Prevention
All dynamic content is escaped using `htmlspecialchars()` with proper flags:
- `ENT_QUOTES` - Escape both single and double quotes
- `'UTF-8'` - Specify character encoding

### 3. SQL Injection Prevention
All queries use PDO prepared statements with parameter binding. No raw SQL concatenation.

## Performance Optimization

### 1. Query Optimization
- All aggregation queries use proper indexes
- UNION ALL (not UNION) for activity feed to avoid expensive DISTINCT operation
- LIMIT clauses on all list queries to prevent large result sets

### 2. Database Indexes Required
Ensure the following indexes exist for optimal performance:

```sql
-- Finance Module
CREATE INDEX idx_accounts_id ON accounts(id);

-- Inventory Module
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_products_quantity ON products(quantity);

-- Sales Module
CREATE INDEX idx_sales_created_at ON sales_orders(created_at);

-- Procurement Module
CREATE INDEX idx_purchase_created_at ON purchase_orders(created_at);

-- Ledger Entries
CREATE INDEX idx_ledger_account_id ON ledger_entries(account_id);
CREATE INDEX idx_transactions_date ON transactions(transaction_date);
```

### 3. Caching Strategy (Future Enhancement)
Consider implementing query result caching for dashboard metrics:
- Cache TTL: 5-10 minutes
- Cache key: `dashboard_summary_{date}`
- Invalidation: On any write operation to core tables

## Testing Routes

### Manual Testing
```bash
# Test dashboard access
curl http://localhost/

# Verify response contains expected elements
curl http://localhost/ | grep "Executive Dashboard"
curl http://localhost/ | grep "Total Cash Liquidity"
```

### Automated Testing
```php
// PHPUnit test example
public function testDashboardRouteExists()
{
    $response = $this->get('/');
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertStringContainsString('Executive Dashboard', $response->getBody());
}
```

## Troubleshooting

### Issue: Dashboard Not Loading
**Symptom:** Accessing `/` returns 404 or loads wrong page

**Solution:**
1. Verify route is registered first in router stack
2. Check that `modules/home/index.php` exists
3. Verify `MODULES_PATH` constant is defined correctly

### Issue: Metrics Showing Zero
**Symptom:** All dashboard metrics display $0.00

**Solution:**
1. Verify database tables are populated with seed data
2. Check database connection in `config/config.php`
3. Run SQL verification queries from module schemas

### Issue: Activity Feed Empty
**Symptom:** "No recent activity" message displays

**Solution:**
1. Verify `sales_orders` and `purchase_orders` tables have data
2. Check that UNION ALL query executes without errors
3. Review database error logs for query failures

## Module Dependencies

### Required Core Components
- `core/Database.php` - Database connection singleton
- `config/config.php` - Database configuration constants

### Required Module Models
- `modules/home/models/DashboardModel.php` - Analytics logic

### Required Database Tables
- `accounts` (Finance)
- `products` (Inventory)
- `sales_orders` (Sales)
- `purchase_orders` (Procurement)
- `ledger_entries` (Finance)
- `transactions` (Finance)

### Optional Dependencies
None. Dashboard operates independently without requiring other module controllers.

## Future Enhancements

### 1. Real-Time Updates
Implement WebSocket or Server-Sent Events for live metric updates without page refresh.

### 2. Customizable Widgets
Allow users to configure which metrics appear on their dashboard.

### 3. Date Range Filters
Add ability to view metrics for custom date ranges (week, month, quarter, year).

### 4. Export Functionality
Add PDF/Excel export for dashboard reports.

### 5. Drill-Down Navigation
Make metric cards clickable to navigate to detailed module views.

## Conclusion
The Dashboard module serves as the central hub for AmenERP, providing executives with at-a-glance business intelligence. Proper routing configuration is critical to ensure it loads correctly as the application's landing page.

---

**Document Version:** 1.0.0  
**Last Updated:** 2026-05-16  
**Author:** Bob (Technical Writer)  
**Module:** Home/Dashboard

<!-- Made with Bob -->