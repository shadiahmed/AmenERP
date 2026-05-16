<?php
/**
 * Inventory Module - Main View
 * Displays inventory metrics and item list with dynamic data from database
 * 
 * @package AmenERP\Modules\Inventory
 * @author Bob
 * @version 1.0.0
 */

declare(strict_types=1);

// Load the InventoryModel
require_once __DIR__ . '/models/InventoryModel.php';

// Initialize the model
$inventoryModel = new InventoryModel();

// Fetch summary statistics
$stats = $inventoryModel->getSummaryStats();

// Fetch all products with category information
$products = $inventoryModel->getAllProducts();

// Fetch all categories for the form dropdown
$categories = $inventoryModel->getAllCategories();

// Generate CSRF token for the form
$csrfToken = Csrf::generateToken();

// Helper function to format currency
function formatCurrency(float $amount): string
{
    return '$' . number_format($amount, 2);
}

// Helper function to format numbers with commas
function formatNumber(int $number): string
{
    return number_format($number);
}

// Helper function to determine stock status badge
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

// Helper function to get status badge
function getStatusBadge(string $status): array
{
    $badges = [
        'active' => ['class' => 'badge-success', 'text' => 'Active'],
        'inactive' => ['class' => 'badge-secondary', 'text' => 'Inactive'],
        'discontinued' => ['class' => 'badge-danger', 'text' => 'Discontinued']
    ];
    
    return $badges[$status] ?? ['class' => 'badge-secondary', 'text' => ucfirst($status)];
}

// Calculate out of stock count
$outOfStockCount = 0;
foreach ($products as $product) {
    if ($product['quantity'] === 0) {
        $outOfStockCount++;
    }
}
?>

