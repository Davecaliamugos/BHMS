<?php
session_start();
include("../config/db.php");

if (isset($_POST['update_tenant'])) {
    // 1. Sanitize Inputs
    $tenant_id = mysqli_real_escape_string($conn, $_POST['tenant_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    // 2. Handle Room IDs (Old vs New)
    $old_room_id = !empty($_POST['old_room_id']) ? mysqli_real_escape_string($conn, $_POST['old_room_id']) : null;
    $new_room_id = !empty($_POST['room_id']) ? mysqli_real_escape_string($conn, $_POST['room_id']) : null;
    
    // 3. Handle Time Out (If empty, set to NULL)
    $time_out_raw = $_POST['time_out'];
    $time_out_sql = !empty($time_out_raw) ? "'" . mysqli_real_escape_string($conn, $time_out_raw) . "'" : "NULL";

    // 4. Logic: If a Time Out is set, the tenant is checking out (Unassign room)
    if ($time_out_sql !== "NULL") {
        $final_room_id = "NULL";
    } else {
        $final_room_id = ($new_room_id) ? $new_room_id : "NULL";
    }

    // 5. Update Tenant Record
    $update_query = "UPDATE tenants SET
                     name = '$name',
                     phone = '$phone',
                     room_id = $final_room_id,
                     time_out = $time_out_sql
                     WHERE tenant_id = $tenant_id";

    if (mysqli_query($conn, $update_query)) {
        
        // --- ROOM STATUS UPDATER ---
        
        // If the tenant moved to a different room
        if ($new_room_id != $old_room_id) {
            // Set the old room back to 'available'
            if ($old_room_id) {
                mysqli_query($conn, "UPDATE rooms SET status = 'available' WHERE room_id = $old_room_id");
            }
            // Set the new room to 'occupied' (unless they just checked out)
            if ($new_room_id && $time_out_sql === "NULL") {
                mysqli_query($conn, "UPDATE rooms SET status = 'occupied' WHERE room_id = $new_room_id");
            }
        }
        
        // Special Case: If they stay in the same room but added a 'Time Out' date
        if ($time_out_sql !== "NULL" && $old_room_id == $new_room_id && $new_room_id != null) {
            mysqli_query($conn, "UPDATE rooms SET status = 'available' WHERE room_id = $new_room_id");
        }

        $_SESSION['success'] = "Tenant updated successfully!";
    } else {
        $_SESSION['error'] = "Update failed: " . mysqli_error($conn);
    }

    header("Location: index.php");
    exit();
}
?>