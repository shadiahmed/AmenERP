<?php

declare(strict_types=1);

/**
 * Finance Module - Main Dashboard
 * 
 * Displays financial overview with:
 * - Summary metrics (Available Money, Monthly Inflow/Outflow)
 * - Quick action buttons for recording transactions
 * - Audit history of recent transactions
 * 
 * @package AmenERP\Modules\Finance
 * @author Bob
 * @version 1.0.0
 */

// Initialize the Finance Model
require_once __DIR__ . '/models/FinanceModel.php';
$financeModel = new FinanceModel();

// Fetch all accounts categorized by type
$accounts = $financeModel->getAccounts();

// Calculate Available Money (sum of all asset accounts)
$availableMoney = 0;
foreach ($accounts['asset'] as $asset) {
    $availableMoney += $asset['balance'];
}

// Calculate Monthly Inflow and Outflow using real-time data
$currentMonth = date('Y-m');
$monthlyMetrics = $financeModel->getMonthlyMetrics($currentMonth);
$monthlyInflow = $monthlyMetrics['inflow'];
$monthlyOutflow = $monthlyMetrics['outflow'];

// Fetch recent ledger entries for audit history
$recentLedger = $financeModel->getRecentLedger(20);

// Group ledger entries by transaction for cleaner display
$transactions = [];
foreach ($recentLedger as $entry) {
    $txId = $entry['transaction_id'];
    if (!isset($transactions[$txId])) {
        $transactions[$txId] = [
            'id' => $txId,
            'description' => $entry['transaction_description'],
            'date' => $entry['transaction_date'],
            'reference' => $entry['reference_number'],
            'entries' => []
        ];
    }
    $transactions[$txId]['entries'][] = $entry;
}

// Generate CSRF token for forms
$csrfToken = Csrf::generateToken();

?>

<!-- Content Header -->
<div class="content-header">
    <h2>Finance Dashboard</h2>
    <div class="breadcrumb">
        <span>Finance</span>
    </div>
</div>

<!-- Summary Matrix -->
<div class="grid grid-cols-3">
    <div class="card">
        <div class="card-body">
            <h3 class="card-stat-label">💰 Available Money</h3>
            <p class="card-stat-value text-primary">
                $<?php echo number_format($availableMoney, 2); ?>
            </p>
            <p class="card-stat-description">
                Total liquid assets (Cash + Bank)
            </p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <h3 class="card-stat-label">📈 Total Inflow This Month</h3>
            <p class="card-stat-value text-success">
                $<?php echo number_format($monthlyInflow, 2); ?>
            </p>
            <p class="card-stat-description">
                Income received in <?php echo date('F Y'); ?>
            </p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <h3 class="card-stat-label">📉 Total Outflow This Month</h3>
            <p class="card-stat-value text-error">
                $<?php echo number_format($monthlyOutflow, 2); ?>
            </p>
            <p class="card-stat-description">
                Expenses paid in <?php echo date('F Y'); ?>
            </p>
        </div>
    </div>
</div>

<!-- Friendly Actions Area -->
<div class="card">
    <div class="card-header">
        <h3>Quick Actions</h3>
    </div>
    <div class="card-body">
        <div class="finance-actions">
            <button type="button" class="btn btn-danger" data-action="open-expense-modal">
                <span>💸</span>
                <span>Record Money Out (Expense)</span>
            </button>
            <button type="button" class="btn btn-success" data-action="open-income-modal">
                <span>💵</span>
                <span>Record Money In (Income)</span>
            </button>
        </div>
    </div>
</div>

