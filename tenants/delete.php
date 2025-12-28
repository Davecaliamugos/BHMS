<?php
session_start();
include("../config/db.php");

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // Get the room_id before deleting the tenant
    $result = mysqli_query($conn, "SELECT room_id FROM tenants WHERE tenant_id = $id");
    $row = mysqli_fetch_assoc($result);
    $room_id = $row['room_id'];

    // Start transaction to ensure data integrity
    mysqli_begin_transaction($conn);

    try {
        // Delete all payments associated with this tenant first
        $delete_payments = mysqli_query($conn, "DELETE FROM payments WHERE tenant_id = $id");
        if (!$delete_payments) {
            throw new Exception("Failed to delete payments: " . mysqli_error($conn));
        }

        // Delete the tenant
        $delete_tenant = mysqli_query($conn, "DELETE FROM tenants WHERE tenant_id = $id");
        if (!$delete_tenant) {
            throw new Exception("Failed to delete tenant: " . mysqli_error($conn));
        }

        // If they had a room, make it available again
        if ($room_id) {
            $update_room = mysqli_query($conn, "UPDATE rooms SET status = 'available' WHERE room_id = $room_id");
            if (!$update_room) {
                throw new Exception("Failed to update room status: " . mysqli_error($conn));
            }
        }

        // Commit transaction
        mysqli_commit($conn);
        $_SESSION['success'] = "Tenant removed and room is now available.";

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error removing tenant: " . $e->getMessage();
    }
}
header("Location: index.php");
exit();