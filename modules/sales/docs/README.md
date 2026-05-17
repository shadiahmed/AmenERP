# Sales & Invoicing Module

## Overview

The Sales & Invoicing Module is a fully integrated component of AmenERP that automates the complete sales workflow by connecting inventory management and financial accounting systems through ACID-compliant database transactions.

## Architecture

### Multi-Module Integration

This module implements a **three-way automated integration**:

1. **Sales Order Management** - Creates invoices and tracks line items
2. **Inventory Integration** - Automatically deducts sold quantities from stock
3. **Finance Integration** - Records double-entry accounting transactions

All operations are wrapped in database transactions to ensure data integrity. If any step fails, the entire operation is rolled back.

## Database Schema

### Tables Created

#### `sales_orders`
Master invoice/sales order records.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| invoice_number | VARCHAR(50) | Unique invoice identifier (e.g., INV-2026-0001) |
| customer_name | VARCHAR(255) | Customer or buyer name |
| total_amount | DECIMAL(15,2) | Total invoice amount |
| created_at | TIMESTAMP | Order creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

#### `sales_items`
Individual line items within each sales order.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| sales_order_id | INT UNSIGNED | Foreign key to sales_orders |
| product_id | INT UNSIGNED | Foreign key to products table |
| quantity | INT | Quantity sold |
| unit_price | DECIMAL(15,2) | Price per unit at time of sale |
| line_total | DECIMAL(15,2) | Calculated line total |
| created_at | TIMESTAMP | Item creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

### Foreign Key Relationships

```
sales_items.sales_order_id → sales_orders.id (CASCADE DELETE)
sales_items.product_id → products.id (RESTRICT DELETE)
```

## Installation

### 1. Database Setup

Run the schema file to create the required tables:

```bash
mysql -u your_user -p your_database < modules/sales/database/schema.sql
```

Or import via phpMyAdmin/Adminer.

### 2. Verify Dependencies

Ensure the following modules are installed and operational:

- **Inventory Module** (`modules/inventory/`)
  - Required: `products` table with `quantity` field
  - Required: `InventoryModel.php`

- **Finance Module** (`modules/finance/`)
  - Required: `accounts` table with Account IDs 1 (Cash) and 3 (Sales Income)
  - Required: `FinanceModel.php` with `addSimpleTransaction()` method

### 3. Account Configuration

The module expects these financial accounts to exist:

| Account ID | Name | Type | Purpose |
|------------|------|------|---------|
| 1 | Cash Safe / Bank Account | asset | Receives sales revenue |
| 3 | General Sales Income | income | Source of sales revenue |

You can modify these IDs in `SalesModel.php` constants:
```php
private const SALES_INCOME_ACCOUNT_ID = 3;
private const CASH_ACCOUNT_ID = 1;
```

## Usage

### Creating a Sale

1. Navigate to `/sales` in your browser
2. Enter customer name
3. Select products from the dropdown (shows current stock levels)
4. Enter quantities (validated against available stock)
5. Click "Complete Sale"

### What Happens Behind the Scenes

When you submit a sale, the system executes this sequence **atomically**:

```
BEGIN TRANSACTION

1. Validate all items have sufficient stock
2. Generate unique invoice number (INV-YYYY-NNNN)
3. Insert master record into sales_orders
4. For each item:
   a. Insert line item into sales_items
   b. UPDATE products SET quantity = quantity - sold_qty
   c. Verify stock didn't go negative
5. Call FinanceModel.addSimpleTransaction():
   - FROM: Sales Income Account (ID 3)
   - TO: Cash Account (ID 1)
   - AMOUNT: Total invoice amount
6. Record double-entry ledger entries
7. Update account balances

COMMIT TRANSACTION (or ROLLBACK on any error)
```

## API Reference

### SalesModel Methods

#### `createSalesOrder(string $customerName, array $items, ?int $cashAccountId = null): array`

Creates a complete sales order with automated integration.

**Parameters:**
- `$customerName` - Customer or buyer name
- `$items` - Array of items, each containing:
  - `product_id` (int) - Product ID from inventory
  - `quantity` (int) - Quantity to sell
  - `unit_price` (float) - Price per unit
- `$cashAccountId` - Optional cash account ID (defaults to 1)

**Returns:**
```php
[
    'success' => true|false,
    'sales_order_id' => int,      // If successful
    'invoice_number' => string,   // If successful
    'total_amount' => float,      // If successful
    'message' => string           // Error message if failed
]
```

**Example:**
```php
$salesModel = new SalesModel();
$result = $salesModel->createSalesOrder('John Doe', [
    ['product_id' => 1, 'quantity' => 2, 'unit_price' => 50.00],
    ['product_id' => 3, 'quantity' => 1, 'unit_price' => 150.00]
]);

if ($result['success']) {
    echo "Invoice: " . $result['invoice_number'];
    echo "Total: $" . $result['total_amount'];
}
```

