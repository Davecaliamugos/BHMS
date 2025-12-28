<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../auth/login.php");
    exit();
}
include("../config/db.php");

// --- 1. FILTERS & SEARCH LOGIC ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_month = isset($_GET['month']) ? mysqli_real_escape_string($conn, $_GET['month']) : '';
$filter_year = isset($_GET['year']) ? mysqli_real_escape_string($conn, $_GET['year']) : date('Y');

$where_clauses = [];
if (!empty($search))
    $where_clauses[] = "tenants.name LIKE '%$search%'";
if (!empty($filter_month))
    $where_clauses[] = "MONTH(payments.payment_date) = '$filter_month'";
if (!empty($filter_year))
    $where_clauses[] = "YEAR(payments.payment_date) = '$filter_year'";

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : "";

// --- 2. PAGINATION SETUP ---
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $limit;

$total_query = "SELECT COUNT(*) as count FROM payments JOIN tenants ON payments.tenant_id = tenants.tenant_id $where_sql";
$total_count_result = mysqli_query($conn, $total_query);
$total_results = mysqli_fetch_assoc($total_count_result)['count'];
$total_pages = ceil($total_results / $limit);

// --- 3. STATS CALCULATION ---
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments"))['total'] ?? 0;
$monthly_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())"))['total'] ?? 0;
$active_tenants = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tenants WHERE room_id IS NOT NULL"))['count'];

// --- 4. MAIN DATA QUERY (PAID HISTORY) ---
$query = "SELECT payments.payment_id, tenants.name, payments.amount, payments.payment_date, rooms.room_number 
          FROM payments 
          JOIN tenants ON payments.tenant_id = tenants.tenant_id 
          LEFT JOIN rooms ON tenants.room_id = rooms.room_id 
          $where_sql 
          ORDER BY payments.payment_date DESC 
          LIMIT $start, $limit";
$result = mysqli_query($conn, $query);

// --- 5. UNPAID TENANTS LOGIC (NEW FEATURE) ---
$status_m = !empty($filter_month) ? $filter_month : date('m');
$status_y = !empty($filter_year) ? $filter_year : date('Y');

$unpaid_query = "SELECT t.name, r.room_number, r.price 
                 FROM tenants t 
                 JOIN rooms r ON t.room_id = r.room_id 
                 WHERE t.tenant_id NOT IN (
                    SELECT tenant_id FROM payments 
                    WHERE MONTH(payment_date) = '$status_m' 
                    AND YEAR(payment_date) = '$status_y'
                 )
                 AND (t.name LIKE '%$search%')";
