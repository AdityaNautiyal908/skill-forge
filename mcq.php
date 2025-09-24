<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/db_mongo.php";

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

if ($index < 0) $index = 0;
if ($index >= count($all)) $index = count($all) - 1;

$q = $all[$index];

// On submit, store choice
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

    // Move next automatically if there is one
    $next = $index + 1;
    if ($next < count($all)) {
        $qs = http_build_query(['index'=>$next,'msg'=>$feedback,'category'=>$category,'difficulty'=>$difficulty]);
        header('Location: mcq.php?' . $qs);
        exit;
    } else {
        $qs = http_build_query(['index'=>$index,'msg'=>$feedback . ' — End of MCQs','category'=>$category,'difficulty'=>$difficulty]);
        header('Location: mcq.php?' . $qs);
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
.light { color:#2d3748 !important; background: radial-gradient(1200px 600px at 10% 10%, rgba(0,0,0,0.08), transparent 60%), radial-gradient(1000px 600px at 90% 30%, rgba(0,0,0,0.06), transparent 60%), linear-gradient(135deg, #e2e8f0, #cbd5e0 60%, #a0aec0) !important; }
.light .title, .light h1, .light h2, .light h3, .light h4, .light h5, .light h6 {
    color: #1a202c !important;
}
.light .subtitle, .light p, .light .desc, .light .card-text {
    color: #4a5568 !important;
}
.panel { background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02)); border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:18px; }
.btn-primary { background: linear-gradient(135deg, #6e8efb, #a777e3); border:none; box-shadow: 0 8px 30px rgba(110,142,251,0.35); }
.btn-outline { background: transparent; border: 1px solid rgba(255,255,255,0.25); color: white; }
.no-anim .stars, .no-anim .orb, .no-anim canvas { display:none !important; }
.filter-pill { margin-right: 6px; margin-bottom: 6px; }
</style>
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

  <?php
    // Distinct filters for dropdowns
    $cmdCat = new MongoDB\Driver\Command(['distinct'=>'mcq','key'=>'language']);
    $cmdDiff = new MongoDB\Driver\Command(['distinct'=>'mcq','key'=>'difficulty']);
    try { $catRes = $coll['manager']->executeCommand($coll['db'], $cmdCat)->toArray()[0]->values ?? []; } catch (Throwable $e) { $catRes = []; }
    try { $diffRes = $coll['manager']->executeCommand($coll['db'], $cmdDiff)->toArray()[0]->values ?? []; } catch (Throwable $e) { $diffRes = []; }
  ?>

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
      <button type="submit" class="btn btn-primary mt-2">Submit Answer</button>
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

</body>
</html>
<script>
(function(){
  function apply(){ var theme=localStorage.getItem('sf_theme')||'dark'; var anim=localStorage.getItem('sf_anim')||'on'; document.body.classList.toggle('light', theme==='light'); document.body.classList.toggle('no-anim', anim==='off'); }
  apply();
  var box=document.createElement('div'); box.style.position='fixed'; box.style.right='14px'; box.style.bottom='14px'; box.style.zIndex='9999'; box.style.display='flex'; box.style.gap='8px';
  function mk(label){ var b=document.createElement('button'); b.textContent=label; b.style.border='1px solid rgba(255,255,255,0.4)'; b.style.background='rgba(0,0,0,0.35)'; b.style.color='#fff'; b.style.padding='8px 12px'; b.style.borderRadius='10px'; b.style.backdropFilter='blur(6px)'; return b; }
  var tBtn=mk((localStorage.getItem('sf_theme')||'dark')==='light'?'Dark Mode':'Light Mode');
  var aBtn=mk((localStorage.getItem('sf_anim')||'on')==='off'?'Enable Anim':'Disable Anim');
  tBtn.onclick=function(){ var cur=localStorage.getItem('sf_theme')||'dark'; var next=cur==='dark'?'light':'dark'; localStorage.setItem('sf_theme',next); tBtn.textContent=next==='light'?'Dark Mode':'Light Mode'; apply(); };
  aBtn.onclick=function(){ var cur=localStorage.getItem('sf_anim')||'on'; var next=cur==='on'?'off':'on'; localStorage.setItem('sf_anim',next); aBtn.textContent=next==='off'?'Enable Anim':'Disable Anim'; apply(); };
  document.body.appendChild(box); box.appendChild(tBtn); box.appendChild(aBtn);
})();
</script>

