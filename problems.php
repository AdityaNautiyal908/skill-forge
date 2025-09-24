<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/db_mongo.php";

// Get language and index from URL
$language = $_GET['language'] ?? null;
if (!$language) die("Language not specified!");
$index = isset($_GET['index']) ? (int)$_GET['index'] : 0;

// Fetch all problems of this language (sorted so new ones slot in automatically)
$coll = getCollection('coding_platform', 'problems');
$query = new MongoDB\Driver\Query(
    ['language' => $language],
    [
        // If documents have an 'order' field, use it; otherwise fallback to _id
        'sort' => ['order' => 1, '_id' => 1]
    ]
);
$allProblems = $coll['manager']->executeQuery($coll['db'] . ".problems", $query)->toArray();

if (count($allProblems) == 0) die("No problems found for this language!");

// Ensure index is within bounds
if ($index < 0) $index = 0;
if ($index >= count($allProblems)) $index = count($allProblems) - 1;

$problem = $allProblems[$index];

// Handle form submission
$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    if ($code) {
        // Insert submission in MongoDB
        $subColl = getCollection('coding_platform', 'submissions');
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->insert([
            'type' => 'code',
            'user_id' => $_SESSION['user_id'],
            'problem_id' => $problem->_id,
            'language' => $problem->language,
            'code' => $code,
            'submitted_at' => new MongoDB\BSON\UTCDateTime()
        ]);
        $subColl['manager']->executeBulkWrite($subColl['db'] . "." . $subColl['collection'], $bulk);

        // Increment index for next problem
        $index++;
        if ($index < count($allProblems)) {
            header("Location: problems.php?language=$language&index=$index&msg=" . urlencode("Code submitted successfully!"));
            exit;
        } else {
            header("Location: problems.php?language=$language&index=" . ($index-1) . "&msg=" . urlencode("Congratulations! You completed all problems!"));
            exit;
        }
    }
}

