# 📊 Database Schema Design Process
## AI-Assisted Relational Database Architecture

---

## Overview

The AmenERP database schema was designed through an iterative, AI-assisted process that prioritized **data integrity**, **referential consistency**, and **performance optimization**. IBM Bob provided architectural guidance on normalization, foreign key relationships, and indexing strategies.

---

## Design Philosophy

### Core Principles

1. **Third Normal Form (3NF)**: Eliminate data redundancy while maintaining query performance
2. **InnoDB Engine**: ACID compliance and foreign key support
3. **UTF-8MB4 Charset**: Full Unicode support including emojis
4. **Timestamp Tracking**: Every table has `created_at` and `updated_at`
5. **Strategic Indexing**: Balance between read performance and write overhead
6. **Foreign Key Constraints**: Database-level referential integrity

---

## Schema Evolution Timeline

### Phase 1: Core Foundation (Initial Design)

**Objective**: Establish base tables for inventory management

```sql
-- Core Tables Created
categories (id, name, description)
products (id, category_id, name, sku, quantity, unit_price, status)
```

**AI Contribution**:
- Suggested `UNIQUE` constraint on SKU for business logic enforcement
- Recommended `ENUM` for status field to prevent invalid values
- Advised on proper indexing strategy for search operations

**Key Decisions**:
- ✅ Use `INT UNSIGNED` for IDs (supports 4.2 billion records)
- ✅ Use `DECIMAL(10,2)` for currency (prevents floating-point errors)
- ✅ Add `status` field for soft deletion pattern
- ✅ Index foreign keys automatically for JOIN performance

---

### Phase 2: Financial Module (Double-Entry Bookkeeping)

**Objective**: Implement complete accounting system

```sql
-- Financial Tables Created
accounts (id, code, name, type, balance)
transactions (id, description, date, total_amount)
ledger_entries (id, transaction_id, account_id, amount, entry_type)
```

**AI Contribution**:
- Designed double-entry bookkeeping structure
- Ensured transaction balancing through CHECK constraints
- Recommended separate ledger_entries table for audit trail

**Key Decisions**:
- ✅ Separate `transactions` (header) from `ledger_entries` (lines)
- ✅ Use `entry_type` ENUM ('debit', 'credit') for clarity
- ✅ Store `balance` in accounts table for performance (denormalization)
- ✅ Add `total_amount` to transactions for quick validation

**Balancing Logic**:
```sql
-- Every transaction must balance to zero
SELECT transaction_id, SUM(amount) as balance
FROM ledger_entries
GROUP BY transaction_id
HAVING SUM(amount) != 0;
-- Should return no rows
```

---

### Phase 3: Sales & Invoicing Module

**Objective**: Track sales orders and line items

```sql
-- Sales Tables Created
sales_orders (id, invoice_number, customer_name, total_amount, status)
sales_items (id, sales_order_id, product_id, quantity, unit_price, subtotal)
```

**AI Contribution**:
- Suggested header-detail pattern for order management
- Recommended `subtotal` denormalization for reporting performance
- Advised on CASCADE vs RESTRICT for foreign key deletes

**Key Decisions**:
- ✅ Store `total_amount` in header for quick access
- ✅ Store `subtotal` in line items (quantity × unit_price)
- ✅ Use `ON DELETE RESTRICT` to prevent orphaned line items
- ✅ Add `invoice_number` with UNIQUE constraint for business logic

**Integration Pattern**:
```sql
-- Sales order creation updates inventory
UPDATE products 
SET quantity = quantity - :sold_quantity 
WHERE id = :product_id;
```

---

### Phase 4: Procurement Module

**Objective**: Manage purchase orders and supplier relationships

```sql
-- Procurement Tables Created
purchase_orders (id, po_number, supplier_id, total_amount, status)
purchase_order_items (id, purchase_order_id, product_id, quantity, unit_price)
```

**AI Contribution**:
- Mirrored sales structure for consistency
- Suggested supplier integration with products table
- Recommended status tracking for order lifecycle

**Key Decisions**:
- ✅ Mirror sales_orders structure for developer familiarity
- ✅ Add `supplier_id` foreign key to purchase_orders
- ✅ Create migration to add `supplier_id` to products table
- ✅ Use same status ENUM values across modules

---

### Phase 5: HR & Payroll Module

**Objective**: Employee management and payroll processing

```sql
-- HR Tables Created
employees (id, name, email, position, salary, status)
payroll_runs (id, period_start, period_end, total_amount, status)
payroll_items (id, payroll_run_id, employee_id, gross_pay, deductions, net_pay)
```

