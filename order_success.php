<?php
// order_success.php
include 'header.php';
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
?>
<main class="py-20 text-center">
    <div class="container mx-auto">
        <h1 class="text-4xl font-bold text-green-600">Thank You!</h1>
        <p class="mt-4 text-lg">Your order has been placed successfully.</p>
        <?php if ($order_id): ?>
            <p class="mt-2 text-gray-600">Your Order ID is: <strong>#<?= htmlspecialchars($order_id) ?></strong></p>
        <?php endif; ?>
        <a href="shop.php" class="mt-8 inline-block bg-blue-600 text-white font-bold py-3 px-6 rounded-full">Continue Shopping</a>
    </div>
</main>
<?php include 'footer.php'; ?>
