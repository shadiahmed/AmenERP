# Inventory Management Module

## Overview

The Inventory Management Module is a comprehensive warehouse tracking system that provides real-time product monitoring, stock level management, and inventory valuation. It serves as the central data source for all product-related operations across AmenERP, acting as a critical validation checkpoint to prevent overselling and material overdraws during Sales and Manufacturing execution loops.

## Architecture

### Multi-Module Integration

This module implements a **hub-and-spoke integration pattern** where inventory acts as the central authority for product availability:

1. **Product Master Data** - Maintains SKU registry, pricing, and categorization
2. **Stock Level Tracking** - Real-time quantity monitoring with threshold alerts
3. **Transactional Validation** - Prevents race-condition overselling through atomic stock checks
4. **Multi-Module Dependencies** - Provides stock verification services to Sales, Manufacturing, and Procurement modules

All stock-modifying operations must query inventory levels within active transaction blocks to ensure data consistency.

## Database Schema

### Tables Created

#### `categories`
Product classification and organizational hierarchy.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| name | VARCHAR(100) | Category name (unique) |
| description | TEXT | Category description |
| created_at | TIMESTAMP | Category creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

#### `products`
Master product inventory with SKU tracking and pricing.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| category_id | INT UNSIGNED | Foreign key to categories |
| name | VARCHAR(200) | Product name |
| sku | VARCHAR(50) | Stock Keeping Unit (unique) |
| quantity | INT | Current stock quantity |
| unit_price | DECIMAL(10,2) | Price per unit |
| status | ENUM | Product status (active, inactive, discontinued) |
| created_at | TIMESTAMP | Product creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

### Foreign Key Relationships

```
products.category_id → categories.id (RESTRICT DELETE, CASCADE UPDATE)
```

### Indexes for Performance

- `uk_products_sku` - Unique index on SKU for fast lookups
- `idx_products_category_id` - Index on category for filtered queries
- `idx_products_status` - Index on status for active product filtering
- `idx_products_name` - Index on name for search operations
- `uk_categories_name` - Unique index on category name

## Installation

### 1. Database Setup

Run the schema file to create the required tables:

```bash
mysql -u your_user -p your_database < modules/inventory/database/schema.sql
```

Or import via phpMyAdmin/Adminer.

### 2. Verify Dependencies

Ensure the following core components are operational:

- **Database Class** (`core/Database.php`)
  - Required: PDO singleton with prepared statement support
  
- **Router Class** (`core/Router.php`)
  - Required: Route registration and parameter extraction

- **CSRF Protection** (`core/Csrf.php`)
  - Required: Token generation and validation

### 3. Seed Data

The schema includes seed data for:
- 5 product categories (Electronics, Office Supplies, Furniture, Software, Hardware)
- 25 sample products with realistic SKUs and pricing

## Usage

### Managing Products

1. Navigate to `/inventory` in your browser
2. View real-time inventory metrics dashboard
3. Use "Add New Item" button to create products
4. Edit or delete products using action buttons in the table
5. Search products by name or SKU using the search bar

### Stock Level Monitoring

The dashboard displays three key metrics:

- **Total SKUs** - Unique product count
- **Low Stock Items** - Products below threshold (quantity < 20)
- **Warehouse Asset Value** - Total inventory value (quantity × unit_price)

### Stock Status Indicators

Products are automatically categorized:

- **In Stock** (Green) - Quantity ≥ 20
- **Low Stock** (Yellow) - Quantity 1-19
- **Out of Stock** (Red) - Quantity = 0

## API Reference

### InventoryModel Methods

#### `getAllProducts(): array`

Retrieves all products with category information.

**Returns:**
```php
[
    [
        'id' => 1,
        'name' => 'Wireless Mouse',
        'sku' => 'ELEC-MOUSE-001',
        'quantity' => 45,
        'unit_price' => 15.99,
        'status' => 'active',
        'category_id' => 1,
        'category_name' => 'Electronics',
        'created_at' => '2026-05-17 10:00:00',
        'updated_at' => '2026-05-17 10:00:00'
    ],
    // ... more products
]
```

#### `getProductById(int $productId): ?array`

Retrieves a single product by ID.

**Parameters:**
- `$productId` - Product ID to fetch

**Returns:** Product array or null if not found

#### `getSummaryStats(): array`

Returns inventory dashboard statistics.

**Returns:**
```php
[
    'total_items' => 25,
    'total_value' => 12500.50,
    'low_stock_count' => 3,
    'active_products' => 23,
    'total_categories' => 5
]
```

#### `getLowStockProducts(): array`

Returns products below the low stock threshold (quantity < 20).

