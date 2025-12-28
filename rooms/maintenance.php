<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

include("../config/db.php");

// 1. Get the ID safely
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // 2. Check if there are any tenants assigned to this room
    $stmt_check = $conn->prepare("SELECT tenant_id FROM tenants WHERE room_id = ?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        // Room is occupied! Redirect with an error message
        header("Location: index.php?error=cannot_maintenance_occupied");
        exit();
    } else {
        // 3. Room is empty, set to maintenance using a Prepared Statement
        $stmt_update = $conn->prepare("UPDATE rooms SET status = 'maintenance' WHERE room_id = ?");
        $stmt_update->bind_param("i", $id);

        if ($stmt_update->execute()) {
            header("Location: index.php?success=maintenance");
        } else {
            header("Location: index.php?error=maintenance_failed");
        }
    }
} else {
    header("Location: index.php");
}
exit();
