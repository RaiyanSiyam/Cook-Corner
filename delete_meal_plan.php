<?php
// delete_meal_plan.php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['plan_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$plan_id = (int)$_POST['plan_id'];

try {
    // Ensure the user owns this plan before deleting
    $sql = "DELETE FROM user_meal_plans WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$plan_id, $user_id]);
} catch (PDOException $e) {
    error_log("Error deleting meal plan: " . $e->getMessage());
}

header('Location: saved_meal_plans.php');
exit;
?>
