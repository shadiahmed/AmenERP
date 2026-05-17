<?php
declare(strict_types=1);

/**
 * AmenERP HR & Payroll - Administration Dashboard
 * 
 * Primary interface for managing employees and processing monthly payroll.
 * Features real-time calculation of net pay with dynamic JavaScript updates.
 * Fully mobile-responsive with centralized CSS architecture integration.
 * 
 * Features:
 * - Employee payroll matrix with real-time calculations
 * - Monthly payroll processing with confirmation
 * - Allowances and deductions management
 * - Payroll history tracking
 * - HR statistics dashboard
 * - Touch-optimized mobile interface
 * 
 * @package AmenERP\Modules\HR
 * @author Bob - Expert Principal Frontend Engineer
 * @version 2.0.0
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/models/HRModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize HR Model
$hrModel = new HRModel();

// Load data for dashboard
$employees = $hrModel->getAllEmployees('active');
$hrStats = $hrModel->getHRStats();
$payrollHistory = $hrModel->getPayrollHistory(10);

// Generate CSRF token for form
$csrfToken = Csrf::generateToken();

// Get current month for default payroll period
$currentMonth = date('Y-m');

// Extract session messages
$successMessage = $_SESSION['success'] ?? null;
$errorMessage = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="erp-container">
    <!-- Page Header -->
    <header class="erp-header">
        <h1>👥 HR & Payroll Management</h1>
        <p>Manage employees and process monthly payroll disbursements</p>
    </header>

    <!-- Alert Messages -->
    <?php if ($successMessage): ?>
        <div class="erp-alert erp-alert-success" role="alert">
            ✓ <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="erp-alert erp-alert-error" role="alert">
            ✗ <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Grid -->
    <section class="erp-stats-grid" aria-label="HR Statistics">
        <div class="erp-card erp-stat-card">
            <div class="erp-stat-value"><?php echo number_format($hrStats['active_employees']); ?></div>
            <div class="erp-stat-label">Active Employees</div>
        </div>

        <div class="erp-card erp-stat-card">
            <div class="erp-stat-value">$<?php echo number_format($hrStats['total_monthly_payroll'], 2); ?></div>
            <div class="erp-stat-label">Monthly Payroll</div>
        </div>

        <div class="erp-card erp-stat-card">
            <div class="erp-stat-value">$<?php echo number_format($hrStats['average_salary'], 2); ?></div>
            <div class="erp-stat-label">Average Salary</div>
        </div>

        <div class="erp-card erp-stat-card">
            <div class="erp-stat-value"><?php echo number_format($hrStats['total_payroll_runs']); ?></div>
            <div class="erp-stat-label">Payroll Runs</div>
        </div>
    </section>

    <!-- Main Content Grid -->
    <div class="erp-grid">
        
        <!-- Payroll Processing Card -->
        <section class="erp-card">
            <div class="erp-card-header">
                <h2>Process Monthly Payroll</h2>
                <p>Review employee salaries and apply adjustments before processing</p>
            </div>

            <div class="erp-card-body">
                <?php if (empty($employees)): ?>
                    <div class="erp-empty-state">
                        <p>No active employees found. Please add employees before processing payroll.</p>
                    </div>
                <?php else: ?>
                    <form method="POST" action="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/hr/payroll/process" id="payrollForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                        <!-- Payroll Month Selection -->
                        <div class="erp-form-group">
                            <label for="payroll_month" class="erp-form-label">Payroll Month *</label>
                            <input 
                                type="month" 
                                id="payroll_month" 
                                name="payroll_month" 
                                class="erp-form-input" 
                                value="<?php echo htmlspecialchars($currentMonth, ENT_QUOTES, 'UTF-8'); ?>"
                                required
                            >
                        </div>

                        <!-- Employee Payroll Table -->
                        <div class="erp-table-container">
                            <table class="erp-table" id="payrollTable">
                                <thead>
                                    <tr>
                                        <th>Employee Code</th>
                                        <th>Full Name</th>
                                        <th>Base Salary</th>
                                        <th>Allowances</th>
                                        <th>Deductions</th>
                                        <th>Net Pay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $employee): ?>
                                        <tr data-employee-id="<?php echo (int) $employee['id']; ?>" data-base-salary="<?php echo (float) $employee['monthly_base_salary']; ?>">
                                            <td><?php echo htmlspecialchars($employee['employee_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($employee['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>$<?php echo number_format((float)$employee['monthly_base_salary'], 2); ?></td>
                                            <td>
                                                <input 
                                                    type="number" 
                                                    name="allowances[<?php echo (int) $employee['id']; ?>]" 
                                                    class="erp-form-input erp-table-input allowance-input" 
                                                    value="0.00" 
                                                    step="0.01" 
                                                    min="0"
                                                    data-employee-id="<?php echo (int) $employee['id']; ?>"
                                                >
                                            </td>
                                            <td>
                                                <input 
                                                    type="number" 
                                                    name="deductions[<?php echo (int) $employee['id']; ?>]" 
                                                    class="erp-form-input erp-table-input deduction-input" 
                                                    value="0.00" 
                                                    step="0.01" 
                                                    min="0"
                                                    data-employee-id="<?php echo (int) $employee['id']; ?>"
                                                >
                                            </td>
                                            <td class="erp-table-net" data-net-pay="<?php echo (int) $employee['id']; ?>">
                                                $<?php echo number_format((float)$employee['monthly_base_salary'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Total Summary -->
                        <div class="erp-summary">
                            <div class="erp-summary-label">Total Company Payout</div>
                            <div class="erp-summary-value" id="totalPayout">
                                $<?php echo number_format($hrStats['total_monthly_payroll'], 2); ?>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="erp-btn erp-btn-primary erp-btn-block">
                            💰 Process Payroll
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </section>

        <!-- Payroll History Card -->
        <?php if (!empty($payrollHistory)): ?>
            <aside class="erp-card">
                <div class="erp-card-header">
                    <h2>Recent Payroll History</h2>
                    <p>Last 10 payroll runs</p>
                </div>

                <div class="erp-card-body">
                    <div class="erp-history">
                        <?php foreach ($payrollHistory as $history): ?>
                            <div class="erp-history-item">
                                <div class="erp-history-content">
                                    <div class="erp-history-month">
                                        <?php echo htmlspecialchars($history['payroll_month'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="erp-history-meta">
                                        <?php echo (int) $history['employee_count']; ?> employees • 
                                        <?php echo date('M d, Y', strtotime($history['processed_at'])); ?>
                                    </div>
                                </div>
                                <div class="erp-history-amount">
                                    $<?php echo number_format((float)$history['total_net_paid'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
        <?php endif; ?>
    </div>
</div>

<!-- External JavaScript Module -->
<script type="module" src="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/js/hr-payroll.js"></script>

<?php
// Made with Bob
?>
