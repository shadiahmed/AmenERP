<?php

declare(strict_types=1);

/**
 * Finance Module - Central Ledger Console
 * 
 * Comprehensive financial management interface with double-entry accounting,
 * real-time metrics, and transaction recording capabilities.
 *
 * Features:
 * - Real-time financial metrics dashboard
 * - Double-entry accounting ledger
 * - Income and expense recording with modal forms
 * - Account balance tracking (Assets, Liabilities, Equity)
 * - Recent transaction audit history
 * - CSRF protection
 * - Responsive mobile-first design
 * 
 * @package AmenERP\Modules\Finance
 * @author Bob
 * @version 2.0.0
 */

// ============================================================================
// BOOTSTRAP: Core Configuration & Dependencies
// ============================================================================

/**
 * Initialize the Finance Model
 */
require_once __DIR__ . '/models/FinanceModel.php';
$financeModel = new FinanceModel();

/**
 * Fetch all accounts categorized by type
 */
$accounts = $financeModel->getAccounts();

/**
 * Calculate Available Money (sum of all asset accounts)
 */
$availableMoney = 0;
foreach ($accounts['asset'] as $asset) {
    $availableMoney += $asset['balance'];
}

/**
 * Calculate Monthly Inflow and Outflow using real-time data
 */
$currentMonth = date('Y-m');
$monthlyMetrics = $financeModel->getMonthlyMetrics($currentMonth);
$monthlyInflow = $monthlyMetrics['inflow'];
$monthlyOutflow = $monthlyMetrics['outflow'];

/**
 * Fetch recent ledger entries for audit history
 */
$recentLedger = $financeModel->getRecentLedger(20);

/**
 * Group ledger entries by transaction for cleaner display
 */
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

/**
 * Generate CSRF token for forms
 */
$csrfToken = Csrf::generateToken();

?>

