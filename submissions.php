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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SkillForge — Submissions</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { margin:0; color:white; min-height:100vh; background: radial-gradient(1200px 600px at 10% 10%, rgba(167,119,227,0.25), transparent 60%), radial-gradient(1000px 600px at 90% 30%, rgba(110,142,251,0.25), transparent 60%), linear-gradient(135deg, #0f1020, #111437 60%, #0a0d2a); }
.navbar { background: rgba(0,0,0,0.35) !important; backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.08); }
.panel { background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02)); border:1px solid rgba(255,255,255,0.08); border-radius:16px; }
table { color: white; }
table td, table th { border-color: rgba(255,255,255,0.15) !important; }
.code-snippet { max-width: 520px; white-space: pre; overflow: hidden; text-overflow: ellipsis; display: inline-block; vertical-align: top; }
.web { position: fixed; inset:0; z-index:0; pointer-events:none; }
.btn-copy { border: 1px solid rgba(255,255,255,0.25); color:#fff; }
</style>
</head>
<body>
<canvas id="webSub" class="web"></canvas>
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
</body>
<script>
(function(){
  var canvas = document.getElementById('webSub'); if (!canvas) return; var ctx = canvas.getContext('2d'); var DPR = Math.max(1, window.devicePixelRatio||1);
  function resize(){ canvas.width=innerWidth*DPR; canvas.height=innerHeight*DPR; } window.addEventListener('resize', resize); resize();
  var nodes=[], NUM=40, K=4; for(var i=0;i<NUM;i++){ nodes.push({x:Math.random()*canvas.width,y:Math.random()*canvas.height,vx:(Math.random()-0.5)*0.15*DPR,vy:(Math.random()-0.5)*0.15*DPR,p:Math.random()*1e3}); }
  function loop(){ ctx.clearRect(0,0,canvas.width,canvas.height); for(var i=0;i<nodes.length;i++){ var a=nodes[i]; ctx.fillStyle='rgba(255,255,255,0.02)'; ctx.beginPath(); ctx.arc(a.x,a.y,2*DPR,0,Math.PI*2); ctx.fill(); var near=[]; for(var j=0;j<nodes.length;j++) if(j!==i){var b=nodes[j],dx=a.x-b.x,dy=a.y-b.y,d=dx*dx+dy*dy; near.push({j:j,d:d});} near.sort(function(p,q){return p.d-q.d;}); for(var k=0;k<K;k++){ var idx=near[k]&&near[k].j; if(idx==null) continue; var b=nodes[idx],dx=a.x-b.x,dy=a.y-b.y,dist=Math.sqrt(dx*dx+dy*dy),alpha=Math.max(0,1-dist/(180*DPR)); if(alpha<=0) continue; ctx.strokeStyle='rgba(160,190,255,'+(0.12*alpha)+')'; ctx.lineWidth=1*DPR; ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke(); var t=(Date.now()+a.p)%1200/1200; var px=a.x+(b.x-a.x)*t, py=a.y+(b.y-a.y)*t; var grad=ctx.createRadialGradient(px,py,0,px,py,10*DPR); grad.addColorStop(0,'rgba(120,220,255,'+(0.35*alpha)+')'); grad.addColorStop(1,'rgba(120,220,255,0)'); ctx.fillStyle=grad; ctx.beginPath(); ctx.arc(px,py,10*DPR,0,Math.PI*2); ctx.fill(); }} for(var i=0;i<nodes.length;i++){ var n=nodes[i]; n.x+=n.vx; n.y+=n.vy; if(n.x<0||n.x>canvas.width) n.vx*=-1; if(n.y<0||n.y>canvas.height) n.vy*=-1;} requestAnimationFrame(loop);} loop();})();
</script>
</html>

