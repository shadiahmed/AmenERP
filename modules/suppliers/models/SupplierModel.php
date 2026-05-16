<?php

declare(strict_types=1);

/**
 * SupplierModel Class
 * 
 * Handles all database operations for the supplier accounts and accounts payable module.
 * Implements ACID-compliant transactions for vendor payment processing with automated
 * integration across multiple modules:
 * - Updates supplier outstanding balances (what we OWE them)
 * - Records payment disbursements
 * - Creates financial transactions (double-entry bookkeeping)
 * - Updates account balances in the general ledger
 * 
 * This is the mirror opposite of CustomerModel:
 * - CustomerModel tracks what customers OWE US (Accounts Receivable - Asset)
 * - SupplierModel tracks what WE OWE suppliers (Accounts Payable - Liability)
 * 
 * All operations are wrapped in database transactions to ensure data integrity.
 * If any step fails, the entire operation is rolled back.
 * 
 * @package AmenERP\Modules\Suppliers
 * @author Bob
 * @version 1.0.0
 */
class SupplierModel
{
    /**
     * Database instance
     * 
     * @var Database
     */
    private Database $db;

    /**
     * Cash Safe Account ID (Primary Liquid Asset)
     * This account tracks our cash on hand
     * When we pay suppliers, this account DECREASES (credit)
     * 
     * @var int
     */
    private const CASH_SAFE_ACCOUNT_ID = 1;

    /**
     * Accounts Payable Liability Account ID
     * This account tracks money we OWE to suppliers
     * When we pay suppliers, this account DECREASES (debit)
     *
     * @var int
     */
    private const ACCOUNTS_PAYABLE_ACCOUNT_ID = 6;

    /**
     * Inventory/Cost of Goods Sold Account ID
     * This account tracks inventory purchases and cost of goods
     * When we purchase on credit, this account INCREASES (debit)
     *
     * @var int
     */
    private const INVENTORY_COGS_ACCOUNT_ID = 7;

