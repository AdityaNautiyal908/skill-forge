<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/db_mongo.php";

// Check if the user is a guest
$is_guest = $_SESSION['user_id'] === 'guest';
$username = $_SESSION['username'] ?? 'Guest';

// Get language and index from URL
$language = $_GET['language'] ?? null;
if (!$language) die("Language not specified!");
$index = isset($_GET['index']) ? (int)$_GET['index'] : 0;

// Fetch all problems of this language (sorted so new ones slot in automatically)
$coll = getCollection('coding_platform', 'problems');
$query = new MongoDB\Driver\Query(
    ['language' => $language],
    [
        // If documents have an 'order' field, use it; otherwise fallback to _id
        'sort' => ['order' => 1, '_id' => 1]
    ]
);
$allProblems = $coll['manager']->executeQuery($coll['db'] . ".problems", $query)->toArray();

if (count($allProblems) == 0) die("No problems found for this language!");

// Ensure index is within bounds
if ($index < 0) $index = 0;
if ($index >= count($allProblems)) $index = count($allProblems) - 1;

$problem = $allProblems[$index];

// Handle form submission
$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the user is a guest before allowing submission
    if ($_SESSION['user_id'] === 'guest') {
        // Redirect the user to the login/registration page
        header("Location: login.php?prompt_register=true");
        exit;
    }

    $code = $_POST['code'] ?? '';
    if ($code) {
        // Insert submission in MongoDB
        $subColl = getCollection('coding_platform', 'submissions');
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->insert([
            'type' => 'code',
            'user_id' => $_SESSION['user_id'],
            'problem_id' => $problem->_id,
            'language' => $problem->language,
            'code' => $code,
            'submitted_at' => new MongoDB\BSON\UTCDateTime()
        ]);
        $subColl['manager']->executeBulkWrite($subColl['db'] . "." . $subColl['collection'], $bulk);

        // Increment index for next problem
        $index++;
        $message = "Code submitted successfully!";
        if ($index < count($allProblems)) {
            header("Location: problems.php?language=$language&index=$index&msg=" . urlencode($message));
            exit;
        } else {
            header("Location: problems.php?language=$language&index=" . ($index-1) . "&msg=" . urlencode("Congratulations! You completed all problems!"));
            exit;
        }
    }
}

// Display success message if any
if (isset($_GET['msg'])) $successMessage = $_GET['msg'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SkillForge — <?= htmlspecialchars($problem->title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets\css\problems.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.23.1/ace.js" crossorigin="anonymous"></script>
</head>
<body>
<div class="stars"></div>
<canvas id="webProb" class="web"></canvas>
<div class="orb o1"></div>
<div class="orb o2"></div>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">SkillForge</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-center">

                <li class="nav-item me-3">
                    <div class="nav-toggles">
                        <button id="themeToggleBtn" class="toggle-btn-nav" title="Toggle Theme">
                            <span class="icon-wrapper icon-sun">☀️</span>
                            <span class="icon-wrapper icon-moon">🌙</span>
                        </button>
                        <button id="animToggleBtn" class="toggle-btn-nav" title="Toggle Animations">
                            <span class="icon-wrapper icon-on">✨</span>
                            <span class="icon-wrapper icon-off">🚫</span>
                        </button>
                    </div>
                </li>

                <li class="nav-item">
                    <span class="nav-link text-white">Hello, <?= htmlspecialchars($username) ?></span>
                </li>
                <?php if ($is_guest): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login / Register</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4 section">
    <div class="panel mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="title mb-1"><?= htmlspecialchars($problem->title) ?></h2>
                <p class="desc mb-0"><?= htmlspecialchars($problem->description) ?></p>
            </div>
            <div class="text-end">
                <a href="dashboard.php" id="exitBtn" class="btn btn-outline btn-sm mb-2">Exit</a>
                <div class="small">Problem <?= $index + 1 ?> of <?= count($allProblems) ?></div>
                <div class="progress" style="width:260px;">
                    <div class="progress-bar" role="progressbar" style="width: <?= round((($index + 1) / max(1, count($allProblems))) * 100) ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($successMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <h5 class="mb-2">Write your code below:</h5>
    <form method="POST" action="problems.php?language=<?= $language ?>&index=<?= $index ?>">
        <input type="hidden" name="problem_id" value="<?= $problem->_id ?>">
        <input type="hidden" name="language" value="<?= htmlspecialchars($problem->language) ?>">

        <div id="editor"><?= htmlspecialchars($problem->starter_code ?? '') ?></div>
        <textarea name="code" style="display:none;"></textarea>

        <button type="submit" class="btn btn-success btn-animated mt-3">Submit Code</button>
    </form>

    <div class="mt-4 d-flex justify-content-between align-items-end">
        <?php if ($index > 0): ?>
        <a href="problems.php?language=<?= $language ?>&index=<?= $index-1 ?>" class="btn btn-outline nav-problem">&laquo; Previous</a>
        <?php else: ?><div></div><?php endif; ?>
        
        <div class="d-flex align-items-end">
            <?php if ($index < count($allProblems)-1): ?>
            <a href="problems.php?language=<?= $language ?>&index=<?= $index+1 ?>" class="btn btn-outline nav-problem">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets\js\problems.js"></script>
</body>
</html>