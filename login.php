<?php
// login.php (Universal Login Handler for AJAX)
// This script processes login attempts from an AJAX-based login form.

// Set the content type to application/json for all responses.
header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Ensure this script is accessed via a POST request.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}

// 2. Include the database connection.
require __DIR__ . '/db.php';

// 3. Get input from the request. This is a more robust way to handle both
//    AJAX JSON requests and standard form submissions.
$email = '';
$password = '';

// Try to get data from the JSON body first (for modern AJAX requests)
$input = json_decode(file_get_contents('php://input'), true);
if (is_array($input) && isset($input['email'])) {
    $email = trim($input['email']);
    $password = (string)($input['password'] ?? '');
}

// If the JSON body was empty, fall back to the standard $_POST array.
// This makes the script more compatible with different server configurations.
if (empty($email) && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $password = (string)($_POST['password'] ?? '');
}


if (empty($email) || empty($password) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); // Bad Request
    echo json_encode(['ok' => false, 'error' => 'Invalid email or password provided.']);
    exit;
}

// 4. Find the user in the database.
try {
    // Select all necessary user data, including the 'is_admin' flag.
    $stmt = $pdo->prepare("SELECT id, name, password_hash, is_admin FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 5. Verify the user exists and the password is correct.
    if ($user && password_verify($password, $user['password_hash'])) {
        // --- Login Successful ---

        // Regenerate session ID for security.
        session_regenerate_id(true);

        // Store user information in the session.
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];

        // 6. Determine the redirect URL based on the user's role.
        $redirectUrl = $_SESSION['is_admin'] ? 'homepage.php' : 'homepage.php';

        // Respond with success and the redirect URL in JSON format.
        echo json_encode(['ok' => true, 'redirect' => $redirectUrl]);
        exit;

    } else {
        // --- Login Failed ---
        http_response_code(401); // Unauthorized
        echo json_encode(['ok' => false, 'error' => 'Invalid credentials. Please check your email and password.']);
        exit;
    }

} catch (PDOException $e) {
    // Handle database errors.
    error_log("Login Error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['ok' => false, 'error' => 'A server error occurred. Please try again later.']);
    exit;
}
?>