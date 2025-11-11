<?php
// recipe_details.php (Definitive Fix for Crash, Missing Content, and All Bugs)

// DEFINITIVE FIX: The database connection must be required first, before the header.
require_once __DIR__ . '/db.php';
include 'header.php';

// --- Get Recipe ID and Validate ---
$recipe_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($recipe_id <= 0) {
    echo "<div class='text-center py-20'>Invalid Recipe ID.</div>";
    include 'footer.php'; exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$user_rating = 0;
$is_saved = false;

// --- Fetch user's existing rating and saved status for this recipe ---
if ($user_id) {
    try {
        $rating_stmt = $pdo->prepare("SELECT rating FROM recipe_ratings WHERE user_id = ? AND recipe_id = ?");
        $rating_stmt->execute([$user_id, $recipe_id]);
        $user_rating = $rating_stmt->fetchColumn() ?: 0;
        
        $check_sql = "SELECT COUNT(*) FROM user_saved_recipes WHERE user_id = ? AND recipe_id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$user_id, $recipe_id]);
        if ($check_stmt->fetchColumn() > 0) { $is_saved = true; }

    } catch (PDOException $e) {
        error_log("DB error fetching user data: " . $e->getMessage());
    }
}

// --- Fetch all recipe data from the database ---
try {
    $sql = "SELECT r.*, c.name AS category_name, u.name AS author_name FROM recipes r LEFT JOIN categories c ON r.category_id = c.id LEFT JOIN users u ON r.author_id = u.id WHERE r.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$recipe_id]);
    $recipe = $stmt->fetch();

    if (!$recipe) {
        echo "<div class='text-center py-20'>Recipe Not Found.</div>";
        include 'footer.php'; exit;
    }

    $ing_sql = "SELECT ri.quantity_description FROM recipe_ingredients ri WHERE ri.recipe_id = ?";
    $ing_stmt = $pdo->prepare($ing_sql);
    $ing_stmt->execute([$recipe_id]);
    $ingredients = $ing_stmt->fetchAll();

    $instructions_json = $recipe['instructions'] ?? '[]';
    $instructions = json_decode($instructions_json, true);
    if (!is_array($instructions)) { $instructions = []; }

    $original_servings = (int)$recipe['servings'] > 0 ? (int)$recipe['servings'] : 1;

    // Fetch reviews for this recipe
    $reviews_sql = "SELECT rr.*, u.name as user_name 
                    FROM recipe_reviews rr 
                    JOIN users u ON rr.user_id = u.id 
                    WHERE rr.recipe_id = ? 
                    ORDER BY rr.created_at DESC";
    $reviews_stmt = $pdo->prepare($reviews_sql);
    $reviews_stmt->execute([$recipe_id]);
    $reviews = $reviews_stmt->fetchAll();

} catch (PDOException $e) {
    error_log("DB fetch recipe details error: " . $e->getMessage());
    echo "<div class='text-center py-20'>Error loading recipe details.</div>";
    include 'footer.php'; exit;
}
?>
<style>
    /* Styles for the interactive star rating widget */
    .star-rating-input .star, .star-rating-widget .star { cursor: pointer; color: #d1d5db; transition: color 0.2s; }
    .star-rating-input:hover .star, .star-rating-widget:hover .star { color: #f59e0b !important; }
    .star-rating-input .star:hover ~ .star, .star-rating-widget .star:hover ~ .star { color: #d1d5db !important; }
    .star-rating-input .star.selected, .star-rating-widget .star.rated { color: #f59e0b !important; }
</style>

<main class="bg-gray-50 py-12">
    <div class="container mx-auto px-4">
        <div id="toast-message" class="hidden fixed top-24 right-5 bg-green-500 text-white py-2 px-4 rounded-lg shadow-lg z-50"></div>
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="relative">
                <img src="<?= htmlspecialchars($recipe['image_url']) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" class="w-full h-64 md:h-96 object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                <div class="absolute bottom-0 left-0 p-8">
                    <h1 class="text-3xl md:text-5xl font-extrabold text-white"><?= htmlspecialchars($recipe['title']) ?></h1>
                    <p class="text-blue-200 mt-2">By <?= htmlspecialchars($recipe['author_name']) ?></p>
                </div>
            </div>

            <div class="p-8">
                <!-- Key Info Bar (RESTORED) -->
                <div class="flex flex-wrap items-center justify-center sm:justify-around bg-gray-100 rounded-lg p-4 -mt-24 relative z-10 shadow">
                    <div class="text-center px-4 py-2">
                        <i data-feather="clock" class="w-6 h-6 mx-auto text-blue-600"></i>
                        <p class="text-sm text-gray-500 mt-1">Prep Time</p>
                        <p class="font-bold"><?= htmlspecialchars($recipe['prep_time_minutes']) ?> min</p>
                    </div>
                     <div class="text-center px-4 py-2">
                        <i data-feather="watch" class="w-6 h-6 mx-auto text-blue-600"></i>
                        <p class="text-sm text-gray-500 mt-1">Cook Time</p>
                        <p class="font-bold"><?= htmlspecialchars($recipe['cook_time_minutes']) ?> min</p>
                    </div>
                     <div class="text-center px-4 py-2">
                        <i data-feather="users" class="w-6 h-6 mx-auto text-blue-600"></i>
                        <p class="text-sm text-gray-500 mt-1">Servings</p>
                        <p class="font-bold"><?= htmlspecialchars($recipe['servings']) ?></p>
                    </div>
                    <div class="text-center px-4 py-2">
                         <p class="text-sm text-gray-500 mb-1">Average Rating</p>
                        <div id="average-rating-display" class="flex items-center justify-center">
                            <?php for($i = 1; $i <= 5; $i++): 
                                $width = ($recipe['average_rating'] >= $i) ? '100%' : (($recipe['average_rating'] > $i-1) ? (($recipe['average_rating'] - ($i-1)) * 100) . '%' : '0%');
                            ?>
                                <div class="relative">
                                    <i data-feather="star" class="w-5 h-5 text-gray-300"></i>
                                    <div class="absolute top-0 left-0 h-full overflow-hidden" style="width: <?= $width ?>;">
                                        <i data-feather="star" class="w-5 h-5 text-amber-400 fill-current"></i>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                         <p class="text-xs text-gray-500 mt-1">(<?= number_format($recipe['average_rating'], 1) ?> from <?= $recipe['rating_count'] ?>)</p>
                    </div>
                    <?php if ($user_id): ?>
                    <div class="text-center px-4 py-2">
                         <button id="saveRecipeBtn" data-recipe-id="<?= $recipe_id ?>" class="inline-flex items-center font-semibold py-2 px-6 rounded-full border transition duration-300 <?= $is_saved ? 'bg-pink-600 text-white' : 'bg-white text-gray-800' ?>">
                           <i data-feather="bookmark" class="w-5 h-5 mr-2"></i>
                           <span id="saveBtnText"><?= $is_saved ? 'Saved' : 'Save' ?></span>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Nutritional Facts (RESTORED) -->
                <?php if (!empty($recipe['calories'])): ?>
                <div class="mt-8">
                    <h3 class="text-xl font-bold text-gray-800 border-b-2 border-blue-500 pb-2 mb-4">Nutrition Facts <span class="text-sm font-normal text-gray-500">(per serving)</span></h3>
                    <div class="flex justify-around text-center bg-gray-50 p-4 rounded-lg">
                        <div>
                            <p class="font-bold text-lg"><?= htmlspecialchars($recipe['calories']) ?> kcal</p>
                            <p class="text-sm text-gray-500">Calories</p>
                        </div>
                        <div>
                            <p class="font-bold text-lg"><?= htmlspecialchars($recipe['protein_grams']) ?>g</p>
                            <p class="text-sm text-gray-500">Protein</p>
                        </div>
                        <div>
                            <p class="font-bold text-lg"><?= htmlspecialchars($recipe['fat_grams']) ?>g</p>
                            <p class="text-sm text-gray-500">Fat</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Ingredients and Instructions (RESTORED) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 mt-8">
                    <div class="lg:col-span-1">
                        <div class="flex justify-between items-center border-b-2 border-blue-500 pb-2 mb-4">
                           <h2 class="text-2xl font-bold text-gray-800">Ingredients</h2>
                           <div class="flex items-center space-x-2">
                               <label class="text-sm font-medium">Servings:</label>
                               <button id="servings-minus" class="bg-gray-200 rounded-full w-6 h-6 flex items-center justify-center font-bold">-</button>
                               <span id="servings-display" class="font-bold w-4 text-center"><?= $original_servings ?></span>
                               <button id="servings-plus" class="bg-gray-200 rounded-full w-6 h-6 flex items-center justify-center font-bold">+</button>
                           </div>
                        </div>
                        <ul id="ingredients-list" class="space-y-3">
                            <?php foreach ($ingredients as $ing): ?>
                                <li class="ingredient-item flex items-start" data-original-text="<?= htmlspecialchars($ing['quantity_description']) ?>">
                                    <i data-feather="check-circle" class="w-5 h-5 text-blue-500 mr-3 mt-1 flex-shrink-0"></i>
                                    <span><?= htmlspecialchars($ing['quantity_description']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="lg:col-span-2">
                         <h2 class="text-2xl font-bold text-gray-800 border-b-2 border-blue-500 pb-2 mb-4">Instructions</h2>
                        <ol class="space-y-6">
                            <?php foreach ($instructions as $index => $step): ?>
                                <li class="flex items-start">
                                    <div class="flex-shrink-0 bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold mr-4"><?= $index + 1 ?></div>
                                    <p class="text-gray-700 leading-relaxed pt-1"><?= htmlspecialchars($step) ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>

                <!-- Full Reviews Section (RESTORED) -->
                <div id="reviews" class="pt-12 border-t mt-12">
                    <h2 class="text-3xl font-bold text-gray-800 mb-8">Community Reviews</h2>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="bg-gray-50 p-6 rounded-lg mb-12">
                        <h3 class="text-xl font-semibold mb-4">Leave Your Review</h3>
                        <form action="submit_review.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">
                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Your Rating*</label>
                                <div class="star-rating-input flex items-center">
                                    <?php for ($i = 1; $i <= 5; $i++): ?><i data-feather="star" class="star w-8 h-8 fill-current" data-value="<?= $i ?>"></i><?php endfor; ?>
                                </div>
                                <input type="hidden" name="rating" id="rating-value" value="0" required>
                            </div>
                            <div class="mb-4">
                                 <label for="review_text" class="block text-sm font-bold text-gray-700">Your Comments</label>
                                 <textarea name="review_text" id="review_text" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                            </div>
                            <div class="mb-4">
                                <label for="review_image" class="block text-sm font-bold text-gray-700">Add a Photo</label>
                                <input type="file" name="review_image" id="review_image" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0" accept="image/png, image/jpeg">
                            </div>
                            <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-6 rounded-md">Submit Review</button>
                        </form>
                    </div>
                    <?php else: ?>
                        <p class="text-center bg-gray-50 p-6 rounded-lg mb-12">Please <a href="login.html" class="text-blue-600 font-bold">login</a> to leave a review.</p>
                    <?php endif; ?>
                    <div class="space-y-8">
                        <?php if (empty($reviews)): ?>
                            <p class="text-gray-500">No reviews yet. Be the first to share your thoughts!</p>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-600"><?= strtoupper(substr($review['user_name'], 0, 1)) ?></div>
                                <div class="flex-grow">
                                    <p class="font-bold text-gray-800"><?= htmlspecialchars($review['user_name']) ?></p>
                                    <div class="flex items-center my-1">
                                        <?php for($i = 1; $i <= 5; $i++): ?><i data-feather="star" class="w-4 h-4 fill-current <?= $i <= $review['rating'] ? 'text-amber-400' : 'text-gray-300' ?>"></i><?php endfor; ?>
                                    </div>
                                    <?php if (!empty($review['review_text'])): ?><p class="text-gray-600 mt-2"><?= nl2br(htmlspecialchars($review['review_text'])) ?></p><?php endif; ?>
                                    <?php if (!empty($review['image_url'])): ?><img src="<?= htmlspecialchars($review['image_url']) ?>" class="mt-4 rounded-lg max-w-xs"><?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // All JavaScript for scaling, saving, rating, and toasts (RESTORED)
    feather.replace(); // Call feather replace

    // --- Recipe Scaling Logic ---
    const originalServings = <?= $original_servings ?>;
    let currentServings = originalServings;
    const servingsDisplay = document.getElementById('servings-display');
    const ingredientsList = document.getElementById('ingredients-list');

    document.getElementById('servings-minus').addEventListener('click', () => {
        if (currentServings > 1) { currentServings--; updateIngredients(); }
    });
    document.getElementById('servings-plus').addEventListener('click', () => {
        currentServings++; updateIngredients();
    });

    function updateIngredients() {
        servingsDisplay.textContent = currentServings;
        const scale = currentServings / originalServings;
        
        ingredientsList.querySelectorAll('.ingredient-item').forEach(item => {
            const originalText = item.dataset.originalText;
            const updatedText = originalText.replace(/[\d\.\/]+/g, (match) => {
                let num = 0;
                if (match.includes('/')) {
                    const parts = match.split(/[ \/]/).filter(Boolean);
                    if (parts.length === 2) { num = parseFloat(parts[0]) / parseFloat(parts[1]); } 
                    else if (parts.length === 3) { num = parseFloat(parts[0]) + (parseFloat(parts[1]) / parseFloat(parts[2])); }
                } else {
                    num = parseFloat(match);
                }
                if (isNaN(num)) return match;
                const scaledNum = num * scale;
                return Number(scaledNum.toFixed(2));
            });
            item.querySelector('span').textContent = updatedText;
        });
    }
    
    // --- Star rating input logic ---
    const ratingInputContainer = document.querySelector('.star-rating-input');
    if (ratingInputContainer) {
        const stars = ratingInputContainer.querySelectorAll('.star');
        const ratingValueInput = document.getElementById('rating-value');
        stars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = star.dataset.value;
                ratingValueInput.value = rating;
                stars.forEach(s => s.classList.toggle('selected', s.dataset.value <= rating));
            });
        });
    }

    // --- Save Recipe Toggle Logic (THIS IS THE NEW CODE) ---
    const saveBtn = document.getElementById('saveRecipeBtn');
    const toast = document.getElementById('toast-message');

    function showToast(message, isError = false) {
        toast.textContent = message;
        toast.className = `fixed top-24 right-5 text-white py-2 px-4 rounded-lg shadow-lg z-50 ${isError ? 'bg-red-500' : 'bg-green-500'}`;
        toast.classList.remove('hidden');
        setTimeout(() => {
            toast.classList.add('hidden');
        }, 3000);
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            const recipeId = this.dataset.recipeId;

            fetch('api_toggle_save.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ recipe_id: recipeId })
            })
            .then(response => {
                if (!response.ok) {
                    // Handle non-200 responses, like 401 Unauthorized
                    return response.json().then(err => { throw new Error(err.error || 'You must be logged in.'); });
                }
                return response.json();
            })
            .then(data => {
                if (data.ok) {
                    const saveBtnText = document.getElementById('saveBtnText');
                    // Update button style and text based on the 'saved' status from the API
                    if (data.saved) {
                        this.classList.add('bg-pink-600', 'text-white');
                        this.classList.remove('bg-white', 'text-gray-800');
                        saveBtnText.textContent = 'Saved';
                    } else {
                        this.classList.remove('bg-pink-600', 'text-white');
                        this.classList.add('bg-white', 'text-gray-800');
                        saveBtnText.textContent = 'Save';
                    }
                    showToast(data.message); // Show success message
                } else {
                    showToast(data.error || 'An unknown error occurred.', true); // Show error from API
                }
            })
            .catch(error => {
                console.error('Save recipe error:', error);
                showToast(error.message, true); // Show fetch or server-side error
            });
        });
    }
});
</script>

<?php include 'footer.php'; ?>



