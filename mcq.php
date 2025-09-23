<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/db_mongo.php";

$index = isset($_GET['index']) ? (int)$_GET['index'] : 0;
$coll = getCollection('coding_platform', 'mcq');

// Fetch all MCQs (you can later filter by category)
$query = new MongoDB\Driver\Query([], ['sort' => ['order' => 1, '_id' => 1]]);
$all = $coll['manager']->executeQuery($coll['db'] . ".mcq", $query)->toArray();
if (count($all) === 0) die('No MCQ questions found.');

if ($index < 0) $index = 0;
if ($index >= count($all)) $index = count($all) - 1;

$q = $all[$index];

// On submit, store choice
$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $choice = $_POST['choice'] ?? '';
    $isCorrect = isset($q->answer) && (string)$q->answer === (string)$choice;
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

    // Move next automatically if there is one
    $next = $index + 1;
    if ($next < count($all)) {
        header('Location: mcq.php?index=' . $next . '&msg=' . urlencode($feedback));
        exit;
    } else {
        header('Location: mcq.php?index=' . $index . '&msg=' . urlencode($feedback . ' — End of MCQs'));
        exit;
    }
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkillForge — MCQ Practice</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { margin:0; color:white; min-height:100vh; background: radial-gradient(1200px 600px at 10% 10%, rgba(167,119,227,0.25), transparent 60%), radial-gradient(1000px 600px at 90% 30%, rgba(110,142,251,0.25), transparent 60%), linear-gradient(135deg, #0f1020, #111437 60%, #0a0d2a); }
.panel { background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02)); border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:18px; }
.btn-primary { background: linear-gradient(135deg, #6e8efb, #a777e3); border:none; box-shadow: 0 8px 30px rgba(110,142,251,0.35); }
.btn-outline { background: transparent; border: 1px solid rgba(255,255,255,0.25); color: white; }
</style>
</head>
<body>
<div class="container mt-4">
  <div class="panel mb-3 d-flex justify-content-between align-items-center">
    <div>
      <h4 class="mb-1">MCQ Practice</h4>
      <div class="small">Question <?= $index + 1 ?> of <?= count($all) ?></div>
    </div>
    <a href="dashboard.php" class="btn btn-outline btn-sm">Exit</a>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="panel">
    <h5 class="mb-3"><?= htmlspecialchars($q->question ?? '') ?></h5>
    <form method="POST" action="mcq.php?index=<?= $index ?>">
      <?php $opts = $q->options ?? []; $i = 0; foreach ($opts as $opt): $i++; $id = 'opt'.$i; ?>
        <div class="form-check mb-2">
          <input class="form-check-input" type="radio" name="choice" id="<?= $id ?>" value="<?= htmlspecialchars((string)$opt) ?>" required>
          <label class="form-check-label" for="<?= $id ?>"><?= htmlspecialchars((string)$opt) ?></label>
        </div>
      <?php endforeach; ?>
      <button type="submit" class="btn btn-primary mt-2">Submit Answer</button>
    </form>

    <div class="d-flex justify-content-between mt-4">
      <?php if ($index > 0): ?>
      <a class="btn btn-outline" href="mcq.php?index=<?= $index - 1 ?>">&laquo; Previous</a>
      <?php else: ?><div></div><?php endif; ?>

      <?php if ($index < count($all) - 1): ?>
      <a class="btn btn-primary" href="mcq.php?index=<?= $index + 1 ?>">Next &raquo;</a>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>

