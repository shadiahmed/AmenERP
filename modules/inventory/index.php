<?php
/**
 * Inventory Module - Main Dashboard View
 *
 * Comprehensive warehouse management interface for tracking products, stock levels,
 * and inventory valuation with real-time search and CRUD operations.
 *
 * Features:
 * - Real-time inventory metrics dashboard
 * - Product CRUD operations with modal forms
 * - Live search and filtering
 * - Stock status indicators with semantic colors
 * - SKU validation and duplicate checking
 * - Responsive mobile-first design
 * - CSRF protection
 *
 * @package AmenERP\Modules\Inventory
 * @author Bob
 * @version 2.0.0
 */

declare(strict_types=1);

// ============================================================================
// BOOTSTRAP: Core Configuration & Dependencies
// ============================================================================

/**
 * Load the InventoryModel
 */
require_once __DIR__ . '/models/InventoryModel.php';

/**
 * Initialize the model
 */
$inventoryModel = new InventoryModel();

/**
 * Fetch summary statistics
 */
$stats = $inventoryModel->getSummaryStats();

/**
 * Fetch all products with category information
 */
$products = $inventoryModel->getAllProducts();

/**
 * Fetch all categories for the form dropdown
 */
$categories = $inventoryModel->getAllCategories();

/**
 * Generate CSRF token for the form
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

/**
 * Determine stock status badge
 *
 * @param int $quantity Current stock quantity
 * @return array Badge class and text
 */
function getStockBadge(int $quantity): array
{
    if ($quantity === 0) {
        return ['class' => 'badge-danger', 'text' => 'Out of Stock'];
    } elseif ($quantity < 20) {
        return ['class' => 'badge-warning', 'text' => 'Low Stock'];
    } else {
        return ['class' => 'badge-success', 'text' => 'In Stock'];
    }
}

/**
 * Get status badge
 *
 * @param string $status Product status
 * @return array Badge class and text
 */
function getStatusBadge(string $status): array
{
    $badges = [
        'active' => ['class' => 'badge-success', 'text' => 'Active'],
        'inactive' => ['class' => 'badge-secondary', 'text' => 'Inactive'],
        'discontinued' => ['class' => 'badge-danger', 'text' => 'Discontinued']
    ];
    
    return $badges[$status] ?? ['class' => 'badge-secondary', 'text' => ucfirst($status)];
}

/**
 * Calculate out of stock count
 */
$outOfStockCount = 0;
foreach ($products as $product) {
    if ($product['quantity'] === 0) {
        $outOfStockCount++;
    }
}
?>