**AI Contribution**:
- Designed batch payroll processing structure
- Recommended separate payroll_items for individual calculations
- Suggested period tracking for historical reporting

**Key Decisions**:
- ✅ Batch processing with `payroll_runs` header table
- ✅ Store calculated values (gross, deductions, net) for audit
- ✅ Add `period_start` and `period_end` for reporting
- ✅ Use `status` to track processing stages

---

### Phase 6: Customer & Supplier Modules

**Objective**: Account receivables and payables tracking

```sql
-- Customer/Supplier Tables Created
customers (id, name, email, phone, credit_limit, balance)
customer_transactions (id, customer_id, type, amount, description)
suppliers (id, name, email, phone, payment_terms, balance)
supplier_transactions (id, supplier_id, type, amount, description)
```

**AI Contribution**:
- Suggested parallel structure for customers and suppliers
- Recommended transaction history tables for audit trail
- Advised on balance caching for performance

**Key Decisions**:
- ✅ Cache `balance` in master tables (denormalization)
- ✅ Use `type` ENUM ('invoice', 'payment', 'credit', 'debit')
- ✅ Separate transaction tables for flexibility
- ✅ Add `credit_limit` for customers, `payment_terms` for suppliers

---

### Phase 7: Manufacturing Module

**Objective**: Bill of materials and production tracking

```sql
-- Manufacturing Tables Created
boms (id, finished_product_id, name, description)
bom_items (id, bom_id, product_id, quantity_required)
production_runs (id, bom_id, quantity_produced, status)
```

**AI Contribution**:
- Designed multi-level BOM structure
- Recommended production run tracking for inventory updates
- Suggested status tracking for manufacturing workflow

**Key Decisions**:
- ✅ Link BOMs to products table (finished goods)
- ✅ BOM items reference raw materials from products table
- ✅ Production runs update inventory automatically
- ✅ Support for future multi-level BOM expansion

---

## Complete Entity Relationship Diagram

```
┌─────────────┐
│ categories  │
│─────────────│
│ id (PK)     │
│ name        │
└──────┬──────┘
       │
       │ 1:N
       │
┌──────▼──────────┐         ┌──────────────┐
│ products        │◄────────┤ sales_items  │
│─────────────────│  N:1    │──────────────│
│ id (PK)         │         │ id (PK)      │
│ category_id(FK) │         │ sales_ord(FK)│
│ supplier_id(FK) │         │ product_id(FK)│
│ sku (UNIQUE)    │         └──────┬───────┘
│ quantity        │                │
│ unit_price      │                │ N:1
└────┬────────────┘                │
     │                       ┌─────▼────────┐
     │ 1:N                   │ sales_orders │
     │                       │──────────────│
     ├───────────────────────┤ id (PK)      │
     │                       │ invoice_num  │
     │                       └──────────────┘
     │
     │ 1:N              ┌──────────────────┐
     ├──────────────────┤ purchase_ord_itm │
     │                  │──────────────────│
     │                  │ id (PK)          │
     │                  │ purchase_ord(FK) │
     │                  │ product_id (FK)  │
     │                  └────────┬─────────┘
     │                           │ N:1
     │                           │
     │                  ┌────────▼─────────┐
     │                  │ purchase_orders  │
     │                  │──────────────────│
     │                  │ id (PK)          │
     │                  │ supplier_id (FK) │
     │                  └──────────────────┘
     │
     │ 1:N              ┌──────────────┐
     ├──────────────────┤ bom_items    │
     │                  │──────────────│
     │                  │ id (PK)      │
     │                  │ bom_id (FK)  │
     │                  │ product_id(FK)│
     │                  └──────┬───────┘
     │                         │ N:1
     │                         │
     │ 1:1            ┌────────▼─────┐
     └────────────────┤ boms         │
                      │──────────────│
                      │ id (PK)      │
                      │ finished_p(FK)│
                      └──────────────┘

┌──────────────┐         ┌──────────────────┐
│ accounts     │◄────────┤ ledger_entries   │
│──────────────│  1:N    │──────────────────│
│ id (PK)      │         │ id (PK)          │
│ code         │         │ transaction_id(FK)│
│ type         │         │ account_id (FK)  │
│ balance      │         │ amount           │
└──────────────┘         └────────┬─────────┘
                                  │ N:1
                                  │
                         ┌────────▼─────────┐
                         │ transactions     │
                         │──────────────────│
                         │ id (PK)          │
                         │ description      │
                         │ total_amount     │
                         └──────────────────┘

┌──────────────┐         ┌──────────────────────┐
│ customers    │◄────────┤ customer_transactions│
│──────────────│  1:N    │──────────────────────│
│ id (PK)      │         │ id (PK)              │
│ name         │         │ customer_id (FK)     │
│ balance      │         │ type                 │
└──────────────┘         └──────────────────────┘

┌──────────────┐         ┌──────────────────────┐
│ suppliers    │◄────────┤ supplier_transactions│
│──────────────│  1:N    │──────────────────────│
│ id (PK)      │         │ id (PK)              │
│ name         │         │ supplier_id (FK)     │
│ balance      │         │ type                 │
└──────────────┘         └──────────────────────┘

┌──────────────┐         ┌──────────────┐
│ employees    │◄────────┤ payroll_items│
│──────────────│  1:N    │──────────────│
│ id (PK)      │         │ id (PK)      │
│ name         │         │ payroll_r(FK)│
│ salary       │         │ employee_i(FK)│
└──────────────┘         └──────┬───────┘
                                │ N:1
                                │
                       ┌────────▼─────────┐
                       │ payroll_runs     │
                       │──────────────────│
                       │ id (PK)          │
                       │ period_start     │
                       │ period_end       │
                       └──────────────────┘
```

