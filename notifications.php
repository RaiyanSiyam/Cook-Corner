<?php
// notifications.php
// This page displays a complete history of all notifications for the logged-in user.

include 'header.php';
require_once __DIR__ . '/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div class='text-center py-20'><p class='text-lg'>Please <a href='login.html' class='text-blue-600 font-bold'>login</a> to view your notifications.</p></div>";
    include 'footer.php';
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch all notifications for the current user, most recent first.
    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $all_notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    $all_notifications = [];
    error_log("DB Error fetching all notifications: " . $e->getMessage());
}
?>

<main class="bg-gray-50 py-12">
    <div class="container mx-auto px-4 max-w-3xl">
        <h1 class="text-3xl font-bold text-center mb-8">My Notifications</h1>
        
        <div class="bg-white rounded-lg shadow-md">
            <?php if (empty($all_notifications)): ?>
                <p class="text-center text-gray-500 p-8">You have no notifications yet.</p>
            <?php else: ?>
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($all_notifications as $notification): ?>
                    <li class="p-4 sm:p-6 hover:bg-gray-50 transition-colors <?= !$notification['is_read'] ? 'bg-blue-50' : '' ?>">
                        <a href="my_orders.php#order-<?= $notification['order_id'] ?>" class="block">
                            <div class="flex justify-between items-start">
                                <p class="text-sm text-gray-800 leading-relaxed <?= !$notification['is_read'] ? 'font-semibold' : '' ?>">
                                    <?= htmlspecialchars($notification['message']) ?>
                                </p>
                                <?php if (!$notification['is_read']): ?>
                                    <span class="mt-1 w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 ml-4" title="Unread"></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                <?= date('F j, Y, g:i a', strtotime($notification['created_at'])) ?>
                            </p>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
