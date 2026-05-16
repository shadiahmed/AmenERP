<?php

declare(strict_types=1);

/**
 * Executive Dashboard - AmenERP Home Landing Page
 * 
 * Primary executive landing dashboard displaying:
 * - Executive Summary: Cash Liquidity, Stock Capital, Monthly Revenue, Monthly Payroll
 * - Operational Alerts: Low stock warnings with procurement links
 * - Recent Activity Feed: Unified Sales and Procurement timeline
 * 
 * Uses centralized DashboardModel for all analytical queries.
 * Implements semantic HTML5 markup with modern CSS Grid and Flexbox layouts.
 * 
 * @package AmenERP\Modules\Home
 * @author Bob (Senior UI/UX Engineer)
 * @version 3.0.0
 */

// ============================================================================
// 1. BOOTSTRAP & INITIALIZE DASHBOARD MODEL
// ============================================================================

// Load the centralized dashboard model
require_once MODULES_PATH . '/home/models/DashboardModel.php';

// Instantiate the dashboard model
$dashboardModel = new DashboardModel();

// ============================================================================
// 2. FETCH DASHBOARD DATA
// ============================================================================

// Get executive summary metrics
$summary = $dashboardModel->getExecutiveSummary();

// Get operational alerts (low stock products)
$lowStockAlerts = $dashboardModel->getOperationalAlerts();

// Get recent activity feed (unified sales & procurement)
$activityFeed = $dashboardModel->getRecentActivityFeed();

// Calculate monthly profit/loss
$monthlyProfit = $summary['monthly_revenue'] - $summary['monthly_payroll'];

?>
    <main>
        <!-- ============================================================================
             Dashboard Header Section
             ============================================================================ -->
        <header class="dashboard-header">
            <h1 class="dashboard-title">Executive Dashboard</h1>
            <p class="dashboard-subtitle">Real-time business intelligence and operational metrics</p>
        </header>

        <!-- ============================================================================
             Executive Summary Metrics Grid
             ============================================================================ -->
        <section class="metrics-grid">
            <!-- Total Cash Liquidity Card -->
            <article class="metric-card">
                <div class="metric-header">
                    <h2 class="metric-title">Total Cash Liquidity</h2>
                    <span class="metric-icon">💰</span>
                </div>
                <div class="metric-value">$<?php echo number_format($summary['total_cash'], 2); ?></div>
                <p class="metric-description">Current active balance in Cash Safe</p>
            </article>

            <!-- Capital in Stock Card -->
            <article class="metric-card metric-card--success">
                <div class="metric-header">
                    <h2 class="metric-title">Capital in Stock</h2>
                    <span class="metric-icon">📦</span>
                </div>
                <div class="metric-value">$<?php echo number_format($summary['stock_value'], 2); ?></div>
                <p class="metric-description">Total inventory asset value</p>
            </article>

            <!-- Monthly Sales Revenue Card -->
            <article class="metric-card metric-card--success">
                <div class="metric-header">
                    <h2 class="metric-title">Monthly Sales Revenue</h2>
                    <span class="metric-icon">📈</span>
                </div>
                <div class="metric-value">$<?php echo number_format($summary['monthly_revenue'], 2); ?></div>
                <p class="metric-description">Sales income for <?php echo date('F Y'); ?></p>
            </article>

            <!-- Monthly Payroll Expense Card -->
            <article class="metric-card metric-card--warning">
                <div class="metric-header">
                    <h2 class="metric-title">Monthly Payroll Expense</h2>
                    <span class="metric-icon">👥</span>
                </div>
                <div class="metric-value">$<?php echo number_format($summary['monthly_payroll'], 2); ?></div>
                <p class="metric-description">HR payroll costs for <?php echo date('F Y'); ?></p>
            </article>
        </section>

        <!-- ============================================================================
             Twin-Column Content Layout
             ============================================================================ -->
        <section class="content-grid">
            <!-- Left Column: Low Stock Alerts -->
            <article class="card">
                <header class="card-header">
                    <h2 class="card-title">Low Stock Alerts</h2>
                    <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/procurement" class="btn btn--outline">
                        Replenish Stock
                    </a>
                </header>
                <div class="card-body card-body--no-padding">
                    <?php if (empty($lowStockAlerts)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">✅</div>
                            <p class="empty-state-text">All products are well stocked</p>
                        </div>
                    <?php else: ?>
                        <ul class="alert-list">
                            <?php foreach ($lowStockAlerts as $alert): ?>
                                <li class="alert-item">
                                    <div class="alert-info">
                                        <div class="alert-name"><?php echo htmlspecialchars($alert['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="alert-details">
                                            SKU: <?php echo htmlspecialchars($alert['sku'], ENT_QUOTES, 'UTF-8'); ?> | 
                                            Category: <?php echo htmlspecialchars($alert['category_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </div>
                                    <div class="alert-badge">
                                        <?php echo $alert['quantity']; ?> left
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </article>

            <!-- Right Column: Recent Activity Feed -->
            <article class="card">
                <header class="card-header">
                    <h2 class="card-title">Recent Activity</h2>
                    <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/sales" class="btn btn--outline">
                        View All
                    </a>
                </header>
                <div class="card-body card-body--no-padding">
                    <?php if (empty($activityFeed)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <p class="empty-state-text">No recent activity</p>
                        </div>
                    <?php else: ?>
                        <ul class="activity-list">
                            <?php foreach ($activityFeed as $activity): ?>
                                <li class="activity-item">
                                    <div class="activity-icon activity-icon--<?php echo $activity['type']; ?>">
                                        <?php echo $activity['type'] === 'sale' ? '💰' : '📦'; ?>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">
                                            <?php echo htmlspecialchars($activity['reference'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                        <div class="activity-details">
                                            <?php echo $activity['type'] === 'sale' ? 'Customer' : 'Supplier'; ?>: 
                                            <?php echo htmlspecialchars($activity['party_name'], ENT_QUOTES, 'UTF-8'); ?> | 
                                            <span class="activity-amount">$<?php echo number_format($activity['total_amount'], 2); ?></span> | 
                                            <?php echo date('M d, Y', strtotime($activity['created_at'])); ?>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </article>
        </section>
    </main>

<!-- Made with Bob -->

// Made with Bob
