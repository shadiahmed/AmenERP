# Finance Module - Routing Configuration

## Route Registration Order (CRITICAL)

The Finance module routes **MUST** be registered in this specific order in `public/index.php` to prevent parameter collision and routing conflicts:

```php
// ============================================================================
// FINANCE MODULE ROUTES
// ============================================================================

// 1. Static GET path - Main finance dashboard
$router->get('/finance', 'finance/index.php');

// 2. Static POST paths - Transaction recording (MUST come before dynamic routes)
$router->post('/finance/record-income', 'finance/controllers/RecordIncomeController.php');
$router->post('/finance/record-expense', 'finance/controllers/RecordExpenseController.php');

// 3. Dynamic GET paths - View specific transaction (future enhancement)
// $router->get('/finance/transactions/{id}', 'finance/controllers/ViewTransactionController.php');

// 4. Report endpoints (future enhancement)
// $router->get('/finance/reports/balance-sheet', 'finance/controllers/BalanceSheetController.php');
// $router->get('/finance/reports/income-statement', 'finance/controllers/IncomeStatementController.php');
```

## Why This Order Matters

### Problem: Route Priority Conflicts

If dynamic routes are registered before static routes, the router may incorrectly match:
- `/finance/record-income` → Interpreted as `/finance/{action}` with `action = "record-income"`
- `/finance/reports/balance-sheet` → Interpreted as `/finance/{type}/{id}` with incorrect parameters
- This causes the wrong controller to execute

### Solution: Static Before Dynamic

By registering static literal paths FIRST, the router checks exact matches before attempting parameter extraction.

**Route Priority Tree:**
```
1. Exact static matches (/finance/record-income)
2. Static nested paths (/finance/reports/balance-sheet)
3. Dynamic parameter matches (/finance/transactions/{id})
```

## Route Details

### 1. GET /finance
**Purpose:** Display the main finance dashboard  
**File:** `modules/finance/index.php`  
**Access:** Public (requires session)  
**Returns:** HTML view with financial metrics, account chart, and transaction history

**Features:**
- Real-time financial metrics (Available Money, Monthly Inflow/Outflow)
- Chart of Accounts summary by type
- Recent transaction audit history
- Quick action buttons for recording income/expenses
- Modal forms for transaction entry

### 2. POST /finance/record-income
**Purpose:** Process income transaction recording  
**File:** `modules/finance/controllers/RecordIncomeController.php`  
**Access:** POST only, CSRF protected  
**Parameters:** 
- `csrf_token` (hidden field)
- `amount` (float, min 0.01)
- `from_account_id` (int) - Income source account
- `to_account_id` (int) - Asset account receiving money
- `description` (string, max 500 chars)
- `date` (date, YYYY-MM-DD format)

**Returns:** Redirect to `/finance` with success/error message

**Form Configuration:**
```php
<form method="POST" action="<?php echo BASE_URL; ?>/finance/record-income">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    
    <input type="number" name="amount" step="0.01" min="0.01" required>
    
    <select name="from_account_id" required>
        <!-- Income accounts (type='income') -->
        <option value="3">General Sales Income</option>
    </select>
    
    <select name="to_account_id" required>
        <!-- Asset accounts (type='asset') -->
        <option value="1">Cash Safe</option>
        <option value="2">Bank Account</option>
    </select>
    
    <input type="text" name="description" maxlength="500" required>
    <input type="date" name="date" required>
    
    <button type="submit">Record Income</button>
</form>
```

**Transaction Flow:**
```
Income Account (FROM) → Asset Account (TO)
Example: General Sales Income → Cash Safe
Result: Income increases, Assets increase
```

### 3. POST /finance/record-expense
**Purpose:** Process expense transaction recording  
**File:** `modules/finance/controllers/RecordExpenseController.php`  
**Access:** POST only, CSRF protected  
**Parameters:** 
- `csrf_token` (hidden field)
- `amount` (float, min 0.01)
- `from_account_id` (int) - Asset account paying money
- `to_account_id` (int) - Expense category account
- `description` (string, max 500 chars)
- `date` (date, YYYY-MM-DD format)

**Returns:** Redirect to `/finance` with success/error message

**Form Configuration:**
```php
<form method="POST" action="<?php echo BASE_URL; ?>/finance/record-expense">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    
    <input type="number" name="amount" step="0.01" min="0.01" required>
    
    <select name="from_account_id" required>
        <!-- Asset accounts (type='asset') -->
        <option value="1">Cash Safe (Balance: $5,000.00)</option>
        <option value="2">Bank Account (Balance: $15,000.00)</option>
    </select>
    
    <select name="to_account_id" required>
        <!-- Expense accounts (type='expense') -->
        <option value="4">Utilities Expense</option>
        <option value="5">Inventory Purchase Expense</option>
    </select>
    
    <input type="text" name="description" maxlength="500" required>
    <input type="date" name="date" required>
    
    <button type="submit">Record Expense</button>
</form>
```

