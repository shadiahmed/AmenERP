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
require_once __DIR__ . '/../suppliers/models/SupplierModel.php';

// Instantiate models
$procurementModel = new ProcurementModel();
$inventoryModel = new InventoryModel();
$supplierModel = new SupplierModel();

// Fetch data for the page
$products = $inventoryModel->getAllProducts();
$recentPurchases = $procurementModel->getAllPurchaseOrders(20);
$stats = $procurementModel->getProcurementStats();
$suppliers = $supplierModel->getAllSuppliers('active');

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

                    <!-- Payment Type Selection -->
                    <div class="erp-form-group">
                        <label for="payment_type" class="erp-form-label">Payment Type *</label>
                        <select
                            id="payment_type"
                            name="payment_type"
                            class="erp-form-select"
                            required
                        >
                            <option value="cash">Direct Cash Purchase</option>
                            <option value="credit">On-Account Vendor Credit</option>
                        </select>
                    </div>

                    <!-- Supplier Selection (for credit purchases) -->
                    <div id="supplier_id_wrapper" class="erp-form-group display-none">
                        <label for="supplier_id" class="erp-form-label">Select Supplier *</label>
                        <select
                            id="supplier_id"
                            name="supplier_id"
                            class="erp-form-select"
                        >
                            <option value="">-- Select a Supplier --</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option
                                    value="<?php echo (int) $supplier['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($supplier['supplier_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-balance="<?php echo (float) $supplier['outstanding_balance']; ?>"
                                    data-credit-available="<?php echo (float) $supplier['credit_available']; ?>"
                                >
                                    <?php echo htmlspecialchars($supplier['supplier_code'], ENT_QUOTES, 'UTF-8'); ?> -
                                    <?php echo htmlspecialchars($supplier['supplier_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    (Balance: $<?php echo number_format($supplier['outstanding_balance'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="erp-form-help">Select the supplier for this credit purchase</small>
                    </div>

                    <!-- Supplier Name (hidden field for form submission) -->
                    <input type="hidden" id="supplier_name" name="supplier_name" value="">

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
         * Handles dynamic row management, real-time calculations, and supplier integration
         */
        class ProcurementFormManager {
            constructor() {
                this.itemsContainer = document.getElementById('itemsContainer');
                this.addItemBtn = document.getElementById('addItemBtn');
                this.totalAmountDisplay = document.getElementById('totalAmount');
                this.rowTemplate = document.getElementById('rowTemplate');
                this.rowCounter = 0;
                
                // Supplier integration elements
                this.paymentTypeSelect = document.getElementById('payment_type');
                this.supplierIdWrapper = document.getElementById('supplier_id_wrapper');
                this.supplierIdSelect = document.getElementById('supplier_id');
                this.supplierNameInput = document.getElementById('supplier_name');
                this.purchaseForm = document.getElementById('purchaseForm');

                this.init();
            }

            /**
             * Initialize the form manager
             */
            init() {
                // Add initial row
                this.addItemRow();

                // Event listeners for items
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

                // Event listeners for supplier integration
                this.paymentTypeSelect.addEventListener('change', () => this.handlePaymentTypeChange());
                this.supplierIdSelect.addEventListener('change', () => this.handleSupplierChange());
                
                // Form submission validation
                this.purchaseForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
            }

            /**
             * Handle payment type change (cash vs credit)
             */
            handlePaymentTypeChange() {
                const paymentType = this.paymentTypeSelect.value;
                
                if (paymentType === 'credit') {
                    // Show supplier dropdown and make it required
                    this.supplierIdWrapper.style.display = 'block';
                    this.supplierIdSelect.setAttribute('required', 'required');
                } else {
                    // Hide supplier dropdown and remove required attribute
                    this.supplierIdWrapper.style.display = 'none';
                    this.supplierIdSelect.removeAttribute('required');
                    this.supplierIdSelect.value = '';
                    this.supplierNameInput.value = '';
                }
            }

            /**
             * Handle supplier selection change
             */
            handleSupplierChange() {
                const selectedOption = this.supplierIdSelect.options[this.supplierIdSelect.selectedIndex];
                
                if (selectedOption.value) {
                    // Update hidden supplier name field
                    const supplierName = selectedOption.dataset.name || '';
                    this.supplierNameInput.value = supplierName;
                    
                    // Optional: Display supplier credit information
                    const creditAvailable = parseFloat(selectedOption.dataset.creditAvailable) || 0;
                    const currentBalance = parseFloat(selectedOption.dataset.balance) || 0;
                    
                    console.log('Supplier selected:', {
                        name: supplierName,
                        creditAvailable: creditAvailable,
                        currentBalance: currentBalance
                    });
                } else {
                    this.supplierNameInput.value = '';
                }
            }

            /**
             * Handle form submission validation
             */
            handleFormSubmit(e) {
                const paymentType = this.paymentTypeSelect.value;
                const totalAmount = this.calculateGrandTotal();
                
                // Validate credit purchase requirements
                if (paymentType === 'credit') {
                    const supplierId = this.supplierIdSelect.value;
                    
                    if (!supplierId) {
                        e.preventDefault();
                        alert('Please select a supplier for credit purchases.');
                        return false;
                    }
                    
                    // Optional: Check credit limit
                    const selectedOption = this.supplierIdSelect.options[this.supplierIdSelect.selectedIndex];
                    const creditAvailable = parseFloat(selectedOption.dataset.creditAvailable) || 0;
                    
                    if (creditAvailable > 0 && totalAmount > creditAvailable) {
                        const confirmMsg = `Warning: Purchase amount ($${totalAmount.toFixed(2)}) exceeds available credit ($${creditAvailable.toFixed(2)}). Continue anyway?`;
                        if (!confirm(confirmMsg)) {
                            e.preventDefault();
                            return false;
                        }
                    }
                }
                
                // Validate at least one item
                const rows = this.itemsContainer.querySelectorAll('.erp-item-row');
                if (rows.length === 0) {
                    e.preventDefault();
                    alert('Please add at least one item to the purchase order.');
                    return false;
                }
                
                return true;
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
             * Calculate the grand total (helper method)
             */
            calculateGrandTotal() {
                const rows = this.itemsContainer.querySelectorAll('.erp-item-row');
                let grandTotal = 0;

                rows.forEach(row => {
                    const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
                    const cost = parseFloat(row.querySelector('.item-cost').value) || 0;
                    grandTotal += quantity * cost;
                });

                return grandTotal;
            }

            /**
             * Update the grand total display
             */
            updateGrandTotal() {
                const grandTotal = this.calculateGrandTotal();
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