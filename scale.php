<?php
require_once __DIR__ . '/includes/db.php';

$page_title = 'Wood Scaling';
$active_page = 'scale';

$message = '';
$db = get_db_data();

$standard_dimensions = [
    '1x2x8'   => ['t'=>1,'w'=>2, 'l'=>8],
    '1x2x10'  => ['t'=>1,'w'=>2, 'l'=>10],
    '1x3x8'   => ['t'=>1,'w'=>3, 'l'=>8],
    '1x3x10'  => ['t'=>1,'w'=>3, 'l'=>10],
    '1x4x8'   => ['t'=>1,'w'=>4, 'l'=>8],
    '1x4x10'  => ['t'=>1,'w'=>4, 'l'=>10],
    '1x6x10'  => ['t'=>1,'w'=>6, 'l'=>10],
    '1x8x10'  => ['t'=>1,'w'=>8, 'l'=>10],
    '1x10x10' => ['t'=>1,'w'=>10,'l'=>10],
    '1x12x10' => ['t'=>1,'w'=>12,'l'=>10],
    '2x2x8'   => ['t'=>2,'w'=>2, 'l'=>8],
    '2x2x10'  => ['t'=>2,'w'=>2, 'l'=>10],
    '2x2x12'  => ['t'=>2,'w'=>2, 'l'=>12],
    '2x3x8'   => ['t'=>2,'w'=>3, 'l'=>8],
    '2x3x10'  => ['t'=>2,'w'=>3, 'l'=>10],
    '2x3x12'  => ['t'=>2,'w'=>3, 'l'=>12],
    '2x4x8'   => ['t'=>2,'w'=>4, 'l'=>8],
    '2x4x10'  => ['t'=>2,'w'=>4, 'l'=>10],
    '2x4x12'  => ['t'=>2,'w'=>4, 'l'=>12],
    '2x6x10'  => ['t'=>2,'w'=>6, 'l'=>10],
    '2x6x12'  => ['t'=>2,'w'=>6, 'l'=>12],
    '2x8x10'  => ['t'=>2,'w'=>8, 'l'=>10],
    '2x8x12'  => ['t'=>2,'w'=>8, 'l'=>12],
    '2x10x10' => ['t'=>2,'w'=>10,'l'=>10],
    '2x12x10' => ['t'=>2,'w'=>12,'l'=>10],
    '3x3x10'  => ['t'=>3,'w'=>3, 'l'=>10],
    '3x4x10'  => ['t'=>3,'w'=>4, 'l'=>10],
    '4x4x10'  => ['t'=>4,'w'=>4, 'l'=>10],
    '4x6x10'  => ['t'=>4,'w'=>6, 'l'=>10],
];

