<?php
session_start();

// We need to check for the session. However, we'll allow 'guest' to pass through.
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if the user is a guest
$is_guest = $_SESSION['user_id'] === 'guest';

// The rest of your PHP logic can remain the same
require_once "../config/db_mongo.php";

$coll = getCollection('coding_platform', 'problems');

// Fetch unique languages
$command = new MongoDB\Driver\Command([
    'distinct' => 'problems',
    'key' => 'language'
]);
// Fetch languages present in DB
$languages = $coll['manager']->executeCommand($coll['db'], $command)->toArray()[0]->values ?? [];

// Also include supported languages explicitly so new tracks appear when added
$supportedLanguages = ['c', 'cpp', 'java', 'javascript', 'html', 'css', 'python'];
$languages = array_merge($languages, $supportedLanguages);

// Normalize, unique, and sort for consistent display
$languages = array_values(array_unique(array_filter(array_map(function($l){
    return is_string($l) ? strtolower(trim($l)) : $l;
}, $languages), function($l){ return !empty($l); })));
sort($languages, SORT_STRING | SORT_FLAG_CASE);

// Friendly display names
$languageLabels = [
    'c' => 'C',
    'cpp' => 'C++',
    'c++' => 'C++',
    'java' => 'Java',
    'javascript' => 'JavaScript',
    'js' => 'JavaScript',
    'html' => 'HTML',
    'css' => 'CSS',
    'python' => 'Python',
];

// Count problems per language
$problems_count = [];
foreach ($languages as $lang) {
    $query = new MongoDB\Driver\Query(['language' => $lang]);
    $count = count($coll['manager']->executeQuery($coll['db'] . ".problems", $query)->toArray());
    $problems_count[$lang] = $count;
}

// --- NEW FUNCTIONALITY LOGIC ---

// MCQ count (distinct category optional later). If collection exists, count all docs
$mcqCount = 0;
try {
    // Assuming a 'mcq' collection exists in your MongoDB database
    $mcqQuery = new MongoDB\Driver\Query([]);
    $mcqCount = count($coll['manager']->executeQuery($coll['db'] . ".mcq", $mcqQuery)->toArray());
} catch (Throwable $e) {
    $mcqCount = 0;
}

$twoPlayerCount = $mcqCount; // Use total MCQ count for questions available


