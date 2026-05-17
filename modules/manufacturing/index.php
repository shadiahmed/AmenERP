<?php
/**
 * Manufacturing Module - Production Tracking Dashboard
 *
 * Shop floor operations interface for managing Bills of Materials (BOM),
 * production runs, and material assembly tracking.
 *
 * Features:
 * - Active Bills of Materials listing with component counts
 * - Production run tracking by status (pending, in_progress, completed)
 * - Launch new batch runs with BOM selection
 * - Complete in-progress production runs
 * - Real-time status updates
 * - CSRF protection
 * - Responsive mobile-first design
 *
 * @package AmenERP\Modules\Manufacturing
 * @author Bob
 * @version 1.0.0
 */

declare(strict_types=1);

// ============================================================================
// BOOTSTRAP: Core Configuration & Dependencies
// ============================================================================

/**
 * Ensure session is active
 * Safety check for direct access scenarios
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Load required core classes
 */
if (!class_exists('Database')) {
    require_once __DIR__ . '/../../core/Database.php';
}

if (!class_exists('Csrf')) {
    require_once __DIR__ . '/../../core/Csrf.php';
}

/**
 * Load Manufacturing Model
 */
require_once __DIR__ . '/models/ManufacturingModel.php';

// ============================================================================
// DATA INITIALIZATION
// ============================================================================

/**
 * Initialize model instance
 */
$manufacturingModel = new ManufacturingModel();

/**
 * Fetch all Bills of Materials with component details
 */
$db = Database::getInstance()->getConnection();
$bomQuery = "SELECT bom.*, p.name as product_name, p.sku as product_sku,
                    (SELECT COUNT(*) FROM bom_items WHERE bom_id = bom.id) as component_count
             FROM bills_of_materials bom
             INNER JOIN products p ON bom.product_id = p.id
             ORDER BY bom.created_at DESC";
$bomStmt = $db->query($bomQuery);
$billsOfMaterials = $bomStmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Fetch all products for BOM creation dropdowns
 */
$productsQuery = "SELECT id, name, sku, quantity FROM products ORDER BY name ASC";
$productsStmt = $db->query($productsQuery);
$allProducts = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Fetch production runs grouped by status
 */
$pendingRuns = $manufacturingModel->getAllProductionRuns('pending');
$inProgressRuns = $manufacturingModel->getAllProductionRuns('in_progress');
$completedRuns = $manufacturingModel->getAllProductionRuns('completed');

/**
 * Generate CSRF token for forms
 */
$csrfToken = Csrf::generateToken();

/**
 * Extract flash messages from session
 */
$successMessage = $_SESSION['success'] ?? null;
$errorMessage = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

?>