$pieces_options = [1,2,3,4,5,6,7,8,9,10,12,15,20,25,30,40,50,75,100,125,150,200];

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_scale') {

    $owner_names_raw   = $_POST['owner_name']       ?? [];
    $owner_cats_raw    = $_POST['owner_category']   ?? [];
    $owner_divs_raw    = $_POST['owner_divisor']    ?? [];
    $owner_rates_raw   = $_POST['owner_rate']       ?? [];
    $owner_dims_raw    = $_POST['owner_dimension']  ?? [];
    $owner_pieces_raw  = $_POST['owner_pieces']     ?? [];

    $owners_data = [];
    $all_parsed  = [];

    foreach ($owner_names_raw as $oi => $oname) {
        $oname    = trim($oname);
        if ($oname === '') continue;

        $category  = trim($owner_cats_raw[$oi]  ?? '');
        $divisor   = floatval($owner_divs_raw[$oi]  ?? 12);
        if ($divisor <= 0) $divisor = 12;
        $unit_cost = floatval($owner_rates_raw[$oi] ?? 0);

        $dims  = $owner_dims_raw[$oi]   ?? [];
        $pcses = $owner_pieces_raw[$oi] ?? [];

        $owner_items = [];
        foreach ($dims as $ri => $dimKey) {
            $dimKey = trim($dimKey);
            $d = null;
            if (isset($standard_dimensions[$dimKey])) {
                $d = $standard_dimensions[$dimKey];
            } else {
                $parts = explode('x', strtolower($dimKey));
                if (count($parts) === 3) {
                    $d = [
                        't' => floatval(trim($parts[0])),
                        'w' => floatval(trim($parts[1])),
                        'l' => floatval(trim($parts[2]))
                    ];
                }
            }
            if (!$d || $d['t'] <= 0 || $d['w'] <= 0 || $d['l'] <= 0) continue;
            
            $pcs = intval($pcses[$ri] ?? 1);
            if ($pcs <= 0) $pcs = 1;
            $gross  = $d['t'] * $d['w'] * $d['l'] * $pcs;
            $bf_row = round($gross / $divisor, 2);
            $row = [
                'dim_key'   => $dimKey,
                'thickness' => $d['t'],
                'width'     => $d['w'],
                'length'    => $d['l'],
                'pieces'    => $pcs,
                'gross'     => $gross,
                'bf'        => $bf_row,
            ];
            $owner_items[] = $row;
            $all_parsed[]  = $row;
        }

        if (!empty($owner_items) && $category !== '') {
            $owner_gross     = array_sum(array_column($owner_items, 'gross'));
            $owner_bf        = round($owner_gross / $divisor, 2);
            $owner_total     = round($owner_bf * $unit_cost, 2);
            $owner_pcs       = array_sum(array_column($owner_items, 'pieces'));
            $owners_data[] = [
                'name'      => $oname,
                'category'  => $category,
                'divisor'   => $divisor,
                'unit_cost' => $unit_cost,
                'items'     => $owner_items,
                'gross'     => $owner_gross,
                'bf'        => $owner_bf,
                'total'     => $owner_total,
                'pieces'    => $owner_pcs,
            ];

            // Sync each owner's items to inventory under their category
            foreach ($owner_items as $p) {
                $found = false;
                foreach ($db['dimensions'] as &$dim) {
                    if (strcasecmp($dim['category'], $category) === 0 &&
                        $dim['thickness'] == $p['thickness'] &&
                        $dim['width']     == $p['width'] &&
                        $dim['length']    == $p['length']) {
                        $dim['stock_pcs'] += $p['pieces'];
                        $found = true;
                        break;
                    }
                }
                unset($dim);
                if (!$found) {
                    $singleBdFt = calculate_board_feet($p['thickness'], $p['width'], $p['length'], 1);
                    $db['dimensions'][] = [
                        'id'              => 'dim_' . time() . '_' . rand(100,999),
                        'category'        => $category,
                        'thickness'       => $p['thickness'],
                        'width'           => $p['width'],
                        'length'          => $p['length'],
                        'board_feet'      => $singleBdFt,
                        'price_per_bdft'  => ($unit_cost > 0) ? round($unit_cost * 1.35, 2) : 45.00,
                        'price_per_piece' => round($singleBdFt * (($unit_cost > 0) ? ($unit_cost * 1.35) : 45.00), 2),
                        'stock_pcs'       => $p['pieces'],
                        'date_added'      => date('Y-m-d'),
                    ];
                }
            }
        }
    }

    if (!empty($owners_data)) {
        $grandBF    = round(array_sum(array_column($owners_data, 'bf')), 2);
        $grandTotal = round(array_sum(array_column($owners_data, 'total')), 2);
        $grandPcs   = array_sum(array_column($owners_data, 'pieces'));

        $ownerList = implode(', ', array_column($owners_data, 'name'));
        $dimSummaries = [];
        foreach ($owners_data as $od) {
            $parts = array_map(fn($i) => "{$i['dim_key']}({$i['pieces']}pcs)", $od['items']);
            $dimSummaries[] = "{$od['name']}: " . implode(', ', $parts);
        }

        $newScale = [
            'id'             => 'scl_' . time() . '_' . rand(100,999),
            'owner_name'     => $ownerList,
            'owner_names'    => array_column($owners_data, 'name'),
            'owners_data'    => $owners_data,
            'category'       => implode(', ', array_unique(array_column($owners_data, 'category'))),
            'dimension_text' => implode(' | ', $dimSummaries),
            'thickness'      => $all_parsed[0]['thickness'] ?? 0,
            'width'          => $all_parsed[0]['width']     ?? 0,
            'length'         => $all_parsed[0]['length']    ?? 0,
            'pieces'         => $grandPcs,
            'gross'          => array_sum(array_column($owners_data, 'gross')),
            'divisor'        => $owners_data[0]['divisor'],
            'board_feet'     => $grandBF,
            'unit_cost'      => $owners_data[0]['unit_cost'],
            'total_cost'     => $grandTotal,
            'date'           => date('Y-m-d'),
        ];

        $db['scales'][] = $newScale;
        save_db_data($db);

        $message = "Saved for <strong>{$ownerList}</strong> — Total: <strong>{$grandBF} BF</strong> = <strong>" . number_format($grandTotal, 2) . "</strong>";
    } else {
        $message = "error:Please fill in at least one Owner with Name, Category, and Dimension rows.";
    }
}

