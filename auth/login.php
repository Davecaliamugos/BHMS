<?php
session_start();
include("../config/db.php");

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // 1. Check Admin Table First
    $admin_query = "SELECT * FROM admin WHERE username='$username'";
    $admin_result = mysqli_query($conn, $admin_query);

    if (mysqli_num_rows($admin_result) == 1) {
        $admin_data = mysqli_fetch_assoc($admin_result);
        if (password_verify($password, $admin_data['password']) || $password == $admin_data['password']) {
            $_SESSION['admin'] = $username;
            header("Location: ../dashboard/index.php");
            exit();
        }
    }

    // 2. Check Tenant Table if Admin fails
    $tenant_query = "SELECT * FROM tenants WHERE email='$username' OR name='$username'";
    $tenant_result = mysqli_query($conn, $tenant_query);

    if (mysqli_num_rows($tenant_result) == 1) {
        $tenant_data = mysqli_fetch_assoc($tenant_result);
        if (password_verify($password, $tenant_data['password'])) {
            $_SESSION['tenant_id'] = $tenant_data['tenant_id'];
            $_SESSION['tenant_name'] = $tenant_data['name'];
            header("Location: ../dashboard/tenants_dashboard.php");
            exit();
        }
    }

    $_SESSION['toast_msg'] = "Invalid credentials. Please try again.";
    $_SESSION['toast_type'] = "error";
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | BHMS </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                        url('https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding: 20px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            color: white;
            position: relative; /* Needed for absolute positioning of the home link */
        }

        /* Style for the Landing Page Anchor */
        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .back-home:hover {
            color: white;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            border-radius: 10px;
            padding: 12px;
        }

        .form-control::placeholder { color: rgba(255, 255, 255, 0.5); }

        .btn-glass {
            background: #ffffff;
            color: #764ba2;
            border: none;
            font-weight: 700;
            padding: 12px;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        .btn-glass:hover {
            background: #764ba2;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

<div class="glass-card text-center">
    <a href="../index.php" class="back-home">
        <i class="fas fa-arrow-left me-1"></i> Home
    </a>

    <h2 class="fw-bold mb-1 mt-2">BHMS</h2>
    <p class="small opacity-75 mb-4">Secure Portal Login</p>



    <form method="POST">
        <div class="mb-3 text-start">
            <label class="form-label small fw-bold opacity-75">USERNAME OR EMAIL</label>
            <input type="text" name="username" class="form-control" placeholder="Enter your credentials" required>
        </div>

        <div class="mb-4 text-start">
            <label class="form-label small fw-bold opacity-75">PASSWORD</label>
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>

        <button type="submit" name="login" class="btn btn-glass w-100">Login</button>
        
        <div class="mt-4">
            <small class="opacity-75">New tenant? <a href="register.php" class="text-white fw-bold text-decoration-none border-bottom">Register here</a></small>
        </div>
    </form>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="loginToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i id="toastIcon" class="fas fa-exclamation-circle me-2"></i>
                <span id="toastMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_SESSION['toast_msg'])): ?>
            const toastEl = document.getElementById('loginToast');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = document.getElementById('toastIcon');
            toastMessage.textContent = '<?php echo $_SESSION['toast_msg']; ?>';
            <?php if ($_SESSION['toast_type'] == 'success'): ?>
                toastEl.classList.add('bg-success');
                toastIcon.className = 'fas fa-check-circle me-2';
            <?php else: ?>
                toastEl.classList.add('bg-danger');
                toastIcon.className = 'fas fa-exclamation-circle me-2';
            <?php endif; ?>
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
            <?php unset($_SESSION['toast_msg'], $_SESSION['toast_type']); ?>
        <?php endif; ?>
    });
</script>

</body>
</html>
