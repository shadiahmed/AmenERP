<?php
/**
 * Sales Module - Main Dashboard View
 *
 * Displays new sales form with support for both Cash Sales and B2B Credit Customers.
 * Implements real-time calculations, stock validation, credit limit checking, and seamless multi-module integration.
 *
 * Features:
 * - Dual payment type support (Cash/Credit)
 * - Dynamic customer selection with credit limit tracking
 * - Dynamic item rows with add/remove functionality
 * - Real-time price and total calculations
 * - Credit boundary validation
 * - Stock availability validation
 * - CSRF protection
 * - Responsive mobile-first design
 *
 * @package AmenERP\Modules\Sales
 * @author Bob
 * @version 2.0.0
 */

declare(strict_types=1);

// ============================================================================
// BOOTSTRAP: Core Configuration & Dependencies
// ============================================================================

/**
 * Ensure session is active (should already be started by front controller)
 * This is a safety check for direct access scenarios
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Load required core classes
 * These are autoloaded by the front controller, but we verify they exist
 */
if (!class_exists('Database')) {
    require_once __DIR__ . '/../../core/Database.php';
}

if (!class_exists('Csrf')) {
    require_once __DIR__ . '/../../core/Csrf.php';
}

/**
 * Load required models
 * SalesModel: Handles sales order creation and retrieval
 * InventoryModel: Provides product data for the sales form
 * CustomerModel: Provides B2B customer data for credit sales
 */
require_once __DIR__ . '/models/SalesModel.php';
require_once __DIR__ . '/../inventory/models/InventoryModel.php';
require_once __DIR__ . '/../customers/models/CustomerModel.php';

// ============================================================================
// DATA INITIALIZATION
// ============================================================================

/**
 * Initialize model instances
 */
$salesModel = new SalesModel();
$inventoryModel = new InventoryModel();
$customerModel = new CustomerModel();

/**
 * Fetch active products for the sales form
 * Only active products can be sold
 */
$allProducts = $inventoryModel->getAllProducts();
$products = array_filter($allProducts, function($product) {
    return isset($product['status']) && $product['status'] === 'active';
});

/**
 * Fetch all active B2B customers for credit sales
 * Only active customers can make credit purchases
 */
$allB2BCustomers = $customerModel->getAllCustomers('active');
$b2bCustomers = array_filter($allB2BCustomers, function($customer) {
    return isset($customer['status']) && $customer['status'] === 'active';
});

/**
 * Fetch recent sales orders for the dashboard table
 * Limited to 10 most recent orders
 */
$recentOrders = $salesModel->getAllSalesOrders(10);

/**
 * Fetch sales statistics for the metrics cards
 * Includes total orders, revenue, and average order value
 */
$salesStats = $salesModel->getSalesStats();

/**
 * Generate CSRF token for form security
 * Protects against Cross-Site Request Forgery attacks
 */
$csrfToken = Csrf::generateToken();

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Format currency values for display
 *
 * @param float $amount The amount to format
 * @return string Formatted currency string (e.g., "$1,234.56")
 */
function formatCurrency(float $amount): string
{
    return '$' . number_format($amount, 2);
}

/**
 * Format integer numbers with thousands separator
 *
 * @param int $number The number to format
 * @return string Formatted number string (e.g., "1,234")
 */
function formatNumber(int $number): string
{
    return number_format($number);
}
?>