<div class="erp-container">
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

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Finance & Ledger Console</h1>
            <p class="page-subtitle">Double-entry accounting and financial management</p>
        </div>
    </div>

    <!-- Financial Metrics Dashboard -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">💰</div>
            <div class="metric-content">
                <div class="metric-label">Available Money</div>
                <div class="metric-value"><?php echo '$' . number_format($availableMoney, 2); ?></div>
                <div class="metric-subtitle">Total Liquid Assets</div>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon">📈</div>
            <div class="metric-content">
                <div class="metric-label">Monthly Inflow</div>
                <div class="metric-value" style="color: var(--success);"><?php echo '$' . number_format($monthlyInflow, 2); ?></div>
                <div class="metric-subtitle"><?php echo date('F Y'); ?></div>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon">📉</div>
            <div class="metric-content">
                <div class="metric-label">Monthly Outflow</div>
                <div class="metric-value" style="color: var(--danger);"><?php echo '$' . number_format($monthlyOutflow, 2); ?></div>
                <div class="metric-subtitle"><?php echo date('F Y'); ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Quick Actions</h2>
        </div>
        <div class="card-body">
            <div class="btn-group-horizontal">
                <button type="button" class="btn btn-danger" id="openExpenseModalBtn">
                    <span class="btn-icon">💸</span>
                    Record Money Out (Expense)
                </button>
                <button type="button" class="btn btn-success" id="openIncomeModalBtn">
                    <span class="btn-icon">💵</span>
                    Record Money In (Income)
                </button>
            </div>
        </div>
    </section>

    <!-- Account Chart Tracking Ledger -->
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Account Chart Summary</h2>
        </div>
        <div class="card-body">
            <div class="account-chart-grid">
                <!-- Assets Column -->
                <div class="account-column">
                    <h3 class="account-column-title">Assets</h3>
                    <div class="account-list">
                        <?php foreach ($accounts['asset'] as $asset): ?>
                            <div class="account-item">
                                <div class="account-name"><?php echo htmlspecialchars($asset['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="account-balance"><?php echo '$' . number_format($asset['balance'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Liabilities Column -->
                <div class="account-column">
                    <h3 class="account-column-title">Liabilities</h3>
                    <div class="account-list">
                        <?php if (empty($accounts['liability'])): ?>
                            <div class="account-item-empty">No liabilities</div>
                        <?php else: ?>
                            <?php foreach ($accounts['liability'] as $liability): ?>
                                <div class="account-item">
                                    <div class="account-name"><?php echo htmlspecialchars($liability['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="account-balance"><?php echo '$' . number_format($liability['balance'], 2); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Equity Column -->
                <div class="account-column">
                    <h3 class="account-column-title">Equity</h3>
                    <div class="account-list">
                        <?php if (empty($accounts['equity'])): ?>
                            <div class="account-item-empty">No equity accounts</div>
                        <?php else: ?>
                            <?php foreach ($accounts['equity'] as $equity): ?>
                                <div class="account-item">
                                    <div class="account-name"><?php echo htmlspecialchars($equity['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="account-balance"><?php echo '$' . number_format($equity['balance'], 2); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Transaction Audit History -->
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Transaction History</h2>
        </div>
        
        <?php if (empty($transactions)): ?>
            <div class="empty-state">
                <p>No transactions recorded yet. Use the buttons above to record your first transaction.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Activity</th>
                            <th class="text-right">Amount</th>
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
                                $amountClass = 'text-danger';
                                $amountPrefix = '-';
                            } elseif ($isIncome) {
                                $activity = "Received {$fromAccount} to {$toAccount}";
                                $amountClass = 'text-success';
                                $amountPrefix = '+';
                            } else {
                                $activity = "Transferred from {$fromAccount} to {$toAccount}";
                                $amountClass = '';
                                $amountPrefix = '';
                            }
                            ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($transaction['date'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($transaction['description'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td><?php echo htmlspecialchars($activity, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-right <?php echo $amountClass; ?>">
                                    <strong><?php echo $amountPrefix; ?>$<?php echo number_format($amount, 2); ?></strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- Money Out Modal (Expense) -->
<div id="expenseModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">💸 Record Money Out (Expense)</h3>
                <button type="button" class="modal-close" id="closeExpenseModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="expenseForm" class="form" method="POST" action="<?php echo BASE_URL; ?>/finance/record-expense">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    
                    <div class="form-group">
                        <label for="expense_amount" class="form-label">Amount <span class="required">*</span></label>
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
                        <label for="paid_from" class="form-label">Paid From <span class="required">*</span></label>
                        <select id="paid_from" name="from_account_id" required class="form-select">
                            <option value="">Select account...</option>
                            <?php foreach ($accounts['asset'] as $asset): ?>
                                <option value="<?php echo $asset['id']; ?>">
                                    <?php echo htmlspecialchars($asset['name'], ENT_QUOTES, 'UTF-8'); ?> 
                                    (Balance: $<?php echo number_format($asset['balance'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="expense_category" class="form-label">Expense Category <span class="required">*</span></label>
                        <select id="expense_category" name="to_account_id" required class="form-select">
                            <option value="">Select category...</option>
                            <?php foreach ($accounts['expense'] as $expense): ?>
                                <option value="<?php echo $expense['id']; ?>">
                                    <?php echo htmlspecialchars($expense['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="expense_description" class="form-label">Description <span class="required">*</span></label>
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
                        <label for="expense_date" class="form-label">Date <span class="required">*</span></label>
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
                        <button type="button" class="btn btn-secondary" id="cancelExpenseBtn">Cancel</button>
                        <button type="submit" class="btn btn-danger">Record Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Money In Modal (Income) -->
<div id="incomeModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">💵 Record Money In (Income)</h3>
                <button type="button" class="modal-close" id="closeIncomeModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="incomeForm" class="form" method="POST" action="<?php echo BASE_URL; ?>/finance/record-income">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    
                    <div class="form-group">
                        <label for="income_amount" class="form-label">Amount <span class="required">*</span></label>
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
                        <label for="deposited_to" class="form-label">Deposited To <span class="required">*</span></label>
                        <select id="deposited_to" name="to_account_id" required class="form-select">
                            <option value="">Select account...</option>
                            <?php foreach ($accounts['asset'] as $asset): ?>
                                <option value="<?php echo $asset['id']; ?>">
                                    <?php echo htmlspecialchars($asset['name'], ENT_QUOTES, 'UTF-8'); ?> 
                                    (Balance: $<?php echo number_format($asset['balance'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="income_source" class="form-label">Income Source <span class="required">*</span></label>
                        <select id="income_source" name="from_account_id" required class="form-select">
                            <option value="">Select source...</option>
                            <?php foreach ($accounts['income'] as $income): ?>
                                <option value="<?php echo $income['id']; ?>">
                                    <?php echo htmlspecialchars($income['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="income_description" class="form-label">Description <span class="required">*</span></label>
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
                        <label for="income_date" class="form-label">Date <span class="required">*</span></label>
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
                        <button type="button" class="btn btn-secondary" id="cancelIncomeBtn">Cancel</button>
                        <button type="submit" class="btn btn-success">Record Income</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Finance Module JavaScript -->
<script type="module">
    /**
     * Finance Module - Client-Side Logic
     * Handles modal management for income and expense recording
     */

    // ====================================================================
    // MODAL MANAGEMENT
    // ====================================================================
    
    // Expense Modal Elements
    const expenseModal = document.getElementById('expenseModal');
    const openExpenseModalBtn = document.getElementById('openExpenseModalBtn');
    const closeExpenseModalBtn = document.getElementById('closeExpenseModalBtn');
    const cancelExpenseBtn = document.getElementById('cancelExpenseBtn');

    // Income Modal Elements
    const incomeModal = document.getElementById('incomeModal');
    const openIncomeModalBtn = document.getElementById('openIncomeModalBtn');
    const closeIncomeModalBtn = document.getElementById('closeIncomeModalBtn');
    const cancelIncomeBtn = document.getElementById('cancelIncomeBtn');

    /**
     * Open Expense Modal
     */
    if (openExpenseModalBtn) {
        openExpenseModalBtn.addEventListener('click', () => {
            expenseModal.classList.add('active');
        });
    }

    /**
     * Close Expense Modal
     */
    function closeExpenseModal() {
        expenseModal.classList.remove('active');
        document.getElementById('expenseForm').reset();
    }

    if (closeExpenseModalBtn) {
        closeExpenseModalBtn.addEventListener('click', closeExpenseModal);
    }

    if (cancelExpenseBtn) {
        cancelExpenseBtn.addEventListener('click', closeExpenseModal);
    }

    /**
     * Open Income Modal
     */
    if (openIncomeModalBtn) {
        openIncomeModalBtn.addEventListener('click', () => {
            incomeModal.classList.add('active');
        });
    }

    /**
     * Close Income Modal
     */
    function closeIncomeModal() {
        incomeModal.classList.remove('active');
        document.getElementById('incomeForm').reset();
    }

    if (closeIncomeModalBtn) {
        closeIncomeModalBtn.addEventListener('click', closeIncomeModal);
    }

    if (cancelIncomeBtn) {
        cancelIncomeBtn.addEventListener('click', closeIncomeModal);
    }

    // Close modals on outside click
    if (expenseModal) {
        expenseModal.addEventListener('click', (e) => {
            if (e.target === expenseModal) {
                closeExpenseModal();
            }
        });
    }

    if (incomeModal) {
        incomeModal.addEventListener('click', (e) => {
            if (e.target === incomeModal) {
                closeIncomeModal();
            }
        });
    }

    // Close modals on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (expenseModal.classList.contains('active')) {
                closeExpenseModal();
            }
            if (incomeModal.classList.contains('active')) {
                closeIncomeModal();
            }
        }
    });
</script>

<?php
// Made with Bob
