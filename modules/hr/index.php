<?php
declare(strict_types=1);

/**
 * AmenERP HR & Payroll - Administration Dashboard
 * 
 * Primary interface for managing employees and processing monthly payroll.
 * Features real-time calculation of net pay with dynamic JavaScript updates.
 * 
 * @package AmenERP
 * @subpackage HR
 * @author Senior UI Engineer
 * @version 1.0.0
 * @created 2026-05-16
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
        <!-- Header -->
        <header class="erp-header">
            <h1>HR & Payroll Management</h1>
            <p>Manage employees and process monthly payroll disbursements</p>
        </header>

        <!-- Alert Messages -->
        <?php if ($successMessage): ?>
            <div class="erp-alert erp-alert--success" role="alert">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="erp-alert erp-alert--error" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Grid -->
        <section class="erp-grid" aria-label="HR Statistics">
            <div class="erp-card erp-stat-card">
                <div class="erp-stat-card__value"><?= number_format($hrStats['active_employees']) ?></div>
                <div class="erp-stat-card__label">Active Employees</div>
            </div>

            <div class="erp-card erp-stat-card">
                <div class="erp-stat-card__value">$<?= number_format($hrStats['total_monthly_payroll'], 2) ?></div>
                <div class="erp-stat-card__label">Monthly Payroll</div>
            </div>

            <div class="erp-card erp-stat-card">
                <div class="erp-stat-card__value">$<?= number_format($hrStats['average_salary'], 2) ?></div>
                <div class="erp-stat-card__label">Average Salary</div>
            </div>

            <div class="erp-card erp-stat-card">
                <div class="erp-stat-card__value"><?= number_format($hrStats['total_payroll_runs']) ?></div>
                <div class="erp-stat-card__label">Payroll Runs</div>
            </div>
        </section>

        <!-- Main Content Grid -->
        <div class="erp-main-grid <?= empty($payrollHistory) ? 'erp-main-grid--single' : '' ?>">
            
            <!-- Payroll Processing Card -->
            <section class="erp-card">
                <div class="erp-card__header">
                    <h2>Process Monthly Payroll</h2>
                    <p>Review employee salaries and apply adjustments before processing</p>
                </div>

                <div class="erp-card__body">
                    <?php if (empty($employees)): ?>
                        <p class="erp-empty">
                            No active employees found. Please add employees before processing payroll.
                        </p>
                    <?php else: ?>
                        <form method="POST" action="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/hr/payroll/process" id="payrollForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                            <!-- Payroll Month Selection -->
                            <div class="erp-form__group">
                                <label for="payroll_month" class="erp-form__label">Payroll Month</label>
                                <input 
                                    type="month" 
                                    id="payroll_month" 
                                    name="payroll_month" 
                                    class="erp-form__input" 
                                    value="<?= htmlspecialchars($currentMonth) ?>"
                                    required
                                >
                            </div>

                            <!-- Employee Payroll Table -->
                            <div class="erp-table__wrapper">
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
                                            <tr data-employee-id="<?= $employee['id'] ?>" data-base-salary="<?= $employee['monthly_base_salary'] ?>">
                                                <td><?= htmlspecialchars($employee['employee_code']) ?></td>
                                                <td><?= htmlspecialchars($employee['full_name']) ?></td>
                                                <td>$<?= number_format((float)$employee['monthly_base_salary'], 2) ?></td>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        name="allowances[<?= $employee['id'] ?>]" 
                                                        class="erp-table__input allowance-input" 
                                                        value="0.00" 
                                                        step="0.01" 
                                                        min="0"
                                                        data-employee-id="<?= $employee['id'] ?>"
                                                    >
                                                </td>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        name="deductions[<?= $employee['id'] ?>]" 
                                                        class="erp-table__input deduction-input" 
                                                        value="0.00" 
                                                        step="0.01" 
                                                        min="0"
                                                        data-employee-id="<?= $employee['id'] ?>"
                                                    >
                                                </td>
                                                <td class="erp-table__net" data-net-pay="<?= $employee['id'] ?>">
                                                    $<?= number_format((float)$employee['monthly_base_salary'], 2) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Total Summary -->
                            <div class="erp-summary">
                                <div class="erp-summary__label">Total Company Payout</div>
                                <div class="erp-summary__value" id="totalPayout">
                                    $<?= number_format($hrStats['total_monthly_payroll'], 2) ?>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="erp-btn erp-btn--primary erp-btn--block">
                                Process Payroll
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Payroll History Card -->
            <?php if (!empty($payrollHistory)): ?>
                <aside class="erp-card">
                    <div class="erp-card__header">
                        <h2>Recent Payroll History</h2>
                        <p>Last 10 payroll runs</p>
                    </div>

                    <div class="erp-card__body">
                        <div class="erp-history">
                            <?php foreach ($payrollHistory as $history): ?>
                                <div class="erp-history__item">
                                    <div>
                                        <div class="erp-history__month">
                                            <?= htmlspecialchars($history['payroll_month']) ?>
                                        </div>
                                        <div class="erp-history__meta">
                                            <?= $history['employee_count'] ?> employees • 
                                            <?= date('M d, Y', strtotime($history['processed_at'])) ?>
                                        </div>
                                    </div>
                                    <div class="erp-history__amount">
                                        $<?= number_format((float)$history['total_net_paid'], 2) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </aside>
            <?php endif; ?>
        </div>
    </div>

<?php
// Made with Bob
?>
