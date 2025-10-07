<?php
session_start();

require_once "config/db_mongo.php";

// 1. Security Check: Must be logged in and an Admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

// 2. Get User ID to Delete
$user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';
$return_to = isset($_GET['return_to']) ? $_GET['return_to'] : 'leaderboard.php';

// Validate that the ID is numeric and non-negative.
// We allow '0' (which is the problematic user) but block empty string or negative numbers.
if (!is_numeric($user_id) || $user_id < 0 || $user_id === '') {
    // Block non-numeric or negative IDs
    header("Location: " . $return_to);
    exit;
}

$user_id = (int)$user_id;

try {
    $coll = getCollection('coding_platform', 'submissions');
    
    // 3. Prepare the MongoDB delete command
    $bulk = new MongoDB\Driver\BulkWrite;
    
    // Store the filter for diagnostic purposes
    $filter = ['user_id' => $user_id]; 

    if ($user_id === 0) {
        // Use the robust $or filter for user ID 0 to handle type mismatches (int 0 or string "0")
        $filter = [
            '$or' => [
                ['user_id' => 0],
                ['user_id' => '0'],
                ['user_id' => new MongoDB\BSON\Int64('0')],
                ['user_id' => new MongoDB\BSON\Type\Double(0.0)],
            ]
        ];
        $bulk->delete($filter);
    } else {
        // Use the strict comparison for all other non-zero IDs
        $bulk->delete(['user_id' => $user_id]);
    }

    // 4. Execute the deletion
    $result = $coll['manager']->executeBulkWrite($coll['db'] . '.' . 'submissions', $bulk);
    
    $deletedCount = $result->getDeletedCount();

    // *** DIAGNOSTIC FEEDBACK ADDED HERE ***
    if ($deletedCount === 0) {
        // Report an issue if 0 documents were deleted
        $_SESSION['error'] = "0 submissions were deleted for User ID #{$user_id}. The entry may still exist due to a database type mismatch. Filter attempted: " . json_encode($filter);
    } else {
        // Report success
        $_SESSION['message'] = "Successfully removed {$deletedCount} submissions for User ID #{$user_id}.";
    }
    // *** END DIAGNOSTIC FEEDBACK ***

} catch (Throwable $e) {
    // Report a full system error
    $_SESSION['error'] = "Critical MongoDB Error (User ID #{$user_id}): " . $e->getMessage();
}

// 5. Redirect back to the leaderboard
header("Location: " . $return_to);
exit;