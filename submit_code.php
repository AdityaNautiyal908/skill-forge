<?php
session_start();
require_once "config/db_mongo.php";

// Check if a user is logged in.
// If not, redirect them to the login page.
// The `user_id` is set to 'guest' for guest users.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get POST data
$problem_id = $_POST['problem_id'] ?? null;
$language   = $_POST['language'] ?? null;
$code       = $_POST['code'] ?? '';

// Check if the user is a guest and trying to submit code
if ($_SESSION['user_id'] === 'guest') {
    // Redirect guests to the login/registration page with a message
    header("Location: login.php?prompt_register=true");
    exit;
}

// Proceed with submission for registered users
if (!$problem_id || !$language || !$code) {
    die("Missing required fields!");
}

// Get MongoDB collection for submissions
$coll = getCollection('coding_platform', 'submissions');

// Create a BulkWrite object
$bulk = new MongoDB\Driver\BulkWrite;

// Prepare submission document
$submission = [
    'type' => 'code',
    'user_id' => $_SESSION['user_id'],
    'problem_id' => $problem_id,
    'language' => $language,
    'code' => $code,
    'submitted_at' => new MongoDB\BSON\UTCDateTime()
];

// Add insert operation to BulkWrite
$bulk->insert($submission);

// Execute the BulkWrite
try {
    $coll['manager']->executeBulkWrite($coll['db'] . "." . $coll['collection'], $bulk);
    // Redirect back to dashboard or problem page
    header("Location: dashboard.php?msg=Code submitted successfully!");
    exit;
} catch (Exception $e) {
    die("Error storing submission: " . $e->getMessage());
}
?>