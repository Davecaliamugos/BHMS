<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../auth/login.php");
    exit();
}
include("../config/db.php");

// Fetch counts for Stats Cards
$total_rooms = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM rooms"))['count'] ?? 0;
$available_rooms = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM rooms WHERE status='available'"))['count'] ?? 0;
$occupied_rooms = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM rooms WHERE status='occupied'"))['count'] ?? 0;

$result = mysqli_query($conn, "SELECT * FROM rooms ORDER BY room_number ASC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management | BHMS</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
            --glass-bg: rgba(255, 255, 255, 0.9);
        }

        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            color: #1e293b;
        }

        .content-wrapper {
            flex-grow: 1;
            height: 100vh;
            overflow-y: auto;
            background: radial-gradient(circle at top right, #f8fafc, #e2e8f0);
        }

        /* Enhanced Cards */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.07);
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
        }

        .stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 6px solid #0d6efd;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            right: -15px;
            bottom: -15px;
            width: 80px;
            height: 80px;
            background: rgba(13, 110, 253, 0.05);
            border-radius: 50%;
        }

        /* Table Styling */
        .table thead th {
            background-color: #f8fafc;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #64748b;
            padding: 1.25rem 1rem;
            border: none;
        }

        .status-badge {
            font-size: 0.7rem;
            font-weight: 800;
            padding: 6px 14px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        /* Modern Buttons */
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            box-shadow: 0 4px 14px 0 rgba(13, 110, 253, 0.35);
        }

        .action-btn {
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
            background: white;
        }

        .btn-edit-hover:hover {
            background: #e0e7ff;
            border-color: #4f46e5;
        }

        .btn-delete-hover:hover {
            background: #fee2e2;
            border-color: #ef4444;
        }

        .btn-maintenance-hover:hover {
            background: #fef3c7;
            border-color: #f59e0b;
        }

        /* Custom scrollbar */
        .content-wrapper::-webkit-scrollbar {
            width: 6px;
        }

        .content-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <div class="d-flex w-100">
        <?php include("../includes/sidebar.php"); ?>

        <main class="content-wrapper p-4 p-md-5">
            <div class="d-md-none d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0 text-primary" style="font-family: 'Montserrat';">BHMS</h5>
                <button class="btn btn-dark btn-sm rounded-3" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            </div>

            <div class="d-md-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold tracking-tight">Room Inventory</h2>
                    <p class="text-muted mb-0">Manage your units, pricing, and availability status.</p>
                </div>
                <button class="btn btn-primary px-4 py-2 rounded-3 fw-bold mt-3 mt-md-0" data-bs-toggle="modal"
                    data-bs-target="#addRoomModal">
                    <i class="fas fa-plus-circle me-2"></i> Add New Unit
                </button>
            </div>



            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card stat-card p-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold">TOTAL UNITS</small>
                                <h2 class="fw-bold mb-0 mt-1"><?php echo $total_rooms; ?></h2>
                            </div>
                            <i class="fas fa-building fs-1 text-primary opacity-25"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-4 shadow-sm" style="border-left-color: #198754;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold text-success">AVAILABLE</small>
                                <h2 class="fw-bold mb-0 mt-1 text-success"><?php echo $available_rooms; ?></h2>
                            </div>
                            <i class="fas fa-door-open fs-1 text-success opacity-25"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-4 shadow-sm" style="border-left-color: #dc3545;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold text-danger">OCCUPIED</small>
                                <h2 class="fw-bold mb-0 mt-1 text-danger"><?php echo $occupied_rooms; ?></h2>
                            </div>
                            <i class="fas fa-user-check fs-1 text-danger opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-2">
                <div class="table-responsive p-3">
                    <table id="roomsTable" class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ROOM NUMBER</th>
                                <th>MONTHLY PRICE</th>
                                <th>STATUS</th>
                                <th class="text-end pe-4">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)):
                                $status = strtolower($row['status']);
                                $badge = ($status == 'available') ? "bg-success-subtle text-success border border-success-subtle" : (($status == 'occupied') ? "bg-danger-subtle text-danger border border-danger-subtle" : "bg-warning-subtle text-warning border border-warning-subtle");
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-3 p-2 me-3 text-primary">
                                                <i class="fas fa-door-closed"></i>
                                            </div>
                                            <span class="fw-bold text-dark">Unit
                                                <?php echo htmlspecialchars($row['room_number']); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-dark fw-medium">₱<?php echo number_format($row['price'], 2); ?></td>
                                    <td><span
                                            class="status-badge <?php echo $badge; ?>"><?php echo ucfirst($status); ?></span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button class="action-btn btn-edit-hover me-1 edit-btn"
                                            data-id="<?php echo $row['room_id']; ?>"
                                            data-num="<?php echo $row['room_number']; ?>"
                                            data-price="<?php echo $row['price']; ?>"
                                            data-status="<?php echo $row['status']; ?>">
                                            <i class="fas fa-edit text-primary"></i>
                                        </button>
                                        <button class="action-btn btn-maintenance-hover maintenance-btn"
                                            data-id="<?php echo $row['room_id']; ?>">
                                            <i class="fas fa-tools text-warning"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div class="modal fade" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="add.php" method="POST">
                    <div class="modal-header border-0 p-4 pb-0">
                        <h5 class="fw-bold">Add New Room</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 rounded-3 mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <small class="fw-medium">Room number will be auto-generated (101-110, then 111+)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Price (PHP)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">₱</span>
                                <input type="number" name="price" class="form-control rounded-3 bg-light border-start-0"
                                    step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Initial Status</label>
                            <select name="status" class="form-select rounded-3 bg-light">
                                <option value="available">Available</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-4"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save" class="btn btn-primary px-4 rounded-3 fw-bold">Save
                            Unit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editRoomModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="edit.php" method="POST">
                    <div class="modal-header border-0 p-4 pb-0">
                        <h5 class="fw-bold text-primary">Edit Room Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="room_id" id="edit_room_id">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Room Number</label>
                            <input type="text" name="room_number" id="edit_room_number"
                                class="form-control rounded-3 bg-light" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Price (PHP)</label>
                            <input type="number" name="price" id="edit_price" class="form-control rounded-3 bg-light"
                                step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="status" id="edit_status" class="form-select rounded-3 bg-light">
                                <option value="available">Available</option>
                                <option value="occupied">Occupied</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light rounded-3 px-4"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update" class="btn btn-primary px-4 rounded-3 fw-bold">Update
                            Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="maintenanceModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-body text-center p-4">
                    <div class="bg-warning-subtle text-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 70px; height: 70px;">
                        <i class="fas fa-tools fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Set to Maintenance?</h5>
                    <p class="text-muted small">This will mark the room as under maintenance. The room must be empty to proceed.</p>
                    <div class="d-grid gap-2 mt-4">
                        <a id="confirmMaintenance" href="#" class="btn btn-warning py-2 rounded-3 fw-bold">Set to Maintenance</a>
                        <button type="button" class="btn btn-light py-2 rounded-3"
                            data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#roomsTable').DataTable({
                "pageLength": 10,
                "language": { "search": "", "searchPlaceholder": "Search rooms..." }
            });

            $('#sidebarToggle').click(function () { $('.sidebar').toggleClass('active'); });

            $('.edit-btn').click(function () {
                const id = $(this).data('id');
                const num = $(this).data('num');
                const price = $(this).data('price');
                const status = $(this).data('status');
                $('#edit_room_id').val(id);
                $('#edit_room_number').val(num);
                $('#edit_price').val(price);
                $('#edit_status').val(status);
                new bootstrap.Modal('#editRoomModal').show();
            });

            $('.maintenance-btn').click(function () {
                const id = $(this).data('id');
                $('#confirmMaintenance').attr('href', 'maintenance.php?id=' + id);
                new bootstrap.Modal('#maintenanceModal').show();
            });

            $('.delete-btn').click(function () {
                const id = $(this).data('id');
                $('#confirmDelete').attr('href', 'delete.php?id=' + id);
                new bootstrap.Modal('#deleteModal').show();
            });
        });
    </script>
</body>

</html>