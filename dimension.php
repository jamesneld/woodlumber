<?php
require_once __DIR__ . '/includes/db.php';

$page_title = 'Fixed Wood Dimensions';
$active_page = 'dimension';

$message = '';
$db = get_db_data();

// Handle form submission to add fixed dimension
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_dimension') {
    $category = trim($_POST['category'] ?? '');
    $dimStr = trim($_POST['dimension_str'] ?? '');
    $parts = explode('x', strtolower($dimStr));
    $thickness = isset($parts[0]) ? floatval(trim($parts[0])) : 0;
    $width = isset($parts[1]) ? floatval(trim($parts[1])) : 0;
    $length = isset($parts[2]) ? floatval(trim($parts[2])) : 0;
    $price_bdft = floatval($_POST['price_per_bdft'] ?? 0);
    $stock_pcs = intval($_POST['stock_pcs'] ?? 0);

    if (!empty($category) && $thickness > 0 && $width > 0 && $length > 0) {
        $singleBdFt = calculate_board_feet($thickness, $width, $length, 1);
        $pricePiece = round($singleBdFt * $price_bdft, 2);

        $newDim = [
            'id' => 'dim_' . time() . '_' . rand(100, 999),
            'category' => $category,
            'thickness' => $thickness,
            'width' => $width,
            'length' => $length,
            'board_feet' => $singleBdFt,
            'price_per_bdft' => $price_bdft,
            'price_per_piece' => $pricePiece,
            'stock_pcs' => $stock_pcs,
            'date_added' => date('Y-m-d')
        ];

        $db['dimensions'][] = $newDim;
        save_db_data($db);
        $message = "Successfully saved fixed dimension {$thickness}\" x {$width}\" x {$length}' ($category)!";
    } else {
        $message = "Please ensure all dimensions (Thickness, Width, Length) and Category are valid.";
    }
}

// Handle delete fixed dimension
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    $db['dimensions'] = array_values(array_filter($db['dimensions'], function($d) use ($deleteId) {
        return $d['id'] !== $deleteId;
    }));
    save_db_data($db);
    header("Location: dimension.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="app-main">
    <header class="app-topbar">
        <div class="page-title-group">
            <h1>
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"></path>
                    <path d="m14.5 12.5 2-2"></path><path d="m11.5 9.5 2-2"></path><path d="m8.5 6.5 2-2"></path><path d="m17.5 15.5 2-2"></path>
                </svg>
                Fixed Lumber Dimensions
            </h1>
        </div>

        <div class="topbar-clock">
            <span class="pulse-dot"></span>
            <span class="live-date-display"><?php echo date('F d, Y'); ?></span>
            <span>&bull;</span>
            <span class="live-time-display"><?php echo date('h:i:s A'); ?></span>
        </div>
    </header>

    <main class="page-content">

        <?php if (!empty($message)): ?>
            <div class="alert alert-success" id="auto-dismiss-alert">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <?php echo $message; ?>
            </div>
            <script>
                setTimeout(() => {
                    const alertEl = document.getElementById('auto-dismiss-alert');
                    if (alertEl) {
                        alertEl.style.transition = 'opacity 0.5s ease';
                        alertEl.style.opacity = '0';
                        setTimeout(() => alertEl.remove(), 500);
                    }
                }, 4000);
            </script>
        <?php endif; ?>

        <!-- Input Form for Fixed Dimension -->
        <div class="content-card">
            <div class="card-header-bar">
                <h2>
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Input Fixed Wood Dimension
                </h2>
            </div>
            <div class="card-body">
                <form action="dimension.php" method="POST">
                    <input type="hidden" name="action" value="add_dimension">
                    <input type="hidden" id="dim_piece_price_input" name="piece_price" value="0">

                    <div class="form-grid">
                        <!-- Category / Wood Type -->
                        <div class="form-group col-span-2">
                            <label class="form-label" for="dim_category">Wood Category / Type</label>
                            <input type="text" id="dim_category" name="category" class="form-control" placeholder="e.g. Mahogany, Lauan, Falcata, Gmelina, Acacia" list="dl_woods" required>
                        </div>

                        <!-- Dimension -->
                        <div class="form-group col-span-2">
                            <label class="form-label" for="dim_string">Dimension (Thickness &times; Width &times; Length)</label>
                            <input type="text" id="dim_string" name="dimension_str" class="form-control" placeholder="e.g. 2x2x10" list="dl_dims" required>
                        </div>

                        <!-- Price per Bd. Ft. -->
                        <div class="form-group">
                            <label class="form-label" for="dim_price_bdft">Selling Price per Bd. Ft. ()</label>
                            <input type="number" step="0.5" id="dim_price_bdft" name="price_per_bdft" class="form-control" placeholder="e.g. 45.00" value="45.00" required>
                        </div>

                        <!-- Initial Stock Pcs -->
                        <div class="form-group col-span-2">
                            <label class="form-label" for="dim_stock_pcs">Fixed Stock Quantity (Pieces)</label>
                            <input type="number" min="0" id="dim_stock_pcs" name="stock_pcs" class="form-control" placeholder="Number of pieces in stock" value="50" required>
                        </div>
                    </div>



                    <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 1rem;">
                        <button type="reset" class="btn btn-secondary">Clear</button>
                        <button type="submit" class="btn btn-primary">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline></svg>
                            Save Fixed Dimension
                        </button>
                    </div>
                </form>
            </div>
        </div>


    </main>

    <datalist id="dl_dims">
        <option value="1x2x8"></option><option value="1x2x10"></option><option value="1x3x8"></option><option value="1x3x10"></option><option value="1x4x8"></option><option value="1x4x10"></option><option value="1x6x10"></option><option value="1x8x10"></option><option value="1x10x10"></option><option value="1x12x10"></option>
        <option value="2x2x8"></option><option value="2x2x10"></option><option value="2x2x12"></option><option value="2x3x8"></option><option value="2x3x10"></option><option value="2x3x12"></option><option value="2x4x8"></option><option value="2x4x10"></option><option value="2x4x12"></option><option value="2x6x10"></option><option value="2x6x12"></option><option value="2x8x10"></option><option value="2x8x12"></option><option value="2x10x10"></option><option value="2x12x10"></option>
        <option value="3x3x10"></option><option value="3x4x10"></option><option value="4x4x10"></option><option value="4x6x10"></option>
    </datalist>
    <datalist id="dl_woods">
        <option value="Mahogany"></option><option value="Lauan"></option><option value="Falcata"></option><option value="Gmelina"></option><option value="Acacia"></option><option value="Narra"></option><option value="Molave"></option><option value="Yakal"></option><option value="Other"></option>
    </datalist>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
