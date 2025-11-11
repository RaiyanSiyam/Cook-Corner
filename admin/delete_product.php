<?php
// admin/delete_product.php
// IMPROVED VERSION: Enhanced error handling and transaction safety

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../db.php';

// 1. Get the product ID from the URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id === 0) {
    $_SESSION['error'] = 'Invalid product ID';
    header('Location: manage_products.php');
    exit;
}

try {
    // 2. Begin a transaction for safety
    $pdo->beginTransaction();

    // 3. Get the image path FIRST, before deleting anything
    $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        throw new Exception('Product not found');
    }

    $image_file_path = null;
    if ($product && !empty($product['image_url'])) {
        $image_file_path = __DIR__ . '/../' . $product['image_url'];
    }

    // 4. Temporarily disable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    // 5. Delete related records from cart_items first
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE product_id = ?");
    $stmt->execute([$product_id]);

    // 6. Delete related records from order_items
    $stmt = $pdo->prepare("DELETE FROM order_items WHERE product_id = ?");
    $stmt->execute([$product_id]);

    // 7. Perform the permanent deletion of the product
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);

    // 8. Re-enable foreign key checks immediately
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    // 9. Commit the transaction to make the deletion permanent
    $pdo->commit();

    // 10. Now that the DB operation is successful, delete the physical image file
    if ($image_file_path && file_exists($image_file_path)) {
        if (!unlink($image_file_path)) {
            error_log("Warning: Could not delete image file: " . $image_file_path);
        }
    }

    // 11. Redirect back with a success message
    $_SESSION['success'] = 'Product deleted successfully';
    header('Location: manage_products.php');
    exit;

} catch (Exception $e) {
    // If anything goes wrong, roll back the database changes
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Crucially, re-enable checks even if there's an error
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    } catch (Exception $fkError) {
        error_log("Failed to re-enable foreign key checks: " . $fkError->getMessage());
    }

    error_log("Product deletion failed: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to delete product: ' . $e->getMessage();
    header('Location: manage_products.php');
    exit;
}
?>