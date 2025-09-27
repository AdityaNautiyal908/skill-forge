<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once "config/db_mongo.php";
require_once "config/db_mysql.php"; // to fetch usernames if needed later

// Filters
$language = isset($_GET['language']) ? trim($_GET['language']) : '';
$user = isset($_GET['user']) ? trim($_GET['user']) : '';

$coll = getCollection('coding_platform', 'submissions');

// Build query filter
$filter = [
    '$or' => [ // Add a condition to ensure the 'deleted' field is either missing or false
        ['deleted' => ['$exists' => false]],
        ['deleted' => false]
    ]
];
if ($language !== '') $filter['language'] = $language;
if ($user !== '') $filter['user_id'] = $user;

// Add type filter for submissions
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
if ($type !== '') $filter['type'] = $type;

// Pagination (simple)
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$skip = ($page - 1) * $limit;

$query = new MongoDB\Driver\Query(
    $filter,
    [ 'sort' => [ 'submitted_at' => -1 ], 'limit' => $limit, 'skip' => $skip ]
);
$rows = $coll['manager']->executeQuery($coll['db'] . ".submissions", $query)->toArray();

// Fetch usernames and problem titles for better display
$userNames = [];
$problemTitles = [];

// Get unique user IDs and problem IDs
if (!empty($rows)) {
    $userIds = array_unique(array_filter(array_map(function($doc) { return $doc->user_id ?? null; }, $rows), function($id) { return $id !== null; }));
    $problemIds = array_unique(array_filter(array_map(function($doc) { return $doc->problem_id ?? null; }, $rows), function($id) { return $id !== null; }));
} else {
    $userIds = [];
    $problemIds = [];
}

// Fetch usernames from MySQL
if (!empty($userIds)) {
    // Filter out null/empty user IDs
    $userIds = array_filter($userIds, function($id) { return !empty($id) && is_numeric($id); });
    
    if (!empty($userIds)) {
        $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($userIds)), ...$userIds);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $userNames[$row['id']] = $row['username'];
        }
    }
}

// Fetch problem titles from MongoDB
if (!empty($problemIds)) {
    $problemColl = getCollection('coding_platform', 'problems');
    
    // Convert problem IDs to ObjectId array, filtering out invalid ones
    $objectIds = [];
    foreach ($problemIds as $id) {
        try {
            if (!empty($id)) {
                $objectIds[] = new MongoDB\BSON\ObjectId($id);
            }
        } catch (Exception $e) {
            // Skip invalid ObjectId
            continue;
        }
    }
    
    if (!empty($objectIds)) {
        $problemQuery = new MongoDB\Driver\Query(['_id' => ['$in' => $objectIds]]);
        $problems = $problemColl['manager']->executeQuery($problemColl['db'] . ".problems", $problemQuery)->toArray();
        foreach ($problems as $problem) {
            $problemTitles[(string)$problem->_id] = $problem->title ?? 'Unknown Problem';
        }
    }
}

// Fetch MCQ question details for MCQ submissions
$mcqQuestions = [];
$mcqIds = array_unique(array_filter(array_map(function($doc) { 
    return (isset($doc->type) && $doc->type === 'mcq' && isset($doc->mcq_id)) ? $doc->mcq_id : null; 
}, $rows), function($id) { return $id !== null; }));