// Fetch recent comments for display (excluding deleted ones)
$comments = [];
try {
    $commentsColl = getCollection('coding_platform', 'comments');
    // Filter out deleted comments
    $filter = ['$or' => [
        ['deleted' => ['$exists' => false]], // Comments without deleted field
        ['deleted' => false] // Comments explicitly marked as not deleted
    ]];
    $commentsQuery = new MongoDB\Driver\Query($filter, ['sort' => ['created_at' => -1], 'limit' => 100]);
    $commentsResult = $commentsColl['manager']->executeQuery($commentsColl['db'] . ".comments", $commentsQuery)->toArray();
    $comments = array_map(function($doc) {
        return [
            'username' => $doc->username,
            'comment' => $doc->comment,
            'rating' => $doc->rating ?? 0,
            'created_at' => $doc->created_at
        ];
    }, $commentsResult);
} catch (Throwable $e) {
    $comments = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SkillForge — Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
<div class="stars"></div>
<canvas id="webDash" class="web"></canvas>
<div class="orb o1"></div>
<div class="orb o2"></div>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">SkillForge</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if ($is_guest): ?>
                    <li class="nav-item">
                        <span class="nav-link text-white">Hello, <?= $_SESSION['username'] ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login / Register</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <span class="nav-link text-white">
                            Hello, <?= $_SESSION['username'] ?>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <span class="badge bg-warning text-dark">Admin</span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="submissions.php">Submissions</a></li>
                        <li class="nav-item"><a class="nav-link" href="../admin/users_admin.php">Users</a></li>
                        <li class="nav-item"><a class="nav-link" href="../admin/admin_feedback.php">Feedback</a></li>
                        <li class="nav-item"><a class="nav-link" href="../admin/admin_mail.php">Send Mail</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="../core/leaderboard.php">Leaderboard</a></li>
                    <li class="nav-item">
                        <a class="nav-link" href="chat.php">Global Q&A <span id="qa-notification" class="badge bg-danger" style="display: none;">New</span></a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="../core/comment.php">Leave Feedback</a></li>
                    <li class="nav-item"><a class="nav-link" href="../core/logout.php">Logout</a></li>
                <?php endif; ?>

                <li class="nav-item ms-3">
                    <div class="toggle-switch" id="themeToggle">
                        <div class="knob"></div>
                        <span class="icon sun">☀️</span>
                        <span class="icon moon">🌙</span>
                    </div>
                </li>
                <li class="nav-item ms-3">
                    <div class="toggle-switch" id="animToggle">
                        <div class="knob"></div>
                        <span class="icon on">✨</span>
                        <span class="icon off">🚫</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>


<div class="container mt-5 section">
    <h2 class="mb-4 heading">Choose a Language to Practice</h2>
    <div class="row">
        <?php foreach ($languages as $lang): $pal=['f1','f2','f3','f4']; static $x=0; $cls=$pal[$x%4]; $x++; ?>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm h-100 feature <?= $cls ?>">
                    <div class="card-body">
                        <h5 class="card-title mb-1"><?= $languageLabels[strtolower($lang)] ?? ucfirst($lang) ?></h5>
                        <p class="card-text mb-3"><?= $problems_count[$lang] ?> problems available</p>
                        <a href="problems.php?language=<?= $lang ?>" class="btn btn-primary btn-animated">Start Practicing</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($mcqCount > 0): $cls=$pal[$x%4]; $x++; ?>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm h-100 feature <?= $cls ?>">
                    <div class="card-body">
                        <h5 class="card-title mb-1">MCQ Practice</h5>
                        <p class="card-text mb-3"><?= $mcqCount ?> questions available</p>
                        <a href="mcq.php?index=0" class="btn btn-primary btn-animated">Start MCQs</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($twoPlayerCount > 0): $cls=$pal[$x%4]; $x++; ?>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm h-100 feature f2"> 
                    <div class="card-body">
                        <h5 class="card-title mb-1">🔥 Two-Player Quiz Battle</h5>
                        <p class="card-text mb-3"><?= $twoPlayerCount ?> potential questions for battle</p>
                        <a href="challenge.php" class="btn btn-primary btn-animated">Start Challenge</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        </div>
    
    <div class="floating-comment-btn">
        <a href="../core/comment.php" class="btn btn-primary btn-animated" title="Leave Feedback">
            💬 Feedback
        </a>
    </div>
    
    <?php if (!empty($comments)): ?>
    <div class="mt-5">
        <h3 class="mb-4 heading">What Our Users Say</h3>

        <div id="commentsCarousel" class="comments-carousel" aria-live="polite">
            <div class="track" id="commentsCarouselTrack"></div>
            <div class="controls" id="commentsCarouselControls" aria-label="Carousel controls">
                <button type="button" class="ctrl-btn" id="ccPrev" title="Previous">◀</button>
                <button type="button" class="ctrl-btn" id="ccPlayPause" title="Pause">❚❚</button>
                <button type="button" class="ctrl-btn" id="ccNext" title="Next">▶</button>
            </div>
        </div>

        <div id="commentsSource" style="display:none;">
            <div class="row">
            <?php foreach ($comments as $comment): 
                $commentPalettes = ['f1','f2','f3','f4']; 
                static $commentIndex = 0; 
                $commentClass = $commentPalettes[$commentIndex % 4]; 
                $commentIndex++; 
            ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card shadow-sm h-100 comment-card feature <?= $commentClass ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0"><?= htmlspecialchars($comment['username']) ?></h6>
                                <?php if ($comment['rating'] > 0): ?>
                                    <div class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span style="color: <?= $i <= $comment['rating'] ? '#ffd700' : '#666' ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <p class="card-text"><?= htmlspecialchars($comment['comment']) ?></p>
                            <small>
                                <?= date('M j, Y', $comment['created_at']->toDateTime()->getTimestamp()) ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="../core/comment.php" class="btn btn-primary btn-animated">Share Your Feedback</a>
        </div>
    </div>
    <?php else: ?>
    <div class="mt-5 text-center">
        <h3 class="mb-4 heading">Be the First to Share Feedback!</h3>
        <p class="subtitle mb-4">Help us improve SkillForge by sharing your experience</p>
        <a href="../core/comment.php" class="btn btn-primary btn-animated">Leave Your Comment</a>
    </div>
    <?php endif; ?>
</div>

<script src="assets/js/dashboard_api.js"></script>
<script src="assets/js/dashboard_ui.js"></script>
<script src="assets/js/dashboard_effects.js"></script>
<script src="assets/js/dashboard_carousel.js"></script>
</body>
</html>