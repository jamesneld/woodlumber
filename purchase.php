<?php
require_once __DIR__ . '/includes/db.php';

$page_title = 'Customer Purchase & Philippine Bill';
$active_page = 'purchase';

$message = '';
$error = '';
$new_bill_id = null;
$db = get_db_data();

// Handle Customer Purchase Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'make_purchase') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $dimension_id = trim($_POST['dimension_id'] ?? '');
    $pieces = intval($_POST['pieces'] ?? 0);
    $payment_type = trim($_POST['payment_type'] ?? 'Cash');

    if (empty($customer_name) || empty($dimension_id) || $pieces <= 0) {
        $error = "Please provide customer name, select lumber dimension, and enter the quantity to purchase.";
    } else {
        // Find dimension in DB
        $dimIndex = -1;
        foreach ($db['dimensions'] as $idx => $d) {
            if ($d['id'] === $dimension_id) {
                $dimIndex = $idx;
                break;
            }
        }

        if ($dimIndex === -1) {
            $error = "Selected lumber dimension was not found in database.";
        } else {
            $selectedDim = &$db['dimensions'][$dimIndex];
            $currentStock = intval($selectedDim['stock_pcs'] ?? 0);

            if ($pieces > $currentStock) {
                $error = "Insufficient stock! Available stock: {$currentStock} pcs, but requested quantity is {$pieces} pcs.";
            } else {
                $unitPrice = floatval($selectedDim['price_per_piece'] ?? 0);
                $totalAmount = $unitPrice * $pieces;
                $dimDescription = "{$selectedDim['thickness']}\" x {$selectedDim['width']}\" x {$selectedDim['length']}'";
                $singleBdFt = floatval($selectedDim['board_feet'] ?? 0);
                $totalBdFt = $singleBdFt * $pieces;

                // Deduct stock
                $selectedDim['stock_pcs'] -= $pieces;

                // Unique Philippine Bill ID
                $billId = 'BILL-PH-' . date('Y') . '-' . str_pad(count($db['purchases']) + 1, 4, '0', STR_PAD_LEFT);

                // Add to purchases
                $newPurchase = [
                    'id' => $billId,
                    'customer_name' => $customer_name,
                    'wood_type' => $selectedDim['category'],
                    'dimension' => $dimDescription,
                    'board_feet' => $singleBdFt,
                    'total_board_feet' => $totalBdFt,
                    'pieces' => $pieces,
                    'unit_price' => $unitPrice,
                    'total_amount' => $totalAmount,
                    'payment_type' => $payment_type,
                    'date' => date('Y-m-d')
                ];
                $db['purchases'][] = $newPurchase;

                // If Credit, automatically add to credits list (Customer Credit)
                if ($payment_type === 'Credit') {
                    $initialPaid = floatval($_POST['initial_paid'] ?? 0);
                    $balance = $totalAmount - $initialPaid;
                    $status = ($balance <= 0) ? 'Paid' : (($initialPaid > 0) ? 'Partial' : 'Unpaid');

                    $db['credits'][] = [
                        'id' => 'crd_' . time() . '_' . rand(100, 999),
                        'type' => 'customer',
                        'name' => $customer_name,
                        'description' => "Philippine Lumber Bill #{$billId} ({$pieces} pcs {$selectedDim['category']} {$dimDescription})",
                        'amount' => $totalAmount,
                        'paid' => $initialPaid,
                        'balance' => $balance,
                        'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d', strtotime('+15 days')),
                        'status' => $status,
                        'date' => date('Y-m-d')
                    ];
                }

                save_db_data($db);
                $new_bill_id = $billId;
                $message = "Successfully created Philippine Lumber Bill #{$billId} for " . htmlspecialchars($customer_name) . " (" . number_format($pieces) . " pcs {$selectedDim['category']})!";
            }
        }
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
                    <circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle>
                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                </svg>
                Customer Purchase &amp; Philippine Bill
            </h1>
            <p>Process lumber sales and issue official Philippine lumber sales bills and invoices</p>
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

        <?php if (!empty($message)): ?>
            <div class="alert alert-success" style="display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span><?php echo $message; ?></span>
                </div>
                <?php if ($new_bill_id): ?>
                    <button class="btn btn-sm" style="background:#15803d; color:#fff;" onclick="openBillModal('<?php echo $new_bill_id; ?>')">
                        📄 View Philippine Bill
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert" style="background:#fee2e2; color:#991b1b; border:1px solid #fecaca;">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Purchase Input Form -->
        <div class="content-card">
            <div class="card-header-bar">
                <h2>
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                    </svg>
                    Issue Customer Purchase &amp; Philippine Bill
                </h2>
                <span style="font-size:0.85rem; color:#64748b;">Automatically calculates bill and updates inventory</span>
            </div>
            <div class="card-body">
                <form id="purchaseForm" action="purchase.php" method="POST">
                    <input type="hidden" name="action" value="make_purchase">
                    <input type="hidden" id="purchase_total_input" name="total_amount" value="0">

                    <div class="form-grid">
                        <!-- Customer Name -->
                        <div class="form-group col-span-2">
                            <label class="form-label" for="purchase_customer_name">Customer Name</label>
                            <input type="text" id="purchase_customer_name" name="customer_name" class="form-control" placeholder="Enter customer / company name (e.g. Rolando Construction)" required>
                        </div>

                        <!-- Select Wood Dimension -->
                        <div class="form-group col-span-2">
                            <label class="form-label" for="purchase_dimension_select">Select Available Wood &amp; Dimension</label>
                            <select id="purchase_dimension_select" name="dimension_id" class="form-select" required>
                                <option value="">-- Choose available lumber stock --</option>
                                <?php foreach ($db['dimensions'] as $dim): ?>
                                    <option value="<?php echo htmlspecialchars($dim['id']); ?>" 
                                            data-price="<?php echo $dim['price_per_piece']; ?>"
                                            data-stock="<?php echo $dim['stock_pcs']; ?>"
                                            data-wood="<?php echo htmlspecialchars($dim['category']); ?>"
                                            data-dimension="<?php echo $dim['thickness']; ?>&quot; x <?php echo $dim['width']; ?>&quot; x <?php echo $dim['length']; ?>'"
                                            <?php echo ($dim['stock_pcs'] <= 0) ? 'disabled' : ''; ?>>
                                        <?php echo htmlspecialchars($dim['category']); ?> &mdash; 
                                        <?php echo $dim['thickness']; ?>" &times; <?php echo $dim['width']; ?>" &times; <?php echo $dim['length']; ?>' 
                                        (<?php echo number_format($dim['board_feet'], 2); ?> BF) &mdash; 
                                        <?php echo number_format($dim['price_per_piece'], 2); ?> / pc 
                                        [Stock: <?php echo $dim['stock_pcs']; ?> pcs]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Input: Pila kabuok ang ma purchase sa customer -->
                        <div class="form-group">
                            <label class="form-label" for="purchase_pieces">Purchase Quantity (Pieces)</label>
                            <input type="number" min="1" id="purchase_pieces" name="pieces" class="form-control" placeholder="Number of pieces" value="1" required>
                            <small id="purchase_available_stock" style="font-weight:600; color:#15803d; margin-top:0.25rem;">0 pcs available</small>
                        </div>

                        <!-- Payment Option -->
                        <div class="form-group">
                            <label class="form-label" for="purchase_payment_type">Payment Method</label>
                            <select id="purchase_payment_type" name="payment_type" class="form-select">
                                <option value="Cash">Cash (Immediate Settlement)</option>
                                <option value="GCash">GCash (Mobile Wallet)</option>
                                <option value="Credit">Credit</option>
                            </select>
                        </div>

                        <!-- Optional Initial Downpayment if Credit -->
                        <div class="form-group" id="downpayment_container" style="display:none; flex-direction:column; gap:1rem;">
                            <div>
                                <label class="form-label" for="purchase_initial_paid">Initial Downpayment (Optional)</label>
                                <input type="number" step="0.5" id="purchase_initial_paid" name="initial_paid" class="form-control" placeholder="0.00">
                            </div>
                            <div>
                                <label class="form-label" for="purchase_due_date">Due Date</label>
                                <input type="date" id="purchase_due_date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Computed Price Preview -->
                    <div class="calc-display">
                        <div class="calc-display-item">
                            <div class="calc-label">Unit Price (Philippine Bill)</div>
                            <div class="calc-num" id="purchase_price_per_piece" style="color: #64748b;">0.00</div>
                        </div>
                        <div style="width: 1px; height: 40px; background: #fcd34d;"></div>
                        <div class="calc-display-item">
                            <div class="calc-label">Total Philippine Bill Amount</div>
                            <div class="calc-num" id="purchase_total_amount" style="color: #15803d;">0.00</div>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 1rem;">
                        <button type="reset" class="btn btn-secondary">Clear</button>
                        <button type="button" class="btn btn-primary" onclick="previewBillModal()">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 13l4 4L19 7"></path></svg>
                            Preview Philippine Bill
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Available Stock Quick Reference Cards -->
        <div class="content-card">
            <div class="card-header-bar" style="cursor:pointer;" onclick="toggleSection('stock_section')">
                <h3>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                    </svg>
                    Available Lumber by Dimension
                </h3>

            </div>
            <div class="table-responsive" id="stock_section" style="display:none;">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>Wood Type</th>
                            <th>Dimension (T" &times; W" &times; L')</th>
                            <th>Board Feet</th>
                            <th>Available Stock (Pcs)</th>
                            <th>Price Per Piece</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($db['dimensions'])): ?>
                            <tr><td colspan="6" style="text-align:center; padding:1.5rem; color:#94a3b8;">No lumber available. Add stock in <a href="dimension.php">Dimension</a> or <a href="scale.php">Scale</a>.</td></tr>
                        <?php else: ?>
                            <?php foreach ($db['dimensions'] as $d): ?>
                                <tr>
                                    <td><span class="badge badge-wood"><?php echo htmlspecialchars($d['category']); ?></span></td>
                                    <td><strong><?php echo $d['thickness']; ?>" &times; <?php echo $d['width']; ?>" &times; <?php echo $d['length']; ?>'</strong></td>
                                    <td><?php echo number_format($d['board_feet'], 2); ?> BF</td>
                                    <td>
                                        <strong style="font-size:1.05rem; <?php echo ($d['stock_pcs'] <= 0) ? 'color:#dc2626;' : 'color:#15803d;'; ?>">
                                            <?php echo number_format($d['stock_pcs']); ?> pcs
                                        </strong>
                                    </td>
                                    <td><strong><?php echo number_format($d['price_per_piece'] ?? 0, 2); ?></strong></td>
                                    <td>
                                        <?php if ($d['stock_pcs'] > 0): ?>
                                            <span class="badge badge-success">Available</span>
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

        <!-- Recent Purchases History Table with Philippine Bill View Button -->
        <div class="content-card">
            <div class="card-header-bar" style="cursor:pointer;" onclick="toggleSection('bills_section')">
                <h3>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    </svg>
                    Philippine Lumber Bills &amp; Purchases
                </h3>
                <span class="badge badge-wood"><?php echo count($db['purchases'] ?? []); ?> Invoices</span>
            </div>
            <div class="table-responsive" id="bills_section" style="display:none;">
                <?php
                    $customers = [];
                    foreach ($db['purchases'] as $pur) {
                        $c = trim($pur['customer_name']);
                        if (!isset($customers[$c])) {
                            $customers[$c] = [
                                'name' => $c,
                                'purchases' => [],
                                'total_spent' => 0,
                                'last_date' => $pur['date']
                            ];
                        }
                        $customers[$c]['purchases'][] = $pur;
                        $customers[$c]['total_spent'] += floatval($pur['total_amount']);
                        if ($pur['date'] > $customers[$c]['last_date']) {
                            $customers[$c]['last_date'] = $pur['date'];
                        }
                    }
                    // Sort customers by last purchase date descending
                    uasort($customers, function($a, $b) {
                        return strtotime($b['last_date']) - strtotime($a['last_date']);
                    });
                ?>
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Total Invoices</th>
                            <th>Total Spent (PHP)</th>
                            <th>Last Purchase Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:2rem; color:#94a3b8;">No customer purchase records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($customers as $cName => $cData): ?>
                                <?php $safeId = md5($cName); ?>
                                <tr class="customer-row" style="cursor: pointer;" onclick="toggleCustomerPurchases('<?php echo $safeId; ?>')">
                                    <td><strong><a href="javascript:void(0)" style="color:#0f172a; text-decoration:none;">📁 <?php echo htmlspecialchars($cName); ?></a></strong></td>
                                    <td><span class="badge badge-wood"><?php echo count($cData['purchases']); ?> Invoices</span></td>
                                    <td><strong style="color:#15803d; font-size:1.05rem;"> <?php echo number_format($cData['total_spent'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($cData['last_date']); ?></td>
                                </tr>
                                <tr id="purchases_<?php echo $safeId; ?>" style="display: none;">
                                    <td colspan="4" style="padding: 0; background-color: #f8fafc; border-bottom: 2px solid #cbd5e1;">
                                        <table class="app-table" style="margin: 0; border: none; box-shadow: none; background: transparent;">
                                            <thead style="background-color: #e2e8f0;">
                                                <tr>
                                                    <th>Bill # / Invoice</th>
                                                    <th>Wood / Dimension</th>
                                                    <th>Quantity (Pcs)</th>
                                                    <th>Total Bill (PHP)</th>
                                                    <th>Payment</th>
                                                    <th>Date</th>
                                                    <th>Philippine Bill</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_reverse($cData['purchases']) as $pur): ?>
                                                <tr>
                                                    <td><strong style="color:#854d0e;"><?php echo htmlspecialchars($pur['id']); ?></strong></td>
                                                    <td>
                                                        <span class="badge badge-wood"><?php echo htmlspecialchars($pur['wood_type']); ?></span>
                                                        <small><?php echo htmlspecialchars($pur['dimension']); ?></small>
                                                    </td>
                                                    <td><span class="badge badge-info"><?php echo number_format($pur['pieces']); ?> pcs</span></td>
                                                    <td><strong style="color:#15803d;"> <?php echo number_format($pur['total_amount'], 2); ?></strong></td>
                                                    <td>
                                                        <?php if ($pur['payment_type'] === 'Cash'): ?>
                                                            <span class="badge badge-success">Cash</span>
                                                        <?php elseif ($pur['payment_type'] === 'GCash'): ?>
                                                            <span class="badge badge-info" style="background-color: #3b82f6; color: white;">GCash</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-warning">Credit</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($pur['date']); ?></td>
                                                    <td>
                                                        <button class="btn btn-secondary btn-sm" onclick="openBillModal('<?php echo htmlspecialchars($pur['id']); ?>')">
                                                            📄 View Bill
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

<!-- =========================================================
     PHILIPPINE BILL / INVOICE MODAL (PRINTABLE)
     ========================================================= -->
<div class="bill-modal-overlay" id="billModal">
    <div class="philippine-bill-card">
        <!-- Bill Header -->
        <div class="bill-header">
            <h2>WOOD LUMBER SYSTEM &amp; SAWMILL</h2>
            <p>Authorized Timber, Lumber &amp; Wood Processing Plant</p>
            <p>Republic of the Philippines &bull; Non-VAT Reg. TIN: 482-194-820-000</p>
            <div class="bill-title-tag">OFFICIAL PHILIPPINE LUMBER SALES BILL</div>
        </div>

        <!-- Bill Details Grid -->
        <div class="bill-info-grid">
            <div class="bill-info-box">
                <div><strong>Bill / Invoice No:</strong> <span id="modalBillNo">-</span></div>
                <div><strong>Date Issued:</strong> <span id="modalBillDate">-</span></div>
                <div><strong>Terms:</strong> <span id="modalBillTerms">-</span></div>
            </div>
            <div class="bill-info-box">
                <div><strong>Customer Name:</strong> <span id="modalBillCustomer">-</span></div>
                <div><strong>Currency:</strong> </div>
                <div><strong>Status:</strong> <span id="modalBillStatus" style="color:#15803d; font-weight:700;">CONFIRMED</span></div>
            </div>
        </div>

        <!-- Itemized Table -->
        <table class="bill-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th>Dimension</th>
                    <th>Qty (Pcs)</th>
                    <th>Unit Price (PHP)</th>
                    <th>Total ()</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td id="modalBillWood">-</td>
                    <td id="modalBillDimension">-</td>
                    <td id="modalBillPieces" style="font-weight:700;">-</td>
                    <td id="modalBillUnitPrice">-</td>
                    <td id="modalBillTotal" style="font-weight:700; color:#15803d;">-</td>
                </tr>
            </tbody>
        </table>

        <!-- Totals & Philippine Denomination Note -->
        <div class="bill-totals">
            <table class="bill-totals-table">
                <tr>
                    <td>Subtotal:</td>
                    <td style="text-align:right;" id="modalBillSubtotal"> 0.00</td>
                </tr>
                <tr>
                    <td>Value Added Tax (VAT 0%):</td>
                    <td style="text-align:right;"> 0.00</td>
                </tr>
                <tr class="bill-grand-total">
                    <td>TOTAL AMOUNT DUE:</td>
                    <td style="text-align:right;" id="modalBillGrandTotal"> 0.00</td>
                </tr>
            </table>
        </div>

        <!-- Signatures & Acknowledgment -->
        <div class="bill-signatures">
            <div>
                <div class="sig-line">Prepared &amp; Released By<br><small>Cashier / Sawmill Dispatcher</small></div>
            </div>
            <div>
                <div class="sig-line">Received in Good Order &amp; Condition<br><small>Customer / Authorized Representative</small></div>
            </div>
        </div>

        <!-- Actions -->
        <div class="bill-modal-actions">
            <button class="btn btn-secondary" onclick="closeBillModal()">Close</button>
            <button class="btn btn-success" id="btnBuyNow" style="background-color: #15803d; color: white; border: none;" onclick="document.getElementById('purchaseForm').submit();">
                🛒 Buy Now
            </button>
            <button class="btn btn-primary" id="btnPrintBill" onclick="window.print()">
                🖨️ Print Philippine Bill
            </button>
        </div>
    </div>
</div>

<script>
// JSON representation of purchases for quick client-side bill rendering
const purchasesData = <?php echo json_encode($db['purchases'] ?? []); ?>;

function openBillModal(billId) {
    const bill = purchasesData.find(p => p.id === billId);
    if (!bill) return;

    document.getElementById('modalBillNo').textContent = bill.id;
    document.getElementById('modalBillDate').textContent = bill.date;
    document.getElementById('modalBillTerms').textContent = bill.payment_type;
    document.getElementById('modalBillCustomer').textContent = bill.customer_name;
    document.getElementById('modalBillWood').textContent = bill.wood_type + ' (Lumber)';
    document.getElementById('modalBillDimension').textContent = bill.dimension;
    document.getElementById('modalBillPieces').textContent = Number(bill.pieces).toLocaleString() + ' pcs';
    document.getElementById('modalBillUnitPrice').textContent = ' ' + Number(bill.unit_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    const formattedTotal = ' ' + Number(bill.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('modalBillTotal').textContent = formattedTotal;
    document.getElementById('modalBillSubtotal').textContent = formattedTotal;
    document.getElementById('modalBillGrandTotal').textContent = formattedTotal;

    document.getElementById('btnBuyNow').style.display = 'none'; // Hide Buy Now for already purchased items
    document.getElementById('btnPrintBill').style.display = 'inline-block';
    const modal = document.getElementById('billModal');
    modal.classList.add('active');
}

function previewBillModal() {
    const customer = document.getElementById('purchase_customer_name').value;
    const dimSelect = document.getElementById('purchase_dimension_select');
    const pieces = document.getElementById('purchase_pieces').value;
    const payment = document.getElementById('purchase_payment_type').value;

    if (!customer || !dimSelect.value || pieces <= 0) {
        alert("Please fill in all required fields.");
        return;
    }

    const selectedOption = dimSelect.options[dimSelect.selectedIndex];
    const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
    const total = price * pieces;
    
    const woodType = selectedOption.getAttribute('data-wood') || '';
    const dimension = selectedOption.getAttribute('data-dimension') || '';

    document.getElementById('modalBillNo').textContent = "PREVIEW";
    document.getElementById('modalBillDate').textContent = new Date().toISOString().split('T')[0];
    document.getElementById('modalBillTerms').textContent = payment;
    document.getElementById('modalBillCustomer').textContent = customer;
    document.getElementById('modalBillWood').textContent = woodType + ' (Lumber)';
    document.getElementById('modalBillDimension').textContent = dimension;
    document.getElementById('modalBillPieces').textContent = Number(pieces).toLocaleString() + ' pcs';
    document.getElementById('modalBillUnitPrice').textContent = ' ' + Number(price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    const formattedTotal = ' ' + Number(total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('modalBillTotal').textContent = formattedTotal;
    document.getElementById('modalBillSubtotal').textContent = formattedTotal;
    document.getElementById('modalBillGrandTotal').textContent = formattedTotal;

    document.getElementById('btnBuyNow').style.display = 'inline-block'; // Show Buy Now
    document.getElementById('btnPrintBill').style.display = 'none'; // Hide Print for preview

    const modal = document.getElementById('billModal');
    modal.classList.add('active');
}

function closeBillModal() {
    document.getElementById('billModal').classList.remove('active');
}

function toggleCustomerPurchases(customerId) {
    const row = document.getElementById('purchases_' + customerId);
    if (row.style.display === 'none' || row.style.display === '') {
        row.style.display = 'table-row';
    } else {
        row.style.display = 'none';
    }
}

function toggleSection(sectionId) {
    const el = document.getElementById(sectionId);
    if (el) {
        if (el.style.display === 'none' || el.style.display === '') {
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    }
}

// Show/hide downpayment input if Credit is chosen
document.addEventListener('DOMContentLoaded', () => {
    const paySelect = document.getElementById('purchase_payment_type');
    const downContainer = document.getElementById('downpayment_container');
    if (paySelect && downContainer) {
        paySelect.addEventListener('change', () => {
            if (paySelect.value === 'Credit') {
                downContainer.style.display = 'flex';
            } else {
                downContainer.style.display = 'none';
            }
        });
    }

    // Auto-open bill modal if a new bill was just created
    <?php if ($new_bill_id): ?>
    openBillModal('<?php echo $new_bill_id; ?>');
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