// ── Delete ────────────────────────────────────────────────────────────────────
if (isset($_GET['delete_id'])) {
    $del = $_GET['delete_id'];
    $db['scales'] = array_values(array_filter($db['scales'], fn($s) => $s['id'] !== $del));
    save_db_data($db);
    header("Location: scale.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$dims_json   = json_encode($standard_dimensions);
$pieces_json = json_encode($pieces_options);
?>

<div class="app-main">
<header class="app-topbar">
    <div class="page-title-group">
        <h1>
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path>
                <path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path>
                <path d="M7 21h10"></path><path d="M12 3v18"></path>
                <path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"></path>
            </svg>
            Wood Scaling (Scale)
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
    <?php $isError = str_starts_with($message, 'error:'); ?>
    <div class="alert <?php echo $isError ? 'alert-danger' : 'alert-success'; ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
            <?php if ($isError): ?>
                <circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>
            <?php else: ?>
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>
            <?php endif; ?>
        </svg>
        <?php echo $isError ? ltrim(substr($message, 6)) : $message; ?>
    </div>
<?php endif; ?>

<!-- ═══ FORM ═══ -->
<div class="content-card">
    <div class="card-header-bar">
        <h2>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            New Wood Scaling Entry
        </h2>
    </div>
    <div class="card-body">
        <form action="scale.php" method="POST" id="scaleForm">
            <input type="hidden" name="action" value="add_scale">

            <!-- OWNERS -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <span style="font-size:1rem; font-weight:700; color:#78350f;">🪵 Tree Owner(s)</span>
                <button type="button" class="btn btn-secondary btn-sm" onclick="addOwnerBlock()">
                    + Add Another Owner
                </button>
            </div>

            <div id="ownersContainer" style="display:flex; flex-direction:column; gap:1.25rem;">
                <!-- owner blocks injected by JS -->
            </div>

            <!-- GRAND TOTAL SUMMARY -->
            <div style="margin-top:1.75rem; background:linear-gradient(135deg,#fffbeb,#fef3c7); border:2px solid #f59e0b; border-radius:14px; padding:1.4rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px dashed #d97706; padding-bottom:0.75rem;">
                    <span style="font-weight:800; color:#78350f; text-transform:uppercase; font-size:0.85rem; letter-spacing:.05em;">
                        🧮 Grand Total (All Owners)
                    </span>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:1rem; text-align:center;">
                    <div style="background:#fff; padding:.85rem; border-radius:8px; border:1px solid #e2e8f0;">
                        <div style="font-size:.75rem; color:#64748b; font-weight:700; text-transform:uppercase;">Total Board Feet</div>
                        <div id="grand_bf" style="font-size:1.5rem; font-weight:800; color:#0284c7;">0.00 BF</div>
                    </div>
                    <div style="background:#fff; padding:.85rem; border-radius:8px; border:1px solid #bbf7d0;">
                        <div style="font-size:.75rem; color:#15803d; font-weight:700; text-transform:uppercase;">Grand Total Amount</div>
                        <div id="grand_total" style="font-size:1.5rem; font-weight:800; color:#15803d;"> 0.00</div>
                        <div style="font-size:.7rem; color:#15803d;">Sum of all owners</div>
                    </div>
                </div>
            </div>

            <div style="margin-top:1.5rem; display:flex; justify-content:flex-end; gap:1rem;">
                <button type="button" class="btn btn-secondary" onclick="resetScaleForm()">Clear Form</button>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Save Scale Entry
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ RECORDS ═══ -->
<div class="content-card">
    <div class="card-header-bar">
        <h3>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
            Wood Scaling Records
        </h3>
        <span class="badge badge-wood"><?php echo count($db['scales'] ?? []); ?> Records</span>
    </div>
    <div class="table-responsive">
        <table class="app-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Owner(s) / Category</th>
                    <th>Dimensions &amp; BF per Owner</th>
                    <th>Total BF</th>
                    <th>Total ()</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($db['scales'])): ?>
                    <tr><td colspan="7" style="text-align:center;padding:2rem;color:#94a3b8;">No scaling records yet.</td></tr>
                <?php else: ?>
                    <?php foreach (array_reverse($db['scales']) as $item): ?>
                    <tr>
                        <td><small style="color:#64748b;font-weight:700;"><?php echo htmlspecialchars($item['id']); ?></small></td>
                        <td>
                            <?php
                            if (!empty($item['owners_data']) && is_array($item['owners_data'])) {
                                foreach ($item['owners_data'] as $od) {
                                    echo '<div style="margin-bottom:3px;">'
                                       . '<span class="badge" style="background:#e0f2fe;color:#0369a1;">' . htmlspecialchars($od['name']) . '</span>'
                                       . ' <span class="badge badge-wood" style="font-size:0.72rem;">' . htmlspecialchars($od['category']) . '</span>'
                                       . '</div>';
                                }
                            } else {
                                echo '<span class="badge" style="background:#e0f2fe;color:#0369a1;">' . htmlspecialchars($item['owner_name'] ?? '—') . '</span>';
                            }
                            ?>
                        </td>
                        <td style="font-size:.83rem; max-width:280px;">
                            <?php
                            if (!empty($item['owners_data']) && is_array($item['owners_data'])) {
                                foreach ($item['owners_data'] as $od) {
                                    $parts = array_map(fn($i) => "{$i['dim_key']}({$i['pieces']}pcs)", $od['items']);
                                    echo '<div style="margin-bottom:2px;"><strong style="color:#78350f;">' . htmlspecialchars($od['name']) . ':</strong> '
                                       . htmlspecialchars(implode(', ', $parts))
                                       . ' <span style="color:#0284c7;font-weight:700;">= ' . number_format($od['bf'],2) . ' BF</span>'
                                       . ' <span style="color:#15803d;font-weight:600;">× ' . number_format($od['unit_cost'],2) . ' = ' . number_format($od['total'],2) . '</span>'
                                       . '</div>';
                                }
                            } else {
                                echo '<strong>' . htmlspecialchars($item['dimension_text'] ?? '—') . '</strong>';
                            }
                            ?>
                        </td>
                        <td><strong style="color:#0284c7;font-size:1.05rem;"><?php echo number_format($item['board_feet'],2); ?> BF</strong></td>
                        <td><strong style="color:#15803d;font-size:1.05rem;"> <?php echo number_format($item['total_cost']??0,2); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['date']); ?></td>
                        <td>
                            <a href="scale.php?delete_id=<?php echo urlencode($item['id']); ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete this record?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</main>