    /**
     * Constructor
     * Initializes database connection using singleton pattern
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Settle a vendor balance with full transactional integrity
     * 
     * This is the core payment processing method that executes a complete
     * vendor payment transaction across multiple modules:
     * 
     * Action A: Validate payment amount and supplier balance
     * Action B: Update supplier outstanding_balance (reduce what we owe)
     * Action C: Insert payment disbursement record for audit trail
     * Action D: Create financial transaction in general ledger
     * Action E: Update account balances (Cash and Accounts Payable)
     * 
     * All operations are wrapped in a database transaction. If any step fails,
     * the entire operation is rolled back to maintain data integrity.
     * 
     * ACCOUNTING LOGIC (Double-Entry):
     * When we pay a supplier $1,000:
     * - DEBIT Accounts Payable $1,000 (decrease liability - what we owe goes down)
     * - CREDIT Cash $1,000 (decrease asset - our cash goes down)
     * 
     * @param int $supplierId Supplier ID receiving the payment
     * @param float $amount Payment amount to disburse (must be positive)
     * @param string $method Payment method (check, wire_transfer, cash, etc.)
     * @param string|null $reference Optional payment reference (check number, wire confirmation, etc.)
     * @param string|null $notes Optional payment notes or remarks
     * @return array<string, mixed> Result array with keys:
     *                              - success: bool
     *                              - disbursement_id: int (if successful)
     *                              - disbursement_number: string (if successful)
     *                              - message: string
     * @throws PDOException If database operation fails
     * 
     * @example
     * $model = new SupplierModel();
     * $result = $model->settleVendorBalance(
     *     supplierId: 1,
     *     amount: 5000.00,
     *     method: 'check',
     *     reference: 'CHK-123456',
     *     notes: 'Payment for Invoice #INV-2026-001'
     * );
     * 
     * if ($result['success']) {
     *     echo "Payment recorded: " . $result['disbursement_number'];
     * } else {
     *     echo "Error: " . $result['message'];
     * }
     */
    public function settleVendorBalance(
        int $supplierId,
        float $amount,
        string $method,
        ?string $reference = null,
        ?string $notes = null
    ): array {
        try {
            // Verify required accounts exist to avoid foreign key violations
            $requiredAccountIds = [self::ACCOUNTS_PAYABLE_ACCOUNT_ID, self::CASH_SAFE_ACCOUNT_ID];
            $placeholders = implode(',', array_fill(0, count($requiredAccountIds), '?'));
            $verifySql = "SELECT id FROM accounts WHERE id IN ($placeholders)";
            $stmt = $this->db->query($verifySql, $requiredAccountIds);
            $found = $stmt->fetchAll();
            if (count($found) !== count($requiredAccountIds)) {
                $missing = array_diff($requiredAccountIds, array_map(fn($r) => (int)$r['id'], $found));
                throw new Exception('Missing required chart of accounts IDs: ' . implode(', ', $missing) . '. Please create these accounts or update the account ID constants.');
            }
            // Start database transaction for ACID compliance
            $this->db->beginTransaction();

            // ACTION A: Validate payment amount
            if ($amount <= 0) {
                throw new Exception('Payment amount must be greater than zero');
            }

            // ACTION A: Get supplier details and validate balance with row lock
            $supplierSql = "
                SELECT 
                    id,
                    supplier_code,
                    supplier_name,
                    outstanding_balance,
                    status
                FROM suppliers
                WHERE id = :supplier_id
                FOR UPDATE
            ";

            $supplierStmt = $this->db->query($supplierSql, ['supplier_id' => $supplierId]);
            $supplier = $supplierStmt->fetch();

            if (!$supplier) {
                throw new Exception("Supplier ID {$supplierId} not found");
            }

            // Validate supplier status
            if ($supplier['status'] !== 'active') {
                throw new Exception(
                    "Cannot process payment: Supplier account is {$supplier['status']}"
                );
            }

            $outstandingBalance = (float) $supplier['outstanding_balance'];

            // Validate payment doesn't exceed outstanding balance
            if ($amount > $outstandingBalance) {
                throw new Exception(
                    "Payment amount ({$amount}) exceeds outstanding balance ({$outstandingBalance}). " .
                    "Maximum payment allowed: {$outstandingBalance}"
                );
            }

            // ACTION B: Update supplier outstanding balance (reduce what we owe)
            $updateSupplierSql = "
                UPDATE suppliers
                SET 
                    outstanding_balance = outstanding_balance - :amount,
                    updated_at = NOW()
                WHERE id = :supplier_id
            ";

            $this->db->query($updateSupplierSql, [
                'amount' => $amount,
                'supplier_id' => $supplierId
            ]);

            // ACTION C: Generate unique disbursement number and insert payment record
            $disbursementNumber = $this->generateDisbursementNumber();

            $disbursementSql = "
                INSERT INTO vendor_payments (
                    supplier_id,
                    disbursement_number,
                    amount_paid,
                    payment_method,
                    payment_reference,
                    notes,
                    processed_at,
                    created_at,
                    updated_at
                ) VALUES (
                    :supplier_id,
                    :disbursement_number,
                    :amount_paid,
                    :payment_method,
                    :payment_reference,
                    :notes,
                    NOW(),
                    NOW(),
                    NOW()
                )
            ";

            $this->db->query($disbursementSql, [
                'supplier_id' => $supplierId,
                'disbursement_number' => $disbursementNumber,
                'amount_paid' => $amount,
                'payment_method' => $method,
                'payment_reference' => $reference,
                'notes' => $notes
            ]);

            $disbursementId = (int) $this->db->lastInsertId();

            // ACTION D: Record financial transaction in general ledger
            // Money flows: DEBIT Accounts Payable (decrease liability), CREDIT Cash (decrease asset)
            $transactionDescription = sprintf(
                "Vendor Payment: %s - %s - Disbursement: %s",
                $supplier['supplier_code'],
                $supplier['supplier_name'],
                $disbursementNumber
            );
            $transactionDate = date('Y-m-d');

            // Insert master transaction record
            $transactionSql = "
                INSERT INTO transactions (
                    description,
                    reference_number,
                    transaction_date,
                    created_at,
                    updated_at
                ) VALUES (
                    :description,
                    :reference_number,
                    :transaction_date,
                    NOW(),
                    NOW()
                )
            ";

            $this->db->query($transactionSql, [
                'description' => $transactionDescription,
                'reference_number' => $disbursementNumber,
                'transaction_date' => $transactionDate
            ]);

            $transactionId = (int) $this->db->lastInsertId();

            // Insert DEBIT entry for Accounts Payable (liability decrease - positive amount)
            $debitPayableSql = "
                INSERT INTO ledger_entries (
                    transaction_id,
                    account_id,
                    amount,
                    created_at,
                    updated_at
                ) VALUES (
                    :transaction_id,
                    :account_id,
                    :amount,
                    NOW(),
                    NOW()
                )
            ";

            $this->db->query($debitPayableSql, [
                'transaction_id' => $transactionId,
                'account_id' => self::ACCOUNTS_PAYABLE_ACCOUNT_ID,
                'amount' => $amount // Positive for debit (liability decrease)
            ]);

            // Insert CREDIT entry for Cash (asset decrease - negative amount)
            $creditCashSql = "
                INSERT INTO ledger_entries (
                    transaction_id,
                    account_id,
                    amount,
                    created_at,
                    updated_at
                ) VALUES (
                    :transaction_id,
                    :account_id,
                    :amount,
                    NOW(),
                    NOW()
                )
            ";

            $this->db->query($creditCashSql, [
                'transaction_id' => $transactionId,
                'account_id' => self::CASH_SAFE_ACCOUNT_ID,
                'amount' => -$amount // Negative for credit (cash decrease)
            ]);

            // ACTION E: Update account balances in the accounts table
            // Update Accounts Payable balance (decrease liability)
            $updatePayableAccountSql = "
                UPDATE accounts
                SET 
                    balance = balance - :amount,
                    updated_at = NOW()
                WHERE id = :account_id
            ";

            $this->db->query($updatePayableAccountSql, [
                'amount' => $amount,
                'account_id' => self::ACCOUNTS_PAYABLE_ACCOUNT_ID
            ]);

            // Update Cash account balance (decrease asset)
            $updateCashAccountSql = "
                UPDATE accounts
                SET 
                    balance = balance - :amount,
                    updated_at = NOW()
                WHERE id = :account_id
            ";

            $this->db->query($updateCashAccountSql, [
                'amount' => $amount,
                'account_id' => self::CASH_SAFE_ACCOUNT_ID
            ]);

            // Commit the transaction - all operations succeeded
            $this->db->commit();

            return [
                'success' => true,
                'disbursement_id' => $disbursementId,
                'disbursement_number' => $disbursementNumber,
                'supplier_code' => $supplier['supplier_code'],
                'supplier_name' => $supplier['supplier_name'],
                'amount_paid' => $amount,
                'new_balance' => $outstandingBalance - $amount,
                'message' => 'Payment processed successfully'
            ];

        } catch (Exception $e) {
            // Rollback on any error to maintain data integrity
            $this->db->rollback();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate a unique disbursement number
     * 
     * Format: DISB-YYYY-NNNN (e.g., DISB-2026-0001)
     * 
     * @return string Unique disbursement number
     * @throws PDOException If query fails
     */
    private function generateDisbursementNumber(): string
    {
        $year = date('Y');
        $prefix = "DISB-{$year}-";

        // Get the highest disbursement number for current year
        $sql = "
            SELECT disbursement_number
            FROM vendor_payments
            WHERE disbursement_number LIKE :prefix
            ORDER BY disbursement_number DESC
            LIMIT 1
        ";

        $stmt = $this->db->query($sql, ['prefix' => $prefix . '%']);
        $result = $stmt->fetch();

        if ($result) {
            // Extract the numeric part and increment
            $lastNumber = (int) substr($result['disbursement_number'], -4);
            $nextNumber = $lastNumber + 1;
        } else {
            // First disbursement of the year
            $nextNumber = 1;
        }

        // Format with leading zeros (4 digits)
        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get all suppliers with their current balances
     * 
     * Returns a list of all suppliers ordered by supplier code.
     * Useful for displaying supplier lists and account summaries.
     * 
     * @param string|null $status Filter by status (active, inactive, suspended) or null for all
     * @return array<int, array<string, mixed>> Array of suppliers
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new SupplierModel();
     * $suppliers = $model->getAllSuppliers('active');
     * foreach ($suppliers as $supplier) {
     *     echo $supplier['supplier_code'] . ': ' . $supplier['supplier_name'];
     * }
     */
    public function getAllSuppliers(?string $status = null): array
    {
        $sql = "
            SELECT 
                id,
                supplier_code,
                supplier_name,
                contact_name,
                contact_email,
                contact_phone,
                address,
                payment_terms,
                credit_limit,
                outstanding_balance,
                status,
                created_at,
                updated_at
            FROM suppliers
        ";

        $params = [];

        if ($status !== null) {
            $sql .= " WHERE status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY supplier_code ASC";

        $stmt = $this->db->query($sql, $params);
        $results = $stmt->fetchAll();

        // Cast numeric values for consistency
        return array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'supplier_code' => $row['supplier_code'],
                'supplier_name' => $row['supplier_name'],
                'contact_name' => $row['contact_name'],
                'contact_email' => $row['contact_email'],
                'contact_phone' => $row['contact_phone'],
                'address' => $row['address'],
                'payment_terms' => $row['payment_terms'],
                'credit_limit' => (float) $row['credit_limit'],
                'outstanding_balance' => (float) $row['outstanding_balance'],
                'credit_available' => (float) $row['credit_limit'] - (float) $row['outstanding_balance'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }, $results);
    }

    /**
     * Get supplier by ID
     * 
     * @param int $supplierId Supplier ID
     * @return array<string, mixed>|null Supplier data or null if not found
     * @throws PDOException If query fails
     */
    public function getSupplierById(int $supplierId): ?array
    {
        $sql = "
            SELECT 
                id,
                supplier_code,
                supplier_name,
                contact_name,
                contact_email,
                contact_phone,
                address,
                payment_terms,
                credit_limit,
                outstanding_balance,
                status,
                created_at,
                updated_at
            FROM suppliers
            WHERE id = :supplier_id
        ";

        $stmt = $this->db->query($sql, ['supplier_id' => $supplierId]);
        $supplier = $stmt->fetch();

        if (!$supplier) {
            return null;
        }

        return [
            'id' => (int) $supplier['id'],
            'supplier_code' => $supplier['supplier_code'],
            'supplier_name' => $supplier['supplier_name'],
            'contact_name' => $supplier['contact_name'],
            'contact_email' => $supplier['contact_email'],
            'contact_phone' => $supplier['contact_phone'],
            'address' => $supplier['address'],
            'payment_terms' => $supplier['payment_terms'],
            'credit_limit' => (float) $supplier['credit_limit'],
            'outstanding_balance' => (float) $supplier['outstanding_balance'],
            'credit_available' => (float) $supplier['credit_limit'] - (float) $supplier['outstanding_balance'],
            'status' => $supplier['status'],
            'created_at' => $supplier['created_at'],
            'updated_at' => $supplier['updated_at']
        ];
    }

    /**
     * Get accounts payable summary statistics
     * 
     * Returns aggregate statistics for all supplier accounts including
     * total payables, total credit available, and supplier counts.
     * 
     * @return array<string, mixed> Payables statistics
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new SupplierModel();
     * $stats = $model->getSupplierSummaryStats();
     * echo "Total Payables: " . $stats['total_payables'];
     * echo "Active Suppliers: " . $stats['active_suppliers'];
     */
    public function getSupplierSummaryStats(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total_suppliers,
                COUNT(CASE WHEN status = 'active' THEN 1 END) AS active_suppliers,
                COALESCE(SUM(credit_limit), 0) AS total_credit_available,
                COALESCE(SUM(outstanding_balance), 0) AS total_payables,
                COALESCE(SUM(credit_limit - outstanding_balance), 0) AS total_credit_remaining
            FROM suppliers
        ";

        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();

        return [
            'total_suppliers' => (int) $result['total_suppliers'],
            'active_suppliers' => (int) $result['active_suppliers'],
            'total_credit_available' => (float) $result['total_credit_available'],
            'total_payables' => (float) $result['total_payables'],
            'total_credit_remaining' => (float) $result['total_credit_remaining'],
            'credit_utilization_percentage' => $result['total_credit_available'] > 0
                ? round(($result['total_payables'] / $result['total_credit_available']) * 100, 2)
                : 0.00
        ];
    }

    /**
     * Get recent payment disbursements
     * 
     * Returns the most recent vendor payments for display in dashboards
     * and activity feeds.
     * 
     * @param int $limit Maximum number of records to return (default: 5)
     * @return array<int, array<string, mixed>> Array of recent disbursements
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new SupplierModel();
     * $recentPayments = $model->getRecentDisbursements(10);
     * foreach ($recentPayments as $payment) {
     *     echo $payment['disbursement_number'] . ': ' . $payment['amount_paid'];
     * }
     */
    public function getRecentDisbursements(int $limit = 5): array
    {
        $sql = "
            SELECT 
                vp.id,
                vp.disbursement_number,
                vp.amount_paid,
                vp.payment_method,
                vp.payment_reference,
                vp.processed_at,
                s.supplier_code,
                s.supplier_name
            FROM vendor_payments vp
            INNER JOIN suppliers s ON vp.supplier_id = s.id
            ORDER BY vp.processed_at DESC
            LIMIT :limit
        ";

        $stmt = $this->db->query($sql, ['limit' => $limit]);
        $results = $stmt->fetchAll();

        return array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'disbursement_number' => $row['disbursement_number'],
                'amount_paid' => (float) $row['amount_paid'],
                'payment_method' => $row['payment_method'],
                'payment_reference' => $row['payment_reference'],
                'processed_at' => $row['processed_at'],
                'supplier_code' => $row['supplier_code'],
                'supplier_name' => $row['supplier_name']
            ];
        }, $results);
    }

