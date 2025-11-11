<?php
// api_update_cart.php
// Handles updating or removing items from the cart.

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'You must be logged in.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? null;
$quantity = $data['quantity'] ?? null;

if (!$product_id || !is_numeric($product_id) || !is_numeric($quantity) || $quantity < 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid data provided.']);
    exit;
}

require_once __DIR__ . '/db.php';
$user_id = $_SESSION['user_id'];

try {
    if ($quantity == 0) {
        // Remove item from cart
        $sql = "DELETE FROM cart_items WHERE user_id = ? AND product_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $product_id]);
    } else {
        // Check stock before updating
        $stock_stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        $stock_stmt->execute([$product_id]);
        $available_stock = $stock_stmt->fetchColumn();

        if ($quantity > $available_stock) {
            throw new Exception("Cannot set quantity to {$quantity}. Only {$available_stock} items are in stock.");
        }

        // Update quantity
        $sql = "UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$quantity, $user_id, $product_id]);
    }

    echo json_encode(['ok' => true, 'message' => 'Cart updated.']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?>
