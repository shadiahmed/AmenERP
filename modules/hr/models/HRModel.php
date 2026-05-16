<?php
declare(strict_types=1);

/**
 * AmenERP HR & Payroll Model
 * 
 * Comprehensive business logic for HR and Payroll management with full
 * transactional integrity and financial system integration.
 * 
 * @package AmenERP
 * @subpackage HR
 * @author Principal Software Engineer
 * @version 1.0.0
 * @created 2026-05-16
 */

require_once __DIR__ . '/../../../core/Database.php';

class HRModel
{
    /**
     * Database connection instance
     * 
     * @var PDO
     */
    private PDO $db;

    /**
     * Corporate account constants for financial integration
     * These map to the accounts table in the finance module
     */
    private const PAYROLL_EXPENSE_ACCOUNT_ID = 4;  // Operating Expense account
    private const CASH_ACCOUNT_ID = 1;              // Primary Cash Liquid Asset account

    /**
     * Initialize HR Model with database connection
     * 
     * @throws PDOException If database connection fails
     */
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Process monthly payroll for all active employees
     * 
     * This method handles the complete payroll cycle including:
     * - Validation to prevent duplicate processing
     * - Master payroll run creation
     * - Individual employee payroll calculations
     * - Financial transaction recording
     * - General ledger entries (double-entry bookkeeping)
     * - Account balance updates
     * 
     * All operations are wrapped in a database transaction to ensure
     * ACID compliance and data integrity.
     * 
     * @param string $payrollMonth Payroll period in YYYY-MM format (e.g., '2026-05')
     * @param array $adjustments Optional employee-specific adjustments
     *                          Format: [employee_id => ['allowances' => float, 'deductions' => float]]
     * 
     * @return array Result array containing:
     *               - success: bool
     *               - message: string
     *               - payroll_run_id: int (on success)
     *               - total_net_paid: float (on success)
     *               - employees_processed: int (on success)
     * 
     * @throws PDOException On database errors
     * @throws Exception On validation or business logic errors
     */
    public function processMonthlyPayroll(string $payrollMonth, array $adjustments = []): array
    {
        try {
            // Begin transaction for atomic operations
            $this->db->beginTransaction();

            // Step 1: Validate payroll month format
            if (!preg_match('/^\d{4}-\d{2}$/', $payrollMonth)) {
                throw new Exception('Invalid payroll month format. Expected YYYY-MM (e.g., 2026-05)');
            }

            // Step 2: Check if payroll for this month has already been processed
            $checkStmt = $this->db->prepare(
                "SELECT id FROM payroll_runs WHERE payroll_month = :payroll_month LIMIT 1"
            );
            $checkStmt->execute([':payroll_month' => $payrollMonth]);
            
            if ($checkStmt->fetch()) {
                throw new Exception("Payroll for {$payrollMonth} has already been processed. Cannot process duplicate payroll.");
            }

            // Step 3: Fetch all active employees
            $employeesStmt = $this->db->prepare(
                "SELECT id, employee_code, full_name, monthly_base_salary 
                 FROM employees 
                 WHERE status = 'active' 
                 ORDER BY employee_code ASC"
            );
            $employeesStmt->execute();
            $employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($employees)) {
                throw new Exception('No active employees found to process payroll.');
            }

            // Step 4: Create master payroll run record (will update total_gross later)
            $insertRunStmt = $this->db->prepare(
                "INSERT INTO payroll_runs (payroll_month, total_gross, processed_at) 
                 VALUES (:payroll_month, 0.00, NOW())"
            );
            $insertRunStmt->execute([':payroll_month' => $payrollMonth]);
            $payrollRunId = (int)$this->db->lastInsertId();

            // Step 5: Process each employee and calculate payroll
            $totalNetPaid = 0.00;
            $employeesProcessed = 0;
            
            $insertDetailStmt = $this->db->prepare(
                "INSERT INTO payroll_details 
                 (payroll_run_id, employee_id, base_salary, allowances, deductions, net_paid, created_at) 
                 VALUES (:payroll_run_id, :employee_id, :base_salary, :allowances, :deductions, :net_paid, NOW())"
            );

            foreach ($employees as $employee) {
                $employeeId = (int)$employee['id'];
                $baseSalary = (float)$employee['monthly_base_salary'];
                
                // Get employee-specific adjustments if provided
                $allowances = 0.00;
                $deductions = 0.00;
                
                if (isset($adjustments[$employeeId])) {
                    $allowances = (float)($adjustments[$employeeId]['allowances'] ?? 0.00);
                    $deductions = (float)($adjustments[$employeeId]['deductions'] ?? 0.00);
                }

                // Calculate net pay: base + allowances - deductions
                $netPaid = $baseSalary + $allowances - $deductions;
                
                // Ensure net paid is not negative
                if ($netPaid < 0) {
                    throw new Exception(
                        "Net pay cannot be negative for employee {$employee['employee_code']} ({$employee['full_name']}). " .
                        "Base: {$baseSalary}, Allowances: {$allowances}, Deductions: {$deductions}"
                    );
                }

                // Insert payroll detail record
                $insertDetailStmt->execute([
                    ':payroll_run_id' => $payrollRunId,
                    ':employee_id' => $employeeId,
                    ':base_salary' => $baseSalary,
                    ':allowances' => $allowances,
                    ':deductions' => $deductions,
                    ':net_paid' => $netPaid
                ]);

                $totalNetPaid += $netPaid;
                $employeesProcessed++;
            }

            // Step 6: Update payroll run with total gross amount
            $updateRunStmt = $this->db->prepare(
                "UPDATE payroll_runs SET total_gross = :total_gross WHERE id = :id"
            );
            $updateRunStmt->execute([
                ':total_gross' => $totalNetPaid,
                ':id' => $payrollRunId
            ]);

            // Step 7: Create financial transaction record
            $transactionDescription = "Payroll disbursement for {$payrollMonth} - {$employeesProcessed} employees";
            
            $insertTransactionStmt = $this->db->prepare(
                "INSERT INTO transactions 
                 (description, transaction_date, created_at) 
                 VALUES (:description, NOW(), NOW())"
            );
            $insertTransactionStmt->execute([
                ':description' => $transactionDescription
            ]);
            $transactionId = (int)$this->db->lastInsertId();

            // Step 8: Create general ledger entries (double-entry bookkeeping)
            // Note: Positive amount = Debit (increase for expenses), Negative amount = Credit
            
            // DEBIT: Payroll Expense Account (increase expense)
            $insertLedgerStmt = $this->db->prepare(
                "INSERT INTO ledger_entries 
                 (transaction_id, account_id, amount, created_at) 
                 VALUES (:transaction_id, :account_id, :amount, NOW())"
            );
            
            $insertLedgerStmt->execute([
                ':transaction_id' => $transactionId,
                ':account_id' => self::PAYROLL_EXPENSE_ACCOUNT_ID,
                ':amount' => $totalNetPaid
            ]);

            // CREDIT: Cash Account (decrease cash)
            $insertLedgerStmt->execute([
                ':transaction_id' => $transactionId,
                ':account_id' => self::CASH_ACCOUNT_ID,
                ':amount' => -$totalNetPaid
            ]);

            // Step 9: Update account balances dynamically
            
            // Update Payroll Expense Account (increase balance for expense account)
            $updateAccountStmt = $this->db->prepare(
                "UPDATE accounts 
                 SET balance = balance + :amount, 
                     updated_at = NOW() 
                 WHERE id = :account_id"
            );
            $updateAccountStmt->execute([
                ':amount' => $totalNetPaid,
                ':account_id' => self::PAYROLL_EXPENSE_ACCOUNT_ID
            ]);

            // Update Cash Account (decrease balance for asset account)
            $updateAccountStmt->execute([
                ':amount' => -$totalNetPaid,
                ':account_id' => self::CASH_ACCOUNT_ID
            ]);

            // Commit transaction - all operations successful
            $this->db->commit();

            return [
                'success' => true,
                'message' => "Payroll for {$payrollMonth} processed successfully.",
                'payroll_run_id' => $payrollRunId,
                'total_net_paid' => $totalNetPaid,
                'employees_processed' => $employeesProcessed,
                'transaction_id' => $transactionId
            ];

        } catch (Exception $e) {
            // Rollback transaction on any error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return [
                'success' => false,
                'message' => 'Payroll processing failed: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }

    /**
     * Get all employees with their details
     * 
     * @param string|null $status Filter by status ('active', 'inactive', or null for all)
     * @param string $orderBy Column to order by (default: 'employee_code')
     * @param string $orderDir Order direction ('ASC' or 'DESC')
     * 
     * @return array Array of employee records
     */
    public function getAllEmployees(?string $status = null, string $orderBy = 'employee_code', string $orderDir = 'ASC'): array
    {
        try {
            // Validate order direction
            $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
            
            // Validate order by column
            $allowedColumns = ['id', 'employee_code', 'full_name', 'role_title', 'monthly_base_salary', 'status', 'created_at'];
            if (!in_array($orderBy, $allowedColumns)) {
                $orderBy = 'employee_code';
            }

            $sql = "SELECT 
                        id,
                        employee_code,
                        full_name,
                        role_title,
                        monthly_base_salary,
                        status,
                        created_at,
                        updated_at
                    FROM employees";

            $params = [];

            if ($status !== null) {
                $sql .= " WHERE status = :status";
                $params[':status'] = $status;
            }

            $sql .= " ORDER BY {$orderBy} {$orderDir}";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error fetching employees: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get HR statistics and metrics
     * 
     * Provides comprehensive overview of HR data including:
     * - Total employee counts by status
     * - Total monthly payroll obligation
     * - Average salary
     * - Recent payroll runs
     * 
     * @return array Associative array of HR statistics
     */
    public function getHRStats(): array
    {
        try {
            $stats = [];

            // Get employee counts by status
            $countStmt = $this->db->query(
                "SELECT 
                    COUNT(*) as total_employees,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_employees,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_employees
                 FROM employees"
            );
            $counts = $countStmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_employees'] = (int)$counts['total_employees'];
            $stats['active_employees'] = (int)$counts['active_employees'];
            $stats['inactive_employees'] = (int)$counts['inactive_employees'];

            // Get salary statistics for active employees
            $salaryStmt = $this->db->query(
                "SELECT 
                    SUM(monthly_base_salary) as total_monthly_payroll,
                    AVG(monthly_base_salary) as average_salary,
                    MIN(monthly_base_salary) as min_salary,
                    MAX(monthly_base_salary) as max_salary
                 FROM employees 
                 WHERE status = 'active'"
            );
            $salaryStats = $salaryStmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_monthly_payroll'] = (float)($salaryStats['total_monthly_payroll'] ?? 0.00);
            $stats['average_salary'] = (float)($salaryStats['average_salary'] ?? 0.00);
            $stats['min_salary'] = (float)($salaryStats['min_salary'] ?? 0.00);
            $stats['max_salary'] = (float)($salaryStats['max_salary'] ?? 0.00);

            // Get total payroll runs count
            $payrollCountStmt = $this->db->query(
                "SELECT COUNT(*) as total_payroll_runs FROM payroll_runs"
            );
            $payrollCount = $payrollCountStmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_payroll_runs'] = (int)$payrollCount['total_payroll_runs'];

            // Get most recent payroll run
            $recentPayrollStmt = $this->db->query(
                "SELECT 
                    payroll_month,
                    total_gross,
                    processed_at
                 FROM payroll_runs 
                 ORDER BY processed_at DESC 
                 LIMIT 1"
            );
            $recentPayroll = $recentPayrollStmt->fetch(PDO::FETCH_ASSOC);
            $stats['last_payroll_month'] = $recentPayroll['payroll_month'] ?? null;
            $stats['last_payroll_amount'] = (float)($recentPayroll['total_gross'] ?? 0.00);
            $stats['last_payroll_date'] = $recentPayroll['processed_at'] ?? null;

            return $stats;

        } catch (PDOException $e) {
            error_log("Error fetching HR stats: " . $e->getMessage());
            return [
                'total_employees' => 0,
                'active_employees' => 0,
                'inactive_employees' => 0,
                'total_monthly_payroll' => 0.00,
                'average_salary' => 0.00,
                'min_salary' => 0.00,
                'max_salary' => 0.00,
                'total_payroll_runs' => 0,
                'last_payroll_month' => null,
                'last_payroll_amount' => 0.00,
                'last_payroll_date' => null
            ];
        }
    }

    /**
     * Get payroll history with optional filtering
     * 
     * @param int $limit Maximum number of records to return (default: 50)
     * @param int $offset Offset for pagination (default: 0)
     * @param string|null $monthFilter Optional filter by specific month (YYYY-MM format)
     * 
     * @return array Array of payroll run records with employee details
     */
    public function getPayrollHistory(int $limit = 50, int $offset = 0, ?string $monthFilter = null): array
    {
        try {
            $sql = "SELECT 
                        pr.id,
                        pr.payroll_month,
                        pr.total_gross,
                        pr.processed_at,
                        COUNT(pd.id) as employee_count,
                        SUM(pd.allowances) as total_allowances,
                        SUM(pd.deductions) as total_deductions,
                        SUM(pd.net_paid) as total_net_paid
                    FROM payroll_runs pr
                    LEFT JOIN payroll_details pd ON pr.id = pd.payroll_run_id";

            $params = [];

            if ($monthFilter !== null) {
                $sql .= " WHERE pr.payroll_month = :month_filter";
                $params[':month_filter'] = $monthFilter;
            }

            $sql .= " GROUP BY pr.id, pr.payroll_month, pr.total_gross, pr.processed_at
                      ORDER BY pr.processed_at DESC
                      LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error fetching payroll history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get detailed payroll information for a specific run
     * 
     * @param int $payrollRunId The payroll run ID
     * 
     * @return array|null Payroll run details with employee breakdown, or null if not found
     */
    public function getPayrollRunDetails(int $payrollRunId): ?array
    {
        try {
            // Get payroll run master record
            $runStmt = $this->db->prepare(
                "SELECT 
                    id,
                    payroll_month,
                    total_gross,
                    processed_at
                 FROM payroll_runs 
                 WHERE id = :id"
            );
            $runStmt->execute([':id' => $payrollRunId]);
            $run = $runStmt->fetch(PDO::FETCH_ASSOC);

            if (!$run) {
                return null;
            }

            // Get employee details for this payroll run
            $detailsStmt = $this->db->prepare(
                "SELECT 
                    pd.id,
                    pd.employee_id,
                    e.employee_code,
                    e.full_name,
                    e.role_title,
                    pd.base_salary,
                    pd.allowances,
                    pd.deductions,
                    pd.net_paid,
                    pd.created_at
                 FROM payroll_details pd
                 INNER JOIN employees e ON pd.employee_id = e.id
                 WHERE pd.payroll_run_id = :payroll_run_id
                 ORDER BY e.employee_code ASC"
            );
            $detailsStmt->execute([':payroll_run_id' => $payrollRunId]);
            $details = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);

            $run['employee_details'] = $details;
            $run['employee_count'] = count($details);

            return $run;

        } catch (PDOException $e) {
            error_log("Error fetching payroll run details: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get employee by ID
     * 
     * @param int $employeeId The employee ID
     * 
     * @return array|null Employee record or null if not found
     */
    public function getEmployeeById(int $employeeId): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT 
                    id,
                    employee_code,
                    full_name,
                    role_title,
                    monthly_base_salary,
                    status,
                    created_at,
                    updated_at
                 FROM employees 
                 WHERE id = :id"
            );
            $stmt->execute([':id' => $employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            return $employee ?: null;

        } catch (PDOException $e) {
            error_log("Error fetching employee: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get employee by employee code
     * 
     * @param string $employeeCode The employee code (e.g., 'EMP-001')
     * 
     * @return array|null Employee record or null if not found
     */
    public function getEmployeeByCode(string $employeeCode): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT 
                    id,
                    employee_code,
                    full_name,
                    role_title,
                    monthly_base_salary,
                    status,
                    created_at,
                    updated_at
                 FROM employees 
                 WHERE employee_code = :employee_code"
            );
            $stmt->execute([':employee_code' => $employeeCode]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            return $employee ?: null;

        } catch (PDOException $e) {
            error_log("Error fetching employee by code: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Add a new employee
     * 
     * @param array $employeeData Employee data array with keys:
     *                           - employee_code (required)
     *                           - full_name (required)
     *                           - role_title (required)
     *                           - monthly_base_salary (required)
     *                           - status (optional, default: 'active')
     * 
     * @return array Result array with success status and employee_id or error message
     */
    public function addEmployee(array $employeeData): array
    {
        try {
            // Validate required fields
            $requiredFields = ['employee_code', 'full_name', 'role_title', 'monthly_base_salary'];
            foreach ($requiredFields as $field) {
                if (!isset($employeeData[$field]) || trim($employeeData[$field]) === '') {
                    return [
                        'success' => false,
                        'message' => "Missing required field: {$field}"
                    ];
                }
            }

            // Check if employee code already exists
            $checkStmt = $this->db->prepare(
                "SELECT id FROM employees WHERE employee_code = :employee_code LIMIT 1"
            );
            $checkStmt->execute([':employee_code' => $employeeData['employee_code']]);
            
            if ($checkStmt->fetch()) {
                return [
                    'success' => false,
                    'message' => "Employee code '{$employeeData['employee_code']}' already exists."
                ];
            }

            // Insert new employee
            $stmt = $this->db->prepare(
                "INSERT INTO employees 
                 (employee_code, full_name, role_title, monthly_base_salary, status, created_at, updated_at) 
                 VALUES (:employee_code, :full_name, :role_title, :monthly_base_salary, :status, NOW(), NOW())"
            );

            $stmt->execute([
                ':employee_code' => trim($employeeData['employee_code']),
                ':full_name' => trim($employeeData['full_name']),
                ':role_title' => trim($employeeData['role_title']),
                ':monthly_base_salary' => (float)$employeeData['monthly_base_salary'],
                ':status' => $employeeData['status'] ?? 'active'
            ]);

            $employeeId = (int)$this->db->lastInsertId();

            return [
                'success' => true,
                'message' => 'Employee added successfully.',
                'employee_id' => $employeeId
            ];

        } catch (PDOException $e) {
            error_log("Error adding employee: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update an existing employee
     * 
     * @param int $employeeId The employee ID to update
     * @param array $employeeData Employee data to update (only provided fields will be updated)
     * 
     * @return array Result array with success status and message
     */
    public function updateEmployee(int $employeeId, array $employeeData): array
    {
        try {
            // Check if employee exists
            $employee = $this->getEmployeeById($employeeId);
            if (!$employee) {
                return [
                    'success' => false,
                    'message' => 'Employee not found.'
                ];
            }

            // Build dynamic update query
            $updateFields = [];
            $params = [':id' => $employeeId];

            $allowedFields = ['employee_code', 'full_name', 'role_title', 'monthly_base_salary', 'status'];
            
            foreach ($allowedFields as $field) {
                if (isset($employeeData[$field])) {
                    $updateFields[] = "{$field} = :{$field}";
                    $params[":{$field}"] = $employeeData[$field];
                }
            }

            if (empty($updateFields)) {
                return [
                    'success' => false,
                    'message' => 'No valid fields provided for update.'
                ];
            }

            // Check for duplicate employee code if being updated
            if (isset($employeeData['employee_code'])) {
                $checkStmt = $this->db->prepare(
                    "SELECT id FROM employees WHERE employee_code = :employee_code AND id != :id LIMIT 1"
                );
                $checkStmt->execute([
                    ':employee_code' => $employeeData['employee_code'],
                    ':id' => $employeeId
                ]);
                
                if ($checkStmt->fetch()) {
                    return [
                        'success' => false,
                        'message' => "Employee code '{$employeeData['employee_code']}' already exists."
                    ];
                }
            }

            $sql = "UPDATE employees SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return [
                'success' => true,
                'message' => 'Employee updated successfully.'
            ];

        } catch (PDOException $e) {
            error_log("Error updating employee: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete an employee (soft delete by setting status to inactive)
     * 
     * Note: Employees with payroll history cannot be hard deleted due to
     * RESTRICT foreign key constraint. This method sets status to 'inactive'.
     * 
     * @param int $employeeId The employee ID to delete
     * 
     * @return array Result array with success status and message
     */
    public function deleteEmployee(int $employeeId): array
    {
        try {
            // Check if employee exists
            $employee = $this->getEmployeeById($employeeId);
            if (!$employee) {
                return [
                    'success' => false,
                    'message' => 'Employee not found.'
                ];
            }

            // Soft delete by setting status to inactive
            $stmt = $this->db->prepare(
                "UPDATE employees SET status = 'inactive', updated_at = NOW() WHERE id = :id"
            );
            $stmt->execute([':id' => $employeeId]);

            return [
                'success' => true,
                'message' => 'Employee deactivated successfully.'
            ];

        } catch (PDOException $e) {
            error_log("Error deleting employee: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
}

// Made with Bob