<div class="erp-container">
    <!-- Flash Messages -->
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

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Inventory Management</h1>
            <p class="page-subtitle">Track products, stock levels, and warehouse valuation</p>
        </div>
        <button id="addItemBtn" class="btn btn-primary">+ Add New Item</button>
    </div>

    <!-- Inventory Metrics Dashboard -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">📦</div>
            <div class="metric-content">
                <div class="metric-label">Total SKUs</div>
                <div class="metric-value"><?php echo formatNumber($stats['total_items']); ?></div>
                <div class="metric-subtitle">Unique Products</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">⚠️</div>
            <div class="metric-content">
                <div class="metric-label">Low Stock Items</div>
                <div class="metric-value"><?php echo formatNumber($stats['low_stock_count']); ?></div>
                <div class="metric-subtitle"><?php echo formatNumber($outOfStockCount); ?> out of stock</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">💰</div>
            <div class="metric-content">
                <div class="metric-label">Warehouse Asset Value</div>
                <div class="metric-value"><?php echo formatCurrency($stats['total_value']); ?></div>
                <div class="metric-subtitle">Total Inventory</div>
            </div>
        </div>
    </div>

    <!-- Product Tracking Ledger -->
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Product Tracking Ledger</h2>
            <div class="card-actions">
                <input type="search"
                       id="searchInput"
                       placeholder="Search by name or SKU..."
                       class="search-input"
                       autocomplete="off">
            </div>
        </div>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <p>No products found. Add your first product to get started.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table" id="inventoryTable">
                    <thead>
                        <tr>
                            <th data-sort="sku" class="sortable">SKU</th>
                            <th data-sort="name" class="sortable">Item Name</th>
                            <th>Category</th>
                            <th data-sort="quantity" class="sortable text-right">Quantity</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Total Value</th>
                            <th>Stock Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productTableBody">
                        <?php foreach ($products as $product): ?>
                            <?php
                            // Calculate total value for this product
                            $totalValue = $product['quantity'] * $product['unit_price'];
                            
                            // Get stock status badge
                            $stockBadge = getStockBadge((int)$product['quantity']);
                            
                            // Get product status badge
                            $statusBadge = getStatusBadge($product['status']);
                            ?>
                            <tr data-product-id="<?php echo (int)$product['id']; ?>"
                                data-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-sku="<?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-quantity="<?php echo (int)$product['quantity']; ?>">
                                <td><code class="sku-code"><?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-right"><?php echo formatNumber((int)$product['quantity']); ?></td>
                                <td class="text-right"><?php echo formatCurrency((float)$product['unit_price']); ?></td>
                                <td class="text-right"><strong><?php echo formatCurrency($totalValue); ?></strong></td>
                                <td>
                                    <span class="badge <?php echo $stockBadge['class']; ?>">
                                        <?php echo $stockBadge['text']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button class="btn btn-small btn-secondary edit-btn"
                                                title="Edit Product"
                                                data-product-id="<?php echo (int)$product['id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-product-sku="<?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-product-category="<?php echo (int)$product['category_id']; ?>"
                                                data-product-quantity="<?php echo (int)$product['quantity']; ?>"
                                                data-product-price="<?php echo (float)$product['unit_price']; ?>"
                                                data-product-status="<?php echo htmlspecialchars($product['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                            ✏️ Edit
                                        </button>
                                        <button class="btn btn-small btn-danger delete-btn"
                                                title="Delete Product"
                                                data-product-id="<?php echo (int)$product['id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                            🗑️ Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <div class="table-info">
                    Showing <span id="visibleCount"><?php echo count($products); ?></span> of <?php echo $stats['total_items']; ?> items
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- Add Product Modal -->
<div id="addProductModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Product</h3>
                <button id="closeModal" class="modal-close" aria-label="Close modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addProductForm" class="form" method="POST" action="<?php echo BASE_URL; ?>/inventory/add">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="productName" class="form-label">Product Name <span class="required">*</span></label>
                            <input type="text"
                                   id="productName"
                                   name="name"
                                   class="form-input"
                                   required
                                   minlength="2"
                                   maxlength="100"
                                   placeholder="Enter product name">
                        </div>

                        <div class="form-group">
                            <label for="productSku" class="form-label">SKU <span class="required">*</span></label>
                            <input type="text"
                                   id="productSku"
                                   name="sku"
                                   class="form-input"
                                   required
                                   pattern="[A-Z0-9\-]+"
                                   maxlength="50"
                                   placeholder="e.g., PROD-001"
                                   data-sku-check="add">
                            <small class="form-hint">Use uppercase letters, numbers, and hyphens only</small>
                            <span id="skuError" class="form-error"></span>
                        </div>

                        <div class="form-group">
                            <label for="productCategory" class="form-label">Category <span class="required">*</span></label>
                            <select id="productCategory" name="category_id" class="form-select" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int)$category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="productQuantity" class="form-label">Quantity <span class="required">*</span></label>
                            <input type="number"
                                   id="productQuantity"
                                   name="quantity"
                                   class="form-input"
                                   required
                                   min="0"
                                   max="999999"
                                   placeholder="0">
                        </div>

                        <div class="form-group">
                            <label for="productPrice" class="form-label">Unit Price <span class="required">*</span></label>
                            <input type="number"
                                   id="productPrice"
                                   name="unit_price"
                                   class="form-input"
                                   required
                                   min="0.01"
                                   step="0.01"
                                   placeholder="0.00">
                        </div>

                        <div class="form-group">
                            <label for="productStatus" class="form-label">Status</label>
                            <select id="productStatus" name="status" class="form-select">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="discontinued">Discontinued</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="cancelBtn" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Product</h3>
                <button id="closeEditModal" class="modal-close" aria-label="Close modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editProductForm" class="form" method="POST" action="<?php echo BASE_URL; ?>/inventory/edit">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    
                    <!-- Hidden Product ID -->
                    <input type="hidden" id="editProductId" name="product_id" value="">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="editProductName" class="form-label">Product Name <span class="required">*</span></label>
                            <input type="text"
                                   id="editProductName"
                                   name="name"
                                   class="form-input"
                                   required
                                   minlength="2"
                                   maxlength="100"
                                   placeholder="Enter product name">
                        </div>

                        <div class="form-group">
                            <label for="editProductSku" class="form-label">SKU <span class="required">*</span></label>
                            <input type="text"
                                   id="editProductSku"
                                   name="sku"
                                   class="form-input"
                                   required
                                   pattern="[A-Z0-9\-]+"
                                   maxlength="50"
                                   placeholder="e.g., PROD-001"
                                   data-sku-check="edit">
                            <small class="form-hint">Use uppercase letters, numbers, and hyphens only</small>
                            <span id="editSkuError" class="form-error"></span>
                        </div>

                        <div class="form-group">
                            <label for="editProductCategory" class="form-label">Category <span class="required">*</span></label>
                            <select id="editProductCategory" name="category_id" class="form-select" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int)$category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="editProductQuantity" class="form-label">Quantity <span class="required">*</span></label>
                            <input type="number"
                                   id="editProductQuantity"
                                   name="quantity"
                                   class="form-input"
                                   required
                                   min="0"
                                   max="999999"
                                   placeholder="0">
                        </div>

                        <div class="form-group">
                            <label for="editProductPrice" class="form-label">Unit Price <span class="required">*</span></label>
                            <input type="number"
                                   id="editProductPrice"
                                   name="unit_price"
                                   class="form-input"
                                   required
                                   min="0.01"
                                   step="0.01"
                                   placeholder="0.00">
                        </div>

                        <div class="form-group">
                            <label for="editProductStatus" class="form-label">Status</label>
                            <select id="editProductStatus" name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="discontinued">Discontinued</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="cancelEditBtn" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Module JavaScript -->
