<?php
session_start();
header('Content-Type: application/json');

// This file handles all notification operations

// Function to set a notification flag for all users except the sender
function setGlobalNotification($senderId) {
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
        'last_sender_id' => $senderId
    ];
    
    // Save notifications - overwrite any existing file
    file_put_contents($notificationFile, json_encode($notifications));
    
    // Log for debugging
    error_log("Notification set by user $senderId at " . date('Y-m-d H:i:s'));
    
    return true;
}

// Function to check if there are new notifications for a user
function checkNotifications($userId) {
    $notificationFile = __DIR__ . '/data/qa_notifications.json';
    
    // If file doesn't exist, no notifications
    if (!file_exists($notificationFile)) {
        return ['has_notifications' => false];
    }
    
    // Load notifications
    $jsonData = file_get_contents($notificationFile);
    if (empty($jsonData)) {
        return ['has_notifications' => false];
    }
    
    $notifications = json_decode($jsonData, true);
    
    // If this user is the sender, don't show notification
    if (isset($notifications['last_sender_id']) && $notifications['last_sender_id'] == $userId) {
        return ['has_notifications' => false];
    }
    
    return ['has_notifications' => true];
}

// Handle API requests
if (isset($_GET['action'])) {
    $response = ['success' => false];
    
    if ($_GET['action'] === 'check') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'has_notifications' => false]);
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $result = checkNotifications($userId);
        $response = ['success' => true] + $result;
    }
    
    if ($_GET['action'] === 'clear') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false]);
            exit;
        }
        
        // Clear notification by updating the last_qa_check in MongoDB
        require_once "config/db_mongo.php";
        
        try {
            $userId = $_SESSION['user_id'];
            $dbName = 'coding_platform';
            $userPrefsColl = getCollection($dbName, 'user_prefs');
            
            $bulk = new MongoDB\Driver\BulkWrite;
            $filter = ['user_id' => (int)$userId];
            $update = ['$set' => ['last_qa_check' => new MongoDB\BSON\UTCDateTime(time() * 1000)]];
            $options = ['upsert' => true];
            
            $bulk->update($filter, $update, $options);
            $result = $userPrefsColl['manager']->executeBulkWrite($dbName . '.user_prefs', $bulk);
            
            $response['success'] = true;
        } catch (Exception $e) {
            error_log("Error clearing notifications: " . $e->getMessage());
            $response['error'] = $e->getMessage();
        }
    }
    
    echo json_encode($response);
    exit;
}
    
    // Check if there are new messages and the user is not the sender
    $hasNotifications = isset($notifications['has_new_message']) && 
                        $notifications['has_new_message'] === true && 
                        (!isset($notifications['last_sender_id']) || 
                         $notifications['last_sender_id'] != $userId);
    
    return [
        'has_notifications' => $hasNotifications,
        'count' => $hasNotifications ? 1 : 0
    ];
}

// Function to clear notifications for a user
function clearNotifications($userId) {
    $notificationFile = __DIR__ . '/data/qa_notifications.json';
    
    // If file doesn't exist, nothing to clear
    if (!file_exists($notificationFile)) {
        return true;
    }
    
    // Load notifications
    $jsonData = file_get_contents($notificationFile);
    if (empty($jsonData)) {
        return true;
    }
    
    $notifications = json_decode($jsonData, true) ?: [];
    
    // Mark as read for this user
    if (isset($notifications['user_read'])) {
        $notifications['user_read'][$userId] = time();
    } else {
        $notifications['user_read'] = [$userId => time()];
    }
    
    // Save notifications
    file_put_contents($notificationFile, json_encode($notifications));
    
    return true;
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_SESSION['user_id'] ?? 0;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    
    switch ($action) {
        case 'set':
            $result = setGlobalNotification($userId);
            echo json_encode(['success' => $result]);
            break;
            
        case 'clear':
            $result = clearNotifications($userId);
            echo json_encode(['success' => $result]);
            break;
            
        case 'check':
            $result = checkNotifications($userId);
            echo json_encode(['success' => true, 'has_notifications' => $result['has_notifications'], 'count' => $result['count']]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'check') {
    $userId = $_SESSION['user_id'] ?? 0;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    
    $result = checkNotifications($userId);
    echo json_encode(['success' => true, 'has_notifications' => $result['has_notifications'], 'count' => $result['count']]);
}
?>