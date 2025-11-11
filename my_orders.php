<?php
// my_orders.php
// This version includes the "Mark as Received" button and displays payment status.

include 'header.php';
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<div class='text-center py-20'><p class='text-lg'>Please <a href='login.html' class='text-blue-600 font-bold'>login</a> to view your order history.</p></div>";
    include 'footer.php';
    exit;
}

$user_id = $_SESSION['user_id'];
$message = isset($_GET['success']) ? "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6'>Order status updated successfully!</div>" : '';

try {
    $sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
    error_log("DB Error fetching orders: " . $e->getMessage());
}
?>
<style>
    details > summary { list-style: none; }
    details > summary::-webkit-details-marker { display: none; }
    details[open] summary .arrow { transform: rotate(90deg); }
</style>
<main class="gradient-bg py-12">
    <div class="container mx-auto px-4 max-w-4xl">
        <h1 class="text-3xl font-bold text-center mb-8">My Order History</h1>
        <?= $message ?>
        <div class="space-y-4">
            <?php if (empty($orders)): ?>
                <p class="text-center text-gray-600">You haven't placed any orders yet.</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <details class="bg-white rounded-lg shadow-md overflow-hidden">
                    <summary class="p-6 grid grid-cols-3 items-center cursor-pointer">
                        <div>
                            <p class="font-bold text-lg">Order #<?= $order['id'] ?></p>
                            <p class="text-sm text-gray-500">Date: <?= date('F j, Y', strtotime($order['order_date'])) ?></p>
                        </div>
                        <div class="text-center">
                            <?php
                                $status_color = match($order['order_status']) {
                                    'Completed' => 'bg-green-100 text-green-800', 'Shipped' => 'bg-blue-100 text-blue-800',
                                    'Cancelled' => 'bg-red-100 text-red-800', default => 'bg-yellow-100 text-yellow-800',
                                };
                                $payment_status_color = ($order['payment_status'] === 'Paid') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                            ?>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $status_color ?>"><?= htmlspecialchars($order['order_status']) ?></span>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $payment_status_color ?> ml-2"><?= htmlspecialchars($order['payment_status']) ?></span>
                        </div>
                        <div class="text-right flex items-center justify-end">
                            <p class="font-bold text-lg mr-4">$<?= number_format($order['total_amount'], 2) ?></p>
                           <i data-feather="chevron-right" class="arrow transition-transform duration-300"></i>
                        </div>
                    </summary>
                    <div class="border-t bg-gray-50 p-6">
                        <div class="md:flex justify-between items-start">
                            <div>
                                <h4 class="font-semibold mb-2">Shipping Address</h4>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($order['shipping_address']) ?></p>
                                <h4 class="font-semibold mb-2 mt-4">Payment Method</h4>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($order['payment_method']) ?></p>
                            </div>
                            <?php if ($order['order_status'] === 'Shipped'): ?>
                            <form action="mark_order_received.php" method="POST" class="mt-4 md:mt-0">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <button type="submit" class="bg-green-500 text-white font-bold py-2 px-4 rounded-md hover:bg-green-600">
                                    Mark as Received
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <h4 class="font-semibold mt-6 mb-4 border-t pt-4">Items in this order:</h4>
                        <ul class="space-y-4">
                            <?php
                            $item_stmt = $pdo->prepare("SELECT p.name, p.image_url, oi.quantity, oi.price_per_item FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                            $item_stmt->execute([$order['id']]);
                            $order_items = $item_stmt->fetchAll();
                            foreach ($order_items as $item):
                            ?>
                            <li class="flex items-center">
                                <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-16 h-16 object-cover rounded-md mr-4">
                                <div class="flex-grow">
                                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($item['name']) ?></p>
                                    <p class="text-sm text-gray-600">Quantity: <?= $item['quantity'] ?> &times; $<?= number_format($item['price_per_item'], 2) ?></p>
                                </div>
                                <p class="font-semibold">$<?= number_format($item['quantity'] * $item['price_per_item'], 2) ?></p>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </details>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>

