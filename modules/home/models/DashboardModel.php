<?php

declare(strict_types=1);

/**
 * DashboardModel Class
 * 
 * Centralized analytical logic for the executive dashboard.
 * Aggregates data from Inventory, Sales, Procurement, HR, and Finance modules
 * to provide high-level business intelligence metrics and operational alerts.
 * 
 * All queries use PDO prepared statements for security and performance.
 * Implements high-performance aggregated queries for real-time analytics.
 * 
 * @package AmenERP\Modules\Home
 * @author Bob (Principal Software Engineer)
 * @version 1.0.0
 */
class DashboardModel
{
    /**
     * Database instance
     * 
     * @var Database
     */
    private Database $db;

    /**
     * Low stock safety threshold
     * Products below this quantity trigger alerts
     * 
     * @var int
     */
    private const LOW_STOCK_THRESHOLD = 5;

    /**
     * Constructor
     * Initializes the database connection using singleton pattern
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get Executive Summary Dashboard Metrics
     * 
     * Compiles high-level financial and operational KPIs:
     * - Total Liquid Runway: Current balance of Cash Safe (Account ID 1)
     * - Stock Capital Asset Value: Total inventory value (quantity × purchase cost)
     * - Monthly Sales Revenue: Total sales income for current month (Account ID 3)
     * - Monthly Payroll Expense: Total HR payroll costs for current month (Account ID 4)
     * 
     * Uses optimized aggregated queries for maximum performance.
     * 
     * @return array<string, float> Associative array of executive metrics
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new DashboardModel();
     * $summary = $model->getExecutiveSummary();
     * echo "Cash: $" . number_format($summary['total_cash'], 2);
     * echo "Stock Value: $" . number_format($summary['stock_value'], 2);
     */
    public function getExecutiveSummary(): array
    {
        // Initialize default metrics
        $summary = [
            'total_cash' => 0.00,
            'stock_value' => 0.00,
            'monthly_revenue' => 0.00,
            'monthly_payroll' => 0.00
        ];

        // Get current month in YYYY-MM format for filtering
        $currentMonth = date('Y-m');

        // ========================================================================
        // METRIC 1: Total Liquid Runway (Cash Safe Balance - Account ID 1)
        // ========================================================================
        $cashSql = "
            SELECT 
                COALESCE(balance, 0) AS cash_balance
            FROM accounts
            WHERE id = 1
            LIMIT 1
        ";

        $cashStmt = $this->db->query($cashSql);
        $cashResult = $cashStmt->fetch();
        
        if ($cashResult) {
            $summary['total_cash'] = (float) $cashResult['cash_balance'];
        }

        // ========================================================================
        // METRIC 2: Stock Capital Asset Value (Inventory × Unit Price)
        // ========================================================================
        $stockSql = "
            SELECT 
                COALESCE(SUM(quantity * unit_price), 0) AS total_stock_value
            FROM products
            WHERE status = 'active'
        ";

        $stockStmt = $this->db->query($stockSql);
        $stockResult = $stockStmt->fetch();
        
        if ($stockResult) {
            $summary['stock_value'] = (float) $stockResult['total_stock_value'];
        }

        // ========================================================================
        // METRIC 3: Monthly Sales Revenue (Account ID 3 - General Sales Income)
        // ========================================================================
        // Sales income is recorded as negative debits in ledger_entries
        // We sum the absolute values of entries hitting the sales income account
        $revenueSql = "
            SELECT 
                COALESCE(SUM(ABS(le.amount)), 0) AS monthly_revenue
            FROM ledger_entries le
            INNER JOIN transactions t ON le.transaction_id = t.id
            WHERE le.account_id = 3
                AND t.transaction_date LIKE CONCAT(:month, '%')
        ";

        $revenueStmt = $this->db->query($revenueSql, ['month' => $currentMonth]);
        $revenueResult = $revenueStmt->fetch();
        
        if ($revenueResult) {
            $summary['monthly_revenue'] = (float) $revenueResult['monthly_revenue'];
        }

        // ========================================================================
        // METRIC 4: Monthly Payroll Expense (Account ID 4 - Utilities Expense)
        // ========================================================================
        // Note: Based on the finance schema, Account ID 4 is "Utilities Expense"
        // In a real system, there would be a dedicated "Payroll Expense" account
        // For now, we'll query expenses that match payroll-related transactions
        $payrollSql = "
            SELECT 
                COALESCE(SUM(ABS(le.amount)), 0) AS monthly_payroll
            FROM ledger_entries le
            INNER JOIN transactions t ON le.transaction_id = t.id
            INNER JOIN accounts a ON le.account_id = a.id
            WHERE a.type = 'expense'
                AND t.description LIKE '%payroll%'
                AND t.transaction_date LIKE CONCAT(:month, '%')
        ";

        $payrollStmt = $this->db->query($payrollSql, ['month' => $currentMonth]);
        $payrollResult = $payrollStmt->fetch();
        
        if ($payrollResult) {
            $summary['monthly_payroll'] = (float) $payrollResult['monthly_payroll'];
        }

        return $summary;
    }

