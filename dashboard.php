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
// Fetch languages present in DB
$languages = $coll['manager']->executeCommand($coll['db'], $command)->toArray()[0]->values ?? [];

// Also include supported languages explicitly so new tracks appear when added
$supportedLanguages = ['c', 'cpp', 'java', 'javascript', 'html', 'css', 'python'];
$languages = array_merge($languages, $supportedLanguages);

// Normalize, unique, and sort for consistent display
$languages = array_values(array_unique(array_filter(array_map(function($l){
    return is_string($l) ? strtolower(trim($l)) : $l;
}, $languages), function($l){ return !empty($l); })));
sort($languages, SORT_STRING | SORT_FLAG_CASE);

// Friendly display names
$languageLabels = [
    'c' => 'C',
    'cpp' => 'C++',
    'c++' => 'C++',
    'java' => 'Java',
    'javascript' => 'JavaScript',
    'js' => 'JavaScript',
    'html' => 'HTML',
    'css' => 'CSS',
    'python' => 'Python',
];

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
<title>SkillForge — Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    margin: 0;
    color: white;
    min-height: 100vh;
    background: radial-gradient(1200px 600px at 10% 10%, rgba(167,119,227,0.25), transparent 60%),
                radial-gradient(1000px 600px at 90% 30%, rgba(110,142,251,0.25), transparent 60%),
                linear-gradient(135deg, #0f1020, #111437 60%, #0a0d2a);
    overflow-x: hidden;
}
.stars { position: fixed; inset: 0; background: radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,0.8), transparent 60%), radial-gradient(1px 1px at 40% 70%, rgba(255,255,255,0.6), transparent 60%), radial-gradient(1px 1px at 65% 25%, rgba(255,255,255,0.7), transparent 60%), radial-gradient(1px 1px at 80% 55%, rgba(255,255,255,0.5), transparent 60%); opacity: .5; pointer-events: none; }
.orb { position:absolute; border-radius:50%; filter: blur(20px); opacity:.5; animation: float 12s ease-in-out infinite; }
.o1{ width: 200px; height: 200px; background:#6e8efb; top:-60px; left:-60px; }
.o2{ width: 260px; height: 260px; background:#a777e3; bottom:-80px; right:10%; animation-delay:2s; }
@keyframes float { 0%,100%{ transform:translateY(0)} 50%{ transform:translateY(-14px)} }

.navbar {
    background: rgba(0,0,0,0.35) !important;
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.navbar-brand { font-weight: 700; }

.section {
    position: relative;
    z-index: 1;
}
.card {
    background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
    border: 1px solid rgba(255,255,255,0.08);
    color: white;
}
.card-title { font-weight: 700; }
.card-text { color: rgba(255,255,255,0.8); }
.btn-primary {
    background: linear-gradient(135deg, #6e8efb, #a777e3);
    border: none;
    box-shadow: 0 8px 30px rgba(110,142,251,0.35);
}
.heading { font-weight: 800; }
</style>
</head>
<body>
<div class="stars"></div>
<div class="orb o1"></div>
<div class="orb o2"></div>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="#">SkillForge</a>
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

<div class="container mt-5 section">
    <h2 class="mb-4 heading">Choose a Language to Practice</h2>
    <div class="row">
        <?php foreach ($languages as $lang): ?>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-1"><?= $languageLabels[strtolower($lang)] ?? ucfirst($lang) ?></h5>
                        <p class="card-text mb-3"><?= $problems_count[$lang] ?> problems available</p>
                        <a href="problems.php?language=<?= $lang ?>" class="btn btn-primary">Start Practicing</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