---

## Indexing Strategy

### Primary Indexes (Automatic)
- All `id` columns have PRIMARY KEY index
- Provides O(log n) lookup performance

### Foreign Key Indexes (Automatic)
- All foreign key columns automatically indexed by InnoDB
- Optimizes JOIN operations

### Business Logic Indexes
```sql
-- Unique constraints for business rules
UNIQUE KEY uk_products_sku (sku)
UNIQUE KEY uk_categories_name (name)
UNIQUE KEY uk_sales_orders_invoice_number (invoice_number)

-- Search optimization
KEY idx_products_name (name)
KEY idx_products_status (status)

-- Reporting optimization
KEY idx_transactions_date (date)
KEY idx_sales_orders_created_at (created_at)
```

### Index Selection Rationale

**When to Add Index**:
- ✅ Foreign keys (automatic)
- ✅ Columns in WHERE clauses
- ✅ Columns in JOIN conditions
- ✅ Columns in ORDER BY clauses
- ✅ Unique business identifiers (SKU, invoice numbers)

**When NOT to Add Index**:
- ❌ Low cardinality columns (few distinct values)
- ❌ Frequently updated columns (index maintenance overhead)
- ❌ Small tables (<1000 rows)
- ❌ Columns rarely used in queries

---

## Data Integrity Mechanisms

### 1. Foreign Key Constraints

```sql
-- Prevent orphaned records
CONSTRAINT fk_products_category 
  FOREIGN KEY (category_id) 
  REFERENCES categories(id) 
  ON DELETE RESTRICT 
  ON UPDATE CASCADE

-- Cascade deletes for dependent data
CONSTRAINT fk_sales_items_order 
  FOREIGN KEY (sales_order_id) 
  REFERENCES sales_orders(id) 
  ON DELETE CASCADE 
  ON UPDATE CASCADE
```

**Strategy**:
- Use `RESTRICT` for master data (categories, accounts)
- Use `CASCADE` for dependent data (line items, transactions)

### 2. Column Constraints

```sql
-- Prevent negative values
quantity INT NOT NULL DEFAULT 0 CHECK (quantity >= 0)

-- Enforce valid values
status ENUM('active', 'inactive', 'discontinued') NOT NULL DEFAULT 'active'

-- Require data
customer_name VARCHAR(200) NOT NULL
```

### 3. Unique Constraints

```sql
-- Business rule enforcement
UNIQUE KEY uk_products_sku (sku)
UNIQUE KEY uk_employees_email (email)
```

### 4. Default Values

```sql
-- Sensible defaults
quantity INT NOT NULL DEFAULT 0
status ENUM(...) NOT NULL DEFAULT 'active'
created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
```

---

## Performance Optimization Techniques

### 1. Denormalization (Strategic)

**Cached Balances**:
```sql
-- Store calculated balance for quick access
accounts.balance DECIMAL(15,2) NOT NULL DEFAULT 0.00
customers.balance DECIMAL(15,2) NOT NULL DEFAULT 0.00
```

