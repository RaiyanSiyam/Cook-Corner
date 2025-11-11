<?php
// delete_saved_item.php
// This is a secure API endpoint to delete a user's saved address or card.

session_start();
header('Content-Type: application/json');

// 1. Authenticate User
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Authentication required.']);
    exit;
}

// 2. Get Input from the POST request
$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? null;
$id = $data['id'] ?? null;

if (!$type || !$id || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid request parameters.']);
    exit;
}

require_once __DIR__ . '/db.php';
$user_id = $_SESSION['user_id'];

// 3. Determine which table to delete from
$table_name = '';
switch ($type) {
    case 'address':
        $table_name = 'user_addresses';
        break;
    case 'card':
        $table_name = 'user_payment_methods';
        break;
    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid item type specified.']);
        exit;
}

try {
    // 4. Perform the deletion, ensuring the item belongs to the logged-in user
    $sql = "DELETE FROM `$table_name` WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $user_id]);

    // 5. Check if a row was actually affected
    if ($stmt->rowCount() > 0) {
        echo json_encode(['ok' => true, 'message' => ucfirst($type) . ' deleted successfully.']);
    } else {
        // This means the item didn't exist or didn't belong to the user
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Item not found or you do not have permission to delete it.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Failed to delete saved item: " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'A database error occurred.']);
}
?>
