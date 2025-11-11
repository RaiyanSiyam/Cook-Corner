<?php
// admin/index.php
// This is the fully upgraded dashboard with advanced analytics and a revenue graph.

include 'header.php';
require_once __DIR__ . '/../db.php';

try {
    // --- 1. Key Performance Indicators (KPIs) ---
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    // FIXED: Revenue now includes 'Completed' and 'Shipped' orders for a more accurate reflection of sales.
    $total_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE order_status IN ('Completed', 'Shipped')")->fetchColumn();

    // Stats for the last 7 days
    $new_users_7_days = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE(NOW()) - INTERVAL 7 DAY")->fetchColumn();
    $orders_7_days = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_date >= DATE(NOW()) - INTERVAL 7 DAY")->fetchColumn();

    // --- 2. Order Status Breakdown ---
    $order_statuses = $pdo->query("SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status")->fetchAll(PDO::FETCH_KEY_PAIR);

    // --- 3. Top Selling Products & Categories ---
    $top_selling_products = $pdo->query("
        SELECT p.name, SUM(oi.quantity) as total_sold
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        GROUP BY p.name
        ORDER BY total_sold DESC
        LIMIT 5
    ")->fetchAll();

    $top_selling_categories = $pdo->query("
        SELECT p.category, SUM(oi.quantity) as total_sold
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        GROUP BY p.category
        ORDER BY total_sold DESC
        LIMIT 5
    ")->fetchAll();

    // --- 4. Recent Orders ---
    $recent_orders = $pdo->query("
        SELECT o.id, u.name AS user_name, o.total_amount, o.order_date, o.order_status
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.order_date DESC
        LIMIT 5
    ")->fetchAll();
    
    // --- 5. Data for Revenue Chart (Last 7 Days) ---
    // FIXED: Chart data also includes 'Completed' and 'Shipped' orders.
    $revenue_chart_data = $pdo->query("
        SELECT 
            DATE(order_date) as date, 
            SUM(total_amount) as daily_revenue
        FROM orders
        WHERE order_date >= DATE(NOW()) - INTERVAL 7 DAY AND order_status IN ('Completed', 'Shipped')
        GROUP BY DATE(order_date)
        ORDER BY date ASC
    ")->fetchAll();

    // Format data for Chart.js
    $chart_labels = [];
    $chart_values = [];
    // Create a map of dates for easy lookup
    $revenue_map = [];
    foreach ($revenue_chart_data as $data) {
        $revenue_map[$data['date']] = $data['daily_revenue'];
    }
    // Loop through the last 7 days to ensure all days are present
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $chart_labels[] = date('M d', strtotime($date));
        $chart_values[] = $revenue_map[$date] ?? 0;
    }


} catch (PDOException $e) {
    echo "<div class='text-red-500 text-center mt-10'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    // Stop execution if the database fails
    include 'footer.php';
    exit;
}
?>
<!-- Include Chart.js library for the graph -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8">
    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                <p class="text-3xl font-bold text-gray-800">$<?= number_format($total_revenue, 2) ?></p>
            </div>
            <div class="bg-blue-100 text-blue-600 rounded-full p-3">
                <i data-feather="dollar-sign" class="w-6 h-6"></i>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Orders</p>
                <p class="text-3xl font-bold text-gray-800"><?= $total_orders ?></p>
                <p class="text-xs text-green-500">+<?= $orders_7_days ?> in last 7 days</p>
            </div>
            <div class="bg-green-100 text-green-600 rounded-full p-3">
                <i data-feather="shopping-cart" class="w-6 h-6"></i>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Users</p>
                <p class="text-3xl font-bold text-gray-800"><?= $total_users ?></p>
                 <p class="text-xs text-green-500">+<?= $new_users_7_days ?> in last 7 days</p>
            </div>
             <div class="bg-yellow-100 text-yellow-600 rounded-full p-3">
                <i data-feather="users" class="w-6 h-6"></i>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Products</p>
                <p class="text-3xl font-bold text-gray-800"><?= $total_products ?></p>
            </div>
            <div class="bg-indigo-100 text-indigo-600 rounded-full p-3">
                <i data-feather="package" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Revenue Chart and Order Statuses -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Revenue (Last 7 Days)</h2>
            <canvas id="revenueChart"></canvas>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Order Statuses</h2>
            <div class="space-y-4">
                <!-- FIXED: Removed 'Pending' status from display -->
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Shipped</span>
                    <span class="font-bold text-blue-500"><?= $order_statuses['Shipped'] ?? 0 ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Completed</span>
                    <span class="font-bold text-green-500"><?= $order_statuses['Completed'] ?? 0 ?></span>
                </div>
                 <div class="flex justify-between items-center">
                    <span class="text-gray-600">Cancelled</span>
                    <span class="font-bold text-red-500"><?= $order_statuses['Cancelled'] ?? 0 ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products and Categories -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Top Selling Products</h2>
            <ul class="space-y-3">
                <?php foreach($top_selling_products as $product): ?>
                <li class="flex justify-between items-center">
                    <span class="text-gray-700"><?= htmlspecialchars($product['name']) ?></span>
                    <span class="font-bold bg-gray-100 px-2 py-1 text-sm rounded-md"><?= $product['total_sold'] ?> sold</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
             <h2 class="text-lg font-semibold text-gray-800 mb-4">Top Selling Categories</h2>
            <ul class="space-y-3">
                <?php foreach($top_selling_categories as $category): ?>
                <li class="flex justify-between items-center">
                    <span class="text-gray-700"><?= htmlspecialchars($category['category']) ?></span>
                    <span class="font-bold bg-gray-100 px-2 py-1 text-sm rounded-md"><?= $category['total_sold'] ?> sold</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Recent Orders</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                        <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr class="border-b">
                            <td class="py-3 px-4 font-medium text-gray-800">#<?= $order['id'] ?></td>
                            <td class="py-3 px-4 text-gray-600"><?= htmlspecialchars($order['user_name']) ?></td>
                            <td class="py-3 px-4 text-gray-600">$<?= number_format($order['total_amount'], 2) ?></td>
                            <td class="py-3 px-4 text-gray-600"><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                            <td class="py-3 px-4">
                                <?php
                                // Improved: Dynamically set status color
                                $status_color = match($order['order_status']) {
                                    'Completed' => 'bg-green-100 text-green-800',
                                    'Shipped'   => 'bg-blue-100 text-blue-800',
                                    'Cancelled' => 'bg-red-100 text-red-800',
                                    default     => 'bg-yellow-100 text-yellow-800', // for Pending
                                };
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $status_color ?>"><?= htmlspecialchars($order['order_status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Revenue',
                data: <?= json_encode($chart_values) ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value, index, values) {
                            return '$' + value;
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>

<?php include 'footer.php'; ?>

