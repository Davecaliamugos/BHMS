<?php
session_start();
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    $errors = [];

    // Check if email already exists
    $check_email = $conn->prepare("SELECT email FROM tenants WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();
    if ($check_email->num_rows > 0) {
        $errors[] = "Email is already registered.";
    }
    $check_email->close();

    // Password strength validation
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least 1 capital letter and 1 number.";
    }

    // Confirm password match
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (!empty($errors)) {
        $_SESSION['toast_msg'] = implode(" ", $errors);
        $_SESSION['toast_type'] = "error";
        header("Location: register.php");
        exit();
    }

    // Hash password and proceed
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $time_in = date('Y-m-d');

    // Using Prepared Statements for security
    $stmt = $conn->prepare("INSERT INTO tenants (name, email, password, phone, room_id, time_in) VALUES (?, ?, ?, ?, NULL, ?)");
    $stmt->bind_param("sssss", $name, $email, $hashed_password, $phone, $time_in);

    if ($stmt->execute()) {
        // Set the session message for the Toast
        $_SESSION['toast_msg'] = "Registration Successful! Please login.";
        $_SESSION['toast_type'] = "success";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['toast_msg'] = "Error: " . $conn->error;
        $_SESSION['toast_type'] = "error";
        header("Location: register.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Registration | BHMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                        url('https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover; background-position: center; background-attachment: fixed;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif; padding: 20px;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5); padding: 40px; width: 100%; max-width: 500px; color: white;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.1) !important; border: 1px solid rgba(255, 255, 255, 0.2) !important;
            color: white !important; border-radius: 10px; padding: 12px;
        }
        .form-control::placeholder { color: rgba(255, 255, 255, 0.5); }
        .btn-glass {
            background: #ffffff; color: #764ba2; border: none; font-weight: 700;
            padding: 12px; border-radius: 10px; transition: all 0.3s ease;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-glass:hover { background: #764ba2; color: #ffffff; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="glass-card">
        <div class="text-center mb-4">
            <h2 class="fw-bold mb-1">Create Account</h2>
            <p class="small opacity-75">Join our community today</p>
        </div>



        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small">FULL NAME</label>
                <input type="text" name="name" class="form-control" placeholder="Enter your name" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">EMAIL ADDRESS</label>
                <input type="email" name="email" class="form-control" placeholder="email@example.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">PHONE NUMBER</label>
                <input type="text" name="phone" class="form-control phone-input" placeholder="+63 000 000 0000" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">PASSWORD</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Create a strong password" required>
                <small class="text-warning">Password must contain at least 1 capital letter and 1 number.</small>
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold small">CONFIRM PASSWORD</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm your password" required>
            </div>
            <button type="submit" class="btn btn-glass w-100 mb-3">Register Now</button>
            <div class="text-center">
                <small class="opacity-75">Already a member? <a href="login.php" class="text-white fw-bold text-decoration-none border-bottom">Login</a></small>
            </div>
        </form>
    </div>

    <script>
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

        // Password validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            let errors = [];

            // Check password strength
            if (!/[A-Z]/.test(password) || !/[0-9]/.test(password)) {
                errors.push('Password must contain at least 1 capital letter and 1 number.');
            }

            // Check if passwords match
            if (password !== confirmPassword) {
                errors.push('Passwords do not match.');
            }

            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join(' '));
                return false;
            }
        });
    </script>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="registerToast" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-exclamation-circle me-2"></i>
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
                const toastEl = document.getElementById('registerToast');
                const toastMessage = document.getElementById('toastMessage');
                toastMessage.textContent = '<?php echo $_SESSION['toast_msg']; ?>';
                const toast = new bootstrap.Toast(toastEl);
                toast.show();
                <?php unset($_SESSION['toast_msg'], $_SESSION['toast_type']); ?>
            <?php endif; ?>
        });
    </script>

</body>
</html>
