<?php
// update_settings.php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'update_profile') {
    $name = trim($_POST['name'] ?? '');
    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $user_id]);
            $_SESSION['user_name'] = $name; // Update session name
            $_SESSION['message'] = 'Profile updated successfully!';
            $_SESSION['message_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Error updating profile.';
            $_SESSION['message_type'] = 'error';
        }
    } else {
        $_SESSION['message'] = 'Name cannot be empty.';
        $_SESSION['message_type'] = 'error';
    }

} elseif ($action === 'update_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 8) {
        $_SESSION['message'] = 'New password must be at least 8 characters long.';
        $_SESSION['message_type'] = 'error';
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['message'] = 'New passwords do not match.';
        $_SESSION['message_type'] = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_password, $user['password_hash'])) {
                // Current password is correct, update to new password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $update_stmt->execute([$new_hash, $user_id]);
                $_SESSION['message'] = 'Password updated successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Incorrect current password.';
                $_SESSION['message_type'] = 'error';
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Error updating password.';
            $_SESSION['message_type'] = 'error';
        }
    }
}

header('Location: settings.php');
exit;
?>
