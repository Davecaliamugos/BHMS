<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

include("../config/db.php");

// Fetch available rooms
$rooms = mysqli_query($conn, "SELECT * FROM rooms");

if (isset($_POST['save'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $room_id = $_POST['room_id'];

    $query = "INSERT INTO tenants (name, phone, room_id)
              VALUES ('$name', '$phone', '$room_id')";

    mysqli_query($conn, $query);

    // Update room status to Occupied
    mysqli_query($conn, "UPDATE rooms SET status='Occupied' WHERE room_id=$room_id");

    header("Location: index.php");
}
?>
