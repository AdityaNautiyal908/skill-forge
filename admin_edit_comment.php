<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once "config/db_mongo.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_id'])) {
    $comment_id = $_POST['comment_id'];
    $comment_text = trim($_POST['comment']);
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    
    // Validate input
    if (empty($comment_text)) {
        header("Location: admin_feedback.php?error=" . urlencode("Comment cannot be empty."));
        exit;
    }
    
    if (strlen($comment_text) < 10) {
        header("Location: admin_feedback.php?error=" . urlencode("Comment must be at least 10 characters long."));
        exit;
    }
    
    if (strlen($comment_text) > 500) {
        header("Location: admin_feedback.php?error=" . urlencode("Comment must be less than 500 characters."));
        exit;
    }
    
    if ($rating < 0 || $rating > 5) {
        header("Location: admin_feedback.php?error=" . urlencode("Rating must be between 0 and 5."));
        exit;
    }
    
    try {
        $coll = getCollection('coding_platform', 'comments');
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update(
            ['_id' => new MongoDB\BSON\ObjectId($comment_id)],
            ['$set' => [
                'comment' => $comment_text,
                'rating' => $rating,
                'edited_at' => new MongoDB\BSON\UTCDateTime(),
                'edited_by' => $_SESSION['user_id']
            ]],
            ['upsert' => false]
        );
        $coll['manager']->executeBulkWrite($coll['db'] . '.comments', $bulk);
        header("Location: admin_feedback.php?message=" . urlencode("Comment updated successfully."));
        exit;
    } catch (Exception $e) {
        header("Location: admin_feedback.php?error=" . urlencode("Failed to update comment: " . $e->getMessage()));
        exit;
    }
} else {
    header("Location: admin_feedback.php");
    exit;
}
?>
