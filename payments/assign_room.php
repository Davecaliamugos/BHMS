<?php
session_start();
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['tenant_id'])) {
    $tenant_id = $_SESSION['tenant_id'];
    $room_id = mysqli_real_escape_string($conn, $_POST['room_id']);

    // 1. Assign room to tenant
    $q1 = "UPDATE tenants SET room_id = '$room_id' WHERE tenant_id = '$tenant_id'";
    // 2. Mark room as occupied
    $q2 = "UPDATE rooms SET status = 'occupied' WHERE room_id = '$room_id'";

    if (mysqli_query($conn, $q1) && mysqli_query($conn, $q2)) {
        header("Location: tenant_dashboard.php?success=room_assigned");
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}
?>