<style>
/* Owner Block */
.owner-block {
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.owner-block-header {
    display: flex;
    align-items: center;
    gap: .65rem;
    padding: .75rem 1.1rem;
    background: linear-gradient(90deg,#fef3c7,#fffbeb);
    border-bottom: 1px solid #fde68a;
}
.owner-block-header input.owner-name-input {
    flex: 1;
    padding: .5rem .75rem;
    border: 1.5px solid #d97706;
    border-radius: 7px;
    font-size: .98rem;
    font-weight: 700;
    background: #fff;
    color: #1e293b;
}
.owner-block-header input.owner-name-input:focus {
    outline: none;
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245,158,11,.15);
}
.owner-block-body {
    padding: 1rem 1.1rem;
    background: #fdfdfd;
}
/* Fields grid inside owner */
.owner-fields-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: .85rem;
    margin-bottom: 1rem;
    padding-bottom: .85rem;
    border-bottom: 1px dashed #e2e8f0;
}
@media(max-width:640px){ .owner-fields-grid{ grid-template-columns:1fr; } }

/* Dim row */
.dim-row {
    display: grid;
    grid-template-columns: minmax(120px,2.2fr) minmax(100px,1.3fr) minmax(100px,1fr) 32px;
    gap: .5rem;
    align-items: center;
    margin-bottom: .4rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 7px;
    padding: .42rem .65rem;
}
.dim-row select {
    width: 100%;
    padding: .4rem .5rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: .875rem;
    background: #fff;
    color: #1e293b;
    appearance: auto;
}
.dim-row .row-bf {
    font-weight: 700;
    color: #0284c7;
    font-size: .85rem;
    background: #e0f2fe;
    border: 1px solid #bae6fd;
    border-radius: 6px;
    padding: .32rem .5rem;
    text-align: center;
    white-space: nowrap;
}
.btn-remove-row {
    width: 28px; height: 28px;
    background: #fee2e2; color: #dc2626;
    border: 1px solid #fca5a5;
    border-radius: 6px;
    cursor: pointer;
    font-size: .95rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: background .15s;
}
.btn-remove-row:hover { background: #fca5a5; }
.dim-header-labels {
    display: grid;
    grid-template-columns: minmax(120px,2.2fr) minmax(100px,1.3fr) minmax(100px,1fr) 32px;
    gap: .5rem;
    margin-bottom: .3rem;
    padding: 0 .2rem;
}
.dim-header-labels span {
    font-size: .7rem;
    color: #64748b;
    font-weight: 700;
    text-transform: uppercase;
}
.owner-subtotal-bar {
    margin-top: .65rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(90deg,#f0f9ff,#e0f2fe);
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: .5rem .85rem;
    font-size: .85rem;
    font-weight: 700;
}
</style>

<script>
const DIMS   = <?php echo $dims_json; ?>;
const PIECES = <?php echo $pieces_json; ?>;

function buildDimOpts(sel){
    return Object.keys(DIMS).map(k=>
        `<option value="${k}"${k===sel?' selected':''}>${k}</option>`
    ).join('');
}
function buildPcsOpts(sel){
    return PIECES.map(p=>
        `<option value="${p}"${p===sel?' selected':''}>${p} pcs</option>`
    ).join('');
}

let ownerCnt=0, dimCnt=0;

function addOwnerBlock(){
    ownerCnt++;
    const oi=ownerCnt;
    const div=document.createElement('div');
    div.className='owner-block';
    div.id='ownerBlock_'+oi;
    div.innerHTML=`
        <div class="owner-block-header">
            <input class="owner-name-input" type="text" name="owner_name[]"
                   placeholder="Tree owner name"
                   oninput="updateGrandTotal()" required>
            <button type="button" class="btn btn-danger btn-sm"
                    style="flex-shrink:0;padding:.3rem .7rem;"
                    onclick="removeOwnerBlock(${oi})">✕ Remove</button>
        </div>
        <div class="owner-block-body">
            <!-- Per-owner fields -->
            <div class="owner-fields-grid">
                <div class="form-group">
                    <label class="form-label">Category (Type of Wood)</label>
                    <input type="text" name="owner_category[]" class="form-control" 
                           list="dl_woods" placeholder="Select or type wood type"
                           oninput="updateGrandTotal()" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Divisor ÷</label>
                    <input type="number" name="owner_divisor[]" class="form-control"
                           value="12" min="1" step="1"
                           id="div_${oi}" oninput="recalcOwner(${oi})">
                </div>
                <div class="form-group">
                    <label class="form-label">Rate ( / BF)</label>
                    <input type="number" name="owner_rate[]" class="form-control"
                           value="30.00" step="0.5"
                           id="rate_${oi}" oninput="recalcOwner(${oi})">
                </div>
            </div>

            <!-- Dim rows -->
            <div class="dim-header-labels">
                <span>Dimension</span>
                <span>Pieces</span>
                <span>BF</span>
                <span></span>
            </div>
            <div id="dimRows_${oi}"></div>

            <!-- Subtotal -->
            <div class="owner-subtotal-bar" id="subtotal_${oi}">
                <span style="color:#0369a1;">Owner BF: <span id="oBF_${oi}">0.00</span> BF</span>
                <span style="color:#15803d;">Owner Total:  <span id="oTotal_${oi}">0.00</span></span>
            </div>

            <div style="margin-top:.65rem;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="addDimRow(${oi})">
                    + Add Dimension Row
                </button>
            </div>
        </div>
    `;
    document.getElementById('ownersContainer').appendChild(div);
    addDimRow(oi);
}

function removeOwnerBlock(oi){
    const c=document.getElementById('ownersContainer');
    if(c.children.length<=1){ alert('At least one owner is required.'); return; }
    const el=document.getElementById('ownerBlock_'+oi);
    if(el) el.remove();
    updateGrandTotal();
}

function addDimRow(oi, defDim, defPcs){
    dimCnt++;
    const rid=dimCnt;
    const c=document.getElementById('dimRows_'+oi);
    const row=document.createElement('div');
    row.className='dim-row';
    row.id='dimRow_'+oi+'_'+rid;
    row.innerHTML=`
        <input type="text" name="owner_dimension[${oi}][]" id="dSel_${oi}_${rid}"
               value="${defDim||'2x2x10'}" placeholder="e.g. 2x2x10" list="dl_dims"
               oninput="recalcOwner(${oi})" autocomplete="off" style="width:100%; padding:.4rem .5rem; border:1px solid #cbd5e1; border-radius:6px; font-size:.875rem;">
        <input type="number" name="owner_pieces[${oi}][]" id="pSel_${oi}_${rid}"
               value="${defPcs||1}" min="1"
               oninput="recalcOwner(${oi})" style="width:100%; padding:.4rem .5rem; border:1px solid #cbd5e1; border-radius:6px; font-size:.875rem;">
        <div class="row-bf" id="rowBf_${oi}_${rid}">— BF</div>
        <button type="button" class="btn-remove-row"
                onclick="removeDimRow(${oi},${rid})" title="Remove">✕</button>
    `;
    c.appendChild(row);
    recalcOwner(oi);
}

function removeDimRow(oi,rid){
    const c=document.getElementById('dimRows_'+oi);
    if(c.children.length<=1){ alert('Each owner needs at least one dimension row.'); return; }
    const el=document.getElementById('dimRow_'+oi+'_'+rid);
    if(el) el.remove();
    recalcOwner(oi);
}

function recalcOwner(oi){
    const divEl  = document.getElementById('div_'+oi);
    const rateEl = document.getElementById('rate_'+oi);
    const divisor= divEl  ? (parseFloat(divEl.value)||12)  : 12;
    const rate   = rateEl ? (parseFloat(rateEl.value)||0)  : 0;

    const dimContainer = document.getElementById('dimRows_'+oi);
    if(!dimContainer) return;
    let ownerBF=0;

    [...dimContainer.children].forEach(row=>{
        const parts=row.id.split('_');
        const rid=parts[parts.length-1];
        const dSel=document.getElementById('dSel_'+oi+'_'+rid);
        const pSel=document.getElementById('pSel_'+oi+'_'+rid);
        const bfEl=document.getElementById('rowBf_'+oi+'_'+rid);
        if(!dSel||!pSel||!bfEl) return;
        let d=DIMS[dSel.value];
        if(!d) {
            const parts = dSel.value.toLowerCase().split('x');
            if(parts.length === 3) {
                d = { t: parseFloat(parts[0]), w: parseFloat(parts[1]), l: parseFloat(parts[2]) };
            }
        }
        if(!d || isNaN(d.t) || isNaN(d.w) || isNaN(d.l)) {
            bfEl.textContent='— BF';
            return;
        }
        const pcs=parseInt(pSel.value)||1;
        const gross=d.t*d.w*d.l*pcs;
        const bf=gross/divisor;
        ownerBF+=bf;
        bfEl.textContent=bf.toFixed(2)+' BF';
    });

    const ownerTotal=ownerBF*rate;
    const bfSpan   =document.getElementById('oBF_'+oi);
    const totSpan  =document.getElementById('oTotal_'+oi);
    if(bfSpan)  bfSpan.textContent  = ownerBF.toFixed(2);
    if(totSpan) totSpan.textContent = ownerTotal.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});

    updateGrandTotal();
}