**Transaction Flow:**
```
Asset Account (FROM) → Expense Account (TO)
Example: Cash Safe → Utilities Expense
Result: Assets decrease, Expenses increase
```

## Form Configuration

### Income Recording Form

The income form in `modules/finance/index.php` uses the correct action path:

```php
<form id="incomeForm" method="POST" action="<?php echo BASE_URL; ?>/finance/record-income">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    
    <!-- Amount Field -->
    <div class="form-group">
        <label for="income_amount">Amount <span class="required">*</span></label>
        <input type="number" 
               id="income_amount" 
               name="amount" 
               step="0.01" 
               min="0.01" 
               required 
               placeholder="0.00">
    </div>
    
    <!-- Deposited To (Asset Account) -->
    <div class="form-group">
        <label for="deposited_to">Deposited To <span class="required">*</span></label>
        <select id="deposited_to" name="to_account_id" required>
            <option value="">Select account...</option>
            <?php foreach ($accounts['asset'] as $asset): ?>
                <option value="<?php echo $asset['id']; ?>">
                    <?php echo htmlspecialchars($asset['name'], ENT_QUOTES, 'UTF-8'); ?> 
                    (Balance: $<?php echo number_format($asset['balance'], 2); ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <!-- Income Source -->
    <div class="form-group">
        <label for="income_source">Income Source <span class="required">*</span></label>
        <select id="income_source" name="from_account_id" required>
            <option value="">Select source...</option>
            <?php foreach ($accounts['income'] as $income): ?>
                <option value="<?php echo $income['id']; ?>">
                    <?php echo htmlspecialchars($income['name'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <!-- Description -->
    <div class="form-group">
        <label for="income_description">Description <span class="required">*</span></label>
        <input type="text" 
               id="income_description" 
               name="description" 
               required 
               maxlength="500"
               placeholder="e.g., Product sales for May">
    </div>
    
    <!-- Date -->
    <div class="form-group">
        <label for="income_date">Date <span class="required">*</span></label>
        <input type="date" 
               id="income_date" 
               name="date" 
               required 
               value="<?php echo date('Y-m-d'); ?>"
               max="<?php echo date('Y-m-d'); ?>">
    </div>
    
    <button type="submit">Record Income</button>
</form>
```

### Expense Recording Form

The expense form follows the same pattern:

```php
<form id="expenseForm" method="POST" action="<?php echo BASE_URL; ?>/finance/record-expense">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    
    <!-- Amount Field -->
    <div class="form-group">
        <label for="expense_amount">Amount <span class="required">*</span></label>
        <input type="number" 
               id="expense_amount" 
               name="amount" 
               step="0.01" 
               min="0.01" 
               required 
               placeholder="0.00">
    </div>
    
    <!-- Paid From (Asset Account) -->
    <div class="form-group">
        <label for="paid_from">Paid From <span class="required">*</span></label>
        <select id="paid_from" name="from_account_id" required>
            <option value="">Select account...</option>
            <?php foreach ($accounts['asset'] as $asset): ?>
                <option value="<?php echo $asset['id']; ?>">
                    <?php echo htmlspecialchars($asset['name'], ENT_QUOTES, 'UTF-8'); ?> 
                    (Balance: $<?php echo number_format($asset['balance'], 2); ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <!-- Expense Category -->
    <div class="form-group">
        <label for="expense_category">Expense Category <span class="required">*</span></label>
        <select id="expense_category" name="to_account_id" required>
            <option value="">Select category...</option>
            <?php foreach ($accounts['expense'] as $expense): ?>
                <option value="<?php echo $expense['id']; ?>">
                    <?php echo htmlspecialchars($expense['name'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <!-- Description -->
    <div class="form-group">
        <label for="expense_description">Description <span class="required">*</span></label>
        <input type="text" 
               id="expense_description" 
               name="description" 
               required 
               maxlength="500"
               placeholder="e.g., Monthly electricity bill">
    </div>
    
    <!-- Date -->
    <div class="form-group">
        <label for="expense_date">Date <span class="required">*</span></label>
        <input type="date" 
               id="expense_date" 
               name="date" 
               required 
               value="<?php echo date('Y-m-d'); ?>"
               max="<?php echo date('Y-m-d'); ?>">
    </div>
    
    <button type="submit">Record Expense</button>
</form>
```

