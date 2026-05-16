<?php

declare(strict_types=1);

/**
 * CustomerModel Class
 * 
 * Handles all database operations for the customer accounts and receivables module.
 * Implements ACID-compliant transactions for customer payment processing with
 * automated integration across multiple modules:
 * - Updates customer outstanding balances
 * - Records payment receipts
 * - Creates financial transactions (double-entry bookkeeping)
 * - Updates account balances in the general ledger
 * 
 * All operations are wrapped in database transactions to ensure data integrity.
 * If any step fails, the entire operation is rolled back.
 * 
 * @package AmenERP\Modules\Customers
 * @author Bob
 * @version 1.0.0
 */
class CustomerModel
{
    /**
     * Database instance
     * 
     * @var Database
     */
    private Database $db;

    /**
     * Accounts Receivable Asset Account ID
     * This account tracks money owed to us by customers
     * 
     * @var int
     */
    private const ACCOUNTS_RECEIVABLE_ID = 5;

    /**
     * Cash Account ID (Primary Liquid Asset)
     * This account tracks cash received from customers
     * 
     * @var int
     */
    private const CASH_ACCOUNT_ID = 1;

    /**
     * Constructor
     * Initializes database connection using singleton pattern
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Record a customer payment with full transactional integrity
     * 
     * This is the core payment processing method that executes a complete
     * customer payment transaction across multiple modules:
     * 
     * Action A: Validate payment amount and customer balance
     * Action B: Update customer outstanding_balance (reduce receivables)
     * Action C: Insert payment receipt record for audit trail
     * Action D: Create financial transaction in general ledger
     * Action E: Update account balances (Cash and Accounts Receivable)
     * 
     * All operations are wrapped in a database transaction. If any step fails,
     * the entire operation is rolled back to maintain data integrity.
     * 
     * @param int $customerId Customer ID receiving the payment
     * @param float $amount Payment amount received (must be positive)
     * @param string $method Payment method (cash, check, bank_transfer, credit_card, etc.)
     * @param string|null $reference Optional payment reference (check number, transaction ID, etc.)
     * @param string|null $notes Optional payment notes or remarks
     * @return array<string, mixed> Result array with keys:
     *                              - success: bool
     *                              - receipt_id: int (if successful)
     *                              - receipt_number: string (if successful)
     *                              - message: string
     * @throws PDOException If database operation fails
     * 
     * @example
     * $model = new CustomerModel();
     * $result = $model->recordCustomerPayment(
     *     customerId: 1,
     *     amount: 5000.00,
     *     method: 'bank_transfer',
     *     reference: 'TXN-123456',
     *     notes: 'Payment for Invoice INV-2026-0001'
     * );
     * 
     * if ($result['success']) {
     *     echo "Payment recorded: " . $result['receipt_number'];
     * } else {
     *     echo "Error: " . $result['message'];
     * }
     */
    public function recordCustomerPayment(
        int $customerId,
        float $amount,
        string $method,
        ?string $reference = null,
        ?string $notes = null
    ): array {
        try {
            // Start database transaction for ACID compliance
            $this->db->beginTransaction();

            // ACTION A: Validate payment amount
            if ($amount <= 0) {
                throw new Exception('Payment amount must be greater than zero');
            }

            // ACTION A: Get customer details and validate balance
            $customerSql = "
                SELECT 
                    id,
                    customer_code,
                    company_name,
                    outstanding_balance,
                    status
                FROM customers
                WHERE id = :customer_id
                FOR UPDATE
            ";

            $customerStmt = $this->db->query($customerSql, ['customer_id' => $customerId]);
            $customer = $customerStmt->fetch();

            if (!$customer) {
                throw new Exception("Customer ID {$customerId} not found");
            }

            // Validate customer status
            if ($customer['status'] !== 'active') {
                throw new Exception(
                    "Cannot process payment: Customer account is {$customer['status']}"
                );
            }

            $outstandingBalance = (float) $customer['outstanding_balance'];

            // Validate payment doesn't exceed outstanding balance
            if ($amount > $outstandingBalance) {
                throw new Exception(
                    "Payment amount ({$amount}) exceeds outstanding balance ({$outstandingBalance}). " .
                    "Maximum payment allowed: {$outstandingBalance}"
                );
            }

            // ACTION B: Update customer outstanding balance (reduce receivables)
            $updateCustomerSql = "
                UPDATE customers
                SET 
                    outstanding_balance = outstanding_balance - :amount,
                    updated_at = NOW()
                WHERE id = :customer_id
            ";

            $this->db->query($updateCustomerSql, [
                'amount' => $amount,
                'customer_id' => $customerId
            ]);

            // ACTION C: Generate unique receipt number and insert receipt record
            $receiptNumber = $this->generateReceiptNumber();

            $receiptSql = "
                INSERT INTO customer_receipts (
                    customer_id,
                    receipt_number,
                    amount_paid,
                    payment_method,
                    payment_reference,
                    notes,
                    processed_at,
                    created_at,
                    updated_at
                ) VALUES (
                    :customer_id,
                    :receipt_number,
                    :amount_paid,
                    :payment_method,
                    :payment_reference,
                    :notes,
                    NOW(),
                    NOW(),
                    NOW()
                )
            ";

            $this->db->query($receiptSql, [
                'customer_id' => $customerId,
                'receipt_number' => $receiptNumber,
                'amount_paid' => $amount,
                'payment_method' => $method,
                'payment_reference' => $reference,
                'notes' => $notes
            ]);

            $receiptId = (int) $this->db->lastInsertId();

            // ACTION D: Record financial transaction in general ledger
            // Money flows: DEBIT Cash (increase asset), CREDIT Accounts Receivable (decrease asset)
            $transactionDescription = sprintf(
                "Customer Payment: %s - %s - Receipt: %s",
                $customer['customer_code'],
                $customer['company_name'],
                $receiptNumber
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
                'reference_number' => $receiptNumber,
                'transaction_date' => $transactionDate
            ]);

            $transactionId = (int) $this->db->lastInsertId();

            // Insert DEBIT entry for Cash account (money entering - positive asset increase)
            $debitCashSql = "
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

            $this->db->query($debitCashSql, [
                'transaction_id' => $transactionId,
                'account_id' => self::CASH_ACCOUNT_ID,
                'amount' => $amount // Positive for debit (cash increase)
            ]);

            // Insert CREDIT entry for Accounts Receivable (money leaving - negative asset decrease)
            $creditReceivableSql = "
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

            $this->db->query($creditReceivableSql, [
                'transaction_id' => $transactionId,
                'account_id' => self::ACCOUNTS_RECEIVABLE_ID,
                'amount' => -$amount // Negative for credit (receivables decrease)
            ]);

            // ACTION E: Update account balances in the accounts table
            // Update Cash account balance (increase)
            $updateCashAccountSql = "
                UPDATE accounts
                SET 
                    balance = balance + :amount,
                    updated_at = NOW()
                WHERE id = :account_id
            ";

            $this->db->query($updateCashAccountSql, [
                'amount' => $amount,
                'account_id' => self::CASH_ACCOUNT_ID
            ]);

            // Update Accounts Receivable balance (decrease)
            $updateReceivableAccountSql = "
                UPDATE accounts
                SET 
                    balance = balance - :amount,
                    updated_at = NOW()
                WHERE id = :account_id
            ";

            $this->db->query($updateReceivableAccountSql, [
                'amount' => $amount,
                'account_id' => self::ACCOUNTS_RECEIVABLE_ID
            ]);

            // Commit the transaction - all operations succeeded
            $this->db->commit();

            return [
                'success' => true,
                'receipt_id' => $receiptId,
                'receipt_number' => $receiptNumber,
                'customer_code' => $customer['customer_code'],
                'company_name' => $customer['company_name'],
                'amount_paid' => $amount,
                'new_balance' => $outstandingBalance - $amount,
                'message' => 'Payment recorded successfully'
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
     * Generate a unique receipt number
     * 
     * Format: RCP-YYYY-NNNN (e.g., RCP-2026-0001)
     * 
     * @return string Unique receipt number
     * @throws PDOException If query fails
     */
    private function generateReceiptNumber(): string
    {
        $year = date('Y');
        $prefix = "RCP-{$year}-";

        // Get the highest receipt number for current year
        $sql = "
            SELECT receipt_number
            FROM customer_receipts
            WHERE receipt_number LIKE :prefix
            ORDER BY receipt_number DESC
            LIMIT 1
        ";

        $stmt = $this->db->query($sql, ['prefix' => $prefix . '%']);
        $result = $stmt->fetch();

        if ($result) {
            // Extract the numeric part and increment
            $lastNumber = (int) substr($result['receipt_number'], -4);
            $nextNumber = $lastNumber + 1;
        } else {
            // First receipt of the year
            $nextNumber = 1;
        }

        // Format with leading zeros (4 digits)
        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get all customers with their current balances
     * 
     * Returns a list of all customers ordered by customer code.
     * Useful for displaying customer lists and account summaries.
     * 
     * @param string|null $status Filter by status (active, inactive, suspended) or null for all
     * @return array<int, array<string, mixed>> Array of customers
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new CustomerModel();
     * $customers = $model->getAllCustomers('active');
     * foreach ($customers as $customer) {
     *     echo $customer['customer_code'] . ': ' . $customer['company_name'];
     * }
     */
    public function getAllCustomers(?string $status = null): array
    {
        $sql = "
            SELECT 
                id,
                customer_code,
                company_name,
                contact_name,
                email,
                phone,
                address,
                credit_limit,
                outstanding_balance,
                status,
                created_at,
                updated_at
            FROM customers
        ";

        $params = [];

        if ($status !== null) {
            $sql .= " WHERE status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY customer_code ASC";

        $stmt = $this->db->query($sql, $params);
        $results = $stmt->fetchAll();

        // Cast numeric values for consistency
        return array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'customer_code' => $row['customer_code'],
                'company_name' => $row['company_name'],
                'contact_name' => $row['contact_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'address' => $row['address'],
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
     * Get customer statement with transaction history
     * 
     * Returns detailed customer information along with all payment receipts
     * and outstanding invoices for a complete account statement.
     * 
     * @param int $customerId Customer ID
     * @return array<string, mixed>|null Customer statement data or null if not found
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new CustomerModel();
     * $statement = $model->getCustomerStatement(1);
     * if ($statement) {
     *     echo "Customer: " . $statement['company_name'];
     *     echo "Balance: " . $statement['outstanding_balance'];
     *     foreach ($statement['receipts'] as $receipt) {
     *         echo $receipt['receipt_number'] . ': ' . $receipt['amount_paid'];
     *     }
     * }
     */
    public function getCustomerStatement(int $customerId): ?array
    {
        // Get customer details
        $customerSql = "
            SELECT 
                id,
                customer_code,
                company_name,
                contact_name,
                email,
                phone,
                address,
                credit_limit,
                outstanding_balance,
                status,
                created_at,
                updated_at
            FROM customers
            WHERE id = :customer_id
        ";

        $customerStmt = $this->db->query($customerSql, ['customer_id' => $customerId]);
        $customer = $customerStmt->fetch();

        if (!$customer) {
            return null;
        }

        // Get payment receipts
        $receiptsSql = "
            SELECT 
                id,
                receipt_number,
                amount_paid,
                payment_method,
                payment_reference,
                notes,
                processed_at,
                created_at
            FROM customer_receipts
            WHERE customer_id = :customer_id
            ORDER BY processed_at DESC
        ";

        $receiptsStmt = $this->db->query($receiptsSql, ['customer_id' => $customerId]);
        $receipts = $receiptsStmt->fetchAll();

        // Get linked sales orders (credit invoices)
        $invoicesSql = "
            SELECT 
                id,
                invoice_number,
                total_amount,
                created_at
            FROM sales_orders
            WHERE customer_id = :customer_id
            ORDER BY created_at DESC
        ";

        $invoicesStmt = $this->db->query($invoicesSql, ['customer_id' => $customerId]);
        $invoices = $invoicesStmt->fetchAll();

        return [
            'id' => (int) $customer['id'],
            'customer_code' => $customer['customer_code'],
            'company_name' => $customer['company_name'],
            'contact_name' => $customer['contact_name'],
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'address' => $customer['address'],
            'credit_limit' => (float) $customer['credit_limit'],
            'outstanding_balance' => (float) $customer['outstanding_balance'],
            'credit_available' => (float) $customer['credit_limit'] - (float) $customer['outstanding_balance'],
            'status' => $customer['status'],
            'created_at' => $customer['created_at'],
            'updated_at' => $customer['updated_at'],
            'receipts' => array_map(function ($receipt) {
                return [
                    'id' => (int) $receipt['id'],
                    'receipt_number' => $receipt['receipt_number'],
                    'amount_paid' => (float) $receipt['amount_paid'],
                    'payment_method' => $receipt['payment_method'],
                    'payment_reference' => $receipt['payment_reference'],
                    'notes' => $receipt['notes'],
                    'processed_at' => $receipt['processed_at'],
                    'created_at' => $receipt['created_at']
                ];
            }, $receipts),
            'invoices' => array_map(function ($invoice) {
                return [
                    'id' => (int) $invoice['id'],
                    'invoice_number' => $invoice['invoice_number'],
                    'total_amount' => (float) $invoice['total_amount'],
                    'created_at' => $invoice['created_at']
                ];
            }, $invoices),
            'total_receipts' => count($receipts),
            'total_invoices' => count($invoices)
        ];
    }

    /**
     * Verify credit availability for a customer order
     * 
     * Checks if a customer has sufficient credit available to place an order
     * of the specified amount. Returns detailed credit information.
     * 
     * @param int $customerId Customer ID
     * @param float $orderTotal Proposed order total amount
     * @return array<string, mixed> Credit verification result with keys:
     *                              - approved: bool
     *                              - credit_limit: float
     *                              - outstanding_balance: float
     *                              - credit_available: float
     *                              - order_total: float
     *                              - remaining_credit: float (after order)
     *                              - message: string
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new CustomerModel();
     * $verification = $model->verifyCreditAvailability(1, 15000.00);
     * if ($verification['approved']) {
     *     echo "Credit approved. Remaining: " . $verification['remaining_credit'];
     * } else {
     *     echo "Credit denied: " . $verification['message'];
     * }
     */
    public function verifyCreditAvailability(int $customerId, float $orderTotal): array
    {
        // Get customer credit information
        $sql = "
            SELECT 
                customer_code,
                company_name,
                credit_limit,
                outstanding_balance,
                status
            FROM customers
            WHERE id = :customer_id
        ";

        $stmt = $this->db->query($sql, ['customer_id' => $customerId]);
        $customer = $stmt->fetch();

        if (!$customer) {
            return [
                'approved' => false,
                'message' => "Customer ID {$customerId} not found"
            ];
        }

        // Check customer status
        if ($customer['status'] !== 'active') {
            return [
                'approved' => false,
                'customer_code' => $customer['customer_code'],
                'company_name' => $customer['company_name'],
                'message' => "Customer account is {$customer['status']}"
            ];
        }

        $creditLimit = (float) $customer['credit_limit'];
        $outstandingBalance = (float) $customer['outstanding_balance'];
        $creditAvailable = $creditLimit - $outstandingBalance;
        $remainingCredit = $creditAvailable - $orderTotal;

        // Verify sufficient credit
        if ($orderTotal > $creditAvailable) {
            return [
                'approved' => false,
                'customer_code' => $customer['customer_code'],
                'company_name' => $customer['company_name'],
                'credit_limit' => $creditLimit,
                'outstanding_balance' => $outstandingBalance,
                'credit_available' => $creditAvailable,
                'order_total' => $orderTotal,
                'credit_shortage' => $orderTotal - $creditAvailable,
                'message' => sprintf(
                    'Insufficient credit: Available %.2f, Required %.2f, Shortage %.2f',
                    $creditAvailable,
                    $orderTotal,
                    $orderTotal - $creditAvailable
                )
            ];
        }

        return [
            'approved' => true,
            'customer_code' => $customer['customer_code'],
            'company_name' => $customer['company_name'],
            'credit_limit' => $creditLimit,
            'outstanding_balance' => $outstandingBalance,
            'credit_available' => $creditAvailable,
            'order_total' => $orderTotal,
            'remaining_credit' => $remainingCredit,
            'message' => 'Credit approved'
        ];
    }

    /**
     * Get customer by ID
     * 
     * @param int $customerId Customer ID
     * @return array<string, mixed>|null Customer data or null if not found
     * @throws PDOException If query fails
     */
    public function getCustomerById(int $customerId): ?array
    {
        $sql = "
            SELECT 
                id,
                customer_code,
                company_name,
                contact_name,
                email,
                phone,
                address,
                credit_limit,
                outstanding_balance,
                status,
                created_at,
                updated_at
            FROM customers
            WHERE id = :customer_id
        ";

        $stmt = $this->db->query($sql, ['customer_id' => $customerId]);
        $customer = $stmt->fetch();

        if (!$customer) {
            return null;
        }

        return [
            'id' => (int) $customer['id'],
            'customer_code' => $customer['customer_code'],
            'company_name' => $customer['company_name'],
            'contact_name' => $customer['contact_name'],
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'address' => $customer['address'],
            'credit_limit' => (float) $customer['credit_limit'],
            'outstanding_balance' => (float) $customer['outstanding_balance'],
            'credit_available' => (float) $customer['credit_limit'] - (float) $customer['outstanding_balance'],
            'status' => $customer['status'],
            'created_at' => $customer['created_at'],
            'updated_at' => $customer['updated_at']
        ];
    }

    /**
     * Get accounts receivable summary statistics
     * 
     * Returns aggregate statistics for all customer accounts including
     * total receivables, total credit extended, and customer counts.
     * 
     * @return array<string, mixed> Receivables statistics
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new CustomerModel();
     * $stats = $model->getReceivablesStats();
     * echo "Total Receivables: " . $stats['total_receivables'];
     * echo "Active Customers: " . $stats['active_customers'];
     */
    public function getReceivablesStats(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total_customers,
                COUNT(CASE WHEN status = 'active' THEN 1 END) AS active_customers,
                COALESCE(SUM(credit_limit), 0) AS total_credit_extended,
                COALESCE(SUM(outstanding_balance), 0) AS total_receivables,
                COALESCE(SUM(credit_limit - outstanding_balance), 0) AS total_credit_available
            FROM customers
        ";

        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();

        return [
            'total_customers' => (int) $result['total_customers'],
            'active_customers' => (int) $result['active_customers'],
            'total_credit_extended' => (float) $result['total_credit_extended'],
            'total_receivables' => (float) $result['total_receivables'],
            'total_credit_available' => (float) $result['total_credit_available'],
            'credit_utilization_percentage' => $result['total_credit_extended'] > 0
                ? round(($result['total_receivables'] / $result['total_credit_extended']) * 100, 2)
                : 0.00
        ];
    }
}

// Made with Bob