$unpaid_res = mysqli_query($conn, $unpaid_query);
$unpaid_count = mysqli_num_rows($unpaid_res);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing & Payments | BHMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; margin: 0; }
        .content-wrapper { flex-grow: 1; height: 100vh; overflow-y: auto; }
        .card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.3s ease; }
        .stat-card { border-left: 6px solid #6366f1; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .amount-text { color: #059669; font-weight: 700; }
        .bg-soft-primary { background-color: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; }
        .nav-pills .nav-link { color: #64748b; border-radius: 8px; padding: 10px 20px; }
        .nav-pills .nav-link.active { background-color: #6366f1; color: white; box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4); }
        .table thead th { background-color: #f8fafc; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; color: #64748b; border-top: none; }
        .table tbody tr { transition: background-color 0.2s ease; }
        .table tbody tr:hover { background-color: #f1f5f9; }
        .btn-primary { background-color: #6366f1; border: none; }
        .btn-primary:hover { background-color: #4f46e5; }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .search-input { padding-left: 40px !important; border-radius: 10px; }
        .badge { font-weight: 600; padding: 0.5em 0.8em; }
    </style>
</head>

<body>

    <div class="d-flex w-100">
        <?php include("../includes/sidebar.php"); ?>

        <main class="content-wrapper p-4 p-md-5">
            <div class="d-md-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="fw-bold h2 mb-1">Billing & Payments</h1>
                    <p class="text-muted">Monitor cash flow and revenue history for your property.</p>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-3 bg-soft-primary rounded-3"><i class="fas fa-wallet fa-lg"></i></div>
                            <div>
                                <small class="text-muted fw-bold d-block mb-1">TOTAL REVENUE</small>
                                <h3 class="fw-bold mb-0">₱<?php echo number_format($total_revenue, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-3" style="border-left-color: #10b981;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-3 rounded-3" style="background: #ecfdf5; color: #059669;"><i class="fas fa-calendar-check fa-lg"></i></div>
                            <div>
                                <small class="text-muted fw-bold d-block mb-1">THIS MONTH</small>
                                <h3 class="fw-bold mb-0 text-success">₱<?php echo number_format($monthly_revenue, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-3" style="border-left-color: #3b82f6;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-3 rounded-3" style="background: #eff6ff; color: #2563eb;"><i class="fas fa-users fa-lg"></i></div>
                            <div>
                                <small class="text-muted fw-bold d-block mb-1">ACTIVE TENANTS</small>
                                <h3 class="fw-bold mb-0 text-primary"><?php echo $active_tenants; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-4 mb-4">
                <form method="GET" class="row g-3">
                    <div class="col-md-4 position-relative">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control search-input" placeholder="Search tenant name..."
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="month" class="form-select rounded-3">
                            <option value="">All Months</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo ($filter_month == $m) ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="year" class="form-select rounded-3">
                            <?php for ($y = date('Y'); $y >= 2023; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo ($filter_year == $y) ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-dark w-100 rounded-3">Filter</button>
                        <a href="index.php" class="btn btn-outline-secondary w-100 rounded-3">Reset</a>
                    </div>
                </form>
            </div>

            <ul class="nav nav-pills mb-4" id="paymentTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold" id="paid-tab" data-bs-toggle="tab" data-bs-target="#paid-list" type="button">
                        <i class="fas fa-receipt me-2"></i>Paid History
                    </button>
                </li>
                <li class="nav-item ms-2">
                    <button class="nav-link fw-bold" id="unpaid-tab" data-bs-toggle="tab" data-bs-target="#unpaid-list" type="button" 
                            style="border: 1px solid #ef4444; color: #ef4444;">
                        <i class="fas fa-clock me-2"></i>Unpaid (<?php echo $unpaid_count; ?>)
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="paid-list">
                    <div class="card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4 py-3">TENANT</th>
                                        <th class="py-3">UNIT</th>
                                        <th class="py-3">AMOUNT</th>
                                        <th class="py-3 text-center">STATUS</th>
                                        <th class="py-3">DATE</th>
                                        <th class="pe-4 py-3 text-end">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($result) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-soft-primary">
                                                        <?php echo $row['room_number'] ? 'Unit ' . $row['room_number'] : 'N/A'; ?>
                                                    </span>
                                                </td>
                                                <td class="amount-text">₱<?php echo number_format($row['amount'], 2); ?></td>
                                                <td class="text-center">
                                                    <span class="badge rounded-pill" style="background: #dcfce7; color: #166534;">
                                                        <i class="fas fa-check-circle me-1"></i> Paid
                                                    </span>
                                                </td>
                                                <td class="text-muted small"><?php echo date("M d, Y", strtotime($row['payment_date'])); ?></td>
                                                <td class="pe-4 text-end">
                                                    <div class="btn-group">
                                                        <a href="generate_receipt.php?id=<?php echo $row['payment_id']; ?>" target="_blank"
                                                            class="btn btn-sm btn-outline-success" title="Print Receipt">
                                                            <i class="fas fa-file-invoice"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-outline-primary edit-btn"
                                                            data-id="<?php echo $row['payment_id']; ?>"
                                                            data-amount="<?php echo $row['amount']; ?>"
                                                            data-date="<?php echo $row['payment_date']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger delete-btn"
                                                            data-id="<?php echo $row['payment_id']; ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <img src="https://illustrations.popsy.co/flat/no-data.svg" alt="No data" style="width: 150px; opacity: 0.6;">
                                                <p class="text-muted mt-3">No payment records found matching your criteria.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($total_pages > 1): ?>
                            <div class="p-3 border-top d-flex justify-content-center bg-light">
                                <nav>
                                    <ul class="pagination pagination-sm mb-0">
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                                <a class="page-link shadow-none" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&month=<?php echo $filter_month; ?>&year=<?php echo $filter_year; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="tab-pane fade" id="unpaid-list">
                    <div class="card overflow-hidden border-top border-danger border-4 shadow-sm">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4 py-3">TENANT</th>
                                        <th class="py-3">UNIT</th>
                                        <th class="py-3 text-danger">MONTHLY RENT</th>
                                        <th class="pe-4 py-3 text-end">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($unpaid_count > 0): ?>
                                        <?php while ($u = mysqli_fetch_assoc($unpaid_res)): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold"><?php echo htmlspecialchars($u['name']); ?></div>
                                                </td>
                                                <td><span class="badge bg-light text-dark border">Unit <?php echo $u['room_number']; ?></span></td>
                                                <td class="fw-bold text-danger">₱<?php echo number_format($u['price'], 2); ?></td>
                                                <td class="pe-4 text-end">
                                                    <!-- Payment collection disabled -->
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5">
                                                <div class="p-4 rounded-circle bg-light d-inline-block mb-3"><i class="fas fa-glass-cheers text-success fa-3x"></i></div>
                                                <h5 class="fw-bold">All caught up!</h5>
                                                <p class="text-muted">All tenants have paid for this period.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white p-4">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>Record New Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="add.php" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Tenant</label>
                            <select name="tenant_id" id="tenantSelect" class="form-select" required>
                                <option value="" data-price="0">-- Choose Tenant --</option>
                                <?php
                                $cM = date('m');
                                $cY = date('Y');
                                $q_tenants = "SELECT t.tenant_id, t.name, r.price 
                                              FROM tenants t
                                              LEFT JOIN rooms r ON t.room_id = r.room_id 
                                              WHERE t.room_id IS NOT NULL 
                                              AND t.tenant_id NOT IN (
                                                  SELECT tenant_id FROM payments 
                                                  WHERE MONTH(payment_date) = '$cM' 
                                                  AND YEAR(payment_date) = '$cY'
                                              )
                                              ORDER BY t.name ASC";
                                $tenants_list = mysqli_query($conn, $q_tenants);
                                while ($t = mysqli_fetch_assoc($tenants_list)) {
                                    echo "<option value='{$t['tenant_id']}' data-price='{$t['price']}'>{$t['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Amount (₱)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">₱</span>
                                <input type="number" name="amount" id="amountInput" class="form-control" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_payment" class="btn btn-primary px-4 rounded-3">Save Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="modal-title fw-bold">Edit Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="edit_payment.php" method="POST">
                    <input type="hidden" name="payment_id" id="edit_id">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Amount (₱)</label>
                            <input type="number" name="amount" id="edit_amount" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Date</label>
                            <input type="date" name="payment_date" id="edit_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="submit" name="update_payment" class="btn btn-dark w-100 rounded-3">Update Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow text-center p-4">
                <div class="text-danger mb-3"><i class="fas fa-exclamation-triangle fa-3x"></i></div>
                <h5 class="fw-bold">Delete Record?</h5>
                <p class="text-muted small">This action cannot be undone. Are you sure you want to remove this payment?</p>
                <form action="delete.php" method="POST">
                    <input type="hidden" name="payment_id" id="delete_id">
                    <div class="d-grid gap-2">
                        <button type="submit" name="delete_payment" class="btn btn-danger">Yes, Delete</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('tenantSelect').addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const amountInput = document.getElementById('amountInput');
            if (price && price !== '0') { amountInput.value = parseFloat(price).toFixed(2); } else { amountInput.value = ''; }
        });

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.onclick = function () {
                document.getElementById('delete_id').value = this.dataset.id;
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            }
        });

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.onclick = function () {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_amount').value = this.dataset.amount;
                document.getElementById('edit_date').value = this.dataset.date;
                new bootstrap.Modal(document.getElementById('editPaymentModal')).show();
            }
        });
    </script>
</body>
</html>