<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/db_mongo.php";

// Get language from URL
$language = $_GET['language'] ?? null;
if (!$language) die("Language not specified!");

// Fetch the first problem of this language from MongoDB
$coll = getCollection('coding_platform', 'problems');
$query = new MongoDB\Driver\Query(['language' => $language], ['limit' => 1]);
$problemArr = $coll['manager']->executeQuery($coll['db'] . ".problems", $query)->toArray();

if (count($problemArr) == 0) die("Problem not found for this language!");
$problem = $problemArr[0];

// Handle form submission on the same page
$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    if ($code) {
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
        $successMessage = "Code submitted successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($problem->title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.23.1/ace.js" integrity="sha512-Ig/..." crossorigin="anonymous"></script>
<style>
    #editor {
        height: 400px;
        width: 100%;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
</style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2><?= htmlspecialchars($problem->title) ?></h2>
    <p><?= htmlspecialchars($problem->description) ?></p>
    <hr>

    <!-- Success message -->
    <?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($successMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <h5>Write your code below:</h5>
    <form method="POST" action="">
    <input type="hidden" name="problem_id" value="<?= $problem->_id ?>">
    <input type="hidden" name="language" value="<?= htmlspecialchars($problem->language) ?>">

    <div id="editor"><?= htmlspecialchars($problem->starter_code ?? '') ?></div>
    <textarea name="code" style="display:none;"></textarea>

    <button type="submit" class="btn btn-success mt-3">Submit Code</button>
</form>

</div>

<script>
    // Initialize ACE editor
    var editor = ace.edit("editor");
    editor.setTheme("ace/theme/monokai");
    editor.session.setMode("ace/mode/<?= htmlspecialchars($problem->language) ?>");
    editor.setOptions({
        fontSize: "14pt",
        showPrintMargin: false
    });

    // Sync ACE editor content to hidden textarea on submit
    var form = document.querySelector("form");
    form.addEventListener("submit", function() {
        document.querySelector("textarea[name='code']").value = editor.getValue();
    });
</script>

<!-- Bootstrap JS for dismissible alert -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
