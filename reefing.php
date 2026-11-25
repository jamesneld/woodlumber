<?php
require_once __DIR__ . '/includes/db.php';

$page_title = 'Reefing & Dimension Calculator';
$active_page = 'reefing';

// Common standard lumber dimensions for reference table
$standardLumberRef = [
    ['size' => '2" x 2" x 8\'', 't' => 2, 'w' => 2, 'l' => 8, 'purpose' => 'Framing, purlins, studs'],
    ['size' => '2" x 2" x 10\'', 't' => 2, 'w' => 2, 'l' => 10, 'purpose' => 'Ceiling joists, rafters'],
    ['size' => '2" x 3" x 8\'', 't' => 2, 'w' => 3, 'l' => 8, 'purpose' => 'Framing, scaffolding, trusses'],
    ['size' => '2" x 3" x 10\'', 't' => 2, 'w' => 3, 'l' => 10, 'purpose' => 'Roof rafters, heavy trusses'],
    ['size' => '2" x 4" x 10\'', 't' => 2, 'w' => 4, 'l' => 10, 'purpose' => 'Girts, posts, structural framing'],
    ['size' => '2" x 4" x 12\'', 't' => 2, 'w' => 4, 'l' => 12, 'purpose' => 'Structural beams, load-bearing joists'],
    ['size' => '1" x 2" x 8\'', 't' => 1, 'w' => 2, 'l' => 8, 'purpose' => 'Furring, baseboard, trims, molding'],
    ['size' => '1" x 6" x 10\'', 't' => 1, 'w' => 6, 'l' => 10, 'purpose' => 'Fascia board, siding, furniture panels'],
    ['size' => '1" x 12" x 10\'', 't' => 1, 'w' => 12, 'l' => 10, 'purpose' => 'Stair treads, wide shelving, tabletops']
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="app-main">
    <header class="app-topbar">
        <div class="page-title-group">
            <h1>
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="2" width="16" height="20" rx="2"></rect>
                    <line x1="8" y1="6" x2="16" y2="6"></line>
                    <line x1="16" y1="14" x2="16" y2="18"></line>
                    <path d="M16 10h.01"></path><path d="M12 10h.01"></path><path d="M8 10h.01"></path>
                    <path d="M12 14h.01"></path><path d="M8 14h.01"></path>
                    <path d="M12 18h.01"></path><path d="M8 18h.01"></path>
                </svg>
                Reefing &amp; Dimension Calculator
            </h1>
            <p>Input lumber dimensions to accurately calculate Board Feet, Metric Volume, and Total Pricing</p>
        </div>

        <div class="topbar-clock">
            <span class="php-currency-badge"></span>
            <span class="pulse-dot"></span>
            <span class="live-date-display"><?php echo date('F d, Y'); ?></span>
            <span>&bull;</span>
            <span class="live-time-display"><?php echo date('h:i:s A'); ?></span>
        </div>
    </header>

    <main class="page-content">

        <!-- Calculator Card -->
        <div class="content-card">
            <div class="card-header-bar" style="background: linear-gradient(135deg, #fef3c7 0%, #ffffff 100%);">
                <h2>
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m10 15 5-3-5-3v6Z"></path>
                    </svg>
                    Accurate Board Foot Calculator (Reefing Tool)
                </h2>
                <span class="badge badge-wood" style="font-weight:700;">
                    Formula: (Thickness" &times; Width" &times; Length' &times; Pieces) &divide; 12
                </span>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <!-- Thickness -->
                    <div class="form-group">
                        <label class="form-label" for="reef_thickness">Thickness (Inches)</label>
                        <input type="number" step="0.25" id="reef_thickness" class="form-control" placeholder="e.g. 2" value="2">
                        <small style="color:#64748b;">Thickness in inches (T")</small>
                    </div>

                    <!-- Width -->
                    <div class="form-group">
                        <label class="form-label" for="reef_width">Width (Inches)</label>
                        <input type="number" step="0.25" id="reef_width" class="form-control" placeholder="e.g. 4" value="4">
                        <small style="color:#64748b;">Width in inches (W")</small>
                    </div>

                    <!-- Length -->
                    <div class="form-group">
                        <label class="form-label" for="reef_length">Length (Feet)</label>
                        <input type="number" step="0.5" id="reef_length" class="form-control" placeholder="e.g. 10" value="10">
                        <small style="color:#64748b;">Length in feet (L')</small>
                    </div>

                    <!-- Pieces -->
                    <div class="form-group">
                        <label class="form-label" for="reef_pieces">Quantity (Pieces)</label>
                        <input type="number" min="1" id="reef_pieces" class="form-control" placeholder="e.g. 50" value="50">
                        <small style="color:#64748b;">Number of lumber pieces</small>
                    </div>

                    <!-- Price per Board Foot -->
                    <div class="form-group col-span-2">
                        <label class="form-label" for="reef_price_per_bdft">Price per Board Foot ()</label>
                        <input type="number" step="0.5" id="reef_price_per_bdft" class="form-control" placeholder="e.g. 45.00" value="45.00">
                        <small style="color:#64748b;">Market selling rate per board foot</small>
                    </div>
                </div>

                <!-- Live Results Output Grid -->
                <div style="margin-top: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    
                    <!-- Single Piece Bd Ft -->
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem; text-align:center;">
                        <div style="font-size:0.8rem; color:#64748b; font-weight:700; text-transform:uppercase;">Per Piece</div>
                        <div id="reef_result_bdft" style="font-size:1.85rem; font-weight:800; color:#0284c7; margin:0.35rem 0;">6.67 BF</div>
                        <div style="font-size:0.78rem; color:#64748b;">Board feet per single piece</div>
                    </div>

                    <!-- Total Bd Ft -->
                    <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:1.25rem; text-align:center;">
                        <div style="font-size:0.8rem; color:#b45309; font-weight:700; text-transform:uppercase;">Total Board Feet</div>
                        <div id="reef_result_total_bdft" style="font-size:1.85rem; font-weight:800; color:#d97706; margin:0.35rem 0;">333.33 BF</div>
                        <div style="font-size:0.78rem; color:#b45309;">Total batch lumber volume</div>
                    </div>

                    <!-- Cubic Meters -->
                    <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:1.25rem; text-align:center;">
                        <div style="font-size:0.8rem; color:#15803d; font-weight:700; text-transform:uppercase;">Metric Volume (m³)</div>
                        <div id="reef_result_cbm" style="font-size:1.85rem; font-weight:800; color:#166534; margin:0.35rem 0;">0.7866 m³</div>
                        <div style="font-size:0.78rem; color:#15803d;">Cubic meters equivalent</div>
                    </div>

                    <!-- Total Estimated Cost -->
                    <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:12px; padding:1.25rem; text-align:center;">
                        <div style="font-size:0.8rem; color:#047857; font-weight:700; text-transform:uppercase;">Total Philippine Bill</div>
                        <div id="reef_result_cost" style="font-size:1.85rem; font-weight:800; color:#059669; margin:0.35rem 0;"> 15,000.00</div>
                        <div style="font-size:0.78rem; color:#047857;">Based on market rate per board foot</div>
                    </div>
                </div>

                <!-- Preview Banner -->
                <div style="margin-top: 1.5rem; padding: 1rem 1.25rem; background: #fafaf9; border-radius: 8px; border: 1px dashed #d6d3d1; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <strong style="color: #44403c;">Current Dimension:</strong>
                        <span id="reef_preview_dimension" style="font-weight:700; color:#854d0e; margin-left:0.5rem;">2" × 4" × 10' (50 pcs)</span>
                    </div>
                    <span style="font-size:0.82rem; color:#78716c;">Precise industrial sawmill calculation</span>
                </div>
            </div>
        </div>

        <!-- Common Dimension Reference Table -->
        <div class="content-card">
            <div class="card-header-bar">
                <h3>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                    Standard Lumber Dimension Quick Reference Table
                </h3>
                <span style="font-size:0.85rem; color:#64748b;">(Computed @ 45/BF)</span>
            </div>
            <div class="table-responsive">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>Standard Dimension</th>
                            <th>Thickness &times; Width &times; Length</th>
                            <th>Board Feet per Piece</th>
                            <th>Cubic Meter (m³)</th>
                            <th>Price per Piece ()</th>
                            <th>Common Purpose</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($standardLumberRef as $ref): ?>
                            <?php 
                                $bf = ($ref['t'] * $ref['w'] * $ref['l']) / 12;
                                $cbm = $bf * 0.00235974;
                                $price = $bf * 45;
                            ?>
                            <tr>
                                <td><strong><?php echo $ref['size']; ?></strong></td>
                                <td><?php echo $ref['t']; ?>" &times; <?php echo $ref['w']; ?>" &times; <?php echo $ref['l']; ?>'</td>
                                <td><span class="badge badge-wood"><?php echo number_format($bf, 2); ?> BF</span></td>
                                <td><small><?php echo number_format($cbm, 4); ?> m³</small></td>
                                <td><strong style="color:#15803d;"> <?php echo number_format($price, 2); ?></strong></td>
                                <td><span style="color:#64748b;"><?php echo $ref['purpose']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
