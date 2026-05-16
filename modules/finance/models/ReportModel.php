<?php

declare(strict_types=1);

/**
 * ReportModel Class
 * 
 * Generates financial statements for the ERP system.
 * Implements standard accounting reports: Income Statement and Balance Sheet.
 * All queries use PDO prepared statements for security and optimal performance.
 * 
 * @package AmenERP\Modules\Finance
 * @author Bob
 * @version 1.0.0
 */
class ReportModel
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
     * Generate Income Statement (Profit & Loss Statement)
     * 
     * Calculates financial performance over a specific period by aggregating:
     * - Revenue: Sum of credit entries (negative amounts) to income accounts
     * - Expenses: Sum of debit entries (positive amounts) to expense accounts
     * - Gross Profit: Revenue - Cost of Goods Sold (if applicable)
     * - Net Profit: Revenue - Total Expenses
     * 
     * The Income Statement follows the formula:
     * Net Profit = Total Revenue - Total Expenses
     * 
     * @param string $startDate Start date in YYYY-MM-DD format (inclusive)
     * @param string $endDate End date in YYYY-MM-DD format (inclusive)
     * @return array<string, mixed> Associative array with revenue, expenses, and profit data
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new ReportModel();
     * $statement = $model->getIncomeStatement('2026-01-01', '2026-12-31');
     * echo "Net Profit: $" . number_format($statement['net_profit'], 2);
     * 
     * @return array Structure:
     * [
     *   'period' => ['start' => '2026-01-01', 'end' => '2026-12-31'],
     *   'revenue' => [
     *     'accounts' => [
     *       ['account_id' => 3, 'account_name' => 'General Sales Income', 'amount' => 50000.00],
     *       ...
     *     ],
     *     'total' => 50000.00
     *   ],
     *   'expenses' => [
     *     'accounts' => [
     *       ['account_id' => 4, 'account_name' => 'Utilities Expense', 'amount' => 5000.00],
     *       ['account_id' => 5, 'account_name' => 'Inventory Purchase Expense', 'amount' => 20000.00],
     *       ...
     *     ],
     *     'total' => 25000.00
     *   ],
     *   'gross_profit' => 50000.00,
     *   'net_profit' => 25000.00
     * ]
     */
    public function getIncomeStatement(string $startDate, string $endDate): array
    {
        // Initialize the report structure
        $report = [
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'revenue' => [
                'accounts' => [],
                'total' => 0.00
            ],
            'expenses' => [
                'accounts' => [],
                'total' => 0.00
            ],
            'gross_profit' => 0.00,
            'net_profit' => 0.00
        ];

        // Query 1: Aggregate Revenue (Income Accounts)
        // Income accounts are credited (negative amounts in ledger_entries)
        // We use ABS() to get positive values for display
        $revenueSql = "
            SELECT 
                a.id AS account_id,
                a.name AS account_name,
                COALESCE(SUM(ABS(le.amount)), 0) AS amount
            FROM accounts a
            LEFT JOIN ledger_entries le ON a.id = le.account_id
            LEFT JOIN transactions t ON le.transaction_id = t.id
            WHERE a.type = 'income'
                AND (
                    t.transaction_date IS NULL 
                    OR (t.transaction_date BETWEEN :start_date AND :end_date)
                )
            GROUP BY a.id, a.name
            HAVING amount > 0
            ORDER BY amount DESC, a.name ASC
        ";

        $revenueStmt = $this->db->query($revenueSql, [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $revenueResults = $revenueStmt->fetchAll();

        foreach ($revenueResults as $row) {
            $amount = (float) $row['amount'];
            $report['revenue']['accounts'][] = [
                'account_id' => (int) $row['account_id'],
                'account_name' => $row['account_name'],
                'amount' => $amount
            ];
            $report['revenue']['total'] += $amount;
        }

        // Query 2: Aggregate Expenses (Expense Accounts)
        // Expense accounts are debited (positive amounts in ledger_entries)
        $expenseSql = "
            SELECT 
                a.id AS account_id,
                a.name AS account_name,
                COALESCE(SUM(ABS(le.amount)), 0) AS amount
            FROM accounts a
            LEFT JOIN ledger_entries le ON a.id = le.account_id
            LEFT JOIN transactions t ON le.transaction_id = t.id
            WHERE a.type = 'expense'
                AND (
                    t.transaction_date IS NULL 
                    OR (t.transaction_date BETWEEN :start_date AND :end_date)
                )
            GROUP BY a.id, a.name
            HAVING amount > 0
            ORDER BY amount DESC, a.name ASC
        ";

        $expenseStmt = $this->db->query($expenseSql, [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $expenseResults = $expenseStmt->fetchAll();

        foreach ($expenseResults as $row) {
            $amount = (float) $row['amount'];
            $report['expenses']['accounts'][] = [
                'account_id' => (int) $row['account_id'],
                'account_name' => $row['account_name'],
                'amount' => $amount
            ];
            $report['expenses']['total'] += $amount;
        }

        // Calculate Profit Metrics
        // Gross Profit = Total Revenue (for service businesses, this equals revenue)
        $report['gross_profit'] = $report['revenue']['total'];

        // Net Profit = Total Revenue - Total Expenses
        $report['net_profit'] = $report['revenue']['total'] - $report['expenses']['total'];

        return $report;
    }

    /**
     * Generate Balance Sheet (Statement of Financial Position)
     * 
     * Provides a snapshot of the company's financial position at the current moment.
     * Implements the fundamental accounting equation:
     * Assets = Liabilities + Equity
     * 
     * The Balance Sheet groups accounts by type and calculates:
     * - Total Assets: Sum of all asset account balances
     * - Total Liabilities: Sum of all liability account balances
     * - Total Equity: Sum of all equity account balances
     * - Balance Check: Verifies Assets = Liabilities + Equity
     * 
     * @return array<string, mixed> Associative array with assets, liabilities, equity, and totals
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new ReportModel();
     * $balanceSheet = $model->getBalanceSheet();
     * echo "Total Assets: $" . number_format($balanceSheet['assets']['total'], 2);
     * echo "Total Liabilities: $" . number_format($balanceSheet['liabilities']['total'], 2);
     * echo "Total Equity: $" . number_format($balanceSheet['equity']['total'], 2);
     * 
     * @return array Structure:
     * [
     *   'as_of_date' => '2026-05-16',
     *   'assets' => [
     *     'accounts' => [
     *       ['account_id' => 1, 'account_name' => 'Cash Safe', 'balance' => 10000.00],
     *       ['account_id' => 2, 'account_name' => 'Bank Account', 'balance' => 25000.00],
     *       ...
     *     ],
     *     'total' => 35000.00
     *   ],
     *   'liabilities' => [
     *     'accounts' => [
     *       ['account_id' => 6, 'account_name' => 'Accounts Payable', 'balance' => 5000.00],
     *       ...
     *     ],
     *     'total' => 5000.00
     *   ],
     *   'equity' => [
     *     'accounts' => [
     *       ['account_id' => 7, 'account_name' => 'Owner Equity', 'balance' => 30000.00],
     *       ...
     *     ],
     *     'total' => 30000.00
     *   ],
     *   'total_liabilities_and_equity' => 35000.00,
     *   'is_balanced' => true
     * ]
     */
    public function getBalanceSheet(): array
    {
        // Initialize the report structure
        $report = [
            'as_of_date' => date('Y-m-d'),
            'assets' => [
                'accounts' => [],
                'total' => 0.00
            ],
            'liabilities' => [
                'accounts' => [],
                'total' => 0.00
            ],
            'equity' => [
                'accounts' => [],
                'total' => 0.00
            ],
            'total_liabilities_and_equity' => 0.00,
            'is_balanced' => false
        ];

        // Single optimized query to fetch all balance sheet accounts
        // Groups by account type for efficient processing
        $sql = "
            SELECT 
                id,
                name,
                type,
                balance
            FROM accounts
            WHERE type IN ('asset', 'liability', 'equity')
            ORDER BY 
                FIELD(type, 'asset', 'liability', 'equity'),
                name ASC
        ";

        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll();

        // Process and categorize accounts
        foreach ($results as $row) {
            $accountData = [
                'account_id' => (int) $row['id'],
                'account_name' => $row['name'],
                'balance' => (float) $row['balance']
            ];

            $type = $row['type'];

            // Categorize by account type
            switch ($type) {
                case 'asset':
                    $report['assets']['accounts'][] = $accountData;
                    $report['assets']['total'] += $accountData['balance'];
                    break;

                case 'liability':
                    $report['liabilities']['accounts'][] = $accountData;
                    $report['liabilities']['total'] += $accountData['balance'];
                    break;

                case 'equity':
                    $report['equity']['accounts'][] = $accountData;
                    $report['equity']['total'] += $accountData['balance'];
                    break;
            }
        }

        // Calculate total liabilities and equity
        $report['total_liabilities_and_equity'] = 
            $report['liabilities']['total'] + $report['equity']['total'];

        // Verify the fundamental accounting equation
        // Assets = Liabilities + Equity
        // Allow for minor floating-point rounding differences (0.01)
        $difference = abs($report['assets']['total'] - $report['total_liabilities_and_equity']);
        $report['is_balanced'] = $difference < 0.01;

        return $report;
    }
}

// Made with Bob