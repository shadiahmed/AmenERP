<?php
declare(strict_types=1);

/**
 * Procurement Module - Main Interface
 * 
 * Primary administration interface for managing inward inventory purchases.
 * Features:
 * - Dynamic purchase order form with line item management
 * - Real-time total calculation
 * - Recent purchase history display
 * - Procurement statistics dashboard
 * 
 * @package AmenERP\Modules\Procurement
 * @author Bob
 * @version 1.0.0
 */

// Start session for flash messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bootstrap application
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/models/ProcurementModel.php';
require_once __DIR__ . '/../inventory/models/InventoryModel.php';

// Instantiate models
$procurementModel = new ProcurementModel();
$inventoryModel = new InventoryModel();

// Fetch data for the page
$products = $inventoryModel->getAllProducts();
$recentPurchases = $procurementModel->getAllPurchaseOrders(20);
$stats = $procurementModel->getProcurementStats();

// Generate CSRF token
$csrfToken = Csrf::generateToken();

// Get flash messages
$successMessage = $_SESSION['success'] ?? null;
$errorMessage = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<div class="erp-container">
        <!-- Header -->
        <header class="erp-header">
            <h1>📦 Procurement Management</h1>
            <p>Create purchase orders and manage inward inventory</p>
        </header>

        <!-- Flash Messages -->
        <?php if ($successMessage): ?>
            <div class="erp-alert erp-alert-success">
                ✓ <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="erp-alert erp-alert-error">
                ✗ <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Dashboard -->
        <section class="erp-card">
            <div class="erp-card-header">
                <h2>Procurement Statistics</h2>
            </div>
            <div class="erp-stats">
                <div class="erp-stat-item">
                    <div class="erp-stat-label">Total Orders</div>
                    <div class="erp-stat-value"><?php echo number_format($stats['total_orders']); ?></div>
                </div>
                <div class="erp-stat-item">
                    <div class="erp-stat-label">Total Spent</div>
                    <div class="erp-stat-value">$<?php echo number_format($stats['total_spent'], 2); ?></div>
                </div>
                <div class="erp-stat-item">
                    <div class="erp-stat-label">Average Order Value</div>
                    <div class="erp-stat-value">$<?php echo number_format($stats['average_order_value'], 2); ?></div>
                </div>
            </div>
        </section>

        <!-- Main Grid Layout -->
        <div class="erp-grid">
            <!-- Purchase Order Form -->
            <section class="erp-card">
                <div class="erp-card-header">
                    <h2>Create Purchase Order</h2>
                </div>

                <form id="purchaseForm" method="POST" action="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/procurement/orders">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                    <!-- Supplier Name -->
                    <div class="erp-form-group">
                        <label for="supplier_name" class="erp-form-label">Supplier Name *</label>
                        <input 
                            type="text" 
                            id="supplier_name" 
                            name="supplier_name" 
                            class="erp-form-input" 
                            placeholder="Enter supplier or vendor name"
                            required
                            minlength="2"
                            maxlength="255"
                        >
                    </div>

                    <!-- Items Section -->
                    <div class="erp-items-container">
                        <div class="erp-items-header">
                            <div>Product</div>
                            <div>Quantity</div>
                            <div>Unit Cost</div>
                            <div>Line Total</div>
                            <div>Action</div>
                        </div>

                        <div id="itemsContainer">
                            <!-- Items will be added here dynamically -->
                        </div>

                        <button type="button" id="addItemBtn" class="erp-btn erp-btn-secondary erp-btn-sm">
                            + Add Item Row
                        </button>
                    </div>

                    <!-- Total Display -->
                    <div class="erp-total-display">
                        <span class="erp-total-label">Total Amount:</span>
                        <span class="erp-total-value" id="totalAmount">$0.00</span>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="erp-btn erp-btn-primary erp-btn-block">
                        Create Purchase Order
                    </button>
                </form>
            </section>

            <!-- Recent Purchase History -->
            <section class="erp-card">
                <div class="erp-card-header">
                    <h2>Recent Purchase Orders</h2>
                </div>

                <?php if (empty($recentPurchases)): ?>
                    <div class="erp-empty-state">
                        <p>No purchase orders yet. Create your first one!</p>
                    </div>
                <?php else: ?>
                    <div class="erp-table-container">
                        <table class="erp-table">
                            <thead>
                                <tr>
                                    <th>PO Number</th>
                                    <th>Supplier</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPurchases as $purchase): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($purchase['po_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($purchase['supplier_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>$<?php echo number_format($purchase['total_amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($purchase['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <!-- Row Template -->
    <template id="rowTemplate">
        <div class="erp-item-row" data-row-index="">
            <select name="items[INDEX][product_id]" class="erp-form-select item-product" required>
                <option value="">Select Product</option>
                <?php foreach ($products as $product): ?>
                    <option 
                        value="<?php echo (int) $product['id']; ?>"
                        data-price="<?php echo (float) $product['unit_price']; ?>"
                    >
                        <?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?> 
                        (<?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <input 
                type="number" 
                name="items[INDEX][quantity]" 
                class="erp-form-input item-quantity" 
                placeholder="Qty"
                min="1"
                step="1"
                required
            >
            <input 
                type="number" 
                name="items[INDEX][unit_cost]" 
                class="erp-form-input item-cost" 
                placeholder="0.00"
                min="0.01"
                step="0.01"
                required
            >
            <div class="item-line-total">$0.00</div>
            <button type="button" class="erp-btn erp-btn-danger erp-btn-sm remove-item-btn">×</button>
        </div>
    </template>

    <!-- JavaScript Module -->
    <script type="module">
        /**
         * Procurement Form Manager
         * Handles dynamic row management and real-time calculations
         */
        class ProcurementFormManager {
            constructor() {
                this.itemsContainer = document.getElementById('itemsContainer');
                this.addItemBtn = document.getElementById('addItemBtn');
                this.totalAmountDisplay = document.getElementById('totalAmount');
                this.rowTemplate = document.getElementById('rowTemplate');
                this.rowCounter = 0;

                this.init();
            }

            /**
             * Initialize the form manager
             */
            init() {
                // Add initial row
                this.addItemRow();

                // Event listeners
                this.addItemBtn.addEventListener('click', () => this.addItemRow());

                // Event delegation for dynamic elements
                this.itemsContainer.addEventListener('click', (e) => {
                    if (e.target.classList.contains('remove-item-btn')) {
                        this.removeItemRow(e.target);
                    }
                });

                this.itemsContainer.addEventListener('input', (e) => {
                    if (e.target.classList.contains('item-quantity') || 
                        e.target.classList.contains('item-cost')) {
                        this.updateLineTotal(e.target);
                        this.updateGrandTotal();
                    }
                });

                this.itemsContainer.addEventListener('change', (e) => {
                    if (e.target.classList.contains('item-product')) {
                        this.handleProductChange(e.target);
                    }
                });
            }

            /**
             * Add a new item row to the form
             */
            addItemRow() {
                const template = this.rowTemplate.content.cloneNode(true);
                const row = template.querySelector('.erp-item-row');
                
                // Set unique index
                row.dataset.rowIndex = this.rowCounter;
                
                // Update name attributes with actual index
                const inputs = row.querySelectorAll('[name*="INDEX"]');
                inputs.forEach(input => {
                    input.name = input.name.replace('INDEX', this.rowCounter.toString());
                });

                this.itemsContainer.appendChild(row);
                this.rowCounter++;
            }

            /**
             * Remove an item row from the form
             */
            removeItemRow(button) {
                const row = button.closest('.erp-item-row');
                
                // Prevent removing the last row
                const rowCount = this.itemsContainer.querySelectorAll('.erp-item-row').length;
                if (rowCount <= 1) {
                    alert('At least one item is required.');
                    return;
                }

                row.remove();
                this.updateGrandTotal();
            }

            /**
             * Handle product selection change
             */
            handleProductChange(selectElement) {
                const row = selectElement.closest('.erp-item-row');
                const costInput = row.querySelector('.item-cost');
                const selectedOption = selectElement.options[selectElement.selectedIndex];
                
                if (selectedOption.value) {
                    const price = parseFloat(selectedOption.dataset.price) || 0;
                    costInput.value = price.toFixed(2);
                    this.updateLineTotal(costInput);
                    this.updateGrandTotal();
                }
            }

            /**
             * Update line total for a specific row
             */
            updateLineTotal(inputElement) {
                const row = inputElement.closest('.erp-item-row');
                const quantityInput = row.querySelector('.item-quantity');
                const costInput = row.querySelector('.item-cost');
                const lineTotalDisplay = row.querySelector('.item-line-total');

                const quantity = parseFloat(quantityInput.value) || 0;
                const cost = parseFloat(costInput.value) || 0;
                const lineTotal = quantity * cost;

                lineTotalDisplay.textContent = '$' + lineTotal.toFixed(2);
            }

            /**
             * Update the grand total
             */
            updateGrandTotal() {
                const rows = this.itemsContainer.querySelectorAll('.erp-item-row');
                let grandTotal = 0;

                rows.forEach(row => {
                    const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
                    const cost = parseFloat(row.querySelector('.item-cost').value) || 0;
                    grandTotal += quantity * cost;
                });

                this.totalAmountDisplay.textContent = '$' + grandTotal.toFixed(2);
            }
        }

        // Initialize the form manager when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            new ProcurementFormManager();
        });
    </script>
<?php
// Made with Bob
?>