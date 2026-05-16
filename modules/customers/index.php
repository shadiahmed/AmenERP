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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Accounts & Receivables - <?php echo APP_NAME; ?></title>
    <style>
        /* ================================================================
           CSS VARIABLES - Global Design Tokens
           ================================================================ */
        :root {
            --color-primary: #2563eb;
            --color-primary-dark: #1e40af;
            --color-success: #059669;
            --color-warning: #d97706;
            --color-danger: #dc2626;
            --color-critical: #991b1b;
            --color-text: #1f2937;
            --color-text-light: #6b7280;
            --color-border: #e5e7eb;
            --color-bg: #f9fafb;
            --color-white: #ffffff;
            --spacing-xs: 0.5rem;
            --spacing-sm: 0.75rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --border-radius: 0.5rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
        }

        /* ================================================================
           LAYOUT - CSS Grid Dashboard Structure
           ================================================================ */
        .customers-dashboard {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: var(--spacing-xl);
            padding: var(--spacing-xl);
            background: var(--color-bg);
            min-height: 100vh;
        }

        .main-panel {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }

        .sidebar-panel {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }

        /* ================================================================
           ALERTS - Flash Messages
           ================================================================ */
        .alert {
            padding: var(--spacing-md);
            border-radius: var(--border-radius);
            margin-bottom: var(--spacing-lg);
            font-size: var(--font-size-sm);
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: var(--color-success);
            border: 1px solid var(--color-success);
        }

        .alert-error {
            background: #fee2e2;
            color: var(--color-danger);
            border: 1px solid var(--color-danger);
        }

        /* ================================================================
           HEADER SECTION
           ================================================================ */
        .module-header {
            background: var(--color-white);
            padding: var(--spacing-lg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
        }

        .module-header h2 {
            margin: 0;
            font-size: var(--font-size-2xl);
            color: var(--color-text);
            font-weight: 600;
        }

        /* ================================================================
           KPI METRICS - Aggregate Statistics
           ================================================================ */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
        }

        .kpi-card {
            background: var(--color-white);
            padding: var(--spacing-lg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .kpi-icon {
            font-size: 2rem;
            width: 3rem;
            height: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-bg);
            border-radius: var(--border-radius);
        }

        .kpi-content {
            flex: 1;
        }

        .kpi-label {
            font-size: var(--font-size-sm);
            color: var(--color-text-light);
            margin-bottom: var(--spacing-xs);
        }

        .kpi-value {
            font-size: var(--font-size-xl);
            font-weight: 700;
            color: var(--color-text);
        }

        .kpi-subtitle {
            font-size: var(--font-size-sm);
            color: var(--color-text-light);
            margin-top: var(--spacing-xs);
        }

        /* ================================================================
           CREDIT LEDGER TABLE
           ================================================================ */
        .ledger-section {
            background: var(--color-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .section-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--color-border);
        }

        .section-header h3 {
            margin: 0;
            font-size: var(--font-size-lg);
            color: var(--color-text);
            font-weight: 600;
        }

        .table-container {
            overflow-x: auto;
        }

        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: var(--font-size-sm);
        }

        .ledger-table thead {
            background: var(--color-bg);
        }

        .ledger-table th {
            padding: var(--spacing-md);
            text-align: left;
            font-weight: 600;
            color: var(--color-text);
            border-bottom: 2px solid var(--color-border);
        }

        .ledger-table td {
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--color-border);
            color: var(--color-text);
        }

        .ledger-table tbody tr {
            transition: background-color 0.2s;
            cursor: pointer;
        }

        .ledger-table tbody tr:hover {
            background: var(--color-bg);
        }

        .customer-name {
            font-weight: 600;
            color: var(--color-text);
        }

        .customer-code {
            font-size: var(--font-size-sm);
            color: var(--color-text-light);
        }

        .balance-amount {
            font-weight: 600;
        }

        .balance-positive {
            color: var(--color-danger);
        }

        .balance-zero {
            color: var(--color-text-light);
        }

        .utilization-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-good {
            background: #d1fae5;
            color: var(--color-success);
        }

        .status-moderate {
            background: #fef3c7;
            color: var(--color-warning);
        }

        .status-warning {
            background: #fed7aa;
            color: var(--color-warning);
        }

        .status-critical {
            background: #fee2e2;
            color: var(--color-danger);
        }

        /* ================================================================
           ACTION BUTTONS
           ================================================================ */
        .btn {
            padding: var(--spacing-sm) var(--spacing-md);
            border: none;
            border-radius: var(--border-radius);
            font-size: var(--font-size-sm);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .btn-primary {
            background: var(--color-primary);
            color: var(--color-white);
        }

        .btn-primary:hover {
            background: var(--color-primary-dark);
        }

        .btn-small {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
        }

        /* ================================================================
           SIDEBAR WIDGETS
           ================================================================ */
        .sidebar-widget {
            background: var(--color-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .widget-header {
            padding: var(--spacing-md);
            background: var(--color-bg);
            border-bottom: 1px solid var(--color-border);
        }

        .widget-header h4 {
            margin: 0;
            font-size: var(--font-size-base);
            color: var(--color-text);
            font-weight: 600;
        }

        .widget-content {
            padding: var(--spacing-md);
        }

        .risk-alert {
            padding: var(--spacing-md);
            background: #fef3c7;
            border-left: 4px solid var(--color-warning);
            margin-bottom: var(--spacing-md);
        }

        .risk-alert-title {
            font-weight: 600;
            color: var(--color-warning);
            margin-bottom: var(--spacing-xs);
        }

        .risk-alert-text {
            font-size: var(--font-size-sm);
            color: var(--color-text);
        }

        .receipt-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }

        .receipt-item {
            padding: var(--spacing-sm);
            border: 1px solid var(--color-border);
            border-radius: var(--border-radius);
            font-size: var(--font-size-sm);
        }

        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-xs);
        }

        .receipt-number {
            font-weight: 600;
            color: var(--color-primary);
        }

        .receipt-amount {
            font-weight: 600;
            color: var(--color-success);
        }

        .receipt-details {
            color: var(--color-text-light);
            font-size: 0.8125rem;
        }

        .empty-state {
            padding: var(--spacing-xl);
            text-align: center;
            color: var(--color-text-light);
        }

        /* ================================================================
           PAYMENT MODAL
           ================================================================ */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: var(--color-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--color-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: var(--font-size-lg);
            color: var(--color-text);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--color-text-light);
            padding: 0;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            color: var(--color-text);
        }

        .modal-body {
            padding: var(--spacing-lg);
        }

        .customer-info-box {
            background: var(--color-bg);
            padding: var(--spacing-md);
            border-radius: var(--border-radius);
            margin-bottom: var(--spacing-lg);
        }

        .customer-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: var(--spacing-xs);
            font-size: var(--font-size-sm);
        }

        .customer-info-label {
            color: var(--color-text-light);
        }

        .customer-info-value {
            font-weight: 600;
            color: var(--color-text);
        }

        .form-group {
            margin-bottom: var(--spacing-md);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-xs);
            font-size: var(--font-size-sm);
            font-weight: 500;
            color: var(--color-text);
        }

        .required {
            color: var(--color-danger);
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: var(--spacing-sm);
            border: 1px solid var(--color-border);
            border-radius: var(--border-radius);
            font-size: var(--font-size-sm);
            color: var(--color-text);
            transition: border-color 0.2s;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .form-input.error {
            border-color: var(--color-danger);
        }

        .form-error {
            color: var(--color-danger);
            font-size: 0.8125rem;
            margin-top: var(--spacing-xs);
            display: none;
        }

        .form-error.active {
            display: block;
        }

        .form-actions {
            display: flex;
            gap: var(--spacing-md);
            justify-content: flex-end;
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--color-border);
        }

        .btn-secondary {
            background: var(--color-bg);
            color: var(--color-text);
        }

        .btn-secondary:hover {
            background: var(--color-border);
        }

        /* ================================================================
           RESPONSIVE DESIGN - Mobile First
           ================================================================ */
        @media (max-width: 1024px) {
            .customers-dashboard {
                grid-template-columns: 1fr;
            }

            .sidebar-panel {
                order: -1;
            }
        }

        @media (max-width: 768px) {
            .customers-dashboard {
                padding: var(--spacing-md);
            }

            .kpi-grid {
                grid-template-columns: 1fr;
            }

            .ledger-table {
                font-size: 0.8125rem;
            }

            .ledger-table th,
            .ledger-table td {
                padding: var(--spacing-sm);
            }
        }
    </style>
