<?php
// api_toggle_save.php
// The definitive backend logic for the save recipe feature.

session_start();
header('Content-Type: application/json');

// 1. Authenticate User: Make sure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['ok' => false, 'error' => 'You must be logged in to save recipes.']);
    exit;
}

// 2. Get Input Data: Read the recipe ID sent from the button's JavaScript.
$data = json_decode(file_get_contents('php://input'), true);
$recipe_id = $data['recipe_id'] ?? null;

if (!$recipe_id || !is_numeric($recipe_id)) {
    http_response_code(400); // Bad Request
    echo json_encode(['ok' => false, 'error' => 'Invalid Recipe ID provided.']);
    exit;
}

// 3. Connect to Database
require_once __DIR__ . '/db.php';
$user_id = $_SESSION['user_id'];

// 4. Perform the Save/Unsave Logic
try {
    // Check if the recipe is already saved by this user
    $check_sql = "SELECT id FROM user_saved_recipes WHERE user_id = ? AND recipe_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$user_id, $recipe_id]);
    $existing_save = $check_stmt->fetch();

    if ($existing_save) {
        // If it exists, UNSAVE it by deleting the record.
        $delete_sql = "DELETE FROM user_saved_recipes WHERE id = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([$existing_save['id']]);
        echo json_encode(['ok' => true, 'saved' => false, 'message' => 'Recipe removed!']);
    } else {
        // If it does not exist, SAVE it by inserting a new record.
        $insert_sql = "INSERT INTO user_saved_recipes (user_id, recipe_id) VALUES (?, ?)";
        $insert_stmt = $pdo->prepare($insert_sql);
        $insert_stmt->execute([$user_id, $recipe_id]);
        echo json_encode(['ok' => true, 'saved' => true, 'message' => 'Recipe saved successfully!']);
    }

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    error_log("API Error toggling saved recipe: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'A database error occurred.']);
}
?>