    /**
     * Get supplier statement with transaction history
     * 
     * Returns detailed supplier information along with all payment disbursements
     * and outstanding purchase orders for a complete account statement.
     * 
     * @param int $supplierId Supplier ID
     * @return array<string, mixed>|null Supplier statement data or null if not found
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new SupplierModel();
     * $statement = $model->getSupplierStatement(1);
     * if ($statement) {
     *     echo "Supplier: " . $statement['supplier_name'];
     *     echo "Balance Owed: " . $statement['outstanding_balance'];
     *     foreach ($statement['payments'] as $payment) {
     *         echo $payment['disbursement_number'] . ': ' . $payment['amount_paid'];
     *     }
     * }
     */
    public function getSupplierStatement(int $supplierId): ?array
    {
        // Get supplier details
        $supplierSql = "
            SELECT 
                id,
                supplier_code,
                supplier_name,
                contact_name,
                contact_email,
                contact_phone,
                address,
                payment_terms,
                credit_limit,
                outstanding_balance,
                status,
                created_at,
                updated_at
            FROM suppliers
            WHERE id = :supplier_id
        ";

        $supplierStmt = $this->db->query($supplierSql, ['supplier_id' => $supplierId]);
        $supplier = $supplierStmt->fetch();

        if (!$supplier) {
            return null;
        }

        // Get payment disbursements
        $paymentsSql = "
            SELECT 
                id,
                disbursement_number,
                amount_paid,
                payment_method,
                payment_reference,
                notes,
                processed_at,
                created_at
            FROM vendor_payments
            WHERE supplier_id = :supplier_id
            ORDER BY processed_at DESC
        ";

        $paymentsStmt = $this->db->query($paymentsSql, ['supplier_id' => $supplierId]);
        $payments = $paymentsStmt->fetchAll();

        return [
            'id' => (int) $supplier['id'],
            'supplier_code' => $supplier['supplier_code'],
            'supplier_name' => $supplier['supplier_name'],
            'contact_name' => $supplier['contact_name'],
            'contact_email' => $supplier['contact_email'],
            'contact_phone' => $supplier['contact_phone'],
            'address' => $supplier['address'],
            'payment_terms' => $supplier['payment_terms'],
            'credit_limit' => (float) $supplier['credit_limit'],
            'outstanding_balance' => (float) $supplier['outstanding_balance'],
            'credit_available' => (float) $supplier['credit_limit'] - (float) $supplier['outstanding_balance'],
            'status' => $supplier['status'],
            'created_at' => $supplier['created_at'],
            'updated_at' => $supplier['updated_at'],
            'payments' => array_map(function ($payment) {
                return [
                    'id' => (int) $payment['id'],
                    'disbursement_number' => $payment['disbursement_number'],
                    'amount_paid' => (float) $payment['amount_paid'],
                    'payment_method' => $payment['payment_method'],
                    'payment_reference' => $payment['payment_reference'],
                    'notes' => $payment['notes'],
                    'processed_at' => $payment['processed_at'],
                    'created_at' => $payment['created_at']
                ];
            }, $payments),
            'total_payments' => count($payments)
        ];
    }