### Key Points:
1. **Uses BASE_URL constant** - Ensures correct path regardless of installation directory
2. **Action points to specific endpoints** - `/finance/record-income` or `/finance/record-expense`
3. **CSRF token included** - Security requirement for all POST requests
4. **Token name is `csrf_token`** - Matches controller expectation
5. **Date validation** - Max date is today (prevents future-dated transactions)
6. **Amount validation** - Minimum 0.01, step 0.01 for decimal precision

## Controller Implementation Patterns

### RecordIncomeController.php

The controller reads data directly from `$_POST`:

```php
// ✅ CORRECT - Reading from $_POST array
$amount = (float)($_POST['amount'] ?? 0);
$fromAccountId = (int)($_POST['from_account_id'] ?? 0);  // Income account
$toAccountId = (int)($_POST['to_account_id'] ?? 0);      // Asset account
$description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');
$date = $_POST['date'] ?? date('Y-m-d');

// Validate inputs
if ($amount <= 0) {
    $_SESSION['error'] = 'Amount must be greater than zero';
    header('Location: ' . BASE_URL . '/finance');
    exit;
}

if ($fromAccountId <= 0 || $toAccountId <= 0) {
    $_SESSION['error'] = 'Invalid account selection';
    header('Location: ' . BASE_URL . '/finance');
    exit;
}

// Record transaction
$financeModel = new FinanceModel();
$result = $financeModel->addSimpleTransaction(
    $description,
    $fromAccountId,  // FROM: Income account
    $toAccountId,    // TO: Asset account
    $amount,
    $date
);

// ❌ INCORRECT - Don't use $params for POST data
// $amount = $params['amount']; // WRONG!
```

### RecordExpenseController.php

The controller follows the same pattern:

```php
// ✅ CORRECT - Reading from $_POST array
$amount = (float)($_POST['amount'] ?? 0);
$fromAccountId = (int)($_POST['from_account_id'] ?? 0);  // Asset account
$toAccountId = (int)($_POST['to_account_id'] ?? 0);      // Expense account
$description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');
$date = $_POST['date'] ?? date('Y-m-d');

// Validate inputs
if ($amount <= 0) {
    $_SESSION['error'] = 'Amount must be greater than zero';
    header('Location: ' . BASE_URL . '/finance');
    exit;
}

// Optional: Check sufficient balance
$db = Database::getInstance();
$sql = "SELECT balance FROM accounts WHERE id = :id";
$stmt = $db->query($sql, ['id' => $fromAccountId]);
$account = $stmt->fetch();

if ($account && $account['balance'] < $amount) {
    $_SESSION['error'] = 'Insufficient funds in selected account';
    header('Location: ' . BASE_URL . '/finance');
    exit;
}

// Record transaction
$financeModel = new FinanceModel();
$result = $financeModel->addSimpleTransaction(
    $description,
    $fromAccountId,  // FROM: Asset account
    $toAccountId,    // TO: Expense account
    $amount,
    $date
);
```

## Testing the Routes

### Test 1: Main Dashboard
```bash
# Navigate to finance dashboard
curl http://localhost/finance
# Expected: HTML dashboard with metrics and forms
```

### Test 2: Record Income
```bash
# Submit income transaction (with CSRF token)
curl -X POST http://localhost/finance/record-income \
  -d "csrf_token=TOKEN" \
  -d "amount=1500.00" \
  -d "from_account_id=3" \
  -d "to_account_id=1" \
  -d "description=Product sales for May" \
  -d "date=2026-05-16"
# Expected: Redirect to /finance with success message
```

### Test 3: Record Expense
```bash
# Submit expense transaction (with CSRF token)
curl -X POST http://localhost/finance/record-expense \
  -d "csrf_token=TOKEN" \
  -d "amount=500.00" \
  -d "from_account_id=1" \
  -d "to_account_id=4" \
  -d "description=Monthly electricity bill" \
  -d "date=2026-05-16"
# Expected: Redirect to /finance with success message
```

### Test 4: Verify Database Changes
```sql
-- Check transactions table
SELECT * FROM transactions ORDER BY id DESC LIMIT 5;

-- Check ledger entries (should balance to 0)
SELECT 
    t.description,
    le.amount,
    a.name as account_name
FROM ledger_entries le
JOIN transactions t ON le.transaction_id = t.id
JOIN accounts a ON le.account_id = a.id
ORDER BY t.id DESC, le.id ASC
LIMIT 10;

-- Verify transaction balances
SELECT 
    transaction_id,
    SUM(amount) as balance
FROM ledger_entries
GROUP BY transaction_id
HAVING SUM(amount) != 0;
-- Should return no rows (all transactions balanced)

-- Check account balances
SELECT name, type, balance FROM accounts ORDER BY type, name;
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

### Error 4: "Invalid account selection"
**Cause:** Account ID doesn't exist or wrong account type selected  
**Solution:** Verify account IDs exist in database, check account types match transaction type

### Error 5: "Amount must be greater than zero"
**Cause:** Invalid or missing amount value  
**Solution:** Ensure form includes amount field with proper validation (min="0.01")

### Error 6: "Transaction failed to balance"
**Cause:** Database corruption or logic error in FinanceModel  
**Solution:** Check `addSimpleTransaction()` method, verify ledger entries sum to zero

## Integration with Router.php

The Finance module relies on the Router class methods:

```php
// Register GET route
$router->get($path, $file);

