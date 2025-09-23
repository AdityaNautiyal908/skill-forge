<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/db_mongo.php";

$coll = getCollection('coding_platform', 'problems');

// Fetch unique languages
$command = new MongoDB\Driver\Command([
    'distinct' => 'problems',
    'key' => 'language'
]);
$languages = $coll['manager']->executeCommand($coll['db'], $command)->toArray()[0]->values ?? [];

// Count problems per language
$problems_count = [];
foreach ($languages as $lang) {
    $query = new MongoDB\Driver\Query(['language' => $lang]);
    $count = count($coll['manager']->executeQuery($coll['db'] . ".problems", $query)->toArray());
    $problems_count[$lang] = $count;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">CodeLearn</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="nav-link text-white">Hello, <?= $_SESSION['username'] ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h2 class="mb-4">Choose a Language to Practice</h2>
    <div class="row">
        <?php foreach ($languages as $lang): ?>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-capitalize"><?= $lang ?></h5>
                        <p class="card-text"><?= $problems_count[$lang] ?> problems available</p>
                        <a href="problems.php?language=<?= $lang ?>" class="btn btn-primary">Start Practicing</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
