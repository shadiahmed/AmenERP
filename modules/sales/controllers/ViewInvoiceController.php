<?php
/**
 * View Invoice Controller
 * 
 * Displays detailed information for a specific sales order/invoice.
 * Shows order header, line items, customer details, and totals.
 * 
 * @package AmenERP\Modules\Sales\Controllers
 * @author Bob
 * @version 1.0.0
 */

declare(strict_types=1);

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load required models
require_once __DIR__ . '/../models/SalesModel.php';

// Get order ID from route parameter (passed by Router, not $_GET)
$orderId = isset($params['id']) ? (int)$params['id'] : 0;

if ($orderId <= 0) {
    $_SESSION['error'] = 'Invalid order ID';
    header('Location: ' . BASE_URL . '/sales');
    exit;
}

// Initialize model
$salesModel = new SalesModel();

// Fetch order details
$order = $salesModel->getSalesOrderById($orderId);

if (!$order) {
    $_SESSION['error'] = 'Order not found';
    header('Location: ' . BASE_URL . '/sales');
    exit;
}

// Helper function to format currency
function formatCurrency(float $amount): string
{
    return '$' . number_format($amount, 2);
}

?>

<div class="module-container sales-module invoice-view">
    <div class="module-header">
        <h2>Invoice Details</h2>
        <a href="<?php echo BASE_URL; ?>/sales" class="btn btn-secondary">← Back to Sales</a>
    </div>

    <!-- Invoice Header -->
    <section class="card">
        <div class="card-header">
            <h3>Invoice #<?php echo htmlspecialchars($order['invoice_number'], ENT_QUOTES, 'UTF-8'); ?></h3>
        </div>
        <div class="card-body">
            <div class="invoice-details-grid">
                <div class="detail-item">
                    <strong>Customer:</strong>
                    <span><?php echo htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Date:</strong>
                    <span><?php echo date('F d, Y', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="detail-item">
                    <strong>Total Amount:</strong>
                    <span class="amount"><?php echo formatCurrency($order['total_amount']); ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- Invoice Items -->
    <section class="card">
        <div class="card-header">
            <h3>Items</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($item['product_sku'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int)$item['quantity']; ?></td>
                                <td><?php echo formatCurrency($item['unit_price']); ?></td>
                                <td class="amount"><?php echo formatCurrency($item['line_total']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right"><strong>Total:</strong></td>
                            <td class="amount"><strong><?php echo formatCurrency($order['total_amount']); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </section>

    <!-- Actions -->
    <div class="form-actions">
        <button onclick="window.print()" class="btn btn-primary">
            <span class="btn-icon">🖨️</span>
            Print Invoice
        </button>
        <a href="<?php echo BASE_URL; ?>/sales" class="btn btn-secondary">
            Back to Sales
        </a>
    </div>
</div>

<?php
// Made with Bob