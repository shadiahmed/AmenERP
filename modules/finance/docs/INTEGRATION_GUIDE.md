# Finance Module - Integration Guide for External Modules

## Quick Integration Overview

The Finance module serves as the **central accounting engine** for AmenERP. All modules that handle monetary transactions (Sales, Procurement, HR, Manufacturing) must integrate with Finance to record double-entry journal entries and maintain accurate financial records.

## Core Integration Pattern

All financial integrations follow the double-entry bookkeeping pattern:

```php
public function recordJournalEntry(
    int $debitAccount,      // Account to debit (money leaving)
    int $creditAccount,     // Account to credit (money entering)
    float $amount,          // Transaction amount
    string $memo            // Transaction description
): array
```

## Step-by-Step Integration for External Modules

### Step 1: Load the FinanceModel

External modules must load and instantiate the FinanceModel:

```php
// In your module controller or model
require_once __DIR__ . '/../../finance/models/FinanceModel.php';

$financeModel = new FinanceModel();
```

### Step 2: Understand Account Types and IDs

Before recording transactions, understand the default chart of accounts:

| ID | Account Name | Type | Purpose |
|----|--------------|------|---------|
| 1 | Cash Safe | asset | Physical cash on hand |
| 2 | Bank Account | asset | Bank deposits |
| 3 | General Sales Income | income | Revenue from sales |
| 4 | Utilities Expense | expense | Utility bills |
| 5 | Inventory Purchase Expense | expense | Cost of goods purchased |
| 6 | Accounts Payable | liability | Amounts owed to suppliers |

### Step 3: Record the Transaction

Use the `addSimpleTransaction()` method within your module's transaction block:

```php
try {
    // Your module's transaction logic here
    
    // Record financial transaction
    $result = $financeModel->addSimpleTransaction(
        $description,      // Transaction description
        $fromAccountId,    // Source account (money leaves)
        $toAccountId,      // Destination account (money enters)
        $amount,           // Transaction amount (positive)
        $date              // Transaction date (YYYY-MM-DD)
    );
    
    if (!$result) {
        throw new Exception("Failed to record financial transaction");
    }
    
} catch (Exception $e) {
    // Handle error
    throw $e;
}
```

## Complete Integration Examples

### Example 1: Sales Module Integration

Complete implementation showing how Sales module records revenue:

```php
<?php
// In modules/sales/models/SalesModel.php

class SalesModel
{
    private Database $db;
    private const SALES_INCOME_ACCOUNT_ID = 3;  // General Sales Income
    private const CASH_ACCOUNT_ID = 1;          // Cash Safe
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create sales order with automatic financial recording
     */
    public function createSalesOrder(
        string $customerName, 
        array $items, 
        ?int $cashAccountId = null
    ): array {
        try {
            $this->db->beginTransaction();
            
            // Calculate total
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }
            
            // Step 1: Create sales order
            $invoiceNumber = $this->generateInvoiceNumber();
            
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
            
            // Step 2: Insert line items and update inventory
            foreach ($items as $item) {
                // Insert sales item
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
                
                // Update inventory
                $sql = "
                    UPDATE products 
                    SET quantity = quantity - :qty 
                    WHERE id = :id
                ";
                
                $this->db->query($sql, [
                    'qty' => $item['quantity'],
                    'id' => $item['product_id']
                ]);
            }
            
            // ============================================================
            // STEP 3: RECORD FINANCIAL TRANSACTION
            // ============================================================
            
            // Load FinanceModel
            require_once __DIR__ . '/../../finance/models/FinanceModel.php';
            $financeModel = new FinanceModel();
            
            // Determine which cash account to use
            $targetCashAccount = $cashAccountId ?? self::CASH_ACCOUNT_ID;
            
            // Record revenue transaction
            // FROM: Sales Income (income account)
            // TO: Cash Safe (asset account)
            $financialResult = $financeModel->addSimpleTransaction(
                "Sales Invoice: {$invoiceNumber} - {$customerName}",
                self::SALES_INCOME_ACCOUNT_ID,  // FROM: Income account
                $targetCashAccount,              // TO: Asset account
                $totalAmount,
                date('Y-m-d')
            );
            
            if (!$financialResult) {
                throw new Exception("Failed to record financial transaction");
            }
            
            // Commit all changes
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

**Accounting Entry Created:**
```
Debit:  General Sales Income (Income) -$1,000.00
Credit: Cash Safe (Asset) +$1,000.00

