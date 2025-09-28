<?php
session_start();
require_once "config/db_mongo.php";
header('Content-Type: application/json');

try {
    $dbName = 'coding_platform';
    $chatColl = getCollection($dbName, 'global_qa');
    
    // CRITICAL: Sort by timestamp in ASCENDING order (1) so that the oldest messages appear first
    $query = new MongoDB\Driver\Query(
        [],
        ['sort' => ['timestamp' => 1], 'limit' => 50] 
    );

    $messages = $chatColl['manager']->executeQuery($dbName . '.global_qa', $query)->toArray();

    $output = array_map(function($doc) {
        $timestamp = null;
        if (isset($doc->timestamp) && $doc->timestamp instanceof MongoDB\BSON\UTCDateTime) {
            $timestamp = $doc->timestamp->toDateTime()->getTimestamp() * 1000;
        }
        
        $userIdValue = $doc->user_id ?? null;
        if (is_object($userIdValue) && property_exists($userIdValue, 'value')) {
             $userIdValue = $userIdValue->value;
        }
        
        return [
            'user_id' => (int)$userIdValue,
            'username' => $doc->username ?? 'Unknown',
            'message' => $doc->message ?? '',
            'timestamp' => $timestamp,
        ];
    }, $messages);

    echo json_encode($output);

} catch (Throwable $e) {
    error_log("MongoDB Fetch Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error while fetching messages.']);
}
