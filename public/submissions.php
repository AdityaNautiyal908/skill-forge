<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once "../config/db_mongo.php";
require_once "../config/db_mysql.php"; // to fetch usernames

// Filters
$language = isset($_GET['language']) ? trim($_GET['language']) : '';
$user = isset($_GET['user']) ? trim($_GET['user']) : '';

$coll = getCollection('coding_platform', 'submissions');

// Build query filter
$filter = [
    '$or' => [ // Condition to ensure the 'deleted' field is either missing or false
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
$limit = 20;
// Fetch total count first to calculate total pages
$command = new MongoDB\Driver\Command(['count' => 'submissions', 'query' => $filter]);
$cursor = $coll['manager']->executeCommand($coll['db'], $command);
$totalSubmissions = $cursor->toArray()[0]->n ?? 0;
$totalPages = ceil($totalSubmissions / $limit);

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$page = min($page, $totalPages > 0 ? $totalPages : 1); // Clamp page number
$skip = ($page - 1) * $limit;

$query = new MongoDB\Driver\Query(
    $filter,
    [ 'sort' => [ 'submitted_at' => -1 ], 'limit' => $limit, 'skip' => $skip ]
);
$rows = $coll['manager']->executeQuery($coll['db'] . ".submissions", $query)->toArray();


// --- Fetch Metadata (Usernames, Problem Titles, MCQ Details) ---
$userNames = [];
$problemTitles = [];
$mcqQuestions = [];

if (!empty($rows)) {
    // Collect IDs
    $userIds = array_unique(array_filter(array_map(function($doc) { return $doc->user_id ?? null; }, $rows)));
    $problemIds = array_unique(array_filter(array_map(function($doc) { return $doc->problem_id ?? null; }, $rows)));
    $mcqIds = array_unique(array_filter(array_map(function($doc) { 
        return (isset($doc->type) && $doc->type === 'mcq' && isset($doc->mcq_id)) ? $doc->mcq_id : null; 
    }, $rows)));

    // 1. Fetch usernames from MySQL
    if (!empty($userIds)) {
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

    // Helper to convert IDs to MongoDB\BSON\ObjectId array
    $toObjectIds = function($ids) {
        $objectIds = [];
        foreach ($ids as $id) {
            try {
                if (!empty($id)) $objectIds[] = new MongoDB\BSON\ObjectId($id);
            } catch (Exception $e) { continue; }
        }
        return $objectIds;
    };
    
    // 2. Fetch problem titles from MongoDB
    $problemObjectIds = $toObjectIds($problemIds);
    if (!empty($problemObjectIds)) {
        $problemColl = getCollection('coding_platform', 'problems');
        $problemQuery = new MongoDB\Driver\Query(['_id' => ['$in' => $problemObjectIds]]);
        $problems = $problemColl['manager']->executeQuery($problemColl['db'] . ".problems", $problemQuery)->toArray();
        foreach ($problems as $problem) {
            $problemTitles[(string)$problem->_id] = $problem->title ?? 'Unknown Problem';
        }
    }

    // 3. Fetch MCQ question details
    $mcqObjectIds = $toObjectIds($mcqIds);
    if (!empty($mcqObjectIds)) {
        $mcqColl = getCollection('coding_platform', 'mcq');
        $mcqQuery = new MongoDB\Driver\Query(['_id' => ['$in' => $mcqObjectIds]]);
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
<link rel="stylesheet" href="assets\css\submissions.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<canvas id="webSub" class="web"></canvas>

<?php
// Display SweetAlert2 for success or error messages
if (isset($_SESSION['success_message'])) {
    $alert_message = htmlspecialchars($_SESSION['success_message'], ENT_QUOTES);
    echo "<script>document.addEventListener('DOMContentLoaded', function(){ Swal.fire({ icon: 'success', title: 'Success!', text: '$alert_message', background: '#111437', color: '#fff', confirmButtonColor: '#6d7cff' }); });</script>";
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $alert_message = htmlspecialchars($_SESSION['error_message'], ENT_QUOTES);
    echo "<script>document.addEventListener('DOMContentLoaded', function(){ Swal.fire({ icon: 'error', title: 'Error!', text: '$alert_message', background: '#111437', color: '#fff', confirmButtonColor: '#dc3545' }); });</script>";
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
        <li class="nav-item"><a class="nav-link" href="../admin/users_admin.php">Users</a></li>
        <li class="nav-item"><a class="nav-link" href="../admin/admin_feedback.php">Feedback</a></li>
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
    <div class="d-flex justify-content-end mb-3">
      <a href="../admin/export_submissions.php" class="btn btn-success">Download All Submissions (Word)</a>
    </div>
    <form class="row g-2" method="GET" action="">
      <div class="col-md-3">
        <input type="text" name="language" value="<?= htmlspecialchars($language) ?>" class="form-control" placeholder="Filter by language">
      </div>
      <div class="col-md-3">
        <input type="text" name="user" value="<?= htmlspecialchars($user) ?>" class="form-control" placeholder="Filter by User ID/Name">
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
                <small>ID: <?= htmlspecialchars((string)($doc->user_id ?? '')) ?></small>
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
                  <small>Language: <?= htmlspecialchars($mcqQuestions[(string)$doc->mcq_id]['language'] ?? 'Unknown') ?></small>
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
                  <small>Difficulty: <?= htmlspecialchars($mcqQuestions[(string)$doc->mcq_id]['difficulty'] ?? 'Unknown') ?></small>
                <?php else: ?>
                  <div class="fw-bold"><?= htmlspecialchars($problemTitles[(string)$doc->problem_id] ?? 'Unknown Problem') ?></div>
                  <small>Language: <?= htmlspecialchars((string)($doc->language ?? '')) ?></small>
                <?php endif; ?>
              </td>
              <td>
                <?php $idAttr = 'm'.substr(md5((string)($doc->_id ?? uniqid())), 0, 8); ?>
                <form method="POST" action="../admin/admin_delete_submission.php" style="display:inline;margin-right:6px;" class="delete-form">
                  <input type="hidden" name="id" value="<?= htmlspecialchars((string)$doc->_id ?? '') ?>">
                  <button type="submit" class="btn btn-sm btn-danger delete-btn">Delete</button>
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
        <a class="btn btn-outline-light" href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $page - 1]))) ?>">&laquo; Prev (Newer)</a>
      <?php else: ?><div></div><?php endif; ?>

      <?php if ($page < $totalPages): ?>
        <a class="btn btn-outline-light" href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>">Next (Older) &raquo;</a>
      <?php elseif (count($rows) === $limit && $totalPages == 0): // Special case for first fetch when total pages not calculated ?>
         <a class="btn btn-outline-light" href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>">Next (Older) &raquo;</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets\js\submissions.js"></script>
</body>
</html>