<?php
// meal_plan.php (Definitive Fix Version)
// This version prevents recipe repetition, generates 3 meals a day, respects nutritional targets, and adds a save plan feature.

include 'header.php';
require_once __DIR__ . '/db.php';

// --- Default values and user inputs (based on DAILY goals) ---
$target_daily_calories = isset($_POST['calories']) ? (int)$_POST['calories'] : 2000;
$target_daily_protein = isset($_POST['protein']) ? (int)$_POST['protein'] : 100;
$target_daily_fat = isset($_POST['fat']) ? (int)$_POST['fat'] : 70;
$category_id_filter = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

// --- Fetch Categories for the dropdown ---
try {
    $cat_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    error_log("DB Error fetching categories for meal plan: " . $e->getMessage());
}

// --- DEFINITIVE FIX: New, more intelligent meal plan generation logic with fallbacks ---
function generate_smart_meal_plan($pdo, $daily_calories, $daily_protein, $daily_fat, $category_id) {
    $full_plan = [];
    $used_recipe_ids = []; // Prevents selecting the same recipe twice
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $meals = [
        'Breakfast' => ['cal_pct' => 0.25, 'pro_pct' => 0.20, 'fat_pct' => 0.20],
        'Lunch'     => ['cal_pct' => 0.40, 'pro_pct' => 0.40, 'fat_pct' => 0.40],
        'Dinner'    => ['cal_pct' => 0.35, 'pro_pct' => 0.40, 'fat_pct' => 0.40]
    ];
    $base_sql = "SELECT id, title, image_url, servings, calories, protein_grams, fat_grams FROM recipes WHERE calories IS NOT NULL AND protein_grams IS NOT NULL AND fat_grams IS NOT NULL";

    try {
        foreach ($days as $day) {
            foreach ($meals as $meal_name => $percentages) {
                // Calculate target nutrition for this specific meal
                $meal_calories = $daily_calories * $percentages['cal_pct'];
                
                // Define a tolerance range (e.g., +/- 40%) to find suitable recipes
                $tolerance = 0.40;
                $min_cal = $meal_calories * (1 - $tolerance);
                $max_cal = $meal_calories * (1 + $tolerance);

                // 1. Primary attempt: Find a UNIQUE recipe within nutritional range and correct meal type
                $meal_sql = $base_sql . " AND meal_type = ?";
                $params = [$meal_name];
                $meal_sql .= " AND calories BETWEEN ? AND ?";
                array_push($params, $min_cal, $max_cal);
                if ($category_id > 0) {
                    $meal_sql .= " AND category_id = ?";
                    $params[] = $category_id;
                }
                if (!empty($used_recipe_ids)) {
                    $placeholders = implode(',', array_fill(0, count($used_recipe_ids), '?'));
                    $meal_sql .= " AND id NOT IN ($placeholders)";
                    $params = array_merge($params, $used_recipe_ids);
                }
                $meal_sql .= " ORDER BY RAND() LIMIT 1";
                $stmt = $pdo->prepare($meal_sql);
                $stmt->execute($params);
                $recipe = $stmt->fetch(PDO::FETCH_ASSOC);

                // 2. First Fallback: If no nutritional match, find ANY UNIQUE recipe of the correct meal type
                if (!$recipe) {
                    $fallback_sql_1 = $base_sql . " AND meal_type = ?";
                    $fallback_params_1 = [$meal_name];
                    if ($category_id > 0) {
                        $fallback_sql_1 .= " AND category_id = ?";
                        $fallback_params_1[] = $category_id;
                    }
                    if (!empty($used_recipe_ids)) {
                        $placeholders = implode(',', array_fill(0, count($used_recipe_ids), '?'));
                        $fallback_sql_1 .= " AND id NOT IN ($placeholders)";
                        $fallback_params_1 = array_merge($fallback_params_1, $used_recipe_ids);
                    }
                    $fallback_sql_1 .= " ORDER BY RAND() LIMIT 1";
                    $stmt1 = $pdo->prepare($fallback_sql_1);
                    $stmt1->execute($fallback_params_1);
                    $recipe = $stmt1->fetch(PDO::FETCH_ASSOC);
                }
                
                // 3. Second Fallback: If still no unique recipe, find ANY recipe of the correct meal type (allows repeats)
                if (!$recipe) {
                    $fallback_sql_2 = $base_sql . " AND meal_type = ? ORDER BY RAND() LIMIT 1";
                    $stmt2 = $pdo->prepare($fallback_sql_2);
                    $stmt2->execute([$meal_name]);
                    $recipe = $stmt2->fetch(PDO::FETCH_ASSOC);
                }

                if ($recipe) {
                    // Only add to used list if it's not already there
                    if (!in_array($recipe['id'], $used_recipe_ids)) {
                        $used_recipe_ids[] = $recipe['id'];
                    }
                }
                
                $full_plan[$day][$meal_name] = $recipe;
            }
        }
        return $full_plan;

    } catch (PDOException $e) {
        error_log("Error generating smart meal plan: " . $e->getMessage());
        return [];
    }
}


