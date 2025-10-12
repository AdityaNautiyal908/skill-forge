<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../config/db_mongo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/submissions.php');
    exit;
}

$id = isset($_POST['id']) ? trim($_POST['id']) : '';
if ($id === '') {
    header('Location: ../public/submissions.php');
    exit;
}

try {
    $coll = getCollection('coding_platform', 'submissions');
    $bulk = new MongoDB\Driver\BulkWrite;
    // Soft delete: mark as deleted instead of removing
    $bulk->update(
        ['_id' => new MongoDB\BSON\ObjectId($id)],
        ['$set' => [
            'deleted' => true,
            'deleted_at' => new MongoDB\BSON\UTCDateTime(),
            'deleted_by' => (int)$_SESSION['user_id']
        ]]
    );
    $coll['manager']->executeBulkWrite($coll['db'] . '.' . $coll['collection'], $bulk);
    header('Location: ../public/submissions.php');
    exit;
} catch (Throwable $e) {
    header('Location: submissions.php');
    exit;
}
?>


