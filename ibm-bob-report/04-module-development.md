# 🔧 Module Development Workflow
## AI-Accelerated Iterative Development Process

---

## Overview

AmenERP was built module-by-module through an iterative development process where IBM Bob served as both architect and code generator. This document details the systematic approach used to develop each module, from initial design to integration testing.

---

## Development Methodology

### Iterative Module-by-Module Approach

```
┌─────────────────────────────────────────────────────────┐
│                  Development Cycle                      │
│                                                          │
│  1. Requirements Analysis                               │
│  2. Database Schema Design                              │
│  3. Model Implementation                                │
│  4. Controller Development                              │
│  5. View Creation                                       │
│  6. Route Registration                                  │
│  7. Integration Testing                                 │
│  8. Documentation                                       │
│  9. Next Module                                         │
└─────────────────────────────────────────────────────────┘
```

**Why This Approach**:
- ✅ Each module is independently testable
- ✅ Clear progress milestones
- ✅ Easier to debug and refine
- ✅ Parallel development possible
- ✅ Incremental value delivery

---

## Module Development Template

### Phase 1: Requirements Analysis

**Objective**: Define module purpose, features, and data requirements

**Process**:
1. Developer describes business requirements
2. Bob asks clarifying questions
3. Together define scope and features
4. Identify integration points with other modules

**Example: Sales Module**

```
Developer: "I need a sales module to create invoices and track orders"

Bob: "Let me clarify the requirements:
- What information do you need on an invoice?
- Should it integrate with inventory for stock updates?
- Do you need to track payment status?
- Should it create financial transactions automatically?"

Developer: "Yes to all. Invoice should have customer name, line items,
and total. It should reduce inventory and create accounting entries."

Bob: "Understood. I'll design a sales_orders table with sales_items
for line items, integrate with inventory for stock updates, and
finance for double-entry bookkeeping."
```

---

### Phase 2: Database Schema Design

**Objective**: Design normalized, efficient database structure

**Bob's Process**:
1. Identify entities and relationships
2. Design tables with proper constraints
3. Add foreign keys for referential integrity
4. Create indexes for performance
5. Generate SQL schema file

**Example: Sales Module Schema**