Result: Income increases, Assets increase
```

### Example 2: Procurement Module Integration

Procurement module records purchase expenses:

```php
<?php
// In modules/procurement/models/ProcurementModel.php

class ProcurementModel
{
    private Database $db;
    private const INVENTORY_EXPENSE_ACCOUNT_ID = 5;  // Inventory Purchase Expense
    private const ACCOUNTS_PAYABLE_ID = 6;           // Accounts Payable
    private const CASH_ACCOUNT_ID = 1;               // Cash Safe
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create purchase order with financial recording
     */
    public function createPurchaseOrder(
        int $supplierId,
        array $items,
        string $paymentMethod = 'credit'  // 'cash' or 'credit'
    ): array {
        try {
            $this->db->beginTransaction();
            
            // Calculate total
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }
            
            // Step 1: Create purchase order
            $poNumber = $this->generatePONumber();
            
            $sql = "
                INSERT INTO purchase_orders (
                    po_number, supplier_id, total_amount, status
                ) VALUES (
                    :po_number, :supplier_id, :total_amount, 'pending'
                )
            ";
            
            $this->db->query($sql, [
                'po_number' => $poNumber,
                'supplier_id' => $supplierId,
                'total_amount' => $totalAmount
            ]);
            
            $purchaseOrderId = (int)$this->db->lastInsertId();
            
            // Step 2: Insert line items
            foreach ($items as $item) {
                $sql = "
                    INSERT INTO purchase_order_items (
                        purchase_order_id, product_id, quantity, unit_price
                    ) VALUES (
                        :po_id, :product_id, :quantity, :unit_price
                    )
                ";
                
                $this->db->query($sql, [
                    'po_id' => $purchaseOrderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price']
                ]);
            }
            
            // ============================================================
            // STEP 3: RECORD FINANCIAL TRANSACTION
            // ============================================================
            
            require_once __DIR__ . '/../../finance/models/FinanceModel.php';
            $financeModel = new FinanceModel();
            
            if ($paymentMethod === 'cash') {
                // Cash purchase: Pay immediately
                // FROM: Cash Safe (asset)
                // TO: Inventory Purchase Expense (expense)
                $financialResult = $financeModel->addSimpleTransaction(
                    "Purchase Order: {$poNumber} - Cash Payment",
                    self::CASH_ACCOUNT_ID,              // FROM: Asset account
                    self::INVENTORY_EXPENSE_ACCOUNT_ID, // TO: Expense account
                    $totalAmount,
                    date('Y-m-d')
                );
            } else {
                // Credit purchase: Record payable
                // FROM: Accounts Payable (liability)
                // TO: Inventory Purchase Expense (expense)
                $financialResult = $financeModel->addSimpleTransaction(
                    "Purchase Order: {$poNumber} - On Credit",
                    self::ACCOUNTS_PAYABLE_ID,          // FROM: Liability account
                    self::INVENTORY_EXPENSE_ACCOUNT_ID, // TO: Expense account
                    $totalAmount,
                    date('Y-m-d')
                );
            }
            
            if (!$financialResult) {
                throw new Exception("Failed to record financial transaction");
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'purchase_order_id' => $purchaseOrderId,
                'po_number' => $poNumber,
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
    
    /**
     * Pay supplier (settle accounts payable)
     */
    public function paySupplier(int $supplierId, float $amount): array
    {
        try {
            $this->db->beginTransaction();
            
            // Record payment
            $sql = "
                INSERT INTO supplier_payments (supplier_id, amount, payment_date)
                VALUES (:supplier_id, :amount, NOW())
            ";
            
            $this->db->query($sql, [
                'supplier_id' => $supplierId,
                'amount' => $amount
            ]);
            
            // ============================================================
            // RECORD FINANCIAL TRANSACTION
            // ============================================================
            
            require_once __DIR__ . '/../../finance/models/FinanceModel.php';
            $financeModel = new FinanceModel();
            
            // FROM: Cash Safe (asset)
            // TO: Accounts Payable (liability)
            $financialResult = $financeModel->addSimpleTransaction(
                "Supplier Payment - Supplier ID: {$supplierId}",
                self::CASH_ACCOUNT_ID,      // FROM: Asset account
                self::ACCOUNTS_PAYABLE_ID,  // TO: Liability account
                $amount,
                date('Y-m-d')
            );
            
            if (!$financialResult) {
                throw new Exception("Failed to record payment transaction");
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Payment recorded successfully'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function generatePONumber(): string
    {
        $year = date('Y');
        $sql = "SELECT COUNT(*) as count FROM purchase_orders WHERE po_number LIKE :pattern";
        $stmt = $this->db->query($sql, ['pattern' => "PO-{$year}-%"]);
        $result = $stmt->fetch();
        $nextNumber = ($result['count'] ?? 0) + 1;
        
        return sprintf("PO-%s-%04d", $year, $nextNumber);
    }
}
```

**Accounting Entries Created:**

**Cash Purchase:**
```
Debit:  Inventory Purchase Expense (Expense) +$2,000.00
Credit: Cash Safe (Asset) -$2,000.00

Result: Expenses increase, Assets decrease
```

**Credit Purchase:**
```
Debit:  Inventory Purchase Expense (Expense) +$2,000.00
Credit: Accounts Payable (Liability) +$2,000.00

Result: Expenses increase, Liabilities increase
```

**Supplier Payment:**
```
Debit:  Accounts Payable (Liability) -$1,000.00
Credit: Cash Safe (Asset) -$1,000.00

Result: Liabilities decrease, Assets decrease
```

### Example 3: HR Module Integration

HR module records payroll expenses:

```php
<?php
// In modules/hr/models/HRModel.php

class HRModel
{
    private Database $db;
    private const SALARIES_EXPENSE_ACCOUNT_ID = 7;  // Add this account
    private const CASH_ACCOUNT_ID = 1;              // Cash Safe
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Process payroll with financial recording
     */
    public function processPayroll(string $period, array $employees): array
    {
        try {
            $this->db->beginTransaction();
            
            // Calculate total payroll
            $totalPayroll = 0;
            foreach ($employees as $employee) {
                $totalPayroll += $employee['salary'];
            }
            
            // Step 1: Create payroll record
            $sql = "
                INSERT INTO payroll_runs (period, total_amount, status)
                VALUES (:period, :total_amount, 'processed')
            ";
            
            $this->db->query($sql, [
                'period' => $period,
                'total_amount' => $totalPayroll
            ]);
            
            $payrollId = (int)$this->db->lastInsertId();
            
            // Step 2: Record individual employee payments
            foreach ($employees as $employee) {
                $sql = "
                    INSERT INTO payroll_items (
                        payroll_run_id, employee_id, amount
                    ) VALUES (
                        :payroll_id, :employee_id, :amount
                    )
                ";
                
                $this->db->query($sql, [
                    'payroll_id' => $payrollId,
                    'employee_id' => $employee['id'],
                    'amount' => $employee['salary']
                ]);
            }
            
            // ============================================================
            // RECORD FINANCIAL TRANSACTION
            // ============================================================
            
            require_once __DIR__ . '/../../finance/models/FinanceModel.php';
            $financeModel = new FinanceModel();
            
            // FROM: Cash Safe (asset)
            // TO: Salaries Expense (expense)
            $financialResult = $financeModel->addSimpleTransaction(
                "Payroll for period: {$period}",
                self::CASH_ACCOUNT_ID,              // FROM: Asset account
                self::SALARIES_EXPENSE_ACCOUNT_ID,  // TO: Expense account
                $totalPayroll,
                date('Y-m-d')
            );
            
            if (!$financialResult) {
                throw new Exception("Failed to record payroll transaction");
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'payroll_id' => $payrollId,
                'total_amount' => $totalPayroll
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

**Accounting Entry Created:**
```
Debit:  Salaries Expense (Expense) +$10,000.00
Credit: Cash Safe (Asset) -$10,000.00

Result: Expenses increase, Assets decrease
```

## Understanding Transaction Direction

### The FROM-TO Pattern

The `addSimpleTransaction()` method uses a FROM-TO pattern:

```php
$financeModel->addSimpleTransaction(
    $description,
    $fromAccountId,  // Money LEAVES this account (Debit)
    $toAccountId,    // Money ENTERS this account (Credit)
    $amount,
    $date
);
```

### Common Transaction Patterns

#### Pattern 1: Recording Income
```php
// Money flows FROM income account TO asset account
$financeModel->addSimpleTransaction(
    'Sales revenue',
    3,  // FROM: General Sales Income (income)
    1,  // TO: Cash Safe (asset)
    1000.00,
    date('Y-m-d')
);
```

#### Pattern 2: Recording Expense
```php
// Money flows FROM asset account TO expense account
$financeModel->addSimpleTransaction(
    'Utility bill payment',
    1,  // FROM: Cash Safe (asset)
    4,  // TO: Utilities Expense (expense)
    500.00,
    date('Y-m-d')
);
```

#### Pattern 3: Recording Liability (Purchase on Credit)
```php
// Money flows FROM liability account TO expense account
$financeModel->addSimpleTransaction(
    'Purchase on credit',
    6,  // FROM: Accounts Payable (liability)
    5,  // TO: Inventory Purchase Expense (expense)
    2000.00,
    date('Y-m-d')
);
```

#### Pattern 4: Paying Liability
```php
// Money flows FROM asset account TO liability account
$financeModel->addSimpleTransaction(
    'Pay supplier',
    1,  // FROM: Cash Safe (asset)
    6,  // TO: Accounts Payable (liability)
    1000.00,
    date('Y-m-d')
);
```

#### Pattern 5: Transfer Between Accounts
```php
// Money flows FROM one asset TO another asset
$financeModel->addSimpleTransaction(
    'Cash deposit to bank',
    1,  // FROM: Cash Safe (asset)
    2,  // TO: Bank Account (asset)
    5000.00,
    date('Y-m-d')
);
```

## Adding Custom Accounts

If your module needs custom accounts, add them to the database:

```sql
-- Add Salaries Expense account for HR module
INSERT INTO accounts (name, type, balance)
VALUES ('Salaries Expense', 'expense', 0.00);

-- Add Accounts Receivable for customer credit sales
INSERT INTO accounts (name, type, balance)
VALUES ('Accounts Receivable', 'asset', 0.00);

-- Add Sales Tax Payable for tax tracking
INSERT INTO accounts (name, type, balance)
VALUES ('Sales Tax Payable', 'liability', 0.00);
```

Then use the account ID in your integration:

```php
// Get the account ID
$sql = "SELECT id FROM accounts WHERE name = 'Salaries Expense' LIMIT 1";
$stmt = $db->query($sql);
$account = $stmt->fetch();
$salariesAccountId = $account['id'];

// Use in transaction
$financeModel->addSimpleTransaction(
    'Payroll',
    1,                    // FROM: Cash Safe
    $salariesAccountId,   // TO: Salaries Expense
    10000.00,
    date('Y-m-d')
);
```

## Best Practices

### 1. Always Record Within Transactions

```php
// ✅ CORRECT
$db->beginTransaction();
try {
    // Your module operations
    
    // Record financial transaction
    $result = $financeModel->addSimpleTransaction(...);
    if (!$result) {
        throw new Exception("Financial recording failed");
    }
    
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}

// ❌ INCORRECT - No transaction wrapper
$financeModel->addSimpleTransaction(...);
```

### 2. Verify Account Existence

```php
// ✅ CORRECT - Verify accounts exist
$sql = "SELECT id FROM accounts WHERE id IN (:id1, :id2)";
$stmt = $db->query($sql, ['id1' => $fromId, 'id2' => $toId]);
$accounts = $stmt->fetchAll();

if (count($accounts) !== 2) {
    throw new Exception("One or more accounts not found");
}

// Then record transaction
$financeModel->addSimpleTransaction(...);
```

### 3. Use Descriptive Transaction Descriptions

```php
// ✅ CORRECT - Clear, detailed description
$description = "Sales Invoice: INV-2026-0001 - John Doe - 5 items";

// ❌ INCORRECT - Vague description
$description = "Sale";
```

### 4. Handle Financial Recording Failures

```php
// ✅ CORRECT - Check result and handle failure
$result = $financeModel->addSimpleTransaction(...);

if (!$result) {
    throw new Exception("Failed to record financial transaction");
}

// ❌ INCORRECT - Ignore result
$financeModel->addSimpleTransaction(...);
// Continue without checking
```

### 5. Use Constants for Account IDs

```php
// ✅ CORRECT - Use constants
class SalesModel
{
    private const SALES_INCOME_ACCOUNT_ID = 3;
    private const CASH_ACCOUNT_ID = 1;
    
    public function recordSale() {
        $financeModel->addSimpleTransaction(
            'Sale',
            self::SALES_INCOME_ACCOUNT_ID,
            self::CASH_ACCOUNT_ID,
            1000.00,
            date('Y-m-d')
        );
    }
}

// ❌ INCORRECT - Magic numbers
$financeModel->addSimpleTransaction('Sale', 3, 1, 1000.00, date('Y-m-d'));
```

## Troubleshooting

### Issue: "Failed to record financial transaction"

**Cause:** Transaction failed in FinanceModel  
**Solution:** 
- Check that both account IDs exist
- Verify amount is positive
- Check date format is YYYY-MM-DD
- Review error logs for specific PDO errors

### Issue: Accounts don't balance

**Cause:** Logic error in transaction recording  
**Solution:** 
- Verify you're using `addSimpleTransaction()` correctly
- Check database: `SELECT transaction_id, SUM(amount) FROM ledger_entries GROUP BY transaction_id HAVING SUM(amount) != 0`
- This should return no rows (all transactions balanced)

### Issue: Account balance incorrect

**Cause:** Cached balance out of sync with ledger  
**Solution:** Recalculate balance from ledger:
```sql
UPDATE accounts a
SET balance = (
    SELECT COALESCE(SUM(amount), 0)
    FROM ledger_entries
    WHERE account_id = a.id
)
WHERE id = :account_id;
```

### Issue: Transaction recorded but module operation failed

**Cause:** Financial recording succeeded but module transaction rolled back  
**Solution:** Ensure financial recording is INSIDE your module's transaction block, not after commit

## Verification Checklist

Before deploying finance integration:

- [ ] FinanceModel loaded correctly
- [ ] Account IDs verified to exist
- [ ] Transaction wrapped in database transaction
- [ ] Financial recording inside transaction block
- [ ] Result of `addSimpleTransaction()` checked
- [ ] Error handling with rollback implemented
- [ ] Transaction description is descriptive
- [ ] Amount is positive
- [ ] Date format is YYYY-MM-DD
- [ ] Constants used for account IDs
- [ ] Integration tested with real data
- [ ] Account balances verified after test
- [ ] Ledger entries verified to balance

## Testing Your Integration

### Test 1: Record a Transaction
```php
// Test script
require_once 'config/config.php';
require_once 'core/Database.php';
require_once 'modules/finance/models/FinanceModel.php';

$financeModel = new FinanceModel();

$result = $financeModel->addSimpleTransaction(
    'Test transaction',
    3,  // FROM: Sales Income
    1,  // TO: Cash Safe
    100.00,
    date('Y-m-d')
);

var_dump($result);  // Should be true
```

### Test 2: Verify Ledger Balance
```sql
-- Check that transaction balances
SELECT 
    t.id,
    t.description,
    SUM(le.amount) as balance
FROM transactions t
JOIN ledger_entries le ON t.id = le.transaction_id
GROUP BY t.id, t.description
HAVING SUM(le.amount) != 0;

-- Should return no rows
```

### Test 3: Verify Account Balances
```sql
-- Check account balances match ledger
SELECT 
    a.id,
    a.name,
    a.balance as cached_balance,
    COALESCE(SUM(le.amount), 0) as calculated_balance,
    a.balance - COALESCE(SUM(le.amount), 0) as difference
FROM accounts a
LEFT JOIN ledger_entries le ON a.id = le.account_id
GROUP BY a.id, a.name, a.balance
HAVING ABS(a.balance - COALESCE(SUM(le.amount), 0)) > 0.01;

-- Should return no rows (all balances match)
```

## Support

For additional help:
- Review `modules/finance/docs/README.md` for detailed documentation
- Review `modules/finance/docs/ROUTING.md` for routing specifics
- Check error logs for specific failure points
- Verify database structure matches schema
- Test with small amounts first

---

**Module Version:** 2.0.0  
**Last Updated:** 2026-05-17  
**Author:** Bob