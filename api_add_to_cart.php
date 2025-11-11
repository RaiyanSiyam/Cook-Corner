<?php
// api_add_to_cart.php
// Upgraded with server-side stock validation.

session_start();
header('Content-Type: application/json');

// 1. Authenticate User
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'You must be logged in to shop.']);
    exit;
}

// 2. Get Input
$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? null;
$quantity_to_add = $data['quantity'] ?? 1;

if (!$product_id || !is_numeric($product_id) || !is_numeric($quantity_to_add) || $quantity_to_add < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid product or quantity.']);
    exit;
}

// 3. Connect to Database and Perform Validation
require_once __DIR__ . '/db.php';
$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // Get current stock and quantity already in cart
    $stmt = $pdo->prepare("
        SELECT 
            p.stock_quantity, 
            (SELECT SUM(quantity) FROM cart_items WHERE user_id = ? AND product_id = ?) as in_cart 
        FROM products p WHERE p.id = ?");
    $stmt->execute([$user_id, $product_id, $product_id]);
    $product_info = $stmt->fetch();

    if (!$product_info) {
        throw new Exception("Product not found.");
    }

    $available_stock = (int)$product_info['stock_quantity'];
    $in_cart_quantity = (int)($product_info['in_cart'] ?? 0);
    
    // Check if the desired quantity exceeds available stock
    if (($in_cart_quantity + $quantity_to_add) > $available_stock) {
        http_response_code(400);
        $remaining = $available_stock - $in_cart_quantity;
        $message = $remaining > 0 ? "You can only add {$remaining} more item(s)." : "This item is out of stock.";
        echo json_encode(['ok' => false, 'error' => $message]);
        $pdo->rollBack();
        exit;
    }

    // If stock is sufficient, add to cart
    $sql = "INSERT INTO cart_items (user_id, product_id, quantity) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $product_id, $quantity_to_add]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'message' => 'Item(s) added to cart!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("API Error adding to cart: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'A database error occurred.']);
}
?>