```sql
-- Sales order header
CREATE TABLE `sales_orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_number` VARCHAR(50) NOT NULL,
  `customer_name` VARCHAR(200) NOT NULL,
  `total_amount` DECIMAL(15, 2) NOT NULL,
  `status` ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sales_orders_invoice_number` (`invoice_number`),
  KEY `idx_sales_orders_status` (`status`),
  KEY `idx_sales_orders_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sales order line items
CREATE TABLE `sales_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sales_order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10, 2) NOT NULL,
  `subtotal` DECIMAL(15, 2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sales_items_order` (`sales_order_id`),
  KEY `idx_sales_items_product` (`product_id`),
  CONSTRAINT `fk_sales_items_order` 
    FOREIGN KEY (`sales_order_id`) 
    REFERENCES `sales_orders` (`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_sales_items_product` 
    FOREIGN KEY (`product_id`) 
    REFERENCES `products` (`id`) 
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Design Decisions**:
- ✅ Header-detail pattern for orders
- ✅ Denormalized `subtotal` for performance
- ✅ CASCADE delete for line items (dependent data)
- ✅ RESTRICT delete for products (prevent orphans)
- ✅ Unique invoice numbers for business logic

---

### Phase 3: Model Implementation

**Objective**: Implement business logic and database operations

**Bob's Process**:
1. Create model class with proper structure
2. Implement CRUD operations
3. Add transaction management
4. Include validation logic
5. Add integration methods

**Example: Sales Model**

```php
declare(strict_types=1);

class SalesModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new sales order with line items
     * Integrates with inventory and finance modules
     */
    public function createSalesOrder(array $orderData, array $items): array
    {
        try {
            $this->db->beginTransaction();

            // Step 1: Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber();

            // Step 2: Calculate total
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            // Step 3: Create order header
            $sql = "INSERT INTO sales_orders 
                    (invoice_number, customer_name, total_amount, status) 
                    VALUES (:invoice_number, :customer_name, :total_amount, :status)";
            
            $this->db->query($sql, [
                'invoice_number' => $invoiceNumber,
                'customer_name' => $orderData['customer_name'],
                'total_amount' => $totalAmount,
                'status' => 'completed'
            ]);

            $orderId = (int)$this->db->lastInsertId();

            // Step 4: Create line items and update inventory
            foreach ($items as $item) {
                $this->addSalesItem($orderId, $item);
                $this->updateInventory($item['product_id'], $item['quantity']);
            }

            // Step 5: Create financial transaction
            $this->recordFinancialTransaction($invoiceNumber, $totalAmount);

            // Step 6: Verify integrity
            $this->verifyOrderIntegrity($orderId);

            $this->db->commit();

            return [
                'success' => true,
                'order_id' => $orderId,
                'invoice_number' => $invoiceNumber
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update inventory after sale
     */
    private function updateInventory(int $productId, int $quantity): void
    {
        // Pre-transaction validation
        $sql = "SELECT quantity FROM products WHERE id = :id";
        $result = $this->db->query($sql, ['id' => $productId])->fetch();
        
        if (!$result || $result['quantity'] < $quantity) {
            throw new Exception('Insufficient stock for product ID: ' . $productId);
        }

        // Update stock
        $sql = "UPDATE products 
                SET quantity = quantity - :qty 
                WHERE id = :id";
        $this->db->query($sql, [
            'qty' => $quantity,
            'id' => $productId
        ]);

        // Post-transaction verification
        $sql = "SELECT quantity FROM products WHERE id = :id";
        $result = $this->db->query($sql, ['id' => $productId])->fetch();
        
        if ($result['quantity'] < 0) {
            throw new Exception('Stock went negative - race condition detected');
        }
    }

    /**
     * Record financial transaction
     */
    private function recordFinancialTransaction(string $invoiceNumber, float $amount): void
    {
        require_once __DIR__ . '/../../finance/models/FinanceModel.php';
        $financeModel = new FinanceModel();

        $result = $financeModel->addSimpleTransaction(
            'Sales Invoice: ' . $invoiceNumber,
            3,  // FROM: General Sales Income (income account)
            1,  // TO: Cash Safe (asset account)
            $amount,
            date('Y-m-d')
        );

        if (!$result) {
            throw new Exception('Failed to record financial transaction');
        }
    }
}
```

**Key Patterns**:
- ✅ Transaction wrapping for atomicity
- ✅ Pre/post validation for race conditions
- ✅ Module integration through model loading
- ✅ Comprehensive error handling
- ✅ Type hints and return types

---

### Phase 4: Controller Development

**Objective**: Handle HTTP requests and coordinate model/view

**Bob's Process**:
1. Create controller file
2. Implement CSRF validation
3. Add input validation
4. Call model methods
5. Set flash messages
6. Handle redirects

**Example: Process Sale Controller**

```php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../../core/Csrf.php';
require_once __DIR__ . '/../models/SalesModel.php';

// Validate CSRF token
if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid security token. Please try again.';
    header('Location: ' . BASE_URL . '/sales');
    exit;
}

// Validate input
$customerName = trim($_POST['customer_name'] ?? '');
if (strlen($customerName) < 2) {
    $_SESSION['error'] = 'Customer name is required';
    header('Location: ' . BASE_URL . '/sales');
    exit;
}

// Parse line items
$items = [];
if (isset($_POST['product_id']) && is_array($_POST['product_id'])) {
    foreach ($_POST['product_id'] as $index => $productId) {
        $quantity = (int)($_POST['quantity'][$index] ?? 0);
        $unitPrice = (float)($_POST['unit_price'][$index] ?? 0);

        if ($quantity > 0 && $unitPrice > 0) {
            $items[] = [
                'product_id' => (int)$productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice
            ];
        }
    }
}

if (empty($items)) {
    $_SESSION['error'] = 'At least one product is required';
    header('Location: ' . BASE_URL . '/sales');
    exit;
}

// Process sale
try {
    $model = new SalesModel();
    $result = $model->createSalesOrder(
        ['customer_name' => $customerName],
        $items
    );

    if ($result['success']) {
        $_SESSION['success'] = 'Sale completed successfully. Invoice: ' . 
                               $result['invoice_number'];
    } else {
        $_SESSION['error'] = $result['message'];
    }

} catch (Exception $e) {
    error_log('Sale processing error: ' . $e->getMessage());
    $_SESSION['error'] = 'Failed to process sale. Please try again.';
}

header('Location: ' . BASE_URL . '/sales');
exit;
```

**Key Patterns**:
- ✅ CSRF validation first
- ✅ Input validation before processing
- ✅ Type casting for safety
- ✅ Try-catch for error handling
- ✅ Flash messages for user feedback
- ✅ Always redirect after POST

---

### Phase 5: View Creation

**Objective**: Create user interface with proper security

**Bob's Process**:
1. Design form structure
2. Add CSRF token field
3. Implement client-side validation
4. Add flash message display
5. Escape all output

**Example: Sales Form View**

```php
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../inventory/models/InventoryModel.php';

$csrfToken = Csrf::generateToken();
$inventoryModel = new InventoryModel();
$products = $inventoryModel->getAllProducts();
?>

<!-- Flash Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8');
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <?php 
        echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8');
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<!-- Sales Form -->
<form method="POST" action="<?php echo BASE_URL; ?>/sales/process" id="salesForm">
    <input type="hidden" name="csrf_token" 
           value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    
    <div class="form-group">
        <label for="customer_name">Customer Name *</label>
        <input type="text" 
               id="customer_name" 
               name="customer_name" 
               required 
               minlength="2" 
               maxlength="200">
    </div>

    <div id="lineItems">
        <div class="line-item">
            <select name="product_id[]" required>
                <option value="">Select Product</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo (int)$product['id']; ?>"
                            data-price="<?php echo (float)$product['unit_price']; ?>"
                            data-stock="<?php echo (int)$product['quantity']; ?>">
                        <?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>
                        (Stock: <?php echo (int)$product['quantity']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="number" 
                   name="quantity[]" 
                   placeholder="Quantity" 
                   min="1" 
                   required>

            <input type="number" 
                   name="unit_price[]" 
                   placeholder="Unit Price" 
                   step="0.01" 
                   min="0.01" 
                   required 
                   readonly>
        </div>
    </div>

    <button type="button" onclick="addLineItem()">Add Item</button>
    <button type="submit">Complete Sale</button>
</form>

<script>
// Auto-populate price and validate stock
document.querySelectorAll('select[name="product_id[]"]').forEach(select => {
    select.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const priceInput = this.closest('.line-item').querySelector('input[name="unit_price[]"]');
        const quantityInput = this.closest('.line-item').querySelector('input[name="quantity[]"]');
        
        priceInput.value = option.dataset.price || '';
        quantityInput.max = option.dataset.stock || '';
    });
});

// Add new line item
function addLineItem() {
    const template = document.querySelector('.line-item').cloneNode(true);
    template.querySelectorAll('input').forEach(input => input.value = '');
    template.querySelector('select').selectedIndex = 0;
    document.getElementById('lineItems').appendChild(template);
}
</script>
```

**Key Patterns**:
- ✅ CSRF token in hidden field
- ✅ HTML5 validation attributes
- ✅ All output escaped
- ✅ Flash messages with unset
- ✅ JavaScript enhancement (progressive)

---

### Phase 6: Route Registration

**Objective**: Connect URLs to controllers

**Process**:
1. Register GET routes for views
2. Register POST routes for actions
3. Follow priority order (static before dynamic)

**Example: Sales Routes**

```php
// In public/index.php

// Sales module routes
$router->get('/sales', 'sales/index.php');
$router->post('/sales/process', 'sales/controllers/ProcessSaleController.php');
$router->get('/sales/orders/{id}', 'sales/controllers/ViewInvoiceController.php');
```

**Priority Order**:
1. Static GET paths (`/sales`)
2. Static POST paths (`/sales/process`)
3. Dynamic paths (`/sales/orders/{id}`)

---

### Phase 7: Integration Testing

**Objective**: Verify module works with other modules

**Test Checklist**:
- [ ] Form submits successfully
- [ ] CSRF token validates
- [ ] Input validation catches errors
- [ ] Database transaction commits
- [ ] Inventory updates correctly
- [ ] Financial transaction records
- [ ] Flash messages display
- [ ] Redirects work properly
- [ ] Race conditions prevented
- [ ] Foreign keys enforced

**Example Test Scenario: Sales Module**

```
1. Create sale with 2 products
   ✓ Sales order created
   ✓ 2 sales items created
   ✓ Inventory reduced by correct amounts
   ✓ Financial transaction recorded
   ✓ Account balances updated

2. Try to sell more than available stock
   ✓ Transaction rolled back
   ✓ Error message displayed
   ✓ No partial updates

3. Submit form without CSRF token
   ✓ Request rejected
   ✓ Error message displayed

4. Submit form with invalid data
   ✓ Validation errors shown
   ✓ No database changes
```

---

### Phase 8: Documentation

**Objective**: Document module for future developers

**Bob Creates**:
1. **README.md**: Module overview, features, database schema
2. **ROUTING.md**: Route configuration, form setup
3. **INTEGRATION_GUIDE.md**: How to integrate with other modules

**Example: Sales Module README.md**

```markdown
# Sales Module

## Overview
Manages sales orders, invoices, and customer transactions.

## Features
- Create sales orders with multiple line items
- Generate unique invoice numbers
- Automatic inventory updates
- Financial transaction recording
- Invoice viewing and printing

## Database Schema
- `sales_orders`: Order headers
- `sales_items`: Order line items

## Integration Points
- **Inventory**: Updates stock levels
- **Finance**: Records double-entry transactions
- **Customers**: Links to customer accounts (future)

## Usage
1. Navigate to `/sales`
2. Fill in customer name
3. Add products and quantities
4. Submit form
5. View generated invoice
```

---

## Module Development Timeline

### Actual Development Sequence

1. **Inventory Module** (Foundation)
   - First module developed
   - Established patterns for others
   - Created product master data

2. **Finance Module** (Core Integration)
   - Double-entry bookkeeping
   - Chart of accounts
   - Transaction recording

3. **Sales Module** (First Integration)
   - Integrated with Inventory
   - Integrated with Finance
   - Established integration patterns

4. **Procurement Module** (Parallel Pattern)
   - Mirrored Sales structure
   - Added supplier integration
   - Purchase order processing

5. **HR Module** (Batch Processing)
   - Payroll batch processing
   - Employee management
   - Financial integration

6. **Customers Module** (Receivables)
   - Customer master data
   - Transaction tracking
   - Balance management

7. **Suppliers Module** (Payables)
   - Supplier master data
   - Transaction tracking
   - Balance management

8. **Manufacturing Module** (Complex Integration)
   - Bill of materials
   - Production runs
   - Multi-level inventory updates

9. **Home Module** (Dashboard)
   - Aggregate data display
   - Quick stats
   - Navigation hub

---

## AI Collaboration Patterns

### Code Generation Workflow

```
Developer: "I need a sales module"
   ↓
Bob: "Let me design the database schema"
   ↓
Developer: "Looks good, proceed"
   ↓
Bob: "Here's the model with transaction management"
   ↓
Developer: "Add inventory integration"
   ↓
Bob: "Updated model with inventory updates"
   ↓
Developer: "Now the controller"
   ↓
Bob: "Here's the controller with CSRF validation"
   ↓
Developer: "And the view"
   ↓
Bob: "Here's the form with proper escaping"
   ↓
Developer: "Perfect, document it"
   ↓
Bob: "Documentation complete"
```

### Iterative Refinement

**Example: Race Condition Fix**

```
Developer: "Sales sometimes create negative inventory"
   ↓
Bob: "That's a race condition. Add verification step"
   ↓
Developer: "How?"
   ↓
Bob: "Check stock after update, rollback if negative"
   ↓
Developer: "Implemented, works perfectly"
```

---

## Code Quality Standards

### Bob's Review Checklist

- [ ] `declare(strict_types=1)` at top
- [ ] Type hints on all functions
- [ ] CSRF validation on POST
- [ ] Input validation and sanitization
- [ ] Output escaping with `htmlspecialchars()`
- [ ] Prepared statements for SQL
- [ ] Transaction wrapping for multi-step ops
- [ ] Error handling with try-catch
- [ ] PHPDoc comments
- [ ] No external dependencies

---

## Lessons Learned

### What Accelerated Development

✅ **AI Code Generation**: Bob generated boilerplate quickly  
✅ **Pattern Reuse**: Each module followed established patterns  
✅ **Comprehensive Documentation**: Reduced back-and-forth questions  
✅ **Security-First**: Built-in from the start, not retrofitted  
✅ **Transaction Templates**: Copy-paste transaction patterns  

### Challenges Overcome

🔧 **Module Integration**: Solved with clear integration guides  
🔧 **Route Conflicts**: Fixed with priority-based registration  
🔧 **Race Conditions**: Prevented with verification steps  
🔧 **Balance Caching**: Required careful transaction management  

### Future Improvements

🚀 **Automated Testing**: Unit and integration tests  
🚀 **Code Templates**: Scaffolding for new modules  
🚀 **Migration Tools**: Database schema versioning  
🚀 **API Generation**: Auto-generate REST endpoints  

---

## Conclusion

The module-by-module development approach, accelerated by IBM Bob, enabled rapid development of a complete ERP system while maintaining high code quality and security standards. Each module was independently developed, tested, and documented before moving to the next, ensuring a solid foundation for the entire system.

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-05-17  
**AI Assistant**: IBM Bob