</head>
<body>
    <div class="customers-dashboard">
        <!-- Main Panel: Credit Ledger -->
        <div class="main-panel">
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

            <!-- Module Header -->
            <div class="module-header">
                <h2>Customer Accounts & Receivables</h2>
            </div>

            <!-- KPI Metrics -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon">💰</div>
                    <div class="kpi-content">
                        <div class="kpi-label">Total Receivables</div>
                        <div class="kpi-value"><?php echo formatCurrency($receivablesStats['total_receivables']); ?></div>
                        <div class="kpi-subtitle">Outstanding Debt</div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">📊</div>
                    <div class="kpi-content">
                        <div class="kpi-label">Credit Extended</div>
                        <div class="kpi-value"><?php echo formatCurrency($receivablesStats['total_credit_extended']); ?></div>
                        <div class="kpi-subtitle"><?php echo $receivablesStats['credit_utilization_percentage']; ?>% Utilized</div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">👥</div>
                    <div class="kpi-content">
                        <div class="kpi-label">Active Customers</div>
                        <div class="kpi-value"><?php echo formatNumber($receivablesStats['active_customers']); ?></div>
                        <div class="kpi-subtitle">of <?php echo formatNumber($receivablesStats['total_customers']); ?> total</div>
                    </div>
                </div>
            </div>

            <!-- Master Credit Ledger -->
            <section class="ledger-section">
                <div class="section-header">
                    <h3>Master Credit Ledger</h3>
                </div>
                
                <?php if (empty($allCustomers)): ?>
                    <div class="empty-state">
                        <p>No active customers found. Add customers to start tracking receivables.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="ledger-table" id="customerLedger">
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
                                            <div class="customer-name"><?php echo htmlspecialchars($customer['company_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="customer-code"><?php echo htmlspecialchars($customer['customer_code'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($customer['contact_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="customer-code"><?php echo htmlspecialchars($customer['phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                                        </td>
                                        <td><?php echo formatCurrency($customer['credit_limit']); ?></td>
                                        <td>
                                            <span class="balance-amount <?php echo $customer['outstanding_balance'] > 0 ? 'balance-positive' : 'balance-zero'; ?>">
                                                <?php echo formatCurrency($customer['outstanding_balance']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatCurrency($customer['credit_available']); ?></td>
                                        <td>
                                            <span class="utilization-badge <?php echo $utilizationClass; ?>">
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
        <aside class="sidebar-panel">
            <!-- High-Risk Accounts Alert -->
            <?php if (!empty($highRiskAccounts)): ?>
                <div class="sidebar-widget">
                    <div class="widget-header">
                        <h4>⚠️ High-Risk Accounts</h4>
                    </div>
                    <div class="widget-content">
                        <div class="risk-alert">
                            <div class="risk-alert-title">Credit Limit Warning</div>
                            <div class="risk-alert-text">
                                <?php echo count($highRiskAccounts); ?> customer(s) have exceeded 80% of their credit limit.
                            </div>
                        </div>
                        <?php foreach (array_slice($highRiskAccounts, 0, 3) as $riskCustomer): ?>
                            <div class="receipt-item">
                                <div class="receipt-header">
                                    <span class="customer-name"><?php echo htmlspecialchars($riskCustomer['company_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="utilization-badge status-critical">
                                        <?php echo calculateUtilization($riskCustomer['outstanding_balance'], $riskCustomer['credit_limit']); ?>%
                                    </span>
                                </div>
                                <div class="receipt-details">
                                    Balance: <?php echo formatCurrency($riskCustomer['outstanding_balance']); ?> / 
                                    Limit: <?php echo formatCurrency($riskCustomer['credit_limit']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recent Payment Receipts -->
            <div class="sidebar-widget">
                <div class="widget-header">
                    <h4>📝 Recent Payments</h4>
                </div>
                <div class="widget-content">
                    <?php if (empty($recentReceipts)): ?>
                        <div class="empty-state">
                            <p>No payment receipts yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="receipt-list">
                            <?php foreach ($recentReceipts as $receipt): ?>
                                <div class="receipt-item">
                                    <div class="receipt-header">
                                        <span class="receipt-number"><?php echo htmlspecialchars($receipt['receipt_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="receipt-amount"><?php echo formatCurrency((float)$receipt['amount_paid']); ?></span>
                                    </div>
                                    <div class="receipt-details">
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

    <!-- Payment Processing Modal -->
    <div class="modal-overlay" id="paymentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Record Customer Payment</h3>
                <button type="button" class="modal-close" id="closeModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Customer Information Display -->
                <div class="customer-info-box" id="customerInfoBox">
                    <div class="customer-info-row">
                        <span class="customer-info-label">Customer:</span>
                        <span class="customer-info-value" id="modalCustomerName">-</span>
                    </div>
                    <div class="customer-info-row">
                        <span class="customer-info-label">Customer Code:</span>
                        <span class="customer-info-value" id="modalCustomerCode">-</span>
                    </div>
                    <div class="customer-info-row">
                        <span class="customer-info-label">Outstanding Balance:</span>
                        <span class="customer-info-value" id="modalOutstandingBalance">$0.00</span>
                    </div>
                    <div class="customer-info-row">
                        <span class="customer-info-label">Credit Limit:</span>
                        <span class="customer-info-value" id="modalCreditLimit">$0.00</span>
                    </div>
                </div>

                <!-- Payment Form -->
                <form id="paymentForm" method="POST" action="<?php echo BASE_URL; ?>/customers/process-payment">
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
</body>
</html>
<?php
// Made with Bob
?>