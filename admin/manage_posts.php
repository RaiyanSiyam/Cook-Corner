<?php
// admin/manage_posts.php
// This version has a working delete button with a custom, site-styled confirmation modal.

include 'header.php';
require_once __DIR__ . '/../db.php';

$message = '';
if (isset($_GET['status_updated'])) $message = "<div class='bg-green-100 text-green-700 p-4 rounded-lg mb-6'>Post status updated.</div>";
if (isset($_GET['deleted'])) $message = "<div class='bg-green-100 text-green-700 p-4 rounded-lg mb-6'>Post permanently deleted.</div>";

try {
    $sql = "SELECT bp.id, bp.title, bp.status, bp.created_at, u.name as author_name
            FROM blog_posts bp JOIN users u ON bp.user_id = u.id
            ORDER BY bp.created_at DESC";
    $stmt = $pdo->query($sql);
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $posts = [];
    $message = "<div class='bg-red-100 text-red-700 p-4 rounded-lg mb-6'>Could not fetch posts.</div>";
}

function getStatusClass($status) {
    return match ($status) {
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        default => 'bg-yellow-100 text-yellow-800',
    };
}
?>
<div class="bg-white p-8 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6">Manage Blog Posts</h1>
    <?= $message ?>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Author</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="py-3 px-4 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody id="posts-table-body" class="divide-y">
                <?php foreach ($posts as $post): ?>
                <tr>
                    <td class="py-4 px-4 font-medium"><?= htmlspecialchars($post['title']) ?></td>
                    <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($post['author_name']) ?></td>
                    <td class="py-4 px-4 text-gray-500"><?= date('M j, Y', strtotime($post['created_at'])) ?></td>
                    <td class="py-4 px-4"><span class="px-2 py-1 text-xs font-semibold rounded-full <?= getStatusClass($post['status']) ?>"><?= ucfirst($post['status']) ?></span></td>
                    <td class="py-4 px-4 text-right text-sm space-x-4">
                        <a href="view_post.php?id=<?= $post['id'] ?>" class="text-blue-600 font-bold">View & Moderate</a>
                        <!-- UPDATED: Removed onclick and added a class for the modal -->
                        <a href="delete_post.php?id=<?= $post['id'] ?>" class="text-red-600 font-bold delete-btn">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Custom Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm mx-auto">
        <div class="text-center">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i data-feather="alert-triangle" class="h-6 w-6 text-red-600"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Delete Post</h3>
            <p class="text-sm text-gray-500 mt-2">Are you sure you want to permanently delete this post? This action cannot be undone.</p>
        </div>
        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3">
            <a id="confirm-delete-btn" href="#" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700">
                Delete
            </a>
            <button id="cancel-delete-btn" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('delete-modal');
    const confirmBtn = document.getElementById('confirm-delete-btn');
    const cancelBtn = document.getElementById('cancel-delete-btn');
    
    // Use event delegation on the table body
    document.getElementById('posts-table-body').addEventListener('click', (e) => {
        // Find the closest delete button link from the click target
        const deleteLink = e.target.closest('.delete-btn');
        
        if (deleteLink) {
            e.preventDefault(); // Stop the link from navigating immediately
            
            // Get the URL from the clicked link and set it on the modal's confirm button
            confirmBtn.href = deleteLink.href;
            
            // Show the modal
            modal.classList.remove('hidden');
            feather.replace();
        }
    });

    // Hide the modal when the cancel button is clicked
    cancelBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
    });

    // Hide the modal if the background overlay is clicked
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
});
</script>

<?php include 'footer.php'; ?>

