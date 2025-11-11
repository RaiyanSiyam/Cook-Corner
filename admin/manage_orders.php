<?php
// admin/manage_orders.php
// This version adds a Payment Status column and a functional "View Details" link.

include 'header.php';
require_once __DIR__ . '/../db.php';

$message = '';
if (isset($_GET['status_updated'])) {
    $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6'>Order status has been updated successfully.</div>";
}

try {
    // The SQL query now fetches payment_status as well.
    $sql = "SELECT 
                o.id, o.total_amount, o.order_status, o.payment_status, o.order_date, 
                u.name AS customer_name, u.email AS customer_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.order_date DESC";
            
    $stmt = $pdo->query($sql);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
    $message = "<div class='bg-red-100 border-red-400 text-red-700 p-4'><strong>Database Error:</strong> Could not fetch orders.</div>";
}

// Helper function for status badges
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

<div class="bg-white p-8 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Order Management</h1>
    
    <?= $message ?>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Order Status</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Payment Status</th>
                    <th class="py-3 px-4 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" class="text-center py-10 text-gray-500">No orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="py-4 px-4 font-medium text-gray-900">#<?= $order['id'] ?></td>
                            <td class="py-4 px-4 text-sm text-gray-800"><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td class="py-4 px-4 text-sm text-gray-500"><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                            <td class="py-4 px-4 text-sm font-semibold text-gray-800">$<?= number_format($order['total_amount'], 2) ?></td>
                            <td class="py-4 px-4"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= getStatusClass($order['order_status']) ?>"><?= htmlspecialchars($order['order_status']) ?></span></td>
                            <td class="py-4 px-4"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= getStatusClass($order['payment_status']) ?>"><?= htmlspecialchars($order['payment_status']) ?></span></td>
                            <td class="py-4 px-4 text-right text-sm font-medium">
                                <a href="view_order.php?id=<?= $order['id'] ?>" class="text-blue-600 hover:text-blue-900">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
