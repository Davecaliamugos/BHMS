<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['tenant_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$tenant_id = $_SESSION['tenant_id'];

// --- 1. Filter & Pagination Logic ---
$filter_year = isset($_GET['year']) ? mysqli_real_escape_string($conn, $_GET['year']) : date('Y');
$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- 2. Fetch Tenant/Room Info for Stat Cards ---
$info_query = "SELECT t.*, r.price, r.room_number 
               FROM tenants t 
               LEFT JOIN rooms r ON t.room_id = r.room_id 
               WHERE t.tenant_id = '$tenant_id'";
$info_result = mysqli_query($conn, $info_query);
$tenant_data = mysqli_fetch_assoc($info_result);

$monthly_rent = $tenant_data['price'] ?? 0;

// --- 3. Calculate Stats (Total Paid this year) ---
$total_paid_query = "SELECT SUM(amount) as total FROM payments 
                     WHERE tenant_id = '$tenant_id' AND YEAR(payment_date) = '$filter_year'";
$total_paid_res = mysqli_fetch_assoc(mysqli_query($conn, $total_paid_query));
$total_paid = $total_paid_res['total'] ?? 0;

// --- 4. Identify Paid Months for the Tracker ---
$paid_months = [];
$check_paid = mysqli_query($conn, "SELECT MONTH(payment_date) as m FROM payments 
                                    WHERE tenant_id = '$tenant_id' AND YEAR(payment_date) = '$filter_year'");
while($row = mysqli_fetch_assoc($check_paid)) { 
    $paid_months[] = (int)$row['m']; 
}

// --- 5. Fetch Paged Payment History ---
$history_query = "SELECT * FROM payments 
                  WHERE tenant_id = '$tenant_id' AND YEAR(payment_date) = '$filter_year'
                  ORDER BY payment_date DESC LIMIT $limit OFFSET $offset";
$history_res = mysqli_query($conn, $history_query);

// Count total for paginator
$count_query = "SELECT COUNT(*) as count FROM payments WHERE tenant_id = '$tenant_id' AND YEAR(payment_date) = '$filter_year'";
$total_count_res = mysqli_fetch_assoc(mysqli_query($conn, $count_query));
$total_pages = ceil($total_count_res['count'] / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bills | BHMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --sidebar-width: 280px; 
            --primary-color: #4361ee; 
            --sidebar-bg: #1e1e2d;
            --bg-light: #f4f7fe; 
        }

        body { background-color: var(--bg-light); font-family: 'Inter', sans-serif; overflow-x: hidden; }

        /* Sidebar & Layout */
        .wrapper { display: flex; min-height: 100vh; }
        #sidebar { 
            width: var(--sidebar-width); background: var(--sidebar-bg); 
            position: fixed; height: 100vh; z-index: 1050; display: flex; flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #sidebar .sidebar-header { padding: 2.5rem 1.5rem; color: white; font-weight: 800; font-size: 1.5rem; }
        
        .nav-link { 
            color: #a2a3b7; padding: 14px 24px; margin: 4px 15px; border-radius: 12px;
            display: flex; align-items: center; gap: 12px; transition: 0.3s; 
        }
        .nav-link:hover { color: white; background: rgba(255, 255, 255, 0.08); }
        .nav-link.active { color: white; background: var(--primary-color); box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3); }

        #content { flex: 1; margin-left: var(--sidebar-width); padding: 2.5rem; transition: all 0.3s; }

        /* Mobile Adjustments */
        .mobile-header {
            display: none; position: fixed; top: 0; left: 0; right: 0; background: white;
            padding: 15px 20px; z-index: 1000; box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            align-items: center; justify-content: space-between;
        }
        .sidebar-overlay {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4);
            backdrop-filter: blur(4px); z-index: 1040;
        }

        @media (max-width: 992px) {
            #sidebar { left: calc(-1 * var(--sidebar-width)); }
            #sidebar.active { left: 0; }
            #content { margin-left: 0; padding: 1.5rem; padding-top: 5.5rem; }
            .mobile-header { display: flex; }
            .sidebar-overlay.active { display: block; }
        }

        /* Cards & Components */
        .stat-card { 
            border: none; border-radius: 20px; background: #ffffff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03); transition: 0.3s;
        }
        .tracker-box {
            background: #fff; border: 1px solid #edf2f7; border-radius: 12px;
            padding: 12px; text-align: center; transition: 0.2s;
        }
        .tracker-box.paid { border-bottom: 3px solid #10b981; background: #f0fdf4; }
        .tracker-box.unpaid { border-bottom: 3px solid #ef4444; background: #fff1f2; }

        .badge-pill { padding: 5px 12px; border-radius: 50px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; }
        
        /* Pagination */
        .pagination .page-link { border: none; margin: 0 3px; border-radius: 8px !important; color: #4b5563; font-weight: 600; }
        .pagination .page-item.active .page-link { background-color: var(--primary-color); box-shadow: 0 4px 10px rgba(67,97,238,0.2); }
    </style>
</head>
<body>

    <div class="mobile-header">
        <button class="btn border-0 p-0" id="burgerToggle"><i class="fas fa-bars fa-lg"></i></button>
        <span class="fw-bold text-primary">BHMS Bills</span>
        <div style="width: 25px;"></div>
    </div>

    <div class="sidebar-overlay" id="overlay"></div>

    <div class="wrapper">
        <nav id="sidebar">
            <div class="sidebar-header d-flex align-items-center justify-content-between">
                <span><i class="fas fa-building me-2 text-primary"></i>BHMS</span>
                <button class="btn text-white d-lg-none border-0" id="sidebarClose"><i class="fas fa-times"></i></button>
            </div>
            <ul class="nav flex-column flex-grow-1">
                <li class="nav-item">
                    <a href="../dashboard/tenants_dashboard.php" class="nav-link">
                        <i class="fas fa-th-large"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="fas fa-file-invoice-dollar"></i> My Bills
                    </a>
                </li>
            </ul>
            <div class="p-4">
                <button type="button" class="btn btn-outline-danger w-100 rounded-pill py-2 small fw-bold" data-bs-toggle="modal" data-bs-target="#logoutModal">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </button>
            </div>
        </nav>

        <div id="content">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h2 class="fw-800 mb-1">Billing Statement</h2>
                    <p class="text-muted mb-0">Overview of your financial records for <?php echo $filter_year; ?>.</p>
                </div>
                <div class="bg-white p-2 px-3 rounded-pill shadow-sm border small fw-bold">
                    <i class="fas fa-calendar-alt text-primary me-2"></i> Fiscal Year <?php echo $filter_year; ?>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card stat-card p-4 border-start border-primary border-5">
                        <small class="text-muted d-block fw-bold text-uppercase mb-1">Monthly Rate</small>
                        <h3 class="fw-800 mb-0">₱<?php echo number_format($monthly_rent, 2); ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-4 border-start border-success border-5">
                        <small class="text-muted d-block fw-bold text-uppercase mb-1">Total Remitted</small>
                        <h3 class="fw-800 mb-0 text-success">₱<?php echo number_format($total_paid, 2); ?></h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-4 border-start border-warning border-5">
                        <small class="text-muted d-block fw-bold text-uppercase mb-1">Assigned Room</small>
                        <h3 class="fw-800 mb-0">Unit <?php echo $tenant_data['room_number'] ?? 'N/A'; ?></h3>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card stat-card p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold mb-0">Yearly Tracker</h6>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light rounded-circle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu shadow-sm border-0">
                                    <li><a class="dropdown-item small" href="?year=2024">View 2024</a></li>
                                    <li><a class="dropdown-item small" href="?year=2025">View 2025</a></li>
                                    <li><a class="dropdown-item small" href="?year=2026">View 2026</a></li>
                                    <li><a class="dropdown-item small" href="?year=2027">View 2027</a></li>
                                    <li><a class="dropdown-item small" href="?year=2028">View 2028</a></li>
                                    <li><a class="dropdown-item small" href="?year=2029">View 2029</a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="row g-2">
                            <?php 
                            $max_month = ($filter_year == date('Y')) ? date('n') : 12;
                            for ($m = 1; $m <= 12; $m++): 
                                $m_name = date('M', mktime(0, 0, 0, $m, 1));
                                $is_paid_month = in_array($m, $paid_months);
                                $is_future = ($filter_year == date('Y') && $m > date('n'));
                            ?>
                                <div class="col-4">
                                    <div class="tracker-box <?php echo $is_paid_month ? 'paid' : ($is_future ? '' : 'unpaid'); ?>">
                                        <small class="d-block fw-bold text-uppercase text-muted" style="font-size: 0.65rem;"><?php echo $m_name; ?></small>
                                        <?php if ($is_paid_month): ?>
                                            <i class="fas fa-check-circle text-success mt-1"></i>
                                        <?php elseif ($is_future): ?>
                                            <i class="fas fa-circle-notch text-light mt-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger mt-1"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex justify-content-between small text-muted">
                                <span><i class="fas fa-circle text-success me-1"></i> Paid</span>
                                <span><i class="fas fa-circle text-danger me-1"></i> Overdue</span>
                                <span><i class="fas fa-circle text-light me-1"></i> Future</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card stat-card p-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">Transaction History</h5>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Statement
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light border-0">
                                    <tr class="small text-uppercase text-muted">
                                        <th class="border-0 rounded-start">Reference</th>
                                        <th class="border-0">Billing Date</th>
                                        <th class="border-0">Amount Paid</th>
                                        <th class="border-0 text-end rounded-end">Method</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($history_res) > 0): ?>
                                        <?php while($row = mysqli_fetch_assoc($history_res)): ?>
                                        <tr class="border-bottom-0">
                                            <td><span class="fw-bold text-primary">#<?php echo $row['payment_id']; ?></span></td>
                                            <td class="text-muted small"><?php echo date('M d, Y', strtotime($row['payment_date'])); ?></td>
                                            <td><span class="fw-800 text-dark">₱<?php echo number_format($row['amount'], 2); ?></span></td>
                                            <td class="text-end">
                                                <span class="badge-pill bg-light text-dark border"><?php echo $row['payment_method']; ?></span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center py-5 text-muted">No transaction logs found for <?php echo $filter_year; ?>.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination pagination-sm justify-content-center">
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link shadow-sm" href="?page=<?php echo $i; ?>&year=<?php echo $filter_year; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-body p-4 text-center">
                    <div class="text-danger mb-3">
                        <i class="fas fa-sign-out-alt fa-3x"></i>
                    </div>
                    <h5 class="fw-bold">Ready to Leave?</h5>
                    <p class="text-muted small">Are you sure you want to log out of your dashboard?</p>
                    <div class="d-flex gap-2 justify-content-center mt-4">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <a href="../auth/logout.php" class="btn btn-danger rounded-pill px-4">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const burger = document.getElementById('burgerToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const closeBtn = document.getElementById('sidebarClose');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        burger.onclick = toggleSidebar;
        overlay.onclick = toggleSidebar;
        closeBtn.onclick = toggleSidebar;
    </script>
</body>
</html>