<?php
session_start();
if (!isset($_SESSION['tenant_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include("../config/db.php");

$tenant_id = $_SESSION['tenant_id'];
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// 1. Fetch Tenant & Room Info
// Note: Using prepared statements is safer, but keeping your logic for consistency.
$query = "SELECT t.*, r.room_number, r.price, r.status
          FROM tenants t
          LEFT JOIN rooms r ON t.room_id = r.room_id
          WHERE t.tenant_id = '$tenant_id'";
$tenant_data = mysqli_fetch_assoc(mysqli_query($conn, $query));

// Fetch available rooms
$available_rooms = [];
if (empty($tenant_data['room_id'])) {
    $rooms_res = mysqli_query($conn, "SELECT * FROM rooms WHERE status = 'available'");
    while($r = mysqli_fetch_assoc($rooms_res)) {
        $available_rooms[] = $r;
    }
}

// 2. Monthly Payment Status
$pay_query = "SELECT * FROM payments 
              WHERE tenant_id = '$tenant_id' 
              AND MONTH(payment_date) = '$currentMonth' 
              AND YEAR(payment_date) = '$currentYear'";
$payment_record = mysqli_fetch_assoc(mysqli_query($conn, $pay_query));
$is_paid = !empty($payment_record);

// 3. Full Payment History
$history_query = "SELECT * FROM payments WHERE tenant_id = '$tenant_id' ORDER BY payment_date DESC";
$history_res = mysqli_query($conn, $history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Dashboard | BHMS</title>
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

        body { 
            background-color: var(--bg-light); 
            font-family: 'Inter', sans-serif; 
            overflow-x: hidden;
        }

        .wrapper { display: flex; min-height: 100vh; }

        #sidebar { 
            width: var(--sidebar-width); 
            background: var(--sidebar-bg); 
            position: fixed;
            height: 100vh;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1050;
            display: flex;
            flex-direction: column;
        }

        #sidebar .sidebar-header { 
            padding: 2.5rem 1.5rem; 
            color: white; 
            font-weight: 800; 
            font-size: 1.5rem; 
            letter-spacing: -0.5px;
        }

        .nav-link { 
            color: #a2a3b7; 
            padding: 14px 24px; 
            margin: 4px 15px;
            border-radius: 12px;
            display: flex; 
            align-items: center; 
            gap: 12px; 
            transition: 0.3s; 
            text-decoration: none;
        }

        .nav-link:hover { color: white; background: rgba(255, 255, 255, 0.08); }
        .nav-link.active { color: white; background: var(--primary-color); box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3); }

        #content { 
            flex: 1; 
            margin-left: var(--sidebar-width);
            padding: 2.5rem; 
            transition: all 0.3s;
        }

        .mobile-header {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0;
            background: white;
            padding: 15px 20px;
            z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(4px);
            z-index: 1040;
        }

        @media (max-width: 992px) {
            #sidebar { left: calc(-1 * var(--sidebar-width)); }
            #sidebar.active { left: 0; }
            #content { margin-left: 0; padding: 1.5rem; padding-top: 5.5rem; }
            .mobile-header { display: flex; }
            .sidebar-overlay.active { display: block; }
        }

        .tenant-card { 
            border: none; 
            border-radius: 20px; 
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03); 
            transition: transform 0.3s ease;
        }
        .tenant-card:hover { transform: translateY(-5px); }

        .stat-icon { 
            width: 54px; height: 54px; 
            border-radius: 16px; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 1.4rem; 
        }

        .room-selector-card { 
            border: 2px dashed #cbd5e0; 
            background: #f8faff; 
            border-radius: 20px; 
        }
        .room-option { 
            background: white; 
            border: 1px solid #edf2f7; 
            border-radius: 15px;
            transition: 0.2s; 
        }
        .room-option:hover { border-color: var(--primary-color); box-shadow: 0 8px 20px rgba(67,97,238,0.1); }

        .toast-container { position: fixed; top: 25px; right: 25px; z-index: 9999; }
    </style>
