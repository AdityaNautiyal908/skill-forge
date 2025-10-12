<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'User not authenticated.']));
}

require_once "../config/db_mysql.php";

$userId = $_SESSION['user_id'];
$currentTime = time();

try {
    // First check if the column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'last_qa_check'");
    
    // If column doesn't exist, create it
    if ($checkColumn->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN last_qa_check BIGINT DEFAULT 0");
    }
    
    // Update the user's last seen timestamp
    $stmt = $conn->prepare("UPDATE users SET last_qa_check = ? WHERE id = ?");
    $stmt->bind_param("ii", $currentTime, $userId);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error updating last seen: ' . $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
?>