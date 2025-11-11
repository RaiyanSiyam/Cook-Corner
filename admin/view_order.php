<?php
// admin/view_order.php
// Displays a detailed breakdown of a single order and provides management actions.

include 'header.php';
require_once __DIR__ . '/../db.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id === 0) {
    header('Location: manage_orders.php');
    exit;
}

try {
    // Fetch main order details and join with user info
    $sql = "SELECT o.*, u.name as user_name, u.email as user_email 
            FROM orders o JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: manage_orders.php');
        exit;
    }

    // Fetch items for this order
    $item_sql = "SELECT oi.*, p.name as product_name 
                 FROM order_items oi JOIN products p ON oi.product_id = p.id 
                 WHERE oi.order_id = ?";
    $item_stmt = $pdo->prepare($item_sql);
    $item_stmt->execute([$order_id]);
    $order_items = $item_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: Could not fetch order details.");
}

function getStatusClass($status) {
    return match (strtolower($status)) {
        'shipped' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
        'paid' => 'bg-green-100 text-green-800',
        'unpaid' => 'bg-yellow-100 text-yellow-800',
        default => 'bg-gray-100 text-gray-800',
    };
}
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white p-8 rounded-lg shadow-md">
        <!-- Header: Order ID and Back Link -->
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800">Order Details: #<?= $order['id'] ?></h1>
            <a href="manage_orders.php" class="text-blue-600 hover:underline">&larr; Back to All Orders</a>
        </div>
        
        <!-- Order & Customer Details Grid -->
        <div class="grid md:grid-cols-2 gap-8 mb-8">
            <!-- Customer Details -->
            <div>
                <h2 class="text-lg font-semibold mb-3">Customer Information</h2>
                <div class="text-sm space-y-2 text-gray-700">
                    <p><strong>Name:</strong> <?= htmlspecialchars($order['user_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($order['user_email']) ?></p>
                    <p><strong>Shipping Address:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone_number']) ?></p>
                </div>
            </div>
            <!-- Order Details -->
            <div>
                <h2 class="text-lg font-semibold mb-3">Order Summary</h2>
                <div class="text-sm space-y-2 text-gray-700">
                    <p><strong>Order Date:</strong> <?= date('F j, Y, g:i a', strtotime($order['order_date'])) ?></p>
                    <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
                    <p><strong>Order Status:</strong> <span class="font-semibold px-2 py-1 rounded-full <?= getStatusClass($order['order_status']) ?>"><?= htmlspecialchars($order['order_status']) ?></span></p>
                    <p><strong>Payment Status:</strong> <span class="font-semibold px-2 py-1 rounded-full <?= getStatusClass($order['payment_status']) ?>"><?= htmlspecialchars($order['payment_status']) ?></span></p>
                    <p class="text-base mt-2"><strong>Total Amount:</strong> <span class="font-bold text-lg text-gray-900">$<?= number_format($order['total_amount'], 2) ?></span></p>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div>
            <h2 class="text-lg font-semibold mb-4">Items Ordered</h2>
            <div class="border rounded-lg overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="py-2 px-4 text-center text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="py-2 px-4 text-right text-xs font-medium text-gray-500 uppercase">Price per Item</th>
                            <th class="py-2 px-4 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td class="py-3 px-4 font-medium text-gray-800"><?= htmlspecialchars($item['product_name']) ?></td>
                            <td class="py-3 px-4 text-center text-gray-600"><?= $item['quantity'] ?></td>
                            <td class="py-3 px-4 text-right text-gray-600">$<?= number_format($item['price_per_item'], 2) ?></td>
                            <td class="py-3 px-4 text-right font-semibold">$<?= number_format($item['price_per_item'] * $item['quantity'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Admin Actions -->
        <?php if ($order['order_status'] === 'Shipped'): ?>
        <div class="mt-8 pt-6 border-t">
            <h2 class="text-lg font-semibold mb-4">Manage Order</h2>
            <div class="flex items-center space-x-4">
                <form action="update_order_status.php" method="POST">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button type="submit" name="action" value="complete" class="bg-green-500 text-white font-bold py-2 px-4 rounded-md hover:bg-green-600">Mark as Completed</button>
                </form>
                <form action="update_order_status.php" method="POST">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button type="submit" name="action" value="cancel" class="bg-red-500 text-white font-bold py-2 px-4 rounded-md hover:bg-red-600" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel Order</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
