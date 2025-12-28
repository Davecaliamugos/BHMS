<?php
session_start();
include("../config/db.php");

if (isset($_POST['add_tenant'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $room_id = !empty($_POST['room_id']) ? $_POST['room_id'] : 'NULL';
    $time_in = !empty($_POST['time_in']) ? $_POST['time_in'] : date('Y-m-d H:i:s');

    // Insert Tenant with Time In
    $query = "INSERT INTO tenants (name, phone, room_id, time_in) 
              VALUES ('$name', '$phone', $room_id, '$time_in')";

    if (mysqli_query($conn, $query)) {
        // If a room was assigned, update room status to 'occupied'
        if ($room_id !== 'NULL') {
            mysqli_query($conn, "UPDATE rooms SET status = 'occupied' WHERE room_id = $room_id");
        }
        $_SESSION['success'] = "Tenant registered successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header("Location: index.php");
    exit();
}
?>