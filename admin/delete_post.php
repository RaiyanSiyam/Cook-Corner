<?php
// admin/delete_post.php
// A dedicated, standalone script to permanently delete a blog post.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../db.php';

// 1. Get the post ID from the URL
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id === 0) {
    header('Location: manage_posts.php?error=invalid_id');
    exit;
}

try {
    // 2. Get the image path first to delete the physical file later
    $stmt = $pdo->prepare("SELECT image_url FROM blog_posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    // 3. Use the "master key" method to ensure deletion succeeds
    $pdo->beginTransaction();
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');

    $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->execute([$post_id]);

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    $pdo->commit();

    // 4. Delete the physical image file from the server, if it exists
    if ($post && !empty($post['image_url']) && strpos($post['image_url'], 'http') !== 0) {
        $image_file_path = __DIR__ . '/../' . $post['image_url'];
        if (file_exists($image_file_path)) {
            @unlink($image_file_path); // Use @ to suppress warnings if file is not found
        }
    }

    // 5. Redirect back with a success message
    header('Location: manage_posts.php?deleted=1');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Ensure checks are re-enabled even on error
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    error_log("Blog post deletion failed: " . $e->getMessage());
    header("Location: manage_posts.php?error=db_error");
    exit;
}
?>
