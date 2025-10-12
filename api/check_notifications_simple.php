<?php
session_start();
header('Content-Type: application/json');

// Simple notification check that doesn't rely on MongoDB
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['has_notifications' => false]);
    exit;
}

$userId = $_SESSION['user_id'];
$notificationFile = __DIR__ . '/data/qa_notifications.json';

// If file doesn't exist, no notifications
if (!file_exists($notificationFile)) {
    echo json_encode(['has_notifications' => false]);
    exit;
}

// Load notifications
$jsonData = file_get_contents($notificationFile);
if (empty($jsonData)) {
    echo json_encode(['has_notifications' => false]);
    exit;
}

$notifications = json_decode($jsonData, true);

// If this user is the sender, don't show notification
if (isset($notifications['last_sender_id']) && $notifications['last_sender_id'] == $userId) {
    echo json_encode(['has_notifications' => false]);
    exit;
}

// Check if user has already seen this notification
$userSeenFile = __DIR__ . '/data/user_' . $userId . '_seen.txt';
if (file_exists($userSeenFile)) {
    $seenTime = (int)file_get_contents($userSeenFile);
    $notificationTime = $notifications['last_message_time'] ?? 0;
    
    // If user has seen this notification already, don't show it again
    if ($seenTime >= $notificationTime) {
        echo json_encode(['has_notifications' => false]);
        exit;
    }
}

// User has notifications
echo json_encode(['has_notifications' => true]);
?>