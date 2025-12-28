<?php
session_start();
include("../config/db.php");

if (isset($_POST['update_payment'])) {
    $payment_id = mysqli_real_escape_string($conn, $_POST['payment_id']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $payment_date = mysqli_real_escape_string($conn, $_POST['payment_date']);

    if (!empty($payment_id)) {
        $query = "UPDATE payments SET amount = '$amount', payment_date = '$payment_date' WHERE payment_id = '$payment_id'";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Payment record updated!";
        } else {
            $_SESSION['error'] = "Update failed: " . mysqli_error($conn);
        }
    }

    header("Location: index.php");
    exit();
}
?>