<?php
// homepage.php
// This is the main homepage for the Cook_Corner website.

// Include the shared header
include 'header.php';

// Include the database connection
require_once __DIR__ . '/db.php';

// --- Fetch Featured Recipes ---
$featured_recipes = [];
try {
    // Fetch 4 random recipes from the database to feature on the homepage
    $stmt = $pdo->query("SELECT id, title, image_url, cook_time_minutes, servings FROM recipes ORDER BY RAND() LIMIT 4");
    $featured_recipes = $stmt->fetchAll();
} catch (PDOException $e) {
    // If there's a database error, we can handle it gracefully
    error_log("Database error fetching featured recipes: " . $e->getMessage());
}

?>

<main>
    <!-- Hero Section -->
    <div class="relative min-h-screen bg-cover bg-center flex items-center" style="background-image: url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=2070');">
        <div class="absolute inset-0 bg-black bg-opacity-40"></div>
        <div class="container mx-auto px-4 text-center relative z-10">
            <h1 class="text-4xl md:text-6xl text-white font-extrabold leading-tight mb-4" data-aos="fade-down">
                Find Your Next Favorite Meal
            </h1>
            <p class="text-lg md:text-xl text-gray-200 max-w-2xl mx-auto mb-8" data-aos="fade-up" data-aos-delay="200">
                Discover thousands of recipes, create weekly meal plans, and get essential kitchen tips all in one place.
            </p>
            <div data-aos="fade-up" data-aos-delay="400">
                <a href="recipes.php" class="bg-blue-600 text-white font-bold py-3 px-8 rounded-lg text-lg hover:bg-blue-700 transition-transform transform hover:scale-105 inline-block">
                    Explore Recipes
                </a>
            </div>
        </div>
    </div>

    <!-- Featured Recipes Section -->
    <?php if (!empty($featured_recipes)): ?>
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-12" data-aos="fade-up">Featured Recipes</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($featured_recipes as $index => $recipe): ?>
                    <a href="recipe_details.php?id=<?= $recipe['id'] ?>" class="group block bg-white rounded-lg shadow-md overflow-hidden transform hover:-translate-y-2 transition-transform duration-300" data-aos="fade-up" data-aos-delay="<?= ($index % 4) * 100 ?>">
                        <div class="relative">
                            <img src="<?= htmlspecialchars($recipe['image_url']) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" class="w-full h-48 object-cover group-hover:opacity-80 transition-opacity">
                        </div>
                        <div class="p-5">
                            <h3 class="text-lg font-semibold text-gray-900 truncate"><?= htmlspecialchars($recipe['title']) ?></h3>
                            <div class="flex items-center text-sm text-gray-500 mt-3">
                                <i data-feather="clock" class="w-4 h-4 mr-2"></i>
                                <span><?= htmlspecialchars($recipe['cook_time_minutes']) ?> min</span>
                                <span class="mx-2">|</span>
                                <i data-feather="users" class="w-4 h-4 mr-2"></i>
                                <span><?= htmlspecialchars($recipe['servings']) ?> servings</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Why Choose Us Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold text-gray-800 mb-12" data-aos="fade-up">Why Choose Cook Corner?</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="text-center" data-aos="fade-up" data-aos-delay="100">
                    <div class="bg-blue-100 text-blue-600 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                        <i data-feather="book-open" class="w-10 h-10"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Vast Recipe Library</h3>
                    <p class="text-gray-600">From quick weeknight dinners to elaborate holiday feasts, find the perfect recipe for any occasion.</p>
                </div>
                <div class="text-center" data-aos="fade-up" data-aos-delay="200">
                    <div class="bg-blue-100 text-blue-600 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                        <i data-feather="calendar" class="w-10 h-10"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Smart Meal Planner</h3>
                    <p class="text-gray-600">Organize your week, generate shopping lists, and take the stress out of meal prep.</p>
                </div>
                <div class="text-center" data-aos="fade-up" data-aos-delay="300">
                    <div class="bg-blue-100 text-blue-600 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                        <i data-feather="award" class="w-10 h-10"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Expert Kitchen Tips</h3>
                    <p class="text-gray-600">Improve your skills with professional tips, techniques, and answers to common cooking questions.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
// Include the shared footer
include 'footer.php';
?>
