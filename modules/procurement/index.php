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
 * - Full mobile responsiveness
 * - Centralized CSS architecture integration
 * 
 * @package AmenERP\Modules\Procurement
 * @author Bob - Expert Principal Frontend Engineer
 * @version 2.0.0
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
    <!-- Page Header -->
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
                <div id="supplier_id_wrapper" class="erp-form-group" style="display: none;">
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

<!-- External JavaScript Module -->
<script type="module" src="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/assets/js/modules/procurement-form.js"></script>

<?php
// Made with Bob
?>