<div class="erp-container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php
            echo htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8');
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php
            echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8');
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <h1 class="page-title">Sales & Invoicing</h1>
        <p class="page-subtitle">Process cash and credit sales transactions</p>
    </div>

    <!-- Sales Statistics Cards -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">📊</div>
            <div class="metric-content">
                <div class="metric-label">Total Orders</div>
                <div class="metric-value"><?php echo formatNumber($salesStats['total_orders']); ?></div>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon">💰</div>
            <div class="metric-content">
                <div class="metric-label">Total Revenue</div>
                <div class="metric-value"><?php echo formatCurrency($salesStats['total_revenue']); ?></div>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-icon">📈</div>
            <div class="metric-content">
                <div class="metric-label">Average Order Value</div>
                <div class="metric-value"><?php echo formatCurrency($salesStats['average_order_value']); ?></div>
            </div>
        </div>
    </div>

    <!-- New Sales Form -->
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">New Sales Transaction</h2>
            <p class="card-description">Create a new sales invoice for cash or credit customers. Stock levels will be automatically updated.</p>
        </div>
        
        <form id="salesForm" class="form" method="POST" action="<?php echo BASE_URL; ?>/sales/checkout">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            
            <!-- Customer Information Section -->
            <div class="form-section">
                <h3 class="form-section-title">Customer Information</h3>
                
                <div class="form-grid">
                    <!-- Payment Type Selector -->
                    <div class="form-group">
                        <label for="payment_type" class="form-label">
                            Payment Type <span class="required">*</span>
                        </label>
                        <select 
                            id="payment_type" 
                            name="payment_type" 
                            class="form-select" 
                            required
                        >
                            <option value="cash">Direct Cash Payment</option>
                            <option value="credit">On-Account Commercial Credit</option>
                        </select>
                    </div>

                    <!-- Cash Customer Name Input (Default Visible) -->
                    <div class="form-group" id="customer_name_wrapper">
                        <label for="customer_name" class="form-label">
                            Customer Name <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="customer_name" 
                            name="customer_name" 
                            class="form-input" 
                            placeholder="Enter customer name"
                            required
                            minlength="2"
                            maxlength="255"
                        >
                    </div>

                    <!-- B2B Customer Selector (Hidden by Default) -->
                    <div class="form-group hidden" id="customer_id_wrapper">
                        <label for="customer_id" class="form-label">
                            B2B Customer <span class="required">*</span>
                        </label>
                        <select 
                            id="customer_id" 
                            name="customer_id" 
                            class="form-select"
                        >
                            <option value="">Select a customer</option>
                            <?php foreach ($b2bCustomers as $customer): ?>
                                <option 
                                    value="<?php echo (int)$customer['id']; ?>"
                                    data-limit="<?php echo (float)$customer['credit_limit']; ?>"
                                    data-debt="<?php echo (float)$customer['outstanding_balance']; ?>"
                                    data-name="<?php echo htmlspecialchars($customer['company_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <?php echo htmlspecialchars($customer['company_name'], ENT_QUOTES, 'UTF-8'); ?> 
                                    (<?php echo htmlspecialchars($customer['customer_code'], ENT_QUOTES, 'UTF-8'); ?>) 
                                    - Credit: <?php echo formatCurrency($customer['credit_available']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Sale Items Section -->
            <div class="form-section">
                <div class="form-section-header">
                    <h3 class="form-section-title">Sale Items</h3>
                    <button type="button" id="addItemBtn" class="btn btn-secondary">+ Add Item</button>
                </div>

                <div id="saleItemsContainer" class="sale-items-container">
                    <!-- Initial item row -->
                    <div class="sale-item-row" data-item-index="0">
                        <div class="form-group">
                            <label class="form-label">Product <span class="required">*</span></label>
                            <select name="items[0][product_id]" class="form-select product-select" required data-item-index="0">
                                <option value="">Select a product</option>
                                <?php foreach ($products as $product): ?>
                                    <option 
                                        value="<?php echo (int)$product['id']; ?>"
                                        data-price="<?php echo (float)$product['unit_price']; ?>"
                                        data-stock="<?php echo (int)$product['quantity']; ?>"
                                        data-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                        <?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?> 
                                        (<?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?>) 
                                        - Stock: <?php echo (int)$product['quantity']; ?> 
                                        - <?php echo formatCurrency((float)$product['unit_price']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Quantity <span class="required">*</span></label>
                            <input 
                                type="number" 
                                name="items[0][quantity]" 
                                class="form-input quantity-input" 
                                min="1" 
                                value="1" 
                                required
                                data-item-index="0"
                            >
                            <small class="stock-info" data-item-index="0"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Unit Price <span class="required">*</span></label>
                            <input 
                                type="number" 
                                name="items[0][unit_price]" 
                                class="form-input price-input" 
                                step="0.01" 
                                min="0.01" 
                                required
                                readonly
                                data-item-index="0"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label">Line Total</label>
                            <input 
                                type="text" 
                                class="form-input line-total" 
                                readonly
                                data-item-index="0"
                                value="$0.00"
                            >
                        </div>

                        <div class="form-group item-actions">
                            <button type="button" class="btn btn-danger btn-small remove-item-btn hidden" data-item-index="0">Remove</button>
                        </div>
                    </div>
                </div>

                <!-- Total Section -->
                <div class="total-section">
                    <div class="total-row">
                        <span class="total-label">Total Amount:</span>
                        <span id="totalAmount" class="total-value">$0.00</span>
                    </div>
                    <div id="credit_warning" class="credit-warning"></div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    <span class="btn-icon">💳</span>
                    Complete Sale
                </button>
                <button type="reset" class="btn btn-secondary" id="resetFormBtn">
                    Clear Form
                </button>
            </div>
        </form>
    </section>

    <!-- Recent Sales Orders -->
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Sales Orders</h2>
        </div>
        
        <?php if (empty($recentOrders)): ?>
            <div class="empty-state">
                <p>No sales orders yet. Create your first sale above!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Total Amount</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['invoice_number'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $order['payment_type'] === 'credit' ? 'warning' : 'success'; ?>">
                                        <?php echo $order['payment_type'] === 'credit' ? 'Credit' : 'Cash'; ?>
                                    </span>
                                </td>
                                <td class="amount"><?php echo formatCurrency($order['total_amount']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <button 
                                        class="btn btn-small btn-secondary view-order-btn" 
                                        data-order-id="<?php echo (int)$order['id']; ?>"
                                    >
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- Sales Module JavaScript -->
<script type="module">
    // Sales form management
    let itemIndex = 1;
    const saleItemsContainer = document.getElementById('saleItemsContainer');
    const addItemBtn = document.getElementById('addItemBtn');
    const totalAmountElement = document.getElementById('totalAmount');
    const salesForm = document.getElementById('salesForm');
    const paymentTypeSelect = document.getElementById('payment_type');
    const customerNameWrapper = document.getElementById('customer_name_wrapper');
    const customerIdWrapper = document.getElementById('customer_id_wrapper');
    const customerNameInput = document.getElementById('customer_name');
    const customerIdSelect = document.getElementById('customer_id');
    const creditWarning = document.getElementById('credit_warning');

    // Product data from PHP - ensure all properties are properly typed
    const products = <?php echo json_encode(array_values(array_map(function($p) {
        return [
            'id' => (int)$p['id'],
            'name' => $p['name'],
            'sku' => $p['sku'],
            'quantity' => (int)$p['quantity'],
            'unit_price' => (float)$p['unit_price']
        ];
    }, $products))); ?>;

    // Payment type change handler
    paymentTypeSelect.addEventListener('change', function() {
        const paymentType = this.value;
        
        if (paymentType === 'credit') {
            // Show customer ID selector, hide customer name input
            customerIdWrapper.classList.remove('hidden');
            customerNameWrapper.classList.add('hidden');
            
            // Update required attributes
            customerIdSelect.setAttribute('required', 'required');
            customerNameInput.removeAttribute('required');
            
            // Clear values
            customerNameInput.value = '';
            customerIdSelect.value = '';
            
            // Clear credit warning
            creditWarning.textContent = '';
        } else {
            // Show customer name input, hide customer ID selector
            customerNameWrapper.classList.remove('hidden');
            customerIdWrapper.classList.add('hidden');
            
            // Update required attributes
            customerNameInput.setAttribute('required', 'required');
            customerIdSelect.removeAttribute('required');
            
            // Clear values
            customerIdSelect.value = '';
            customerNameInput.value = '';
            
            // Clear credit warning
            creditWarning.textContent = '';
        }
    });

    // Customer ID change handler - sync name and check credit
    customerIdSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            // Extract customer name from data attribute and set it in the hidden input
            const customerName = selectedOption.dataset.name || '';
            customerNameInput.value = customerName;
            
            // Check credit boundaries
            checkCreditBoundaries();
        } else {
            customerNameInput.value = '';
            creditWarning.textContent = '';
        }
    });

    // Check credit boundaries function
    function checkCreditBoundaries() {
        const paymentType = paymentTypeSelect.value;
        
        // Only check for credit sales
        if (paymentType !== 'credit') {
            creditWarning.textContent = '';
            return;
        }
        
        const selectedOption = customerIdSelect.options[customerIdSelect.selectedIndex];
        
        if (!selectedOption.value) {
            creditWarning.textContent = '';
            return;
        }
        
        // Parse customer credit data
        const creditLimit = parseFloat(selectedOption.dataset.limit) || 0;
        const outstandingDebt = parseFloat(selectedOption.dataset.debt) || 0;
        const creditAvailable = creditLimit - outstandingDebt;
        
        // Get current total amount
        const totalAmountText = totalAmountElement.textContent.replace('$', '').replace(',', '');
        const totalAmount = parseFloat(totalAmountText) || 0;
        
        // Check if transaction would exceed credit limit
        if (totalAmount > creditAvailable) {
            const shortage = totalAmount - creditAvailable;
            creditWarning.textContent = `⚠️ WARNING: This transaction ($${totalAmount.toFixed(2)}) exceeds available credit ($${creditAvailable.toFixed(2)}) by $${shortage.toFixed(2)}. Transaction will be declined.`;
            creditWarning.style.color = 'var(--danger)';
        } else if (totalAmount > 0) {
            const remainingCredit = creditAvailable - totalAmount;
            creditWarning.textContent = `✓ Credit approved. Remaining credit after this sale: $${remainingCredit.toFixed(2)}`;
            creditWarning.style.color = 'var(--success)';
        } else {
            creditWarning.textContent = '';
        }
    }

    // Add new item row
    addItemBtn.addEventListener('click', () => {
        const newRow = createItemRow(itemIndex);
        saleItemsContainer.insertAdjacentHTML('beforeend', newRow);
        attachItemRowListeners(itemIndex);
        updateRemoveButtons();
        itemIndex++;
    });

    // Create item row HTML
    function createItemRow(index) {
        return `
            <div class="sale-item-row" data-item-index="${index}">
                <div class="form-group">
                    <label class="form-label">Product <span class="required">*</span></label>
                    <select name="items[${index}][product_id]" class="form-select product-select" required data-item-index="${index}">
                        <option value="">Select a product</option>
                        ${products.map(p => `
                            <option 
                                value="${p.id}"
                                data-price="${p.unit_price}"
                                data-stock="${p.quantity}"
                                data-name="${escapeHtml(p.name)}"
                            >
                                ${escapeHtml(p.name)} (${escapeHtml(p.sku)}) - Stock: ${p.quantity} - $${p.unit_price.toFixed(2)}
                            </option>
                        `).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Quantity <span class="required">*</span></label>
                    <input type="number" name="items[${index}][quantity]" class="form-input quantity-input" min="1" value="1" required data-item-index="${index}">
                    <small class="stock-info" data-item-index="${index}"></small>
                </div>
                <div class="form-group">
                    <label class="form-label">Unit Price <span class="required">*</span></label>
                    <input type="number" name="items[${index}][unit_price]" class="form-input price-input" step="0.01" min="0.01" required readonly data-item-index="${index}">
                </div>
                <div class="form-group">
                    <label class="form-label">Line Total</label>
                    <input type="text" class="form-input line-total" readonly data-item-index="${index}" value="$0.00">
                </div>
                <div class="form-group item-actions">
                    <button type="button" class="btn btn-danger btn-small remove-item-btn" data-item-index="${index}">Remove</button>
                </div>
            </div>
        `;
    }

    // Escape HTML for security
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Attach listeners to item row
    function attachItemRowListeners(index) {
        const row = document.querySelector(`.sale-item-row[data-item-index="${index}"]`);
        const productSelect = row.querySelector('.product-select');
        const quantityInput = row.querySelector('.quantity-input');
        const removeBtn = row.querySelector('.remove-item-btn');

        productSelect.addEventListener('change', () => updateLineTotal(index));
        quantityInput.addEventListener('input', () => updateLineTotal(index));
        removeBtn.addEventListener('click', () => removeItemRow(index));
    }

    // Update line total calculation
    function updateLineTotal(index) {
        const row = document.querySelector(`.sale-item-row[data-item-index="${index}"]`);
        const productSelect = row.querySelector('.product-select');
        const quantityInput = row.querySelector('.quantity-input');
        const priceInput = row.querySelector('.price-input');
        const lineTotalInput = row.querySelector('.line-total');
        const stockInfo = row.querySelector('.stock-info');

        const selectedOption = productSelect.options[productSelect.selectedIndex];
        
        if (selectedOption.value) {
            // Safely parse data attributes with fallback values
            const price = parseFloat(selectedOption.dataset.price) || 0;
            const stock = parseInt(selectedOption.dataset.stock) || 0;
            const quantity = parseInt(quantityInput.value) || 0;

            priceInput.value = price.toFixed(2);
            const lineTotal = price * quantity;
            lineTotalInput.value = `$${lineTotal.toFixed(2)}`;

            // Stock validation with CSS classes
            if (quantity > stock) {
                stockInfo.textContent = `⚠️ Only ${stock} available`;
                stockInfo.classList.remove('stock-valid');
                stockInfo.classList.add('stock-invalid');
                quantityInput.setCustomValidity('Insufficient stock');
            } else {
                stockInfo.textContent = `✓ ${stock} available`;
                stockInfo.classList.remove('stock-invalid');
                stockInfo.classList.add('stock-valid');
                quantityInput.setCustomValidity('');
            }
        } else {
            priceInput.value = '';
            lineTotalInput.value = '$0.00';
            stockInfo.textContent = '';
            stockInfo.classList.remove('stock-valid', 'stock-invalid');
        }

        updateTotalAmount();
        checkCreditBoundaries();
    }

    // Update total amount
    function updateTotalAmount() {
        let total = 0;
        document.querySelectorAll('.line-total').forEach(input => {
            const value = parseFloat(input.value.replace('$', '').replace(',', '')) || 0;
            total += value;
        });
        totalAmountElement.textContent = `$${total.toFixed(2)}`;
        
        // Re-check credit boundaries when total changes
        checkCreditBoundaries();
    }

    // Remove item row
    function removeItemRow(index) {
        const row = document.querySelector(`.sale-item-row[data-item-index="${index}"]`);
        row.remove();
        updateTotalAmount();
        updateRemoveButtons();
        checkCreditBoundaries();
    }

    // Update remove button visibility using CSS classes
    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.sale-item-row');
        rows.forEach((row, idx) => {
            const removeBtn = row.querySelector('.remove-item-btn');
            if (rows.length > 1) {
                removeBtn.classList.remove('hidden');
            } else {
                removeBtn.classList.add('hidden');
            }
        });
    }

    // Initialize first row listeners
    attachItemRowListeners(0);

    // Form reset handler
    document.getElementById('resetFormBtn').addEventListener('click', () => {
        // Remove all rows except first
        const rows = document.querySelectorAll('.sale-item-row');
        rows.forEach((row, idx) => {
            if (idx > 0) row.remove();
        });
        itemIndex = 1;
        
        // Reset payment type to cash
        paymentTypeSelect.value = 'cash';
        customerNameWrapper.classList.remove('hidden');
        customerIdWrapper.classList.add('hidden');
        customerNameInput.setAttribute('required', 'required');
        customerIdSelect.removeAttribute('required');
        
        // Clear credit warning
        creditWarning.textContent = '';
        
        updateTotalAmount();
        updateRemoveButtons();
    });

    // Form submission validation
    salesForm.addEventListener('submit', (e) => {
        const rows = document.querySelectorAll('.sale-item-row');
        let hasError = false;

        rows.forEach(row => {
            const productSelect = row.querySelector('.product-select');
            if (!productSelect.value) {
                hasError = true;
                alert('Please select a product for all items');
                e.preventDefault();
            }
        });
        
        // Additional validation for credit sales
        const paymentType = paymentTypeSelect.value;
        if (paymentType === 'credit' && !hasError) {
            const selectedOption = customerIdSelect.options[customerIdSelect.selectedIndex];
            
            if (selectedOption.value) {
                const creditLimit = parseFloat(selectedOption.dataset.limit) || 0;
                const outstandingDebt = parseFloat(selectedOption.dataset.debt) || 0;
                const creditAvailable = creditLimit - outstandingDebt;
                const totalAmountText = totalAmountElement.textContent.replace('$', '').replace(',', '');
                const totalAmount = parseFloat(totalAmountText) || 0;
                
                if (totalAmount > creditAvailable) {
                    alert('Cannot process: Transaction exceeds customer credit limit');
                    e.preventDefault();
                }
            }
        }
    });

    // View order details
    document.addEventListener('click', (e) => {
        if (e.target.closest('.view-order-btn')) {
            const orderId = e.target.closest('.view-order-btn').dataset.orderId;
            if (orderId) {
                // Navigate to order details page
                window.location.href = `<?php echo BASE_URL; ?>/sales/orders/${orderId}`;
            }
        }
    });
</script>

<?php
// Made with Bob
