<?php
// submit_review.php
// Handles submission of a new recipe review, including photo upload.

session_start();
require_once __DIR__ . '/db.php';

// --- 1. Security and Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html?message=Please log in to leave a review.'); exit;
}

$recipe_id = isset($_POST['recipe_id']) ? (int)$_POST['recipe_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$review_text = trim($_POST['review_text'] ?? '');

if ($recipe_id <= 0 || !in_array($rating, [1, 2, 3, 4, 5])) {
    header('Location: recipe_details.php?id=' . $recipe_id . '&error=' . urlencode('A star rating is required.')); exit;
}

// --- 2. Handle Optional File Upload ---
$image_path = null;
if (isset($_FILES['review_image']) && $_FILES['review_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

    $file = $_FILES['review_image'];
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        header('Location: recipe_details.php?id=' . $recipe_id . '&error=' . urlencode('Image file is too large (max 5MB).')); exit;
    }
    $allowed_types = ['image/jpeg', 'image/png'];
    if (!in_array($file['type'], $allowed_types)) {
        header('Location: recipe_details.php?id=' . $recipe_id . '&error=' . urlencode('Invalid image type (JPG or PNG only).')); exit;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $unique_filename = 'review_' . $recipe_id . '_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
    $destination = $upload_dir . $unique_filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $image_path = 'uploads/' . $unique_filename;
    } else {
        header('Location: recipe_details.php?id=' . $recipe_id . '&error=' . urlencode('Failed to save image.')); exit;
    }
}

// --- 3. Save to Database and Recalculate Average ---
try {
    $pdo->beginTransaction();

    // Insert or update the user's review. `ON DUPLICATE KEY UPDATE` lets users change their review.
    $sql = "INSERT INTO recipe_reviews (recipe_id, user_id, rating, review_text, image_url)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), review_text = VALUES(review_text), image_url = IF(VALUES(image_url) IS NOT NULL, VALUES(image_url), image_url)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$recipe_id, $_SESSION['user_id'], $rating, $review_text, $image_path]);

    // Recalculate the average rating and count for the main recipe table
    $update_sql = "UPDATE recipes r SET 
                   r.average_rating = (SELECT AVG(rr.rating) FROM recipe_reviews rr WHERE rr.recipe_id = r.id),
                   r.rating_count = (SELECT COUNT(rr.id) FROM recipe_reviews rr WHERE rr.recipe_id = r.id)
                   WHERE r.id = ?";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([$recipe_id]);

    $pdo->commit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    error_log("Submit review error: " . $e->getMessage());
    header('Location: recipe_details.php?id=' . $recipe_id . '&error=' . urlencode('A database error occurred.')); exit;
}

// --- 4. Redirect Back on Success ---
header('Location: recipe_details.php?id=' . $recipe_id . '&review_success=1');
exit;
?>
