<?php

declare(strict_types=1);

/**
 * FinanceModel Class
 * 
 * Handles all database operations for the finance module.
 * Implements double-entry bookkeeping with ACID-compliant transactions.
 * All queries use PDO prepared statements for security.
 * 
 * @package AmenERP\Modules\Finance
 * @author Bob
 * @version 1.0.0
 */
class FinanceModel
{
    /**
     * Database instance
     * 
     * @var Database
     */
    private Database $db;

    /**
     * Constructor
     * Initializes the database connection using singleton pattern
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all financial accounts categorized by their type
     * 
     * Returns all accounts organized by type (asset, liability, equity, income, expense).
     * Useful for displaying the chart of accounts and financial reports.
     * 
     * @return array<string, array<int, array<string, mixed>>> Accounts grouped by type
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new FinanceModel();
     * $accounts = $model->getAccounts();
     * foreach ($accounts['asset'] as $account) {
     *     echo $account['name'] . ': ' . $account['balance'];
     * }
     */
    public function getAccounts(): array
    {
        $sql = "
            SELECT 
                id,
                name,
                type,
                balance,
                created_at,
                updated_at
            FROM accounts
            ORDER BY type ASC, name ASC
        ";

        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll();

        // Group accounts by type for easier display
        $categorized = [
            'asset' => [],
            'liability' => [],
            'equity' => [],
            'income' => [],
            'expense' => []
        ];

        foreach ($results as $account) {
            $type = $account['type'];
            $categorized[$type][] = [
                'id' => (int) $account['id'],
                'name' => $account['name'],
                'type' => $account['type'],
                'balance' => (float) $account['balance'],
                'created_at' => $account['created_at'],
                'updated_at' => $account['updated_at']
            ];
        }

        return $categorized;
    }

    /**
     * Get recent ledger entries with transaction details
     * 
     * Returns a list of recent transactions joined with their ledger distribution
     * details for a clear audit trail. Shows the complete double-entry flow.
     * 
     * @param int $limit Maximum number of transactions to return (default: 50)
     * @return array<int, array<string, mixed>> Array of ledger entries with transaction data
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new FinanceModel();
     * $ledger = $model->getRecentLedger(20);
     * foreach ($ledger as $entry) {
     *     echo $entry['transaction_description'] . ' - ' . $entry['account_name'] . ': ' . $entry['amount'];
     * }
     */
    public function getRecentLedger(int $limit = 50): array
    {
        $sql = "
            SELECT 
                le.id AS entry_id,
                le.amount,
                le.created_at AS entry_created_at,
                t.id AS transaction_id,
                t.description AS transaction_description,
                t.reference_number,
                t.transaction_date,
                t.created_at AS transaction_created_at,
                a.id AS account_id,
                a.name AS account_name,
                a.type AS account_type,
                a.balance AS account_balance
            FROM ledger_entries le
            INNER JOIN transactions t ON le.transaction_id = t.id
            INNER JOIN accounts a ON le.account_id = a.id
            ORDER BY t.transaction_date DESC, t.id DESC, le.id ASC
            LIMIT :limit
        ";

        $stmt = $this->db->query($sql, ['limit' => $limit]);
        $results = $stmt->fetchAll();

        // Cast numeric values for consistency
        return array_map(function ($row) {
            return [
                'entry_id' => (int) $row['entry_id'],
                'amount' => (float) $row['amount'],
                'entry_created_at' => $row['entry_created_at'],
                'transaction_id' => (int) $row['transaction_id'],
                'transaction_description' => $row['transaction_description'],
                'reference_number' => $row['reference_number'],
                'transaction_date' => $row['transaction_date'],
                'transaction_created_at' => $row['transaction_created_at'],
                'account_id' => (int) $row['account_id'],
                'account_name' => $row['account_name'],
                'account_type' => $row['account_type'],
                'account_balance' => (float) $row['account_balance']
            ];
        }, $results);
    }

