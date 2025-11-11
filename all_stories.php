<?php
// all_stories.php
// This is the redesigned, more beautiful version of the community stories page.

include 'header.php';
require_once __DIR__ . '/db.php';

try {
    // Fetch all 'approved' blog posts, ordered by the most recent
    $sql = "SELECT bp.id, bp.title, bp.content, bp.image_url, bp.created_at, u.name as author_name
            FROM blog_posts bp
            JOIN users u ON bp.user_id = u.id
            WHERE bp.status = 'approved'
            ORDER BY bp.created_at DESC";
    $stmt = $pdo->query($sql);
    $posts = $stmt->fetchAll();

    // Separate the first post to be featured
    $featured_post = null;
    if (!empty($posts)) {
        $featured_post = array_shift($posts); // Gets the first item and removes it from the array
    }

} catch (PDOException $e) {
    $posts = [];
    $featured_post = null;
    error_log("Error fetching all blog posts: " . $e->getMessage());
}
?>

<main class="bg-gray-50 py-12">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12" data-aos="fade-down">
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800">All Community Stories</h1>
            <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">Discover the latest heartwarming tales and kitchen triumphs from your fellow food lovers.</p>
        </div>

        <?php if ($featured_post): ?>
            <!-- Featured Post Section -->
            <section class="mb-16" data-aos="fade-up">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Latest Story</h2>
                <a href="view_post.php?id=<?= $featured_post['id'] ?>" class="block bg-white rounded-lg shadow-lg overflow-hidden group transform hover:shadow-2xl transition-shadow duration-300">
                    <div class="md:flex">
                        <div class="md:w-1/2">
                            <img class="h-64 w-full object-cover md:h-full" src="<?= htmlspecialchars($featured_post['image_url']) ?>" alt="<?= htmlspecialchars($featured_post['title']) ?>">
                        </div>
                        <div class="md:w-1/2 p-8 lg:p-12 flex flex-col justify-center">
                            <h3 class="text-2xl md:text-3xl font-bold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($featured_post['title']) ?></h3>
                            <p class="text-gray-600 mb-4 text-sm leading-relaxed"><?= htmlspecialchars(substr($featured_post['content'], 0, 200)) ?>...</p>
                            <div class="flex items-center text-xs text-gray-500">
                                <i data-feather="user" class="w-4 h-4 mr-2"></i>
                                <span>By <?= htmlspecialchars($featured_post['author_name']) ?></span>
                                <span class="mx-2">&bull;</span>
                                <i data-feather="clock" class="w-4 h-4 mr-2"></i>
                                <span><?= date('F j, Y', strtotime($featured_post['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                </a>
            </section>
        <?php endif; ?>

        <!-- More Stories Grid -->
        <section>
             <div class="flex justify-between items-center mb-8">
                 <h2 class="text-2xl font-bold text-gray-800">More Stories</h2>
                 <a href="add_post.php" class="bg-blue-600 text-white font-bold py-2 px-5 rounded-full hover:bg-blue-700 transition duration-300 flex items-center">
                    <i data-feather="plus" class="w-5 h-5 mr-2"></i> Share Yours
                </a>
            </div>
            
            <?php if (empty($posts) && empty($featured_post)): ?>
                <div class="text-center bg-white p-12 rounded-lg shadow-md">
                    <p class="text-xl text-gray-600">No stories have been shared yet. Be the first!</p>
                </div>
            <?php elseif (empty($posts) && !empty($featured_post)): ?>
                 <div class="text-center bg-white p-12 rounded-lg shadow-md">
                    <p class="text-gray-500">No other stories have been shared yet.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($posts as $post): ?>
                    <a href="view_post.php?id=<?= $post['id'] ?>" class="block bg-white rounded-lg shadow-md overflow-hidden transform hover:-translate-y-1.5 transition duration-300 group" data-aos="fade-up">
                        <div class="h-48 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($post['image_url']) ?>');"></div>
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-2 group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($post['title']) ?></h3>
                            <div class="flex items-center text-xs text-gray-500">
                                <span>By <?= htmlspecialchars($post['author_name']) ?></span>
                                <span class="mx-2">&bull;</span>
                                <span><?= date('M j, Y', strtotime($post['created_at'])) ?></span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include 'footer.php'; ?>