#### `getAllSalesOrders(int $limit = 50): array`

Retrieves recent sales orders.

#### `getSalesOrderById(int $salesOrderId): ?array`

Gets complete order details including line items.

#### `getSalesStats(): array`

Returns sales statistics for dashboard display.

## Security Features

### CSRF Protection
All forms include CSRF tokens validated on submission.

### Input Validation
- Customer name: 2-255 characters, XSS-sanitized
- Product IDs: Integer validation
- Quantities: Positive integers only
- Prices: Positive floats with 2 decimal precision

### Stock Validation
- Pre-transaction check: Validates stock availability
- Post-transaction check: Ensures stock didn't go negative
- Automatic rollback if validation fails

### SQL Injection Prevention
All queries use PDO prepared statements with parameter binding.

## Error Handling

### Transaction Rollback Scenarios

The system automatically rolls back the entire transaction if:

1. **Insufficient Stock** - Any product has less stock than requested
2. **Invalid Product** - Product ID doesn't exist
3. **Negative Stock** - Stock would become negative after deduction
4. **Finance Error** - Financial transaction fails to record
5. **Database Error** - Any database operation fails

### User Feedback

Success and error messages are displayed via session flash messages:

```php
$_SESSION['success'] = 'Sale completed! Invoice: INV-2026-0001';
$_SESSION['error'] = 'Insufficient stock for product ID 5';
```

## File Structure

```
modules/sales/
├── README.md                           # This file
├── index.php                           # Main UI view
├── database/
│   └── schema.sql                      # Database schema
├── models/
│   └── SalesModel.php                  # Core business logic
└── controllers/
    └── CreateSaleController.php        # Form submission handler
```

## Integration Points

### With Inventory Module

**Read Operations:**
- Fetches active products for dropdown
- Checks stock availability

**Write Operations:**
- Updates `products.quantity` field
- Decrements stock by sold quantity

### With Finance Module

**Dependencies:**
- Requires `FinanceModel` class
- Uses `addSimpleTransaction()` method

**Accounting Flow:**
```
Sales Income (Account 3) → Cash Safe (Account 1)
```

This creates:
- Debit entry: Sales Income (-$amount)
- Credit entry: Cash Safe (+$amount)
- Updates both account balances

## Customization

### Changing Default Accounts

Edit `SalesModel.php` constants:

```php
// Default income account (where sales revenue comes from)
private const SALES_INCOME_ACCOUNT_ID = 3;

// Default cash account (where money goes)
private const CASH_ACCOUNT_ID = 1;
```

### Invoice Number Format

Modify the `generateInvoiceNumber()` method in `SalesModel.php`:

```php
// Current format: INV-2026-0001
$prefix = "INV-{$year}-";

// Custom format example: SALE-2026-0001
$prefix = "SALE-{$year}-";
```

### Adding Custom Fields

To add custom fields to sales orders:

1. Add column to `sales_orders` table
2. Update `createSalesOrder()` method in `SalesModel.php`
3. Add form field to `index.php`
4. Update controller validation in `CreateSaleController.php`

## Performance Considerations

### Database Indexes

The schema includes indexes on:
- `sales_orders.invoice_number` (UNIQUE)
- `sales_orders.customer_name`
- `sales_orders.created_at`
- `sales_items.sales_order_id`
- `sales_items.product_id`

### Transaction Optimization

- All operations in a single transaction
- Minimal database round-trips
- Efficient stock validation queries

## Troubleshooting

### "Product not found" Error
**Cause:** Product ID doesn't exist or is inactive  
**Solution:** Ensure product exists and status is 'active'

### "Insufficient stock" Error
**Cause:** Requested quantity exceeds available stock  
**Solution:** Check current stock levels in inventory module

### "Failed to record financial transaction" Error
**Cause:** Finance module integration issue  
**Solution:** Verify Account IDs 1 and 3 exist in `accounts` table

### Transaction Rollback
**Cause:** Any validation or database error  
**Solution:** Check error logs for specific failure point

## Future Enhancements

Potential features for future versions:

- [ ] Invoice PDF generation
- [ ] Email invoice to customer
- [ ] Payment method selection (cash/card/bank transfer)
- [ ] Partial payments and payment tracking
- [ ] Sales returns and refunds
- [ ] Discount and tax calculations
- [ ] Customer management system
- [ ] Sales reports and analytics
- [ ] Multi-currency support

## License

Part of AmenERP - Made with Bob

## Support

For issues or questions, refer to the main project documentation or contact the development team.