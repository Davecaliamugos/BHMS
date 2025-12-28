<?php
session_start();
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tenant_id = $_POST['tenant_id'];
    $method = mysqli_real_escape_string($conn, $_POST['method']);
    $payment_date = date('Y-m-d H:i:s');

    // 1. Get the current rent amount for this tenant's room
    $room_query = "SELECT r.price FROM tenants t JOIN rooms r ON t.room_id = r.room_id WHERE t.tenant_id = '$tenant_id'";
    $room_res = mysqli_query($conn, $room_query);
    $room_data = mysqli_fetch_assoc($room_res);
    $amount = $room_data['price'];

    // 2. Insert the payment record
    // Ensure your payments table has these columns: tenant_id, amount, payment_date, payment_method
    $sql = "INSERT INTO payments (tenant_id, amount, payment_date, payment_method) 
            VALUES ('$tenant_id', '$amount', '$payment_date', '$method')";

    if (mysqli_query($conn, $sql)) {
        // Redirect back to dashboard with success
        header("Location: tenants_dashboard.php?payment=success");
    } else {
        // Redirect back with error
        header("Location: tenants_dashboard.php?payment=error");
    }
}
?>