    /**
     * Get Operational Alerts for Low Stock Items
     * 
     * Queries the inventory system for active products where stock quantities
     * have dropped below the safety threshold (5 units).
     * 
     * Returns detailed product information to enable quick procurement decisions.
     * 
     * @return array<int, array<string, mixed>> Array of low stock products
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new DashboardModel();
     * $alerts = $model->getOperationalAlerts();
     * foreach ($alerts as $alert) {
     *     echo "ALERT: {$alert['name']} - Only {$alert['quantity']} left!";
     * }
     */
    public function getOperationalAlerts(): array
    {
        $sql = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.quantity,
                p.unit_price,
                c.name AS category_name
            FROM products p
            INNER JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'active'
                AND p.quantity < :threshold
            ORDER BY p.quantity ASC, p.name ASC
        ";

        $stmt = $this->db->query($sql, ['threshold' => self::LOW_STOCK_THRESHOLD]);
        $results = $stmt->fetchAll();

        // Cast numeric values for consistency
        return array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'sku' => $row['sku'],
                'quantity' => (int) $row['quantity'],
                'unit_price' => (float) $row['unit_price'],
                'category_name' => $row['category_name']
            ];
        }, $results);
    }

    /**
     * Get Recent Activity Feed (Unified Sales & Procurement Stream)
     * 
     * Uses a clean SQL UNION ALL statement to combine:
     * - 5 most recent Sales invoices
     * - 5 most recent Procurement orders
     * 
     * Returns a chronologically sorted, unified activity stream for the dashboard.
     * Each record includes a type indicator ('sale' or 'purchase') for UI styling.
     * 
     * @return array<int, array<string, mixed>> Unified activity feed
     * @throws PDOException If query fails
     * 
     * @example
     * $model = new DashboardModel();
     * $feed = $model->getRecentActivityFeed();
     * foreach ($feed as $activity) {
     *     if ($activity['type'] === 'sale') {
     *         echo "SALE: {$activity['reference']} - {$activity['party_name']}";
     *     } else {
     *         echo "PURCHASE: {$activity['reference']} - {$activity['party_name']}";
     *     }
     * }
     */
    public function getRecentActivityFeed(): array
    {
        $sql = "
            (
                SELECT 
                    'sale' AS type,
                    id,
                    invoice_number AS reference,
                    customer_name AS party_name,
                    total_amount,
                    created_at
                FROM sales_orders
                ORDER BY created_at DESC
                LIMIT 5
            )
            UNION ALL
            (
                SELECT 
                    'purchase' AS type,
                    id,
                    po_number AS reference,
                    supplier_name AS party_name,
                    total_amount,
                    created_at
                FROM purchase_orders
                ORDER BY created_at DESC
                LIMIT 5
            )
            ORDER BY created_at DESC
        ";

        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll();

        // Cast numeric values and ensure consistent data types
        return array_map(function ($row) {
            return [
                'type' => $row['type'],
                'id' => (int) $row['id'],
                'reference' => $row['reference'],
                'party_name' => $row['party_name'],
                'total_amount' => (float) $row['total_amount'],
                'created_at' => $row['created_at']
            ];
        }, $results);
    }

    /**
     * Get the low stock threshold constant
     * 
     * @return int Low stock threshold value
     */
    public function getLowStockThreshold(): int
    {
        return self::LOW_STOCK_THRESHOLD;
    }
}

// Made with Bob