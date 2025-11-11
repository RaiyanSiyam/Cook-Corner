<?php
// mark_order_received.php
// This script updates an order's status from "Shipped" to "Completed"
// and also updates the payment status to "Paid".

session_start();
require_once __DIR__ . '/db.php';

// 1. Ensure user is logged in and the request is a POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if ($order_id === 0) {
    header('Location: my_orders.php');
    exit;
}

try {
    // 2. Security Check: Update the order and payment status ONLY if the order belongs 
    //    to the current user and its current status is 'Shipped'.
    $sql = "UPDATE orders SET order_status = 'Completed', payment_status = 'Paid' 
            WHERE id = ? AND user_id = ? AND order_status = 'Shipped'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id, $user_id]);

    // 3. Redirect back to the orders page with a success message
    header('Location: my_orders.php?success=1');
    exit;

} catch (PDOException $e) {
    error_log("Error updating order status: " . $e->getMessage());
    header('Location: my_orders.php?error=1');
    exit;
}
?>
