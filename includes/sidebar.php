<aside class="sidebar flex-column p-4 text-white d-flex shadow">
    <div class="d-md-none text-end mb-2">
        <button class="btn border-0 text-white p-0 opacity-75 hover-opacity-100" id="closeSidebar">
            <i class="fas fa-times fa-lg"></i>
        </button>
    </div>

    <div class="d-flex align-items-center mb-5 ps-2">
        <div class="brand-icon-box me-3">
            <i class="fas fa-city text-white"></i>
        </div>
        <h4 class="mb-0 fw-bold tracking-tight">BH<span class="text-info">MS</span></h4>
    </div>
    
    <nav class="nav flex-column mb-auto">
        <?php 
        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
        ?>

        <a href="../dashboard/index.php" class="nav-link mb-3 <?php echo ($current_dir == 'dashboard') ? 'active' : ''; ?>">
            <div class="nav-icon-wrapper"><i class="fas fa-th-large"></i></div>
            <span>Dashboard</span>
        </a>
        
        <a href="../rooms/index.php" class="nav-link mb-3 <?php echo ($current_dir == 'rooms') ? 'active' : ''; ?>">
            <div class="nav-icon-wrapper"><i class="fas fa-door-closed"></i></div>
            <span>Rooms</span>
        </a>
        
        <a href="../tenants/index.php" class="nav-link mb-3 <?php echo ($current_dir == 'tenants') ? 'active' : ''; ?>">
            <div class="nav-icon-wrapper"><i class="fas fa-user-tag"></i></div>
            <span>Tenants</span>
        </a>
        
        <a href="../payments/index.php" class="nav-link mb-3 <?php echo ($current_dir == 'billing' || $current_dir == 'payments') ? 'active' : ''; ?>">
            <div class="nav-icon-wrapper"><i class="fas fa-receipt"></i></div>
            <span>Billing</span>
        </a>
    </nav>

    <div class="mt-auto border-top border-white-10 pt-4">
        <div class="user-mini-profile mb-4 d-flex align-items-center px-2">
            <div class="avatar-circle me-3">A</div>
            <div class="overflow-hidden">
                <p class="small fw-bold mb-0 text-truncate">Administrator</p>
                <p class="x-small text-white-50 mb-0">bhms.zap@zap.com</p>
            </div>
        </div>
        <a href="#" class="nav-link logout-link px-3 py-2" data-bs-toggle="modal" data-bs-target="#logoutModal">
            <i class="fas fa-sign-out-alt me-3 text-danger-emphasis"></i>
            <span class="fw-medium">Sign Out</span>
        </a>
    </div>
</aside>

<style>
/* Sidebar structural CSS */
.sidebar {
    width: 280px;
    min-width: 280px;
    height: 100vh;
    background: linear-gradient(185deg, #0f172a 0%, #1e3a8a 100%);
    position: sticky;
    top: 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border-right: 1px solid rgba(255, 255, 255, 0.05);
}

/* Brand styling */
.brand-icon-box {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    padding: 10px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.tracking-tight { letter-spacing: -0.5px; }

/* Enhanced Nav Links */
.nav-link {
    color: rgba(255, 255, 255, 0.6);
    border-radius: 14px;
    padding: 12px 16px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    font-size: 0.95rem;
    font-weight: 500;
    border: 1px solid transparent;
}

.nav-icon-wrapper {
    width: 24px;
    margin-right: 12px;
    display: flex;
    justify-content: center;
    font-size: 1.1rem;
    transition: transform 0.3s ease;
}

.nav-link:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #fff !important;
    transform: translateX(5px);
}

.nav-link:hover .nav-icon-wrapper {
    transform: scale(1.1);
}

.nav-link.active {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa !important;
    border: 1px solid rgba(59, 130, 246, 0.2);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.nav-link.active .nav-icon-wrapper {
    color: #60a5fa;
}

/* Footer Section */
.border-white-10 { border-color: rgba(255, 255, 255, 0.1) !important; }

.avatar-circle {
    width: 38px;
    height: 38px;
    background: #334155;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #94a3b8;
    border: 2px solid rgba(255, 255, 255, 0.05);
}

.x-small { font-size: 0.75rem; }

.logout-link:hover {
    background: rgba(244, 63, 94, 0.1);
}

/* Modal Styling */
.modal-content {
    backdrop-filter: blur(10px);
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .sidebar {
        position: fixed;
        left: -280px;
        z-index: 2000;
    }
    .sidebar.active {
        left: 0;
        box-shadow: 20px 0 50px rgba(0,0,0,0.5);
    }
}
</style>

<script>
    document.getElementById('closeSidebar')?.addEventListener('click', function() {
        document.querySelector('.sidebar').classList.remove('active');
    });
</script>

<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: #fff;">
            <div class="modal-body text-center p-5">
                <div class="mb-4">
                    <i class="fas fa-exclamation-circle text-danger" style="font-size: 4rem; opacity: 0.2;"></i>
                </div>
                <h4 class="fw-bold mb-3 text-dark">Confirm Sign Out</h4>
                <p class="text-muted mb-4">Are you sure you want to end your session? You will need to log back in to manage the system.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <a href="../auth/logout.php" class="btn btn-danger rounded-pill px-4 fw-semibold shadow-sm">Sign Out</a>
                </div>
            </div>
        </div>
    </div>
</div>
