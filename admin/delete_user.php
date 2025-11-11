<?php
// admin/delete_user.php
// Handles the permanent deletion of a user account.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../db.php';

$user_id_to_delete = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_user_id = $_SESSION['user_id'];

// CRITICAL SAFETY CHECK: Prevent an admin from deleting their own account.
if ($user_id_to_delete === $current_user_id) {
    header('Location: manage_users.php?error=self_delete');
    exit;
}

if ($user_id_to_delete === 0) {
    header('Location: manage_users.php?error=invalid_id');
    exit;
}

try {
    // Temporarily disable foreign key checks to allow deletion even if the user has created recipes or orders.
    $pdo->beginTransaction();
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id_to_delete]);

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    $pdo->commit();

    header('Location: manage_users.php?deleted=1');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
    error_log("User deletion failed: " . $e->getMessage());
    header('Location: manage_users.php?error=db_error');
    exit;
}
?>
