<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/db_mongo.php";

echo "<h2>Comment System Debug</h2>";
echo "<p><strong>Current User:</strong> " . $_SESSION['username'] . " (ID: " . $_SESSION['user_id'] . ")</p>";

// Test MongoDB connection
try {
    $coll = getCollection('coding_platform', 'comments');
    echo "<p>✅ MongoDB connection successful</p>";
    
    // Check if comments collection exists and has data
    $query = new MongoDB\Driver\Query([]);
    $result = $coll['manager']->executeQuery($coll['db'] . ".comments", $query)->toArray();
    echo "<p><strong>Total comments in database:</strong> " . count($result) . "</p>";
    
    if (count($result) > 0) {
        echo "<h3>All Comments in Database:</h3>";
        foreach ($result as $comment) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<strong>User ID:</strong> " . $comment->user_id . "<br>";
            echo "<strong>Username:</strong> " . $comment->username . "<br>";
            echo "<strong>Comment:</strong> " . $comment->comment . "<br>";
            echo "<strong>Rating:</strong> " . ($comment->rating ?? 0) . "<br>";
            echo "<strong>Status:</strong> " . ($comment->status ?? 'unknown') . "<br>";
            echo "<strong>Created:</strong> " . $comment->created_at->toDateTime()->format('Y-m-d H:i:s') . "<br>";
            echo "</div>";
        }
    } else {
        echo "<p>❌ No comments found in database</p>";
    }
    
    // Check if current user has already submitted a comment
    $userQuery = new MongoDB\Driver\Query(['user_id' => $_SESSION['user_id']]);
    $userComments = $coll['manager']->executeQuery($coll['db'] . ".comments", $userQuery)->toArray();
    echo "<p><strong>Comments by current user:</strong> " . count($userComments) . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Error details: " . $e->getTraceAsString() . "</p>";
}

echo "<hr>";
echo "<h3>Test Comment Submission</h3>";
echo "<form method='POST' action='comment.php'>";
echo "<textarea name='comment' placeholder='Test comment' required>This is a test comment from debug page.</textarea><br><br>";
echo "<input type='hidden' name='rating' value='5'>";
echo "<button type='submit'>Submit Test Comment</button>";
echo "</form>";

echo "<hr>";
echo "<p><a href='dashboard.php'>Back to Dashboard</a> | <a href='comment.php'>Go to Comment Page</a></p>";
?>
