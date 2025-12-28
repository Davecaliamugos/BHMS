<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Auto-generate room number
    $result = mysqli_query($conn, "SELECT MAX(CAST(room_number AS UNSIGNED)) as max_room FROM rooms");
    $row = mysqli_fetch_assoc($result);
    $max_room = $row['max_room'] ?? 0;

    // Generate next room number following the pattern: 101-110, then 111+
    if ($max_room < 101) {
        $room_number = 101;
    } elseif ($max_room < 110) {
        $room_number = $max_room + 1;
    } else {
        $room_number = $max_room + 1;
    }

    // Using a Prepared Statement for Security
    $stmt = $conn->prepare("INSERT INTO rooms (room_number, price, status) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $room_number, $price, $status);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Room $room_number added successfully!";
    } else {
        $_SESSION['error'] = "Error adding room: " . $conn->error;
    }

    $stmt->close();
    header("Location: index.php");
    exit();
}
