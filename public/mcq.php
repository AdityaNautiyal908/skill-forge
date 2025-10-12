<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "../config/db_mongo.php";

$index = isset($_GET['index']) ? (int)$_GET['index'] : 0;
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$difficulty = isset($_GET['difficulty']) ? trim($_GET['difficulty']) : '';
$coll = getCollection('coding_platform', 'mcq');

// Build filter for MCQs
$filter = [];
if ($category !== '') $filter['language'] = $category;
if ($difficulty !== '') $filter['difficulty'] = $difficulty;

// Fetch all MCQs per filter
$query = new MongoDB\Driver\Query($filter, ['sort' => ['order' => 1, '_id' => 1]]);
$all = $coll['manager']->executeQuery($coll['db'] . ".mcq", $query)->toArray();
if (count($all) === 0) die('No MCQ questions found.');

// Boundary checks for index
if ($index < 0) $index = 0;
if ($index >= count($all)) $index = count($all) - 1;

$q = $all[$index];

// On submit, store choice and redirect
$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $choice = $_POST['choice'] ?? '';
    $choiceIndex = (int)$choice; // Convert to integer for comparison
    $isCorrect = isset($q->answer) && $q->answer === $choiceIndex;
    $feedback = $isCorrect ? 'Correct!' : 'Incorrect';

    // Save attempt
    $sub = getCollection('coding_platform', 'submissions');
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->insert([
        'type' => 'mcq',
        'user_id' => $_SESSION['user_id'],
        'mcq_id' => $q->_id,
        'choice' => $choice,
        'correct' => $isCorrect,
        'submitted_at' => new MongoDB\BSON\UTCDateTime()
    ]);
    $sub['manager']->executeBulkWrite($sub['db'] . '.' . $sub['collection'], $bulk);

    // Prepare URL query string for redirection
    $next = $index + 1;
    $finalMsg = $next < count($all) ? $feedback : $feedback . ' — End of MCQs';
    $nextIndex = $next < count($all) ? $next : $index;
    
    $qs = http_build_query([
        'index' => $nextIndex,
        'msg' => $finalMsg,
        'category' => $category,
        'difficulty' => $difficulty
    ]);
    
    header('Location: mcq.php?' . $qs);
    exit;
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// Fetch filter options for dropdowns
$cmdCat = new MongoDB\Driver\Command(['distinct'=>'mcq','key'=>'language']);
$cmdDiff = new MongoDB\Driver\Command(['distinct'=>'mcq','key'=>'difficulty']);
try { $catRes = $coll['manager']->executeCommand($coll['db'], $cmdCat)->toArray()[0]->values ?? []; } catch (Throwable $e) { $catRes = []; }
try { $diffRes = $coll['manager']->executeCommand($coll['db'], $cmdDiff)->toArray()[0]->values ?? []; } catch (Throwable $e) { $diffRes = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkillForge — MCQ Practice</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets\css\mcq.css">
</head>
<body>
<div class="container mt-4">
    <div class="panel mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-1">MCQ Practice</h4>
            <div class="small">Question <?= $index + 1 ?> of <?= count($all) ?></div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="dashboard.php" class="btn btn-outline btn-sm">SkillForge</a>
            <a href="profile.php" class="btn btn-outline btn-sm">Profile</a>
            <a href="dashboard.php" class="btn btn-outline btn-sm">Exit</a>
        </div>
    </div>

    <div class="panel mb-3">
        <form class="row g-2" method="GET" action="">
            <input type="hidden" name="index" value="0" />
            <div class="col-md-4">
                <select class="form-select" name="category">
                    <option value="">All Languages</option>
                    <?php foreach ($catRes as $c): $sel = ($category === (string)$c) ? 'selected' : ''; ?>
                        <option <?= $sel ?> value="<?= htmlspecialchars((string)$c) ?>"><?= htmlspecialchars((string)$c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="difficulty">
                    <option value="">All Difficulties</option>
                    <?php foreach ($diffRes as $d): $sel = ($difficulty === (string)$d) ? 'selected' : ''; ?>
                        <option <?= $sel ?> value="<?= htmlspecialchars((string)$d) ?>"><?= htmlspecialchars((string)$d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Apply</button>
            </div>
            <div class="col-md-2">
            <a class="btn btn-outline w-100" href="mcq.php?index=0">Reset</a>
            </div>
        </form>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="panel">
        <h5 class="mb-3"><?= htmlspecialchars($q->question ?? '') ?></h5>
        <form method="POST" action="mcq.php?index=<?= $index ?>&category=<?= urlencode($category) ?>&difficulty=<?= urlencode($difficulty) ?>">
            <?php $opts = $q->options ?? []; $i = 0; foreach ($opts as $opt): $i++; $id = 'opt'.$i; ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="choice" id="<?= $id ?>" value="<?= $i-1 ?>" required>
                    <label class="form-check-label" for="<?= $id ?>"><?= htmlspecialchars((string)$opt) ?></label>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary btn-animated mt-2">Submit Answer</button>
        </form>

        <div class="d-flex justify-content-between mt-4">
            <?php if ($index > 0): ?>
            <a class="btn btn-outline" href="mcq.php?index=<?= $index - 1 ?>&category=<?= urlencode($category) ?>&difficulty=<?= urlencode($difficulty) ?>">&laquo; Previous</a>
            <?php else: ?><div></div><?php endif; ?>

            <?php if ($index < count($all) - 1): ?>
            <a class="btn btn-primary" href="mcq.php?index=<?= $index + 1 ?>&category=<?= urlencode($category) ?>&difficulty=<?= urlencode($difficulty) ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const CURRENT_THEME = localStorage.getItem('sf_theme') || 'dark';
    const CURRENT_ANIMATION_STATE = localStorage.getItem('sf_anim') || 'on';
</script>
<script src="assets\js\mcq.js"></script>
</body>
</html>