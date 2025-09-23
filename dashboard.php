<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

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
    background: radial-gradient(1200px 600px at 10% 10%, rgba(167,119,227,0.25), transparent 60%),
                radial-gradient(1000px 600px at 90% 30%, rgba(110,142,251,0.25), transparent 60%),
                linear-gradient(135deg, #0f1020, #111437 60%, #0a0d2a);
    overflow-x: hidden;
}
.light {
    color: #111 !important;
    background: radial-gradient(1200px 600px at 10% 10%, rgba(0,0,0,0.05), transparent 60%),
                radial-gradient(1000px 600px at 90% 30%, rgba(0,0,0,0.04), transparent 60%),
                linear-gradient(135deg, #f7f8fc, #f0f3ff 60%, #e9ecff) !important;
}
.stars { position: fixed; inset: 0; background: radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,0.8), transparent 60%), radial-gradient(1px 1px at 40% 70%, rgba(255,255,255,0.6), transparent 60%), radial-gradient(1px 1px at 65% 25%, rgba(255,255,255,0.7), transparent 60%), radial-gradient(1px 1px at 80% 55%, rgba(255,255,255,0.5), transparent 60%); opacity: .5; pointer-events: none; }
.web { position: fixed; inset:0; z-index:0; pointer-events:none; }
.no-anim .stars, .no-anim .web, .no-anim .orb { display:none !important; }
.orb { position:absolute; border-radius:50%; filter: blur(20px); opacity:.5; animation: float 12s ease-in-out infinite; }
.o1{ width: 200px; height: 200px; background:#6e8efb; top:-60px; left:-60px; }
.o2{ width: 260px; height: 260px; background:#a777e3; bottom:-80px; right:10%; animation-delay:2s; }
@keyframes float { 0%,100%{ transform:translateY(0)} 50%{ transform:translateY(-14px)} }

.navbar {
    background: rgba(0,0,0,0.35) !important;
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.navbar-brand { font-weight: 700; }

.section {
    position: relative;
    z-index: 1;
}
.card {
    background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
    border: 1px solid rgba(255,255,255,0.08);
    color: white;
}
.card-title { font-weight: 700; }
.card-text { color: rgba(255,255,255,0.8); }
.btn-primary {
    background: linear-gradient(135deg, #6e8efb, #a777e3);
    border: none;
    box-shadow: 0 8px 30px rgba(110,142,251,0.35);
}
.heading { font-weight: 800; }
@media (max-width: 576px){
  .progress { width: 160px !important; }
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
                <li class="nav-item">
                    <span class="nav-link text-white">Hello, <?= $_SESSION['username'] ?><?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?> <span class="badge bg-warning text-dark">Admin</span><?php endif; ?></span>
                </li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="submissions.php">Submissions</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5 section">
    <h2 class="mb-4 heading">Choose a Language to Practice</h2>
    <div class="row">
        <?php foreach ($languages as $lang): ?>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-1"><?= $languageLabels[strtolower($lang)] ?? ucfirst($lang) ?></h5>
                        <p class="card-text mb-3"><?= $problems_count[$lang] ?> problems available</p>
                        <a href="problems.php?language=<?= $lang ?>" class="btn btn-primary">Start Practicing</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- MCQ Practice Card -->
        <?php if ($mcqCount > 0): ?>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-1">MCQ Practice</h5>
                        <p class="card-text mb-3"><?= $mcqCount ?> questions available</p>
                        <a href="mcq.php?index=0" class="btn btn-primary">Start MCQs</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
<script>
(function(){
  var canvas = document.getElementById('webDash'); if (!canvas) return; var ctx = canvas.getContext('2d'); var DPR = Math.max(1, window.devicePixelRatio||1);
  function resize(){ canvas.width=innerWidth*DPR; canvas.height=innerHeight*DPR; } window.addEventListener('resize', resize); resize();
  var nodes=[], NUM=50, K=4; for(var i=0;i<NUM;i++){ nodes.push({x:Math.random()*canvas.width,y:Math.random()*canvas.height,vx:(Math.random()-0.5)*0.15*DPR,vy:(Math.random()-0.5)*0.15*DPR,p:Math.random()*1e3}); }
  function loop(){ ctx.clearRect(0,0,canvas.width,canvas.height); for(var i=0;i<nodes.length;i++){ var a=nodes[i]; ctx.fillStyle='rgba(255,255,255,0.02)'; ctx.beginPath(); ctx.arc(a.x,a.y,2*DPR,0,Math.PI*2); ctx.fill(); var near=[]; for(var j=0;j<nodes.length;j++) if(j!==i){var b=nodes[j],dx=a.x-b.x,dy=a.y-b.y,d=dx*dx+dy*dy; near.push({j:j,d:d});} near.sort(function(p,q){return p.d-q.d;}); for(var k=0;k<K;k++){ var idx=near[k]&&near[k].j; if(idx==null) continue; var b=nodes[idx],dx=a.x-b.x,dy=a.y-b.y,dist=Math.sqrt(dx*dx+dy*dy),alpha=Math.max(0,1-dist/(180*DPR)); if(alpha<=0) continue; ctx.strokeStyle='rgba(160,190,255,'+(0.12*alpha)+')'; ctx.lineWidth=1*DPR; ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke(); var t=(Date.now()+a.p)%1200/1200; var px=a.x+(b.x-a.x)*t, py=a.y+(b.y-a.y)*t; var grad=ctx.createRadialGradient(px,py,0,px,py,10*DPR); grad.addColorStop(0,'rgba(120,220,255,'+(0.35*alpha)+')'); grad.addColorStop(1,'rgba(120,220,255,0)'); ctx.fillStyle=grad; ctx.beginPath(); ctx.arc(px,py,10*DPR,0,Math.PI*2); ctx.fill(); }} for(var i=0;i<nodes.length;i++){ var n=nodes[i]; n.x+=n.vx; n.y+=n.vy; if(n.x<0||n.x>canvas.width) n.vx*=-1; if(n.y<0||n.y>canvas.height) n.vy*=-1;} requestAnimationFrame(loop);} loop();})();

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
