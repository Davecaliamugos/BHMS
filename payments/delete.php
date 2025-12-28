<?php
session_start();
include("../config/db.php");

if (isset($_POST['delete_payment'])) {
    $payment_id = mysqli_real_escape_string($conn, $_POST['payment_id']);

    $query = "DELETE FROM payments WHERE payment_id = '$payment_id'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Payment record deleted.";
    } else {
        $_SESSION['error'] = "Delete failed.";
    }

    header("Location: index.php");
    exit();
}
?>