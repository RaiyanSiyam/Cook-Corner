<?php
// admin/manage_users.php
// Fully upgraded with live search, dynamic actions, and role management.

require_once __DIR__ . '/../db.php';

$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    $sql = "SELECT id, name, email, created_at, is_admin FROM users";
    $params = [];
    if (!empty($search_term)) {
        $sql .= " WHERE name LIKE ? OR email LIKE ?";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
    }
    $sql .= " ORDER BY is_admin DESC, created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    if (isset($_GET['ajax'])) {
        echo '<tr><td colspan="5" class="text-center py-10 text-red-500">Database Error.</td></tr>';
        exit;
    }
    $users = [];
    $message = "<div class='text-red-500 text-center p-6'>Error fetching users.</div>";
}

// --- Handle AJAX requests for live search ---
if (isset($_GET['ajax'])) {
    if (empty($users)) {
        echo '<tr><td colspan="5" class="text-center py-10 text-gray-500">No users found.</td></tr>';
    } else {
        foreach ($users as $user) {
            echo '<tr>';
            echo '<td class="py-4 px-4 whitespace-nowrap font-medium text-gray-900">' . htmlspecialchars($user['name']) . '</td>';
            echo '<td class="py-4 px-4 text-gray-600">' . htmlspecialchars($user['email']) . '</td>';
            echo '<td class="py-4 px-4 text-gray-600">' . date('M d, Y', strtotime($user['created_at'])) . '</td>';
            echo '<td class="py-4 px-4">';
            if ($user['is_admin']) {
                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Admin</span>';
            } else {
                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">User</span>';
            }
            echo '</td>';
            echo '<td class="py-4 px-4 whitespace-nowrap text-sm font-medium text-right">';
            echo '<a href="edit_user.php?id=' . $user['id'] . '" class="text-blue-600 hover:text-blue-900 mr-4">Edit</a>';
            if ($_SESSION['user_id'] != $user['id']) { // Don't show delete button for self
                echo '<a href="delete_user.php?id=' . $user['id'] . '" class="text-red-600 hover:text-red-900 delete-btn">Delete</a>';
            }
            echo '</td>';
            echo '</tr>';
        }
    }
    exit;
}

include 'header.php';

if (!isset($message)) {
    $message = '';
    if (isset($_GET['updated'])) $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6'>User updated successfully.</div>";
    if (isset($_GET['deleted'])) $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6'>User permanently deleted.</div>";
    if (isset($_GET['error'])) {
        $error_type = $_GET['error'];
        if ($error_type === 'self_delete') {
            $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Error: You cannot delete your own account.</div>";
        }
    }
}
?>

<div class="bg-white p-8 rounded-lg shadow-md">
    <div class="md:flex justify-between items-center mb-6 space-y-4 md:space-y-0">
        <h1 class="text-2xl font-bold text-gray-800">Manage Users</h1>
        <form id="search-form" action="manage_users.php" method="GET" class="flex-grow md:mx-8">
            <div class="relative">
                <input type="text" id="search-input" name="q" placeholder="Search by name or email..." value="<?= htmlspecialchars($search_term) ?>" class="w-full pl-10 pr-4 py-2 border rounded-full shadow-sm">
            </div>
        </form>
    </div>

    <?= $message ?>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                    <th class="py-3 px-4 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody id="user-table-body" class="divide-y divide-gray-200">
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="py-4 px-4 whitespace-nowrap font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></td>
                        <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($user['email']) ?></td>
                        <td class="py-4 px-4 text-gray-600"><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                        <td class="py-4 px-4">
                            <?php if ($user['is_admin']): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Admin</span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">User</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 px-4 whitespace-nowrap text-sm font-medium text-right">
                            <a href="edit_user.php?id=<?= $user['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-4">Edit</a>
                            <?php if ($_SESSION['user_id'] != $user['id']): // Don't show delete button for self ?>
                                <a href="delete_user.php?id=<?= $user['id'] ?>" class="text-red-600 hover:text-red-900 delete-btn">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm mx-auto">
        <h3 class="text-lg font-medium text-gray-900">Delete User</h3>
        <p class="text-sm text-gray-500 mt-2">Are you sure? This will permanently delete the user and cannot be undone.</p>
        <div class="mt-5 sm:grid sm:grid-cols-2 sm:gap-3">
            <a id="confirm-delete-btn" href="#" class="w-full inline-flex justify-center rounded-md bg-red-600 text-white px-4 py-2">Delete</a>
            <button id="cancel-delete-btn" type="button" class="w-full inline-flex justify-center rounded-md bg-white text-gray-700 px-4 py-2 border">Cancel</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Modal Logic
    const modal = document.getElementById('delete-modal');
    document.getElementById('user-table-body').addEventListener('click', (e) => {
        if (e.target.classList.contains('delete-btn')) {
            e.preventDefault();
            document.getElementById('confirm-delete-btn').href = e.target.href;
            modal.classList.remove('hidden');
        }
    });
    document.getElementById('cancel-delete-btn').addEventListener('click', () => modal.classList.add('hidden'));

    // Live Search Logic
    const searchInput = document.getElementById('search-input');
    const userTableBody = document.getElementById('user-table-body');
    let debounceTimer;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const searchTerm = e.target.value;
            fetch(`manage_users.php?q=${encodeURIComponent(searchTerm)}&ajax=1`)
                .then(response => response.text())
                .then(html => { userTableBody.innerHTML = html; });
        }, 300);
    });
});
</script>

<?php include 'footer.php'; ?>
