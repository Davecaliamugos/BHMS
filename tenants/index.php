<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../auth/login.php");
    exit();
}
include("../config/db.php");

// Stats Queries
$total_tenants = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tenants"))['count'];
$assigned_tenants = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tenants WHERE room_id IS NOT NULL"))['count'];

// Fetch Tenants with Room Details and Time Tracking
$query = "SELECT tenants.*, rooms.room_number 
          FROM tenants 
          LEFT JOIN rooms ON tenants.room_id = rooms.room_id 
          ORDER BY tenants.name ASC";
$result = mysqli_query($conn, $query);

// Fetch AVAILABLE rooms for the "Register" dropdown
$available_rooms_query = mysqli_query($conn, "SELECT room_id, room_number FROM rooms WHERE status = 'available'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Directory | BHMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
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
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        /* Enhanced Cards */
        .card { 
            border: none; 
            border-radius: 16px; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.07); 
            background: var(--glass-bg);
            transition: transform 0.2s ease;
        }

        .stat-card { 
            position: relative;
            overflow: hidden;
            border-left: 6px solid #6366f1; 
        }

        .stat-card::after {
            content: '';
            position: absolute;
            right: -20px;
            bottom: -20px;
            width: 100px;
            height: 100px;
            background: rgba(99, 102, 241, 0.05);
            border-radius: 50%;
        }

        /* Table Styling */
        .table thead th {
            background-color: #f8fafc;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #64748b;
            border-top: none;
            padding: 1.25rem 1rem;
        }

        .avatar-circle { 
            width: 40px; 
            height: 40px; 
            background: var(--primary-gradient); 
            color: white; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            border-radius: 12px; 
            font-weight: 600;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3);
        }

        /* Form Controls */
        .form-control, .form-select {
            padding: 0.6rem 1rem;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        /* Badges */

        /* Buttons */
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.39);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.23);
        }

        .action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            margin: 0 2px;
            transition: all 0.2s;
        }

        .btn-edit-light { background: #eef2ff; color: #4f46e5; }
        .btn-edit-light:hover { background: #4f46e5; color: white; }
        
        .btn-delete-light { background: #fff1f2; color: #e11d48; }
        .btn-delete-light:hover { background: #e11d48; color: white; }

        /* DataTables Customization */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 10px;
            padding: 8px 15px;
            width: 250px;
        }
        
        .dt-buttons { display: none; }
    </style>
</head>
<body>
    <div class="d-flex w-100">
        <?php include("../includes/sidebar.php"); ?>

        <main class="content-wrapper p-4 p-lg-5">
            <div class="d-md-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold tracking-tight">Tenants Directory</h2>
                    <p class="text-muted mb-0">Manage resident information and room assignments.</p>
                </div>
                <div class="d-flex gap-3 mt-3 mt-md-0">
                    <button class="btn btn-white border px-4 py-2 rounded-3 fw-semibold shadow-sm bg-white" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="fas fa-file-export me-2 text-muted"></i> Export
                    </button>
                    <button class="btn btn-primary px-4 py-2 rounded-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#addTenantModal">
                        <i class="fas fa-plus me-2"></i> Register Tenant
                    </button>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="card stat-card p-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted fw-bold text-uppercase">Total Residents</small>
                                <h2 class="fw-bold mb-0 mt-1"><?php echo $total_tenants; ?></h2>
                            </div>
                            <div class="avatar-circle" style="width: 50px; height: 50px; font-size: 1.2rem;">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card stat-card p-4" style="border-left-color: #10b981;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted fw-bold text-uppercase">Assigned to Units</small>
                                <h2 class="fw-bold mb-0 mt-1 text-success"><?php echo $assigned_tenants; ?></h2>
                            </div>
                            <div class="avatar-circle bg-success bg-gradient border-0" style="width: 50px; height: 50px; font-size: 1.2rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                <i class="fas fa-door-open"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 p-2">
                <div class="table-responsive p-3">
                    <table id="tenantsTable" class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tenant Details</th>
                                <th>Contact Information</th>
                                <th>Unit Assignment</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php mysqli_data_seek($result, 0);
                            while ($row = mysqli_fetch_assoc($result)):
                            ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-3"><?php echo strtoupper(substr($row['name'], 0, 1)); ?></div>
                                            <div>
                                                <div class="fw-bold mb-0"><?php echo htmlspecialchars($row['name']); ?></div>
                                                <small class="text-muted">ID: #TN-<?php echo $row['tenant_id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span><i class="fas fa-phone-alt me-2 text-muted small"></i><?php echo htmlspecialchars($row['phone']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($row['room_number']): ?>
                                            <span class="badge bg-indigo-subtle text-primary px-3 py-2 rounded-pill" style="background: #eef2ff;">Unit <?php echo $row['room_number']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted px-3 py-2 rounded-pill">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn action-btn btn-edit-light edit-tenant-btn" 
                                                data-id="<?php echo $row['tenant_id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                                data-phone="<?php echo htmlspecialchars($row['phone']); ?>" 
                                                data-room="<?php echo $row['room_id']; ?>"
                                                data-timein="<?php echo $row['time_in']; ?>"
                                                data-timeout="<?php echo $row['time_out']; ?>">
                                            <i class="fas fa-pen small"></i>
                                        </button>
                                        <button class="btn action-btn btn-delete-light delete-btn" data-id="<?php echo $row['tenant_id']; ?>">
                                            <i class="fas fa-trash-alt small"></i>
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

    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h6 class="fw-bold mb-0">Choose Export Format</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-success py-2 rounded-3 text-start" onclick="triggerExport('excel')">
                            <i class="fas fa-file-excel me-2"></i> Excel Spreadsheet
                        </button>
                        <button type="button" class="btn btn-outline-danger py-2 rounded-3 text-start" onclick="triggerExport('pdf')">
                            <i class="fas fa-file-pdf me-2"></i> PDF Document
                        </button>
                        <button type="button" class="btn btn-outline-dark py-2 rounded-3 text-start" onclick="triggerExport('print')">
                            <i class="fas fa-print me-2"></i> Print View
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addTenantModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="process_tenant.php" method="POST">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bold">Register New Tenant</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" name="name" class="form-control rounded-3" placeholder="full name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Phone Number</label>
                            <input type="text" name="phone" class="form-control rounded-3 phone-input" placeholder="+63 000 000 0000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Assign Room</label>
                            <select name="room_id" class="form-select rounded-3">
                                <option value="">No Room / Waitlist</option>
                                <?php 
                                mysqli_data_seek($available_rooms_query, 0);
                                while ($room = mysqli_fetch_assoc($available_rooms_query)): ?>
                                    <option value="<?php echo $room['room_id']; ?>">Unit <?php echo $room['room_number']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" name="add_tenant" class="btn btn-primary w-100 rounded-3 py-2 fw-bold">Confirm Registration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editTenantModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <form action="edit.php" method="POST">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="fw-bold">Update Tenant Information</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="tenant_id" id="edit_tenant_id">
                        <input type="hidden" name="old_room_id" id="old_room_id">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control rounded-3" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Phone Number</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control rounded-3 phone-input" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Time In</label>
                                <input type="datetime-local" name="time_in" id="edit_time_in" class="form-control rounded-3">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold">Time Out</label>
                                <input type="datetime-local" name="time_out" id="edit_time_out" class="form-control rounded-3">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Unit Assignment</label>
                            <select name="room_id" id="edit_room_id" class="form-select rounded-3">
                                <option value="">No Room / Move Out</option>
                                <?php 
                                $all_rooms_query = mysqli_query($conn, "SELECT room_id, room_number, status FROM rooms");
                                while ($room = mysqli_fetch_assoc($all_rooms_query)): ?>
                                    <option value="<?php echo $room['room_id']; ?>">
                                        Unit <?php echo $room['room_number']; ?> (<?php echo ucfirst($room['status']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_tenant" class="btn btn-primary px-4 rounded-3 fw-bold">Update Records</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-body text-center p-4">
                    <div class="mb-3 text-danger">
                        <i class="fas fa-exclamation-circle display-4"></i>
                    </div>
                    <h5 class="fw-bold">Remove Tenant?</h5>
                    <p class="text-muted small">This will unassign them and permanently delete records. This action cannot be undone.</p>
                    <div class="d-grid gap-2 mt-4">
                        <a id="confirmDelete" href="#" class="btn btn-danger py-2 rounded-3 fw-bold">Confirm Delete</a>
                        <button type="button" class="btn btn-light py-2 rounded-3" data-bs-dismiss="modal">Keep Tenant</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

    <script>
        var table;
        $(document).ready(function () {
            table = $('#tenantsTable').DataTable({
                "pageLength": 10,
                "dom": '<"d-flex justify-content-end mb-3"f>Brtip',
                "buttons": [
                    { extend: 'excelHtml5', title: 'PropCore Tenant Directory', exportOptions: { columns: [0, 1, 2] } },
                    { extend: 'pdfHtml5', title: 'PropCore Tenant Directory', exportOptions: { columns: [0, 1, 2] } },
                    { extend: 'print', exportOptions: { columns: [0, 1, 2] } }
                ],
                "language": { 
                    "search": "", 
                    "searchPlaceholder": "Search residents..." 
                }
            });

            $(document).on('click', '.edit-tenant-btn', function() {
                $('#edit_tenant_id').val($(this).data('id'));
                $('#edit_name').val($(this).data('name'));
                $('#edit_phone').val($(this).data('phone'));
                let roomId = $(this).data('room');
                $('#edit_room_id').val(roomId ? roomId : "");
                $('#old_room_id').val(roomId ? roomId : "");
                let tOut = $(this).data('timeout');
                if(tOut) $('#edit_time_out').val(tOut.replace(' ', 'T').substring(0, 16));
                new bootstrap.Modal('#editTenantModal').show();
            });

            $(document).on('click', '.delete-btn', function() {
                $('#confirmDelete').attr('href', 'delete.php?id=' + $(this).data('id'));
                new bootstrap.Modal('#deleteModal').show();
            });
        });

        function triggerExport(type) {
            if(type === 'excel') table.button(0).trigger();
            if(type === 'pdf') table.button(1).trigger();
            if(type === 'print') table.button(2).trigger();
            bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
        }

        // Phone formatting logic
        document.querySelectorAll('.phone-input').forEach(input => {
            input.addEventListener('input', function (e) {
                let x = e.target.value.replace(/\D/g, '');
                if (x.length > 0 && !x.startsWith('63')) x = '63' + x;
                x = x.substring(0, 12);
                let formatted = '';
                if (x.length > 0) formatted += '+' + x.substring(0, 2);
                if (x.length > 2) formatted += ' ' + x.substring(2, 5);
                if (x.length > 5) formatted += ' ' + x.substring(5, 8);
                if (x.length > 8) formatted += ' ' + x.substring(8, 12);
                e.target.value = formatted;
            });
        });
    </script>
</body>
</html>