<?php

declare(strict_types=1);

/**
 * ProcurementModel Class
 *
 * Handles all database operations for the procurement module with automated integration.
 * Implements ACID-compliant transactions that span multiple modules:
 * - Creates purchase orders and line items
 * - Updates inventory stock levels automatically (INCREASES quantity)
 * - Records financial transactions directly (double-entry bookkeeping)
 * - Integrates with supplier accounts for credit purchases
 *
 * All operations are wrapped in a single database transaction to ensure data integrity.
 * If any step fails, the entire operation is rolled back.
 *
 * @package AmenERP\Modules\Procurement
 * @author Bob
 * @version 1.0.0
 */
class ProcurementModel
{
    /**
     * Database instance
     *
     * @var Database
     */
    private Database $db;

    /**
     * Default Inventory Asset account ID
     * This account is DEBITED when purchasing inventory (increasing asset value)
     *
     * @var int
     */
    private const INVENTORY_ASSET_ACCOUNT_ID = 2;

    /**
     * Default cash/bank account ID (Cash Safe or Bank Account)
     * This account is CREDITED when making purchases (reducing cash)
     *
     * @var int
     */
    private const CASH_ACCOUNT_ID = 1;

    /**
     * Accounts Payable Liability Account ID
     * This account is CREDITED when making credit purchases (increasing liability)
     *
     * @var int
     */
    private const ACCOUNTS_PAYABLE_ACCOUNT_ID = 6;