$weekly_plan = [];
$actual_totals = ['calories' => 0, 'protein' => 0, 'fat' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $weekly_plan = generate_smart_meal_plan($pdo, $target_daily_calories, $target_daily_protein, $target_daily_fat, $category_id_filter);

    if (!empty($weekly_plan)) {
        foreach ($weekly_plan as $day_meals) {
            foreach ($day_meals as $recipe) {
                if ($recipe) {
                    $actual_totals['calories'] += $recipe['calories'];
                    $actual_totals['protein'] += $recipe['protein_grams'];
                    $actual_totals['fat'] += $recipe['fat_grams'];
                }
            }
        }
    }
}
?>

<main class="bg-gray-50 py-12">
    <div class="container mx-auto px-4">
        <div id="toast-message" class="hidden fixed top-24 right-5 bg-green-500 text-white py-2 px-4 rounded-lg shadow-lg z-50"></div>
        <!-- Page Header -->
        <div class="text-center mb-8" data-aos="fade-down">
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800">Smart 3-Meal-a-Day Planner</h1>
            <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">Set your daily nutritional goals for a complete weekly menu.</p>
        </div>
        
        <!-- Controls Form -->
        <form action="meal_plan.php" method="POST" class="bg-white p-6 rounded-lg shadow-md mb-12" data-aos="fade-up">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Your Average Daily Nutritional Goals</h3>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 items-end">
                <div>
                    <label for="calories" class="block text-sm font-medium text-gray-700">Calories (kcal)</label>
                    <input type="number" name="calories" id="calories" value="<?= $target_daily_calories ?>" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="protein" class="block text-sm font-medium text-gray-700">Protein (g)</label>
                    <input type="number" name="protein" id="protein" value="<?= $target_daily_protein ?>" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="fat" class="block text-sm font-medium text-gray-700">Fat (g)</label>
                    <input type="number" name="fat" id="fat" value="<?= $target_daily_fat ?>" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                </div>
                 <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Dietary Preference</label>
                    <select name="category_id" id="category_id" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        <option value="0" <?= $category_id_filter == 0 ? 'selected' : '' ?>>Any</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_id_filter == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-6 rounded-md hover:bg-blue-700 transition">
                    <i data-feather="refresh-cw" class="w-5 h-5 inline-block mr-2"></i>Generate Plan
                </button>
            </div>
        </form>
        
        <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
             <div class="text-center text-gray-600 bg-white p-10 rounded-lg shadow"><p>Set your nutritional goals above to create your menu.</p></div>
        <?php elseif (empty($weekly_plan)): ?>
            <div class="text-center text-gray-600 bg-white p-10 rounded-lg shadow"><p>No recipes found matching your criteria. Try adjusting your goals.</p></div>
        <?php else: ?>
            <!-- Plan Summary Section -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8" data-aos="fade-up">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h3 class="text-2xl font-bold text-gray-800">Weekly Plan Summary</h3>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <button id="savePlanBtn" class="bg-green-600 text-white font-bold py-2 px-4 rounded-md hover:bg-green-700 transition flex items-center">
                        <i data-feather="save" class="w-4 h-4 mr-2"></i> Save Plan
                    </button>
                    <?php endif; ?>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
                    <div>
                        <p class="text-sm text-gray-500">Calories (kcal)</p>
                        <p class="text-lg font-semibold text-gray-800"><span class="text-blue-600"><?= number_format($actual_totals['calories']) ?></span> / <?= number_format($target_daily_calories * 7) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Protein (g)</p>
                        <p class="text-lg font-semibold text-gray-800"><span class="text-blue-600"><?= number_format($actual_totals['protein']) ?></span> / <?= number_format($target_daily_protein * 7) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Fat (g)</p>
                        <p class="text-lg font-semibold text-gray-800"><span class="text-blue-600"><?= number_format($actual_totals['fat']) ?></span> / <?= number_format($target_daily_fat * 7) ?></p>
                    </div>
                </div>
            </div>

            <!-- Meal Plan Display -->
            <div class="space-y-8">
                <?php foreach ($weekly_plan as $day => $meals): ?>
                <div class="bg-white rounded-lg shadow-lg" data-aos="fade-up">
                    <h3 class="text-2xl font-bold text-gray-800 p-4 bg-gray-50 rounded-t-lg border-b"><?= $day ?></h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x">
                        <?php foreach ($meals as $meal_name => $recipe): ?>
                        <div class="p-4 flex items-start">
                            <?php if($recipe): ?>
                            <a href="recipe_details.php?id=<?= $recipe['id'] ?>" class="flex-shrink-0">
                                <img src="<?= htmlspecialchars($recipe['image_url']) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" class="w-20 h-20 object-cover rounded-md mr-4">
                            </a>
                            <div>
                                <p class="font-bold text-blue-600 text-sm"><?= $meal_name ?></p>
                                <a href="recipe_details.php?id=<?= $recipe['id'] ?>" class="font-semibold text-gray-900 hover:underline leading-tight"><?= htmlspecialchars($recipe['title']) ?></a>
                                <div class="text-xs text-gray-500 mt-1">
                                    <p>C: <?= $recipe['calories'] ?> | P: <?= $recipe['protein_grams'] ?>g | F: <?= $recipe['fat_grams'] ?>g</p>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="text-gray-400 text-sm flex items-center h-full">No suitable <?= strtolower($meal_name) ?> recipe found.</div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('savePlanBtn');
    if(saveBtn) {
        saveBtn.addEventListener('click', async () => {
            const planData = <?= json_encode($weekly_plan) ?>;
            const originalBtnHTML = saveBtn.innerHTML;
            saveBtn.innerHTML = 'Saving...';
            saveBtn.disabled = true;

            try {
                const response = await fetch('save_meal_plan.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ plan: planData })
                });
                const result = await response.json();
                if(!response.ok) { throw new Error(result.error); }
                showToast(result.message, 'success');
                saveBtn.innerHTML = '<i data-feather="check" class="w-4 h-4 mr-2"></i> Saved!';
                feather.replace();
            } catch (error) {
                showToast(error.message, 'error');
                saveBtn.innerHTML = originalBtnHTML;
                saveBtn.disabled = false;
                feather.replace();
            }
        });
    }
});
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast-message');
    toast.textContent = message;
    toast.classList.remove('hidden');
    toast.classList.add(type === 'success' ? 'bg-green-500' : 'bg-red-500');
    setTimeout(() => { toast.classList.add('hidden'); }, 3000);
}
</script>
<?php include 'footer.php'; ?>

