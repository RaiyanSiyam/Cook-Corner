<?php
// admin/edit_product.php
// This version includes the intelligent URL check for the current image preview.

include 'header.php';
require_once __DIR__ . '/../db.php';

// --- Build a reliable base URL for local images ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_root = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\'); 
$base_url = $protocol . $host . $project_root . '/';


$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

if ($product_id === 0) {
    header('Location: manage_products.php');
    exit;
}

// --- HANDLE FORM SUBMISSION (UPDATE LOGIC) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve form data
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock_quantity = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT);
    $category = trim($_POST['category'] ?? '');
    $current_image_path = $_POST['current_image_path'] ?? '';

    if (empty($name) || $price === false || $stock_quantity === false || empty($category)) {
        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Error: Please fill in all required fields.</div>";
    } else {
        $new_image_path = $current_image_path;

        // Handle new image upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/';
            $file_tmp_path = $_FILES['product_image']['tmp_name'];
            $file_ext = strtolower(pathinfo(basename($_FILES['product_image']['name']), PATHINFO_EXTENSION));
            $new_file_name = uniqid('product_', true) . '.' . $file_ext;
            $dest_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_path, $dest_path)) {
                $new_image_path = 'uploads/' . $new_file_name;
                // Delete the old image file ONLY if it was a local file
                if (!empty($current_image_path) && strpos($current_image_path, 'http') !== 0 && file_exists(__DIR__ . '/../' . $current_image_path)) {
                    unlink(__DIR__ . '/../' . $current_image_path);
                }
            } else {
                 $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Error: Could not upload new image.</div>";
            }
        }

        if (empty($message)) {
            try {
                $sql = "UPDATE products SET name = ?, description = ?, price = ?, stock_quantity = ?, category = ?, image_url = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $description, $price, $stock_quantity, $category, $new_image_path, $product_id]);

                $success_redirect_url = 'manage_products.php?updated=1';
                $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6'><strong>Success!</strong> The product has been updated. <a href='{$success_redirect_url}' class='font-bold underline hover:text-green-900'>Return to Product List</a>.</div>";

            } catch (PDOException $e) {
                 $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Database error: Could not update product.</div>";
                 error_log("Product update failed: " . $e->getMessage());
            }
        }
    }
}


// --- FETCH EXISTING PRODUCT DATA ---
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        header('Location: manage_products.php');
        exit;
    }
} catch (PDOException $e) {
    die("Database error: Could not fetch product data.");
}

?>

<div class="bg-white p-8 rounded-lg shadow-md max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Edit Product</h1>
        <a href="manage_products.php" class="text-blue-600 hover:underline">&larr; Back to Products</a>
    </div>

    <?= $message ?>

    <form action="edit_product.php?id=<?= $product_id ?>" method="POST" enctype="multipart/form-data">
        <div class="space-y-6">
            <!-- Product Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Product Name</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($product['name']) ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm">
            </div>

            <!-- Product Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Product Description</label>
                <textarea id="description" name="description" rows="4" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Price -->
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">Price ($)</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required value="<?= htmlspecialchars($product['price']) ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm">
                </div>
                <!-- Stock Quantity -->
                <div>
                    <label for="stock_quantity" class="block text-sm font-medium text-gray-700">Stock Quantity</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" min="0" required value="<?= htmlspecialchars($product['stock_quantity']) ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm">
                </div>
            </div>

            <!-- Category -->
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                <input type="text" id="category" name="category" required value="<?= htmlspecialchars($product['category']) ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm">
            </div>

            <!-- Current Image -->
            <div>
                 <label class="block text-sm font-medium text-gray-700">Current Image</label>
                 <?php
                    // --- THE FIX IS HERE: Intelligent URL checking ---
                    $image_url_display = htmlspecialchars($product['image_url']);
                    if (strpos($image_url_display, 'http') === 0) {
                        $image_src_display = $image_url_display;
                    } else {
                        $image_src_display = $base_url . $image_url_display;
                    }
                 ?>
                 <img src="<?= $image_src_display ?>" alt="Current Image" class="mt-2 w-32 h-32 object-cover rounded-md border">
                 <input type="hidden" name="current_image_path" value="<?= htmlspecialchars($product['image_url']) ?>">
            </div>

            <!-- New Image Upload -->
            <div>
                <label for="product_image" class="block text-sm font-medium text-gray-700">Upload New Image (Optional)</label>
                <input type="file" id="product_image" name="product_image" accept="image/png, image/jpeg, image/gif" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="mt-1 text-xs text-gray-500">Leave this empty to keep the current image. Replaces both local and URL images.</p>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4">
                <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Update Product
                </button>
            </div>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>

