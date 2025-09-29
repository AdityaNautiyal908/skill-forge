<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'User not authenticated.']));
}

require_once "config/db_mongo.php";
require_once "config/db_mysql.php";

$userId = $_SESSION['user_id'];
$dbName = 'coding_platform';

try {
    // Get the user's last seen timestamp from MySQL
    $stmt = $conn->prepare("SELECT last_qa_check FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $lastCheck = $row['last_qa_check'] ?? 0;
    
    // Get count of new messages since last check
    $chatColl = getCollection($dbName, 'global_qa');
    
    // Debug: Log the last check time
    error_log("User ID: $userId, Last check: $lastCheck");
    
    // Query for messages newer than last check
    // Note: MongoDB timestamp is stored as ISO string
    $filter = [];
    if ($lastCheck > 0) {
        $lastCheckDate = date('Y-m-d\TH:i:s', $lastCheck);
        $filter = ['timestamp' => ['$gt' => $lastCheckDate]];
    }
    $options = ['projection' => ['_id' => 1]];
    
    $query = new MongoDB\Driver\Query($filter, $options);
    $newMessages = $chatColl['manager']->executeQuery($dbName . '.global_qa', $query)->toArray();
    
    $count = count($newMessages);
    
    echo json_encode([
        'success' => true,
        'has_notifications' => $count > 0,
        'count' => $count
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error checking notifications: ' . $e->getMessage()]);
}
?>