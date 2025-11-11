<?php
// admin/edit_user.php
// This page allows editing a user's details and assigning roles.

include 'header.php';
require_once __DIR__ . '/../db.php';

$user_id_to_edit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_user_id = $_SESSION['user_id'];
$message = '';

if ($user_id_to_edit === 0) {
    header('Location: manage_users.php');
    exit;
}

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $is_admin = isset($_POST['is_admin']) ? (int)$_POST['is_admin'] : 0;

    // Safety check: Prevent user from removing their own admin role
    if ($user_id_to_edit === $current_user_id && $is_admin == 0) {
        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Error: You cannot remove your own admin status.</div>";
    } elseif (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Error: Please provide a valid name and email.</div>";
    } else {
        try {
            $sql = "UPDATE users SET name = ?, email = ?, is_admin = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $email, $is_admin, $user_id_to_edit]);

            $success_redirect_url = 'manage_users.php?updated=1';
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6'><strong>Success!</strong> User has been updated. <a href='{$success_redirect_url}' class='font-bold underline hover:text-green-900'>Return to User List</a>.</div>";
        } catch (PDOException $e) {
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Database error: Could not update user. The email may already be in use.</div>";
        }
    }
}

// --- FETCH USER DATA ---
try {
    $stmt = $pdo->prepare("SELECT id, name, email, is_admin FROM users WHERE id = ?");
    $stmt->execute([$user_id_to_edit]);
    $user = $stmt->fetch();
    if (!$user) {
        header('Location: manage_users.php');
        exit;
    }
} catch (PDOException $e) {
    die("Database error: Could not fetch user data.");
}
?>

<div class="bg-white p-8 rounded-lg shadow-md max-w-xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Edit User</h1>
        <a href="manage_users.php" class="text-blue-600 hover:underline">&larr; Back to Users</a>
    </div>

    <?= $message ?>

    <form action="edit_user.php?id=<?= $user_id_to_edit ?>" method="POST">
        <div class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($user['name']) ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Role</label>
                <select id="is_admin" name="is_admin" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                    <option value="0" <?= !$user['is_admin'] ? 'selected' : '' ?>>User</option>
                    <option value="1" <?= $user['is_admin'] ? 'selected' : '' ?>>Admin</option>
                </select>
                 <?php if ($user['id'] === $current_user_id): ?>
                    <p class="mt-2 text-xs text-yellow-600">Note: You cannot revoke your own admin status.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex justify-end pt-8 mt-8 border-t">
            <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                Update User
            </button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
