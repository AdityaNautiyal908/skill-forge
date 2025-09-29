<?php
session_start();
header('Content-Type: application/json');

// Simple notification clearing that doesn't rely on MongoDB
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$userId = $_SESSION['user_id'];
$notificationFile = __DIR__ . '/data/qa_notifications.json';

// If file doesn't exist, nothing to clear
if (!file_exists($notificationFile)) {
    echo json_encode(['success' => true]);
    exit;
}

// Load notifications
$jsonData = file_get_contents($notificationFile);
if (empty($jsonData)) {
    echo json_encode(['success' => true]);
    exit;
}

$notifications = json_decode($jsonData, true);

// Mark this user as having seen the notifications
if (isset($notifications['last_sender_id']) && $notifications['last_sender_id'] != $userId) {
    // Create a user-specific file to track that they've seen the notification
    $userSeenFile = __DIR__ . '/data/user_' . $userId . '_seen.txt';
    file_put_contents($userSeenFile, time());
}

echo json_encode(['success' => true]);
?>