**Cached Totals**:
```sql
-- Store calculated totals to avoid SUM() queries
sales_orders.total_amount DECIMAL(15,2) NOT NULL
sales_items.subtotal DECIMAL(15,2) NOT NULL
```

**Trade-off**: Faster reads, slower writes, requires consistency maintenance

### 2. Composite Indexes (Future Enhancement)

```sql
-- For complex queries
KEY idx_products_category_status (category_id, status)
KEY idx_sales_date_status (created_at, status)
```

### 3. Partitioning (Future Enhancement)

```sql
-- For large transaction tables
PARTITION BY RANGE (YEAR(created_at)) (
  PARTITION p2024 VALUES LESS THAN (2025),
  PARTITION p2025 VALUES LESS THAN (2026),
  PARTITION p2026 VALUES LESS THAN (2027)
);
```

---

## Migration Strategy

### Schema Versioning

Each module includes its own schema file:
```
modules/sales/database/schema.sql
modules/inventory/database/schema.sql
modules/finance/database/schema.sql
```

### Migration Files

For schema changes after initial deployment:
```
modules/procurement/database/migration_add_supplier_integration.sql
modules/suppliers/database/migration_add_supplier_to_products.sql
```

### Migration Pattern

```sql
-- Check if column exists before adding
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS supplier_id INT UNSIGNED NULL,
ADD CONSTRAINT fk_products_supplier 
  FOREIGN KEY (supplier_id) 
  REFERENCES suppliers(id) 
  ON DELETE SET NULL;
```

---

## Data Validation Layers

### Layer 1: Database Constraints
```sql
quantity INT NOT NULL CHECK (quantity >= 0)
unit_price DECIMAL(10,2) NOT NULL CHECK (unit_price >= 0)
```

### Layer 2: Application Logic
```php
if ($quantity < 0) {
    throw new Exception('Quantity cannot be negative');
}
```

### Layer 3: Business Rules
```php
if ($product['quantity'] < $requestedQuantity) {
    throw new Exception('Insufficient stock');
}
```

---

## Testing & Verification

### Integrity Checks

```sql
-- Verify transaction balancing
SELECT transaction_id, SUM(amount) as balance
FROM ledger_entries
GROUP BY transaction_id
HAVING ABS(SUM(amount)) > 0.01;

-- Verify account balances
SELECT a.id, a.balance - COALESCE(SUM(le.amount), 0) as difference
FROM accounts a
LEFT JOIN ledger_entries le ON a.id = le.account_id
GROUP BY a.id, a.balance
HAVING ABS(difference) > 0.01;

-- Check for negative stock
SELECT id, name, quantity
FROM products
WHERE quantity < 0;

-- Verify foreign key integrity
SELECT p.id, p.category_id
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
WHERE c.id IS NULL;
```

---

## AI Collaboration Highlights

### Key Contributions by IBM Bob

1. **Normalization Guidance**: Advised on proper 3NF structure while allowing strategic denormalization
2. **Foreign Key Strategy**: Recommended CASCADE vs RESTRICT based on data relationships
3. **Indexing Optimization**: Suggested indexes based on query patterns
4. **Double-Entry Design**: Architected complete accounting system structure
5. **Migration Patterns**: Provided safe migration strategies for schema evolution
6. **Integrity Checks**: Created SQL queries to verify data consistency

### Iterative Refinement Process

1. **Initial Design**: Bob proposed base schema structure
2. **Review & Feedback**: Developer reviewed and requested modifications
3. **Refinement**: Bob adjusted based on business requirements
4. **Implementation**: Schema deployed and tested
5. **Optimization**: Performance tuning based on usage patterns

---

## Lessons Learned

### What Worked Well
✅ **Modular Schema Files**: Each module owns its database structure  
✅ **Foreign Key Enforcement**: Prevented data integrity issues  
✅ **Strategic Denormalization**: Balanced performance with consistency  
✅ **Timestamp Tracking**: Invaluable for debugging and auditing  

### Challenges Overcome
🔧 **Balance Caching**: Required careful transaction management  
🔧 **Circular Dependencies**: Resolved with nullable foreign keys  
🔧 **Migration Coordination**: Needed clear ordering of schema imports  

### Future Improvements
🚀 **Soft Deletes**: Add `deleted_at` column for audit trail  
🚀 **Audit Tables**: Track all changes to critical data  
🚀 **Partitioning**: Implement for large transaction tables  
🚀 **Read Replicas**: Scale read operations independently  

---

**Document Version**: 1.0.0  
**Last Updated**: 2026-05-17  
**AI Assistant**: IBM Bob