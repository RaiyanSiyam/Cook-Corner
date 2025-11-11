<?php
// save_meal_plan.php
// This script saves a generated meal plan for a logged-in user.

session_start();
header('Content-Type: application/json');

// 1. Authenticate User: Make sure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['ok' => false, 'error' => 'You must be logged in to save a plan.']);
    exit;
}

// 2. Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['ok' => false, 'error' => 'Invalid request method.']);
    exit;
}

// 3. Get and validate data from the request body
$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['plan']) || !is_array($data['plan'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['ok' => false, 'error' => 'Invalid or missing plan data.']);
    exit;
}

// 4. Connect to the database
require_once __DIR__ . '/db.php';

$user_id = $_SESSION['user_id'];
$plan_data_json = json_encode($data['plan']);

// 5. Insert the plan into the database
try {
    $sql = "INSERT INTO user_meal_plans (user_id, plan_data) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $plan_data_json]);
    
    // 6. Send a success response
    echo json_encode(['ok' => true, 'message' => 'Plan saved successfully!']);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    error_log("Error saving meal plan for user $user_id: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Could not save the plan due to a database error.']);
}
?>

