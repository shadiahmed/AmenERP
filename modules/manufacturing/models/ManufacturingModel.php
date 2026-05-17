<?php
declare(strict_types=1);

/**
 * Manufacturing Model
 * 
 * Core business logic for manufacturing and production operations.
 * Handles Bill of Materials (BOM) management, production runs, inventory
 * consumption, and financial accounting integration.
 * 
 * @package AmenERP\Manufacturing
 * @author Principal Software Engineer
 * @version 1.0.0
 */

require_once __DIR__ . '/../../../core/Database.php';

class ManufacturingModel
{
    /**
     * Chart of Accounts Configuration
     * These constants define the financial account IDs used for manufacturing transactions
     */
// These IDs must exist in modules/finance/database/schema.sql `accounts`.
// If your finance schema uses different IDs, update these accordingly.
private const RAW_MATERIALS_ACCOUNT_ID = 1;    // Cash Safe (example fallback)
private const WIP_ACCOUNT_ID = 2;              // Bank Account (example fallback)
private const FINISHED_GOODS_ACCOUNT_ID = 1;   // Cash Safe (example fallback)

    /**
     * @var PDO Database connection instance
     */
    private PDO $db;

    /**
     * Constructor
     * 
     * Initializes the model with a database connection from the central registry.
     */
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Start Production Run
     * 
     * Initiates a new production run by:
     * 1. Validating the BOM exists and retrieving component requirements
     * 2. Verifying sufficient raw material inventory is available
     * 3. Deducting raw materials from inventory
     * 4. Creating the production run record with 'in_progress' status
     * 5. Recording financial journal entries (Raw Materials → WIP)
     * 
     * This operation is wrapped in a database transaction to ensure ACID compliance.
     * 
     * @param int $bomId The Bill of Materials ID to produce
     * @param int $quantity The number of units to produce
     * 
     * @return array Success response with production run details
     * 
     * @throws Exception If BOM not found, insufficient materials, or database error
     */
    public function startProductionRun(int $bomId, int $quantity): array
    {
        try {
            // Begin transaction for ACID compliance
            $this->db->beginTransaction();

            // Step 1: Validate BOM exists and retrieve details
            $bomQuery = "SELECT bom.*, p.name as product_name, p.unit_price as product_price 
                        FROM bills_of_materials bom
                        INNER JOIN products p ON bom.product_id = p.id
                        WHERE bom.id = :bom_id";
            $bomStmt = $this->db->prepare($bomQuery);
            $bomStmt->execute([':bom_id' => $bomId]);
            $bom = $bomStmt->fetch(PDO::FETCH_ASSOC);

            if (!$bom) {
                throw new Exception("Bill of Materials with ID {$bomId} not found");
            }

            // Step 2: Retrieve all component requirements for this BOM
            $componentsQuery = "SELECT bi.*, p.name as component_name, p.quantity as available_quantity, p.unit_price as component_price
                               FROM bom_items bi
                               INNER JOIN products p ON bi.component_id = p.id
                               WHERE bi.bom_id = :bom_id";
            $componentsStmt = $this->db->prepare($componentsQuery);
            $componentsStmt->execute([':bom_id' => $bomId]);
            $components = $componentsStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($components)) {
                throw new Exception("No components defined for this BOM");
            }

            // Step 3: Verify sufficient inventory for all components
            $totalRawMaterialValue = 0;
            foreach ($components as $component) {
                $requiredQuantity = $component['quantity_required'] * $quantity;
                $availableQuantity = $component['available_quantity'];

                if ($availableQuantity < $requiredQuantity) {
                    throw new Exception(
                        "Insufficient inventory for component '{$component['component_name']}'. " .
                        "Required: {$requiredQuantity}, Available: {$availableQuantity}"
                    );
                }

                // Calculate total raw material value for financial entries
                $totalRawMaterialValue += ($component['component_price'] * $requiredQuantity);
            }

            // Step 4: Deduct raw materials from inventory
            foreach ($components as $component) {
                $requiredQuantity = $component['quantity_required'] * $quantity;
                
                $updateInventoryQuery = "UPDATE products 
                                        SET quantity = quantity - :quantity,
                                            updated_at = CURRENT_TIMESTAMP
                                        WHERE id = :component_id";
                $updateInventoryStmt = $this->db->prepare($updateInventoryQuery);
                $updateInventoryStmt->execute([
                    ':quantity' => $requiredQuantity,
                    ':component_id' => $component['component_id']
                ]);
            }

            // Step 5: Generate unique batch number
            $batchNumber = 'BATCH-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            // Step 6: Create production run record
            $createRunQuery = "INSERT INTO production_runs 
                              (bom_id, batch_number, quantity_to_produce, status, created_at, updated_at)
                              VALUES (:bom_id, :batch_number, :quantity, 'in_progress', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $createRunStmt = $this->db->prepare($createRunQuery);
            $createRunStmt->execute([
                ':bom_id' => $bomId,
                ':batch_number' => $batchNumber,
                ':quantity' => $quantity
            ]);
            $runId = (int)$this->db->lastInsertId();

            // Step 7: Record financial journal entries (Raw Materials → WIP)
            $this->recordJournalEntry(
                self::RAW_MATERIALS_ACCOUNT_ID,
                self::WIP_ACCOUNT_ID,
                $totalRawMaterialValue,
                "Production run {$batchNumber} started - Raw materials transferred to WIP"
            );

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Production run started successfully',
                'data' => [
                    'run_id' => $runId,
                    'batch_number' => $batchNumber,
                    'bom_id' => $bomId,
                    'product_name' => $bom['product_name'],
                    'quantity_to_produce' => $quantity,
                    'status' => 'in_progress',
                    'raw_material_value' => $totalRawMaterialValue,
                    'components_consumed' => count($components)
                ]
            ];

        } catch (Exception $e) {
            // Rollback transaction on any error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            return [
                'success' => false,
                'message' => 'Failed to start production run: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Complete Production Run
     * 
     * Finalizes a production run by:
     * 1. Validating the run exists and is in 'in_progress' status
     * 2. Retrieving the BOM and finished product details
     * 3. Incrementing finished goods inventory
     * 4. Updating production run status to 'completed'
     * 5. Recording financial journal entries (WIP → Finished Goods)
     * 
     * This operation is wrapped in a database transaction to ensure ACID compliance.
     * 
     * @param int $runId The production run ID to complete
     * 
     * @return array Success response with completion details
     * 
     * @throws Exception If run not found, invalid status, or database error
     */
    public function completeProductionRun(int $runId): array
    {
        try {
            // Begin transaction for ACID compliance
            $this->db->beginTransaction();

            // Step 1: Validate production run exists and retrieve details
            $runQuery = "SELECT pr.*, bom.product_id, bom.name as bom_name, p.name as product_name, p.unit_price as product_price
                        FROM production_runs pr
                        INNER JOIN bills_of_materials bom ON pr.bom_id = bom.id
                        INNER JOIN products p ON bom.product_id = p.id
                        WHERE pr.id = :run_id";
            $runStmt = $this->db->prepare($runQuery);
            $runStmt->execute([':run_id' => $runId]);
            $run = $runStmt->fetch(PDO::FETCH_ASSOC);

            if (!$run) {
                throw new Exception("Production run with ID {$runId} not found");
            }

            // Step 2: Verify run is in 'in_progress' status
            if ($run['status'] !== 'in_progress') {
                throw new Exception(
                    "Cannot complete production run. Current status: {$run['status']}. " .
                    "Only runs with 'in_progress' status can be completed."
                );
            }

            // Step 3: Calculate finished goods value
            // Value equals the product price multiplied by quantity produced
            $finishedGoodsValue = $run['product_price'] * $run['quantity_to_produce'];

            // Step 4: Increment finished product inventory
            $updateInventoryQuery = "UPDATE products 
                                    SET quantity = quantity + :quantity,
                                        updated_at = CURRENT_TIMESTAMP
                                    WHERE id = :product_id";
            $updateInventoryStmt = $this->db->prepare($updateInventoryQuery);
            $updateInventoryStmt->execute([
                ':quantity' => $run['quantity_to_produce'],
                ':product_id' => $run['product_id']
            ]);

            // Step 5: Update production run status to 'completed'
            $updateRunQuery = "UPDATE production_runs 
                              SET status = 'completed',
                                  updated_at = CURRENT_TIMESTAMP
                              WHERE id = :run_id";
            $updateRunStmt = $this->db->prepare($updateRunQuery);
            $updateRunStmt->execute([':run_id' => $runId]);

            // Step 6: Record financial journal entries (WIP → Finished Goods)
            $this->recordJournalEntry(
                self::WIP_ACCOUNT_ID,
                self::FINISHED_GOODS_ACCOUNT_ID,
                $finishedGoodsValue,
                "Production run {$run['batch_number']} completed - WIP transferred to Finished Goods"
            );

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Production run completed successfully',
                'data' => [
                    'run_id' => $runId,
                    'batch_number' => $run['batch_number'],
                    'product_name' => $run['product_name'],
                    'quantity_produced' => $run['quantity_to_produce'],
                    'status' => 'completed',
                    'finished_goods_value' => $finishedGoodsValue
                ]
            ];

        } catch (Exception $e) {
            // Rollback transaction on any error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            return [
                'success' => false,
                'message' => 'Failed to complete production run: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Record Journal Entry
     * 
     * Creates a double-entry accounting journal entry for manufacturing transactions.
     * Records both debit and credit entries to maintain accounting balance.
     * 
     * @param int $fromAccountId Source account ID (will be credited)
     * @param int $toAccountId Destination account ID (will be debited)
     * @param float $amount Transaction amount
     * @param string $description Transaction description
     * 
     * @return void
     * 
     * @throws Exception If journal entry creation fails
     */
    private function recordJournalEntry(
        int $fromAccountId,
        int $toAccountId,
        float $amount,
        string $description
    ): void {
        // Finance module uses these tables:
        // - transactions
        // - ledger_entries
        // Not journal_entries/transactions[type] style.
        // Implement production journal as a double-entry transaction:
        //   debit/credit amounts are encoded using sign on ledger_entries.amount

        // Create transaction header
        $transactionSql = "INSERT INTO transactions (description, transaction_date, created_at, updated_at)
                           VALUES (:description, CURDATE(), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $transactionStmt = $this->db->prepare($transactionSql);
        $transactionStmt->execute([':description' => $description]);
        $transactionId = (int)$this->db->lastInsertId();

        // Debit entry (money enters destination asset): positive amount
        $creditSql = "INSERT INTO ledger_entries
                       (transaction_id, account_id, amount, created_at, updated_at)
                       VALUES (:transaction_id, :account_id, :amount, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $stmt = $this->db->prepare($creditSql);

        // For ledger_entries:
        // - amount > 0 is debit (as used by FinanceModel::addSimpleTransaction)
        // Manufacturing transfer Raw Materials -> WIP:
        // - source account decreases => debit with negative amount
        // - destination account increases => debit with positive amount

        // Source (fromAccount) decrease: negative amount
        $stmt->execute([
            ':transaction_id' => $transactionId,
            ':account_id' => $fromAccountId,
            ':amount' => -$amount
        ]);

        // Destination (toAccount) increase: positive amount
        $stmt->execute([
            ':transaction_id' => $transactionId,
            ':account_id' => $toAccountId,
            ':amount' => $amount
        ]);
    }

    /**
     * Get Production Run Details
     * 
     * Retrieves comprehensive details about a specific production run including
     * BOM information, product details, and current status.
     * 
     * @param int $runId The production run ID
     * 
     * @return array|null Production run details or null if not found
     */
    public function getProductionRunDetails(int $runId): ?array
    {
        $query = "SELECT pr.*, 
                         bom.bom_code, bom.name as bom_name,
                         p.name as product_name, p.sku as product_sku
                  FROM production_runs pr
                  INNER JOIN bills_of_materials bom ON pr.bom_id = bom.id
                  INNER JOIN products p ON bom.product_id = p.id
                  WHERE pr.id = :run_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':run_id' => $runId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get All Production Runs
     * 
     * Retrieves a list of all production runs with optional status filtering.
     * 
     * @param string|null $status Optional status filter ('pending', 'in_progress', 'completed', 'cancelled')
     * 
     * @return array List of production runs
     */
    public function getAllProductionRuns(?string $status = null): array
    {
        $query = "SELECT pr.*, 
                         bom.bom_code, bom.name as bom_name,
                         p.name as product_name, p.sku as product_sku
                  FROM production_runs pr
                  INNER JOIN bills_of_materials bom ON pr.bom_id = bom.id
                  INNER JOIN products p ON bom.product_id = p.id";
        
        if ($status !== null) {
            $query .= " WHERE pr.status = :status";
        }
        
        $query .= " ORDER BY pr.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        
        if ($status !== null) {
            $stmt->execute([':status' => $status]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get BOM Details
     * 
     * Retrieves complete Bill of Materials information including all components.
     * 
     * @param int $bomId The BOM ID
     * 
     * @return array|null BOM details with components or null if not found
     */
    public function getBOMDetails(int $bomId): ?array
    {
        // Get BOM header
        $bomQuery = "SELECT bom.*, p.name as product_name, p.sku as product_sku
                    FROM bills_of_materials bom
                    INNER JOIN products p ON bom.product_id = p.id
                    WHERE bom.id = :bom_id";
        
        $bomStmt = $this->db->prepare($bomQuery);
        $bomStmt->execute([':bom_id' => $bomId]);
        $bom = $bomStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$bom) {
            return null;
        }
        
        // Get BOM components
        $componentsQuery = "SELECT bi.*, p.name as component_name, p.sku as component_sku, p.quantity as available_quantity
                           FROM bom_items bi
                           INNER JOIN products p ON bi.component_id = p.id
                           WHERE bi.bom_id = :bom_id";
        
        $componentsStmt = $this->db->prepare($componentsQuery);
        $componentsStmt->execute([':bom_id' => $bomId]);
        $bom['components'] = $componentsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $bom;
    }

    /**
     * Cancel Production Run
     * 
     * Cancels a production run that is in 'pending' or 'in_progress' status.
     * For 'in_progress' runs, this will reverse the raw material consumption
     * and WIP accounting entries.
     * 
     * @param int $runId The production run ID to cancel
     * 
     * @return array Success or error response
     */
    public function cancelProductionRun(int $runId): array
    {
        try {
            $this->db->beginTransaction();

            // Get run details
            $run = $this->getProductionRunDetails($runId);
            
            if (!$run) {
                throw new Exception("Production run with ID {$runId} not found");
            }

            if ($run['status'] === 'completed') {
                throw new Exception("Cannot cancel a completed production run");
            }

            if ($run['status'] === 'cancelled') {
                throw new Exception("Production run is already cancelled");
            }

            // If run was in progress, reverse the material consumption
            if ($run['status'] === 'in_progress') {
                // Get components
            $componentsQuery = "SELECT bi.*, p.unit_price as component_price
                                   FROM bom_items bi
                                   INNER JOIN products p ON bi.component_id = p.id
                                   WHERE bi.bom_id = :bom_id";
                $componentsStmt = $this->db->prepare($componentsQuery);
                $componentsStmt->execute([':bom_id' => $run['bom_id']]);
                $components = $componentsStmt->fetchAll(PDO::FETCH_ASSOC);

                $totalRawMaterialValue = 0;

                // Return materials to inventory
                foreach ($components as $component) {
                    $requiredQuantity = $component['quantity_required'] * $run['quantity_to_produce'];
                    
                    $updateInventoryQuery = "UPDATE products 
                                            SET quantity = quantity + :quantity,
                                                updated_at = CURRENT_TIMESTAMP
                                            WHERE id = :component_id";
                    $updateInventoryStmt = $this->db->prepare($updateInventoryQuery);
                    $updateInventoryStmt->execute([
                        ':quantity' => $requiredQuantity,
                        ':component_id' => $component['component_id']
                    ]);

                    $totalRawMaterialValue += ($component['component_price'] * $requiredQuantity);
                }

                // Reverse financial entries (WIP → Raw Materials)
                $this->recordJournalEntry(
                    self::WIP_ACCOUNT_ID,
                    self::RAW_MATERIALS_ACCOUNT_ID,
                    $totalRawMaterialValue,
                    "Production run {$run['batch_number']} cancelled - WIP returned to Raw Materials"
                );
            }

            // Update run status to cancelled
            $updateRunQuery = "UPDATE production_runs 
                              SET status = 'cancelled',
                                  updated_at = CURRENT_TIMESTAMP
                              WHERE id = :run_id";
            $updateRunStmt = $this->db->prepare($updateRunQuery);
            $updateRunStmt->execute([':run_id' => $runId]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Production run cancelled successfully',
                'data' => [
                    'run_id' => $runId,
                    'batch_number' => $run['batch_number'],
                    'status' => 'cancelled'
                ]
            ];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            return [
                'success' => false,
                'message' => 'Failed to cancel production run: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create Bill of Materials
     *
     * Creates a new Bill of Materials with component mappings in a single transaction.
     *
     * Process:
     * 1. Validates the finished product exists
     * 2. Generates a unique BOM code (BOM-YYYYMMDD-HHMMSS)
     * 3. Inserts BOM header record
     * 4. Inserts all component line items with quantities
     * 5. Validates all quantities are greater than zero
     *
     * This operation is wrapped in a database transaction to ensure ACID compliance.
     *
     * @param int $productId The finished product ID
     * @param string $name Descriptive name for this BOM
     * @param array $components Array of component product IDs
     * @param array $quantities Array of required quantities (matching components array)
     *
     * @return array Success response with BOM details
     *
     * @throws Exception If product not found, duplicate BOM code, or database error
     */
    public function createBillOfMaterials(int $productId, string $name, array $components, array $quantities): array
    {
        try {
            // Begin transaction for ACID compliance
            $this->db->beginTransaction();

            // Step 1: Validate finished product exists
            $productQuery = "SELECT id, name, sku FROM products WHERE id = :product_id";
            $productStmt = $this->db->prepare($productQuery);
            $productStmt->execute([':product_id' => $productId]);
            $product = $productStmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new Exception("Product with ID {$productId} not found");
            }

            // Step 2: Generate unique BOM code with timestamp
            $bomCode = 'BOM-' . date('Ymd-His');

            // Verify BOM code uniqueness (extremely unlikely collision, but safety check)
            $checkCodeQuery = "SELECT id FROM bills_of_materials WHERE bom_code = :bom_code";
            $checkCodeStmt = $this->db->prepare($checkCodeQuery);
            $checkCodeStmt->execute([':bom_code' => $bomCode]);
            
            if ($checkCodeStmt->fetch()) {
                // If collision occurs, append microseconds
                $bomCode .= '-' . substr((string)microtime(true), -6);
            }

            // Step 3: Insert BOM header record
            $insertBomQuery = "INSERT INTO bills_of_materials
                              (product_id, bom_code, name, created_at, updated_at)
                              VALUES (:product_id, :bom_code, :name, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $insertBomStmt = $this->db->prepare($insertBomQuery);
            $insertBomStmt->execute([
                ':product_id' => $productId,
                ':bom_code' => $bomCode,
                ':name' => $name
            ]);
            $bomId = (int)$this->db->lastInsertId();

            // Step 4: Insert component line items
            $insertItemQuery = "INSERT INTO bom_items
                               (bom_id, component_id, quantity_required, created_at, updated_at)
                               VALUES (:bom_id, :component_id, :quantity_required, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $insertItemStmt = $this->db->prepare($insertItemQuery);

            $componentCount = 0;
            foreach ($components as $index => $componentId) {
                $quantity = $quantities[$index];

                // Step 5: Assert quantity is greater than zero
                if ($quantity <= 0) {
                    throw new Exception("Component quantity must be greater than zero for component ID {$componentId}");
                }

                // Verify component exists
                $componentCheckQuery = "SELECT id, name FROM products WHERE id = :component_id";
                $componentCheckStmt = $this->db->prepare($componentCheckQuery);
                $componentCheckStmt->execute([':component_id' => $componentId]);
                $component = $componentCheckStmt->fetch(PDO::FETCH_ASSOC);

                if (!$component) {
                    throw new Exception("Component with ID {$componentId} not found");
                }

                // Insert component item
                $insertItemStmt->execute([
                    ':bom_id' => $bomId,
                    ':component_id' => $componentId,
                    ':quantity_required' => $quantity
                ]);

                $componentCount++;
            }

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Bill of Materials created successfully',
                'data' => [
                    'bom_id' => $bomId,
                    'bom_code' => $bomCode,
                    'product_id' => $productId,
                    'product_name' => $product['name'],
                    'product_sku' => $product['sku'],
                    'bom_name' => $name,
                    'component_count' => $componentCount
                ]
            ];

        } catch (Exception $e) {
            // Rollback transaction on any error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            return [
                'success' => false,
                'message' => 'Failed to create Bill of Materials: ' . $e->getMessage()
            ];
        }
    }
}

// Made with Bob
