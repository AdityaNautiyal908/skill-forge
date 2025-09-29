<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'User not authenticated.']));
}

require_once "config/db_mysql.php";
require_once "config/db_mongo.php"; 

$userId = $_SESSION['user_id'];
$message = trim($_POST['message'] ?? '');
$dbName = 'coding_platform'; // Confirmed database name

if (empty($message)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Message cannot be empty.']));
}

// --- 1. Fetch Username from MySQL ---
$username = 'Unknown User';
try {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $username = $row['username'];
    }
} catch (Throwable $e) {
    // Failsafe if MySQL fails
}

// --- 2. Insert Message into MongoDB ---
try {
    $chatColl = getCollection($dbName, 'global_qa');
    
    $document = [
        'user_id' => (int)$userId,
        'username' => $username,
        'message' => $message,
        'timestamp' => new MongoDB\BSON\UTCDateTime(),
    ];

    // Build the BulkWrite object correctly and pass it as the second argument
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->insert($document); 

    $result = $chatColl['manager']->executeBulkWrite(
        $dbName . '.global_qa', // Argument 1: Namespace
        $bulk                   // Argument 2: The BulkWrite object
    );
    
    if ($result->getInsertedCount() > 0) {
        // Set notification for all users - but don't require the file
        // Just create the notification directly
        $notificationFile = __DIR__ . '/data/qa_notifications.json';
        $dirPath = __DIR__ . '/data';
        
        // Create directory if it doesn't exist
        if (!file_exists($dirPath)) {
            mkdir($dirPath, 0777, true);
        }
        
        // Set notification with current timestamp
        $notifications = [
            'has_new_message' => true,
            'last_message_time' => time(),
            'last_sender_id' => $userId
        ];
        
        // Save notifications - overwrite any existing file
        file_put_contents($notificationFile, json_encode($notifications));
        
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to insert message.']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    error_log("Error posting message: " . $e->getMessage());
}
