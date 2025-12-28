<?php
session_start();
include("../config/db.php");

if (isset($_POST['save_payment'])) {
    // Sanitize inputs
    $tenant_id = mysqli_real_escape_string($conn, $_POST['tenant_id']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $payment_date = mysqli_real_escape_string($conn, $_POST['payment_date']);

    // Basic Validation
    if (!empty($tenant_id) && !empty($amount) && !empty($payment_date)) {
        $query = "INSERT INTO payments (tenant_id, amount, payment_date) VALUES ('$tenant_id', '$amount', '$payment_date')";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Payment recorded successfully!";
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "All fields are required.";
    }

    // Redirect back to billing index
    header("Location: index.php");
    exit();
}
?>