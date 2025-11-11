<?php
// add_recipe.php (Definitive Fix Version)
// Fixes fatal crash, adds file upload, and includes all required database fields.

// DEFINITIVE FIX: The database must be connected before the header is included.
require_once __DIR__ . '/db.php';
include 'header.php';

// --- Security Check: Ensure user is logged in ---
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html?message=Please log in to add a recipe.');
    exit;
}

// --- Fetch categories and occasions for dropdowns ---
try {
    $categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
    $occasions = $pdo->query("SELECT id, name FROM occasions ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    $occasions = [];
    error_log("DB Error fetching categories/occasions: " . $e->getMessage());
}

$meal_types = ['Breakfast', 'Lunch', 'Dinner', 'Snack', 'Dessert'];
?>

<main class="bg-gray-50 py-12">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
            <h1 class="text-3xl font-extrabold text-gray-800 mb-6 border-b pb-4">Add a New Recipe</h1>

            <!-- Display errors if redirected from submit script -->
            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?= htmlspecialchars($_GET['error']) ?></span>
                </div>
            <?php endif; ?>

            <!-- The form MUST have enctype="multipart/form-data" for file uploads -->
            <form action="submit_recipe.php" method="POST" enctype="multipart/form-data">
                <!-- Basic Information Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="title" class="block text-sm font-bold text-gray-700">Recipe Title</label>
                        <input type="text" id="title" name="title" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm" required>
                    </div>
                    <div>
                        <label for="recipe_image" class="block text-sm font-bold text-gray-700">Recipe Photo</label>
                        <!-- DEFINITIVE FIX: Changed from URL input to file input -->
                        <input type="file" id="recipe_image" name="recipe_image" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                    </div>
                </div>

                <!-- Details Section -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label for="prep_time" class="block text-sm font-bold text-gray-700">Prep Time (min)</label>
                        <input type="number" id="prep_time" name="prep_time" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm" required>
                    </div>
                    <div>
                        <label for="cook_time" class="block text-sm font-bold text-gray-700">Cook Time (min)</label>
                        <input type="number" id="cook_time" name="cook_time" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm" required>
                    </div>
                    <div>
                        <label for="servings" class="block text-sm font-bold text-gray-700">Servings</label>
                        <input type="number" id="servings" name="servings" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm" required>
                    </div>
                     <div>
                        <label for="meal_type" class="block text-sm font-bold text-gray-700">Meal Type</label>
                        <select id="meal_type" name="meal_type" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm" required>
                            <?php foreach($meal_types as $type): ?>
                                <option value="<?= $type ?>"><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div>
                        <label for="category_id" class="block text-sm font-bold text-gray-700">Category</label>
                        <select id="category_id" name="category_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm" required>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div>
                        <label for="occasion_id" class="block text-sm font-bold text-gray-700">Occasion</label>
                        <select id="occasion_id" name="occasion_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm" required>
                             <?php foreach($occasions as $occ): ?>
                                <option value="<?= $occ['id'] ?>"><?= htmlspecialchars($occ['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                 <!-- Nutritional Info Section -->
                 <h2 class="text-xl font-bold text-gray-800 mt-8 mb-4 border-t pt-6">Nutritional Information (per serving)</h2>
                 <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                     <div>
                        <label for="calories" class="block text-sm font-bold text-gray-700">Calories (kcal)</label>
                        <input type="number" id="calories" name="calories" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm" placeholder="e.g., 550">
                    </div>
                    <div>
                        <label for="protein" class="block text-sm font-bold text-gray-700">Protein (g)</label>
                        <input type="number" id="protein" name="protein" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm" placeholder="e.g., 30">
                    </div>
                    <div>
                        <label for="fat" class="block text-sm font-bold text-gray-700">Fat (g)</label>
                        <input type="number" id="fat" name="fat" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm" placeholder="e.g., 22">
                    </div>
                 </div>

                <!-- Ingredients Section -->
                <div class="mt-8 border-t pt-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Ingredients</h2>
                    <div id="ingredients-container" class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <input type="text" name="ingredients[]" class="flex-grow border-gray-300 rounded-lg shadow-sm" placeholder="e.g., 2 cups sugar" required>
                            <button type="button" class="remove-ingredient-btn bg-red-500 text-white p-2 rounded-full hover:bg-red-600" disabled><i data-feather="minus" class="w-4 h-4"></i></button>
                        </div>
                    </div>
                    <button type="button" id="add-ingredient-btn" class="mt-4 text-sm font-semibold text-blue-600 hover:text-blue-800 flex items-center">
                        <i data-feather="plus-circle" class="w-4 h-4 mr-2"></i> Add Another Ingredient
                    </button>
                </div>

                <!-- Instructions Section -->
                <div class="mt-8 border-t pt-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Instructions</h2>
                     <div id="instructions-container" class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <textarea name="instructions[]" class="flex-grow border-gray-300 rounded-lg shadow-sm" rows="2" placeholder="First step..." required></textarea>
                            <button type="button" class="remove-instruction-btn bg-red-500 text-white p-2 rounded-full hover:bg-red-600" disabled><i data-feather="minus" class="w-4 h-4"></i></button>
                        </div>
                    </div>
                    <button type="button" id="add-instruction-btn" class="mt-4 text-sm font-semibold text-blue-600 hover:text-blue-800 flex items-center">
                        <i data-feather="plus-circle" class="w-4 h-4 mr-2"></i> Add Another Step
                    </button>
                </div>
                
                <div class="mt-8 border-t pt-6">
                    <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-green-700 transition">Submit Recipe</button>
                </div>
            </form>
        </div>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Dynamic Field Logic ---
    function addField(containerId, template) {
        const container = document.getElementById(containerId);
        const newField = document.createElement('div');
        newField.innerHTML = template.trim();
        container.appendChild(newField.firstChild);
        feather.replace();
        updateRemoveButtons(containerId);
    }

    function removeField(event) {
        if (event.target.closest('.remove-ingredient-btn, .remove-instruction-btn')) {
            const fieldWrapper = event.target.closest('.flex');
            const container = fieldWrapper.parentElement;
            fieldWrapper.remove();
            updateRemoveButtons(container.id);
        }
    }

    function updateRemoveButtons(containerId) {
        const container = document.getElementById(containerId);
        const buttons = container.querySelectorAll('.remove-ingredient-btn, .remove-instruction-btn');
        buttons.forEach((btn, index) => {
            btn.disabled = buttons.length === 1;
        });
    }

    // --- Ingredient Logic ---
    const ingredientContainer = document.getElementById('ingredients-container');
    const ingredientTemplate = `
        <div class="flex items-center space-x-2">
            <input type="text" name="ingredients[]" class="flex-grow border-gray-300 rounded-lg shadow-sm" placeholder="e.g., 1 tsp salt" required>
            <button type="button" class="remove-ingredient-btn bg-red-500 text-white p-2 rounded-full hover:bg-red-600"><i data-feather="minus" class="w-4 h-4"></i></button>
        </div>`;
    
    document.getElementById('add-ingredient-btn').addEventListener('click', () => addField('ingredients-container', ingredientTemplate));
    ingredientContainer.addEventListener('click', removeField);
    updateRemoveButtons('ingredients-container');

    // --- Instruction Logic ---
    const instructionContainer = document.getElementById('instructions-container');
    const instructionTemplate = `
        <div class="flex items-center space-x-2">
            <textarea name="instructions[]" class="flex-grow border-gray-300 rounded-lg shadow-sm" rows="2" placeholder="Another step..." required></textarea>
            <button type="button" class="remove-instruction-btn bg-red-500 text-white p-2 rounded-full hover:bg-red-600"><i data-feather="minus" class="w-4 h-4"></i></button>
        </div>`;
    
    document.getElementById('add-instruction-btn').addEventListener('click', () => addField('instructions-container', instructionTemplate));
    instructionContainer.addEventListener('click', removeField);
    updateRemoveButtons('instructions-container');
});
</script>

<?php include 'footer.php'; ?>

