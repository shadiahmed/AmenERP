<?php
/**
 * Supplier Management & Accounts Payable Module - Executive Dashboard
 *
 * Corporate management interface for supplier accounts with real-time
 * payment processing, liability monitoring, and accounts payable tracking.
 *
 * Features:
 * - Master Accounts Payable Ledger with supplier balances
 * - Real-time payment disbursement processing with validation
 * - Aggregate KPI metrics (Total Payables, High-Risk Accounts)
 * - Recent payment disbursement history tracking
 * - Modal payment form with inline validation
 * - CSRF protection and strict input sanitization
 * - Responsive CSS Grid/Flexbox layout
 *
 * @package AmenERP\Modules\Suppliers
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
 * Load SupplierModel for data operations
 */
require_once __DIR__ . '/models/SupplierModel.php';

// ============================================================================
// DATA INITIALIZATION
// ============================================================================

/**
 * Initialize SupplierModel instance
 */
$supplierModel = new SupplierModel();

/**
 * Fetch all active suppliers with liability information
 * Includes credit limits, outstanding balances, and contact details
 */
$allSuppliers = $supplierModel->getAllSuppliers('active');

/**
 * Fetch aggregate accounts payable statistics
 * Includes total payables, credit available, and utilization metrics
 */
$payableStats = $supplierModel->getSupplierSummaryStats();

/**
 * Fetch recent payment disbursements (last 5 transactions)
 * Used for the payment history sidebar
 */
$recentDisbursements = $supplierModel->getRecentDisbursements(5);

/**
 * Identify high-risk accounts (suppliers where we owe more than 80% of credit limit)
 */
