<?php
// place_order.php
// UPDATED: Now creates a notification when an order is successfully placed.

session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$zip_code = trim($_POST['zip_code'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$full_address = "$address, $city, $zip_code";

try {
    $pdo->beginTransaction();

    $cart_sql = "SELECT p.id, p.price, p.stock_quantity, ci.quantity 
                 FROM cart_items ci 
                 JOIN products p ON ci.product_id = p.id 
                 WHERE ci.user_id = ? FOR UPDATE";
    $stmt = $pdo->prepare($cart_sql);
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        throw new Exception("Your cart is empty.");
    }

    $total_amount = 0;
    foreach ($items as $item) {
        if ($item['quantity'] > $item['stock_quantity']) {
            throw new Exception("Not enough stock for one of your items.");
        }
        $total_amount += $item['price'] * $item['quantity'];
    }

    $order_sql = "INSERT INTO orders (user_id, total_amount, shipping_address, phone_number, order_status) VALUES (?, ?, ?, ?, 'Shipped')";
    $pdo->prepare($order_sql)->execute([$user_id, $total_amount, $full_address, $phone_number]);
    $order_id = $pdo->lastInsertId();

    $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price_per_item) VALUES (?, ?, ?, ?)";
    $update_stock_sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
    
    foreach ($items as $item) {
        $pdo->prepare($item_sql)->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
        $pdo->prepare($update_stock_sql)->execute([$item['quantity'], $item['id']]);
    }

    $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$user_id]);

    // --- THIS IS THE MISSING PIECE ---
    $notification_message = "Your order #{$order_id} has been placed and is now being shipped!";
    $pdo->prepare("INSERT INTO notifications (user_id, order_id, message) VALUES (?, ?, ?)")->execute([$user_id, $order_id, $notification_message]);
    // --- END ---

    $pdo->commit();
    
    header("Location: order_success.php?order_id=" . $order_id);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: checkout.php?error=" . urlencode($e->getMessage()));
    exit;
}
?>
