# Inventory Module - Integration Guide for External Modules

## Quick Integration Overview

The Inventory module serves as the **central product master and stock authority** for AmenERP. External modules (Sales, Manufacturing, Procurement) must integrate with Inventory to verify stock availability and update quantities within ACID-compliant transaction blocks.

## Core Integration Pattern

All stock-modifying operations follow this three-phase pattern:

```php
// Phase 1: Pre-Transaction Validation
// Phase 2: Stock Modification within Transaction
// Phase 3: Post-Transaction Verification (Race Condition Check)
```

## Step-by-Step Integration for External Modules

### Step 1: Instantiate the InventoryModel

External modules must load and instantiate the InventoryModel:

```php
// In your module controller or model
require_once __DIR__ . '/../../inventory/models/InventoryModel.php';

$inventoryModel = new InventoryModel();
```

### Step 2: Verify Stock Availability (Pre-Transaction)

Before starting a database transaction, verify that sufficient stock exists:

```php
// Example: Sales module checking stock before creating order
$items = [
    ['product_id' => 1, 'quantity' => 5],
    ['product_id' => 3, 'quantity' => 2]
];

// Validate each item
foreach ($items as $item) {
    $product = $inventoryModel->getProductById($item['product_id']);
    
    // Check 1: Product exists
    if (!$product) {
        throw new Exception("Product ID {$item['product_id']} not found");
    }
    
    // Check 2: Product is active
    if ($product['status'] !== 'active') {
        throw new Exception("Product {$product['name']} is not active");
    }
    
    // Check 3: Sufficient stock
    if ($product['quantity'] < $item['quantity']) {
        throw new Exception(
            "Insufficient stock for {$product['name']}. " .
            "Available: {$product['quantity']}, Requested: {$item['quantity']}"
        );
    }
}
```

### Step 3: Start Database Transaction

Begin an ACID-compliant transaction:

```php
$db = Database::getInstance();

try {
    $db->beginTransaction();
    
    // All stock modifications happen here
    
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}
```

### Step 4: Update Stock Quantities (Within Transaction)

Modify stock levels using atomic SQL operations:

```php
// Inside transaction block

// Method A: Direct SQL Update (Recommended for performance)
foreach ($items as $item) {
    $sql = "
        UPDATE products 
        SET quantity = quantity - :qty,
            updated_at = NOW()
        WHERE id = :id
    ";
    
    $db->query($sql, [
        'qty' => $item['quantity'],
        'id' => $item['product_id']
    ]);
}

// Method B: Using InventoryModel (if update method supports it)
// Note: Current InventoryModel doesn't have decrementStock method
// You would need to add this method or use direct SQL
```

### Step 5: Verify No Negative Stock (Post-Transaction)

After updating, verify that no stock went negative (race condition check):

```php
// Inside transaction block, after stock updates

foreach ($items as $item) {
    $sql = "
        SELECT id, name, quantity 
        FROM products 
        WHERE id = :id 
        AND quantity < 0
    ";
    
    $stmt = $db->query($sql, ['id' => $item['product_id']]);
    $negativeStock = $stmt->fetch();
    
    if ($negativeStock) {
        throw new Exception(
            "Stock validation failed for product ID {$item['product_id']}. " .
            "Race condition detected - stock went negative."
        );
    }
}
```

### Step 6: Commit Transaction

If all validations pass, commit the transaction:

```php
// After all operations succeed
$db->commit();
```

## Complete Integration Examples

### Example 1: Sales Module Integration

Complete implementation showing how Sales module reserves and deducts inventory:

```php
<?php
// In modules/sales/models/SalesModel.php

class SalesModel
{
    private Database $db;
    private InventoryModel $inventoryModel;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        
        // Load InventoryModel
        require_once __DIR__ . '/../../inventory/models/InventoryModel.php';
        $this->inventoryModel = new InventoryModel();
    }
    
    /**
     * Create sales order with automatic inventory deduction
     */
    public function createSalesOrder(string $customerName, array $items): array
    {
        try {
            // ============================================================
            // PHASE 1: PRE-TRANSACTION VALIDATION
            // ============================================================
            
            // Validate stock availability for all items
            foreach ($items as $item) {
                $product = $this->inventoryModel->getProductById($item['product_id']);
                
                if (!$product) {
                    return [
                        'success' => false,
                        'message' => "Product ID {$item['product_id']} not found"
                    ];
                }
                
                if ($product['status'] !== 'active') {
                    return [
                        'success' => false,
                        'message' => "Product {$product['name']} is not available for sale"
                    ];
                }
                
                if ($product['quantity'] < $item['quantity']) {
                    return [
                        'success' => false,
                        'message' => "Insufficient stock for {$product['name']}. " .
                                   "Available: {$product['quantity']}, Requested: {$item['quantity']}"
                    ];
                }
            }
            
            // ============================================================
            // PHASE 2: TRANSACTION EXECUTION
            // ============================================================
            
            $this->db->beginTransaction();
            
            // Step 1: Create sales order
            $invoiceNumber = $this->generateInvoiceNumber();
            $totalAmount = array_sum(array_map(function($item) {
                return $item['quantity'] * $item['unit_price'];
            }, $items));
            
            $sql = "
                INSERT INTO sales_orders (invoice_number, customer_name, total_amount)
                VALUES (:invoice_number, :customer_name, :total_amount)
            ";
            
            $this->db->query($sql, [
                'invoice_number' => $invoiceNumber,
                'customer_name' => $customerName,
                'total_amount' => $totalAmount
            ]);
            
            $salesOrderId = (int)$this->db->lastInsertId();
            
            // Step 2: Insert line items and deduct inventory
            foreach ($items as $item) {
                // Insert sales line item
                $sql = "
                    INSERT INTO sales_items (
                        sales_order_id, product_id, quantity, unit_price, line_total
                    ) VALUES (
                        :sales_order_id, :product_id, :quantity, :unit_price, :line_total
                    )
                ";
                
                $lineTotal = $item['quantity'] * $item['unit_price'];
                
                $this->db->query($sql, [
                    'sales_order_id' => $salesOrderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $lineTotal
                ]);
                
                // Deduct inventory (ATOMIC OPERATION)
                $sql = "
                    UPDATE products 
                    SET quantity = quantity - :qty,
                        updated_at = NOW()
                    WHERE id = :id
                ";
                
                $this->db->query($sql, [
                    'qty' => $item['quantity'],
                    'id' => $item['product_id']
                ]);
            }
            
            // ============================================================
            // PHASE 3: POST-TRANSACTION VERIFICATION
            // ============================================================
            
            // Verify no stock went negative (race condition check)
            foreach ($items as $item) {
                $sql = "
                    SELECT id, name, quantity 
                    FROM products 
                    WHERE id = :id AND quantity < 0
                ";
                
                $stmt = $this->db->query($sql, ['id' => $item['product_id']]);
                $negativeStock = $stmt->fetch();
                
                if ($negativeStock) {
                    throw new Exception(
                        "Stock validation failed for product ID {$item['product_id']}. " .
                        "Quantity became negative: {$negativeStock['quantity']}"
                    );
                }
            }
            
            // Step 3: Record financial transaction (if Finance module integrated)
            // ... finance integration code ...
            
            // Commit transaction
            $this->db->commit();
            
            return [
                'success' => true,
                'sales_order_id' => $salesOrderId,
                'invoice_number' => $invoiceNumber,
                'total_amount' => $totalAmount
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $sql = "SELECT COUNT(*) as count FROM sales_orders WHERE invoice_number LIKE :pattern";
        $stmt = $this->db->query($sql, ['pattern' => "INV-{$year}-%"]);
        $result = $stmt->fetch();
        $nextNumber = ($result['count'] ?? 0) + 1;
        
        return sprintf("INV-%s-%04d", $year, $nextNumber);
    }
}
```

### Example 2: Manufacturing Module Integration

Manufacturing consumes raw materials and produces finished goods:

```php
<?php
// In modules/manufacturing/models/ManufacturingModel.php

class ManufacturingModel
{
    private Database $db;
    private InventoryModel $inventoryModel;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        
        require_once __DIR__ . '/../../inventory/models/InventoryModel.php';
        $this->inventoryModel = new InventoryModel();
    }
    
    /**
     * Process production run with BOM material consumption
     */
    public function processProduction(int $bomId, int $quantity): array
    {
        try {
            // Get BOM details
            $bom = $this->getBomById($bomId);
            if (!$bom) {
                return ['success' => false, 'message' => 'BOM not found'];
            }
            
            // Get BOM items (raw materials)
            $bomItems = $this->getBomItems($bomId);
            
            // ============================================================
            // PHASE 1: VALIDATE RAW MATERIAL AVAILABILITY
            // ============================================================
            
            foreach ($bomItems as $material) {
                $requiredQty = $material['quantity'] * $quantity;
                $product = $this->inventoryModel->getProductById($material['product_id']);
                
                if (!$product) {
                    return [
                        'success' => false,
                        'message' => "Raw material ID {$material['product_id']} not found"
                    ];
                }
                
                if ($product['quantity'] < $requiredQty) {
                    return [
                        'success' => false,
                        'message' => "Insufficient raw material: {$product['name']}. " .
                                   "Available: {$product['quantity']}, Required: {$requiredQty}"
                    ];
                }
            }
            
            // ============================================================
            // PHASE 2: EXECUTE PRODUCTION TRANSACTION
            // ============================================================
            
            $this->db->beginTransaction();
            
            // Step 1: Create production record
            $sql = "
                INSERT INTO production_runs (bom_id, quantity, status)
                VALUES (:bom_id, :quantity, 'completed')
            ";
            
            $this->db->query($sql, [
                'bom_id' => $bomId,
                'quantity' => $quantity
            ]);
            
            $productionId = (int)$this->db->lastInsertId();
            
            // Step 2: Deduct raw materials from inventory
            foreach ($bomItems as $material) {
                $requiredQty = $material['quantity'] * $quantity;
                
                $sql = "
                    UPDATE products 
                    SET quantity = quantity - :qty,
                        updated_at = NOW()
                    WHERE id = :id
                ";
                
                $this->db->query($sql, [
                    'qty' => $requiredQty,
                    'id' => $material['product_id']
                ]);
            }
            
            // Step 3: Add finished goods to inventory
            $sql = "
                UPDATE products 
                SET quantity = quantity + :qty,
                    updated_at = NOW()
                WHERE id = :id
            ";
            
            $this->db->query($sql, [
                'qty' => $quantity,
                'id' => $bom['finished_product_id']
            ]);
            
            // ============================================================
            // PHASE 3: VERIFY NO NEGATIVE STOCK
            // ============================================================
            
            foreach ($bomItems as $material) {
                $sql = "
                    SELECT id, name, quantity 
                    FROM products 
                    WHERE id = :id AND quantity < 0
                ";
                
                $stmt = $this->db->query($sql, ['id' => $material['product_id']]);
                $negativeStock = $stmt->fetch();
                
                if ($negativeStock) {
                    throw new Exception(
                        "Raw material stock validation failed for {$negativeStock['name']}"
                    );
                }
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'production_id' => $productionId,
                'quantity_produced' => $quantity
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
```

### Example 3: Procurement Module Integration

Procurement increases stock when purchase orders are received:

```php
<?php
// In modules/procurement/models/ProcurementModel.php

class ProcurementModel
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Receive purchase order and update inventory
     */
    public function receivePurchaseOrder(int $purchaseOrderId, array $receivedItems): array
    {
        try {
            $this->db->beginTransaction();
            
            // Update purchase order status
            $sql = "
                UPDATE purchase_orders 
                SET status = 'received',
                    received_date = NOW(),
                    updated_at = NOW()
                WHERE id = :id
            ";
            
            $this->db->query($sql, ['id' => $purchaseOrderId]);
            
            // Increase inventory for each received item
            foreach ($receivedItems as $item) {
                $sql = "
                    UPDATE products 
                    SET quantity = quantity + :qty,
                        updated_at = NOW()
                    WHERE id = :id
                ";
                
                $this->db->query($sql, [
                    'qty' => $item['quantity'],
                    'id' => $item['product_id']
                ]);
                
                // Record receipt in purchase order items
                $sql = "
                    UPDATE purchase_order_items 
                    SET received_quantity = :qty,
                        updated_at = NOW()
                    WHERE purchase_order_id = :po_id 
                    AND product_id = :product_id
                ";
                
                $this->db->query($sql, [
                    'qty' => $item['quantity'],
                    'po_id' => $purchaseOrderId,
                    'product_id' => $item['product_id']
                ]);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Purchase order received and inventory updated'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
```

## Read-Only Integration (No Stock Modification)

For modules that only need to read inventory data without modifying stock:

```php
// Simple product lookup
$inventoryModel = new InventoryModel();

// Get all active products for dropdown
$products = $inventoryModel->getProductsByStatus('active');

// Get specific product details
$product = $inventoryModel->getProductById($productId);

// Search products
$results = $inventoryModel->searchProducts('laptop');

// Get low stock alerts
$lowStockProducts = $inventoryModel->getLowStockProducts();

// Get inventory statistics
$stats = $inventoryModel->getSummaryStats();
```