<script type="module">
    /**
     * Inventory Module - Client-Side Logic
     * Handles modal management, real-time search, SKU validation, and CRUD operations
     */

    const baseUrl = '<?php echo BASE_URL; ?>';

    // ====================================================================
    // MODAL MANAGEMENT
    // ====================================================================
    
    // Add Product Modal Elements
    const modal = document.getElementById('addProductModal');
    const addItemBtn = document.getElementById('addItemBtn');
    const closeModalBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const addProductForm = document.getElementById('addProductForm');

    // Edit Product Modal Elements
    const editModal = document.getElementById('editProductModal');
    const closeEditModalBtn = document.getElementById('closeEditModal');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const editProductForm = document.getElementById('editProductForm');

    /**
     * Open Add Product modal
     */
    if (addItemBtn) {
        addItemBtn.addEventListener('click', () => {
            modal.classList.add('active');
        });
    }

    /**
     * Close Add Product modal
     */
    function closeModal() {
        modal.classList.remove('active');
        if (addProductForm) {
            addProductForm.reset();
        }
    }

    /**
     * Close Edit Product modal
     */
    function closeEditModal() {
        editModal.classList.remove('active');
        if (editProductForm) {
            editProductForm.reset();
        }
    }

    // Close Add modal on close button
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeModal);
    }

    // Close Add modal on cancel button
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    // Close Edit modal on close button
    if (closeEditModalBtn) {
        closeEditModalBtn.addEventListener('click', closeEditModal);
    }

    // Close Edit modal on cancel button
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', closeEditModal);
    }

    // Close Add modal on outside click
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    // Close Edit modal on outside click
    if (editModal) {
        editModal.addEventListener('click', (e) => {
            if (e.target === editModal) {
                closeEditModal();
            }
        });
    }

    // Close modals on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (modal.classList.contains('active')) {
                closeModal();
            }
            if (editModal.classList.contains('active')) {
                closeEditModal();
            }
        }
    });

    // ====================================================================
    // EDIT BUTTON HANDLER
    // ====================================================================
    
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            // Populate edit form with product data
            document.getElementById('editProductId').value = editBtn.dataset.productId;
            document.getElementById('editProductName').value = editBtn.dataset.productName;
            document.getElementById('editProductSku').value = editBtn.dataset.productSku;
            document.getElementById('editProductCategory').value = editBtn.dataset.productCategory;
            document.getElementById('editProductQuantity').value = editBtn.dataset.productQuantity;
            document.getElementById('editProductPrice').value = editBtn.dataset.productPrice;
            document.getElementById('editProductStatus').value = editBtn.dataset.productStatus;
            
            // Open edit modal
            editModal.classList.add('active');
        }
    });

    // ====================================================================
    // DELETE BUTTON HANDLER
    // ====================================================================
    
    document.addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const productId = deleteBtn.dataset.productId;
            const productName = deleteBtn.dataset.productName;
            
            if (confirm(`Are you sure you want to delete "${productName}"? This action cannot be undone.`)) {
                try {
                    const formData = new FormData();
                    formData.append('csrf_token', '<?php echo $csrfToken; ?>');
                    
                    const response = await fetch(`${baseUrl}/inventory/delete/${productId}`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (response.ok && result.success) {
                        // Reload page to show updated list
                        window.location.reload();
                    } else {
                        alert(`Error: ${result.error || 'Failed to delete product'}`);
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                    alert(`An error occurred: ${error.message}`);
                }
            }
        }
    });

    // ====================================================================
    // EDIT FORM SUBMISSION HANDLER
    // ====================================================================
    
    if (editProductForm) {
        editProductForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            try {
                const formData = new FormData(editProductForm);
                const productId = document.getElementById('editProductId').value;
                const productName = document.getElementById('editProductName').value;

                // Show loading state on button
                const submitBtn = editProductForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Updating...';

                // Send POST request with AJAX headers
                const response = await fetch(`${baseUrl}/inventory/edit/${productId}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                // Parse JSON response
                const result = await response.json();

                // Restore button state
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;

                if (response.ok && result.success) {
                    console.log('✅ Product updated:', result);
                    
                    // Close the modal
                    closeEditModal();

                    // Reload the page to show updated data
                    window.location.reload();
                } else {
                    // Show error message
                    const errorMsg = result.error || 'Failed to update product';
                    alert(`Error: ${errorMsg}`);
                    console.error('Update failed:', result);
                }
            } catch (error) {
                console.error('Edit form submission error:', error);
                alert(`An error occurred: ${error.message}`);
            }
        });
    }

    // ====================================================================
    // REAL-TIME SEARCH
    // ====================================================================
    
    const searchInput = document.getElementById('searchInput');
    const productTableBody = document.getElementById('productTableBody');
    const visibleCount = document.getElementById('visibleCount');

    if (searchInput && productTableBody) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase().trim();
            const rows = productTableBody.querySelectorAll('tr');
            let visibleRows = 0;

            rows.forEach(row => {
                const name = row.dataset.name?.toLowerCase() || '';
                const sku = row.dataset.sku?.toLowerCase() || '';
                
                if (name.includes(searchTerm) || sku.includes(searchTerm)) {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleCount) {
                visibleCount.textContent = visibleRows;
            }
        });
    }

    // ====================================================================
    // SKU VALIDATION
    // ====================================================================
    
    const skuInputs = document.querySelectorAll('[data-sku-check]');
    
    skuInputs.forEach(input => {
        let debounceTimer;
        
        input.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            
            const sku = e.target.value.trim();
            const mode = e.target.dataset.skuCheck;
            const errorElement = mode === 'add' ? document.getElementById('skuError') : document.getElementById('editSkuError');
            
            if (sku.length < 2) {
                errorElement.textContent = '';
                errorElement.classList.remove('active');
                return;
            }
            
            debounceTimer = setTimeout(async () => {
                try {
                    const response = await fetch(`${baseUrl}/api/inventory/check-sku?sku=${encodeURIComponent(sku)}`);
                    const result = await response.json();
                    
                    if (result.exists) {
                        errorElement.textContent = '⚠️ This SKU is already in use';
                        errorElement.classList.add('active');
                        input.setCustomValidity('SKU already exists');
                    } else {
                        errorElement.textContent = '✓ SKU is available';
                        errorElement.classList.add('active');
                        errorElement.style.color = 'var(--success)';
                        input.setCustomValidity('');
                        
                        setTimeout(() => {
                            errorElement.textContent = '';
                            errorElement.classList.remove('active');
                            errorElement.style.color = '';
                        }, 2000);
                    }
                } catch (error) {
                    console.error('SKU check error:', error);
                }
            }, 500);
        });
    });
</script>

<?php
// Made with Bob