<div class="module-container inventory-module">
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

    <div class="module-header">
        <h2>Inventory Management</h2>
        <button id="addItemBtn" class="btn btn-primary">Add New Item</button>
    </div>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Product</h3>
                <button id="closeModal" class="btn-close" aria-label="Close modal">&times;</button>
            </div>
            <form id="addProductForm" class="form-grid" method="POST" action="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/inventory/add">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                
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
                    <span id="skuError" class="error-text" style="display: none;"></span>
                </div>

                <div class="form-group">
                    <label for="productCategory" class="form-label">Category <span class="required">*</span></label>
                    <select id="productCategory" name="category_id" class="form-select" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars((string)$category['id'], ENT_QUOTES, 'UTF-8'); ?>">
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

                <div class="form-group form-group-full">
                    <label for="productStatus" class="form-label">Status</label>
                    <select id="productStatus" name="status" class="form-select">
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="discontinued">Discontinued</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" id="cancelBtn" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Product</h3>
                <button id="closeEditModal" class="btn-close" aria-label="Close modal">&times;</button>
            </div>
            <form id="editProductForm" class="form-grid" method="POST" action="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/inventory/edit">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                
                <!-- Hidden Product ID -->
                <input type="hidden" id="editProductId" name="product_id" value="">
                
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
                    <span id="editSkuError" class="error-text" style="display: none;"></span>
                </div>

                <div class="form-group">
                    <label for="editProductCategory" class="form-label">Category <span class="required">*</span></label>
                    <select id="editProductCategory" name="category_id" class="form-select" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars((string)$category['id'], ENT_QUOTES, 'UTF-8'); ?>">
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

                <div class="form-group form-group-full">
                    <label for="editProductStatus" class="form-label">Status</label>
                    <select id="editProductStatus" name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="discontinued">Discontinued</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" id="cancelEditBtn" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Metric Cards -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">📦</div>
            <div class="metric-content">
                <h3 class="metric-value"><?php echo formatNumber($stats['total_items']); ?></h3>
                <p class="metric-label">Total Items</p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">⚠️</div>
            <div class="metric-content">
                <h3 class="metric-value"><?php echo formatNumber($outOfStockCount); ?></h3>
                <p class="metric-label">Out of Stock</p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">📊</div>
            <div class="metric-content">
                <h3 class="metric-value"><?php echo formatNumber($stats['low_stock_count']); ?></h3>
                <p class="metric-label">Low Stock</p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">💰</div>
            <div class="metric-content">
                <h3 class="metric-value"><?php echo formatCurrency($stats['total_value']); ?></h3>
                <p class="metric-label">Total Value</p>
            </div>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="table-container">
        <div class="table-header">
            <h3>Inventory Items</h3>
            <div class="table-actions">
                <input type="search"
                       id="searchInput"
                       placeholder="Search by name or SKU..."
                       class="search-input"
                       autocomplete="off">
                <button class="btn btn-secondary">Export</button>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th data-sort="sku" class="sortable">SKU <span class="sort-indicator"></span></th>
                    <th data-sort="name" class="sortable">Item Name <span class="sort-indicator"></span></th>
                    <th>Category</th>
                    <th data-sort="quantity" class="sortable">Quantity <span class="sort-indicator"></span></th>
                    <th>Unit Price</th>
                    <th>Total Value</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            No products found. Add your first product to get started.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                        // Calculate total value for this product
                        $totalValue = $product['quantity'] * $product['unit_price'];
                        
                        // Get stock status badge
                        $stockBadge = getStockBadge((int)$product['quantity']);
                        
                        // Get product status badge
                        $statusBadge = getStatusBadge($product['status']);
                        
                        // Determine row class based on stock level
                        $rowClass = '';
                        if ($product['quantity'] === 0) {
                            $rowClass = 'row-out-of-stock';
                        } elseif ($product['quantity'] < 20) {
                            $rowClass = 'row-low-stock';
                        }
                        ?>
                        <tr data-product-id="<?php echo htmlspecialchars((string)$product['id'], ENT_QUOTES, 'UTF-8'); ?>"
                            class="<?php echo htmlspecialchars($rowClass, ENT_QUOTES, 'UTF-8'); ?>"
                            data-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-sku="<?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-quantity="<?php echo htmlspecialchars((string)$product['quantity'], ENT_QUOTES, 'UTF-8'); ?>">
                            <td><?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo formatNumber((int)$product['quantity']); ?></td>
                            <td><?php echo formatCurrency((float)$product['unit_price']); ?></td>
                            <td><?php echo formatCurrency($totalValue); ?></td>
                            <td>
                                <span class="badge <?php echo htmlspecialchars($stockBadge['class'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($stockBadge['text'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-icon"
                                        title="Edit"
                                        data-action="edit"
                                        data-product-id="<?php echo htmlspecialchars((string)$product['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-product-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-product-sku="<?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-product-category="<?php echo htmlspecialchars((string)$product['category_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-product-quantity="<?php echo htmlspecialchars((string)$product['quantity'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-product-price="<?php echo htmlspecialchars((string)$product['unit_price'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-product-status="<?php echo htmlspecialchars($product['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                    ✏️
                                </button>
                                <button class="btn-icon"
                                        title="Delete"
                                        data-action="delete"
                                        data-product-id="<?php echo htmlspecialchars((string)$product['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-product-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="table-footer">
            <div class="pagination">
                <button class="btn-pagination" disabled>Previous</button>
                <span class="pagination-info">Showing <?php echo count($products); ?> of <?php echo $stats['total_items']; ?> items</span>
                <button class="btn-pagination" disabled>Next</button>
            </div>
        </div>
    </div>
</div>

<script>
    // ES6 Module Script - Inventory Module with Real-Time Search
    (() => {
        'use strict';
        const baseUrl = '<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>';

        // ============================================================================
        // MODAL MANAGEMENT
        // ============================================================================
        
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

        // Open Add modal
        if (addItemBtn) {
            addItemBtn.addEventListener('click', () => {
                modal.classList.remove('hidden');
                document.body.classList.add('modal-open');
            });
        }

        // Close Add modal function
        const closeModal = () => {
            modal.classList.add('hidden');
            document.body.classList.remove('modal-open');
            if (addProductForm) {
                addProductForm.reset();
            }
        };

        // Close Edit modal function
        const closeEditModal = () => {
            editModal.classList.add('hidden');
            document.body.classList.remove('modal-open');
            if (editProductForm) {
                editProductForm.reset();
            }
        };

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
                if (!modal.classList.contains('hidden')) {
                    closeModal();
                }
                if (!editModal.classList.contains('hidden')) {
                    closeEditModal();
                }
            }
        });

        // ============================================================================
        // EDIT FORM SUBMISSION HANDLER
        // ============================================================================
        
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
                    const baseUrl = '<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>';
                    const response = await fetch(`${baseUrl}/inventory/edit/${productId}`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    // Parse JSON response
                    let result;
                    try {
                        result = await response.json();
                    } catch (parseError) {
                        console.error('Failed to parse response as JSON:', parseError);
                        throw new Error('Invalid server response');
                    }

                    // Restore button state
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;

                    if (response.ok && result.success) {
                        // Show success notification
                        if (window.showNotification) {
                            window.showNotification(`✅ Product "${productName}" updated successfully!`, 'success');
                        }
                        
                        console.log('✅ Product updated:', result);
                        
                        // Close the modal
                        closeEditModal();

                        // Reload the page to show updated data
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    } else {
                        // Show error message
                        const errorMsg = result.error || 'Failed to update product';
                        if (window.showNotification) {
                            window.showNotification(`❌ Error: ${errorMsg}`, 'error');
                        } else {
                            alert(`Error: ${errorMsg}`);
                        }
                        console.error('Update failed:', result);
                    }
                } catch (error) {
                    console.error('Edit form submission error:', error);
                    if (window.showNotification) {
                        window.showNotification(`❌ An error occurred: ${error.message}`, 'error');
                    } else {
                        alert(`An error occurred: ${error.message}`);
                    }
                    
                    // Restore button state
                    const submitBtn = editProductForm.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Update Product';
                    }
                }
            });
        }

        // ============================================================================
        // DEBOUNCE UTILITY FUNCTION
        // ============================================================================
        
        /**
         * Debounce function - delays execution until after wait time has elapsed
         * since the last time it was invoked
         * @param {Function} func - Function to debounce
         * @param {number} wait - Wait time in milliseconds
         * @returns {Function} Debounced function
         */
        const debounce = (func, wait) => {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        };

        // ============================================================================
        // REAL-TIME SEARCH WITH API INTEGRATION
        // ============================================================================
        
        const searchInput = document.getElementById('searchInput');
        const productTableBody = document.getElementById('productTableBody');
        const baseUrl = '<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>';

        /**
         * Render products in the table using secure DOM manipulation
         * @param {Array} products - Array of product objects
         */
        const renderProducts = (products) => {
            // Clear existing rows
            productTableBody.innerHTML = '';

            if (products.length === 0) {
                const emptyRow = document.createElement('tr');
                const emptyCell = document.createElement('td');
                emptyCell.colSpan = 8;
                emptyCell.className = 'empty-state';
                emptyCell.textContent = 'No products found matching your search.';
                emptyRow.appendChild(emptyCell);
                productTableBody.appendChild(emptyRow);
                return;
            }

            // Build rows using secure DOM methods (no innerHTML to prevent XSS)
            products.forEach(product => {
                const row = document.createElement('tr');
                row.dataset.productId = product.id;

                // SKU
                const skuCell = document.createElement('td');
                skuCell.textContent = product.sku;
                row.appendChild(skuCell);

                // Name
                const nameCell = document.createElement('td');
                nameCell.textContent = product.name;
                row.appendChild(nameCell);

                // Category
                const categoryCell = document.createElement('td');
                categoryCell.textContent = product.category_name;
                row.appendChild(categoryCell);

                // Quantity
                const quantityCell = document.createElement('td');
                quantityCell.textContent = new Intl.NumberFormat().format(product.quantity);
                row.appendChild(quantityCell);

                // Unit Price
                const priceCell = document.createElement('td');
                priceCell.textContent = '$' + parseFloat(product.unit_price).toFixed(2);
                row.appendChild(priceCell);

                // Total Value
                const totalCell = document.createElement('td');
                const totalValue = product.quantity * product.unit_price;
                totalCell.textContent = '$' + totalValue.toFixed(2);
                row.appendChild(totalCell);

                // Stock Status Badge
                const statusCell = document.createElement('td');
                const badge = document.createElement('span');
                badge.className = 'badge';
                
                if (product.quantity === 0) {
                    badge.classList.add('badge-danger');
                    badge.textContent = 'Out of Stock';
                } else if (product.quantity < 20) {
                    badge.classList.add('badge-warning');
                    badge.textContent = 'Low Stock';
                } else {
                    badge.classList.add('badge-success');
                    badge.textContent = 'In Stock';
                }
                
                statusCell.appendChild(badge);
                row.appendChild(statusCell);

                // Actions
                const actionsCell = document.createElement('td');
                
                const editBtn = document.createElement('button');
                editBtn.className = 'btn-icon';
                editBtn.title = 'Edit';
                editBtn.dataset.action = 'edit';
                editBtn.dataset.productId = product.id;
                editBtn.dataset.productName = product.name;
                editBtn.dataset.productSku = product.sku;
                editBtn.dataset.productCategory = product.category_id;
                editBtn.dataset.productQuantity = product.quantity;
                editBtn.dataset.productPrice = product.unit_price;
                editBtn.dataset.productStatus = product.status;
                editBtn.textContent = '✏️';
                
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn-icon';
                deleteBtn.title = 'Delete';
                deleteBtn.dataset.action = 'delete';
                deleteBtn.dataset.productId = product.id;
                deleteBtn.dataset.productName = product.name;
                deleteBtn.textContent = '🗑️';
                
                actionsCell.appendChild(editBtn);
                actionsCell.appendChild(deleteBtn);
                row.appendChild(actionsCell);

                productTableBody.appendChild(row);
            });
        };

        /**
         * Perform search via API using native fetch
         * @param {string} query - Search query
         */
        const performSearch = async (query) => {
            // If query is empty, reload the page to show all products
            if (!query.trim()) {
                window.location.reload();
                return;
            }

            try {
                const response = await fetch(`${baseUrl}/api/inventory/search?q=${encodeURIComponent(query)}`);
                
                // Try to parse JSON even if response is not ok
                const result = await response.json();
                
                // Log full response for debugging
                console.log('API Response:', result);
                
                if (!response.ok) {
                    console.error('HTTP Error:', response.status, result);
                    if (result.debug) {
                        console.error('Debug Info:', result.debug);
                    }
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                if (result.success) {
                    renderProducts(result.data);
                    console.log(`✅ Found ${result.count} products for "${result.query}"`);
                } else {
                    console.error('Search failed:', result.error);
                    if (result.debug) {
                        console.error('Debug Info:', result.debug);
                    }
                }
            } catch (error) {
                console.error('Search error:', error);
                // Show error message in table using secure DOM manipulation
                productTableBody.innerHTML = '';
                const errorRow = document.createElement('tr');
                const errorCell = document.createElement('td');
                errorCell.colSpan = 8;
                errorCell.className = 'empty-state';
                errorCell.textContent = 'Error performing search. Check console for details.';
                errorRow.appendChild(errorCell);
                productTableBody.appendChild(errorRow);
            }
        };

        // Attach debounced search handler (400ms delay to prevent database flooding)
        if (searchInput) {
            const debouncedSearch = debounce(performSearch, 400);
            
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.trim();
                debouncedSearch(query);
            });
        }

        // ============================================================================
        // EVENT DELEGATION FOR ACTION BUTTONS
        // ============================================================================
        
        if (productTableBody) {
            productTableBody.addEventListener('click', (e) => {
                const button = e.target.closest('.btn-icon');
                if (!button) return;

                const action = button.dataset.action;
                const productId = button.dataset.productId;
                const productName = button.dataset.productName;

                if (action === 'edit') {
                    // Extract product data from button attributes
                    const productSku = button.dataset.productSku;
                    const productCategory = button.dataset.productCategory;
                    const productQuantity = button.dataset.productQuantity;
                    const productPrice = button.dataset.productPrice;
                    const productStatus = button.dataset.productStatus;

                    // Store original SKU for comparison
                    originalSku = productSku;
                    
                    // Clear any previous SKU errors
                    clearSkuError('edit');
                    enableSubmitButton('edit');
                    
                    // Dynamically set form action to point to edit endpoint with product ID
                    editProductForm.action = `${window.location.origin}/AmenERP/public/inventory/edit/${productId}`;

                    // Populate form fields with product data
                    document.getElementById('editProductId').value = productId;
                    document.getElementById('editProductName').value = productName;
                    document.getElementById('editProductSku').value = productSku;
                    document.getElementById('editProductCategory').value = productCategory;
                    document.getElementById('editProductQuantity').value = productQuantity;
                    document.getElementById('editProductPrice').value = productPrice;
                    document.getElementById('editProductStatus').value = productStatus;

                    // Show Edit modal
                    editModal.classList.remove('hidden');
                    document.body.classList.add('modal-open');

                    console.log(`✏️ Editing product ID: ${productId} - ${productName}`);
                } else if (action === 'delete') {
                    // Confirm deletion
                    if (confirm(`Are you sure you want to delete "${productName}"?\n\nThis action cannot be undone.`)) {
                        // Create a hidden form for POST request with CSRF token
                        const deleteForm = document.createElement('form');
                        deleteForm.method = 'POST';
                        deleteForm.action = `${window.location.origin}/AmenERP/public/inventory/delete/${productId}`;
                        deleteForm.style.display = 'none';

                        // Add CSRF token
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = 'csrf_token';
                        csrfInput.value = '<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>';
                        deleteForm.appendChild(csrfInput);

                        // Append to body and submit
                        document.body.appendChild(deleteForm);
                        deleteForm.submit();

                        console.log(`🗑️ Deleting product ID: ${productId} - ${productName}`);
                    }
                }
            });
        }

        // ============================================================================
        // SKU UNIQUENESS CHECK (REAL-TIME VALIDATION)
        // ============================================================================
        
        const productSkuInput = document.getElementById('productSku');
        const editProductSkuInput = document.getElementById('editProductSku');
        const addSubmitBtn = addProductForm?.querySelector('button[type="submit"]');
        const editSubmitBtn = editProductForm?.querySelector('button[type="submit"]');
        
        let skuCheckTimeout = null;
        let originalSku = ''; // Store original SKU for edit mode
        
        /**
         * Check SKU availability via API
         * @param {string} sku - SKU to check
         * @param {string} mode - 'add' or 'edit'
         */
        const checkSkuAvailability = async (sku, mode) => {
            // Skip check if empty or invalid
            if (!sku || sku.length < 2) {
                clearSkuError(mode);
                enableSubmitButton(mode);
                return;
            }
            
            // For edit mode, skip check if SKU hasn't changed
            if (mode === 'edit' && sku === originalSku) {
                clearSkuError(mode);
                enableSubmitButton(mode);
                return;
            }
            
            try {
                const response = await fetch(`${baseUrl}/api/inventory/check-sku?sku=${encodeURIComponent(sku)}`);
                const result = await response.json();
                
                if (result.exists) {
                    showSkuError(mode, 'This SKU is already in use. Please choose a different one.');
                    disableSubmitButton(mode);
                } else {
                    clearSkuError(mode);
                    enableSubmitButton(mode);
                }
            } catch (error) {
                console.error('SKU check error:', error);
                // On error, allow submission (fail open)
                clearSkuError(mode);
                enableSubmitButton(mode);
            }
        };
        
        /**
         * Show SKU error message
         * @param {string} mode - 'add' or 'edit'
         * @param {string} message - Error message
         */
        const showSkuError = (mode, message) => {
            const errorElement = document.getElementById(mode === 'add' ? 'skuError' : 'editSkuError');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
                errorElement.style.color = 'var(--color-error)';
                errorElement.style.fontSize = 'var(--font-size-sm)';
                errorElement.style.marginTop = 'var(--space-xs)';
            }
        };
        
        /**
         * Clear SKU error message
         * @param {string} mode - 'add' or 'edit'
         */
        const clearSkuError = (mode) => {
            const errorElement = document.getElementById(mode === 'add' ? 'skuError' : 'editSkuError');
            if (errorElement) {
                errorElement.style.display = 'none';
                errorElement.textContent = '';
            }
        };
        
        /**
         * Disable submit button
         * @param {string} mode - 'add' or 'edit'
         */
        const disableSubmitButton = (mode) => {
            const btn = mode === 'add' ? addSubmitBtn : editSubmitBtn;
            if (btn) {
                btn.disabled = true;
                btn.style.opacity = '0.5';
                btn.style.cursor = 'not-allowed';
            }
        };
        
        /**
         * Enable submit button
         * @param {string} mode - 'add' or 'edit'
         */
        const enableSubmitButton = (mode) => {
            const btn = mode === 'add' ? addSubmitBtn : editSubmitBtn;
            if (btn) {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
            }
        };
        
        // Attach SKU check to Add form
        if (productSkuInput) {
            productSkuInput.addEventListener('input', (e) => {
                clearTimeout(skuCheckTimeout);
                const sku = e.target.value.trim().toUpperCase();
                e.target.value = sku; // Auto-uppercase
                
                // Debounce API call (500ms)
                skuCheckTimeout = setTimeout(() => {
                    checkSkuAvailability(sku, 'add');
                }, 500);
            });
        }
        
        // Attach SKU check to Edit form
        if (editProductSkuInput) {
            editProductSkuInput.addEventListener('input', (e) => {
                clearTimeout(skuCheckTimeout);
                const sku = e.target.value.trim().toUpperCase();
                e.target.value = sku; // Auto-uppercase
                
                // Debounce API call (500ms)
                skuCheckTimeout = setTimeout(() => {
                    checkSkuAvailability(sku, 'edit');
                }, 500);
            });
        }

        // ============================================================================
        // CLIENT-SIDE TABLE SORTING (ZERO-DEPENDENCY)
        // ============================================================================
        
        const tableHeaders = document.querySelectorAll('.data-table thead th.sortable');
        let currentSortColumn = null;
        let currentSortDirection = 'asc';
        
        /**
         * Sort table by column
         * @param {string} column - Column name to sort by
         */
        const sortTable = (column) => {
            const tbody = productTableBody;
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Toggle sort direction if same column
            if (currentSortColumn === column) {
                currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortDirection = 'asc';
                currentSortColumn = column;
            }
            
            // Sort rows based on column and direction
            rows.sort((a, b) => {
                let aValue, bValue;
                
                if (column === 'quantity') {
                    // Numeric sort for quantity
                    aValue = parseInt(a.dataset.quantity) || 0;
                    bValue = parseInt(b.dataset.quantity) || 0;
                } else if (column === 'name') {
                    // Alphabetic sort for name
                    aValue = (a.dataset.name || '').toLowerCase();
                    bValue = (b.dataset.name || '').toLowerCase();
                } else if (column === 'sku') {
                    // Alphabetic sort for SKU
                    aValue = (a.dataset.sku || '').toLowerCase();
                    bValue = (b.dataset.sku || '').toLowerCase();
                }
                
                // Compare values
                if (aValue < bValue) return currentSortDirection === 'asc' ? -1 : 1;
                if (aValue > bValue) return currentSortDirection === 'asc' ? 1 : -1;
                return 0;
            });
            
            // Clear tbody and re-append sorted rows
            tbody.innerHTML = '';
            rows.forEach(row => tbody.appendChild(row));
            
            // Update sort indicators
            updateSortIndicators(column);
        };
        
        /**
         * Update visual sort indicators on headers
         * @param {string} activeColumn - Currently sorted column
         */
        const updateSortIndicators = (activeColumn) => {
            tableHeaders.forEach(header => {
                const indicator = header.querySelector('.sort-indicator');
                const column = header.dataset.sort;
                
                if (column === activeColumn) {
                    indicator.textContent = currentSortDirection === 'asc' ? ' ▲' : ' ▼';
                    header.style.fontWeight = 'bold';
                } else {
                    indicator.textContent = '';
                    header.style.fontWeight = 'normal';
                }
            });
        };
        
        // Attach click handlers to sortable headers
        tableHeaders.forEach(header => {
            header.style.cursor = 'pointer';
            header.style.userSelect = 'none';
            
            header.addEventListener('click', () => {
                const column = header.dataset.sort;
                sortTable(column);
                console.log(`📊 Sorted by ${column} (${currentSortDirection})`);
            });
        });

        // ============================================================================
        // INITIALIZATION
        // ============================================================================
        
        console.log('📦 Inventory Module loaded with Real-Time Search, SKU Validation & Table Sorting!');
        console.log('📊 Statistics:', {
            totalItems: <?php echo $stats['total_items']; ?>,
            lowStock: <?php echo $stats['low_stock_count']; ?>,
            totalValue: '<?php echo formatCurrency($stats['total_value']); ?>'
        });
    })();
</script>

<!-- Made with Bob -->

// Made with Bob