## Best Practices

### 1. Always Use Transactions

```php
// ✅ CORRECT
$db->beginTransaction();
try {
    // Stock modifications
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}

// ❌ INCORRECT - No transaction
$sql = "UPDATE products SET quantity = quantity - 5 WHERE id = 1";
$db->query($sql);
```

### 2. Validate Before Transaction

```php
// ✅ CORRECT - Validate first, then transact
$product = $inventoryModel->getProductById($id);
if ($product['quantity'] < $requestedQty) {
    throw new Exception("Insufficient stock");
}

$db->beginTransaction();
// ... modifications ...
$db->commit();

// ❌ INCORRECT - Validate inside transaction (wastes resources)
$db->beginTransaction();
$product = $inventoryModel->getProductById($id);
if ($product['quantity'] < $requestedQty) {
    $db->rollback();
    throw new Exception("Insufficient stock");
}
```

### 3. Use Atomic SQL Operations

```php
// ✅ CORRECT - Atomic decrement
$sql = "UPDATE products SET quantity = quantity - :qty WHERE id = :id";
$db->query($sql, ['qty' => 5, 'id' => 1]);

// ❌ INCORRECT - Read-modify-write (race condition prone)
$product = $inventoryModel->getProductById(1);
$newQty = $product['quantity'] - 5;
$sql = "UPDATE products SET quantity = :qty WHERE id = :id";
$db->query($sql, ['qty' => $newQty, 'id' => 1]);
```

### 4. Always Verify Post-Transaction

```php
// ✅ CORRECT - Verify no negative stock
$sql = "SELECT id FROM products WHERE id = :id AND quantity < 0";
$check = $db->query($sql, ['id' => $productId])->fetch();
if ($check) {
    throw new Exception("Stock went negative");
}

// ❌ INCORRECT - Trust the update without verification
// (Race conditions can still cause negative stock)
```

## Troubleshooting

### Issue: "Insufficient stock" error during high concurrency

**Cause:** Multiple requests attempting to purchase the same product simultaneously  
**Solution:** This is expected behavior. The pre-transaction check prevents overselling. Consider implementing a queue system for high-demand products.

### Issue: Stock went negative despite validation

**Cause:** Race condition between pre-transaction check and actual update  
**Solution:** The post-transaction verification should catch this and rollback. Ensure you're implementing Phase 3 verification.

### Issue: Transaction deadlock

**Cause:** Multiple transactions waiting for each other to release locks  
**Solution:** 
- Always update products in the same order (e.g., by product ID ascending)
- Keep transactions as short as possible
- Implement retry logic with exponential backoff

### Issue: InventoryModel not found

**Cause:** Incorrect require_once path  
**Solution:** Use relative path from your module:
```php
require_once __DIR__ . '/../../inventory/models/InventoryModel.php';
```

## Verification Checklist

Before deploying inventory integration:

- [ ] Pre-transaction validation implemented
- [ ] Database transaction wraps all stock modifications
- [ ] Atomic SQL operations used (quantity = quantity - :qty)
- [ ] Post-transaction verification checks for negative stock
- [ ] Proper error handling with transaction rollback
- [ ] CSRF protection on all forms
- [ ] Input validation and sanitization
- [ ] Tested under concurrent load
- [ ] Logging implemented for audit trail
- [ ] Stock levels verified after integration testing

## Performance Considerations

### Batch Operations

When processing multiple items, batch the validation:

```php
// Get all products in one query
$productIds = array_column($items, 'product_id');
$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$sql = "SELECT * FROM products WHERE id IN ($placeholders)";
$products = $db->query($sql, $productIds)->fetchAll();

// Create lookup map
$productMap = [];
foreach ($products as $product) {
    $productMap[$product['id']] = $product;
}

// Validate using map
foreach ($items as $item) {
    $product = $productMap[$item['product_id']] ?? null;
    if (!$product || $product['quantity'] < $item['quantity']) {
        throw new Exception("Insufficient stock");
    }
}
```

## Support

For additional help:
- Review `modules/inventory/docs/README.md` for detailed documentation
- Review `modules/inventory/docs/ROUTING.md` for routing specifics
- Check error logs for specific failure points
- Verify database structure matches schema

---

**Module Version:** 2.0.0  
**Last Updated:** 2026-05-17  
**Author:** Bob