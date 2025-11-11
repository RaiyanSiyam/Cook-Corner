<?php
// admin/edit_recipe.php
// This version removes the 'carbs' field and calculates the average rating as a non-editable field.

include 'header.php';
require_once __DIR__ . '/../db.php';

// --- Build a reliable base URL for local images ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_root = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\'); 
$base_url = $protocol . $host . $project_root . '/';

$recipe_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

if ($recipe_id === 0) {
    header('Location: manage_recipes.php');
    exit;
}

// --- Pre-fetch data for dropdowns ---
try {
    $users = $pdo->query("SELECT id, name FROM users ORDER BY name")->fetchAll();
    $categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
    $occasions = $pdo->query("SELECT id, name FROM occasions ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    die("Database error: Could not fetch form data.");
}


// --- HANDLE FORM SUBMISSION (UPDATE LOGIC) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve all form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $cook_time = filter_input(INPUT_POST, 'cook_time_minutes', FILTER_VALIDATE_INT);
    $servings = filter_input(INPUT_POST, 'servings', FILTER_VALIDATE_INT);
    // --- Get Nutritional Facts (Carbs removed) ---
    $calories = filter_input(INPUT_POST, 'calories', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    $protein = filter_input(INPUT_POST, 'protein_grams', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    $fat = filter_input(INPUT_POST, 'fat_grams', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    // --- END ---
    $instructions = trim($_POST['instructions'] ?? '');
    $author_id = filter_input(INPUT_POST, 'author_id', FILTER_VALIDATE_INT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $occasion_id = filter_input(INPUT_POST, 'occasion_id', FILTER_VALIDATE_INT);
    $current_image_path = $_POST['current_image_path'] ?? '';
    
    $author_id = $author_id ?: null;
    $category_id = $category_id ?: null;
    $occasion_id = $occasion_id ?: null;

    if (empty($title) || $cook_time === false || $servings === false) {
        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Error: Please fill in all required fields.</div>";
    } else {
        $new_image_path = $current_image_path;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/recipes/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $file_tmp_path = $_FILES['image']['tmp_name'];
            $file_ext = strtolower(pathinfo(basename($_FILES['image']['name']), PATHINFO_EXTENSION));
            $new_file_name = uniqid('recipe_', true) . '.' . $file_ext;
            $dest_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_path, $dest_path)) {
                $new_image_path = 'uploads/recipes/' . $new_file_name;
                if (!empty($current_image_path) && strpos($current_image_path, 'http') !== 0 && file_exists(__DIR__ . '/../' . $current_image_path)) {
                    unlink(__DIR__ . '/../' . $current_image_path);
                }
            }
        }

        try {
            // --- Updated SQL Query (Carbs and Rating removed) ---
            $sql = "UPDATE recipes SET 
                        title = ?, 
                        description = ?, 
                        cook_time_minutes = ?, 
                        servings = ?, 
                        calories = ?,
                        protein_grams = ?,
                        fat_grams = ?,
                        instructions = ?, 
                        author_id = ?, 
                        category_id = ?, 
                        occasion_id = ?,
                        image_url = ?
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $title, $description, $cook_time, $servings, 
                $calories, $protein, $fat,
                $instructions, 
                $author_id, $category_id, $occasion_id, $new_image_path, $recipe_id
            ]);

            $success_redirect_url = 'manage_recipes.php?updated=1';
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6'><strong>Success!</strong> The recipe has been updated. <a href='{$success_redirect_url}' class='font-bold underline hover:text-green-900'>Return to Recipe List</a>.</div>";

        } catch (PDOException $e) {
             $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6'>Database error: Could not update recipe.</div>";
             error_log("Recipe update failed: " . $e->getMessage());
        }
    }
}

// --- FETCH EXISTING RECIPE DATA (NOW WITH AVERAGE RATING) ---
try {
    // This query now calculates the average rating from the recipe_ratings table.
    $sql = "SELECT r.*, AVG(rr.rating) as average_rating 
            FROM recipes r
            LEFT JOIN recipe_ratings rr ON r.id = rr.recipe_id
            WHERE r.id = ?
            GROUP BY r.id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$recipe_id]);
    $recipe = $stmt->fetch();
    if (!$recipe) {
        header('Location: manage_recipes.php');
        exit;
    }
} catch (PDOException $e) {
    // Fallback if the recipe_ratings table doesn't exist, use the 'rating' column from recipes
    if ($e->getCode() === '42S02') { // Table not found error
        $stmt = $pdo->prepare("SELECT *, rating as average_rating FROM recipes WHERE id = ?");
        $stmt->execute([$recipe_id]);
        $recipe = $stmt->fetch();
    } else {
        die("Database error: Could not fetch recipe data. " . $e->getMessage());
    }
}
?>

