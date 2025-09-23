<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/db_mongo.php";

// Get POST data
$problem_id = $_POST['problem_id'] ?? null;
$language   = $_POST['language'] ?? null;
$code       = $_POST['code'] ?? '';

if (!$problem_id || !$language || !$code) {
    die("Missing required fields!");
}

// Get MongoDB collection for submissions
$coll = getCollection('coding_platform', 'submissions');

// Create a BulkWrite object
$bulk = new MongoDB\Driver\BulkWrite;

// Prepare submission document
$submission = [
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