**Returns:** Array of low stock products

#### `addProduct(array $data): bool`

Adds a new product to inventory.

**Parameters:**
```php
$data = [
    'name' => 'Product Name',
    'sku' => 'PROD-001',
    'category_id' => 1,
    'quantity' => 100,
    'unit_price' => 49.99,
    'status' => 'active'
];
```

**Returns:** True on success, false on failure

**Example:**
```php
$inventoryModel = new InventoryModel();
$result = $inventoryModel->addProduct([
    'name' => 'Laptop Computer',
    'sku' => 'TECH-001',
    'category_id' => 1,
    'quantity' => 50,
    'unit_price' => 999.99,
    'status' => 'active'
]);
```

#### `updateProduct(int $productId, array $data): bool`

Updates an existing product.

**Parameters:**
- `$productId` - Product ID to update
- `$data` - Array of fields to update (same structure as addProduct)

**Returns:** True on success, false on failure

#### `deleteProduct(int $productId): bool`

Permanently removes a product from inventory.

**WARNING:** This is a hard delete. Consider implementing soft deletes (status = 'deleted') for production systems.

**Parameters:**
- `$productId` - Product ID to delete

**Returns:** True on success, false on failure

#### `skuExists(string $sku, ?int $excludeProductId = null): bool`

Checks if a SKU already exists in the database.

**Parameters:**
- `$sku` - SKU to check
- `$excludeProductId` - Optional product ID to exclude (for edit operations)

**Returns:** True if SKU exists, false otherwise

**Example:**
```php
$inventoryModel = new InventoryModel();
if ($inventoryModel->skuExists('PROD-001')) {
    echo "SKU already exists";
}
```

#### `searchProducts(string $searchTerm): array`

Performs case-insensitive search on product name and SKU.

**Parameters:**
- `$searchTerm` - Search term

**Returns:** Array of matching products

## Integration with Other Modules

### Sales Module Integration

The Sales module queries inventory to verify stock availability before completing transactions:

```php
// Inside Sales transaction block
$inventoryModel = new InventoryModel();

// Step 1: Verify stock availability
foreach ($items as $item) {
    $product = $inventoryModel->getProductById($item['product_id']);
    
    if (!$product || $product['quantity'] < $item['quantity']) {
        throw new Exception("Insufficient stock for product ID {$item['product_id']}");
    }
}

// Step 2: Deduct stock within transaction
foreach ($items as $item) {
    $sql = "UPDATE products SET quantity = quantity - :qty WHERE id = :id";
    $db->query($sql, ['qty' => $item['quantity'], 'id' => $item['product_id']]);
}

// Step 3: Verify no negative stock (race condition check)
$sql = "SELECT id FROM products WHERE id = :id AND quantity < 0";
$check = $db->query($sql, ['id' => $item['product_id']])->fetch();

if ($check) {
    throw new Exception("Stock validation failed - race condition detected");
}
```

### Manufacturing Module Integration

Manufacturing processes consume raw materials and produce finished goods:

```php
// Inside Manufacturing transaction block
$inventoryModel = new InventoryModel();

// Step 1: Verify raw material availability
foreach ($bomItems as $material) {
    $product = $inventoryModel->getProductById($material['product_id']);
    
    if ($product['quantity'] < $material['required_quantity']) {
        throw new Exception("Insufficient raw material: {$product['name']}");
    }
}

// Step 2: Deduct raw materials
foreach ($bomItems as $material) {
    $sql = "UPDATE products SET quantity = quantity - :qty WHERE id = :id";
    $db->query($sql, ['qty' => $material['required_quantity'], 'id' => $material['product_id']]);
}

// Step 3: Add finished goods
$sql = "UPDATE products SET quantity = quantity + :qty WHERE id = :id";
$db->query($sql, ['qty' => $finishedQuantity, 'id' => $finishedProductId]);
```

### Procurement Module Integration

Procurement increases stock levels when purchase orders are received:

```php
// Inside Procurement transaction block
$sql = "UPDATE products SET quantity = quantity + :qty WHERE id = :id";
$db->query($sql, ['qty' => $receivedQuantity, 'id' => $productId]);
```

## Minimum Threshold Logic

The system implements a configurable low stock threshold:

```php
private const LOW_STOCK_THRESHOLD = 20;
```

Products with `quantity < LOW_STOCK_THRESHOLD` are flagged as "Low Stock" and appear in:
- Dashboard metrics (`low_stock_count`)
- `getLowStockProducts()` method results
- Visual indicators in the product table (yellow badge)

### Customizing the Threshold

