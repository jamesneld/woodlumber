<?php
require_once __DIR__ . '/includes/db.php';

$page_title = 'Dashboard Overview';
$active_page = 'dashboard';

$metrics = get_dashboard_metrics();
$db = get_db_data();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="app-main">
    <!-- Top Bar -->
    <header class="app-topbar">
        <div class="page-title-group">
            <h1>
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="9"></rect>
                    <rect x="14" y="3" width="7" height="5"></rect>
                    <rect x="14" y="12" width="7" height="9"></rect>
                    <rect x="3" y="16" width="7" height="5"></rect>
                </svg>
                Dashboard Overview
            </h1>
            <p>Wood Lumber Operations &amp; Financial Summary</p>
        </div>

        <div class="topbar-clock">
            <span class="pulse-dot"></span>
            <span class="live-date-display"><?php echo date('F d, Y'); ?></span>
            <span>&bull;</span>
            <span class="live-time-display"><?php echo date('h:i:s A'); ?></span>
        </div>
    </header>

    <!-- Page Content -->
    <main class="page-content">

        <!-- 5 Requested Metric Cards (Philippine Currency) -->
        <div class="grid-cards">
            <!-- 1. Total Profit -->
            <div class="metric-card profit">
                <div class="metric-header">
                    <span class="metric-title">Total Profit</span>
                    <div class="metric-icon-wrap">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                </div>
                <div class="metric-value"> <?php echo number_format($metrics['total_profit'], 2); ?></div>
                <div class="metric-sub">Net earnings from lumber sales &amp; operations</div>
            </div>

            <!-- 2. Total Credit -->
            <div class="metric-card credit">
                <div class="metric-header">
                    <span class="metric-title">Total Credit</span>
                    <div class="metric-icon-wrap">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                            <line x1="2" y1="10" x2="22" y2="10"></line>
                        </svg>
                    </div>
                </div>
                <div class="metric-value"> <?php echo number_format($metrics['total_credit'], 2); ?></div>
                <div class="metric-sub">
                    Customer: <strong><?php echo number_format($metrics['customer_credit'], 2); ?></strong> | 
                    Tree Owner: <strong><?php echo number_format($metrics['owner_credit'], 2); ?></strong>
                </div>
            </div>

            <!-- 3. Total Purchaser -->
            <div class="metric-card purchaser">
                <div class="metric-header">
                    <span class="metric-title">Total Purchaser</span>
                    <div class="metric-icon-wrap">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                    </div>
                </div>
                <div class="metric-value"><?php echo number_format($metrics['purchaser_count']); ?></div>
                <div class="metric-sub">Total Orders (Sales: <?php echo number_format($metrics['total_sales'], 2); ?>)</div>
            </div>

            <!-- 4. Total Expenses -->
            <div class="metric-card expenses">
                <div class="metric-header">
                    <span class="metric-title">Total Expenses</span>
                    <div class="metric-icon-wrap">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline>
                            <polyline points="17 18 23 18 23 12"></polyline>
                        </svg>
                    </div>
                </div>
                <div class="metric-value"> <?php echo number_format($metrics['expenses'], 2); ?></div>
                <div class="metric-sub">Fuel, maintenance, labor &amp; logistics</div>
            </div>

            <!-- 5. Total Capital -->
            <div class="metric-card capital">
                <div class="metric-header">
                    <span class="metric-title">Total Capital</span>
                    <div class="metric-icon-wrap">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2v20"></path>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                </div>
                <div class="metric-value"> <?php echo number_format($metrics['capital'], 2); ?></div>
                <div class="metric-sub">Initial business investment / Capital</div>
            </div>
        </div>

        <!-- Quick Summary Grids -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
            <!-- Recent Purchases -->
            <div class="content-card">
                <div class="card-header-bar">
                    <h3>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle>
                            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                        </svg>
                        Recent Customer Purchases
                    </h3>
                    <a href="purchase.php" class="btn btn-secondary btn-sm">View All Purchases &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th>Wood / Dimension</th>
                                <th>Pieces</th>
                                <th>Total Amount</th>
                                <th>Payment</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($db['purchases'])): ?>
                                <tr><td colspan="6" style="text-align:center; color:#94a3b8; padding: 1.5rem;">No purchase records found.</td></tr>
                            <?php else: ?>
                                <?php foreach (array_slice(array_reverse($db['purchases']), 0, 5) as $purchase): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($purchase['customer_name']); ?></strong></td>
                                        <td>
                                            <span class="badge badge-wood"><?php echo htmlspecialchars($purchase['wood_type']); ?></span>
                                            <small><?php echo htmlspecialchars($purchase['dimension']); ?></small>
                                        </td>
                                        <td><?php echo number_format($purchase['pieces']); ?> pcs</td>
                                        <td><strong> <?php echo number_format($purchase['total_amount'], 2); ?></strong></td>
                                        <td>
                                            <?php if ($purchase['payment_type'] === 'Cash'): ?>
                                                <span class="badge badge-success">Cash</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Credit</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($purchase['date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Scale & Credit Snapshot -->
            <div class="content-card">
                <div class="card-header-bar">
                    <h3>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                            <line x1="2" y1="10" x2="22" y2="10"></line>
                        </svg>
                        Credit Status Overview
                    </h3>
                    <a href="credit.php" class="btn btn-secondary btn-sm">View Credits &rarr;</a>
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 1.25rem; padding: 1rem; background: #fff7ed; border-radius: 8px; border: 1px solid #fed7aa;">
                        <div style="font-size: 0.8rem; text-transform: uppercase; color: #9a3412; font-weight: 700;">Customer Credit (Receivables)</div>
                        <div style="font-size: 1.4rem; font-weight: 800; color: #ea580c; margin-top: 0.25rem;"> <?php echo number_format($metrics['customer_credit'], 2); ?></div>
                        <div style="font-size: 0.78rem; color: #7c2d12; margin-top: 0.2rem;">Collectible receivables from lumber released on credit.</div>
                    </div>

                    <div style="padding: 1rem; background: #fef2f2; border-radius: 8px; border: 1px solid #fecaca;">
                        <div style="font-size: 0.8rem; text-transform: uppercase; color: #991b1b; font-weight: 700;">Tree Owner Payables (Wood Compra)</div>
                        <div style="font-size: 1.4rem; font-weight: 800; color: #dc2626; margin-top: 0.25rem;"> <?php echo number_format($metrics['owner_credit'], 2); ?></div>
                        <div style="font-size: 0.78rem; color: #7f1d1d; margin-top: 0.2rem;">Outstanding balance owed to tree owners for acquired lumber.</div>
                    </div>

                    <div style="margin-top: 1.5rem; text-align: center;">
                        <a href="scale.php" class="btn btn-primary" style="width: 100%;">
                            + Scale New Lumber
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