    /**
     * Constructor
     * Initializes database connection
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new purchase order with automated multi-module integration
     *
     * This is the core integration method that executes a complete procurement transaction:
     *
     * Action A: Insert master purchase_order and line items into purchase_items
     * Action B: Update inventory stock levels for each product purchased (INCREASE quantity)
     * Action C: Record financial transaction (double-entry bookkeeping)
     * Action D: For credit purchases, update supplier outstanding balance and accounts payable
     *
     * Financial Flow (Cash Purchase):
     * - DEBIT Inventory Asset Account (increases asset value)
     * - CREDIT Cash/Bank Account (reduces cash)
     *
     * Financial Flow (Credit Purchase):
     * - DEBIT Inventory Asset Account (increases asset value)
     * - CREDIT Accounts Payable (increases liability - what we owe)
     * - Update supplier outstanding_balance (increases what we owe to supplier)
     *
     * All operations are wrapped in a database transaction. If any step fails
     * (e.g., product not found, database error), the entire operation is rolled back.
     *
     * @param string $supplierName Supplier or vendor name
     * @param array<int, array<string, mixed>> $items Array of items with keys:
     *                                                - product_id: Product ID (int)
     *                                                - quantity: Quantity to purchase (int)
     *                                                - unit_cost: Cost per unit (float)
     * @param int|null $cashAccountId Optional cash account ID (defaults to CASH_ACCOUNT_ID)
     * @param string $paymentType Payment type: 'cash' or 'credit' (default: 'cash')
     * @param int|null $supplierId Supplier ID for credit purchases (required if paymentType is 'credit')
     * @return array<string, mixed> Result array with keys:
     *                              - success: bool
     *                              - purchase_order_id: int (if successful)
     *                              - po_number: string (if successful)
     *                              - message: string (error message if failed)
     * @throws PDOException If database operation fails
     *
     * @example
     * // Cash purchase
     * $model = new ProcurementModel();
     * $result = $model->createPurchaseOrder('ABC Suppliers Ltd', [
     *     ['product_id' => 1, 'quantity' => 100, 'unit_cost' => 25.00],
     *     ['product_id' => 3, 'quantity' => 50, 'unit_cost' => 75.00]
     * ], null, 'cash');
     *
     * // Credit purchase
     * $result = $model->createPurchaseOrder('ABC Suppliers Ltd', [
     *     ['product_id' => 1, 'quantity' => 100, 'unit_cost' => 25.00]
     * ], null, 'credit', 5);
     *
     * if ($result['success']) {
     *     echo "Purchase order created: " . $result['po_number'];
     * } else {
     *     echo "Error: " . $result['message'];
     * }
     */
    public function createPurchaseOrder(
        string $supplierName,
        array $items,
        ?int $cashAccountId = null,
        string $paymentType = 'cash',
        ?int $supplierId = null
    ): array {
        // Use default cash account if not specified
        $cashAccountId = $cashAccountId ?? self::CASH_ACCOUNT_ID;

        try {
            // Start database transaction for ACID compliance
            $this->db->beginTransaction();

            // Validate payment type
            if (!in_array($paymentType, ['cash', 'credit'], true)) {
                throw new Exception('Invalid payment type. Must be "cash" or "credit"');
            }

            // Validate supplier_id for credit purchases
            if ($paymentType === 'credit' && ($supplierId === null || $supplierId <= 0)) {
                throw new Exception('Supplier ID is required for credit purchases');
            }

            // Validate that we have items to purchase
            if (empty($items)) {
                throw new Exception('Cannot create purchase order: No items provided');
            }

            // Calculate total amount and validate products exist
            $totalAmount = 0.00;
            foreach ($items as $item) {
                // Validate required fields
                if (!isset($item['product_id'], $item['quantity'], $item['unit_cost'])) {
                    throw new Exception('Invalid item data: Missing required fields');
                }

                // Validate quantity is positive
                if ($item['quantity'] <= 0) {
                    throw new Exception('Invalid quantity: Must be greater than zero');
                }

                // Validate unit cost is positive
                if ($item['unit_cost'] <= 0) {
                    throw new Exception('Invalid unit cost: Must be greater than zero');
                }

                // Check that product exists
                $productCheckSql = "SELECT id FROM products WHERE id = :product_id";
                $productStmt = $this->db->query($productCheckSql, ['product_id' => $item['product_id']]);
                $productResult = $productStmt->fetch();

                if (!$productResult) {
                    throw new Exception("Product ID {$item['product_id']} not found");
                }

                // Calculate line total
                $lineTotal = $item['quantity'] * $item['unit_cost'];
                $totalAmount += $lineTotal;
            }

            // Generate unique PO number
            $poNumber = $this->generatePONumber();

            // ACTION A: Insert master purchase order record with supplier integration
            $orderSql = "
                INSERT INTO purchase_orders (
                    po_number,
                    supplier_name,
                    supplier_id,
                    total_amount,
                    payment_type,
                    created_at,
                    updated_at
                ) VALUES (
                    :po_number,
                    :supplier_name,
                    :supplier_id,
                    :total_amount,
                    :payment_type,
                    NOW(),
                    NOW()
                )
            ";

            $this->db->query($orderSql, [
                'po_number' => $poNumber,
                'supplier_name' => $supplierName,
                'supplier_id' => $supplierId,
                'total_amount' => $totalAmount,
                'payment_type' => $paymentType
            ]);

            // Get the purchase order ID
            $purchaseOrderId = (int) $this->db->lastInsertId();

            // ACTION A & B: Insert line items and update inventory
            foreach ($items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_cost'];

                // Insert purchase item
                $itemSql = "
                    INSERT INTO purchase_items (
                        purchase_order_id,
                        product_id,
                        quantity,
                        unit_cost,
                        line_total,
                        created_at,
                        updated_at
                    ) VALUES (
                        :purchase_order_id,
                        :product_id,
                        :quantity,
                        :unit_cost,
                        :line_total,
                        NOW(),
                        NOW()
                    )
                ";

                $this->db->query($itemSql, [
                    'purchase_order_id' => $purchaseOrderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $lineTotal
                ]);

                // ACTION B: Update inventory stock (INCREASE quantity - opposite of sales)
                $inventoryUpdateSql = "
                    UPDATE products
                    SET 
                        quantity = quantity + :purchased_qty,
                        updated_at = NOW()
                    WHERE id = :product_id
                ";

                $this->db->query($inventoryUpdateSql, [
                    'purchased_qty' => $item['quantity'],
                    'product_id' => $item['product_id']
                ]);
            }

            // ACTION C: Record financial transaction based on payment type
            $transactionDescription = "Purchase Order: {$poNumber} - Supplier: {$supplierName}";
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

            // Insert debit entry for Inventory Asset account (asset increasing)
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
                'account_id' => self::INVENTORY_ASSET_ACCOUNT_ID,
                'amount' => $totalAmount // Positive for debit (asset increasing)
            ]);