<!-- Audit History -->
<div class="card">
    <div class="card-header">
        <h3>Recent Activity</h3>
    </div>
    <div class="card-body">
        <?php if (empty($transactions)): ?>
            <p class="text-secondary text-center p-xl">
                No transactions recorded yet. Use the buttons above to record your first transaction.
            </p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Activity</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <?php
                            // Parse transaction entries to create friendly description
                            $fromAccount = '';
                            $toAccount = '';
                            $amount = 0;
                            
                            foreach ($transaction['entries'] as $entry) {
                                if ($entry['amount'] < 0) {
                                    $fromAccount = $entry['account_name'];
                                    $amount = abs($entry['amount']);
                                } else {
                                    $toAccount = $entry['account_name'];
                                }
                            }
                            
                            // Determine if it's income or expense based on account types
                            $isIncome = false;
                            $isExpense = false;
                            foreach ($transaction['entries'] as $entry) {
                                if ($entry['account_type'] === 'income' && $entry['amount'] > 0) {
                                    $isIncome = true;
                                }
                                if ($entry['account_type'] === 'expense' && $entry['amount'] > 0) {
                                    $isExpense = true;
                                }
                            }
                            
                            // Create friendly activity description
                            if ($isExpense) {
                                $activity = "Paid {$toAccount} from {$fromAccount}";
                                $amountClass = 'text-error';
                                $amountPrefix = '-';
                            } elseif ($isIncome) {
                                $activity = "Received {$fromAccount} to {$toAccount}";
                                $amountClass = 'text-success';
                                $amountPrefix = '+';
                            } else {
                                $activity = "Transferred from {$fromAccount} to {$toAccount}";
                                $amountClass = 'text-secondary';
                                $amountPrefix = '';
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($transaction['date']))); ?></td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                <td><?php echo htmlspecialchars($activity); ?></td>
                                <td class="<?php echo $amountClass; ?> finance-amount">
                                    <?php echo $amountPrefix; ?>$<?php echo number_format($amount, 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Money Out Modal (Expense) -->
<div id="expenseModal" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3>💸 Record Money Out (Expense)</h3>
            <button type="button" class="btn-close" data-action="close-expense-modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="expenseForm" method="POST" action="<?php echo BASE_URL; ?>/finance/record-expense">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="expense_amount">Amount *</label>
                    <input 
                        type="number" 
                        id="expense_amount" 
                        name="amount" 
                        step="0.01" 
                        min="0.01" 
                        required 
                        placeholder="0.00"
                        class="form-input"
                    >
                </div>
                
                <div class="form-group">
                    <label for="paid_from">Paid From *</label>
                    <select id="paid_from" name="from_account_id" required class="form-select">
                        <option value="">Select account...</option>
                        <?php foreach ($accounts['asset'] as $asset): ?>
                            <option value="<?php echo $asset['id']; ?>">
                                <?php echo htmlspecialchars($asset['name']); ?> 
                                (Balance: $<?php echo number_format($asset['balance'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="expense_category">Expense Category *</label>
                    <select id="expense_category" name="to_account_id" required class="form-select">
                        <option value="">Select category...</option>
                        <?php foreach ($accounts['expense'] as $expense): ?>
                            <option value="<?php echo $expense['id']; ?>">
                                <?php echo htmlspecialchars($expense['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="expense_description">Description *</label>
                    <input 
                        type="text" 
                        id="expense_description" 
                        name="description" 
                        required 
                        placeholder="e.g., Monthly electricity bill"
                        maxlength="500"
                        class="form-input"
                    >
                </div>
                
                <div class="form-group">
                    <label for="expense_date">Date *</label>
                    <input 
                        type="date" 
                        id="expense_date" 
                        name="date" 
                        required 
                        value="<?php echo date('Y-m-d'); ?>"
                        max="<?php echo date('Y-m-d'); ?>"
                        class="form-input"
                    >
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" data-action="close-expense-modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Record Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Money In Modal (Income) -->
<div id="incomeModal" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3>💵 Record Money In (Income)</h3>
            <button type="button" class="btn-close" data-action="close-income-modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="incomeForm" method="POST" action="<?php echo BASE_URL; ?>/finance/record-income">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="income_amount">Amount *</label>
                    <input 
                        type="number" 
                        id="income_amount" 
                        name="amount" 
                        step="0.01" 
                        min="0.01" 
                        required 
                        placeholder="0.00"
                        class="form-input"
                    >
                </div>
                
                <div class="form-group">
                    <label for="deposited_to">Deposited To *</label>
                    <select id="deposited_to" name="to_account_id" required class="form-select">
                        <option value="">Select account...</option>
                        <?php foreach ($accounts['asset'] as $asset): ?>
                            <option value="<?php echo $asset['id']; ?>">
                                <?php echo htmlspecialchars($asset['name']); ?> 
                                (Balance: $<?php echo number_format($asset['balance'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="income_source">Income Source *</label>
                    <select id="income_source" name="from_account_id" required class="form-select">
                        <option value="">Select source...</option>
                        <?php foreach ($accounts['income'] as $income): ?>
                            <option value="<?php echo $income['id']; ?>">
                                <?php echo htmlspecialchars($income['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="income_description">Description *</label>
                    <input 
                        type="text" 
                        id="income_description" 
                        name="description" 
                        required 
                        placeholder="e.g., Product sales for May"
                        maxlength="500"
                        class="form-input"
                    >
                </div>
                
                <div class="form-group">
                    <label for="income_date">Date *</label>
                    <input 
                        type="date" 
                        id="income_date" 
                        name="date" 
                        required 
                        value="<?php echo date('Y-m-d'); ?>"
                        max="<?php echo date('Y-m-d'); ?>"
                        class="form-input"
                    >
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" data-action="close-income-modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Record Income</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Made with Bob -->

// Made with Bob