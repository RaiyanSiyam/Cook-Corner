<?php
// admin/add_product.php
// FINAL FIX: This version adds a pre-check to prevent duplicate product names,
// addressing the "Integrity constraint violation" error.

require_once __DIR__ . '/../db.php';
$message = '';

// --- 1. PROCESS FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_dir = __DIR__ . '/../uploads/';
    $image_path = null;
    $error_log = []; 

    // --- Sanitize and retrieve text data ---
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock_quantity = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT);
    $category = trim($_POST['category'] ?? '');

    // --- Step 1: Validate Text Fields and Check for Duplicates ---
    if (empty($name) || $price === false || $stock_quantity === false || empty($category)) {
        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Error: Please fill in all required fields.</div>";
    } elseif ($price <= 0 || $stock_quantity < 0) {
        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Error: Price and stock must not be negative.</div>";
    } else {
        // *** NEW: DUPLICATE NAME CHECK ***
        try {
            $stmt = $pdo->prepare("SELECT id FROM products WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Error: A product with the name '" . htmlspecialchars($name) . "' already exists.</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Database error during duplicate check.</div>";
            error_log("Duplicate check failed: " . $e->getMessage());
        }
        // *** END OF NEW CHECK ***

        // --- Step 2: Handle File Upload (only if no errors so far) ---
        if (empty($message)) {
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir_writable = is_writable($upload_dir);
                if (!$upload_dir_writable) {
                     $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'><b>Permission Error:</b> The server cannot write to the 'uploads' directory. Please check its file permissions.</div>";
                } else {
                    $file_tmp_path = $_FILES['product_image']['tmp_name'];
                    $file_name = basename($_FILES['product_image']['name']);
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($file_ext, $allowed_extensions)) {
                        $new_file_name = uniqid('product_', true) . '.' . $file_ext;
                        $dest_path = $upload_dir . $new_file_name;
                        if (move_uploaded_file($file_tmp_path, $dest_path)) {
                            $image_path = 'uploads/' . $new_file_name;
                        } else {
                            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Error: Could not move the uploaded file. Check server permissions.</div>";
                        }
                    } else {
                        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Error: Invalid file type. Only JPG, PNG, and GIF are allowed.</div>";
                    }
                }
            } else {
                $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Error: A product image is required and must be uploaded successfully.</div>";
            }
        }

        // --- Step 3: Database Insertion (only if all previous steps were successful) ---
        if (empty($message) && $image_path) {
            try {
                $sql = "INSERT INTO products (name, description, price, stock_quantity, category, image_url) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $description, $price, $stock_quantity, $category, $image_path]);
                header("Location: manage_products.php?success=1");
                exit;
            } catch (PDOException $e) {
                // This will catch the duplicate error again as a final safety net
                $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Database error: Could not add the product. Ensure the name is unique.</div>";
                error_log("Add Product DB Error: " . $e->getMessage());
            }
        }
    }
}

// --- DISPLAY THE PAGE & FORM ---
include 'header.php';
?>

<div class="bg-white p-8 rounded-lg shadow-md max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Add New Product</h1>
        <a href="manage_products.php" class="text-blue-600 hover:underline">&larr; Back to Products</a>
    </div>

    <?= $message ?>

    <form action="add_product.php" method="POST" enctype="multipart/form-data">
        <div class="space-y-6">
            <!-- Product Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Product Name</label>
                <input type="text" id="name" name="name" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Product Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Product Description</label>
                <textarea id="description" name="description" rows="4" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Price -->
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">Price ($)</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <!-- Stock Quantity -->
                <div>
                    <label for="stock_quantity" class="block text-sm font-medium text-gray-700">Stock Quantity</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" min="0" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Category -->
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                <input type="text" id="category" name="category" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., Kitchenware, Pantry, Gadgets">
            </div>

            <!-- Image Upload -->
            <div>
                <label for="product_image" class="block text-sm font-medium text-gray-700">Product Image</label>
                <input type="file" id="product_image" name="product_image" required accept="image/png, image/jpeg, image/gif" class="mt-1 block w-full text-sm text-gray-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-full file:border-0
                    file:text-sm file:font-semibold
                    file:bg-blue-50 file:text-blue-700
                    hover:file:bg-blue-100
                ">
                <p class="mt-1 text-xs text-gray-500">A product image is required.</p>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4">
                <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Add Product
                </button>
            </div>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>

