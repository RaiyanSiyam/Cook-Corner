<?php
// saved_recipes.php
// Displays recipes saved by the logged-in user.

include 'header.php';
require_once __DIR__ . '/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div class='text-center py-20'><p class='text-lg'>Please <a href='login.html' class='text-blue-600 font-bold'>login</a> to see your saved recipes.</p></div>";
    include 'footer.php';
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch saved recipes for the current user
try {
    $sql = "SELECT r.id, r.title, r.image_url, r.prep_time_minutes, r.cook_time_minutes, c.name as category_name
            FROM user_saved_recipes usr
            JOIN recipes r ON usr.recipe_id = r.id
            LEFT JOIN categories c ON r.category_id = c.id
            WHERE usr.user_id = ?
            ORDER BY usr.saved_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $saved_recipes = $stmt->fetchAll();
} catch (PDOException $e) {
    $saved_recipes = [];
    error_log("DB error fetching saved recipes: " . $e->getMessage());
}

?>

<main class="gradient-bg py-12">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12" data-aos="fade-down">
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800">My Saved Recipes</h1>
            <p class="mt-4 text-lg text-gray-600">Your personal collection of culinary inspiration.</p>
        </div>

        <!-- Recipe Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <?php if (empty($saved_recipes)): ?>
                <div class="col-span-full text-center py-16 bg-white rounded-lg shadow">
                    <p class="text-gray-600 text-lg">You haven't saved any recipes yet.</p>
                    <a href="recipes.php" class="mt-4 inline-block bg-blue-600 text-white font-bold py-2 px-4 rounded-full">Explore Recipes</a>
                </div>
            <?php else: ?>
                <?php foreach ($saved_recipes as $recipe): ?>
                    <div class="recipe-card bg-white rounded-lg overflow-hidden shadow-lg transition duration-300 ease-in-out" data-aos="fade-up">
                        <a href="recipe_details.php?id=<?= $recipe['id'] ?>">
                            <img src="<?= htmlspecialchars($recipe['image_url']) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" class="w-full h-48 object-cover">
                        </a>
                        <div class="p-5">
                            <span class="text-xs font-semibold text-blue-600 uppercase"><?= htmlspecialchars($recipe['category_name']) ?></span>
                            <h3 class="mt-2 text-lg font-bold text-gray-800 truncate">
                                <a href="recipe_details.php?id=<?= $recipe['id'] ?>" class="hover:text-blue-600"><?= htmlspecialchars($recipe['title']) ?></a>
                            </h3>
                            <div class="flex items-center text-sm text-gray-500 mt-3">
                                <i data-feather="clock" class="w-4 h-4 mr-1"></i>
                                <?= (int)$recipe['prep_time_minutes'] + (int)$recipe['cook_time_minutes'] ?> min
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
include 'footer.php';
?>