<div class="bg-white p-8 rounded-lg shadow-md max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Edit Recipe</h1>
        <a href="manage_recipes.php" class="text-blue-600 hover:underline">&larr; Back to Recipes</a>
    </div>

    <?= $message ?>

    <form action="edit_recipe.php?id=<?= $recipe_id ?>" method="POST" enctype="multipart/form-data">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Left Column: Main Details -->
            <div class="md:col-span-2 space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Recipe Title</label>
                    <input type="text" id="title" name="title" required value="<?= htmlspecialchars($recipe['title']) ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="5" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"><?= htmlspecialchars($recipe['description']) ?></textarea>
                </div>
                <div>
                    <label for="instructions" class="block text-sm font-medium text-gray-700">Instructions</label>
                    <textarea id="instructions" name="instructions" rows="10" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm"><?= htmlspecialchars($recipe['instructions']) ?></textarea>
                </div>
            </div>

            <!-- Right Column: Metadata & Image -->
            <div class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="cook_time_minutes" class="block text-sm font-medium text-gray-700">Cook Time (min)</label>
                        <input type="number" id="cook_time_minutes" name="cook_time_minutes" required value="<?= htmlspecialchars($recipe['cook_time_minutes']) ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label for="servings" class="block text-sm font-medium text-gray-700">Servings</label>
                        <input type="number" id="servings" name="servings" required value="<?= htmlspecialchars($recipe['servings']) ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
                
                <!-- --- Nutritional Facts & Rating Section --- -->
                <div class="border-t pt-6">
                     <div>
                        <label for="rating" class="block text-sm font-medium text-gray-700 mb-1">Average Rating</label>
                        <input type="text" id="rating" name="rating" readonly value="<?= $recipe['average_rating'] ? number_format($recipe['average_rating'], 2) . ' / 5' : 'Not Rated' ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed">
                    </div>
                    <h3 class="text-sm font-medium text-gray-700 mt-4 mb-2">Nutritional Facts (per serving)</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="calories" class="block text-xs text-gray-600">Calories</label>
                            <input type="number" id="calories" name="calories" value="<?= htmlspecialchars($recipe['calories'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                        </div>
                         <div>
                            <label for="protein_grams" class="block text-xs text-gray-600">Protein (g)</label>
                            <input type="number" id="protein_grams" name="protein_grams" value="<?= htmlspecialchars($recipe['protein_grams'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                        </div>
                         <div>
                            <label for="fat_grams" class="block text-xs text-gray-600">Fat (g)</label>
                            <input type="number" id="fat_grams" name="fat_grams" value="<?= htmlspecialchars($recipe['fat_grams'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                </div>
                <!-- --- END --- -->

                <div>
                    <label for="author_id" class="block text-sm font-medium text-gray-700">Author</label>
                    <select id="author_id" name="author_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                        <option value="">(None)</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $recipe['author_id'] == $user['id'] ? 'selected' : '' ?>><?= htmlspecialchars($user['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                    <select id="category_id" name="category_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                        <option value="">(None)</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= $recipe['category_id'] == $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="occasion_id" class="block text-sm font-medium text-gray-700">Occasion</label>
                    <select id="occasion_id" name="occasion_id" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                        <option value="">(None)</option>
                        <?php foreach ($occasions as $occasion): ?>
                            <option value="<?= $occasion['id'] ?>" <?= $recipe['occasion_id'] == $occasion['id'] ? 'selected' : '' ?>><?= htmlspecialchars($occasion['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Current Image</label>
                    <?php
                        $image_url_display = htmlspecialchars($recipe['image_url']);
                        if (strpos($image_url_display, 'http') === 0) {
                            $image_src_display = $image_url_display;
                        } else {
                            $image_src_display = $base_url . $image_url_display;
                        }
                    ?>
                    <img src="<?= $image_src_display ?>" alt="Current Image" class="mt-2 w-full h-32 object-cover rounded-md border">
                    <input type="hidden" name="current_image_path" value="<?= htmlspecialchars($recipe['image_url']) ?>">
                </div>
                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700">Upload New Image</label>
                    <input type="file" id="image" name="image" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-8 mt-8 border-t">
            <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                Update Recipe
            </button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>

