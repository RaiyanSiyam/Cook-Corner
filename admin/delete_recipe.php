<?php
// admin/delete_recipe.php
// This script handles the permanent, destructive deletion of a recipe.
// It temporarily disables foreign key checks to ensure deletion is successful.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../db.php';

// 1. Get the recipe ID from the URL
$recipe_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($recipe_id === 0) {
    header('Location: manage_recipes.php?error=invalid_id');
    exit;
}

try {
    // 2. Get the image path FIRST to delete the physical file later
    $stmt = $pdo->prepare("SELECT image_url FROM recipes WHERE id = ?");
    $stmt->execute([$recipe_id]);
    $recipe = $stmt->fetch();

    // 3. Begin a transaction for safety
    $pdo->beginTransaction();

    // 4. Temporarily disable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');

    // 5. Perform the permanent deletion of the recipe
    // The database will automatically handle related items in `recipe_ingredients` etc. due to CASCADE rules.
    $stmt = $pdo->prepare("DELETE FROM recipes WHERE id = ?");
    $stmt->execute([$recipe_id]);

    // 6. Re-enable the foreign key checks immediately
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');

    // 7. Commit the transaction
    $pdo->commit();

    // 8. Delete the physical image file from the server
    if ($recipe && !empty($recipe['image_url']) && strpos($recipe['image_url'], 'http') !== 0) {
        $image_file_path = __DIR__ . '/../' . $recipe['image_url'];
        if (file_exists($image_file_path)) {
            unlink($image_file_path);
        }
    }

    // 9. Redirect back with a success message
    header('Location: manage_recipes.php?deleted=1');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    error_log("Recipe deletion failed: " . $e->getMessage());
    header('Location: manage_recipes.php?error=db_error');
    exit;
}
?>
