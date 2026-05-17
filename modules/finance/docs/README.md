# Finance & Ledger Module

## Overview

The Finance & Ledger Module is a comprehensive double-entry accounting system that provides real-time financial tracking, automated journal entries, and complete audit trails. It implements strict accounting principles where every transaction must balance (total debits = total credits) and maintains ACID-compliant data integrity across all financial operations.

## Architecture

### Double-Entry Bookkeeping System

This module implements a **three-tier accounting architecture**:

1. **Chart of Accounts (COA)** - Master account registry with running balances
2. **Transaction Headers** - Groups related journal entries into logical units
3. **Ledger Entries** - Individual debit/credit lines that must balance to zero

All financial operations are wrapped in database transactions to ensure the fundamental accounting equation remains balanced:

```
Assets = Liabilities + Equity
```

And for every transaction:

```
SUM(Debits) = SUM(Credits)
```

### Multi-Module Integration

The Finance module serves as the **central accounting hub** for all monetary operations:

- **Sales Module** → Records revenue (Income) and cash receipts (Asset)
- **Procurement Module** → Records expenses (Expense) and payables (Liability)
- **HR Module** → Records payroll expenses (Expense) and cash disbursements (Asset)
- **Manufacturing Module** → Records cost of goods and inventory valuation

## Database Schema

### Tables Created

#### `accounts`
Master chart of accounts with running balances.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| name | VARCHAR(255) | Account name (e.g., Cash Safe, Sales Income) |
| type | ENUM | Account classification (asset, liability, equity, income, expense) |
| balance | DECIMAL(15,2) | Current account balance (cached for performance) |
| created_at | TIMESTAMP | Account creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

**Account Types:**
- **asset** - Resources owned (Cash, Bank Accounts, Accounts Receivable)
- **liability** - Obligations owed (Accounts Payable, Loans)
- **equity** - Owner's stake (Capital, Retained Earnings)
- **income** - Revenue streams (Sales, Service Income)
- **expense** - Costs incurred (Utilities, Salaries, Rent)

