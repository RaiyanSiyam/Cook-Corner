<?php
// settings.php
include 'header.php';
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch current user info
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle messages from the update script
$message = $_SESSION['message'] ?? null;
$message_type = $_SESSION['message_type'] ?? 'success';
unset($_SESSION['message'], $_SESSION['message_type']);
?>
<main class="gradient-bg py-12">
    <div class="container mx-auto px-4 max-w-2xl">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-extrabold text-gray-800">Account Settings</h1>
            <p class="mt-4 text-lg text-gray-600">Manage your profile and password.</p>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-md <?= $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-lg p-8 space-y-8">
            <!-- Update Profile Form -->
            <form action="update_settings.php" method="POST">
                <input type="hidden" name="action" value="update_profile">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Update Profile</h2>
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($user['name']) ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address (cannot be changed)</label>
                        <input type="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" readonly class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100">
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-md hover:bg-blue-700">Save Changes</button>
                </div>
            </form>

            <hr>

            <!-- Change Password Form -->
            <form action="update_settings.php" method="POST">
                <input type="hidden" name="action" value="update_password">
                 <h2 class="text-2xl font-bold text-gray-800 mb-4">Change Password</h2>
                <div class="space-y-4">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                        <input type="password" name="current_password" id="current_password" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                     <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" name="new_password" id="new_password" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                     <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-md hover:bg-blue-700">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>