</head>
<body>

    <div class="mobile-header">
        <button class="btn border-0 p-0" id="burgerToggle"><i class="fas fa-bars fa-lg text-dark"></i></button>
        <span class="fw-bold text-primary">BHMS Dashboard</span>
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
                    <a href="#" class="nav-link active">
                        <i class="fas fa-th-large"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../payments/tenants_bills.php" class="nav-link">
                        <i class="fas fa-file-invoice-dollar"></i> <span>My Bills</span>
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
            <div class="row align-items-center mb-4 g-3">
                <div class="col-md-7">
                    <h2 class="fw-800 mb-1">Hello, <?php echo explode(' ', $tenant_data['name'])[0]; ?>!</h2>
                    <p class="text-muted mb-0">Here's what's happening with your account today.</p>
                </div>
                <div class="col-md-5 text-md-end">
                    <div class="d-inline-flex align-items-center bg-white p-2 px-3 rounded-pill shadow-sm border">
                        <div class="bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                            <i class="fas fa-door-open fa-xs"></i>
                        </div>
                        <span class="fw-bold small"><?php echo !empty($tenant_data['room_id']) ? "Unit " . $tenant_data['room_number'] : "No Unit Assigned"; ?></span>
                    </div>
                    <?php if (!empty($tenant_data['room_id']) && $tenant_data['status'] == 'maintenance'): ?>
                        <div class="d-block mt-2">
                            <span class="badge bg-warning text-dark rounded-pill px-3 py-1">
                                <i class="fas fa-tools me-1"></i> Under Maintenance
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($tenant_data['room_id'])): ?>
            <div class="room-selector-card p-4 mb-5">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon bg-primary text-white me-3 shadow-sm"><i class="fas fa-key"></i></div>
                    <h5 class="fw-bold mb-0">Select Your New Home</h5>
                </div>
                <div class="row g-3">
                    <?php if (!empty($available_rooms)): ?>
                        <?php foreach($available_rooms as $room): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="room-option p-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="d-block fw-bold text-dark">Room <?php echo $room['room_number']; ?></span>
                                    <span class="text-success fw-600 small">₱<?php echo number_format($room['price'], 2); ?></span>
                                </div>
                                <button onclick="assignRoom(<?php echo $room['room_id']; ?>)" class="btn btn-primary btn-sm rounded-pill px-3">Choose</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12"><div class="alert alert-light border text-muted small"><i class="fas fa-info-circle me-2"></i>No rooms available. Please wait for management.</div></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card tenant-card p-3">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary-subtle text-primary me-3"><i class="fas fa-wallet"></i></div>
                            <div>
                                <small class="text-muted d-block fw-600">Monthly Rent</small>
                                <span class="fw-bold h4 mb-0">₱<?php echo number_format($tenant_data['price'] ?? 0, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card tenant-card p-3">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon <?php echo $is_paid ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?> me-3">
                                <i class="fas <?php echo $is_paid ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block fw-600">Status (<?php echo date('M Y'); ?>)</small>
                                <span class="fw-bold h4 mb-0"><?php echo $is_paid ? 'Paid' : 'Unpaid'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card tenant-card p-3">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning-subtle text-warning me-3"><i class="fas fa-bullhorn"></i></div>
                            <div>
                                <small class="text-muted d-block fw-600">Announcements</small>
                                <span class="fw-bold h4 mb-0">System Live</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card tenant-card p-4 h-100">
                        <h6 class="fw-bold mb-4">Payment Control</h6>
                        <form method="GET" class="mb-4">
                            <div class="row g-2">
                                <div class="col-7">
                                    <select name="month" class="form-select border-0 bg-light rounded-3">
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?php echo $m; ?>" <?php if ($currentMonth == $m) echo 'selected'; ?>>
                                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-5">
                                    <select name="year" class="form-select border-0 bg-light rounded-3">
                                        <?php $yNow = date('Y'); for($y=$yNow; $y<=$yNow+5; $y++): ?>
                                            <option value="<?php echo $y; ?>" <?php if ($currentYear == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-dark w-100 mt-3 rounded-pill py-2 fw-bold shadow-sm">Filter View</button>
                        </form>

                        <div class="text-center p-4 rounded-4 <?php echo $is_paid ? 'bg-success-subtle' : 'bg-danger-subtle'; ?>">
                            <?php if ($is_paid): ?>
                                <div class="text-success mb-2"><i class="fas fa-check-circle fa-3x"></i></div>
                                <h5 class="fw-bold text-success mb-0">Balance Paid</h5>
                                <p class="text-muted small mt-1"><?php echo date('M d, Y', strtotime($payment_record['payment_date'])); ?></p>
                            <?php else: ?>
                                <div class="text-danger mb-2"><i class="fas fa-exclamation-circle fa-3x"></i></div>
                                <h5 class="fw-bold text-danger mb-1">Unpaid Bill</h5>
                                <?php if (!empty($tenant_data['room_id'])): ?>
                                    <button class="btn btn-primary mt-3 w-100 rounded-pill py-2 shadow" data-bs-toggle="modal" data-bs-target="#payModal">Pay Now</button>
                                <?php else: ?>
                                    <p class="text-muted x-small mb-0 mt-2">Assign a room to enable billing.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card tenant-card p-4 shadow-sm h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">Transaction History</h5>
                            <i class="fas fa-history text-muted"></i>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light border-0">
                                    <tr class="small text-uppercase text-muted">
                                        <th class="border-0 rounded-start">Ref ID</th>
                                        <th class="border-0">Date</th>
                                        <th class="border-0">Amount</th>
                                        <th class="border-0">Method</th>
                                        <th class="border-0 text-end rounded-end">Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($history_res) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($history_res)): ?>
                                            <tr>
                                                <td class="fw-bold">#PAY-<?php echo $row['payment_id']; ?></td>
                                                <td class="text-muted"><?php echo date('M d, Y', strtotime($row['payment_date'])); ?></td>
                                                <td class="fw-bold text-dark">₱<?php echo number_format($row['amount'], 2); ?></td>
                                                <td><span class="badge rounded-pill bg-light text-dark border px-3"><?php echo $row['payment_method'] ?? 'Online'; ?></span></td>
                                                <td class="text-end">
                                                    <a href="../payments/tenants_reciept.php?id=<?php echo $row['payment_id']; ?>" target="_blank" class="btn btn-sm btn-white border shadow-sm rounded-circle">
                                                        <i class="fas fa-print text-primary"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-5 text-muted">No transactions recorded yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="payModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg">
                <div class="modal-header border-0 bg-light p-4">
                    <h5 class="modal-title fw-bold">Settlement Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="paymentForm">
                        <input type="hidden" name="tenant_id" value="<?php echo $tenant_id; ?>">
                        <div class="text-center mb-4">
                            <p class="text-muted small text-uppercase fw-bold mb-1">Outstanding Balance</p>
                            <h1 class="fw-bold text-primary mb-0">₱<?php echo number_format($tenant_data['price'] ?? 0, 2); ?></h1>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Choose Gateway</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="method" id="gcash" value="GCash" checked>
                                    <label class="btn btn-outline-primary w-100 py-3 rounded-4" for="gcash">GCash</label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="method" id="maya" value="Maya">
                                    <label class="btn btn-outline-primary w-100 py-3 rounded-4" for="maya">Maya</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow">Confirm & Pay</button>
                    </form>
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

    <div class="toast-container">
        <div id="successToast" class="toast align-items-center text-white bg-success border-0 rounded-4" role="alert">
            <div class="d-flex p-3">
                <i class="fas fa-check-circle me-3 fa-lg"></i>
                <div class="toast-body fw-bold" id="toastMessage">Done! Action completed.</div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Control
        const burgerToggle = document.getElementById('burgerToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const sidebarClose = document.getElementById('sidebarClose');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        if (burgerToggle) burgerToggle.onclick = toggleSidebar;
        if (overlay) overlay.onclick = toggleSidebar;
        if (sidebarClose) sidebarClose.onclick = toggleSidebar;

        // Auto-close sidebar on link click (mobile)
        document.querySelectorAll('.nav-link').forEach(link => {
            link.onclick = () => {
                if (window.innerWidth <= 992) toggleSidebar();
            };
        });

        // Toast Helper
        function showToast(message) {
            document.getElementById('toastMessage').innerText = message;
            const toast = new bootstrap.Toast(document.getElementById('successToast'));
            toast.show();
        }

        // Action: Assign Room
        function assignRoom(roomId) {
            const fd = new FormData();
            fd.append('room_id', roomId);
            fetch('../payments/assign_room.php', { method: 'POST', body: fd })
                .then(() => {
                    showToast('Room successfully assigned!');
                    setTimeout(() => location.reload(), 1200);
                });
        }

        // Action: Payment Process
        document.getElementById('paymentForm').onsubmit = function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fetch('../payments/payment_process.php', { method: 'POST', body: fd })
                .then(() => {
                    bootstrap.Modal.getInstance(document.getElementById('payModal')).hide();
                    showToast('Payment successful!');
                    setTimeout(() => location.reload(), 1200);
                });
        };
    </script>
</body>
</html>