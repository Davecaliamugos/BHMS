<!-- Toast Notification System -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div id="globalToast" class="toast align-items-center text-white border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center">
                <i id="toastIcon" class="fas me-3 fs-5"></i>
                <span id="toastMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<style>
.toast-container {
    max-width: 400px;
}

.toast {
    border-radius: 12px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.toast.success {
    background: linear-gradient(135deg, #10b981, #059669);
}

.toast.error {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.toast.warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.toast.info {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}
</style>

<script>
function showToast(message, type = 'success', duration = 5000) {
    const toast = document.getElementById('globalToast');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = document.getElementById('toastIcon');

    // Set message
    toastMessage.textContent = message;

    // Set icon and type
    toast.className = 'toast align-items-center text-white border-0 shadow-lg';
    switch(type) {
        case 'success':
            toast.classList.add('success');
            toastIcon.className = 'fas fa-check-circle me-3 fs-5';
            break;
        case 'error':
            toast.classList.add('error');
            toastIcon.className = 'fas fa-exclamation-circle me-3 fs-5';
            break;
        case 'warning':
            toast.classList.add('warning');
            toastIcon.className = 'fas fa-exclamation-triangle me-3 fs-5';
            break;
        case 'info':
            toast.classList.add('info');
            toastIcon.className = 'fas fa-info-circle me-3 fs-5';
            break;
    }

    // Show toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: duration
    });
    bsToast.show();
}

// Check for session messages on page load
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['success'])): ?>
        showToast("<?php echo addslashes($_SESSION['success']); ?>", 'success');
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        showToast("<?php echo addslashes($_SESSION['error']); ?>", 'error');
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <?php
        $errorMessage = '';
        if ($_GET['error'] == 'cannot_delete_occupied') {
            $errorMessage = 'This room has active tenants. You must remove or move the tenants before deleting the unit.';
        } elseif ($_GET['error'] == 'cannot_maintenance_occupied') {
            $errorMessage = 'This room has active tenants. You must remove or move the tenants before setting it to maintenance.';
        } else {
            $errorMessage = 'An error occurred while trying to perform the action.';
        }
        ?>
        showToast("<?php echo addslashes($errorMessage); ?>", 'error');
    <?php endif; ?>
});
</script>