Edit the constant in `modules/inventory/models/InventoryModel.php`:

```php
private const LOW_STOCK_THRESHOLD = 50; // Change to your desired threshold
```

## Race Condition Prevention

The module prevents overselling through a two-phase validation pattern:

### Phase 1: Pre-Transaction Check
```php
// Before starting transaction
$product = $inventoryModel->getProductById($productId);
if ($product['quantity'] < $requestedQuantity) {
    throw new Exception("Insufficient stock");
}
```

### Phase 2: Post-Transaction Verification
```php
// After updating stock within transaction
$sql = "SELECT quantity FROM products WHERE id = :id";
$result = $db->query($sql, ['id' => $productId])->fetch();

if ($result['quantity'] < 0) {
    // Rollback transaction
    throw new Exception("Race condition detected - stock went negative");
}
```

This pattern ensures that even if multiple requests attempt to purchase the same product simultaneously, the database transaction isolation prevents overselling.

## Security Features

### CSRF Protection
All forms include CSRF tokens validated on submission.

### Input Validation
- Product name: 2-100 characters, XSS-sanitized
- SKU: Uppercase letters, numbers, and hyphens only (pattern: `[A-Z0-9\-]+`)
- Quantity: Non-negative integers (0-999999)
- Unit price: Positive floats with 2 decimal precision (min: 0.01)
- Status: Enum validation (active, inactive, discontinued)

### SKU Uniqueness
Real-time AJAX validation prevents duplicate SKU entries:
- Debounced API calls (500ms delay)
- Case-insensitive duplicate checking
- Visual feedback (✓ available / ⚠️ already in use)

### SQL Injection Prevention
All queries use PDO prepared statements with parameter binding.

### XSS Prevention
All output uses `htmlspecialchars()` with `ENT_QUOTES` and `UTF-8` encoding.

## File Structure

```
modules/inventory/
├── docs/
│   ├── README.md                       # This file
│   ├── ROUTING.md                      # Route configuration guide
│   └── INTEGRATION_GUIDE.md            # Integration instructions
├── index.php                           # Main dashboard view
├── add.php                             # Add product form (deprecated - use modal)
├── database/
│   └── schema.sql                      # Database schema with seed data
├── models/
│   └── InventoryModel.php              # Core business logic
├── controllers/
│   ├── AddProductController.php        # Add product handler
│   ├── EditProductController.php       # Edit product handler
│   └── DeleteProductController.php     # Delete product handler
└── api/
    ├── search.php                      # Product search API
    └── check-sku.php                   # SKU validation API
```

## Performance Considerations

### Database Indexes

The schema includes strategic indexes for optimal query performance:
- Unique index on SKU for O(1) lookups
- Index on category_id for filtered queries
- Index on status for active product filtering
- Index on name for search operations

### Query Optimization

- Uses `INNER JOIN` for category data (single query instead of N+1)
- Implements `COALESCE()` for null-safe aggregations
- Limits result sets with configurable pagination
- Caches summary statistics in single query

### Real-Time Search

Client-side search implementation:
- No server round-trips for filtering
- Instant results as user types
- Searches both name and SKU fields
- Updates visible count dynamically

## Troubleshooting

### "SKU already exists" Error
**Cause:** Attempting to create/update product with duplicate SKU  
**Solution:** Choose a unique SKU or update the existing product

### "Category not found" Error
**Cause:** Selected category doesn't exist  
**Solution:** Verify categories table has data, run seed data if needed

### "Foreign key constraint fails" Error
**Cause:** Attempting to delete category with associated products  
**Solution:** Delete or reassign products first, or change status to 'discontinued'

### Low Stock Alerts Not Showing
**Cause:** Threshold constant may be too low  
**Solution:** Adjust `LOW_STOCK_THRESHOLD` constant in InventoryModel.php

### Search Not Working
**Cause:** JavaScript not loaded or data attributes missing  
**Solution:** Verify `data-name` and `data-sku` attributes on table rows

## Future Enhancements

Potential features for future versions:

- [ ] Stock adjustment history tracking
- [ ] Barcode scanning integration
- [ ] Multi-warehouse location tracking
- [ ] Automated reorder point calculations
- [ ] Batch/lot number tracking
- [ ] Expiration date management
- [ ] Stock transfer between locations
- [ ] Inventory valuation methods (FIFO, LIFO, Weighted Average)
- [ ] Physical inventory count reconciliation
- [ ] Product image uploads
- [ ] Supplier integration for automatic reordering
- [ ] Stock movement audit trail

## License

Part of AmenERP - Made with Bob

## Support

For issues or questions, refer to the main project documentation or contact the development team.