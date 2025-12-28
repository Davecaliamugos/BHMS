<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

include("../config/db.php");

// --- 1. DATA CALCULATIONS ---
$currentMonth = date('m');
$currentYear = date('Y');

$res_rooms = mysqli_query($conn, "SELECT COUNT(*) as total FROM rooms");
$total_rooms = mysqli_fetch_assoc($res_rooms)['total'] ?? 0;

$res_occ = mysqli_query($conn, "SELECT COUNT(*) as total FROM rooms WHERE status = 'occupied'");
$occupied_rooms = mysqli_fetch_assoc($res_occ)['total'] ?? 0;
$vacant_rooms = $total_rooms - $occupied_rooms;

$res_mtd = mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE MONTH(payment_date) = '$currentMonth' AND YEAR(payment_date) = '$currentYear'");
$monthly_revenue = mysqli_fetch_assoc($res_mtd)['total'] ?? 0;

$res_ytd = mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE YEAR(payment_date) = '$currentYear'");
$year_to_date = mysqli_fetch_assoc($res_ytd)['total'] ?? 0;

$res_expected = mysqli_query($conn, "SELECT SUM(r.price) as expected FROM tenants t JOIN rooms r ON t.room_id = r.room_id WHERE t.room_id IS NOT NULL");
$total_expected = mysqli_fetch_assoc($res_expected)['expected'] ?? 0;
$pending_collection = max(0, $total_expected - $monthly_revenue);

// --- 2. CHART DATA ---
$months_label = [];
$revenue_history = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date("m", strtotime("-$i months"));
    $y = date("Y", strtotime("-$i months"));
    $months_label[] = date("M", strtotime("-$i months"));
    $res_rev = mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE MONTH(payment_date) = '$m' AND YEAR(payment_date) = '$y'");
    $revenue_history[] = mysqli_fetch_assoc($res_rev)['total'] ?? 0;
}

$res_paid_count = mysqli_query($conn, "SELECT COUNT(DISTINCT tenant_id) as count FROM payments WHERE MONTH(payment_date) = '$currentMonth' AND YEAR(payment_date) = '$currentYear'");
$paid_tenants = mysqli_fetch_assoc($res_paid_count)['count'] ?? 0;
$unpaid_tenants = max(0, $occupied_rooms - $paid_tenants);

$res_status = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM rooms GROUP BY status");
$status_labels = []; $status_counts = [];
while($row = mysqli_fetch_assoc($res_status)) {
    $status_labels[] = ucfirst($row['status']);
    $status_counts[] = $row['count'];
}

$recent_payments = mysqli_query($conn, "SELECT p.*, t.name FROM payments p JOIN tenants t ON p.tenant_id = t.tenant_id ORDER BY p.payment_date DESC LIMIT 5");

