<?php
session_start();

require_once "config/db_mongo.php";
require_once "config/db_mysql.php"; // for usernames

// Auth: allow both users and admins to see the leaderboard (must be logged in)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Date range filter (optional)
$range = isset($_GET['range']) ? trim($_GET['range']) : 'all';
$now = time() * 1000; // ms for MongoDB date comparisons
$since = null;
if ($range === '7d') $since = $now - 7*24*60*60*1000;
if ($range === '30d') $since = $now - 30*24*60*60*1000;
if ($range === '90d') $since = $now - 90*24*60*60*1000;

$coll = getCollection('coding_platform', 'submissions');

// Build aggregation: count all submissions (code and MCQ), regardless of correctness
$match = [];
if ($since !== null) {
    $match['submitted_at'] = ['$gte' => new MongoDB\BSON\UTCDateTime($since)];
}
// MongoDB aggregate expects an object; use empty object when no filters
if (empty($match)) { $match = new stdClass(); }

$pipeline = [
    [ '$match' => $match ],
    [ '$group' => [
        '_id' => '$user_id',
        'count' => [ '$sum' => 1 ],
        'lastSolved' => [ '$max' => '$submitted_at' ],
    ]],
    [ '$sort' => [ 'count' => -1, 'lastSolved' => -1 ] ],
    [ '$limit' => 100 ]
];

$cmd = new MongoDB\Driver\Command([
    'aggregate' => 'submissions',
    'pipeline' => $pipeline,
    'cursor' => new stdClass()
]);

$results = [];
try {
    $cursor = $coll['manager']->executeCommand($coll['db'], $cmd);
    foreach ($cursor as $doc) {
        $results[] = $doc;
    }
} catch (Throwable $e) {
    $results = [];
}

// Gather user ids
$userIds = array_map(function($doc){ return (int)$doc->_id; }, $results);
$usernames = [];
if (!empty($userIds)) {
    $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($userIds)), ...$userIds);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $usernames[(int)$row['id']] = $row['username'];
    }
}

function rankBadge($rank) {
    if ($rank === 1) return '<span class="badge" style="background:linear-gradient(135deg,#d4af37,#f3e47a);color:#1a202c">🥇 1st</span>';
    if ($rank === 2) return '<span class="badge" style="background:linear-gradient(135deg,#c0c0c0,#e6e6e6);color:#1a202c">🥈 2nd</span>';
    if ($rank === 3) return '<span class="badge" style="background:linear-gradient(135deg,#cd7f32,#f3b27a);color:#1a202c">🥉 3rd</span>';
    return '<span class="badge bg-secondary">#'. $rank .'</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SkillForge — Leaderboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { margin:0; color:white; min-height:100vh; background: radial-gradient(1200px 600px at 10% 10%, rgba(167,119,227,0.25), transparent 60%), radial-gradient(1000px 600px at 90% 30%, rgba(110,142,251,0.25), transparent 60%), linear-gradient(135deg, #0f1020, #111437 60%, #0a0d2a); }
.light { color:#2d3748 !important; background: radial-gradient(1200px 600px at 10% 10%, rgba(0,0,0,0.08), transparent 60%), radial-gradient(1000px 600px at 90% 30%, rgba(0,0,0,0.06), transparent 60%), linear-gradient(135deg, #e2e8f0, #cbd5e0 60%, #a0aec0) !important; }
.table { color:#fff; }
.table td, .table th { border-color: rgba(255,255,255,0.15) !important; }
.panel { background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02)); border:1px solid rgba(255,255,255,0.08); border-radius:16px; }
.badge { font-size: .85rem; }
.medal { font-size: 22px; }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background: rgba(0,0,0,0.35); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.08);">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">SkillForge</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="mcq.php?index=0">MCQ</a></li>
        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
        <li class="nav-item"><a class="nav-link active" href="leaderboard.php">Leaderboard</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Leaderboard</h3>
    <form method="GET" class="d-flex gap-2">
      <select name="range" class="form-select">
        <option value="all" <?= $range==='all'?'selected':'' ?>>All time</option>
        <option value="7d" <?= $range==='7d'?'selected':'' ?>>Last 7 days</option>
        <option value="30d" <?= $range==='30d'?'selected':'' ?>>Last 30 days</option>
        <option value="90d" <?= $range==='90d'?'selected':'' ?>>Last 90 days</option>
      </select>
      <button class="btn btn-primary" type="submit">Apply</button>
    </form>
  </div>

  <div class="panel p-3">
    <div class="table-responsive">
      <table class="table table-dark table-hover align-middle">
        <thead>
          <tr>
            <th style="width:100px">Rank</th>
            <th>User</th>
            <th style="width:180px">Solved</th>
            <th style="width:220px">Last Solved</th>
          </tr>
        </thead>
        <tbody>
          <?php $rank=0; foreach ($results as $row): $rank++; $uid=(int)$row->_id; $name=$usernames[$uid] ?? ('User #'.$uid); $count=(int)($row->count ?? 0); $lastTs = ($row->lastSolved instanceof MongoDB\BSON\UTCDateTime) ? $row->lastSolved->toDateTime()->format('Y-m-d H:i:s') : ''; ?>
            <tr>
              <td><?= rankBadge($rank) ?></td>
              <td class="fw-semibold">
                <?= htmlspecialchars($name) ?>
                <?php if ($rank<=3): ?>
                  <span class="ms-2 medal"><?= $rank===1?'🥇':($rank===2?'🥈':'🥉') ?></span>
                <?php endif; ?>
                <div class="small text-muted">ID: <?= $uid ?></div>
              </td>
              <td><?= $count ?></td>
              <td><?= htmlspecialchars($lastTs) ?></td>
            </tr>
          <?php endforeach; if (empty($results)): ?>
            <tr><td colspan="4" class="text-center text-muted">No submissions yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  function apply(){ var theme=localStorage.getItem('sf_theme')||'dark'; var anim=localStorage.getItem('sf_anim')||'on'; document.body.classList.toggle('light', theme==='light'); document.body.classList.toggle('no-anim', anim==='off'); }
  apply();
})();
</script>
</body>
</html>
