<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/db_mongo.php";

// Get language and index from URL
$language = $_GET['language'] ?? null;
if (!$language) die("Language not specified!");
$index = isset($_GET['index']) ? (int)$_GET['index'] : 0;

// Fetch all problems of this language
$coll = getCollection('coding_platform', 'problems');
$query = new MongoDB\Driver\Query(['language' => $language]);
$allProblems = $coll['manager']->executeQuery($coll['db'] . ".problems", $query)->toArray();

if (count($allProblems) == 0) die("No problems found for this language!");

// Ensure index is within bounds
if ($index < 0) $index = 0;
if ($index >= count($allProblems)) $index = count($allProblems) - 1;

$problem = $allProblems[$index];

// Handle form submission
$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    if ($code) {
        // Insert submission in MongoDB
        $subColl = getCollection('coding_platform', 'submissions');
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->insert([
            'user_id' => $_SESSION['user_id'],
            'problem_id' => $problem->_id,
            'language' => $problem->language,
            'code' => $code,
            'submitted_at' => new MongoDB\BSON\UTCDateTime()
        ]);
        $subColl['manager']->executeBulkWrite($subColl['db'] . "." . $subColl['collection'], $bulk);

        // Increment index for next problem
        $index++;
        if ($index < count($allProblems)) {
            header("Location: problems.php?language=$language&index=$index&msg=" . urlencode("Code submitted successfully!"));
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
<title><?= htmlspecialchars($problem->title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.23.1/ace.js" crossorigin="anonymous"></script>
<style>
#editor { height: 400px; width: 100%; border: 1px solid #ddd; border-radius: 5px; }
</style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2><?= htmlspecialchars($problem->title) ?></h2>
    <p><?= htmlspecialchars($problem->description) ?></p>
    <hr>

    <?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($successMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <h5>Write your code below:</h5>
    <form method="POST" action="problems.php?language=<?= $language ?>&index=<?= $index ?>">
        <input type="hidden" name="problem_id" value="<?= $problem->_id ?>">
        <input type="hidden" name="language" value="<?= htmlspecialchars($problem->language) ?>">

        <div id="editor"><?= htmlspecialchars($problem->starter_code ?? '') ?></div>
        <textarea name="code" style="display:none;"></textarea>

        <button type="submit" class="btn btn-success mt-3">Submit Code</button>
    </form>

    <!-- Navigation -->
    <div class="mt-4 d-flex justify-content-between">
        <?php if ($index > 0): ?>
        <a href="problems.php?language=<?= $language ?>&index=<?= $index-1 ?>" class="btn btn-primary">&laquo; Previous</a>
        <?php else: ?><div></div><?php endif; ?>

        <?php if ($index < count($allProblems)-1): ?>
        <a href="problems.php?language=<?= $language ?>&index=<?= $index+1 ?>" class="btn btn-primary">Next &raquo;</a>
        <?php endif; ?>
    </div>
</div>

<script>
var editor = ace.edit("editor");
editor.setTheme("ace/theme/monokai");
editor.session.setMode("ace/mode/<?= htmlspecialchars($problem->language) ?>");
editor.setOptions({ fontSize:"14pt", showPrintMargin:false });

var form = document.querySelector("form");
form.addEventListener("submit", function() {
    document.querySelector("textarea[name='code']").value = editor.getValue();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
