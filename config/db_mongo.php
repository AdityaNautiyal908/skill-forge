<?php
error_reporting(E_ALL); ini_set('display_errors', 1);
// Ensure MongoDB PHP extension is available
if (!extension_loaded('mongodb')) {
    die('MongoDB PHP extension not enabled. Enable php_mongodb in php.ini and restart Apache.');
}

// Create a new Manager instance (no Composer required)
try {
    $manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
} catch (Throwable $e) {
    die('Failed to connect to MongoDB: ' . $e->getMessage());
}

function getCollection($db, $collection) {
    try {
        $manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
    } catch (Throwable $e) {
        die('Failed to connect to MongoDB: ' . $e->getMessage());
    }
    return [
        'manager' => $manager,
        'db' => $db,
        'collection' => $collection
    ];
}

// Example usage:
// $problemsCollection = getCollection('coding_platform', 'problems');
?>
