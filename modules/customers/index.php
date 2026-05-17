<?php
/**
 * Customer Accounts & Receivables Module - Executive Dashboard
 *
 * Corporate management interface for customer credit accounts with real-time
 * payment processing, credit monitoring, and accounts receivable tracking.
 *
 * Features:
 * - Master Credit Ledger with customer balances
 * - Real-time payment processing with validation
 * - Aggregate KPI metrics (Total Receivables, High-Risk Accounts)
 * - Recent payment history tracking
 * - Modal payment form with inline validation
 * - CSRF protection and strict input sanitization
 * - Responsive CSS Grid/Flexbox layout
 *
 * @package AmenERP\Modules\Customers
 * @author Bob
 * @version 1.0.0
 */

declare(strict_types=1);

// ============================================================================
// BOOTSTRAP: Core Configuration & Dependencies
// ============================================================================

/**
 * Ensure session is active (should already be started by front controller)
 * This is a safety check for direct access scenarios
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Load required core classes
 * These are autoloaded by the front controller, but we verify they exist
 */
if (!class_exists('Database')) {
    require_once __DIR__ . '/../../core/Database.php';
}

if (!class_exists('Csrf')) {
    require_once __DIR__ . '/../../core/Csrf.php';
}

/**
 * Load CustomerModel for data operations
 */
require_once __DIR__ . '/models/CustomerModel.php';

// ============================================================================
// DATA INITIALIZATION
// ============================================================================

/**
 * Initialize CustomerModel instance
 */
$customerModel = new CustomerModel();

/**
 * Fetch all active customers with credit information
 * Includes credit limits, outstanding balances, and contact details
 */
$allCustomers = $customerModel->getAllCustomers('active');

/**
 * Fetch aggregate receivables statistics
 * Includes total receivables, credit extended, and utilization metrics
 */
$receivablesStats = $customerModel->getReceivablesStats();

/**
 * Fetch recent payment receipts (last 5 transactions)
 * Used for the payment history sidebar
 */
$recentReceipts = [];
try {
    $db = Database::getInstance();
    $receiptsSql = "
        SELECT 
            cr.receipt_number,
            cr.amount_paid,
            cr.payment_method,
            cr.processed_at,
            c.company_name,
            c.customer_code
        FROM customer_receipts cr
        INNER JOIN customers c ON cr.customer_id = c.id
        ORDER BY cr.processed_at DESC
        LIMIT 5
    ";
    $stmt = $db->query($receiptsSql);
    $recentReceipts = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Failed to fetch recent receipts: ' . $e->getMessage());
}

/**
 * Identify high-risk accounts (customers exceeding 80% of credit limit)
 */
$highRiskAccounts = array_filter($allCustomers, function($customer) {
    $utilizationRate = $customer['credit_limit'] > 0 
        ? ($customer['outstanding_balance'] / $customer['credit_limit']) * 100 
        : 0;
    return $utilizationRate >= 80;
});

/**
 * Generate CSRF token for payment form security
 * Protects against Cross-Site Request Forgery attacks
 */
$csrfToken = Csrf::generateToken();

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Format currency values for display
 *
 * @param float $amount The amount to format
 * @return string Formatted currency string (e.g., "$1,234.56")
 */
function formatCurrency(float $amount): string
{
    return '$' . number_format($amount, 2);
}

/**
 * Format integer numbers with thousands separator
 *
 * @param int $number The number to format
 * @return string Formatted number string (e.g., "1,234")
 */
function formatNumber(int $number): string
{
    return number_format($number);
}

/**
 * Calculate credit utilization percentage
 *
 * @param float $outstanding Outstanding balance
 * @param float $limit Credit limit
 * @return float Utilization percentage
 */
function calculateUtilization(float $outstanding, float $limit): float
{
    return $limit > 0 ? round(($outstanding / $limit) * 100, 1) : 0.0;
}

/**
 * Get utilization status class for styling
 *
 * @param float $percentage Utilization percentage
 * @return string CSS class name
 */
