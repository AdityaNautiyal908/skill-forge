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
require_once "config/db_mongo.php";

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

// MCQ count (distinct category optional later). If collection exists, count all docs
$mcqCount = 0;
try {
    $mcqQuery = new MongoDB\Driver\Query([]);
    $mcqCount = count($coll['manager']->executeQuery($coll['db'] . ".mcq", $mcqQuery)->toArray());
} catch (Throwable $e) {
    $mcqCount = 0;
}

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
    // Debug: Uncomment the line below to see error details
    // error_log("Comments fetch error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SkillForge — Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    margin: 0;
    color: white;
    min-height: 100vh;
    /* Higher-contrast deep blue background */
    background:
        radial-gradient(1200px 600px at 10% 10%, rgba(76,91,155,0.35), transparent 60%),
        radial-gradient(1000px 600px at 90% 30%, rgba(60,70,123,0.35), transparent 60%),
        linear-gradient(135deg, #171b30, #20254a 55%, #3c467b);
    overflow-x: hidden;
}
.light {
    color: #2d3748 !important;
    background: radial-gradient(1200px 600px at 10% 10%, rgba(0,0,0,0.08), transparent 60%),
                radial-gradient(1000px 600px at 90% 30%, rgba(0,0,0,0.06), transparent 60%),
                linear-gradient(135deg, #e2e8f0, #cbd5e0 60%, #a0aec0) !important;
}
.light .title, .light h1, .light h2, .light h3, .light h4, .light h5, .light h6 {
    color: #1a202c !important;
}
.light .subtitle, .light p, .light .desc, .light .card-text {
    color: #4a5568 !important;
}
.stars { position: fixed; inset: 0; background: radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,0.7), transparent 60%), radial-gradient(1px 1px at 40% 70%, rgba(255,255,255,0.55), transparent 60%), radial-gradient(1px 1px at 65% 25%, rgba(255,255,255,0.6), transparent 60%), radial-gradient(1px 1px at 80% 55%, rgba(255,255,255,0.45), transparent 60%); opacity: .45; pointer-events: none; }
.web { position: fixed; inset:0; z-index:0; pointer-events:none; }
.no-anim .stars, .no-anim .web, .no-anim .orb { display:none !important; }
.orb { position:absolute; border-radius:50%; filter: blur(20px); opacity:.45; animation: float 12s ease-in-out infinite; }
.o1{ width: 200px; height: 200px; background:#6d7cff; top:-60px; left:-60px; }
.o2{ width: 260px; height: 260px; background:#7aa2ff; bottom:-80px; right:10%; animation-delay:2s; }
/* Light yellow glow */
.light .o1{ background:#ffd54f !important; }
.light .o2{ background:#ffb300 !important; }
@keyframes float { 0%,100%{ transform:translateY(0)} 50%{ transform:translateY(-14px)} }

.navbar {
    background: rgba(10,12,28,0.45) !important;
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255,255,255,0.12);
}
.navbar-brand { font-weight: 700; }

.section {
    position: relative;
    z-index: 1;
}
.card {
    /* Darker panel with stronger contrast against background */
    background: linear-gradient(180deg, rgba(60,70,123,0.42), rgba(60,70,123,0.18));
    border: 1px solid rgba(255,255,255,0.14);
    color: white;
}
.card-title { font-weight: 700; }
.card-text { color: rgba(255,255,255,0.88); }
.btn-primary {
    /* Brighter button for contrast */
    background: linear-gradient(135deg, #6d7cff, #7aa2ff);
    border: none;
    box-shadow: 0 8px 30px rgba(109,124,255,0.35);
}
.btn-animated { position: relative; overflow: hidden; border: none; transition: transform .15s ease, box-shadow .2s ease, filter .2s ease, background .25s ease; cursor: pointer; }
.btn-primary.btn-animated { background: linear-gradient(135deg, #6d7cff, #7aa2ff); }
.btn-primary.btn-animated:hover { background: linear-gradient(135deg, #7b88ff, #8fb1ff); filter: brightness(1.06); box-shadow: 0 12px 38px rgba(109,124,255,0.5); }
.btn-primary.btn-animated:active { background: linear-gradient(135deg, #5a69f0, #6e90ff); filter: brightness(.98); box-shadow: 0 6px 18px rgba(109,124,255,0.35); }
.btn-animated .ripple { position:absolute; border-radius:50%; transform: scale(0); animation: ripple .6s linear; background: rgba(255,255,255,0.7); pointer-events:none; }
@keyframes ripple { to { transform: scale(10); opacity: 0; } }

/* Feature/comment cards keep their conic-border effect but on deeper base */
.feature { position: relative; overflow: hidden; transform-style: preserve-3d; transition: transform .1s ease, box-shadow .2s ease, background .25s ease, border-color .25s ease; will-change: transform;
    background-image: var(--spotGradient, none), linear-gradient(180deg, rgba(60,70,123,0.42), rgba(60,70,123,0.18));
}
.feature::before { content:""; position:absolute; inset:-2px; border-radius: 16px; padding:2px; background: conic-gradient(from 0deg, var(--c1), var(--c2), var(--c3), var(--c1)); -webkit-mask:linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0); -webkit-mask-composite: xor; mask-composite: exclude; animation: spin 8s linear infinite; pointer-events:none; }
.feature::after { content:""; position:absolute; inset:-30% -20% auto -20%; height:120%; background: radial-gradient(closest-side, var(--glow) 0%, transparent 60%); filter: blur(16px); opacity:.32; animation: pulse 4.5s ease-in-out infinite; pointer-events:none; }
.feature:hover { box-shadow: 0 20px 46px rgba(0,0,0,.5); }
.feature.f1 { --c1:#6d7cff; --c2:#7aa2ff; --c3:#a8b0ff; --glow: rgba(109,124,255,.45); }
.feature.f2 { --c1:#ff7eb3; --c2:#ff758c; --c3:#ffd86f; --glow: rgba(255,126,179,.45); }
.feature.f3 { --c1:#5efc8d; --c2:#3ecf8e; --c3:#9be15d; --glow: rgba(62,207,142,.45); }
.feature.f4 { --c1:#ffd54f; --c2:#ffa726; --c3:#ff6f61; --glow: rgba(255,165,0,0.45); }
.feature.f1:hover { border-color: rgba(109,124,255,0.45); }
.feature.f2:hover { border-color: rgba(255,126,179,0.45); }
.feature.f3:hover { border-color: rgba(62,207,142,0.45); }
.feature.f4:hover { border-color: rgba(255,165,0,0.45); }
@keyframes spin { to { transform: rotate(360deg);} }
@keyframes pulse { 0%,100%{ opacity:.28; transform: translateY(0);} 50%{ opacity:.5; transform: translateY(-6px);} }
.heading { font-weight: 800; }

/* Floating comment button */
.floating-comment-btn {
    position: fixed;
    bottom: 80px;
    right: 20px;
    z-index: 1000;
    animation: float 3s ease-in-out infinite;
}
.floating-comment-btn .btn {
    border-radius: 25px;
    padding: 12px 20px;
    font-weight: 600;
    box-shadow: 0 8px 30px rgba(110,142,251,0.4);
}

/* Comment card specific styles */
.comment-card {
    position: relative;
    overflow: hidden;
    transform-style: preserve-3d;
    transition: transform .1s ease, box-shadow .2s ease, background .25s ease, border-color .25s ease;
    will-change: transform;
    background-image: var(--spotGradient, none), linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
}

.comment-card::before {
    content: "";
    position: absolute;
    inset: -2px;
    border-radius: 16px;
    padding: 2px;
    background: conic-gradient(from 0deg, var(--c1), var(--c2), var(--c3), var(--c1));
    -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    animation: spin 8s linear infinite;
    pointer-events: none;
}

.comment-card::after {
    content: "";
    position: absolute;
    inset: -30% -20% auto -20%;
    height: 120%;
    background: radial-gradient(closest-side, var(--glow) 0%, transparent 60%);
    filter: blur(16px);
    opacity: .35;
    animation: pulse 4.5s ease-in-out infinite;
    pointer-events: none;
}

.comment-card:hover {
    box-shadow: 0 20px 46px rgba(0,0,0,.35);
}

/* Comment card hover effects with different color palettes */
.comment-card.f1:hover { border-color: rgba(110,142,251,0.35); }
.comment-card.f2:hover { border-color: rgba(255,126,179,0.35); }
.comment-card.f3:hover { border-color: rgba(62,207,142,0.35); }
.comment-card.f4:hover { border-color: rgba(255,165,0,0.35); }

/* Respect global animation toggle: hide card line effects when animations are off */
.no-anim .feature::before,
.no-anim .feature::after,
.no-anim .comment-card::before,
.no-anim .comment-card::after {
    display: none !important;
    animation: none !important;
}

@media (max-width: 576px){
    .progress { width: 160px !important; }
    .floating-comment-btn {
        bottom: 70px;
        right: 15px;
    }
    .floating-comment-btn .btn {
        padding: 10px 16px;
        font-size: 14px;
    }
}
</style>
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
            <ul class="navbar-nav ms-auto">
                <?php if ($is_guest): ?>
                    <li class="nav-item">
                        <span class="nav-link text-white">Hello, <?= $_SESSION['username'] ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login / Register</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <span class="nav-link text-white">Hello, <?= $_SESSION['username'] ?><?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?> <span class="badge bg-warning text-dark">Admin</span><?php endif; ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="submissions.php">Submissions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users_admin.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_feedback.php">Feedback</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="leaderboard.php">Leaderboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="chat.php">Global Q&A <span id="qa-notification" class="badge bg-danger" style="display: none;">New</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="comment.php">Leave Feedback</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                <?php endif; ?>
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

        <?php if ($mcqCount > 0): $cls=$pal[$x%4]; ?>
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
    </div>
    
    <div class="floating-comment-btn">
        <a href="comment.php" class="btn btn-primary btn-animated" title="Leave Feedback">
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
                            <small class="text-muted">
                                <?= date('M j, Y', $comment['created_at']->toDateTime()->getTimestamp()) ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="comment.php" class="btn btn-primary btn-animated">Share Your Feedback</a>
        </div>
    </div>
    <?php else: ?>
    <div class="mt-5 text-center">
        <h3 class="mb-4 heading">Be the First to Share Feedback!</h3>
        <p class="subtitle mb-4">Help us improve SkillForge by sharing your experience</p>
        <a href="comment.php" class="btn btn-primary btn-animated">Leave Your Comment</a>
    </div>
    <?php endif; ?>
</div>
<script>
// Function to check for new notifications
function checkNotifications() {
    // Use a direct file check approach
    fetch('check_notifications_simple.php')
        .then(response => response.json())
        .then(data => {
            console.log('Notification check:', data);
            if (data.has_notifications) {
                document.getElementById('qa-notification').style.display = 'inline';
                document.getElementById('qa-notification').textContent = 'New';
                
                // Play sound if notification wasn't showing before
                if (document.getElementById('qa-notification').style.display === 'none') {
                    playNotificationSound();
                    // Flash the notification badge
                    flashNotification();
                }
            } else {
                document.getElementById('qa-notification').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error checking notifications:', error);
        });
}

// Function to flash the notification badge
function flashNotification() {
    const badge = document.getElementById('qa-notification');
    let flashCount = 0;
    
    const flashInterval = setInterval(() => {
        badge.style.backgroundColor = flashCount % 2 === 0 ? '#dc3545' : '#28a745';
        flashCount++;
        
        if (flashCount > 5) {
            clearInterval(flashInterval);
            badge.style.backgroundColor = '#dc3545'; // Reset to original color
        }
    }, 300);
}

// Check for notifications on page load
checkNotifications();

// Check for notifications every 5 seconds for real-time updates
setInterval(checkNotifications, 5000);

// Play notification sound when new messages arrive
let previousCount = 0;
function playNotificationSound() {
    // Create audio element for notification sound
    const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
    audio.volume = 0.5;
    audio.play().catch(e => console.log('Audio play prevented by browser policy'));
}
</script>
</body>
</html>
<script>
(function(){
    var canvas = document.getElementById('webDash'); if (!canvas) return; var ctx = canvas.getContext('2d'); var DPR = Math.max(1, window.devicePixelRatio||1);
    function resize(){ canvas.width=innerWidth*DPR; canvas.height=innerHeight*DPR; } window.addEventListener('resize', resize); resize();
    var nodes=[], NUM=50, K=4; for(var i=0;i<NUM;i++){ nodes.push({x:Math.random()*canvas.width,y:Math.random()*canvas.height,vx:(Math.random()-0.5)*0.15*DPR,vy:(Math.random()-0.5)*0.15*DPR,p:Math.random()*1e3}); }
    function loop(){ ctx.clearRect(0,0,canvas.width,canvas.height); for(var i=0;i<nodes.length;i++){ var a=nodes[i]; ctx.fillStyle='rgba(255,255,255,0.02)'; ctx.beginPath(); ctx.arc(a.x,a.y,2*DPR,0,Math.PI*2); ctx.fill(); var near=[]; for(var j=0;j<nodes.length;j++) if(j!==i){var b=nodes[j],dx=a.x-b.x,dy=a.y-b.y,d=dx*dx+dy*dy; near.push({j:j,d:d});} near.sort(function(p,q){return p.d-q.d;}); for(var k=0;k<K;k++){ var idx=near[k]&&near[k].j; if(idx==null) continue; var b=nodes[idx],dx=a.x-b.x,dy=a.y-b.y,dist=Math.sqrt(dx*dx+dy*dy),alpha=Math.max(0,1-dist/(180*DPR)); if(alpha<=0) continue; ctx.strokeStyle='rgba(160,190,255,'+(0.12*alpha)+')'; ctx.lineWidth=1*DPR; ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke(); var t=(Date.now()+a.p)%1200/1200; var px=a.x+(b.x-a.x)*t, py=a.y+(b.y-a.y)*t; var grad=ctx.createRadialGradient(px,py,0,px,py,10*DPR); grad.addColorStop(0,'rgba(120,220,255,'+(0.35*alpha)+')'); grad.addColorStop(1,'rgba(120,220,255,0)'); ctx.fillStyle=grad; ctx.beginPath(); ctx.arc(px,py,10*DPR,0,Math.PI*2); ctx.fill(); }} for(var i=0;i<nodes.length;i++){ var n=nodes[i]; n.x+=n.vx; n.y+=n.vy; if(n.x<0||n.x>canvas.width) n.vx*=-1; if(n.y<0||n.y>canvas.height) n.vy*=-1;} requestAnimationFrame(loop);} loop();})();

// Button ripple
(document.querySelectorAll('.btn-animated')||[]).forEach(function(btn){
    btn.addEventListener('click', function(e){
        var rect = this.getBoundingClientRect();
        var ripple = document.createElement('span');
        var size = Math.max(rect.width, rect.height);
        ripple.className = 'ripple';
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = (e.clientX - rect.left - size/2) + 'px';
        ripple.style.top = (e.clientY - rect.top - size/2) + 'px';
        this.appendChild(ripple);
        setTimeout(function(){ ripple.remove(); }, 600);
    });
});

// Toggles
(function(){
    function apply(){
        var theme = localStorage.getItem('sf_theme')||'dark';
        var anim = localStorage.getItem('sf_anim')||'on';
        document.body.classList.toggle('light', theme==='light');
        document.body.classList.toggle('no-anim', anim==='off');
    }
    apply();
    var box=document.createElement('div'); box.style.position='fixed'; box.style.right='14px'; box.style.bottom='14px'; box.style.zIndex='9999'; box.style.display='flex'; box.style.gap='8px';
    function mk(label){ var b=document.createElement('button'); b.textContent=label; b.style.border='1px solid rgba(255,255,255,0.4)'; b.style.background='rgba(0,0,0,0.35)'; b.style.color='#fff'; b.style.padding='8px 12px'; b.style.borderRadius='10px'; b.style.backdropFilter='blur(6px)'; return b; }
    var tBtn=mk((localStorage.getItem('sf_theme')||'dark')==='light'?'Dark Mode':'Light Mode');
    var aBtn=mk((localStorage.getItem('sf_anim')||'on')==='off'?'Enable Anim':'Disable Anim');
    tBtn.onclick=function(){ var cur=localStorage.getItem('sf_theme')||'dark'; var next=cur==='dark'?'light':'dark'; localStorage.setItem('sf_theme',next); tBtn.textContent=next==='light'?'Dark Mode':'Light Mode'; apply(); };
    aBtn.onclick=function(){ var cur=localStorage.getItem('sf_anim')||'on'; var next=cur==='on'?'off':'on'; localStorage.setItem('sf_anim',next); aBtn.textContent=next==='off'?'Enable Anim':'Disable Anim'; apply(); };
    box.appendChild(tBtn); box.appendChild(aBtn); document.body.appendChild(box);
})();
</script>
<script>
// Comments LTR carousel: continuous scrolling of full cards (built from hidden source)
(function(){
    var wrap = document.getElementById('commentsCarousel');
    var track = document.getElementById('commentsCarouselTrack');
    var source = document.getElementById('commentsSource');
    if (!wrap || !track || !source) return;

    // Inject minimal styles
    var css = document.createElement('style');
    css.textContent = '\n.comments-carousel{position:relative;overflow:hidden;border:1px solid rgba(255,255,255,0.08);border-radius:12px;background:linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));margin-bottom:16px;}\n.comments-carousel .track{display:flex;gap:18px;align-items:stretch;will-change:transform;padding:12px;}\n.comments-carousel .comment-card{min-width:420px;width:420px;}\n.comments-carousel .controls{position:absolute;right:10px;bottom:10px;display:flex;gap:8px;z-index:2}\n.comments-carousel .ctrl-btn{border:1px solid rgba(255,255,255,0.35);background:rgba(0,0,0,0.35);color:#fff;padding:6px 10px;border-radius:8px;backdrop-filter:blur(6px);cursor:pointer}\n.comments-carousel .ctrl-btn:hover{background:rgba(255,255,255,0.15)}\n@media (max-width:576px){ .comments-carousel .comment-card{min-width:320px;width:320px;} }';
    document.head.appendChild(css);

    // Gather cards from hidden source
    var cards = Array.prototype.slice.call(source.querySelectorAll('.comment-card'));
    if (!cards.length) return;
    var cardWidth = null; // computed later
    var html = cards.map(function(c){ return c.outerHTML; }).join('');
    track.innerHTML = html + html; // duplicate for seamless loop

    var totalWidth = 0;
    function recalc(){ totalWidth = track.scrollWidth / 2; var first = track.querySelector('.comment-card'); cardWidth = first ? first.getBoundingClientRect().width + 18 /* gap */ : 420; }
    recalc();
    window.addEventListener('resize', function(){ setTimeout(recalc, 100); });

    // Animate left-to-right
    var offset = -totalWidth;
    var speed = 40; // px/s
    var last = null;
    var paused = false;
    var rafId = null;
    function step(ts){
        if (paused) { rafId = requestAnimationFrame(step); return; }
        if (last == null) last = ts;
        var dt = (ts - last) / 1000; last = ts;
        offset += speed * dt;
        if (offset >= 0) offset = -totalWidth;
        track.style.transform = 'translateX(' + offset + 'px)';
        rafId = requestAnimationFrame(step);
    }
    rafId = requestAnimationFrame(step);

    // Controls
    var prevBtn = document.getElementById('ccPrev');
    var nextBtn = document.getElementById('ccNext');
    var playPauseBtn = document.getElementById('ccPlayPause');

    function jump(delta){
        // Stop momentarily to avoid fighting animation frame
        paused = true;
        // snap by one card width left (-) or right (+). Since LTR, right is increasing offset
        offset += delta;
        // wrap handling
        while (offset >= 0) offset -= totalWidth;
        while (offset < -totalWidth) offset += totalWidth;
        track.style.transform = 'translateX(' + offset + 'px)';
        // small delay then resume if play state is not paused by user
        setTimeout(function(){ if (playPauseBtn.getAttribute('data-paused') !== 'true') { paused = false; } }, 50);
    }

    if (prevBtn) prevBtn.addEventListener('click', function(){ jump(-cardWidth); });
    if (nextBtn) nextBtn.addEventListener('click', function(){ jump(cardWidth); });
    if (playPauseBtn) playPauseBtn.addEventListener('click', function(){
        var isPaused = this.getAttribute('data-paused') === 'true';
        if (isPaused) {
            this.setAttribute('data-paused', 'false');
            this.textContent = '❚❚';
            paused = false;
            last = null; // reset timing for smooth resume
        } else {
            this.setAttribute('data-paused', 'true');
            this.textContent = '►';
            paused = true;
        }
    });
})();
</script>
<script>
// Pointer-based 3D tilt for dashboard feature cards and comment cards
(function(){
    var cards = document.querySelectorAll('.feature');
    if (!cards || cards.length === 0) return;
    var maxTilt = 10; // degrees

    function setTransform(card, xRatio, yRatio){
        // xRatio and yRatio are in [-0.5, 0.5]
        var rotateX = (yRatio * -2) * maxTilt; // move up => tilt back
        var rotateY = (xRatio * 2) * maxTilt;  // move right => tilt right
        card.style.transform = 'perspective(800px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg)';
    }

    function handleMove(e){
        var card = e.currentTarget;
        var rect = card.getBoundingClientRect();
        var x = (e.clientX - rect.left) / Math.max(1, rect.width) - 0.5; // -0.5..0.5
        var y = (e.clientY - rect.top) / Math.max(1, rect.height) - 0.5;  // -0.5..0.5
        setTransform(card, x, y);
        // Spotlight gradient following cursor
        var px = (x + 0.5) * 100; // 0..100
        var py = (y + 0.5) * 100; // 0..100
        var glow = getComputedStyle(card).getPropertyValue('--glow') || 'rgba(110,142,251,.45)';
        var spot = 'radial-gradient(300px 200px at ' + px + '% ' + py + '%, ' + glow + ', rgba(0,0,0,0) 70%)';
        card.style.setProperty('--spotGradient', spot);
    }

    function reset(e){
        var card = e.currentTarget;
        card.style.transform = 'perspective(800px) rotateX(0deg) rotateY(0deg)';
        card.style.removeProperty('--spotGradient');
    }

    cards.forEach(function(card){
        card.addEventListener('mousemove', handleMove);
        card.addEventListener('mouseleave', reset);
        card.addEventListener('mouseenter', function(){
            card.style.transition = 'transform .08s ease';
        });
    });
})();
</script>