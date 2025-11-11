<?php
// admin/update_order_status.php
// Handles the logic for an admin to update an order's status.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_orders.php');
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($order_id === 0 || !in_array($action, ['complete', 'cancel'])) {
    header('Location: manage_orders.php');
    exit;
}

$new_status = '';
if ($action === 'complete') {
    $new_status = 'Completed';
} elseif ($action === 'cancel') {
    $new_status = 'Cancelled';
}

try {
    // We only allow updates on orders that are currently 'Shipped'
    $sql = "UPDATE orders SET order_status = ? WHERE id = ? AND order_status = 'Shipped'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$new_status, $order_id]);

    // If the order was 'Completed', also mark it as 'Paid'
    if ($new_status === 'Completed') {
        $payment_sql = "UPDATE orders SET payment_status = 'Paid' WHERE id = ?";
        $pdo->prepare($payment_sql)->execute([$order_id]);
    }

    // Redirect back to the main order list with a success message
    header('Location: manage_orders.php?status_updated=1');
    exit;

} catch (PDOException $e) {
    error_log("Order status update failed: " . $e->getMessage());
    header('Location: view_order.php?id=' . $order_id . '&error=1');
    exit;
}
?>