// Display success message if any
if (isset($_GET['msg'])) $successMessage = $_GET['msg'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SkillForge — <?= htmlspecialchars($problem->title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.23.1/ace.js" crossorigin="anonymous"></script>
<style>
body {
    margin: 0;
    color: white;
    min-height: 100vh;
    background: radial-gradient(1200px 600px at 10% 10%, rgba(167,119,227,0.25), transparent 60%),
                radial-gradient(1000px 600px at 90% 30%, rgba(110,142,251,0.25), transparent 60%),
                linear-gradient(135deg, #0f1020, #111437 60%, #0a0d2a);
    overflow-x: hidden;
}
.light { color:#2d3748 !important; background: radial-gradient(1200px 600px at 10% 10%, rgba(0,0,0,0.08), transparent 60%), radial-gradient(1000px 600px at 90% 30%, rgba(0,0,0,0.06), transparent 60%), linear-gradient(135deg, #e2e8f0, #cbd5e0 60%, #a0aec0) !important; }
.light .title, .light h1, .light h2, .light h3, .light h4, .light h5, .light h6 {
    color: #1a202c !important;
}
.light .subtitle, .light p, .light .desc, .light .card-text {
    color: #4a5568 !important;
}
.stars { position: fixed; inset: 0; background: radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,0.8), transparent 60%), radial-gradient(1px 1px at 40% 70%, rgba(255,255,255,0.6), transparent 60%), radial-gradient(1px 1px at 65% 25%, rgba(255,255,255,0.7), transparent 60%), radial-gradient(1px 1px at 80% 55%, rgba(255,255,255,0.5), transparent 60%); opacity: .5; pointer-events: none; }
.web { position: fixed; inset:0; z-index:0; pointer-events:none; }
.no-anim .stars, .no-anim .web, .no-anim .orb { display:none !important; }
.orb { position:absolute; border-radius:50%; filter: blur(20px); opacity:.5; animation: float 12s ease-in-out infinite; }
.o1{ width: 200px; height: 200px; background:#6e8efb; top:-60px; left:-60px; }
.o2{ width: 260px; height: 260px; background:#a777e3; bottom:-80px; right:10%; animation-delay:2s; }
/* Light yellow glow */
.light .o1{ background:#ffd54f !important; }
.light .o2{ background:#ffb300 !important; }
@keyframes float { 0%,100%{ transform:translateY(0)} 50%{ transform:translateY(-14px)} }

.section { position: relative; z-index: 1; }
.panel { background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02)); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 18px; }
.title { font-weight: 800; }
.desc { color: rgba(255,255,255,0.8); }
.btn-primary, .btn-success { background: linear-gradient(135deg, #6e8efb, #a777e3); border: none; box-shadow: 0 8px 30px rgba(110,142,251,0.35); }
.btn-outline { background: transparent; border: 1px solid rgba(255,255,255,0.25); color: white; }
#editor { height: 420px; width: 100%; border: 1px solid rgba(255,255,255,0.12); border-radius: 12px; background:#1e1f1e; }
.progress { height: 10px; background: rgba(255,255,255,0.12); }
.progress-bar { background: linear-gradient(135deg, #36d1dc, #5b86e5); }
@media (max-width: 576px){
  #editor { height: 300px; }
}
</style>
</head>
<body>
<div class="stars"></div>
<canvas id="webProb" class="web"></canvas>
<div class="orb o1"></div>
<div class="orb o2"></div>

<div class="container mt-4 section">
    <div class="panel mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="title mb-1"><?= htmlspecialchars($problem->title) ?></h2>
                <p class="desc mb-0"><?= htmlspecialchars($problem->description) ?></p>
            </div>
            <div class="text-end">
                <a href="dashboard.php" id="exitBtn" class="btn btn-outline btn-sm mb-2">Exit</a>
                <div class="small">Problem <?= $index + 1 ?> of <?= count($allProblems) ?></div>
                <div class="progress" style="width:260px;">
                    <div class="progress-bar" role="progressbar" style="width: <?= round((($index + 1) / max(1, count($allProblems))) * 100) ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($successMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <h5 class="mb-2">Write your code below:</h5>
    <form method="POST" action="problems.php?language=<?= $language ?>&index=<?= $index ?>">
        <input type="hidden" name="problem_id" value="<?= $problem->_id ?>">
        <input type="hidden" name="language" value="<?= htmlspecialchars($problem->language) ?>">

        <div id="editor"><?= htmlspecialchars($problem->starter_code ?? '') ?></div>
        <textarea name="code" style="display:none;"></textarea>

        <button type="submit" class="btn btn-success mt-3">Submit Code</button>
    </form>

    <!-- Navigation -->
    <div class="mt-4 d-flex justify-content-between">
        <?php if ($index > 0): ?>
        <a href="problems.php?language=<?= $language ?>&index=<?= $index-1 ?>" class="btn btn-outline nav-problem">&laquo; Previous</a>
        <?php else: ?><div></div><?php endif; ?>

        <?php if ($index < count($allProblems)-1): ?>
        <a href="problems.php?language=<?= $language ?>&index=<?= $index+1 ?>" class="btn btn-primary nav-problem">Next &raquo;</a>
        <?php endif; ?>
    </div>
</div>

<script>
var editor = ace.edit("editor");
editor.setTheme("ace/theme/monokai");
editor.session.setMode("ace/mode/<?= htmlspecialchars($problem->language) ?>");
editor.setOptions({ fontSize:"14pt", showPrintMargin:false });

var form = document.querySelector("form");
form.addEventListener("submit", function() {
    document.querySelector("textarea[name='code']").value = editor.getValue();
    // prevent leave warning on intentional submit
    dirty = false;
});

// Warn on exit if code changed and not submitted yet
var initialCode = editor.getValue();
var dirty = false;
editor.session.on('change', function(){
    dirty = editor.getValue() !== initialCode;
});

function guardExit(e){
    if (dirty) {
        if (!confirm('You have unsaved code. Are you sure you want to leave?')) {
            e.preventDefault();
            return false;
        }
    }
}

var exitBtn = document.getElementById('exitBtn');
if (exitBtn) exitBtn.addEventListener('click', guardExit);
document.querySelectorAll('.nav-problem').forEach(function(a){ a.addEventListener('click', guardExit); });

window.addEventListener('beforeunload', function (e) {
    if (dirty) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// web effect
(function(){
  var canvas = document.getElementById('webProb'); if (!canvas) return; var ctx = canvas.getContext('2d'); var DPR = Math.max(1, window.devicePixelRatio||1);
  function resize(){ canvas.width=innerWidth*DPR; canvas.height=innerHeight*DPR; } window.addEventListener('resize', resize); resize();
  var nodes=[], NUM=45, K=4; for(var i=0;i<NUM;i++){ nodes.push({x:Math.random()*canvas.width,y:Math.random()*canvas.height,vx:(Math.random()-0.5)*0.15*DPR,vy:(Math.random()-0.5)*0.15*DPR,p:Math.random()*1e3}); }
  function loop(){ ctx.clearRect(0,0,canvas.width,canvas.height); for(var i=0;i<nodes.length;i++){ var a=nodes[i]; ctx.fillStyle='rgba(255,255,255,0.02)'; ctx.beginPath(); ctx.arc(a.x,a.y,2*DPR,0,Math.PI*2); ctx.fill(); var near=[]; for(var j=0;j<nodes.length;j++) if(j!==i){var b=nodes[j],dx=a.x-b.x,dy=a.y-b.y,d=dx*dx+dy*dy; near.push({j:j,d:d});} near.sort(function(p,q){return p.d-q.d;}); for(var k=0;k<K;k++){ var idx=near[k]&&near[k].j; if(idx==null) continue; var b=nodes[idx],dx=a.x-b.x,dy=a.y-b.y,dist=Math.sqrt(dx*dx+dy*dy),alpha=Math.max(0,1-dist/(180*DPR)); if(alpha<=0) continue; var isLight=document.body.classList.contains('light'); ctx.strokeStyle=isLight?('rgba(255,203,0,'+(0.16*alpha)+')'):'rgba(160,190,255,'+(0.12*alpha)+')'; ctx.lineWidth=1*DPR; ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke(); var t=(Date.now()+a.p)%1200/1200; var px=a.x+(b.x-a.x)*t, py=a.y+(b.y-a.y)*t; var grad=ctx.createRadialGradient(px,py,0,px,py,10*DPR); if(isLight){ grad.addColorStop(0,'rgba(255,220,120,'+(0.45*alpha)+')'); grad.addColorStop(1,'rgba(255,220,120,0)'); } else { grad.addColorStop(0,'rgba(120,220,255,'+(0.35*alpha)+')'); grad.addColorStop(1,'rgba(120,220,255,0)'); } ctx.fillStyle=grad; ctx.beginPath(); ctx.arc(px,py,10*DPR,0,Math.PI*2); ctx.fill(); }} for(var i=0;i<nodes.length;i++){ var n=nodes[i]; n.x+=n.vx; n.y+=n.vy; if(n.x<0||n.x>canvas.width) n.vx*=-1; if(n.y<0||n.y>canvas.height) n.vy*=-1;} requestAnimationFrame(loop);} loop();})();

// Toggles
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