    /**
     * Record a credit purchase transaction (inverse of payment)
     *
     * This method records when we purchase goods/services from a supplier on credit,
     * which INCREASES our liability (what we OWE to the supplier). This is the
     * opposite of settleVendorBalance which DECREASES our liability when we pay.
     *
     * @param int $supplierId
     * @param float $totalAmount
     * @param string $invoiceReference
     * @return array<string,mixed>
     */
    public function recordCreditPurchase(
        int $supplierId,
        float $totalAmount,
        string $invoiceReference
    ): array {
        try {
            // Verify required accounts exist to avoid foreign key violations
            $requiredAccountIds = [self::ACCOUNTS_PAYABLE_ACCOUNT_ID, self::INVENTORY_COGS_ACCOUNT_ID];
            $placeholders = implode(',', array_fill(0, count($requiredAccountIds), '?'));
            $verifySql = "SELECT id FROM accounts WHERE id IN ($placeholders)";
            $stmt = $this->db->query($verifySql, $requiredAccountIds);
            $found = $stmt->fetchAll();
            if (count($found) !== count($requiredAccountIds)) {
                $missing = array_diff($requiredAccountIds, array_map(fn($r) => (int)$r['id'], $found));
                throw new Exception('Missing required chart of accounts IDs: ' . implode(', ', $missing) . '. Please create these accounts or update the account ID constants.');
            }
            $this->db->beginTransaction();

            if ($totalAmount <= 0) {
                throw new Exception('Purchase amount must be greater than zero');
            }

            $supplierSql = "
                SELECT id, supplier_code, supplier_name, outstanding_balance, credit_limit, status
                FROM suppliers
                WHERE id = :supplier_id
                FOR UPDATE
            ";

            $supplierStmt = $this->db->query($supplierSql, ['supplier_id' => $supplierId]);
            $supplier = $supplierStmt->fetch();

            if (!$supplier) {
                throw new Exception("Supplier ID {$supplierId} not found");
            }

            if ($supplier['status'] !== 'active') {
                throw new Exception("Cannot process credit purchase: Supplier account is {$supplier['status']}");
            }

            $currentBalance = (float) $supplier['outstanding_balance'];
            $creditLimit = (float) $supplier['credit_limit'];
            $newBalance = $currentBalance + $totalAmount;

            if ($creditLimit > 0 && $newBalance > $creditLimit) {
                throw new Exception('Credit purchase would exceed supplier credit limit');
            }

            $updateSupplierSql = "
                UPDATE suppliers
                SET outstanding_balance = outstanding_balance + :amount, updated_at = NOW()
                WHERE id = :supplier_id
            ";

            $this->db->query($updateSupplierSql, [
                'amount' => $totalAmount,
                'supplier_id' => $supplierId
            ]);

            // Insert transaction and ledger entries
            $transactionDescription = sprintf("Credit Purchase: %s - %s - Invoice: %s", $supplier['supplier_code'], $supplier['supplier_name'], $invoiceReference);
            $transactionDate = date('Y-m-d');

            $transactionSql = "
                INSERT INTO transactions (description, reference_number, transaction_date, created_at, updated_at)
                VALUES (:description, :reference_number, :transaction_date, NOW(), NOW())
            ";

            $this->db->query($transactionSql, [
                'description' => $transactionDescription,
                'reference_number' => $invoiceReference,
                'transaction_date' => $transactionDate
            ]);

            $transactionId = (int) $this->db->lastInsertId();

            $debitInventorySql = "
                INSERT INTO ledger_entries (transaction_id, account_id, amount, created_at, updated_at)
                VALUES (:transaction_id, :account_id, :amount, NOW(), NOW())
            ";

            $this->db->query($debitInventorySql, [
                'transaction_id' => $transactionId,
                'account_id' => self::INVENTORY_COGS_ACCOUNT_ID,
                'amount' => $totalAmount
            ]);

            $creditPayableSql = "
                INSERT INTO ledger_entries (transaction_id, account_id, amount, created_at, updated_at)
                VALUES (:transaction_id, :account_id, :amount, NOW(), NOW())
            ";

            $this->db->query($creditPayableSql, [
                'transaction_id' => $transactionId,
                'account_id' => self::ACCOUNTS_PAYABLE_ACCOUNT_ID,
                'amount' => -$totalAmount
            ]);

            $updateInventoryAccountSql = "UPDATE accounts SET balance = balance + :amount, updated_at = NOW() WHERE id = :account_id";
            $this->db->query($updateInventoryAccountSql, ['amount' => $totalAmount, 'account_id' => self::INVENTORY_COGS_ACCOUNT_ID]);

            $updatePayableAccountSql = "UPDATE accounts SET balance = balance + :amount, updated_at = NOW() WHERE id = :account_id";
            $this->db->query($updatePayableAccountSql, ['amount' => $totalAmount, 'account_id' => self::ACCOUNTS_PAYABLE_ACCOUNT_ID]);

            $this->db->commit();

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'reference_number' => $invoiceReference,
                'supplier_code' => $supplier['supplier_code'],
                'supplier_name' => $supplier['supplier_name'],
                'purchase_amount' => $totalAmount,
                'new_balance' => $newBalance,
                'message' => 'Credit purchase recorded successfully'
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

}
// Made with Bob