            if ($paymentType === 'cash') {
                // CASH PURCHASE: CREDIT Cash account (cash decreasing)
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
                    'amount' => -$totalAmount // Negative for credit (cash leaving)
                ]);

                // Update Cash account balance (subtract)
                $updateCashSql = "
                    UPDATE accounts
                    SET
                        balance = balance - :amount,
                        updated_at = NOW()
                    WHERE id = :account_id
                ";

                $this->db->query($updateCashSql, [
                    'amount' => $totalAmount,
                    'account_id' => $cashAccountId
                ]);
            } else {
                // CREDIT PURCHASE: CREDIT Accounts Payable (liability increasing)
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
                    'account_id' => self::ACCOUNTS_PAYABLE_ACCOUNT_ID,
                    'amount' => -$totalAmount // Negative for credit (liability increasing)
                ]);

                // Update Accounts Payable account balance (add)
                $updatePayableSql = "
                    UPDATE accounts
                    SET
                        balance = balance + :amount,
                        updated_at = NOW()
                    WHERE id = :account_id
                ";

                $this->db->query($updatePayableSql, [
                    'amount' => $totalAmount,
                    'account_id' => self::ACCOUNTS_PAYABLE_ACCOUNT_ID
                ]);

                // ACTION D: Update supplier outstanding balance
                $updateSupplierSql = "
                    UPDATE suppliers
                    SET
                        outstanding_balance = outstanding_balance + :amount,
                        updated_at = NOW()
                    WHERE id = :supplier_id
                ";

                $this->db->query($updateSupplierSql, [
                    'amount' => $totalAmount,
                    'supplier_id' => $supplierId
                ]);
            }

            // Update Inventory Asset account balance (add) - applies to both cash and credit
            $updateInventorySql = "
                UPDATE accounts
                SET
                    balance = balance + :amount,
                    updated_at = NOW()
                WHERE id = :account_id
            ";

            $this->db->query($updateInventorySql, [
                'amount' => $totalAmount,
                'account_id' => self::INVENTORY_ASSET_ACCOUNT_ID
            ]);

            // Commit the transaction - all operations succeeded
            $this->db->commit();

            return [
                'success' => true,
                'purchase_order_id' => $purchaseOrderId,
                'po_number' => $poNumber,
                'total_amount' => $totalAmount,
                'payment_type' => $paymentType,
                'supplier_id' => $supplierId,
                'supplier_name' => $supplierName,
                'message' => 'Purchase order created successfully'
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
     * Generate a unique purchase order number
     * 
     * Format: PO-YYYY-NNNN (e.g., PO-2026-0001)
     * 
     * @return string Unique PO number
     * @throws PDOException If query fails
     */
    private function generatePONumber(): string
    {
        $year = date('Y');
        $prefix = "PO-{$year}-";

        // Get the highest PO number for current year
        $sql = "
            SELECT po_number
            FROM purchase_orders
            WHERE po_number LIKE :prefix
            ORDER BY po_number DESC
            LIMIT 1
        ";

        $stmt = $this->db->query($sql, ['prefix' => $prefix . '%']);
        $result = $stmt->fetch();

        if ($result) {
            // Extract the numeric part and increment
            $lastNumber = (int) substr($result['po_number'], -4);
            $nextNumber = $lastNumber + 1;
        } else {
            // First PO of the year
            $nextNumber = 1;
        }

        // Format with leading zeros (4 digits)
        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get all purchase orders with summary information
     * 
     * @param int $limit Maximum number of orders to return (default: 50)
     * @return array<int, array<string, mixed>> Array of purchase orders
     * @throws PDOException If query fails
     */
    public function getAllPurchaseOrders(int $limit = 50): array
    {
        $sql = "
            SELECT 
                id,
                po_number,
                supplier_name,
                total_amount,
                created_at,
                updated_at
            FROM purchase_orders
            ORDER BY created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->db->query($sql, ['limit' => $limit]);
        $results = $stmt->fetchAll();

        // Cast numeric values
        return array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'po_number' => $row['po_number'],
                'supplier_name' => $row['supplier_name'],
                'total_amount' => (float) $row['total_amount'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }, $results);
    }

    /**
     * Get purchase order details with line items
     * 
     * @param int $purchaseOrderId Purchase order ID
     * @return array<string, mixed>|null Order details with items array, or null if not found
     * @throws PDOException If query fails
     */
    public function getPurchaseOrderById(int $purchaseOrderId): ?array
    {
        // Get order header
        $orderSql = "
            SELECT 
                id,
                po_number,
                supplier_name,
                total_amount,
                created_at,
                updated_at
            FROM purchase_orders
            WHERE id = :purchase_order_id
        ";

        $orderStmt = $this->db->query($orderSql, ['purchase_order_id' => $purchaseOrderId]);
        $order = $orderStmt->fetch();

        if (!$order) {
            return null;
        }

        // Get line items
        $itemsSql = "
            SELECT 
                pi.id,
                pi.product_id,
                pi.quantity,
                pi.unit_cost,
                pi.line_total,
                p.name AS product_name,
                p.sku AS product_sku
            FROM purchase_items pi
            INNER JOIN products p ON pi.product_id = p.id
            WHERE pi.purchase_order_id = :purchase_order_id
            ORDER BY pi.id ASC
        ";

        $itemsStmt = $this->db->query($itemsSql, ['purchase_order_id' => $purchaseOrderId]);
        $items = $itemsStmt->fetchAll();

        return [
            'id' => (int) $order['id'],
            'po_number' => $order['po_number'],
            'supplier_name' => $order['supplier_name'],
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
                    'unit_cost' => (float) $item['unit_cost'],
                    'line_total' => (float) $item['line_total']
                ];
            }, $items)
        ];
    }

    /**
     * Get procurement statistics for dashboard
     * 
     * @return array<string, mixed> Procurement statistics
     * @throws PDOException If query fails
     */
    public function getProcurementStats(): array
    {
        $sql = "
            SELECT 
                COUNT(id) AS total_orders,
                COALESCE(SUM(total_amount), 0) AS total_spent,
                COALESCE(AVG(total_amount), 0) AS average_order_value
            FROM purchase_orders
        ";

        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();

        return [
            'total_orders' => (int) $result['total_orders'],
            'total_spent' => (float) $result['total_spent'],
            'average_order_value' => (float) $result['average_order_value']
        ];
    }

    /**
     * Get purchase orders by supplier
     * 
     * @param string $supplierName Supplier name to filter by
     * @param int $limit Maximum number of orders to return (default: 50)
     * @return array<int, array<string, mixed>> Array of purchase orders
     * @throws PDOException If query fails
     */
    public function getPurchaseOrdersBySupplier(string $supplierName, int $limit = 50): array
    {
        $sql = "
            SELECT 
                id,
                po_number,
                supplier_name,
                total_amount,
                created_at,
                updated_at
            FROM purchase_orders
            WHERE supplier_name LIKE :supplier_name
            ORDER BY created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->db->query($sql, [
            'supplier_name' => '%' . $supplierName . '%',
            'limit' => $limit
        ]);
        $results = $stmt->fetchAll();

        // Cast numeric values
        return array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'po_number' => $row['po_number'],
                'supplier_name' => $row['supplier_name'],
                'total_amount' => (float) $row['total_amount'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }, $results);
    }
}

// Made with Bob