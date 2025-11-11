<?php
// view_post.php
// Displays a single, full blog post.

include 'header.php';
require_once __DIR__ . '/db.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($post_id === 0) {
    header('Location: blog.php');
    exit;
}

try {
    $sql = "SELECT bp.*, u.name as author_name 
            FROM blog_posts bp JOIN users u ON bp.user_id = u.id 
            WHERE bp.id = ? AND bp.status = 'approved'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        // Post not found or not approved, redirect
        header('Location: blog.php');
        exit;
    }
} catch (PDOException $e) {
    die("Error fetching post.");
}
?>

<main class="py-12 bg-gray-50">
    <div class="container mx-auto px-4 max-w-3xl">
        <article class="bg-white p-8 rounded-lg shadow-md">
            <?php if (!empty($post['image_url'])): ?>
                <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-80 object-cover rounded-lg mb-8">
            <?php endif; ?>
            
            <h1 class="text-4xl font-extrabold text-gray-900 mb-4"><?= htmlspecialchars($post['title']) ?></h1>
            
            <div class="flex items-center text-sm text-gray-500 mb-8 border-b pb-4">
                <i data-feather="user" class="w-4 h-4 mr-2"></i>
                <span>By <?= htmlspecialchars($post['author_name']) ?></span>
                <span class="mx-2">&bull;</span>
                <i data-feather="clock" class="w-4 h-4 mr-2"></i>
                <span><?= date('F j, Y', strtotime($post['created_at'])) ?></span>
            </div>

            <div class="prose max-w-none text-gray-700">
                <?= nl2br(htmlspecialchars($post['content'])) // nl2br converts newlines to <br> tags ?>
            </div>
        </article>
    </div>
</main>

<?php include 'footer.php'; ?>