function getUtilizationClass(float $percentage): string
{
    if ($percentage >= 90) return 'status-critical';
    if ($percentage >= 80) return 'status-warning';
    if ($percentage >= 50) return 'status-moderate';
    return 'status-good';
}
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
        <h1 class="page-title">Customer Accounts & Receivables</h1>
        <p class="page-subtitle">Manage B2B customer credit accounts and track outstanding balances</p>
    </div>

    <!-- KPI Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">💰</div>
            <div class="metric-content">
                <div class="metric-label">Total Receivables</div>
                <div class="metric-value"><?php echo formatCurrency($receivablesStats['total_receivables']); ?></div>
                <div class="metric-subtitle">Outstanding Debt</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">📊</div>
            <div class="metric-content">
                <div class="metric-label">Credit Extended</div>
                <div class="metric-value"><?php echo formatCurrency($receivablesStats['total_credit_extended']); ?></div>
                <div class="metric-subtitle"><?php echo $receivablesStats['credit_utilization_percentage']; ?>% Utilized</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">👥</div>
            <div class="metric-content">
                <div class="metric-label">Active Customers</div>
                <div class="metric-value"><?php echo formatNumber($receivablesStats['active_customers']); ?></div>
                <div class="metric-subtitle">of <?php echo formatNumber($receivablesStats['total_customers']); ?> total</div>
            </div>
        </div>
    </div>

    <!-- Split Layout: Main Ledger + Sidebar -->
    <div class="layout-split-pane">
        <!-- Main Panel: Credit Ledger -->
        <div class="split-main">
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Master Credit Ledger</h2>
                </div>
                
                <?php if (empty($allCustomers)): ?>
                    <div class="empty-state">
                        <p>No active customers found. Add customers to start tracking receivables.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table" id="customerLedger">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Contact</th>
                                    <th>Credit Limit</th>
                                    <th>Outstanding Balance</th>
                                    <th>Available Credit</th>
                                    <th>Utilization</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allCustomers as $customer): ?>
                                    <?php
                                    $utilization = calculateUtilization(
                                        $customer['outstanding_balance'],
                                        $customer['credit_limit']
                                    );
                                    $utilizationClass = getUtilizationClass($utilization);
                                    ?>
                                    <tr data-customer-id="<?php echo (int)$customer['id']; ?>"
                                        data-customer-name="<?php echo htmlspecialchars($customer['company_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-customer-code="<?php echo htmlspecialchars($customer['customer_code'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-credit-limit="<?php echo (float)$customer['credit_limit']; ?>"
                                        data-outstanding-balance="<?php echo (float)$customer['outstanding_balance']; ?>"
                                        data-available-credit="<?php echo (float)$customer['credit_available']; ?>">
                                        <td>
                                            <div class="table-cell-primary"><?php echo htmlspecialchars($customer['company_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="table-cell-secondary"><?php echo htmlspecialchars($customer['customer_code'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        </td>
                                        <td>
                                            <div class="table-cell-primary"><?php echo htmlspecialchars($customer['contact_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="table-cell-secondary"><?php echo htmlspecialchars($customer['phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                                        </td>
                                        <td><?php echo formatCurrency($customer['credit_limit']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $customer['outstanding_balance'] > 0 ? 'badge-warning' : 'badge-success'; ?>">
                                                <?php echo formatCurrency($customer['outstanding_balance']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatCurrency($customer['credit_available']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $utilizationClass; ?>">
                                                <?php echo $utilization; ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-primary btn-small record-payment-btn"
                                                    data-customer-id="<?php echo (int)$customer['id']; ?>">
                                                💳 Record Payment
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Sidebar Panel: KPIs and Recent Receipts -->
        <aside class="split-sidebar">
            <!-- High-Risk Accounts Alert -->
            <?php if (!empty($highRiskAccounts)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">⚠️ High-Risk Accounts</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <div class="alert-title">Credit Limit Warning</div>
                            <div class="alert-text">
                                <?php echo count($highRiskAccounts); ?> customer(s) have exceeded 80% of their credit limit.
                            </div>
                        </div>
                        <?php foreach (array_slice($highRiskAccounts, 0, 3) as $riskCustomer): ?>
                            <div class="list-item">
                                <div class="list-item-header">
                                    <span class="list-item-title"><?php echo htmlspecialchars($riskCustomer['company_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="badge status-critical">
                                        <?php echo calculateUtilization($riskCustomer['outstanding_balance'], $riskCustomer['credit_limit']); ?>%
                                    </span>
                                </div>
                                <div class="list-item-details">
                                    Balance: <?php echo formatCurrency($riskCustomer['outstanding_balance']); ?> / 
                                    Limit: <?php echo formatCurrency($riskCustomer['credit_limit']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recent Payment Receipts -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📝 Recent Payments</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recentReceipts)): ?>
                        <div class="empty-state">
                            <p>No payment receipts yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="list">
                            <?php foreach ($recentReceipts as $receipt): ?>
                                <div class="list-item">
                                    <div class="list-item-header">
                                        <span class="list-item-code"><?php echo htmlspecialchars($receipt['receipt_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="list-item-amount"><?php echo formatCurrency((float)$receipt['amount_paid']); ?></span>
                                    </div>
                                    <div class="list-item-details">
                                        <?php echo htmlspecialchars($receipt['company_name'], ENT_QUOTES, 'UTF-8'); ?> • 
                                        <?php echo htmlspecialchars($receipt['payment_method'], ENT_QUOTES, 'UTF-8'); ?> • 
                                        <?php echo date('M d, Y', strtotime($receipt['processed_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- Payment Processing Modal -->
<div class="modal-overlay" id="paymentModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Record Customer Payment</h3>
                <button type="button" class="modal-close" id="closeModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Customer Information Display -->
                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Customer:</span>
                        <span class="info-value" id="modalCustomerName">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Customer Code:</span>
                        <span class="info-value" id="modalCustomerCode">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Outstanding Balance:</span>
                        <span class="info-value" id="modalOutstandingBalance">$0.00</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Credit Limit:</span>
                        <span class="info-value" id="modalCreditLimit">$0.00</span>
                    </div>
                </div>

                <!-- Payment Form -->
                <form id="paymentForm" class="form" method="POST" action="<?php echo BASE_URL; ?>/customers/process-payment">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="customer_id" id="paymentCustomerId" value="">

                    <div class="form-group">
                        <label for="amount_paid" class="form-label">
                            Payment Amount <span class="required">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="amount_paid" 
                            name="amount_paid" 
                            class="form-input" 
                            step="0.01"
                            min="0.01"
                            placeholder="0.00"
                            required
                        >
                        <div class="form-error" id="amountError">Payment amount cannot exceed outstanding balance</div>
                    </div>

                    <div class="form-group">
                        <label for="payment_method" class="form-label">
                            Payment Method <span class="required">*</span>
                        </label>
                        <select id="payment_method" name="payment_method" class="form-select" required>
                            <option value="">Select payment method</option>
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="mobile_payment">Mobile Payment</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="payment_reference" class="form-label">
                            Payment Reference
                        </label>
                        <input 
                            type="text" 
                            id="payment_reference" 
                            name="payment_reference" 
                            class="form-input" 
                            placeholder="Check number, transaction ID, etc."
                            maxlength="100"
                        >
                    </div>

                    <div class="form-group">
                        <label for="payment_notes" class="form-label">
                            Notes
                        </label>
                        <input 
                            type="text" 
                            id="payment_notes" 
                            name="payment_notes" 
                            class="form-input" 
                            placeholder="Optional payment notes"
                            maxlength="500"
                        >
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelPaymentBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitPaymentBtn">💳 Process Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Vanilla JavaScript - ES6+ Module -->
<script type="module">
    /**
     * Customer Payment Processing Module
     * 
     * Handles UI interactions for the customer receivables dashboard:
     * - Opens payment modal when "Record Payment" is clicked
     * - Populates modal with customer data
     * - Validates payment amount in real-time
     * - Prevents overpayment (amount exceeding balance)
     * 
     * Uses event delegation for performance with large customer lists
     */

    // ====================================================================
    // DOM ELEMENT REFERENCES
    // ====================================================================
    const paymentModal = document.getElementById('paymentModal');
    const paymentForm = document.getElementById('paymentForm');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelPaymentBtn = document.getElementById('cancelPaymentBtn');
    const customerLedger = document.getElementById('customerLedger');
    const amountInput = document.getElementById('amount_paid');
    const amountError = document.getElementById('amountError');
    const submitBtn = document.getElementById('submitPaymentBtn');

    // Modal customer info elements
    const modalCustomerName = document.getElementById('modalCustomerName');
    const modalCustomerCode = document.getElementById('modalCustomerCode');
    const modalOutstandingBalance = document.getElementById('modalOutstandingBalance');
    const modalCreditLimit = document.getElementById('modalCreditLimit');
    const paymentCustomerId = document.getElementById('paymentCustomerId');

    // Store current customer data
    let currentCustomer = null;

    // ====================================================================
    // EVENT DELEGATION - Handle "Record Payment" Button Clicks
    // ====================================================================
    if (customerLedger) {
        customerLedger.addEventListener('click', (event) => {
            // Find the button that was clicked
            const button = event.target.closest('.record-payment-btn');
            
            if (button) {
                // Get customer data from the table row
                const row = button.closest('tr');
                
                currentCustomer = {
                    id: parseInt(row.dataset.customerId),
                    name: row.dataset.customerName,
                    code: row.dataset.customerCode,
                    creditLimit: parseFloat(row.dataset.creditLimit),
                    outstandingBalance: parseFloat(row.dataset.outstandingBalance),
                    availableCredit: parseFloat(row.dataset.availableCredit)
                };
                
                // Open payment modal with customer data
                openPaymentModal(currentCustomer);
            }
        });
    }

    // ====================================================================
    // MODAL FUNCTIONS
    // ====================================================================
    
    /**
     * Open payment modal and populate with customer data
     */
    function openPaymentModal(customer) {
        // Populate customer information
        modalCustomerName.textContent = customer.name;
        modalCustomerCode.textContent = customer.code;
        modalOutstandingBalance.textContent = formatCurrency(customer.outstandingBalance);
        modalCreditLimit.textContent = formatCurrency(customer.creditLimit);
        paymentCustomerId.value = customer.id;
        
        // Reset form
        paymentForm.reset();
        paymentCustomerId.value = customer.id; // Restore after reset
        amountInput.classList.remove('error');
        amountError.classList.remove('active');
        submitBtn.disabled = false;
        
        // Show modal
        paymentModal.classList.add('active');
        
        // Focus on amount input
        setTimeout(() => amountInput.focus(), 100);
    }

    /**
     * Close payment modal
     */
    function closePaymentModal() {
        paymentModal.classList.remove('active');
        currentCustomer = null;
    }

    // ====================================================================
    // REAL-TIME PAYMENT VALIDATION
    // ====================================================================
    
    /**
     * Validate payment amount as user types
     * Prevents overpayment (amount exceeding outstanding balance)
     */
    if (amountInput) {
        amountInput.addEventListener('input', () => {
            if (!currentCustomer) return;
            
            const enteredAmount = parseFloat(amountInput.value) || 0;
            const maxAllowed = currentCustomer.outstandingBalance;
            
            if (enteredAmount > maxAllowed) {
                // Show error - payment exceeds balance
                amountInput.classList.add('error');
                amountError.classList.add('active');
                submitBtn.disabled = true;
            } else {
                // Valid amount
                amountInput.classList.remove('error');
                amountError.classList.remove('active');
                submitBtn.disabled = false;
            }
        });
    }

    // ====================================================================
    // MODAL CLOSE HANDLERS
    // ====================================================================
    
    // Close button
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closePaymentModal);
    }
    
    // Cancel button
    if (cancelPaymentBtn) {
        cancelPaymentBtn.addEventListener('click', closePaymentModal);
    }
    
    // Click outside modal to close
    if (paymentModal) {
        paymentModal.addEventListener('click', (event) => {
            if (event.target === paymentModal) {
                closePaymentModal();
            }
        });
    }
    
    // ESC key to close
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && paymentModal.classList.contains('active')) {
            closePaymentModal();
        }
    });

    // ====================================================================
    // UTILITY FUNCTIONS
    // ====================================================================
    
    /**
     * Format number as currency
     */
    function formatCurrency(amount) {
        return '$' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
</script>

<?php
// Made with Bob
