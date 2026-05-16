<?php

declare(strict_types=1);

/**
 * Home Controller
 * 
 * Displays the welcome dashboard for AmenERP
 * 
 * @package AmenERP\Modules\Home
 * @author Bob
 * @version 1.0.0
 */

?>

<!-- Content Header -->
<div class="content-header">
    <h2>Dashboard</h2>
    <div class="breadcrumb">
        <span>Home</span>
    </div>
</div>

<!-- Dashboard Stats Grid -->
<div class="grid grid-cols-4">
    <div class="card">
        <div class="card-body">
            <h3 class="text-secondary" style="font-size: var(--font-size-sm); margin-bottom: var(--space-sm);">Total Revenue</h3>
            <p style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); color: var(--color-success);">$45,231</p>
            <p class="text-secondary" style="font-size: var(--font-size-sm); margin-top: var(--space-sm);">↑ 12% from last month</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <h3 class="text-secondary" style="font-size: var(--font-size-sm); margin-bottom: var(--space-sm);">Total Orders</h3>
            <p style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); color: var(--color-primary);">1,234</p>
            <p class="text-secondary" style="font-size: var(--font-size-sm); margin-top: var(--space-sm);">↑ 8% from last month</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <h3 class="text-secondary" style="font-size: var(--font-size-sm); margin-bottom: var(--space-sm);">Inventory Items</h3>
            <p style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); color: var(--color-secondary);">567</p>
            <p class="text-secondary" style="font-size: var(--font-size-sm); margin-top: var(--space-sm);">23 low stock alerts</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <h3 class="text-secondary" style="font-size: var(--font-size-sm); margin-bottom: var(--space-sm);">Active Users</h3>
            <p style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold); color: var(--color-info);">89</p>
            <p class="text-secondary" style="font-size: var(--font-size-sm); margin-top: var(--space-sm);">↑ 5% from last month</p>
        </div>
    </div>
</div>

<!-- System Status -->
<div class="card">
    <div class="card-header">
        <h3>System Status</h3>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-2">
            <div>
                <p class="mb-md">
                    <span class="text-success" style="font-weight: var(--font-weight-semibold);">✓</span>
                    <strong>Configuration:</strong> Loaded successfully
                </p>
                <p class="mb-md">
                    <span class="text-success" style="font-weight: var(--font-weight-semibold);">✓</span>
                    <strong>Database:</strong> Connection initialized
                </p>
                <p class="mb-md">
                    <span class="text-success" style="font-weight: var(--font-weight-semibold);">✓</span>
                    <strong>Router:</strong> System active
                </p>
            </div>
            <div>
                <p class="mb-md">
                    <strong>Environment:</strong> <span class="text-primary"><?php echo htmlspecialchars(ENVIRONMENT); ?></span>
                </p>
                <p class="mb-md">
                    <strong>Version:</strong> <span class="text-secondary"><?php echo htmlspecialchars(APP_VERSION); ?></span>
                </p>
                <p class="mb-md">
                    <strong>Timezone:</strong> <span class="text-secondary"><?php echo htmlspecialchars(APP_TIMEZONE); ?></span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="grid grid-cols-2">
    <div class="card">
        <div class="card-header">
            <h3>Recent Orders</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#ORD-001</td>
                            <td>John Doe</td>
                            <td>$1,234.56</td>
                            <td><span class="bg-success" style="padding: var(--space-xs) var(--space-sm); border-radius: var(--radius-sm); font-size: var(--font-size-xs);">Completed</span></td>
                        </tr>
                        <tr>
                            <td>#ORD-002</td>
                            <td>Jane Smith</td>
                            <td>$987.65</td>
                            <td><span class="bg-warning" style="padding: var(--space-xs) var(--space-sm); border-radius: var(--radius-sm); font-size: var(--font-size-xs);">Pending</span></td>
                        </tr>
                        <tr>
                            <td>#ORD-003</td>
                            <td>Bob Johnson</td>
                            <td>$2,345.67</td>
                            <td><span class="bg-success" style="padding: var(--space-xs) var(--space-sm); border-radius: var(--radius-sm); font-size: var(--font-size-xs);">Completed</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <a href="<?php echo BASE_URL; ?>/orders" class="btn btn-outline">View All Orders</a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Quick Actions</h3>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-direction: column; gap: var(--space-md);">
                <a href="<?php echo BASE_URL; ?>/inventory/add" class="btn btn-primary">
                    <span>📦</span>
                    <span>Add New Product</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/orders/create" class="btn btn-primary">
                    <span>🛒</span>
                    <span>Create Order</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/finance/invoice" class="btn btn-secondary">
                    <span>💰</span>
                    <span>Generate Invoice</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/reports" class="btn btn-outline">
                    <span>📊</span>
                    <span>View Reports</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Router Information (Development Only) -->
<?php if (ENVIRONMENT === 'development'): ?>
<div class="card">
    <div class="card-header">
        <h3>Router Information</h3>
    </div>
    <div class="card-body">
        <p class="mb-sm"><strong>Current URI:</strong> <code><?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/'); ?></code></p>
        <p class="mb-sm"><strong>Request Method:</strong> <code><?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'GET'); ?></code></p>
        <p class="mb-sm"><strong>Base URL:</strong> <code><?php echo htmlspecialchars(BASE_URL); ?></code></p>
        <p class="text-secondary" style="font-size: var(--font-size-sm); margin-top: var(--space-md);">
            💡 This information is only visible in development mode. Define routes in <code>public/index.php</code>.
        </p>
    </div>
</div>
<?php endif; ?>

<!-- Made with Bob -->

// Made with Bob