// Register POST route
$router->post($path, $file);

// Router does NOT extract parameters for Finance routes (all static paths)
// No dynamic segments like {id} in current implementation
```

## Security Considerations

1. **CSRF Protection:** All POST routes require valid CSRF token
2. **Input Validation:** Controllers validate all input before processing
3. **SQL Injection Prevention:** All queries use PDO prepared statements
4. **XSS Prevention:** All output uses `htmlspecialchars()`
5. **Session Management:** Routes check for active session
6. **Amount Validation:** Prevents negative or zero amounts
7. **Date Validation:** Prevents future-dated transactions
8. **Account Verification:** Ensures accounts exist before transaction
9. **Balance Checking:** Optional verification of sufficient funds
10. **Transaction Integrity:** ACID-compliant database transactions

## Future Route Additions

When adding new routes, follow this pattern:

```php
// Static routes first
$router->get('/finance/reports', 'finance/controllers/ReportsController.php');
$router->get('/finance/reports/balance-sheet', 'finance/controllers/BalanceSheetController.php');
$router->get('/finance/reports/income-statement', 'finance/controllers/IncomeStatementController.php');
$router->get('/finance/reports/cash-flow', 'finance/controllers/CashFlowController.php');

// POST routes for additional operations
$router->post('/finance/accounts/create', 'finance/controllers/CreateAccountController.php');
$router->post('/finance/transactions/void', 'finance/controllers/VoidTransactionController.php');

// API endpoints
$router->get('/api/finance/accounts', 'finance/api/accounts.php');
$router->get('/api/finance/balance', 'finance/api/balance.php');

// Dynamic routes last
$router->get('/finance/transactions/{id}', 'finance/controllers/ViewTransactionController.php');
$router->get('/finance/accounts/{id}', 'finance/controllers/ViewAccountController.php');
```

## Modal Management

The Finance module uses JavaScript to manage modal forms:

```javascript
// Open Income Modal
document.getElementById('openIncomeModalBtn').addEventListener('click', () => {
    document.getElementById('incomeModal').classList.add('active');
});

// Close Income Modal
function closeIncomeModal() {
    document.getElementById('incomeModal').classList.remove('active');
    document.getElementById('incomeForm').reset();
}

// Open Expense Modal
document.getElementById('openExpenseModalBtn').addEventListener('click', () => {
    document.getElementById('expenseModal').classList.add('active');
});

// Close Expense Modal
function closeExpenseModal() {
    document.getElementById('expenseModal').classList.remove('active');
    document.getElementById('expenseForm').reset();
}

// Close on outside click
document.getElementById('incomeModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('incomeModal')) {
        closeIncomeModal();
    }
});

// Close on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (document.getElementById('incomeModal').classList.contains('active')) {
            closeIncomeModal();
        }
        if (document.getElementById('expenseModal').classList.contains('active')) {
            closeExpenseModal();
        }
    }
});
```

## Troubleshooting Checklist

- [ ] Routes registered in correct order (static before dynamic)
- [ ] Form action uses `BASE_URL` constant
- [ ] Form method matches route method (GET/POST)
- [ ] CSRF token included in POST forms
- [ ] Controller reads from `$_POST` (not `$params`)
- [ ] Route paths match exactly (case-sensitive)
- [ ] Controller file exists at specified path
- [ ] Account IDs exist in database
- [ ] Amount validation implemented
- [ ] Date format is YYYY-MM-DD
- [ ] Transaction balances verified (SUM = 0)
- [ ] Account balances updated correctly

## Related Files

- `public/index.php` - Route registration
- `core/Router.php` - Router implementation
- `modules/finance/index.php` - Main view with forms
- `modules/finance/controllers/RecordIncomeController.php` - Income handler
- `modules/finance/controllers/RecordExpenseController.php` - Expense handler
- `modules/finance/models/FinanceModel.php` - Core accounting logic
- `core/Csrf.php` - CSRF token generation/validation

---

**Last Updated:** 2026-05-17  
**Module Version:** 2.0.0  
**Author:** Bob