$res_price_dist = mysqli_query($conn, "SELECT price, COUNT(*) as count FROM rooms GROUP BY price LIMIT 5");
$price_labels = []; $price_counts = [];
while($row = mysqli_fetch_assoc($res_price_dist)) {
    $price_labels[] = "₱" . number_format($row['price']);
    $price_counts[] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | BHMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-accent: #4361ee;
            --glass-bg: rgba(255, 255, 255, 0.85);
        }

        body { 
            background: linear-gradient(135deg, #f8faff 0%, #eff2f9 100%);
            font-family: 'Plus Jakarta Sans', sans-serif; 
            overflow-x: hidden; 
            color: #1e293b;
        }

        .main-content { min-height: 100vh; padding: 25px; }

        /* Enhanced Stat Cards */
        .stat-card { 
            border: none; 
            border-radius: 20px; 
            background: var(--glass-bg); 
            padding: 24px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.03); 
            height: 100%; 
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.5);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(67, 97, 238, 0.1);
            background: #fff;
        }

        /* Chart Cards Decor */
        .chart-card { 
            border: none; 
            border-radius: 20px; 
            background: #fff; 
            padding: 25px; 
            box-shadow: 0 8px 20px rgba(0,0,0,0.04); 
            margin-bottom: 24px; 
            border: 1px solid #f1f5f9;
        }

        .chart-container { position: relative; height: 320px; width: 100%; }

        .icon-box { 
            width: 52px; 
            height: 52px; 
            border-radius: 14px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.4rem; 
            margin-bottom: 15px;
        }

        /* Printing & Button Styles */
        .btn-primary {
            background: var(--primary-accent);
            border: none;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 8px 15px rgba(67, 97, 238, 0.25);
        }

        @media print {
            .sidebar, #toggleSidebar, .btn-primary { display: none !important; }
            .main-content { padding: 0; }
            .stat-card { border: 1px solid #ddd; box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include("../includes/sidebar.php"); ?>

    <div class="flex-grow-1 main-content">
        <div class="container-fluid">
            <header class="mb-5 d-flex flex-wrap justify-content-between align-items-center">
                <div class="d-flex align-items-center mb-2">
                    <button class="btn btn-white bg-white shadow-sm border rounded-3 d-md-none me-3" id="toggleSidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h2 class="fw-extrabold m-0 text-dark" style="letter-spacing: -1px;">Business Intelligence</h2>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary-subtle text-primary me-2">Live Update</span>
                            <small class="text-muted fw-medium"><?php echo date('F d, Y'); ?></small>
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary rounded-pill px-4 py-2" onclick="window.print()">
                    <i class="fas fa-file-export me-2"></i> Export Report
                </button>
            </header>

            <div class="row g-4 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="icon-box bg-primary-subtle text-primary"><i class="fas fa-wallet"></i></div>
                        <span class="text-muted small fw-bold text-uppercase opacity-75">MTD Revenue</span>
                        <h3 class="fw-bold mb-0 text-dark">₱<?php echo number_format($monthly_revenue, 0); ?></h3>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="icon-box bg-success-subtle text-success"><i class="fas fa-vault"></i></div>
                        <span class="text-muted small fw-bold text-uppercase opacity-75">YTD Total</span>
                        <h3 class="fw-bold mb-0 text-dark">₱<?php echo number_format($year_to_date, 0); ?></h3>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="icon-box bg-danger-subtle text-danger"><i class="fas fa-clock"></i></div>
                        <span class="text-muted small fw-bold text-uppercase opacity-75">Pending Collection</span>
                        <h3 class="fw-bold mb-0 text-dark">₱<?php echo number_format($pending_collection, 0); ?></h3>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="icon-box bg-warning-subtle text-warning"><i class="fas fa-door-open"></i></div>
                        <span class="text-muted small fw-bold text-uppercase opacity-75">Occupancy Rate</span>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo ($total_rooms > 0) ? round(($occupied_rooms/$total_rooms)*100) : 0; ?>%</h3>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="chart-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold m-0"><i class="fas fa-chart-line text-primary me-2"></i>Revenue History (6 Months)</h6>
                            <small class="text-muted">Trends across last semester</small>
                        </div>
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="chart-card">
                        <h6 class="fw-bold mb-4"><i class="fas fa-chart-pie text-danger me-2"></i>Room Status</h6>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-card">
                        <h6 class="fw-bold mb-4"><i class="fas fa-percentage text-success me-2"></i>Collection Progress</h6>
                        <div class="chart-container">
                            <canvas id="collectionChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-card">
                        <h6 class="fw-bold mb-4"><i class="fas fa-tags text-dark me-2"></i>Inventory Pricing</h6>
                        <div class="chart-container">
                            <canvas id="priceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // General Options for responsiveness
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { 
                position: 'bottom', 
                labels: { 
                    usePointStyle: true,
                    padding: 20,
                    font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" } 
                } 
            },
            tooltip: {
                backgroundColor: '#1e293b',
                padding: 12,
                titleFont: { size: 14 },
                cornerRadius: 8
            }
        }
    };

    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months_label); ?>,
            datasets: [{
                label: 'Monthly Revenue',
                data: <?php echo json_encode($revenue_history); ?>,
                borderColor: '#4361ee',
                borderWidth: 3,
                backgroundColor: (context) => {
                    const ctx = context.chart.ctx;
                    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                    gradient.addColorStop(0, 'rgba(67, 97, 238, 0.25)');
                    gradient.addColorStop(1, 'rgba(67, 97, 238, 0.0)');
                    return gradient;
                },
                fill: true, 
                tension: 0.4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#4361ee',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Room Status Pie
    new Chart(document.getElementById('statusChart'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($status_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($status_counts); ?>,
                backgroundColor: ['#10b981', '#f43f5e', '#f59e0b'],
                borderWidth: 0
            }]
        },
        options: commonOptions
    });

    // Collection Doughnut
    new Chart(document.getElementById('collectionChart'), {
        type: 'doughnut',
        data: {
            labels: ['Paid Tenants', 'Unpaid'],
            datasets: [{
                data: [<?php echo $paid_tenants; ?>, <?php echo $unpaid_tenants; ?>],
                backgroundColor: ['#10b981', '#f1f5f9'],
                borderWidth: 0,
                hoverOffset: 4,
                cutout: '75%'
            }]
        },
        options: commonOptions
    });

    // Price Bar Chart
    new Chart(document.getElementById('priceChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($price_labels); ?>,
            datasets: [{
                label: 'Number of Units',
                data: <?php echo json_encode($price_counts); ?>,
                backgroundColor: '#334155',
                borderRadius: 8,
                barThickness: 25
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: { grid: { display: false } },
                x: { grid: { display: false } }
            }
        }
    });

    // Burger menu toggle for mobile
    document.getElementById('toggleSidebar').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });

</script>
</body>
</html>
