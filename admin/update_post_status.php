<?php
// admin/update_post_status.php
// UPDATED: Now handles POST requests and redirects back to the detail view.

if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_posts.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($id === 0 || !in_array($action, ['approve', 'reject'])) {
    header('Location: manage_posts.php');
    exit;
}

$new_status = ($action === 'approve') ? 'approved' : 'rejected';

try {
    $sql = "UPDATE blog_posts SET status = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$new_status, $id]);
    
    // Redirect back to the view page to show the result of the action
    header('Location: view_post.php?id=' . $id . '&status_updated=1');
    exit;
} catch (PDOException $e) {
    header('Location: manage_posts.php?error=db_error');
    exit;
}
?>