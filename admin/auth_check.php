<?php
// admin/auth_check.php
// Definitive Fix: This script now verifies the user's admin status directly 
// from the database on every page load for maximum security and reliability.

// Ensure a session is active.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- STEP 1: Check if a user is logged in at all ---
if (!isset($_SESSION['user_id'])) {
    // If no user is logged in, redirect to the login page.
    header('Location: ../login.html');
    exit;
}

// --- STEP 2: Verify the logged-in user's admin status from the database ---
try {
    // Include the database connection.
    require_once __DIR__ . '/../db.php';

    // Prepare a query to get the user's admin status.
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // --- STEP 3: Make the final decision ---
    // If the user was not found in the DB, or if their is_admin flag is NOT 1,
    // they are not a valid admin.
    if (!$user || $user['is_admin'] != 1) {
        // Destroy the potentially invalid session and redirect.
        session_destroy();
        header('Location: ../login.html');
        exit;
    }

    // If we reach this point, the user is a logged-in, verified admin.
    // We can also ensure the session variable is correctly set for other scripts.
    $_SESSION['is_admin'] = true;

} catch (PDOException $e) {
    // If there's a database error, it's safer to deny access.
    // Log the error for debugging.
    error_log("Admin auth check failed: " . $e->getMessage());
    session_destroy();
    header('Location: ../login.html');
    exit;
}
?>