<div class="erp-container">
    <!-- Flash Messages -->
    <?php if ($successMessage): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">Manufacturing Floor Operations</h1>
        <p class="page-subtitle">Manage production runs and Bills of Materials</p>
    </div>

    <!-- Two-Column Layout: BOMs + Production Runs -->
    <div class="manufacturing-grid">
        <!-- Left Panel: Bills of Materials -->
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Bills of Materials</h2>
                <button type="button" class="btn btn-primary" id="createBomBtn">➕ Create New BOM</button>
            </div>

            <div class="card-body">
                <?php if (empty($billsOfMaterials)): ?>
                    <div class="empty-state">
                        <p>No Bills of Materials defined yet. Create your first BOM to start production.</p>
                    </div>
                <?php else: ?>
                    <div class="bom-list">
                        <?php foreach ($billsOfMaterials as $bom): ?>
                            <article class="bom-card">
                                <div class="bom-header">
                                    <span class="bom-code"><?php echo htmlspecialchars($bom['bom_code'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="badge badge-info"><?php echo $bom['component_count']; ?> components</span>
                                </div>
                                <div class="bom-title">
                                    <strong><?php echo htmlspecialchars($bom['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                                <div class="bom-product">
                                    Product: <?php echo htmlspecialchars($bom['product_name'], ENT_QUOTES, 'UTF-8'); ?> 
                                    (<?php echo htmlspecialchars($bom['product_sku'], ENT_QUOTES, 'UTF-8'); ?>)
                                </div>
                                <div class="bom-actions">
                                    <button type="button" 
                                            class="btn btn-primary btn-small launch-batch-btn" 
                                            data-bom-id="<?php echo $bom['id']; ?>" 
                                            data-bom-name="<?php echo htmlspecialchars($bom['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        🚀 Launch Batch Run
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Right Panel: Production Runs -->
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Production Runs</h2>
            </div>

            <div class="card-body">
                <div class="production-runs-grid">
                    <!-- Pending Runs -->
                    <div class="run-column">
                        <h3 class="run-column-title">Pending (<?php echo count($pendingRuns); ?>)</h3>
                        <?php if (empty($pendingRuns)): ?>
                            <div class="empty-state-small">
                                <p>No pending runs</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pendingRuns as $run): ?>
                                <article class="run-card status-pending">
                                    <div class="run-batch"><?php echo htmlspecialchars($run['batch_number'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="run-details">
                                        <strong><?php echo htmlspecialchars($run['product_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                        BOM: <?php echo htmlspecialchars($run['bom_code'], ENT_QUOTES, 'UTF-8'); ?><br>
                                        Quantity: <?php echo number_format($run['quantity_to_produce']); ?> units<br>
                                        Created: <?php echo date('Y-m-d H:i', strtotime($run['created_at'])); ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- In Progress Runs -->
                    <div class="run-column">
                        <h3 class="run-column-title">In Progress (<?php echo count($inProgressRuns); ?>)</h3>
                        <?php if (empty($inProgressRuns)): ?>
                            <div class="empty-state-small">
                                <p>No active runs</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($inProgressRuns as $run): ?>
                                <article class="run-card status-in-progress">
                                    <div class="run-batch"><?php echo htmlspecialchars($run['batch_number'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="run-details">
                                        <strong><?php echo htmlspecialchars($run['product_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                        BOM: <?php echo htmlspecialchars($run['bom_code'], ENT_QUOTES, 'UTF-8'); ?><br>
                                        Quantity: <?php echo number_format($run['quantity_to_produce']); ?> units<br>
                                        Started: <?php echo date('Y-m-d H:i', strtotime($run['created_at'])); ?>
                                    </div>
                                    <div class="run-actions">
                                        <form method="POST" action="<?php echo BASE_URL; ?>/manufacturing/process" class="inline-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="action" value="complete">
                                            <input type="hidden" name="run_id" value="<?php echo $run['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-small" onclick="return confirm('Complete this production run?')">
                                                ✓ Complete Production
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Completed Runs -->
                    <div class="run-column">
                        <h3 class="run-column-title">Completed (<?php echo count($completedRuns); ?>)</h3>
                        <?php if (empty($completedRuns)): ?>
                            <div class="empty-state-small">
                                <p>No completed runs</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($completedRuns, 0, 5) as $run): ?>
                                <article class="run-card status-completed">
                                    <div class="run-batch"><?php echo htmlspecialchars($run['batch_number'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="run-details">
                                        <strong><?php echo htmlspecialchars($run['product_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                        BOM: <?php echo htmlspecialchars($run['bom_code'], ENT_QUOTES, 'UTF-8'); ?><br>
                                        Quantity: <?php echo number_format($run['quantity_to_produce']); ?> units<br>
                                        Completed: <?php echo date('Y-m-d H:i', strtotime($run['updated_at'])); ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modal: Launch Batch Run -->
<div class="modal-overlay" id="launchBatchModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Launch Production Batch</h3>
                <button type="button" class="modal-close" id="closeModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="<?php echo BASE_URL; ?>/manufacturing/process" id="launchBatchForm" class="form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="start">
                    <input type="hidden" name="bom_id" id="modalBomId">

                    <div class="form-group">
                        <label class="form-label">Bill of Materials</label>
                        <input type="text" class="form-input" id="modalBomName" readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="modalQuantity">Quantity to Produce <span class="required">*</span></label>
                        <input type="number" class="form-input" id="modalQuantity" name="quantity" min="1" max="10000" required>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelModalBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary">🚀 Start Production</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Create Bill of Materials -->
<div class="modal-overlay" id="createBomModal">
    <div class="modal-dialog modal-large">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Create New Bill of Materials</h3>
                <button type="button" class="modal-close" id="closeCreateBomBtn">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="<?php echo BASE_URL; ?>/manufacturing/bom" id="createBomForm" class="form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                    <div class="form-group">
                        <label class="form-label" for="finishedProductId">Finished Product <span class="required">*</span></label>
                        <select class="form-select" id="finishedProductId" name="finished_product_id" required>
                            <option value="">-- Select Finished Product --</option>
                            <?php foreach ($allProducts as $product): ?>
                                <option value="<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    (<?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="bomName">BOM Name <span class="required">*</span></label>
                        <input type="text" class="form-input" id="bomName" name="bom_name" maxlength="255" required placeholder="e.g., Standard Assembly Recipe">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Components & Quantities <span class="required">*</span></label>
                        <div id="componentsContainer" class="components-container">
                            <!-- Component rows will be added here dynamically -->
                        </div>
                        <button type="button" class="btn btn-secondary" id="addComponentBtn">+ Add Component</button>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancelCreateBomBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create BOM</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="module">
    /**
     * Manufacturing Module - Client-Side Logic
     * Vanilla JavaScript ES6+ for modal handling and form interactions
     */

    // ====================================================================
    // LAUNCH BATCH MODAL - Existing Functionality
    // ====================================================================

    // Get modal elements
    const modal = document.getElementById('launchBatchModal');
    const modalBomId = document.getElementById('modalBomId');
    const modalBomName = document.getElementById('modalBomName');
    const modalQuantity = document.getElementById('modalQuantity');
    const cancelModalBtn = document.getElementById('cancelModalBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const launchBatchBtns = document.querySelectorAll('.launch-batch-btn');

    /**
     * Open modal and populate with BOM data
     */
    launchBatchBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const bomId = e.target.dataset.bomId;
            const bomName = e.target.dataset.bomName;

            // Populate modal fields
            modalBomId.value = bomId;
            modalBomName.value = bomName;
            modalQuantity.value = '';
            modalQuantity.focus();

            // Show modal
            modal.classList.add('active');
        });
    });

    /**
     * Close modal
     */
    function closeLaunchModal() {
        modal.classList.remove('active');
    }

    cancelModalBtn.addEventListener('click', closeLaunchModal);
    closeModalBtn.addEventListener('click', closeLaunchModal);

    /**
     * Close modal on overlay click
     */
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeLaunchModal();
        }
    });

    /**
     * Close modal on Escape key
     */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeLaunchModal();
        }
    });

    // ====================================================================
    // CREATE BOM MODAL - New Functionality
    // ====================================================================

    // Get Create BOM modal elements
    const createBomModal = document.getElementById('createBomModal');
    const createBomBtn = document.getElementById('createBomBtn');
    const cancelCreateBomBtn = document.getElementById('cancelCreateBomBtn');
    const closeCreateBomBtn = document.getElementById('closeCreateBomBtn');
    const createBomForm = document.getElementById('createBomForm');
    const componentsContainer = document.getElementById('componentsContainer');
    const addComponentBtn = document.getElementById('addComponentBtn');

    // Product data for dropdowns (from PHP)
    const products = <?php echo json_encode($allProducts, JSON_THROW_ON_ERROR); ?>;

    // Component row counter for unique IDs
    let componentRowCounter = 0;

    /**
     * Create a new component row with material dropdown and quantity input
     */
    function createComponentRow() {
        componentRowCounter++;
        const rowId = `component-row-${componentRowCounter}`;

        const row = document.createElement('div');
        row.className = 'component-row';
        row.id = rowId;

        // Material dropdown
        const selectWrapper = document.createElement('div');
        selectWrapper.className = 'component-select-wrapper';
        
        const select = document.createElement('select');
        select.className = 'form-select';
        select.name = 'components[]';
        select.required = true;

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = '-- Select Component --';
        select.appendChild(defaultOption);

        products.forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            option.textContent = `${product.name} (${product.sku}) - Stock: ${product.quantity}`;
            select.appendChild(option);
        });

        selectWrapper.appendChild(select);

        // Quantity input
        const inputWrapper = document.createElement('div');
        inputWrapper.className = 'component-input-wrapper';
        
        const input = document.createElement('input');
        input.type = 'number';
        input.className = 'form-input';
        input.name = 'quantities[]';
        input.placeholder = 'Quantity';
        input.step = '0.0001';
        input.min = '0.0001';
        input.max = '999999.9999';
        input.required = true;

        inputWrapper.appendChild(input);

        // Remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-danger btn-small';
        removeBtn.textContent = '✕';
        removeBtn.addEventListener('click', () => {
            row.remove();
            // Ensure at least one component row exists
            if (componentsContainer.children.length === 0) {
                createComponentRow();
            }
        });

        row.appendChild(selectWrapper);
        row.appendChild(inputWrapper);
        row.appendChild(removeBtn);

        componentsContainer.appendChild(row);
    }

    /**
     * Open Create BOM modal
     */
    createBomBtn.addEventListener('click', () => {
        // Clear form
        createBomForm.reset();
        componentsContainer.innerHTML = '';
        
        // Add initial component row
        createComponentRow();

        // Show modal
        createBomModal.classList.add('active');
        
        // Focus on first input
        document.getElementById('finishedProductId').focus();
    });

    /**
     * Close Create BOM modal
     */
    function closeCreateBomModal() {
        createBomModal.classList.remove('active');
    }

    cancelCreateBomBtn.addEventListener('click', closeCreateBomModal);
    closeCreateBomBtn.addEventListener('click', closeCreateBomModal);

    /**
     * Add component row button
     */
    addComponentBtn.addEventListener('click', () => {
        createComponentRow();
    });

    /**
     * Close Create BOM modal on overlay click
     */
    createBomModal.addEventListener('click', (e) => {
        if (e.target === createBomModal) {
            closeCreateBomModal();
        }
    });

    /**
     * Close Create BOM modal on Escape key
     */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && createBomModal.classList.contains('active')) {
            closeCreateBomModal();
        }
    });

    /**
     * Form validation before submission
     * Ensure all quantities are greater than zero
     */
    createBomForm.addEventListener('submit', (e) => {
        const quantityInputs = componentsContainer.querySelectorAll('input[name="quantities[]"]');
        const componentSelects = componentsContainer.querySelectorAll('select[name="components[]"]');

        // Validate at least one component
        if (quantityInputs.length === 0) {
            e.preventDefault();
            alert('Please add at least one component to the BOM.');
            return false;
        }

        // Validate all quantities are greater than zero
        let hasInvalidQuantity = false;
        quantityInputs.forEach(input => {
            const value = parseFloat(input.value);
            if (isNaN(value) || value <= 0) {
                hasInvalidQuantity = true;
                input.classList.add('error');
            } else {
                input.classList.remove('error');
            }
        });

        if (hasInvalidQuantity) {
            e.preventDefault();
            alert('All component quantities must be positive numbers greater than zero.');
            return false;
        }

        // Validate all components are selected
        let hasEmptyComponent = false;
        componentSelects.forEach(select => {
            if (select.value === '') {
                hasEmptyComponent = true;
                select.classList.add('error');
            } else {
                select.classList.remove('error');
            }
        });

        if (hasEmptyComponent) {
            e.preventDefault();
            alert('Please select a component for all rows.');
            return false;
        }

        // Check for duplicate components
        const selectedComponents = Array.from(componentSelects).map(s => s.value);
        const uniqueComponents = new Set(selectedComponents);
        
        if (selectedComponents.length !== uniqueComponents.size) {
            e.preventDefault();
            alert('Duplicate components detected. Each component can only be added once.');
            return false;
        }

        return true;
    });
</script>

<?php
// Made with Bob
