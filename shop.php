<?php
// shop.php
// Definitive Fix: Corrected DB connection order, restored products, and added a non-header "See Cart" button.

include 'header.php';
// DEFINITIVE FIX: The database connection must be required here to prevent the fatal error.
require_once __DIR__ . '/db.php';

// --- Initialize cart item count for the button ---
$cart_item_count = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $count_stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart_items WHERE user_id = ?");
        $count_stmt->execute([$_SESSION['user_id']]);
        $result = $count_stmt->fetchColumn();
        if ($result !== false) {
            $cart_item_count = (int)$result;
        }
    } catch (PDOException $e) {
        error_log("DB Error counting cart items: " . $e->getMessage());
    }
}

// --- Get filter and search parameters ---
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : 'all';

// --- Fetch categories ---
try {
    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories = [];
}

// --- Build and execute product query ---
$sql = "SELECT * FROM products";
$params = [];
$where_clauses = [];

if ($search_term) {
    $where_clauses[] = "name LIKE ?";
    $params[] = '%' . $search_term . '%';
}
if ($category_filter !== 'all') {
    $where_clauses[] = "category = ?";
    $params[] = $category_filter;
}
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " ORDER BY name";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}
?>

<main class="gradient-bg py-12">
    <div class="container mx-auto px-4">
        <div class="text-center mb-8" data-aos="fade-down">
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800">Grocery Store</h1>
            <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">Fresh ingredients delivered right to your door.</p>
        </div>

        <!-- Search and Filter Controls -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-12" data-aos="fade-up">
            <div class="flex flex-col md:flex-row gap-4 items-center">
                <form action="shop.php" method="GET" class="flex-grow flex gap-4 items-center">
                    <div class="relative flex-grow">
                        <input type="text" name="q" placeholder="Search for products..." value="<?= htmlspecialchars($search_term) ?>" class="w-full pl-10 pr-4 py-2 border rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i data-feather="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-6 rounded-full hover:bg-blue-700 transition">Search</button>
                    <?php if ($category_filter !== 'all'): ?>
                        <input type="hidden" name="category" value="<?= htmlspecialchars($category_filter) ?>">
                    <?php endif; ?>
                </form>
                
                <!-- NEW: "See Cart" button added here -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="cart.php" class="relative bg-gray-100 text-gray-800 font-bold py-2 px-6 rounded-full hover:bg-gray-200 transition flex items-center flex-shrink-0">
                    <span>See Cart</span>
                    <i data-feather="shopping-cart" class="w-5 h-5 ml-2"></i>
                    <span id="page-cart-count" class="absolute -top-2 -right-2 bg-pink-500 text-white text-xs font-bold rounded-full h-6 w-6 flex items-center justify-center <?= $cart_item_count > 0 ? '' : 'hidden' ?>">
                        <?= $cart_item_count ?>
                    </span>
                </a>
                <?php endif; ?>
            </div>

            <div class="flex flex-wrap justify-center gap-2 mt-6">
                <a href="shop.php?q=<?= htmlspecialchars($search_term) ?>" class="filter-btn px-4 py-2 text-sm font-semibold rounded-full <?= $category_filter == 'all' ? 'active' : 'bg-gray-100 hover:bg-gray-200' ?>">All Products</a>
                <?php foreach ($categories as $category): ?>
                    <a href="shop.php?category=<?= urlencode($category) ?>&q=<?= htmlspecialchars($search_term) ?>" class="filter-btn px-4 py-2 text-sm font-semibold rounded-full <?= $category_filter == $category ? 'active' : 'bg-gray-100 hover:bg-gray-200' ?>">
                        <?= htmlspecialchars($category) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>


        <div id="toast-message" class="hidden fixed top-24 right-5 bg-green-500 text-white py-2 px-4 rounded-lg shadow-lg z-50"></div>

        <!-- Product Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-8">
            <?php if (empty($products)): ?>
                <div class="col-span-full text-center py-16">
                    <p class="text-gray-600 text-lg">No products found matching your criteria.</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card bg-white rounded-lg overflow-hidden shadow-lg flex flex-col" data-aos="fade-up" data-stock="<?= $product['stock_quantity'] ?>">
                         <!-- DEFINITIVE FIX: Product Card Content RESTORED -->
                         <div class="relative">
                            <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-48 object-cover">
                            <?php if ($product['stock_quantity'] == 0): ?>
                                <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                    <span class="text-white font-bold text-lg">Out of Stock</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4 flex flex-col flex-grow">
                            <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="text-sm text-gray-500 flex-grow mt-1"><?= htmlspecialchars($product['description']) ?></p>
                            <p class="text-xs mt-2 font-medium <?= $product['stock_quantity'] > 10 ? 'text-green-600' : ($product['stock_quantity'] > 0 ? 'text-amber-600' : 'text-red-600') ?>">
                                <?= $product['stock_quantity'] > 0 ? htmlspecialchars($product['stock_quantity']) . ' in stock' : 'Out of stock' ?>
                            </p>
                            <div class="flex justify-between items-center mt-2">
                                <span class="text-xl font-bold text-blue-600">$<?= htmlspecialchars($product['price']) ?></span>
                                <div class="flex items-center">
                                    <div class="flex items-center border rounded-full">
                                        
                                        <input type="number" class="quantity-input w-10 text-center border-0 focus:ring-0" value="1" min="1" max="<?= $product['stock_quantity'] ?>" <?= $product['stock_quantity'] == 0 ? 'disabled' : '' ?>>
                                      
                                    </div>
                                    <button data-product-id="<?= $product['id'] ?>" class="add-to-cart-btn bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700 transition ml-2 disabled:bg-gray-400" <?= $product['stock_quantity'] == 0 ? 'disabled' : '' ?>>
                                        <i data-feather="shopping-cart" class="w-5 h-5"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Add to Cart Logic with count update ---
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', async function() {
            // Check if the user is logged in
            const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
            if (!isLoggedIn) {
                showToast('Please log in to add items to your cart.', 'error');
                setTimeout(() => { window.location.href = 'login.html'; }, 2000);
                return;
            }

            const productId = this.dataset.productId;
            const quantityInput = this.closest('.product-card').querySelector('.quantity-input');
            const quantity = parseInt(quantityInput.value, 10);
            
            try {
                const response = await fetch('api_add_to_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId, quantity: quantity })
                });

                const result = await response.json();
                if (!response.ok) { throw new Error(result.error); }
                
                showToast(result.message, 'success');

                // Update the page cart count
                const pageCountSpan = document.getElementById('page-cart-count');
                if (pageCountSpan) {
                    const newCount = parseInt(pageCountSpan.textContent || '0') + quantity;
                    pageCountSpan.textContent = newCount;
                    pageCountSpan.classList.remove('hidden');
                }
                
                // Also update the header cart count if it exists
                const headerCountSpan = document.getElementById('header-cart-count');
                if (headerCountSpan) {
                     const newHeaderCount = parseInt(headerCountSpan.textContent || '0') + quantity;
                    headerCountSpan.textContent = newHeaderCount;
                    headerCountSpan.classList.remove('hidden');
                }

            } catch (error) {
                showToast(error.message, 'error');
            }
        });
    });
    
    // --- Other JavaScript for quantity buttons ---
    document.querySelectorAll('.product-card').forEach(card => {
        const stock = parseInt(card.dataset.stock, 10);
        const input = card.querySelector('.quantity-input');
        const plusBtn = card.querySelector('.plus-btn');
        const minusBtn = card.querySelector('.minus-btn');

        if(plusBtn) {
            plusBtn.addEventListener('click', () => {
                let currentValue = parseInt(input.value, 10);
                if (currentValue < stock) {
                    input.value = currentValue + 1;
                }
            });
        }
        if(minusBtn) {
            minusBtn.addEventListener('click', () => {
                let currentValue = parseInt(input.value, 10);
                if (currentValue > 1) {
                    input.value = currentValue - 1;
                }
            });
        }
    });
});

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast-message');
    toast.textContent = message;
    toast.classList.remove('hidden', 'bg-green-500', 'bg-red-500');
    toast.classList.add(type === 'success' ? 'bg-green-500' : 'bg-red-500');
    setTimeout(() => { toast.classList.add('hidden'); }, 3000);
}
</script>
<?php include 'footer.php'; ?>

