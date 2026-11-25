<?php
require_once __DIR__ . '/includes/db.php';

$page_title = 'Lumber Inventory';
$active_page = 'inventory';

$db = get_db_data();

// Group inventory based on dimensions and scale entries
$inventoryList = $db['dimensions'] ?? [];

// Calculate total stats
$totalPieces = 0;
$totalBdFt = 0;
$totalValue = 0;
$woodCategories = [];

foreach ($inventoryList as $item) {
    $pcs = intval($item['stock_pcs'] ?? 0);
    $bdftSingle = floatval($item['board_feet'] ?? 0);
    $pricePiece = floatval($item['price_per_piece'] ?? 0);

    $totalPieces += $pcs;
    $totalBdFt += ($pcs * $bdftSingle);
    $totalValue += ($pcs * $pricePiece);

    $cat = $item['category'] ?? 'General';
    if (!in_array($cat, $woodCategories)) {
        $woodCategories[] = $cat;
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="app-main">
    <header class="app-topbar">
        <div class="page-title-group">
            <h1>
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                    <path d="m3.3 7 8.7 5 8.7-5"></path>
                    <path d="M12 22V12"></path>
                </svg>
                Lumber Inventory
            </h1>
            <p>Automatically tracked and calculated based on wood scaling and fixed dimensions</p>
        </div>

        <div class="topbar-clock">
            <span class="pulse-dot"></span>
            <span class="live-date-display"><?php echo date('F d, Y'); ?></span>
            <span>&bull;</span>
            <span class="live-time-display"><?php echo date('h:i:s A'); ?></span>
        </div>
    </header>

    <main class="page-content">

        <!-- Inventory Stats -->
        <div class="grid-cards">
            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">Available Stocks</span>
                    <div class="metric-icon-wrap" style="background: #e0f2fe; color: #0284c7;">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path></svg>
                    </div>
                </div>
                <div class="metric-value"><?php echo number_format($totalPieces); ?> <span style="font-size: 1rem; font-weight: 500;">pcs</span></div>
                <div class="metric-sub">Total lumber pieces available in yard/warehouse</div>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">Total Board Feet</span>
                    <div class="metric-icon-wrap" style="background: #fef3c7; color: #b45309;">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line></svg>
                    </div>
                </div>
                <div class="metric-value"><?php echo number_format($totalBdFt, 2); ?> <span style="font-size: 1rem; font-weight: 500;">BF</span></div>
                <div class="metric-sub">Combined volume across all lumber varieties</div>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">Estimated Stock Value</span>
                    <div class="metric-icon-wrap" style="background: #dcfce7; color: #15803d;">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                </div>
                <div class="metric-value"> <?php echo number_format($totalValue, 2); ?></div>
                <div class="metric-sub">Estimated retail value of current stock</div>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <span class="metric-title">Wood Varieties</span>
                    <div class="metric-icon-wrap" style="background: #f1f5f9; color: #475569;">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line></svg>
                    </div>
                </div>
                <div class="metric-value"><?php echo count($woodCategories); ?></div>
                <div class="metric-sub"><?php echo implode(', ', $woodCategories); ?></div>
            </div>
        </div>

        <!-- Inventory Table Section -->
        <div class="content-card">
            <div class="card-header-bar">
                <h2>
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                    Lumber Inventory Stock
                </h2>
                <div style="display: flex; gap: 0.75rem;">
                    <a href="scale.php" class="btn btn-primary btn-sm">+ Scale Wood</a>
                    <a href="dimension.php" class="btn btn-secondary btn-sm">+ Fix Dimension</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>Wood Category</th>
                            <th>Dimension (Thickness &times; Width &times; Length)</th>
                            <th>Bd. Ft. Per Piece</th>
                            <th>Available Stock (Pcs)</th>
                            <th>Total Board Feet</th>
                            <th>Price Per Piece ()</th>
                            <th>Total Value ()</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inventoryList)): ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding: 2rem; color: #94a3b8;">
                                    No lumber currently in inventory. Start by entering wood in <a href="scale.php">Scale</a> or <a href="dimension.php">Dimension</a>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inventoryList as $item): ?>
                                <?php 
                                    $stock = intval($item['stock_pcs'] ?? 0);
                                    $bdftPerPiece = floatval($item['board_feet'] ?? 0);
                                    $itemTotalBdft = $stock * $bdftPerPiece;
                                    $itemTotalPrice = $stock * floatval($item['price_per_piece'] ?? 0);
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-wood" style="font-size: 0.85rem;">
                                            <?php echo htmlspecialchars($item['category']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo $item['thickness']; ?>" &times; <?php echo $item['width']; ?>" &times; <?php echo $item['length']; ?>'</strong>
                                    </td>
                                    <td><?php echo number_format($bdftPerPiece, 2); ?> BF</td>
                                    <td>
                                        <strong style="font-size: 1.05rem; color: #1e293b;"><?php echo number_format($stock); ?> pcs</strong>
                                    </td>
                                    <td><strong><?php echo number_format($itemTotalBdft, 2); ?> BF</strong></td>
                                    <td> <?php echo number_format($item['price_per_piece'] ?? 0, 2); ?></td>
                                    <td><strong> <?php echo number_format($itemTotalPrice, 2); ?></strong></td>
                                    <td>
                                        <?php if ($stock > 50): ?>
                                            <span class="badge badge-success">In Stock</span>
                                        <?php elseif ($stock > 0): ?>
                                            <span class="badge badge-warning">Low Stock</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Out of Stock</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Scale Origin Traceability Section -->
        <div class="content-card">
            <div class="card-header-bar">
                <h3>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    </svg>
                    Recently Scaled Wood Added to Inventory (Scaled Log)
                </h3>
                <a href="scale.php" class="btn btn-secondary btn-sm">Open Scaling Page &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>Owner (Supplier)</th>
                            <th>Wood Type</th>
                            <th>Dimension</th>
                            <th>Quantity</th>
                            <th>Total Bd. Ft.</th>
                            <th>Scaling Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($db['scales'])): ?>
                            <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 1rem;">No scale logs recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach (array_slice(array_reverse($db['scales']), 0, 4) as $s): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($s['owner_name']); ?></strong></td>
                                    <td><span class="badge badge-wood"><?php echo htmlspecialchars($s['category']); ?></span></td>
                                    <td><?php echo $s['thickness']; ?>" &times; <?php echo $s['width']; ?>" &times; <?php echo $s['length']; ?>'</td>
                                    <td><?php echo number_format($s['pieces']); ?> pcs</td>
                                    <td><?php echo number_format($s['board_feet'], 2); ?> BF</td>
                                    <td><?php echo htmlspecialchars($s['date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
