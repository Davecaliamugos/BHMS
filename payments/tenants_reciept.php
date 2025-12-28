<?php
session_start();
// Allow access if EITHER admin or tenant is logged in
if (!isset($_SESSION['admin']) && !isset($_SESSION['tenant_id'])) { 
    header("Location: ../auth/login.php"); 
    exit(); 
}

include("../config/db.php");

if (isset($_GET['id'])) {
    $payment_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Security Fix: If a tenant is logged in, ensure they can ONLY see their own receipt
    $extra_condition = "";
    if (isset($_SESSION['tenant_id'])) {
        $tid = $_SESSION['tenant_id'];
        $extra_condition = " AND payments.tenant_id = '$tid'";
    }

    $query = "SELECT payments.*, tenants.name, rooms.room_number 
              FROM payments 
              JOIN tenants ON payments.tenant_id = tenants.tenant_id 
              LEFT JOIN rooms ON tenants.room_id = rooms.room_id 
              WHERE payments.payment_id = '$payment_id' $extra_condition";
              
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);

    if (!$data) { die("Access Denied or Receipt not found."); }
} else {
    die("Invalid Request");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt_<?php echo $data['payment_id']; ?></title>
    <style>
        /* Your existing styles remain exactly the same */
        body { font-family: 'Courier New', Courier, monospace; padding: 20px; color: #333; background-color: #f9f9f9; }
        .receipt-box { max-width: 400px; margin: auto; border: 1px solid #eee; padding: 30px; background: #fff; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); }
        .header { text-align: center; border-bottom: 2px dashed #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .total { font-weight: bold; font-size: 1.2em; border-top: 2px solid #eee; padding-top: 10px; }
        .footer { text-align: center; margin-top: 30px; font-size: 0.8em; color: #777; }
        
        /* This hides the buttons when printing */
        @media print { .no-print { display: none; } .receipt-box { box-shadow: none; border: none; } body { background: white; } }
    </style>
</head>
<body>

<div class="no-print" style="text-align:center; margin-bottom: 20px;">
    <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #4361ee; color: white; border: none; border-radius: 5px;">
        <i class="fas fa-print"></i> Print Receipt
    </button>
    <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; border: 1px solid #ccc; border-radius: 5px; margin-left: 10px;">
        Close
    </button>
</div>

<div class="receipt-box">
    <div class="header">
        <h2>PROPCORE</h2>
        <p>Official Rental Receipt</p>
    </div>

    <div class="row">
        <span>Receipt No:</span>
        <span>#RCP-<?php echo str_pad($data['payment_id'], 5, '0', STR_PAD_LEFT); ?></span>
    </div>
    <div class="row">
        <span>Date:</span>
        <span><?php echo date("M d, Y", strtotime($data['payment_date'])); ?></span>
    </div>
    <hr>
    <div class="row">
        <span>Tenant:</span>
        <span style="font-weight:bold;"><?php echo strtoupper($data['name']); ?></span>
    </div>
    <div class="row">
        <span>Unit:</span>
        <span>Unit <?php echo $data['room_number']; ?></span>
    </div>
    <div class="row">
        <span>Payment Method:</span>
        <span><?php echo $data['payment_method'] ?? 'Online'; ?></span>
    </div>
    <hr>
    <div class="row total">
        <span>TOTAL PAID:</span>
        <span>₱<?php echo number_format($data['amount'], 2); ?></span>
    </div>

    <div class="footer">
        <p>Thank you for your payment!</p>
        <p>This is a system-generated receipt.</p>
    </div>
</div>

</body>
</html>