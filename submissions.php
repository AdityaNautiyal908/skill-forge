<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/db_mongo.php";
require_once "config/db_mysql.php"; // to fetch usernames if needed later

// Filters
$language = isset($_GET['language']) ? trim($_GET['language']) : '';
$user = isset($_GET['user']) ? trim($_GET['user']) : '';

$coll = getCollection('coding_platform', 'submissions');

// Build query filter
$filter = [];
if ($language !== '') $filter['language'] = $language;
if ($user !== '') $filter['user_id'] = $user;

// Pagination (simple)
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$skip = ($page - 1) * $limit;

$query = new MongoDB\Driver\Query(
    $filter,
    [ 'sort' => [ 'submitted_at' => -1 ], 'limit' => $limit, 'skip' => $skip ]
);
$rows = $coll['manager']->executeQuery($coll['db'] . ".submissions", $query)->toArray();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkillForge — Submissions</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { margin:0; color:white; min-height:100vh; background: radial-gradient(1200px 600px at 10% 10%, rgba(167,119,227,0.25), transparent 60%), radial-gradient(1000px 600px at 90% 30%, rgba(110,142,251,0.25), transparent 60%), linear-gradient(135deg, #0f1020, #111437 60%, #0a0d2a); }
.navbar { background: rgba(0,0,0,0.35) !important; backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.08); }
.panel { background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02)); border:1px solid rgba(255,255,255,0.08); border-radius:16px; }
table { color: white; }
table td, table th { border-color: rgba(255,255,255,0.15) !important; }
.code-snippet { max-width: 520px; white-space: pre; overflow: hidden; text-overflow: ellipsis; display: inline-block; vertical-align: top; }
.btn-copy { border: 1px solid rgba(255,255,255,0.25); color:#fff; }
</style>
</head>
<body>
<?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
} ?>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">SkillForge</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="submissions.php">Submissions</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
  </nav>

<div class="container my-4">
  <div class="panel p-3 mb-3">
    <form class="row g-2" method="GET" action="">
      <div class="col-md-3">
        <input type="text" name="language" value="<?= htmlspecialchars($language) ?>" class="form-control" placeholder="Filter by language (e.g., html, c, java)">
      </div>
      <div class="col-md-3">
        <input type="text" name="user" value="<?= htmlspecialchars($user) ?>" class="form-control" placeholder="Filter by user_id">
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
            <th scope="col">Language</th>
            <th scope="col">Problem Id</th>
            <th scope="col">Code</th>
            <th scope="col" style="width:80px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $doc): ?>
            <tr>
              <td>
                <?php
                  $ts = isset($doc->submitted_at) && $doc->submitted_at instanceof MongoDB\BSON\UTCDateTime
                    ? $doc->submitted_at->toDateTime()->format('Y-m-d H:i:s')
                    : '';
                  echo htmlspecialchars($ts);
                ?>
              </td>
              <td><?= htmlspecialchars((string)($doc->user_id ?? '')) ?></td>
              <td><?= htmlspecialchars((string)($doc->language ?? '')) ?></td>
              <td><?= htmlspecialchars((string)($doc->problem_id ?? '')) ?></td>
              <td><span class="code-snippet"><?= htmlspecialchars(mb_strimwidth((string)($doc->code ?? ''), 0, 300, '…', 'UTF-8')) ?></span></td>
              <td>
                <?php $idAttr = 'm'.substr(md5((string)($doc->_id ?? uniqid())), 0, 8); ?>
                <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#<?= $idAttr ?>">View</button>
              </td>
            </tr>
            <div class="modal fade" id="<?= $idAttr ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content" style="background: #1b1e2b; color:#fff;">
                  <div class="modal-header">
                    <h5 class="modal-title">Submission — <?= htmlspecialchars((string)($doc->language ?? '')) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <pre style="white-space: pre-wrap; word-wrap: break-word;" id="code-<?= $idAttr ?>"><?php echo htmlspecialchars((string)($doc->code ?? '')); ?></pre>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light btn-copy" data-target="code-<?= $idAttr ?>">Copy</button>
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
</body>
</html>

