<?php
// admin/manage_products.php
// FIXED: The AJAX handling logic has been moved to the top of the script. This ensures
// that live search requests receive only the table data they need, which prevents
// the page layout from breaking.

require_once __DIR__ . '/../db.php';

// --- Build a reliable base URL for local images (needed for both load types) ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_root = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\'); 
$base_url = $protocol . $host . $project_root . '/';

// --- Handle Search Functionality (needed for both load types) ---
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';

// Fetch products from the database, filtering by search term if provided
try {
    $sql = "SELECT * FROM products";
    $params = [];
    if (!empty($search_term)) {
        $sql .= " WHERE name LIKE ?";
        $params[] = "%" . $search_term . "%";
    }
    $sql .= " ORDER BY id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    // If it's an AJAX request, send back an error row.
    if (isset($_GET['ajax'])) {
        echo '<tr><td colspan="6" class="text-center py-10 text-red-500">Database Error.</td></tr>';
        exit;
    }
    // For a normal page load, set an error message to be displayed.
    $products = [];
    $message = "<div class='text-red-500 text-center p-6'>Error fetching products: " . htmlspecialchars($e->getMessage()) . "</div>";
}


// --- THIS ENTIRE BLOCK IS MOVED TO THE TOP ---
// It now runs BEFORE any HTML is sent to the browser.
if (isset($_GET['ajax'])) {
    if (empty($products)) {
        echo '<tr><td colspan="6" class="text-center py-10 text-gray-500">';
        echo !empty($search_term) ? 'No products found for "' . htmlspecialchars($search_term) . '".' : 'No products found.';
        echo '</td></tr>';
    } else {
        foreach ($products as $product) {
            $image_url = htmlspecialchars($product['image_url']);
            $image_src = (strpos($image_url, 'http') === 0) ? $image_url : $base_url . $image_url;

            echo '<tr>';
            echo '<td class="py-4 px-4">';
            if (!empty($product['image_url'])) {
                echo '<img src="' . $image_src . '" alt="' . htmlspecialchars($product['name']) . '" class="w-16 h-16 object-cover rounded-md">';
            } else {
                echo '<div class="w-16 h-16 bg-gray-200 rounded-md flex items-center justify-center text-xs text-gray-500">No Image</div>';
            }
            echo '</td>';
            echo '<td class="py-4 px-4 whitespace-nowrap font-medium text-gray-900">' . htmlspecialchars($product['name']) . '</td>';
            echo '<td class="py-4 px-4 text-gray-600">' . htmlspecialchars($product['category']) . '</td>';
            echo '<td class="py-4 px-4 text-gray-800">$' . number_format($product['price'], 2) . '</td>';
            echo '<td class="py-4 px-4 text-gray-800">' . $product['stock_quantity'] . '</td>';
            echo '<td class="py-4 px-4 whitespace-nowrap text-sm font-medium">';
            echo '<a href="edit_product.php?id=' . $product['id'] . '" class="text-blue-600 hover:text-blue-900 mr-4">Edit</a>';
            echo '<a href="delete_product.php?id=' . $product['id'] . '" class="text-red-600 hover:text-red-900 delete-btn">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
    }
    exit; // Stop script execution for AJAX requests. This is critical.
}

// --- The rest of the file runs ONLY for a normal, full page load ---
include 'header.php';

// Check for success/error messages from redirects
if (!isset($message)) {
    $message = '';
    if (isset($_GET['success'])) {
        $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6'>Product added successfully!</div>";
    }
    if (isset($_GET['updated'])) {
        $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6'>Product updated successfully!</div>";
    }
    if (isset($_GET['deleted'])) {
        $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6'>Product permanently deleted.</div>";
    }
}
?>

<div class="bg-white p-8 rounded-lg shadow-md">
    <div class="md:flex justify-between items-center mb-6 space-y-4 md:space-y-0">
        <h1 class="text-2xl font-bold text-gray-800">Manage Products</h1>
        
        <form id="search-form" action="manage_products.php" method="GET" class="flex-grow md:mx-8">
            <div class="relative">
                <input type="text" id="search-input" name="q" placeholder="Search products by name..." value="<?= htmlspecialchars($search_term) ?>" class="w-full pl-10 pr-4 py-2 border rounded-full shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="absolute top-0 left-0 inline-flex items-center p-2 h-full">
                     <i data-feather="search" class="text-gray-400"></i>
                </div>
            </div>
        </form>

        <a href="add_product.php" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-full hover:bg-blue-700 transition duration-300 whitespace-nowrap">
            + Add New Product
        </a>
    </div>

    <?= $message ?>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody id="product-table-body" class="divide-y divide-gray-200">
                <!-- This section is now populated by PHP on initial load -->
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-10 text-gray-500">
                            <?= !empty($search_term) ? 'No products found for "' . htmlspecialchars($search_term) . '".' : 'No products found.' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td class="py-4 px-4">
                                <?php
                                $image_url = htmlspecialchars($product['image_url']);
                                $image_src = (strpos($image_url, 'http') === 0) ? $image_url : $base_url . $image_url;
                                ?>
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="<?= $image_src ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-16 h-16 object-cover rounded-md">
                                <?php else: ?>
                                    <div class="w-16 h-16 bg-gray-200 rounded-md flex items-center justify-center text-xs text-gray-500">No Image</div>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></td>
                            <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($product['category']) ?></td>
                            <td class="py-4 px-4 text-gray-800">$<?= number_format($product['price'], 2) ?></td>
                            <td class="py-4 px-4 text-gray-800"><?= $product['stock_quantity'] ?></td>
                            <td class="py-4 px-4 whitespace-nowrap text-sm font-medium">
                                <a href="edit_product.php?id=<?= $product['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-4">Edit</a>
                                <a href="delete_product.php?id=<?= $product['id'] ?>" class="text-red-600 hover:text-red-900 delete-btn">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Custom Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm mx-auto">
        <div class="text-center">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i data-feather="alert-triangle" class="h-6 w-6 text-red-600"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Delete Product</h3>
            <div class="mt-2">
                <p class="text-sm text-gray-500">
                    Are you sure you want to permanently delete this product? This action cannot be undone.
                </p>
            </div>
        </div>
        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
            <a id="confirm-delete-btn" href="#" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:col-start-2 sm:text-sm">
                Delete
            </a>
            <button id="cancel-delete-btn" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- Custom Delete Modal Logic ---
    const modal = document.getElementById('delete-modal');
    const confirmBtn = document.getElementById('confirm-delete-btn');
    const cancelBtn = document.getElementById('cancel-delete-btn');
    
    // We need to use event delegation because table rows are now dynamic
    document.getElementById('product-table-body').addEventListener('click', (e) => {
        if (e.target.classList.contains('delete-btn')) {
            e.preventDefault();
            const deleteUrl = e.target.href;
            confirmBtn.href = deleteUrl;
            modal.classList.remove('hidden');
            feather.replace(); // Redraw icons in the modal
        }
    });

    cancelBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
    });
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });

    // --- Live Search Logic ---
    const searchInput = document.getElementById('search-input');
    const productTableBody = document.getElementById('product-table-body');
    const searchForm = document.getElementById('search-form');
    let debounceTimer;

    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
    });

    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        const searchTerm = e.target.value;

        debounceTimer = setTimeout(() => {
            productTableBody.innerHTML = `<tr><td colspan="6" class="text-center py-10 text-gray-500">
                <div class="flex justify-center items-center">
                    <i data-feather="loader" class="animate-spin mr-2"></i> Searching...
                </div>
            </td></tr>`;
            feather.replace();

            const url = new URL(window.location);
            url.searchParams.set('q', searchTerm);
            window.history.pushState({}, '', url);

            fetch(`manage_products.php?q=${encodeURIComponent(searchTerm)}&ajax=1`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.text();
                })
                .then(html => {
                    productTableBody.innerHTML = html;
                })
                .catch(error => {
                    console.error('Search failed:', error);
                    productTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-10 text-red-500">Error loading search results.</td></tr>';
                });
        }, 300);
    });
});
</script>

<?php include 'footer.php'; ?>