#### `transactions`
Transaction headers that group related ledger entries.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| description | VARCHAR(500) | Transaction description |
| reference_number | VARCHAR(100) | Optional reference (invoice #, receipt #) |
| transaction_date | DATE | Date of the transaction |
| created_at | TIMESTAMP | Transaction creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

#### `ledger_entries`
Individual double-entry ledger lines.

| Column | Type | Description |
|--------|------|-------------|
| id | INT UNSIGNED | Primary key |
| transaction_id | INT UNSIGNED | Foreign key to transactions |
| account_id | INT UNSIGNED | Foreign key to accounts |
| amount | DECIMAL(15,2) | Positive=Debit, Negative=Credit |
| created_at | TIMESTAMP | Entry creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

**Amount Convention:**
- **Positive (+)** = Debit (increases assets/expenses, decreases liabilities/equity/income)
- **Negative (-)** = Credit (decreases assets/expenses, increases liabilities/equity/income)
- **Balance Rule:** For each transaction, `SUM(amount) = 0`

### Foreign Key Relationships

```
ledger_entries.transaction_id → transactions.id (CASCADE DELETE)
ledger_entries.account_id → accounts.id (RESTRICT DELETE)
```

### Indexes for Performance

- `idx_type` - Index on account type for filtered queries
- `idx_name` - Index on account name for search operations
- `idx_transaction_date` - Index on transaction date for period reports
- `idx_reference_number` - Index on reference for lookup
- `idx_transaction_id` - Index on transaction_id for joins
- `idx_account_id` - Index on account_id for account-specific queries

## Installation

### 1. Database Setup

Run the schema file to create the required tables:

```bash
mysql -u your_user -p your_database < modules/finance/database/schema.sql
```

Or import via phpMyAdmin/Adminer.

### 2. Verify Seed Data

The schema includes a basic chart of accounts:

| ID | Account Name | Type | Purpose |
|----|--------------|------|---------|
| 1 | Cash Safe | asset | Physical cash on hand |
| 2 | Bank Account | asset | Bank deposits |
| 3 | General Sales Income | income | Revenue from sales |
| 4 | Utilities Expense | expense | Utility bills |
| 5 | Inventory Purchase Expense | expense | Cost of goods purchased |
| 6 | Accounts Payable | liability | Amounts owed to suppliers |

### 3. Verify Dependencies

Ensure the following core components are operational:

- **Database Class** (`core/Database.php`)
  - Required: PDO singleton with transaction support
  - Required: `beginTransaction()`, `commit()`, `rollback()` methods
  
- **Router Class** (`core/Router.php`)
  - Required: Route registration and dispatching

- **CSRF Protection** (`core/Csrf.php`)
  - Required: Token generation and validation

## Usage

### Recording Financial Transactions

1. Navigate to `/finance` in your browser
2. View real-time financial metrics dashboard
3. Use "Record Money In" for income transactions
4. Use "Record Money Out" for expense transactions
5. View recent transaction history in the audit table

### Dashboard Metrics

The dashboard displays three key financial indicators:

- **Available Money** - Sum of all asset account balances (liquid assets)
- **Monthly Inflow** - Total income for current month
- **Monthly Outflow** - Total expenses for current month

### Transaction Recording Flow

When you record a transaction, the system executes this sequence **atomically**:

```
BEGIN TRANSACTION

1. Insert master record into transactions table
2. Insert debit entry into ledger_entries (negative amount)
3. Insert credit entry into ledger_entries (positive amount)
4. Update source account balance (subtract amount)
5. Update destination account balance (add amount)
6. Verify SUM(ledger_entries.amount) = 0 for this transaction

COMMIT TRANSACTION (or ROLLBACK on any error)
```

## API Reference

### FinanceModel Methods

#### `getAccounts(): array`

Retrieves all accounts categorized by type.

**Returns:**
```php
[
    'asset' => [
        ['id' => 1, 'name' => 'Cash Safe', 'type' => 'asset', 'balance' => 5000.00],
        ['id' => 2, 'name' => 'Bank Account', 'type' => 'asset', 'balance' => 15000.00]
    ],
    'liability' => [
        ['id' => 6, 'name' => 'Accounts Payable', 'type' => 'liability', 'balance' => 2500.00]
    ],
    'equity' => [],
    'income' => [
        ['id' => 3, 'name' => 'General Sales Income', 'type' => 'income', 'balance' => 0.00]
    ],
    'expense' => [
        ['id' => 4, 'name' => 'Utilities Expense', 'type' => 'expense', 'balance' => 0.00],
        ['id' => 5, 'name' => 'Inventory Purchase Expense', 'type' => 'expense', 'balance' => 0.00]
    ]
]
```

#### `getRecentLedger(int $limit = 50): array`

Retrieves recent ledger entries with transaction details.

**Parameters:**
- `$limit` - Maximum number of entries to return (default: 50)

**Returns:** Array of ledger entries with joined transaction and account data

**Example:**
```php
$financeModel = new FinanceModel();
$ledger = $financeModel->getRecentLedger(20);

foreach ($ledger as $entry) {
    echo "{$entry['transaction_description']} - ";
    echo "{$entry['account_name']}: ";
    echo "$" . number_format($entry['amount'], 2);
}
```

#### `getMonthlyMetrics(string $monthYmd): array`

Calculates monthly income and expense totals.

**Parameters:**
- `$monthYmd` - Month in YYYY-MM format (e.g., '2026-05')

**Returns:**
```php
[
    'inflow' => 12500.50,   // Total income for the month
    'outflow' => 8750.25    // Total expenses for the month
]
```

**Example:**
```php
$financeModel = new FinanceModel();
$currentMonth = date('Y-m');
$metrics = $financeModel->getMonthlyMetrics($currentMonth);

echo "Income: $" . number_format($metrics['inflow'], 2);
echo "Expenses: $" . number_format($metrics['outflow'], 2);
echo "Net: $" . number_format($metrics['inflow'] - $metrics['outflow'], 2);
```

#### `addSimpleTransaction(string $description, int $fromAccountId, int $toAccountId, float $amount, string $date): bool`

Records a double-entry transaction with automatic balance updates.

**Parameters:**
- `$description` - Transaction description (max 500 chars)
- `$fromAccountId` - Source account ID (money leaves this account)
- `$toAccountId` - Destination account ID (money enters this account)
- `$amount` - Transaction amount (must be positive)
- `$date` - Transaction date in YYYY-MM-DD format

**Returns:** True on success, false on failure

**Transaction Flow:**
```
FROM Account (Debit -$amount) → TO Account (Credit +$amount)
```

**Example 1: Recording an Expense**
```php
$financeModel = new FinanceModel();

// Pay utility bill from cash safe
$result = $financeModel->addSimpleTransaction(
    'Monthly electricity bill',
    1,  // FROM: Cash Safe (asset)
    4,  // TO: Utilities Expense (expense)
    500.00,
    '2026-05-16'
);

if ($result) {
    echo "Expense recorded successfully";
}
```

**Example 2: Recording Income**
```php
// Record sales revenue deposited to bank
$result = $financeModel->addSimpleTransaction(
    'Product sales for May',
    3,  // FROM: General Sales Income (income)
    2,  // TO: Bank Account (asset)
    2500.00,
    '2026-05-16'
);
```

**Example 3: Transfer Between Accounts**
```php
// Transfer cash from safe to bank
$result = $financeModel->addSimpleTransaction(
    'Cash deposit to bank',
    1,  // FROM: Cash Safe (asset)
    2,  // TO: Bank Account (asset)
    1000.00,
    '2026-05-16'
);
```

## Double-Entry Bookkeeping Logic

### The Fundamental Equation

```
Assets = Liabilities + Equity
```

### Account Balance Rules

| Account Type | Debit (+) | Credit (-) | Normal Balance |
|--------------|-----------|------------|----------------|
| Asset | Increases | Decreases | Debit |
| Liability | Decreases | Increases | Credit |
| Equity | Decreases | Increases | Credit |
| Income | Decreases | Increases | Credit |
| Expense | Increases | Decreases | Debit |

### Transaction Examples

#### Example 1: Cash Sale
```
Transaction: Sold products for $1,000 cash

Debit:  Cash Safe (Asset) +$1,000
Credit: Sales Income (Income) +$1,000

Result: Assets increase, Income increases
```

#### Example 2: Pay Expense
```
Transaction: Paid $500 for utilities

Debit:  Utilities Expense (Expense) +$500
Credit: Cash Safe (Asset) -$500

Result: Expenses increase, Assets decrease
```

#### Example 3: Purchase on Credit
```
Transaction: Purchased inventory for $2,000 on credit

Debit:  Inventory Purchase Expense (Expense) +$2,000
Credit: Accounts Payable (Liability) +$2,000

Result: Expenses increase, Liabilities increase
```

#### Example 4: Pay Liability
```
Transaction: Paid $1,000 to supplier

Debit:  Accounts Payable (Liability) -$1,000
Credit: Cash Safe (Asset) -$1,000

Result: Liabilities decrease, Assets decrease
```

## Integration with Other Modules

### Sales Module Integration

The Sales module automatically records revenue transactions:

```php
// In SalesModel after creating sales order
require_once __DIR__ . '/../../finance/models/FinanceModel.php';
$financeModel = new FinanceModel();

// Record sales revenue
$result = $financeModel->addSimpleTransaction(
    "Sales Invoice: {$invoiceNumber}",
    3,  // FROM: General Sales Income (income)
    1,  // TO: Cash Safe (asset)
    $totalAmount,
    date('Y-m-d')
);

if (!$result) {
    throw new Exception("Failed to record financial transaction");
}
```

### Procurement Module Integration

The Procurement module records purchase expenses:

```php
// In ProcurementModel after receiving purchase order
$financeModel = new FinanceModel();

// Record purchase expense
$result = $financeModel->addSimpleTransaction(
    "Purchase Order: {$poNumber}",
    1,  // FROM: Cash Safe (asset)
    5,  // TO: Inventory Purchase Expense (expense)
    $totalAmount,
    date('Y-m-d')
);
```

### HR Module Integration

The HR module records payroll expenses:

```php
// In HRModel after processing payroll
$financeModel = new FinanceModel();

// Record payroll expense
$result = $financeModel->addSimpleTransaction(
    "Payroll for {$period}",
    1,  // FROM: Cash Safe (asset)
    7,  // TO: Salaries Expense (expense) - add this account
    $totalPayroll,
    date('Y-m-d')
);
```

## Balancing Transaction Equations

The system enforces the double-entry rule: **SUM(Debits) = SUM(Credits)**

### How It Works

1. **Transaction Creation:** Master record inserted into `transactions` table
2. **Debit Entry:** Negative amount inserted into `ledger_entries` (source account)
3. **Credit Entry:** Positive amount inserted into `ledger_entries` (destination account)
4. **Balance Verification:** `SUM(amount) WHERE transaction_id = X` must equal 0

### Example Transaction Breakdown

```sql
-- Transaction: Pay $500 utility bill
INSERT INTO transactions (description, transaction_date)
VALUES ('Monthly electricity bill', '2026-05-16');
-- transaction_id = 1

-- Debit entry (money leaving Cash Safe)
INSERT INTO ledger_entries (transaction_id, account_id, amount)
VALUES (1, 1, -500.00);  -- Cash Safe (asset) decreases

-- Credit entry (money entering Utilities Expense)
INSERT INTO ledger_entries (transaction_id, account_id, amount)
VALUES (1, 4, 500.00);   -- Utilities Expense (expense) increases

-- Verification: SUM(amount) = -500.00 + 500.00 = 0 ✓
```

### Account Balance Updates

After ledger entries are created, account balances are updated:

```sql
-- Update source account (Cash Safe)
UPDATE accounts 
SET balance = balance - 500.00 
WHERE id = 1;

-- Update destination account (Utilities Expense)
UPDATE accounts 
SET balance = balance + 500.00 
WHERE id = 4;
```

## Chart of Accounts (COA) Management

### Default COA Structure

The system includes a basic COA that can be extended:

```
Assets (1xxx)
├── 1 - Cash Safe
└── 2 - Bank Account

Liabilities (2xxx)
└── 6 - Accounts Payable

Equity (3xxx)
└── (Add as needed)

Income (4xxx)
└── 3 - General Sales Income

Expenses (5xxx)
├── 4 - Utilities Expense
└── 5 - Inventory Purchase Expense
```

### Adding New Accounts

To add accounts, insert directly into the `accounts` table:

```sql
-- Add a new expense account
INSERT INTO accounts (name, type, balance)
VALUES ('Salaries Expense', 'expense', 0.00);

-- Add a new asset account
INSERT INTO accounts (name, type, balance)
VALUES ('Accounts Receivable', 'asset', 0.00);

-- Add an equity account
INSERT INTO accounts (name, type, balance)
VALUES ('Owner Capital', 'equity', 0.00);
```

### Account Numbering Convention (Optional)

For larger organizations, consider implementing account numbering:

- **1000-1999:** Assets
- **2000-2999:** Liabilities
- **3000-3999:** Equity
- **4000-4999:** Income/Revenue
- **5000-5999:** Expenses

## Security Features

### CSRF Protection
All forms include CSRF tokens validated on submission.

### Input Validation
- Description: 1-500 characters, XSS-sanitized
- Account IDs: Integer validation, existence verification
- Amount: Positive floats with 2 decimal precision (min: 0.01)
- Date: Valid date format (YYYY-MM-DD), not future dates

### Transaction Integrity
- All operations wrapped in database transactions
- Automatic rollback on any error
- Balance verification after updates
- Foreign key constraints prevent orphaned records

### SQL Injection Prevention
All queries use PDO prepared statements with parameter binding.

### XSS Prevention
All output uses `htmlspecialchars()` with `ENT_QUOTES` and `UTF-8` encoding.

## File Structure

```
modules/finance/
├── docs/
│   ├── README.md                       # This file
│   ├── ROUTING.md                      # Route configuration guide
│   └── INTEGRATION_GUIDE.md            # Integration instructions
├── index.php                           # Main dashboard view
├── database/
│   └── schema.sql                      # Database schema with seed data
├── models/
│   ├── FinanceModel.php                # Core accounting logic
│   └── ReportModel.php                 # Financial reporting (future)
└── controllers/
    ├── RecordIncomeController.php      # Income transaction handler
    └── RecordExpenseController.php     # Expense transaction handler
```

## Performance Considerations

### Cached Account Balances

The `accounts.balance` field is a cached value for performance:
- Updated with every transaction
- Eliminates need to SUM ledger entries for balance queries
- Can be recalculated from ledger if needed:

```sql
-- Recalculate account balance from ledger
UPDATE accounts a
SET balance = (
    SELECT COALESCE(SUM(amount), 0)
    FROM ledger_entries
    WHERE account_id = a.id
)
WHERE id = :account_id;
```

### Query Optimization

- Uses indexed columns for filtering (type, transaction_date)
- Limits result sets with configurable pagination
- Efficient joins between transactions and ledger entries
- `COALESCE()` for null-safe aggregations

### Transaction Isolation

Database transactions use default isolation level (READ COMMITTED) which provides:
- Protection against dirty reads
- Consistent reads within transaction
- Minimal lock contention

## Troubleshooting

### "Account not found" Error
**Cause:** Referenced account ID doesn't exist  
**Solution:** Verify account exists in `accounts` table, check seed data

### "Transaction failed to balance" Error
**Cause:** Debit and credit amounts don't match  
**Solution:** This shouldn't occur with `addSimpleTransaction()`. If it does, check for database corruption.

### Negative Asset Balance
**Cause:** Spending more than available in account  
**Solution:** Implement balance checking before transactions:
```php
$account = $db->query("SELECT balance FROM accounts WHERE id = :id", ['id' => $accountId])->fetch();
if ($account['balance'] < $amount) {
    throw new Exception("Insufficient funds");
}
```

### Transaction Rollback
**Cause:** Any validation or database error  
**Solution:** Check error logs for specific failure point, verify all account IDs exist

### Incorrect Monthly Metrics
**Cause:** Transaction dates not in expected format  
**Solution:** Ensure dates are stored as YYYY-MM-DD format

## Future Enhancements

Potential features for future versions:

- [ ] Multi-currency support with exchange rates
- [ ] Budget tracking and variance analysis
- [ ] Financial statement generation (Balance Sheet, P&L, Cash Flow)
- [ ] Account reconciliation tools
- [ ] Recurring transaction templates
- [ ] Tax calculation and reporting
- [ ] Cost center / department tracking
- [ ] Approval workflows for large transactions
- [ ] Audit log with user tracking
- [ ] Bank feed integration
- [ ] Automated bank reconciliation
- [ ] Financial forecasting and projections
- [ ] Custom report builder
- [ ] Export to accounting software (QuickBooks, Xero)

## License

Part of AmenERP - Made with Bob

## Support

For issues or questions, refer to the main project documentation or contact the development team.