function updateGrandTotal(){
    const ownerContainer=document.getElementById('ownersContainer');
    let grandBF=0, grandTotal=0, rowCount=0, pcsCount=0;

    [...ownerContainer.children].forEach(block=>{
        const oi=block.id.replace('ownerBlock_','');
        const divEl =document.getElementById('div_'+oi);
        const rateEl=document.getElementById('rate_'+oi);
        const divisor=divEl  ? (parseFloat(divEl.value)||12) : 12;
        const rate   =rateEl ? (parseFloat(rateEl.value)||0) : 0;
        const dc=document.getElementById('dimRows_'+oi);
        if(!dc) return;
        let oBF=0;
        [...dc.children].forEach(row=>{
            const parts=row.id.split('_');
            const rid=parts[parts.length-1];
            const dSel=document.getElementById('dSel_'+oi+'_'+rid);
            const pSel=document.getElementById('pSel_'+oi+'_'+rid);
            if(!dSel||!pSel) return;
            let d=DIMS[dSel.value];
            if(!d) {
                const parts = dSel.value.toLowerCase().split('x');
                if(parts.length === 3) {
                    d = { t: parseFloat(parts[0]), w: parseFloat(parts[1]), l: parseFloat(parts[2]) };
                }
            }
            if(!d || isNaN(d.t) || isNaN(d.w) || isNaN(d.l)) return;
            const pcs=parseInt(pSel.value)||1;
            const gross=d.t*d.w*d.l*pcs;
            oBF+=gross/divisor;
            pcsCount+=pcs;
            rowCount++;
        });
        grandBF+=oBF;
        grandTotal+=oBF*rate;
    });

    document.getElementById('grand_bf').textContent    = grandBF.toFixed(2)+' BF';
    document.getElementById('grand_total').textContent = ' '+grandTotal.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
}

function resetScaleForm(){
    document.getElementById('ownersContainer').innerHTML='';
    ownerCnt=0; dimCnt=0;
    addOwnerBlock();
    updateGrandTotal();
}

document.addEventListener('DOMContentLoaded',()=>{
    addOwnerBlock();
    const dl = document.createElement('datalist');
    dl.id = 'dl_dims';
    dl.innerHTML = Object.keys(DIMS).map(k=>`<option value="${k}">`).join('');
    document.body.appendChild(dl);

    const dlWoods = document.createElement('datalist');
    dlWoods.id = 'dl_woods';
    const woods = ['Mahogany', 'Lauan', 'Falcata', 'Gmelina', 'Acacia', 'Narra', 'Molave', 'Yakal', 'Other'];
    dlWoods.innerHTML = woods.map(w=>`<option value="${w}">`).join('');
    document.body.appendChild(dlWoods);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
