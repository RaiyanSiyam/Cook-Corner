<?php
// signup.php
// This script handles new user registration.

// Set the content type to application/json for AJAX responses.
header('Content-Type: application/json');

// 1. Ensure the request method is POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); // Method Not Allowed
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

// 2. Include the database connection.
require __DIR__ . '/db.php';

// 3. Get and validate the input from the form.
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');

if (empty($name) || empty($email) || empty($password)) {
  http_response_code(400); // Bad Request
  echo json_encode(['ok' => false, 'error' => 'All fields are required.']);
  exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid email format.']);
  exit;
}

if (strlen($password) < 8) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Password must be at least 8 characters long.']);
  exit;
}

// 4. Check if the user already exists.
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      http_response_code(409); // Conflict
      echo json_encode(['ok' => false, 'error' => 'An account with this email already exists.']);
      exit;
    }

    // 5. Hash the password for secure storage.
    // PASSWORD_BCRYPT is the current standard.
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // 6. Insert the new user into the database.
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $password_hash]);

    // 7. Respond with success.
    echo json_encode(['ok' => true, 'message' => 'Account created successfully!']);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    // In a real app, log this error instead of showing it to the user.
    // error_log($e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'A server error occurred. Please try again.']);
}

