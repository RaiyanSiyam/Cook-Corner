<?php
// cart.php
// Displays the user's shopping cart and allows for modifications.

include 'header.php';
require_once __DIR__ . '/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div class='text-center py-20'><p class='text-lg'>Please <a href='login.html' class='text-blue-600 font-bold'>login</a> to view your cart.</p></div>";
    include 'footer.php';
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_total = 0;

// Fetch items in the user's cart, joining with products to get details
try {
    $sql = "SELECT p.id, p.name, p.price, p.image_url, p.stock_quantity, ci.quantity
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();
} catch (PDOException $e) {
    $cart_items = [];
    error_log("DB Error fetching cart items: " . $e->getMessage());
}
?>

<main class="gradient-bg py-12">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12" data-aos="fade-down">
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800">Your Shopping Cart</h1>
        </div>

        <!-- Toast for feedback messages -->
        <div id="toast-message" class="hidden fixed top-24 right-5 bg-green-500 text-white py-2 px-4 rounded-lg shadow-lg z-50"></div>

        <div id="cart-container" class="bg-white rounded-lg shadow-lg p-4 sm:p-8">
            <div id="cart-content">
                <?php if (empty($cart_items)): ?>
                    <div id="empty-cart-message" class="text-center py-16">
                        <i data-feather="shopping-bag" class="w-16 h-16 mx-auto text-gray-300"></i>
                        <p class="text-gray-600 text-lg mt-4">Your cart is empty.</p>
                        <a href="shop.php" class="mt-6 inline-block bg-blue-600 text-white font-bold py-2 px-4 rounded-full hover:bg-blue-700 transition">Continue Shopping</a>
                    </div>
                <?php else: ?>
                    <!-- Cart Items Table -->
                    <div class="flow-root">
                        <ul id="cart-items-list" role="list" class="-my-6 divide-y divide-gray-200">
                            <?php foreach ($cart_items as $item): 
                                $subtotal = $item['price'] * $item['quantity'];
                                $cart_total += $subtotal;
                            ?>
                            <li class="flex py-6 cart-item" data-product-id="<?= $item['id'] ?>" data-price="<?= $item['price'] ?>">
                                <div class="h-24 w-24 flex-shrink-0 overflow-hidden rounded-md border border-gray-200">
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="h-full w-full object-cover object-center">
                                </div>
                                <div class="ml-4 flex flex-1 flex-col">
                                    <div>
                                        <div class="flex justify-between text-base font-medium text-gray-900">
                                            <h3><a href="#"><?= htmlspecialchars($item['name']) ?></a></h3>
                                            <p class="item-subtotal ml-4">$<?= number_format($subtotal, 2) ?></p>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500">$<?= htmlspecialchars($item['price']) ?> each</p>
                                    </div>
                                    <div class="flex flex-1 items-end justify-between text-sm">
                                        <div class="flex items-center border rounded-md">
                                            <button class="quantity-btn minus-btn p-1 text-gray-600 hover:bg-gray-100 rounded-l-md">-</button>
                                            <input type="number" class="quantity-input w-12 text-center border-0 focus:ring-0" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock_quantity'] ?>">
                                            <button class="quantity-btn plus-btn p-1 text-gray-600 hover:bg-gray-100 rounded-r-md">+</button>
                                        </div>
                                        <div class="flex">
                                            <button type="button" class="remove-btn font-medium text-red-600 hover:text-red-500">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Cart Summary & Checkout -->
                    <div id="cart-summary" class="border-t border-gray-200 px-4 py-6 sm:px-6 mt-8">
                        <div class="flex justify-between text-lg font-medium text-gray-900">
                            <p>Subtotal</p>
                            <p id="cart-total">$<?= number_format($cart_total, 2) ?></p>
                        </div>
                        <p class="mt-0.5 text-sm text-gray-500">Shipping and taxes calculated at checkout.</p>
                        <div class="mt-6">
                            <a href="checkout.php" class="flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-blue-700">Checkout</a>
                        </div>
                         <div class="mt-6 flex justify-center text-center text-sm text-gray-500">
                            <p>or <a href="shop.php" class="font-medium text-blue-600 hover:text-blue-500">Continue Shopping<span aria-hidden="true"> &rarr;</span></a></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    function recalculateTotal() {
        let total = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            const price = parseFloat(item.dataset.price);
            const quantity = parseInt(item.querySelector('.quantity-input').value);
            const subtotal = price * quantity;
            item.querySelector('.item-subtotal').textContent = '$' + subtotal.toFixed(2);
            total += subtotal;
        });
        document.getElementById('cart-total').textContent = '$' + total.toFixed(2);

        // If no items left, show empty cart message
        if (document.querySelectorAll('.cart-item').length === 0) {
            document.getElementById('cart-content').innerHTML = `
                <div id="empty-cart-message" class="text-center py-16">
                    <i data-feather="shopping-bag" class="w-16 h-16 mx-auto text-gray-300"></i>
                    <p class="text-gray-600 text-lg mt-4">Your cart is empty.</p>
                    <a href="shop.php" class="mt-6 inline-block bg-blue-600 text-white font-bold py-2 px-4 rounded-full hover:bg-blue-700 transition">Continue Shopping</a>
                </div>`;
            feather.replace();
        }
    }

    document.querySelectorAll('.cart-item').forEach(item => {
        const productId = item.dataset.productId;
        const input = item.querySelector('.quantity-input');
        const stock = parseInt(input.max, 10);

        const updateCartAPI = async (newQuantity) => {
            try {
                const response = await fetch('api_update_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId, quantity: newQuantity })
                });
                const result = await response.json();
                if (!response.ok) { throw new Error(result.error); }
                return true; // Success
            } catch (e) {
                showToast(e.message, 'error');
                return false; // Failure
            }
        };

        // --- REMOVE BUTTON LOGIC ---
        item.querySelector('.remove-btn').addEventListener('click', async function() {
            const cartItemElement = this.closest('.cart-item');
            const success = await updateCartAPI(0); // Set quantity to 0 to remove
            if (success) {
                cartItemElement.style.transition = 'opacity 0.5s ease';
                cartItemElement.style.opacity = '0';
                setTimeout(() => {
                    cartItemElement.remove();
                    recalculateTotal();
                    showToast('Item removed from cart.', 'success');
                }, 500);
            }
        });

        // --- QUANTITY BUTTONS LOGIC ---
        item.querySelector('.plus-btn').addEventListener('click', async () => {
            let currentVal = parseInt(input.value);
            if (currentVal < stock) {
                const success = await updateCartAPI(currentVal + 1);
                if (success) {
                    input.value = currentVal + 1;
                    recalculateTotal();
                }
            } else {
                showToast(`Only ${stock} items available.`, 'error');
            }
        });

        item.querySelector('.minus-btn').addEventListener('click', async () => {
            let currentVal = parseInt(input.value);
            if (currentVal > 1) {
                const success = await updateCartAPI(currentVal - 1);
                if (success) {
                    input.value = currentVal - 1;
                    recalculateTotal();
                }
            }
        });
    });
});

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast-message');
    if (!toast) return;
    toast.textContent = message;
    toast.classList.remove('hidden', 'bg-green-500', 'bg-red-500');
    toast.classList.add(type === 'success' ? 'bg-green-500' : 'bg-red-500');
    
    setTimeout(() => { toast.classList.remove('hidden'); }, 100);
    setTimeout(() => { toast.classList.add('hidden'); }, 3000);
}
</script>
<?php include 'footer.php'; ?>

