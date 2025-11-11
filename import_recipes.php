<?php
// import_recipes.php (FINAL VERSION)
// This script reads the entire JSON file, parses it, and imports all recipes.
// This is the correct version to use with the sample_recipes.json file.

// Set a long timeout to prevent the script from stopping prematurely.
set_time_limit(600); 
ini_set('memory_limit', '256M');

echo "<pre>";

// 1. Establish Database Connection
require_once __DIR__ . '/db.php';

// 2. Define the path to your JSON file
$file_path = __DIR__ . '/sample_recipes.json';
if (!file_exists($file_path)) {
    die("ERROR: The file sample_recipes.json was not found in the project folder.\n");
}

// 3. Read and Decode the JSON file
$json_data = file_get_contents($file_path);
if ($json_data === false) {
    die("ERROR: Could not read the file: $file_path\n");
}

// Decode the entire JSON array from the file content
$recipes = json_decode($json_data, true);

// Check for JSON decoding errors
if (json_last_error() !== JSON_ERROR_NONE) {
    die("ERROR: Malformed JSON in $file_path. Error: " . json_last_error_msg() . "\n");
}

if (!is_array($recipes)) {
    die("ERROR: The JSON file does not contain a valid array of recipes.\n");
}

$recipe_count = count($recipes);
echo "Found $recipe_count recipes to import.\n\n";

// 4. Prepare SQL Statements for efficiency
$stmt_add_recipe = $pdo->prepare(
    "INSERT INTO recipes (title, instructions, image_url, prep_time_minutes, cook_time_minutes, servings, author_id, category_id, occasion_id) 
     VALUES (:title, :instructions, :image_url, :prep_time_minutes, :cook_time_minutes, :servings, :author_id, :category_id, :occasion_id)"
);
$stmt_find_ingredient = $pdo->prepare("SELECT id FROM ingredients WHERE name = ?");
$stmt_add_ingredient = $pdo->prepare("INSERT INTO ingredients (name) VALUES (?)");
$stmt_link_ingredient = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity_description) VALUES (?, ?, ?)");

// 5. Start a Transaction for data integrity
try {
    $pdo->beginTransaction();
    $imported_count = 0;

    // 6. Loop through each recipe and import it
    foreach ($recipes as $index => $recipe) {
        // Basic validation for the recipe object
        if (!isset($recipe['title']) || !isset($recipe['ingredients']) || !isset($recipe['directions']) || !is_array($recipe['ingredients']) || !is_array($recipe['directions'])) {
            echo "Skipping malformed recipe at index $index. Missing required fields.\n";
            continue;
        }

        // Extract and clean data
        $title = trim($recipe['title']);
        $instructions = json_encode($recipe['directions']);
        $image_url = $recipe['image_url'] ?? null;
        
        // Extract numbers from time and serving strings
        $prep_time_str = $recipe['prep_time'] ?? '0';
        $cook_time_str = $recipe['cook_time'] ?? '0';
        $servings_str = $recipe['servings'] ?? '0';

        preg_match('/\\d+/', $prep_time_str, $prep_matches);
        $prep_time_minutes = isset($prep_matches[0]) ? (int)$prep_matches[0] : null;

        preg_match('/\\d+/', $cook_time_str, $cook_matches);
        $cook_time_minutes = isset($cook_matches[0]) ? (int)$cook_matches[0] : null;
        
        preg_match('/\\d+/', $servings_str, $servings_matches);
        $servings = isset($servings_matches[0]) ? (int)$servings_matches[0] : null;

        // Add the recipe to the 'recipes' table
        $stmt_add_recipe->execute([
            'title' => $title,
            'instructions' => $instructions,
            'image_url' => $image_url,
            'prep_time_minutes' => $prep_time_minutes,
            'cook_time_minutes' => $cook_time_minutes,
            'servings' => $servings,
            'author_id' => 1, // Default author
            'category_id' => rand(1, 4), // Assign a random category
            'occasion_id' => rand(1, 4)  // Assign a random occasion
        ]);
        $recipe_id = $pdo->lastInsertId();

        // Process and link ingredients
        foreach ($recipe['ingredients'] as $ingredient_str) {
            $ingredient_name = trim(preg_replace('/^[\\d\\/\\s.()]+(oz|g|kg|lb|cup|tbsp|tsp|pinch|of)?\\s*/i', '', $ingredient_str));
            $ingredient_name = ucfirst(strtolower($ingredient_name));

            $stmt_find_ingredient->execute([$ingredient_name]);
            $ingredient_id = $stmt_find_ingredient->fetchColumn();

            if (!$ingredient_id) {
                $stmt_add_ingredient->execute([$ingredient_name]);
                $ingredient_id = $pdo->lastInsertId();
            }
            $stmt_link_ingredient->execute([$recipe_id, $ingredient_id, $ingredient_str]);
        }
        
        $imported_count++;
        echo "Imported: $title\n";
    }

    // 7. Commit the transaction if everything was successful
    $pdo->commit();
    echo "\nSUCCESS: $imported_count of $recipe_count recipes were imported successfully!\n";

} catch (Exception $e) {
    // 8. Roll back the transaction if any error occurred
    $pdo->rollBack();
    die("\nERROR: An error occurred during the import. The transaction has been rolled back. Error message: " . $e->getMessage() . "\n");
}

echo "</pre>";
?>

