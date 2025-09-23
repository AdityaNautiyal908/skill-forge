<?php
// Connect to MongoDB without Composer
$manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");

function getCollection($db, $collection) {
    $manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
    return [
        'manager' => $manager,
        'db' => $db,
        'collection' => $collection
    ];
}

// Example usage:
// $problemsCollection = getCollection('coding_platform', 'problems');
?>
