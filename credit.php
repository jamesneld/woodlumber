<?php
require_once __DIR__ . '/includes/db.php';

$page_title = 'Credit Management & Philippine Billing';
$active_page = 'credit';

$message = '';
$db = get_db_data();



// Handle Payment on Credit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'make_payment') {
    $creditId = trim($_POST['credit_id'] ?? '');
    $payAmount = floatval($_POST['payment_amount'] ?? 0);

    if (!empty($creditId) && $payAmount > 0) {
        foreach ($db['credits'] as &$credit) {
            if ($credit['id'] === $creditId) {
                $credit['paid'] += $payAmount;
                $credit['balance'] = max(0, $credit['amount'] - $credit['paid']);
                $credit['status'] = ($credit['balance'] <= 0) ? 'Paid' : 'Partial';
                break;
            }
        }
        save_db_data($db);
        $message = "Payment of  " . number_format($payAmount, 2) . " successfully applied!";
    }
}

// Split credits into Customer vs Owner
$customerCredits = [];
$ownerCredits = [];
$totalCustUtang = 0;
$totalOwnerUtang = 0;

foreach ($db['credits'] ?? [] as $c) {
    if (($c['type'] ?? '') === 'customer') {
        $customerCredits[] = $c;
        $totalCustUtang += floatval($c['balance'] ?? 0);
    } else {
        $ownerCredits[] = $c;
        $totalOwnerUtang += floatval($c['balance'] ?? 0);
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
                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                </svg>
                Credit Management &amp; Philippine Billing Statements
            </h1>
            <p>Track lumber loaned to customers and outstanding payables owed to tree owners</p>
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
            <div class="alert alert-success">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Credit Summary Cards -->
        <div class="grid-cards" style="grid-template-columns: 1fr 1fr;">
            <!-- 1. Customer Credit -->
            <div class="metric-card credit" style="--card-accent: #ea580c;">
                <div class="metric-header">
                    <span class="metric-title">1. Customer Credit (Receivables)</span>
                    <div class="metric-icon-wrap" style="background:#ffedd5; color:#ea580c;">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                    </div>
                </div>
                <div class="metric-value"> <?php echo number_format($totalCustUtang, 2); ?></div>
                <div class="metric-sub">Receivables to collect from customers who took lumber on credit.</div>
            </div>

            <!-- 2. Tree Owner Payables -->
            <div class="metric-card expenses" style="--card-accent: #dc2626;">
                <div class="metric-header">
                    <span class="metric-title">2. Tree Owner Payables (Wood Compra)</span>
                    <div class="metric-icon-wrap" style="background:#fee2e2; color:#dc2626;">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
                    </div>
                </div>
                <div class="metric-value"> <?php echo number_format($totalOwnerUtang, 2); ?></div>
                <div class="metric-sub">Payables owed by business to tree owners for purchased/compra lumber.</div>
            </div>
        </div>


        <!-- Section 1: Customer Credit Records (Receivables) -->
        <div class="content-card">
            <div class="card-header-bar" style="background:#fff7ed;">
                <h3 style="color:#9a3412;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle>
                    </svg>
                    Customer Credit Records (Lumber Loaned to Customers)
                </h3>
                <span class="badge badge-warning"><?php echo count($customerCredits); ?> Accounts</span>
            </div>
            <div class="table-responsive">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>Bill # / ID</th>
                            <th>Customer Name</th>
                            <th>Lumber Description</th>
                            <th>Total ()</th>
                            <th>Paid ()</th>
                            <th>Balance Due ()</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Payment Action</th>
                            <th>Statement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customerCredits)): ?>
                            <tr><td colspan="10" style="text-align:center; padding:1.5rem; color:#94a3b8;">No customer credit records.</td></tr>
                        <?php else: ?>
                            <?php foreach ($customerCredits as $c): ?>
                                <tr>
                                    <td><small style="color:#64748b; font-weight:700;"><?php echo htmlspecialchars($c['id']); ?></small></td>
                                    <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($c['description']); ?></td>
                                    <td> <?php echo number_format($c['amount'], 2); ?></td>
                                    <td> <?php echo number_format($c['paid'], 2); ?></td>
                                    <td><strong style="color:#ea580c; font-size:1.05rem;"> <?php echo number_format($c['balance'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($c['due_date']); ?></td>
                                    <td>
                                        <?php if ($c['status'] === 'Paid'): ?>
                                            <span class="badge badge-success">Fully Paid</span>
                                        <?php elseif ($c['status'] === 'Partial'): ?>
                                            <span class="badge badge-warning">Partial</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Unpaid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($c['balance'] > 0): ?>
                                            <form action="credit.php" method="POST" style="display:inline-flex; gap:0.35rem; align-items:center;">
                                                <input type="hidden" name="action" value="make_payment">
                                                <input type="hidden" name="credit_id" value="<?php echo htmlspecialchars($c['id']); ?>">
                                                <input type="number" step="0.5" name="payment_amount" placeholder="Pay " style="width:90px; padding:0.25rem 0.5rem; border-radius:4px; border:1px solid #cbd5e1; font-size:0.8rem;" required>
                                                <button type="submit" class="btn btn-primary btn-sm">Collect</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color:#15803d; font-size:0.82rem; font-weight:600;">✓ Settled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-secondary btn-sm" onclick="openCreditStatement('<?php echo htmlspecialchars($c['id']); ?>')">
                                            📄 Bill Statement
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: Tree Owner Payables (Compra Payables) -->
        <div class="content-card">
            <div class="card-header-bar" style="background:#fef2f2;">
                <h3 style="color:#991b1b;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                    Tree Owner Payables (Wood Compra Payables)
                </h3>
                <span class="badge badge-danger"><?php echo count($ownerCredits); ?> Accounts</span>
            </div>
            <div class="table-responsive">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>Bill # / ID</th>
                            <th>Tree Owner (Supplier)</th>
                            <th>Compra Particulars</th>
                            <th>Total Wood Value</th>
                            <th>Paid to Date</th>
                            <th>Balance Payable</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Payment Action</th>
                            <th>Statement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ownerCredits)): ?>
                            <tr><td colspan="10" style="text-align:center; padding:1.5rem; color:#94a3b8;">No tree owner payables found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ownerCredits as $o): ?>
                                <tr>
                                    <td><small style="color:#64748b; font-weight:700;"><?php echo htmlspecialchars($o['id']); ?></small></td>
                                    <td><strong><?php echo htmlspecialchars($o['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($o['description']); ?></td>
                                    <td> <?php echo number_format($o['amount'], 2); ?></td>
                                    <td> <?php echo number_format($o['paid'], 2); ?></td>
                                    <td><strong style="color:#dc2626; font-size:1.05rem;"> <?php echo number_format($o['balance'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($o['due_date']); ?></td>
                                    <td>
                                        <?php if ($o['status'] === 'Paid'): ?>
                                            <span class="badge badge-success">Fully Settled</span>
                                        <?php elseif ($o['status'] === 'Partial'): ?>
                                            <span class="badge badge-warning">Partial Balance</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Unpaid Compra</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($o['balance'] > 0): ?>
                                            <form action="credit.php" method="POST" style="display:inline-flex; gap:0.35rem; align-items:center;">
                                                <input type="hidden" name="action" value="make_payment">
                                                <input type="hidden" name="credit_id" value="<?php echo htmlspecialchars($o['id']); ?>">
                                                <input type="number" step="0.5" name="payment_amount" placeholder="Pay " style="width:90px; padding:0.25rem 0.5rem; border-radius:4px; border:1px solid #cbd5e1; font-size:0.8rem;" required>
                                                <button type="submit" class="btn btn-success btn-sm">Pay</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color:#15803d; font-size:0.82rem; font-weight:600;">✓ Settled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-secondary btn-sm" onclick="openCreditStatement('<?php echo htmlspecialchars($o['id']); ?>')">
                                            📄 Voucher
                                        </button>
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
     PHILIPPINE STATEMENT OF ACCOUNT / BILL MODAL
     ========================================================= -->
<div class="bill-modal-overlay" id="creditStatementModal">
    <div class="philippine-bill-card">
        <div class="bill-header">
            <h2>WOOD LUMBER SYSTEM &amp; SAWMILL</h2>
            <p>Authorized Timber, Lumber &amp; Wood Processing Plant</p>
            <p>Republic of the Philippines &bull; Non-VAT Reg. TIN: 482-194-820-000</p>
            <div class="bill-title-tag" id="stmtTitle">PHILIPPINE STATEMENT OF ACCOUNT / BILL</div>
        </div>

        <div class="bill-info-grid">
            <div class="bill-info-box">
                <div><strong>Reference No:</strong> <span id="stmtRefNo">-</span></div>
                <div><strong>Statement Date:</strong> <?php echo date('F d, Y'); ?></div>
                <div><strong>Due Date:</strong> <span id="stmtDueDate">-</span></div>
            </div>
            <div class="bill-info-box">
                <div><strong>Account Name:</strong> <span id="stmtAccountName">-</span></div>
                <div><strong>Currency:</strong> </div>
                <div><strong>Status:</strong> <span id="stmtStatus" style="font-weight:700;">-</span></div>
            </div>
        </div>

        <table class="bill-table">
            <thead>
                <tr>
                    <th>Description / Particulars</th>
                    <th>Total Amount ()</th>
                    <th>Paid to Date ()</th>
                    <th>Outstanding Balance ()</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td id="stmtDescription">-</td>
                    <td id="stmtTotalAmount">-</td>
                    <td id="stmtPaidAmount">-</td>
                    <td id="stmtBalance" style="font-weight:700; color:#ea580c;">-</td>
                </tr>
            </tbody>
        </table>

        <div class="bill-totals">
            <table class="bill-totals-table">
                <tr class="bill-grand-total">
                    <td>TOTAL BALANCE DUE:</td>
                    <td style="text-align:right;" id="stmtGrandBalance"> 0.00</td>
                </tr>
            </table>
        </div>

        <div class="bill-signatures">
            <div>
                <div class="sig-line">Prepared &amp; Verified By<br><small>Accounting / Credit Officer</small></div>
            </div>
            <div>
                <div class="sig-line">Acknowledged &amp; Conformed By<br><small>Account Holder / Authorized Signatory</small></div>
            </div>
        </div>

        <div class="bill-modal-actions">
            <button class="btn btn-secondary" onclick="closeCreditStatement()">Close</button>
            <button class="btn btn-primary" onclick="window.print()">
                🖨️ Print Philippine Statement / Bill
            </button>
        </div>
    </div>
</div>

<script>
const allCredits = <?php echo json_encode($db['credits'] ?? []); ?>;

function openCreditStatement(creditId) {
    const item = allCredits.find(c => c.id === creditId);
    if (!item) return;

    document.getElementById('stmtRefNo').textContent = item.id;
    document.getElementById('stmtDueDate').textContent = item.due_date;
    document.getElementById('stmtAccountName').textContent = item.name;
    document.getElementById('stmtDescription').textContent = item.description;
    
    document.getElementById('stmtTotalAmount').textContent = ' ' + Number(item.amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('stmtPaidAmount').textContent = ' ' + Number(item.paid).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    const balanceStr = ' ' + Number(item.balance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('stmtBalance').textContent = balanceStr;
    document.getElementById('stmtGrandBalance').textContent = balanceStr;

    const statusEl = document.getElementById('stmtStatus');
    statusEl.textContent = item.status.toUpperCase();
    statusEl.style.color = item.status === 'Paid' ? '#15803d' : '#ea580c';

    const titleTag = document.getElementById('stmtTitle');
    if (item.type === 'owner') {
        titleTag.textContent = 'PHILIPPINE WOOD COMPRA DISBURSEMENT VOUCHER';
    } else {
        titleTag.textContent = 'PHILIPPINE STATEMENT OF ACCOUNT / CREDIT BILL';
    }

    document.getElementById('creditStatementModal').classList.add('active');
}

function closeCreditStatement() {
    document.getElementById('creditStatementModal').classList.remove('active');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