    /**
     * Get monthly financial metrics (inflow and outflow)
     * 
     * Calculates the total income and expense amounts for a specific month
     * by aggregating ledger entries filtered by transaction date.
     * 
     * Monthly Inflow: Sum of absolute values from entries hitting 'income' type accounts
     * Monthly Outflow: Sum of absolute values from entries hitting 'expense' type accounts
     * 
     * @param string $monthYmd Month in YYYY-MM format (e.g., '2026-05')
     * @return array<string, float> Associative array with 'inflow' and 'outflow' keys
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new FinanceModel();
     * $metrics = $model->getMonthlyMetrics('2026-05');
     * echo "Inflow: $" . number_format($metrics['inflow'], 2);
     * echo "Outflow: $" . number_format($metrics['outflow'], 2);
     */
    public function getMonthlyMetrics(string $monthYmd): array
    {
        // Initialize default return values
        $metrics = [
            'inflow' => 0.00,
            'outflow' => 0.00
        ];

        // Query 1: Calculate Monthly Inflow (income account entries)
        $inflowSql = "
            SELECT 
                COALESCE(SUM(ABS(le.amount)), 0) AS total_inflow
            FROM ledger_entries le
            INNER JOIN transactions t ON le.transaction_id = t.id
            INNER JOIN accounts a ON le.account_id = a.id
            WHERE a.type = 'income'
                AND t.transaction_date LIKE CONCAT(:month, '%')
        ";

        $inflowStmt = $this->db->query($inflowSql, ['month' => $monthYmd]);
        $inflowResult = $inflowStmt->fetch();
        
        if ($inflowResult && isset($inflowResult['total_inflow'])) {
            $metrics['inflow'] = (float) $inflowResult['total_inflow'];
        }

        // Query 2: Calculate Monthly Outflow (expense account entries)
        $outflowSql = "
            SELECT 
                COALESCE(SUM(ABS(le.amount)), 0) AS total_outflow
            FROM ledger_entries le
            INNER JOIN transactions t ON le.transaction_id = t.id
            INNER JOIN accounts a ON le.account_id = a.id
            WHERE a.type = 'expense'
                AND t.transaction_date LIKE CONCAT(:month, '%')
        ";

        $outflowStmt = $this->db->query($outflowSql, ['month' => $monthYmd]);
        $outflowResult = $outflowStmt->fetch();
        
        if ($outflowResult && isset($outflowResult['total_outflow'])) {
            $metrics['outflow'] = (float) $outflowResult['total_outflow'];
        }

        return $metrics;
    }

    /**
     * Add a simple transaction using double-entry bookkeeping
     * 
     * This is the Double-Entry Engine Wrapper. It creates a complete transaction
     * with balanced ledger entries and updates account balances atomically.
     * 
     * The method:
     * 1. Starts a database transaction for ACID compliance
     * 2. Inserts a master record into transactions table
     * 3. Creates a debit entry (negative amount) for the source account
     * 4. Creates a credit entry (positive amount) for the destination account
     * 5. Updates cached balances in both accounts
     * 6. Commits the transaction if all operations succeed
     * 7. Rolls back if any error occurs
     * 
     * @param string $description Transaction description
     * @param int $fromAccountId Source account ID (money leaves this account)
     * @param int $toAccountId Destination account ID (money enters this account)
     * @param float $amount Transaction amount (must be positive)
     * @param string $date Transaction date in YYYY-MM-DD format
     * @return bool True on success, false on failure
     * @throws PDOException If database operation fails
     * 
     * @example
     * $model = new FinanceModel();
     * try {
     *     $result = $model->addSimpleTransaction(
     *         'Payment for office supplies',
     *         1, // Cash Safe account
     *         4, // Utilities Expense account
     *         500.00,
     *         '2026-05-16'
     *     );
     *     if ($result) {
     *         echo "Transaction recorded successfully";
     *     }
     * } catch (PDOException $e) {
     *     echo "Transaction failed: " . $e->getMessage();
     * }
     */
    public function addSimpleTransaction(
        string $description,
        int $fromAccountId,
        int $toAccountId,
        float $amount,
        string $date
    ): bool {
        try {
            // Start database transaction for ACID compliance
            $this->db->beginTransaction();

            // Step 1: Insert master transaction record
            $transactionSql = "
                INSERT INTO transactions (
                    description,
                    transaction_date,
                    created_at,
                    updated_at
                ) VALUES (
                    :description,
                    :transaction_date,
                    NOW(),
                    NOW()
                )
            ";

            $this->db->query($transactionSql, [
                'description' => $description,
                'transaction_date' => $date
            ]);

            // Get the transaction ID for ledger entries
            $transactionId = (int) $this->db->lastInsertId();

            // Step 2: Insert debit entry for source account (subtract amount)
            $debitSql = "
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

            $this->db->query($debitSql, [
                'transaction_id' => $transactionId,
                'account_id' => $fromAccountId,
                'amount' => -$amount // Negative for debit (money leaving)
            ]);

            // Step 3: Insert credit entry for destination account (add amount)
            $creditSql = "
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

            $this->db->query($creditSql, [
                'transaction_id' => $transactionId,
                'account_id' => $toAccountId,
                'amount' => $amount // Positive for credit (money entering)
            ]);

            // Step 4: Update cached balance for source account (subtract)
            $updateFromSql = "
                UPDATE accounts
                SET 
                    balance = balance - :amount,
                    updated_at = NOW()
                WHERE id = :account_id
            ";

            $this->db->query($updateFromSql, [
                'amount' => $amount,
                'account_id' => $fromAccountId
            ]);

            // Step 5: Update cached balance for destination account (add)
            $updateToSql = "
                UPDATE accounts
                SET 
                    balance = balance + :amount,
                    updated_at = NOW()
                WHERE id = :account_id
            ";

            $this->db->query($updateToSql, [
                'amount' => $amount,
                'account_id' => $toAccountId
            ]);

            // Commit the transaction - all operations succeeded
            $this->db->commit();

            return true;

        } catch (PDOException $e) {
            // Rollback on any error to maintain data integrity
            $this->db->rollback();

            // Re-throw the exception for proper error handling
            throw $e;
        }
    }
}

// Made with Bob