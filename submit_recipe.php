<?php
// submit_recipe.php (Definitive Fix Version)
// Handles file upload, all new fields, and saves the new recipe correctly.

session_start();
// DEFINITIVE FIX: The database connection must be required at the top.
require_once __DIR__ . '/db.php';

// --- 1. Security and Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_recipe.php'); exit;
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html?message=Please log in to add a recipe.'); exit;
}

$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
    // This error happens if you have not created the 'uploads' folder in your project directory.
    header('Location: add_recipe.php?error=' . urlencode('Server error: The uploads directory is not available. Please create it.')); exit;
}

// --- 2. Handle File Upload ---
$image_path = null;
if (isset($_FILES['recipe_image']) && $_FILES['recipe_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['recipe_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        header('Location: add_recipe.php?error=' . urlencode('Invalid file type. Please upload a JPG, PNG, or GIF.')); exit;
    }
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        header('Location: add_recipe.php?error=' . urlencode('File is too large. Maximum size is 5MB.')); exit;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $unique_filename = uniqid('recipe_', true) . '.' . $extension;
    $destination = $upload_dir . $unique_filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $image_path = 'uploads/' . $unique_filename; // Store the relative path for web access
    } else {
        header('Location: add_recipe.php?error=' . urlencode('Failed to move uploaded file.')); exit;
    }
} else {
    header('Location: add_recipe.php?error=' . urlencode('A recipe image is required.')); exit;
}

// --- 3. Get and Sanitize Form Data ---
$title = trim($_POST['title'] ?? '');
$prep_time = (int)($_POST['prep_time'] ?? 0);
$cook_time = (int)($_POST['cook_time'] ?? 0);
$servings = (int)($_POST['servings'] ?? 0);
$meal_type = trim($_POST['meal_type'] ?? 'Dinner');
$category_id = (int)($_POST['category_id'] ?? 0);
$occasion_id = (int)($_POST['occasion_id'] ?? 0);
$calories = !empty($_POST['calories']) ? (int)$_POST['calories'] : null;
$protein = !empty($_POST['protein']) ? (int)$_POST['protein'] : null;
$fat = !empty($_POST['fat']) ? (int)$_POST['fat'] : null;
$ingredients_raw = $_POST['ingredients'] ?? [];
$instructions_raw = $_POST['instructions'] ?? [];

// Basic validation
if (empty($title) || empty($ingredients_raw) || empty($instructions_raw) || $category_id === 0 || $occasion_id === 0) {
    header('Location: add_recipe.php?error=' . urlencode('All fields are required. Please fill out the form completely.')); exit;
}
$instructions_json = json_encode(array_values(array_filter($instructions_raw)));

try {
    $pdo->beginTransaction();

    // --- 4. Insert into 'recipes' table ---
    $sql = "INSERT INTO recipes (author_id, title, image_url, prep_time_minutes, cook_time_minutes, servings, meal_type, category_id, occasion_id, calories, protein_grams, fat_grams, instructions)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_SESSION['user_id'], $title, $image_path, $prep_time, $cook_time, $servings, $meal_type, 
        $category_id, $occasion_id, $calories, $protein, $fat, $instructions_json
    ]);
    $new_recipe_id = $pdo->lastInsertId();

    // --- 5. Process and Insert Ingredients ---
    $stmt_find_ingredient = $pdo->prepare("SELECT id FROM ingredients WHERE name = ?");
    $stmt_add_ingredient = $pdo->prepare("INSERT INTO ingredients (name) VALUES (?)");
    $stmt_link_ingredient = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity_description) VALUES (?, ?, ?)");

    foreach ($ingredients_raw as $ing_str) {
        if (empty(trim($ing_str))) continue;

        // Extract a clean name for the 'ingredients' table
        $clean_name = trim(preg_replace('/^[\d\.\/\s]+(\s?(cup|tbsp|tsp|oz|g|kg|lb)[s\.]?)?\s*/i', '', $ing_str));
        $clean_name = ucfirst(strtolower($clean_name));

        if (empty($clean_name)) continue; // Skip if parsing leaves an empty name

        $stmt_find_ingredient->execute([$clean_name]);
        $ingredient_id = $stmt_find_ingredient->fetchColumn();

        if (!$ingredient_id) {
            $stmt_add_ingredient->execute([$clean_name]);
            $ingredient_id = $pdo->lastInsertId();
        }
        
        $stmt_link_ingredient->execute([$new_recipe_id, $ingredient_id, $ing_str]);
    }

    $pdo->commit();

    // --- 6. Redirect to the new recipe's page on success ---
    header('Location: recipe_details.php?id=' . $new_recipe_id);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Submit recipe error: " . $e->getMessage());
    header('Location: add_recipe.php?error=' . urlencode('A database error occurred. Could not save recipe.'));
    exit;
}
?>
