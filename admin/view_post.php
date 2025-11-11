<?php
// admin/view_post.php
// This page allows an admin to view a full blog post and moderate it.

include 'header.php';
require_once __DIR__ . '/../db.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($post_id === 0) {
    header('Location: manage_posts.php');
    exit;
}

$message = '';
if (isset($_GET['status_updated'])) {
    $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6'>Post status has been updated successfully.</div>";
}

try {
    // Fetch post details, regardless of status
    $sql = "SELECT bp.*, u.name as author_name 
            FROM blog_posts bp JOIN users u ON bp.user_id = u.id 
            WHERE bp.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        header('Location: manage_posts.php');
        exit;
    }
} catch (PDOException $e) {
    die("Database error: Could not fetch post details.");
}

function getStatusClass($status) {
    return match ($status) {
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        default => 'bg-yellow-100 text-yellow-800',
    };
}
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white p-8 rounded-lg shadow-md">
        <!-- Header: Back Link and Status -->
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <div>
                <a href="manage_posts.php" class="text-blue-600 hover:underline">&larr; Back to All Posts</a>
                <h1 class="text-3xl font-bold text-gray-800 mt-2"><?= htmlspecialchars($post['title']) ?></h1>
            </div>
            <div>
                <span class="font-semibold px-3 py-1 rounded-full <?= getStatusClass($post['status']) ?>"><?= ucfirst($post['status']) ?></span>
            </div>
        </div>

        <?= $message ?>

        <!-- Post Details -->
        <div class="mb-8">
            <p class="text-sm text-gray-600">
                <strong>Author:</strong> <?= htmlspecialchars($post['author_name']) ?> | 
                <strong>Submitted on:</strong> <?= date('F j, Y', strtotime($post['created_at'])) ?>
            </p>
        </div>

        <?php if (!empty($post['image_url'])): ?>
            <img src="../<?= htmlspecialchars($post['image_url']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-auto max-h-[500px] object-cover rounded-lg mb-8">
        <?php endif; ?>

        <!-- Post Content -->
        <div class="prose max-w-none text-gray-700 mb-8">
            <?= nl2br(htmlspecialchars($post['content'])) ?>
        </div>

        <!-- Admin Moderation Actions -->
        <?php if ($post['status'] === 'pending'): ?>
        <div class="mt-8 pt-6 border-t">
            <h2 class="text-lg font-semibold mb-4">Moderation Actions</h2>
            <div class="flex items-center space-x-4">
                <form action="update_post_status.php" method="POST">
                    <input type="hidden" name="id" value="<?= $post['id'] ?>">
                    <button type="submit" name="action" value="approve" class="bg-green-500 text-white font-bold py-2 px-4 rounded-md hover:bg-green-600">Approve Post</button>
                </form>
                <form action="update_post_status.php" method="POST">
                    <input type="hidden" name="id" value="<?= $post['id'] ?>">
                    <button type="submit" name="action" value="reject" class="bg-yellow-500 text-white font-bold py-2 px-4 rounded-md hover:bg-yellow-600">Reject Post</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