if (!empty($mcqIds)) {
    $mcqColl = getCollection('coding_platform', 'mcq');
    $objectIds = [];
    foreach ($mcqIds as $id) {
        try {
            if (!empty($id)) {
                $objectIds[] = new MongoDB\BSON\ObjectId($id);
            }
        } catch (Exception $e) {
            continue;
        }
    }
    
    if (!empty($objectIds)) {
        $mcqQuery = new MongoDB\Driver\Query(['_id' => ['$in' => $objectIds]]);
        $mcqs = $mcqColl['manager']->executeQuery($mcqColl['db'] . ".mcq", $mcqQuery)->toArray();
        foreach ($mcqs as $mcq) {
            $mcqQuestions[(string)$mcq->_id] = [
                'question' => $mcq->question ?? 'Unknown Question',
                'language' => $mcq->language ?? 'Unknown',
                'difficulty' => $mcq->difficulty ?? 'Unknown'
            ];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SkillForge — Submissions</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body { margin:0; color:white; min-height:100vh; background: radial-gradient(1200px 600px at 10% 10%, rgba(167,119,227,0.25), transparent 60%), radial-gradient(1000px 600px at 90% 30%, rgba(110,142,251,0.25), transparent 60%), linear-gradient(135deg, #0f1020, #111437 60%, #0a0d2a); }
.light { color:#2d3748 !important; background: radial-gradient(1200px 600px at 10% 10%, rgba(0,0,0,0.08), transparent 60%), radial-gradient(1000px 600px at 90% 30%, rgba(0,0,0,0.06), transparent 60%), linear-gradient(135deg, #e2e8f0, #cbd5e0 60%, #a0aec0) !important; }
.light .title, .light h1, .light h2, .light h3, .light h4, .light h5, .light h6 {
    color: #1a202c !important;
}
.light .subtitle, .light p, .light .desc, .light .card-text {
    color: #4a5568 !important;
}
.navbar { background: rgba(0,0,0,0.35) !important; backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.08); }
.panel { background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02)); border:1px solid rgba(255,255,255,0.08); border-radius:16px; }
table { color: white; }
table td, table th { border-color: rgba(255,255,255,0.15) !important; }
.code-snippet { max-width: 520px; white-space: pre; overflow: hidden; text-overflow: ellipsis; display: inline-block; vertical-align: top; }
.web { position: fixed; inset:0; z-index:0; pointer-events:none; }
.btn-copy { border: 1px solid rgba(255,255,255,0.25); color:#fff; }
/* Light yellow orbs/web palette is handled in canvas script below */
</style>
</head>
<body>
<canvas id="webSub" class="web"></canvas>

<?php
// Display SweetAlert2 for success or error messages
if (isset($_SESSION['success_message'])) {
    echo "
    <script>
    Swal.fire({
      icon: 'success',
      title: 'Success!',
      text: '" . htmlspecialchars($_SESSION['success_message']) . "',
      background: '#111437',
      color: '#fff',
      confirmButtonColor: '#6d7cff'
    });
    </script>
    ";
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo "
    <script>
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: '" . htmlspecialchars($_SESSION['error_message']) . "',
      background: '#111437',
      color: '#fff',
      confirmButtonColor: '#dc3545'
    });
    </script>
    ";
    unset($_SESSION['error_message']);
}
?>

<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">SkillForge</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <li class="nav-item"><a class="nav-link" href="users_admin.php">Users</a></li>
        <li class="nav-item"><a class="nav-link" href="admin_feedback.php">Feedback</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
        <li class="nav-item"><a class="nav-link active" href="submissions.php">Submissions</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
  </nav>

<div class="container my-4">
  <div class="panel p-3 mb-3">
    <form class="row g-2" method="GET" action="">
      <div class="col-md-2">
        <input type="text" name="language" value="<?= htmlspecialchars($language) ?>" class="form-control" placeholder="Filter by language">
      </div>
      <div class="col-md-2">
        <input type="text" name="user" value="<?= htmlspecialchars($user) ?>" class="form-control" placeholder="Filter by username or user_id">
      </div>
      <div class="col-md-2">
        <select name="type" class="form-select">
          <option value="">All Types</option>
          <option value="code" <?= $type === 'code' ? 'selected' : '' ?>>Code Submissions</option>
          <option value="mcq" <?= $type === 'mcq' ? 'selected' : '' ?>>MCQ Submissions</option>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100" type="submit">Apply</button>
      </div>
      <div class="col-md-2">
        <a class="btn btn-outline-light w-100" href="submissions.php">Reset</a>
      </div>
    </form>
  </div>

  <div class="panel p-3">
    <div class="table-responsive">
      <table class="table table-dark table-hover align-middle">
        <thead>
          <tr>
            <th scope="col">When</th>
            <th scope="col">User</th>
            <th scope="col">Type</th>
            <th scope="col">Content</th>
            <th scope="col">Details</th>
            <th scope="col" style="width:160px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $doc): ?>
            <tr style="white-space: nowrap;">
              <td>
                <?php
                  $ts = isset($doc->submitted_at) && $doc->submitted_at instanceof MongoDB\BSON\UTCDateTime
                    ? $doc->submitted_at->toDateTime()->format('Y-m-d H:i:s')
                    : '';
                  echo htmlspecialchars($ts);
                ?>
              </td>
              <td>
                <div class="fw-bold"><?= htmlspecialchars($userNames[$doc->user_id] ?? 'Unknown User') ?></div>
                <small class="text-muted">ID: <?= htmlspecialchars((string)($doc->user_id ?? '')) ?></small>
              </td>
              <td>
                <?php if (isset($doc->type) && $doc->type === 'mcq'): ?>
                  <span class="badge bg-info">MCQ</span>
                <?php else: ?>
                  <span class="badge bg-primary">Code</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (isset($doc->type) && $doc->type === 'mcq'): ?>
                  <div class="fw-bold"><?= htmlspecialchars($mcqQuestions[(string)$doc->mcq_id]['question'] ?? 'Unknown Question') ?></div>
                  <small class="text-muted">Language: <?= htmlspecialchars($mcqQuestions[(string)$doc->mcq_id]['language'] ?? 'Unknown') ?></small>
                <?php else: ?>
                  <span class="code-snippet"><?= htmlspecialchars(mb_strimwidth((string)($doc->code ?? ''), 0, 300, '…', 'UTF-8')) ?></span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (isset($doc->type) && $doc->type === 'mcq'): ?>
                  <div class="fw-bold">Answer: <?= htmlspecialchars((string)($doc->choice ?? '')) ?></div>
                  <div class="fw-bold <?= isset($doc->correct) && $doc->correct ? 'text-success' : 'text-danger' ?>">
                    <?= isset($doc->correct) && $doc->correct ? 'Correct' : 'Incorrect' ?>
                  </div>
                  <small class="text-muted">Difficulty: <?= htmlspecialchars($mcqQuestions[(string)$doc->mcq_id]['difficulty'] ?? 'Unknown') ?></small>
                <?php else: ?>
                  <div class="fw-bold"><?= htmlspecialchars($problemTitles[(string)$doc->problem_id] ?? 'Unknown Problem') ?></div>
                  <small class="text-muted">Language: <?= htmlspecialchars((string)($doc->language ?? '')) ?></small>
                <?php endif; ?>
              </td>
              <td>
                <?php $idAttr = 'm'.substr(md5((string)($doc->_id ?? uniqid())), 0, 8); ?>
                <form method="POST" action="admin_delete_submission.php" style="display:inline;margin-right:6px;" onsubmit="return confirm('Delete this submission?');">
                  <input type="hidden" name="id" value="<?= htmlspecialchars((string)$doc->_id ?? '') ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
                <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#<?= $idAttr ?>">View</button>
              </td>
            </tr>
            <div class="modal fade" id="<?= $idAttr ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content" style="background: #1b1e2b; color:#fff;">
                  <div class="modal-header">
                    <h5 class="modal-title">
                      <?php if (isset($doc->type) && $doc->type === 'mcq'): ?>
                        MCQ Submission by <?= htmlspecialchars($userNames[$doc->user_id] ?? 'Unknown User') ?>
                      <?php else: ?>
                        Code Submission by <?= htmlspecialchars($userNames[$doc->user_id] ?? 'Unknown User') ?> 
                        — <?= htmlspecialchars((string)($doc->language ?? '')) ?>
                      <?php endif; ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <?php if (isset($doc->type) && $doc->type === 'mcq'): ?>
                      <div class="mb-3">
                        <h6>Question:</h6>
                        <p class="mb-2"><?= htmlspecialchars($mcqQuestions[(string)$doc->mcq_id]['question'] ?? 'Unknown Question') ?></p>
                        <h6>User's Answer:</h6>
                        <p class="mb-2"><?= htmlspecialchars((string)($doc->choice ?? '')) ?></p>
                        <h6>Result:</h6>
                        <p class="mb-2 <?= isset($doc->correct) && $doc->correct ? 'text-success' : 'text-danger' ?>">
                          <?= isset($doc->correct) && $doc->correct ? 'Correct ✓' : 'Incorrect ✗' ?>
                        </p>
                        <h6>Language:</h6>
                        <p class="mb-2"><?= htmlspecialchars($mcqQuestions[(string)$doc->mcq_id]['language'] ?? 'Unknown') ?></p>
                        <h6>Difficulty:</h6>
                        <p class="mb-2"><?= htmlspecialchars($mcqQuestions[(string)$doc->mcq_id]['difficulty'] ?? 'Unknown') ?></p>
                      </div>
                    <?php else: ?>
                      <div class="mb-3">
                        <h6>Problem:</h6>
                        <p class="mb-2"><?= htmlspecialchars($problemTitles[(string)$doc->problem_id] ?? 'Unknown Problem') ?></p>
                        <h6>Code:</h6>
                      </div>
                      <pre style="white-space: pre-wrap; word-wrap: break-word;" id="code-<?= $idAttr ?>"><?php echo htmlspecialchars((string)($doc->code ?? '')); ?></pre>
                    <?php endif; ?>
                  </div>
                  <div class="modal-footer">
                    <?php if (!isset($doc->type) || $doc->type !== 'mcq'): ?>
                      <button type="button" class="btn btn-outline-light btn-copy" data-target="code-<?= $idAttr ?>">Copy</button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-between mt-2">
      <?php if ($page > 1): ?>
        <a class="btn btn-outline-light" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo; Prev</a>
      <?php else: ?><div></div><?php endif; ?>

      <?php if (count($rows) === $limit): ?>
        <a class="btn btn-outline-light" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next &raquo;</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.btn-copy').forEach(function(btn){
  btn.addEventListener('click', function(){
    var id = this.getAttribute('data-target');
    var pre = document.getElementById(id);
    if (!pre) return;
    var text = pre.textContent || pre.innerText || '';
    navigator.clipboard.writeText(text).then(()=>{
      this.textContent = 'Copied!';
      setTimeout(()=>{ this.textContent = 'Copy'; }, 1500);
    });
  });
});
</script>
<script>
(function(){
  function apply(){ var theme=localStorage.getItem('sf_theme')||'dark'; var anim=localStorage.getItem('sf_anim')||'on'; document.body.classList.toggle('light', theme==='light'); document.body.classList.toggle('no-anim', anim==='off'); }
  apply();
  var box=document.createElement('div'); box.style.position='fixed'; box.style.right='14px'; box.style.bottom='14px'; box.style.zIndex='9999'; box.style.display='flex'; box.style.gap='8px';
  function mk(label){ var b=document.createElement('button'); b.textContent=label; b.style.border='1px solid rgba(255,255,255,0.4)'; b.style.background='rgba(0,0,0,0.35)'; b.style.color='#fff'; b.style.padding='8px 12px'; b.style.borderRadius='10px'; b.style.backdrop-filter:blur(6px)'; return b; }
  var tBtn=mk((localStorage.getItem('sf_theme')||'dark')==='light'?'Dark Mode':'Light Mode');
  var aBtn=mk((localStorage.getItem('sf_anim')||'on')==='off'?'Enable Anim':'Disable Anim');
  tBtn.onclick=function(){ var cur=localStorage.getItem('sf_theme')||'dark'; var next=cur==='dark'?'light':'dark'; localStorage.setItem('sf_theme',next); tBtn.textContent=next==='light'?'Dark Mode':'Light Mode'; apply(); };
  aBtn.onclick=function(){ var cur=localStorage.getItem('sf_anim')||'on'; var next=cur==='on'?'off':'on'; localStorage.setItem('sf_anim',next); aBtn.textContent=next==='off'?'Enable Anim':'Disable Anim'; apply(); };
  document.body.appendChild(box); box.appendChild(tBtn); box.appendChild(aBtn);
})();
</script>
</body>
<script>
(function(){
  var canvas = document.getElementById('webSub'); if (!canvas) return; var ctx = canvas.getContext('2d'); var DPR = Math.max(1, window.devicePixelRatio||1);
  function resize(){ canvas.width=innerWidth*DPR; canvas.height=innerHeight*DPR; } window.addEventListener('resize', resize); resize();
  var nodes=[], NUM=40, K=4; for(var i=0;i<NUM;i++){ nodes.push({x:Math.random()*canvas.width,y:Math.random()*canvas.height,vx:(Math.random()-0.5)*0.15*DPR,vy:(Math.random()-0.5)*0.15*DPR,p:Math.random()*1e3}); }
  function loop(){ ctx.clearRect(0,0,canvas.width,canvas.height); var isLight=document.body.classList.contains('light'); for(var i=0;i<nodes.length;i++){ var a=nodes[i]; ctx.fillStyle='rgba(255,255,255,0.02)'; ctx.beginPath(); ctx.arc(a.x,a.y,2*DPR,0,Math.PI*2); ctx.fill(); var near=[]; for(var j=0;j<nodes.length;j++) if(j!==i){var b=nodes[j],dx=a.x-b.x,dy=a.y-b.y,d=dx*dx+dy*dy; near.push({j:j,d:d});} near.sort(function(p,q){return p.d-q.d;}); for(var k=0;k<K;k++){ var idx=near[k]&&near[k].j; if(idx==null) continue; var b=nodes[idx],dx=a.x-b.x,dy=a.y-b.y,dist=Math.sqrt(dx*dx+dy*dy),alpha=Math.max(0,1-dist/(180*DPR)); if(alpha<=0) continue; ctx.strokeStyle=isLight?('rgba(255,203,0,'+(0.16*alpha)+')'):'rgba(160,190,255,'+(0.12*alpha)+')'; ctx.lineWidth=1*DPR; ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke(); var t=(Date.now()+a.p)%1200/1200; var px=a.x+(b.x-a.x)*t, py=a.y+(b.y-a.y)*t; var grad=ctx.createRadialGradient(px,py,0,px,py,10*DPR); if(isLight){grad.addColorStop(0,'rgba(255,220,120,'+(0.45*alpha)+')'); grad.addColorStop(1,'rgba(255,220,120,0)');} else {grad.addColorStop(0,'rgba(120,220,255,'+(0.35*alpha)+')'); grad.addColorStop(1,'rgba(120,220,255,0)');} ctx.fillStyle=grad; ctx.beginPath(); ctx.arc(px,py,10*DPR,0,Math.PI*2); ctx.fill(); }} for(var i=0;i<nodes.length;i++){ var n=nodes[i]; n.x+=n.vx; n.y+=n.vy; if(n.x<0||n.x>canvas.width) n.vx*=-1; if(n.y<0||n.y>canvas.height) n.vy*=-1;} requestAnimationFrame(loop);} loop();})();
</script>
</html>