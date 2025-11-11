<?php
// blog.php
// This is the new, beautifully designed introductory blog landing page.

include 'header.php';
require_once __DIR__ . '/db.php';

try {
    // Fetch the 3 most recent approved posts to feature
    $featured_sql = "SELECT bp.id, bp.title, bp.image_url, u.name as author_name
                     FROM blog_posts bp JOIN users u ON bp.user_id = u.id
                     WHERE bp.status = 'approved'
                     ORDER BY bp.created_at DESC LIMIT 3";
    $featured_posts = $pdo->query($featured_sql)->fetchAll();
} catch (PDOException $e) {
    $featured_posts = [];
    error_log("Error fetching featured posts: " . $e->getMessage());
}
?>

<main>
    <!-- 1. Hero Section -->
    <section class="relative h-[60vh] bg-cover bg-center flex items-center text-white" style="background-image: url('https://images.unsplash.com/photo-1495195129352-aeb325a55b65?q=80&w=2070');">
        <div class="absolute inset-0 bg-black bg-opacity-40"></div>
        <div class="container mx-auto px-4 text-center relative z-10" data-aos="fade-in">
            <h1 class="text-4xl md:text-6xl font-extrabold leading-tight">More Than a Meal</h1>
            <p class="mt-4 text-lg md:text-xl max-w-2xl mx-auto">It’s a story, a memory, a bond. It’s the thread that connects us all.</p>
        </div>
    </section>

    <!-- 2. Thematic Storytelling Sections -->
    <div class="bg-gray-50 py-16">
        <div class="container mx-auto px-4 space-y-16">
            <!-- Section: Food & Memory -->
            <section class="flex flex-col md:flex-row items-center gap-8 md:gap-12" data-aos="fade-up">
                <div class="md:w-1/2">
                    <img src="https://images.stockcake.com/public/5/5/d/55da09e9-aa01-49c9-b834-c30a17e6ac0f_large/cooking-with-grandma-stockcake.jpg" alt="Grandmother and child cooking together" class="rounded-lg shadow-xl w-full">
                </div>
                <div class="md:w-1/2">
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">A Taste of Yesterday</h2>
                    <p class="text-gray-600 leading-relaxed">A single bite can be a time machine. The scent of a freshly baked pie that reminds you of your grandmother's kitchen, the spice blend that brings back memories of a bustling market on a faraway trip. Food doesn't just feed us; it holds our most cherished moments.</p>
                </div>
            </section>

            <!-- Section: Food & Community -->
            <section class="flex flex-col md:flex-row-reverse items-center gap-8 md:gap-12" data-aos="fade-up">
                <div class="md:w-1/2">
                    <img src="https://media.istockphoto.com/id/1181396290/photo/people-laughing-at-dinner-table.jpg?s=612x612&w=0&k=20&c=6Vyn5ppOp9fLqOeVKL9KCWfNYS69_SchgiOyviVAVuM=" alt="Friends laughing and sharing a meal" class="rounded-lg shadow-xl w-full">
                </div>
                <div class="md:w-1/2">
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">The Universal Language</h2>
                    <p class="text-gray-600 leading-relaxed">Across cultures and continents, sharing a meal is a gesture of friendship, a way to build communities and forge unbreakable bonds. From family dinners to celebrations with friends, the table is where we connect, share our lives, and create new memories together.</p>
                </div>
            </section>
        </div>
    </div>
    
    <!-- 3. Featured Stories Section -->
    <?php if (!empty($featured_posts)): ?>
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-extrabold text-gray-800">Stories from Our Kitchens</h2>
                <p class="mt-4 text-lg text-gray-600">A few of the latest tales shared by our community.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" data-aos="fade-up">
                <?php foreach ($featured_posts as $post): ?>
                <a href="view_post.php?id=<?= $post['id'] ?>" class="block bg-white rounded-lg shadow-md overflow-hidden transform hover:-translate-y-1.5 transition duration-300 group">
                    <div class="h-48 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($post['image_url']) ?>');"></div>
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-2 group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($post['title']) ?></h3>
                        <p class="text-xs text-gray-500">By <?= htmlspecialchars($post['author_name']) ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-12">
                <a href="all_stories.php" class="inline-block bg-gray-800 text-white font-bold py-3 px-8 rounded-full hover:bg-black transition duration-300">
                    See All Stories
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>

