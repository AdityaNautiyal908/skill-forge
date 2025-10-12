<?php
session_start();
header('Content-Type: application/json');

require_once "../config/db_mysql.php";

$response = ['has_new_messages' => false];

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === 'guest') {
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['user_id'];
$notificationFile = __DIR__ . '/../data/qa_notifications.json';

// 1. Get user's last_qa_check from MySQL
$lastQaCheck = 0;
try {
    $stmt = $conn->prepare("SELECT last_qa_check FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $lastQaCheck = (int)$row['last_qa_check'];
    }
} catch (Throwable $e) {
    error_log("Error fetching last_qa_check for user {$userId}: " . $e->getMessage());
}

// 2. Read global chat notification file
if (file_exists($notificationFile)) {
    $jsonData = file_get_contents($notificationFile);
    $notifications = json_decode($jsonData, true);

    if (isset($notifications['has_new_message']) && $notifications['has_new_message'] === true) {
        $lastMessageTime = $notifications['last_message_time'] ?? 0;
        $lastSenderId = $notifications['last_sender_id'] ?? 0;

        // Only show notification if there's a new message AND it's not from the current user
        // AND the message is newer than the user's last check
        if ($lastMessageTime > $lastQaCheck && (int)$lastSenderId !== (int)$userId) {
            $response['has_new_messages'] = true;
        }
    }
}

echo json_encode($response);
exit;
?>