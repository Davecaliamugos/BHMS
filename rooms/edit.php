<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['room_id'])) {
    $id = $_POST['room_id'];
    $room_number = mysqli_real_escape_string($conn, $_POST['room_number']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Secure Prepared Statement
    $stmt = $conn->prepare("UPDATE rooms SET room_number=?, price=?, status=? WHERE room_id=?");
    $stmt->bind_param("sdsi", $room_number, $price, $status, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Unit $room_number has been updated!";
    } else {
        $_SESSION['error'] = "Update failed: " . $conn->error;
    }

    $stmt->close();
    header("Location: index.php");
    exit();
}