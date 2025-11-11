<?php
// api_rate_recipe.php
// Handles submitting a user's rating for a recipe.

session_start();
header('Content-Type: application/json');

// 1. Authenticate User
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'You must be logged in to rate a recipe.']);
    exit;
}

// 2. Get Input Data
$data = json_decode(file_get_contents('php://input'), true);
$recipe_id = $data['recipe_id'] ?? null;
$rating = $data['rating'] ?? null;

if (!$recipe_id || !is_numeric($recipe_id) || !$rating || !in_array($rating, [1, 2, 3, 4, 5])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid data provided.']);
    exit;
}

// 3. Connect to Database and Perform Logic
require_once __DIR__ . '/db.php';
$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // 4. Insert or update the user's rating in the `recipe_ratings` table.
    $sql = "INSERT INTO recipe_ratings (user_id, recipe_id, rating) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE rating = VALUES(rating)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $recipe_id, $rating]);

    // 5. Recalculate the average rating and count for the recipe.
    $update_sql = "UPDATE recipes r SET 
                   r.average_rating = (SELECT AVG(rr.rating) FROM recipe_ratings rr WHERE rr.recipe_id = r.id),
                   r.rating_count = (SELECT COUNT(rr.id) FROM recipe_ratings rr WHERE rr.recipe_id = r.id)
                   WHERE r.id = ?";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([$recipe_id]);

    // 6. Fetch the newly calculated average and count to send back to the user.
    $fetch_sql = "SELECT average_rating, rating_count FROM recipes WHERE id = ?";
    $fetch_stmt = $pdo->prepare($fetch_sql);
    $fetch_stmt->execute([$recipe_id]);
    $new_stats = $fetch_stmt->fetch();

    $pdo->commit();

    // 7. Send a success response with the new data.
    echo json_encode([
        'ok' => true, 
        'message' => 'Thank you for your rating!',
        'new_average_rating' => (float)$new_stats['average_rating'],
        'new_rating_count' => (int)$new_stats['rating_count']
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("API Error rating recipe: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'A database error occurred.']);
}
?>
