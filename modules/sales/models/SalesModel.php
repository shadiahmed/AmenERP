<?php

declare(strict_types=1);

/**
 * SalesModel Class
 *
 * Handles all database operations for the sales module with automated integration.
 * Implements ACID-compliant transactions that span multiple modules:
 * - Creates sales orders and line items
 * - Updates inventory stock levels automatically
 * - Records financial transactions directly (double-entry bookkeeping)
 *
 * All operations are wrapped in a single database transaction to ensure data integrity.
 * If any step fails, the entire operation is rolled back.
 *
 * @package AmenERP\Modules\Sales
 * @author Bob
 * @version 1.0.1
 */
class SalesModel
{
    /**
     * Database instance
     *
     * @var Database
     */
    private Database $db;

    /**
     * Default income account ID (General Sales Income)
     *
     * @var int
     */
    private const SALES_INCOME_ACCOUNT_ID = 3;

    /**
     * Default cash/bank account ID (Cash Safe or Bank Account)
     *
     * @var int
     */
    private const CASH_ACCOUNT_ID = 1;

    /**
     * Constructor
     * Initializes database connection
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new sales order with automated multi-module integration
     * 
     * This is the core integration method that executes a complete sales transaction:
     * 
     * Action A: Insert master sales_order and line items into sales_items
     * Action B: Update inventory stock levels for each product sold
     * Action C: Record financial transaction via FinanceModel
     * 
     * All operations are wrapped in a database transaction. If any step fails
     * (e.g., insufficient stock, database error), the entire operation is rolled back.
     * 
     * @param string $customerName Customer or buyer name
     * @param array<int, array<string, mixed>> $items Array of items with keys:
     *                                                - product_id: Product ID (int)
     *                                                - quantity: Quantity to sell (int)
     *                                                - unit_price: Price per unit (float)
     * @param int|null $cashAccountId Optional cash account ID (defaults to CASH_ACCOUNT_ID)
     * @return array<string, mixed> Result array with keys:
     *                              - success: bool
     *                              - sales_order_id: int (if successful)
     *                              - invoice_number: string (if successful)
     *                              - message: string (error message if failed)
     * @throws PDOException If database operation fails
     * 
     * @example
     * $model = new SalesModel();
     * $result = $model->createSalesOrder('John Doe', [
     *     ['product_id' => 1, 'quantity' => 2, 'unit_price' => 50.00],
     *     ['product_id' => 3, 'quantity' => 1, 'unit_price' => 150.00]
     * ]);
     * 
     * if ($result['success']) {
     *     echo "Order created: " . $result['invoice_number'];
     * } else {
     *     echo "Error: " . $result['message'];
     * }
     */
    public function createSalesOrder(
        string $customerName,
        array $items,
        ?int $cashAccountId = null
    ): array {
        // Use default cash account if not specified
        $cashAccountId = $cashAccountId ?? self::CASH_ACCOUNT_ID;

        try {
            // Start database transaction for ACID compliance
            $this->db->beginTransaction();

            // Validate that we have items to sell
            if (empty($items)) {
                throw new Exception('Cannot create sales order: No items provided');
            }

            // Calculate total amount and validate inventory availability
            $totalAmount = 0.00;
            foreach ($items as $item) {
                // Validate required fields
                if (!isset($item['product_id'], $item['quantity'], $item['unit_price'])) {
                    throw new Exception('Invalid item data: Missing required fields');
                }

                // Validate quantity is positive
                if ($item['quantity'] <= 0) {
                    throw new Exception('Invalid quantity: Must be greater than zero');
                }

                // Check inventory availability BEFORE creating the order
                $stockCheckSql = "SELECT quantity FROM products WHERE id = :product_id";
                $stockStmt = $this->db->query($stockCheckSql, ['product_id' => $item['product_id']]);
                $stockResult = $stockStmt->fetch();

                if (!$stockResult) {
                    throw new Exception("Product ID {$item['product_id']} not found");
                }

                $availableStock = (int) $stockResult['quantity'];
                if ($availableStock < $item['quantity']) {
                    throw new Exception(
                        "Insufficient stock for product ID {$item['product_id']}: " .
                        "Available: {$availableStock}, Required: {$item['quantity']}"
                    );
                }

                // Calculate line total
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $lineTotal;
            }

            // Generate unique invoice number
            $invoiceNumber = $this->generateInvoiceNumber();

            // ACTION A: Insert master sales order record
            $orderSql = "
                INSERT INTO sales_orders (
                    invoice_number,
                    customer_name,
                    total_amount,
                    created_at,
                    updated_at
                ) VALUES (
                    :invoice_number,
                    :customer_name,
                    :total_amount,
                    NOW(),
                    NOW()
                )
            ";

            $this->db->query($orderSql, [
                'invoice_number' => $invoiceNumber,
                'customer_name' => $customerName,
                'total_amount' => $totalAmount
            ]);

            // Get the sales order ID
            $salesOrderId = (int) $this->db->lastInsertId();

            // ACTION A & B: Insert line items and update inventory
            foreach ($items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];

                // Insert sales item
                $itemSql = "
                    INSERT INTO sales_items (
                        sales_order_id,
                        product_id,
                        quantity,
                        unit_price,
                        line_total,
                        created_at,
                        updated_at
                    ) VALUES (
                        :sales_order_id,
                        :product_id,
                        :quantity,
                        :unit_price,
                        :line_total,
                        NOW(),
                        NOW()
                    )
                ";

                $this->db->query($itemSql, [
                    'sales_order_id' => $salesOrderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $lineTotal
                ]);

                // ACTION B: Update inventory stock (deduct sold quantity)
                $inventoryUpdateSql = "
                    UPDATE products
                    SET 
                        quantity = quantity - :sold_qty,
                        updated_at = NOW()
                    WHERE id = :product_id
                ";

                $this->db->query($inventoryUpdateSql, [
                    'sold_qty' => $item['quantity'],
                    'product_id' => $item['product_id']
                ]);

                // Verify stock didn't go negative (safety check)
                $verifyStockSql = "SELECT quantity FROM products WHERE id = :product_id";
                $verifyStmt = $this->db->query($verifyStockSql, ['product_id' => $item['product_id']]);
                $verifyResult = $verifyStmt->fetch();

                if ($verifyResult && (int) $verifyResult['quantity'] < 0) {
                    throw new Exception(
                        "Stock validation failed for product ID {$item['product_id']}: " .
                        "Stock would become negative"
                    );
                }
            }

            // ACTION C: Record financial transaction manually (avoid nested transactions)
            // Money flows FROM Sales Income Account TO Cash/Bank Account
            $transactionDescription = "Sales Invoice: {$invoiceNumber} - Customer: {$customerName}";
            $transactionDate = date('Y-m-d');

            // Insert master transaction record
            $financeSql = "
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

            $this->db->query($financeSql, [
                'description' => $transactionDescription,
                'transaction_date' => $transactionDate
            ]);

            $transactionId = (int) $this->db->lastInsertId();

            // Insert debit entry for Sales Income account (money leaving)
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
                'account_id' => self::SALES_INCOME_ACCOUNT_ID,
                'amount' => -$totalAmount // Negative for debit
            ]);

            // Insert credit entry for Cash account (money entering)
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
                'account_id' => $cashAccountId,
                'amount' => $totalAmount // Positive for credit
            ]);

            // Update Sales Income account balance (subtract)
            $updateIncomeSql = "
                UPDATE accounts
                SET
                    balance = balance - :amount,
                    updated_at = NOW()
                WHERE id = :account_id
            ";

            $this->db->query($updateIncomeSql, [
                'amount' => $totalAmount,
                'account_id' => self::SALES_INCOME_ACCOUNT_ID
            ]);

            // Update Cash account balance (add)
            $updateCashSql = "
                UPDATE accounts
                SET
                    balance = balance + :amount,
                    updated_at = NOW()
                WHERE id = :account_id
            ";

            $this->db->query($updateCashSql, [
                'amount' => $totalAmount,
                'account_id' => $cashAccountId
            ]);

            // Commit the transaction - all operations succeeded
            $this->db->commit();

            return [
                'success' => true,
                'sales_order_id' => $salesOrderId,
                'invoice_number' => $invoiceNumber,
                'total_amount' => $totalAmount,
                'message' => 'Sales order created successfully'
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
     * Generate a unique invoice number
     * 
     * Format: INV-YYYY-NNNN (e.g., INV-2026-0001)
     * 
     * @return string Unique invoice number
     * @throws PDOException If query fails
     */
    private function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $prefix = "INV-{$year}-";

        // Get the highest invoice number for current year
        $sql = "
            SELECT invoice_number
            FROM sales_orders
            WHERE invoice_number LIKE :prefix
            ORDER BY invoice_number DESC
            LIMIT 1
        ";

        $stmt = $this->db->query($sql, ['prefix' => $prefix . '%']);
        $result = $stmt->fetch();

        if ($result) {
            // Extract the numeric part and increment
            $lastNumber = (int) substr($result['invoice_number'], -4);
            $nextNumber = $lastNumber + 1;
        } else {
            // First invoice of the year
            $nextNumber = 1;
        }

        // Format with leading zeros (4 digits)
        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get all sales orders with summary information
     * 
     * @param int $limit Maximum number of orders to return (default: 50)
     * @return array<int, array<string, mixed>> Array of sales orders
     * @throws PDOException If query fails
     */
    public function getAllSalesOrders(int $limit = 50): array
    {
        $sql = "
            SELECT 
                id,
                invoice_number,
                customer_name,
                total_amount,
                created_at,
                updated_at
            FROM sales_orders
            ORDER BY created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->db->query($sql, ['limit' => $limit]);
        $results = $stmt->fetchAll();

        // Cast numeric values
        return array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'invoice_number' => $row['invoice_number'],
                'customer_name' => $row['customer_name'],
                'total_amount' => (float) $row['total_amount'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }, $results);
    }

    /**
     * Get sales order details with line items
     * 
     * @param int $salesOrderId Sales order ID
     * @return array<string, mixed>|null Order details with items array, or null if not found
     * @throws PDOException If query fails
     */
    public function getSalesOrderById(int $salesOrderId): ?array
    {
        // Get order header
        $orderSql = "
            SELECT 
                id,
                invoice_number,
                customer_name,
                total_amount,
                created_at,
                updated_at
            FROM sales_orders
            WHERE id = :sales_order_id
        ";

        $orderStmt = $this->db->query($orderSql, ['sales_order_id' => $salesOrderId]);
        $order = $orderStmt->fetch();

        if (!$order) {
            return null;
        }

        // Get line items
        $itemsSql = "
            SELECT 
                si.id,
                si.product_id,
                si.quantity,
                si.unit_price,
                si.line_total,
                p.name AS product_name,
                p.sku AS product_sku
            FROM sales_items si
            INNER JOIN products p ON si.product_id = p.id
            WHERE si.sales_order_id = :sales_order_id
            ORDER BY si.id ASC
        ";

        $itemsStmt = $this->db->query($itemsSql, ['sales_order_id' => $salesOrderId]);
        $items = $itemsStmt->fetchAll();

        return [
            'id' => (int) $order['id'],
            'invoice_number' => $order['invoice_number'],
            'customer_name' => $order['customer_name'],
            'total_amount' => (float) $order['total_amount'],
            'created_at' => $order['created_at'],
            'updated_at' => $order['updated_at'],
            'items' => array_map(function ($item) {
                return [
                    'id' => (int) $item['id'],
                    'product_id' => (int) $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (float) $item['unit_price'],
                    'line_total' => (float) $item['line_total']
                ];
            }, $items)
        ];
    }

    /**
     * Get sales statistics for dashboard
     * 
     * @return array<string, mixed> Sales statistics
     * @throws PDOException If query fails
     */
    public function getSalesStats(): array
    {
        $sql = "
            SELECT 
                COUNT(id) AS total_orders,
                COALESCE(SUM(total_amount), 0) AS total_revenue,
                COALESCE(AVG(total_amount), 0) AS average_order_value
            FROM sales_orders
        ";

        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();

        return [
            'total_orders' => (int) $result['total_orders'],
            'total_revenue' => (float) $result['total_revenue'],
            'average_order_value' => (float) $result['average_order_value']
        ];
    }
}

// Made with Bob