$highRiskAccounts = array_filter($allSuppliers, function($supplier) {
    $utilizationRate = $supplier['credit_limit'] > 0 
        ? ($supplier['outstanding_balance'] / $supplier['credit_limit']) * 100 
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
 * @param float $outstanding Outstanding balance (what we owe)
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
        <h1 class="page-title">Supplier Management & Accounts Payable</h1>
        <p class="page-subtitle">Manage vendor accounts and track outstanding payment obligations</p>
    </div>

    <!-- KPI Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">💸</div>
            <div class="metric-content">
                <div class="metric-label">Total Payables</div>
                <div class="metric-value"><?php echo formatCurrency($payableStats['total_payables']); ?></div>
                <div class="metric-subtitle">Amount We Owe</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">📊</div>
            <div class="metric-content">
                <div class="metric-label">Credit Available</div>
                <div class="metric-value"><?php echo formatCurrency($payableStats['total_credit_available']); ?></div>
                <div class="metric-subtitle"><?php echo $payableStats['credit_utilization_percentage']; ?>% Utilized</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">🏢</div>
            <div class="metric-content">
                <div class="metric-label">Active Suppliers</div>
                <div class="metric-value"><?php echo formatNumber($payableStats['active_suppliers']); ?></div>
                <div class="metric-subtitle">of <?php echo formatNumber($payableStats['total_suppliers']); ?> total</div>
            </div>
        </div>
    </div>

    <!-- Split Layout: Main Ledger + Sidebar -->
    <div class="layout-split-pane">
        <!-- Main Panel: Accounts Payable Ledger -->
        <div class="split-main">
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Corporate Accounts Payable Ledger</h2>
                </div>
                
                <?php if (empty($allSuppliers)): ?>
                    <div class="empty-state">
                        <p>No active suppliers found. Add suppliers to start tracking payables.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table" id="supplierLedger">
                            <thead>
                                <tr>
                                    <th>Supplier</th>
                                    <th>Contact</th>
                                    <th>Credit Limit</th>
                                    <th>Outstanding Balance</th>
                                    <th>Available Credit</th>
                                    <th>Utilization</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allSuppliers as $supplier): ?>
                                    <?php
                                    $utilization = calculateUtilization(
                                        $supplier['outstanding_balance'],
                                        $supplier['credit_limit']
                                    );
                                    $utilizationClass = getUtilizationClass($utilization);
                                    ?>
                                    <tr data-supplier-id="<?php echo (int)$supplier['id']; ?>"
                                        data-supplier-name="<?php echo htmlspecialchars($supplier['supplier_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-supplier-code="<?php echo htmlspecialchars($supplier['supplier_code'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-credit-limit="<?php echo (float)$supplier['credit_limit']; ?>"
                                        data-outstanding-balance="<?php echo (float)$supplier['outstanding_balance']; ?>"
                                        data-available-credit="<?php echo (float)$supplier['credit_available']; ?>">
                                        <td>
                                            <div class="table-cell-primary"><?php echo htmlspecialchars($supplier['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="table-cell-secondary"><?php echo htmlspecialchars($supplier['supplier_code'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        </td>
                                        <td>
                                            <div class="table-cell-primary"><?php echo htmlspecialchars($supplier['contact_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="table-cell-secondary"><?php echo htmlspecialchars($supplier['contact_email'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                                        </td>
                                        <td><?php echo formatCurrency($supplier['credit_limit']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $supplier['outstanding_balance'] > 0 ? 'badge-warning' : 'badge-success'; ?>">
                                                <?php echo formatCurrency($supplier['outstanding_balance']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatCurrency($supplier['credit_available']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $utilizationClass; ?>">
                                                <?php echo $utilization; ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-primary btn-small disburse-payment-btn"
                                                    data-supplier-id="<?php echo (int)$supplier['id']; ?>"
                                                    <?php echo $supplier['outstanding_balance'] <= 0 ? 'disabled' : ''; ?>>
                                                💳 Disburse Payment
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

        <!-- Sidebar Panel: KPIs and Recent Disbursements -->
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
                                <?php echo count($highRiskAccounts); ?> supplier(s) have exceeded 80% of their credit limit.
                            </div>
                        </div>
                        <?php foreach (array_slice($highRiskAccounts, 0, 3) as $riskSupplier): ?>
                            <div class="list-item">
                                <div class="list-item-header">
                                    <span class="list-item-title"><?php echo htmlspecialchars($riskSupplier['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="badge status-critical">
                                        <?php echo calculateUtilization($riskSupplier['outstanding_balance'], $riskSupplier['credit_limit']); ?>%
                                    </span>
                                </div>
                                <div class="list-item-details">
                                    Balance: <?php echo formatCurrency($riskSupplier['outstanding_balance']); ?> / 
                                    Limit: <?php echo formatCurrency($riskSupplier['credit_limit']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recent Payment Disbursements -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📝 Recent Disbursements</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recentDisbursements)): ?>
                        <div class="empty-state">
                            <p>No payment disbursements yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="list">
                            <?php foreach ($recentDisbursements as $disbursement): ?>
                                <div class="list-item">
                                    <div class="list-item-header">
                                        <span class="list-item-code"><?php echo htmlspecialchars($disbursement['disbursement_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="list-item-amount"><?php echo formatCurrency($disbursement['amount_paid']); ?></span>
                                    </div>
                                    <div class="list-item-details">
                                        <?php echo htmlspecialchars($disbursement['supplier_name'], ENT_QUOTES, 'UTF-8'); ?> • 
                                        <?php echo htmlspecialchars($disbursement['payment_method'], ENT_QUOTES, 'UTF-8'); ?> • 
                                        <?php echo date('M d, Y', strtotime($disbursement['processed_at'])); ?>
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
                <h3 class="modal-title">Disburse Vendor Payment</h3>
                <button type="button" class="modal-close" id="closeModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Supplier Information Display -->
                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Supplier:</span>
                        <span class="info-value" id="modalSupplierName">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Supplier Code:</span>
                        <span class="info-value" id="modalSupplierCode">-</span>
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
                <form id="paymentForm" class="form" method="POST" action="<?php echo BASE_URL; ?>/suppliers/process-payment">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="supplier_id" id="paymentSupplierId" value="">

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
                            <option value="check">Check</option>
                            <option value="wire_transfer">Wire Transfer</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="ach">ACH Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="online_payment">Online Payment</option>
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
                            placeholder="Check number, wire confirmation, etc."
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
     * Supplier Payment Processing Module
     * 
     * Handles UI interactions for the supplier payables dashboard:
     * - Opens payment modal when "Disburse Payment" is clicked
     * - Populates modal with supplier data
     * - Validates payment amount in real-time
     * - Prevents overpayment (amount exceeding balance)
     * 
     * Uses event delegation for performance with large supplier lists
     */

    // ====================================================================
    // DOM ELEMENT REFERENCES
    // ====================================================================
    const paymentModal = document.getElementById('paymentModal');
    const paymentForm = document.getElementById('paymentForm');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelPaymentBtn = document.getElementById('cancelPaymentBtn');
    const supplierLedger = document.getElementById('supplierLedger');
    const amountInput = document.getElementById('amount_paid');
    const amountError = document.getElementById('amountError');
    const submitBtn = document.getElementById('submitPaymentBtn');

    // Modal supplier info elements
    const modalSupplierName = document.getElementById('modalSupplierName');
    const modalSupplierCode = document.getElementById('modalSupplierCode');
    const modalOutstandingBalance = document.getElementById('modalOutstandingBalance');
    const modalCreditLimit = document.getElementById('modalCreditLimit');
    const paymentSupplierId = document.getElementById('paymentSupplierId');

    // Store current supplier data
    let currentSupplier = null;

    // ====================================================================
    // EVENT DELEGATION - Handle "Disburse Payment" Button Clicks
    // ====================================================================
    if (supplierLedger) {
        supplierLedger.addEventListener('click', (event) => {
            // Find the button that was clicked
            const button = event.target.closest('.disburse-payment-btn');
            
            if (button && !button.disabled) {
                // Get supplier data from the table row
                const row = button.closest('tr');
                
                currentSupplier = {
                    id: parseInt(row.dataset.supplierId),
                    name: row.dataset.supplierName,
                    code: row.dataset.supplierCode,
                    creditLimit: parseFloat(row.dataset.creditLimit),
                    outstandingBalance: parseFloat(row.dataset.outstandingBalance),
                    availableCredit: parseFloat(row.dataset.availableCredit)
                };
                
                // Open payment modal with supplier data
                openPaymentModal(currentSupplier);
            }
        });
    }

    // ====================================================================
    // MODAL FUNCTIONS
    // ====================================================================
    
    /**
     * Open payment modal and populate with supplier data
     */
    function openPaymentModal(supplier) {
        // Populate supplier information
        modalSupplierName.textContent = supplier.name;
        modalSupplierCode.textContent = supplier.code;
        modalOutstandingBalance.textContent = formatCurrency(supplier.outstandingBalance);
        modalCreditLimit.textContent = formatCurrency(supplier.creditLimit);
        paymentSupplierId.value = supplier.id;
        
        // Reset form
        paymentForm.reset();
        paymentSupplierId.value = supplier.id; // Restore after reset
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
        currentSupplier = null;
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
            if (!currentSupplier) return;
            
            const enteredAmount = parseFloat(amountInput.value) || 0;
            const maxAllowed = currentSupplier.outstandingBalance;
            
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
