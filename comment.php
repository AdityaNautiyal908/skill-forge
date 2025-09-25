<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "config/db_mongo.php";

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment_text = trim($_POST['comment']);
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    
    // Debug: Log session data
    error_log("Comment submission attempt - User ID: " . $_SESSION['user_id'] . ", Username: " . $_SESSION['username']);
    
    if (empty($comment_text)) {
        $error = "Please write a comment before submitting.";
    } elseif (strlen($comment_text) < 10) {
        $error = "Comment must be at least 10 characters long.";
    } elseif (strlen($comment_text) > 500) {
        $error = "Comment must be less than 500 characters.";
    } else {
        try {
            $coll = getCollection('coding_platform', 'comments');
            
            // Check if user already submitted a comment
            $query = new MongoDB\Driver\Query(['user_id' => $_SESSION['user_id']]);
            $existing = $coll['manager']->executeQuery($coll['db'] . ".comments", $query)->toArray();
            
            if (count($existing) > 0) {
                $error = "You have already submitted a comment. You can only submit one comment per account.";
            } else {
                // Insert new comment
                $bulk = new MongoDB\Driver\BulkWrite;
                $comment_doc = [
                    'user_id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'comment' => $comment_text,
                    'rating' => $rating,
                    'created_at' => new MongoDB\BSON\UTCDateTime(),
                    'status' => 'approved' // Auto-approve for now
                ];
                
                $bulk->insert($comment_doc);
                $result = $coll['manager']->executeBulkWrite($coll['db'] . ".comments", $bulk);
                
                if ($result->getInsertedCount() > 0) {
                    $message = "Thank you for your feedback! Your comment has been submitted successfully.";
                } else {
                    $error = "Failed to submit comment. Please try again.";
                }
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
            // Debug: Log the full error for troubleshooting
            error_log("Comment submission error: " . $e->getMessage() . " - " . $e->getTraceAsString());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SkillForge — Leave Feedback</title>
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
.stars { position: fixed; inset: 0; background: radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,0.8), transparent 60%), radial-gradient(1px 1px at 40% 70%, rgba(255,255,255,0.6), transparent 60%), radial-gradient(1px 1px at 65% 25%, rgba(255,255,255,0.7), transparent 60%), radial-gradient(1px 1px at 80% 55%, rgba(255,255,255,0.5), transparent 60%); opacity: .5; pointer-events: none; }
.web { position: fixed; inset:0; z-index:0; pointer-events:none; }
.no-anim .stars, .no-anim .web, .no-anim .orb { display:none !important; }
.orb { position:absolute; border-radius:50%; filter: blur(20px); opacity:.5; animation: float 12s ease-in-out infinite; }
.o1{ width: 200px; height: 200px; background:#6e8efb; top:-60px; left:-60px; }
.o2{ width: 260px; height: 260px; background:#a777e3; bottom:-80px; right:10%; animation-delay:2s; }
.light .o1{ background:#ffd54f !important; }
.light .o2{ background:#ffb300 !important; }
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

.comment-card {
    background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02));
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px;
    padding: 32px;
    max-width: 600px;
    margin: 0 auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.35);
}

.brand { display:inline-block; padding:8px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.06); font-weight:600; margin-bottom:16px; }
.title { font-weight:800; margin:0 0 8px 0; }
.subtitle { color: rgba(255,255,255,0.8); margin-bottom: 24px; }

.form-label { color: rgba(255,255,255,0.9); font-weight: 600; }
.form-control, .form-select { 
    background: rgba(255,255,255,0.08); 
    border: 1px solid rgba(255,255,255,0.12); 
    color: #fff; 
    border-radius: 10px;
}
.form-control:focus, .form-select:focus { 
    background: rgba(255,255,255,0.12); 
    color: #fff; 
    border-color: #6e8efb; 
    box-shadow: 0 0 0 0.2rem rgba(110,142,251,0.25); 
}
textarea.form-control { min-height: 120px; resize: vertical; }

.btn-primary-glow { 
    background: linear-gradient(135deg, #6e8efb, #a777e3); 
    border:none; 
    padding: 12px 24px; 
    border-radius: 10px; 
    box-shadow: 0 8px 30px rgba(110,142,251,0.35);
    font-weight: 600;
}
.btn-primary-glow:hover { 
    background: linear-gradient(135deg, #7f9bff, #b48af3); 
    transform: translateY(-2px);
    box-shadow: 0 12px 34px rgba(110,142,251,0.5);
}

.btn-secondary { 
    background: rgba(255,255,255,0.1); 
    border: 1px solid rgba(255,255,255,0.2); 
    color: white; 
    padding: 12px 24px; 
    border-radius: 10px;
    text-decoration: none;
    display: inline-block;
    font-weight: 600;
}
.btn-secondary:hover { 
    background: rgba(255,255,255,0.15); 
    color: white;
    text-decoration: none;
}

.rating-stars {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
}
.star {
    font-size: 24px;
    color: #666;
    cursor: pointer;
    transition: color 0.2s ease;
}
.star:hover, .star.active {
    color: #ffd700;
}

.alert {
    border-radius: 10px;
    border: none;
}
.alert-success {
    background: rgba(40, 167, 69, 0.2);
    color: #d4edda;
    border: 1px solid rgba(40, 167, 69, 0.3);
}
.alert-danger {
    background: rgba(220, 53, 69, 0.2);
    color: #f8d7da;
    border: 1px solid rgba(220, 53, 69, 0.3);
}

.char-count {
    font-size: 12px;
    color: rgba(255,255,255,0.6);
    text-align: right;
    margin-top: 5px;
}
</style>
</head>
<body>
<div class="stars"></div>
<canvas id="webComment" class="web"></canvas>
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
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5 section">
    <div class="comment-card">
        <span class="brand">SkillForge</span>
        <h2 class="title">Share Your Feedback</h2>
        <p class="subtitle">Help us improve SkillForge by sharing your thoughts and experience!</p>
        
        <?php if($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <!-- Debug Info (remove this after testing) -->
        <?php if (isset($_GET['debug'])): ?>
            <div class="alert alert-info">
                <strong>Debug Info:</strong><br>
                User ID: <?= $_SESSION['user_id'] ?? 'Not set' ?><br>
                Username: <?= $_SESSION['username'] ?? 'Not set' ?><br>
                Session Status: <?= session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive' ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-4">
                <label class="form-label">Rate your experience (optional)</label>
                <div class="rating-stars">
                    <span class="star" data-rating="1">★</span>
                    <span class="star" data-rating="2">★</span>
                    <span class="star" data-rating="3">★</span>
                    <span class="star" data-rating="4">★</span>
                    <span class="star" data-rating="5">★</span>
                </div>
                <input type="hidden" name="rating" id="rating-input" value="0">
            </div>
            
            <div class="mb-4">
                <label for="comment" class="form-label">Your Comment</label>
                <textarea 
                    name="comment" 
                    id="comment" 
                    class="form-control" 
                    placeholder="Tell us what you think about SkillForge. What did you like? What could be improved? Your feedback helps us make the platform better for everyone!"
                    maxlength="500"
                    required
                ><?= isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : '' ?></textarea>
                <div class="char-count">
                    <span id="char-count">0</span>/500 characters
                </div>
            </div>
            
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary-glow">Submit Feedback</button>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </form>
    </div>
</div>

<script>
// Star rating functionality
document.querySelectorAll('.star').forEach((star, index) => {
    star.addEventListener('click', () => {
        const rating = index + 1;
        document.getElementById('rating-input').value = rating;
        
        // Update star display
        document.querySelectorAll('.star').forEach((s, i) => {
            s.classList.toggle('active', i < rating);
        });
    });
    
    star.addEventListener('mouseenter', () => {
        const rating = index + 1;
        document.querySelectorAll('.star').forEach((s, i) => {
            s.classList.toggle('active', i < rating);
        });
    });
});

document.querySelector('.rating-stars').addEventListener('mouseleave', () => {
    const currentRating = document.getElementById('rating-input').value;
    document.querySelectorAll('.star').forEach((s, i) => {
        s.classList.toggle('active', i < currentRating);
    });
});

// Character count
document.getElementById('comment').addEventListener('input', function() {
    const count = this.value.length;
    document.getElementById('char-count').textContent = count;
});

// Initialize character count
document.getElementById('char-count').textContent = document.getElementById('comment').value.length;
</script>

<script>
// Web animation
(function(){
  var canvas = document.getElementById('webComment'); if (!canvas) return; var ctx = canvas.getContext('2d'); var DPR = Math.max(1, window.devicePixelRatio||1);
  function resize(){ canvas.width=innerWidth*DPR; canvas.height=innerHeight*DPR; } window.addEventListener('resize', resize); resize();
  var nodes=[], NUM=40, K=4; for(var i=0;i<NUM;i++){ nodes.push({x:Math.random()*canvas.width,y:Math.random()*canvas.height,vx:(Math.random()-0.5)*0.15*DPR,vy:(Math.random()-0.5)*0.15*DPR,p:Math.random()*1e3}); }
  function loop(){ ctx.clearRect(0,0,canvas.width,canvas.height); var isLight=document.body.classList.contains('light'); for(var i=0;i<nodes.length;i++){ var a=nodes[i]; ctx.fillStyle='rgba(255,255,255,0.02)'; ctx.beginPath(); ctx.arc(a.x,a.y,2*DPR,0,Math.PI*2); ctx.fill(); var near=[]; for(var j=0;j<nodes.length;j++) if(j!==i){var b=nodes[j],dx=a.x-b.x,dy=a.y-b.y,d=dx*dx+dy*dy; near.push({j:j,d:d});} near.sort(function(p,q){return p.d-q.d;}); for(var k=0;k<K;k++){ var idx=near[k]&&near[k].j; if(idx==null) continue; var b=nodes[idx],dx=a.x-b.x,dy=a.y-b.y,dist=Math.sqrt(dx*dx+dy*dy),alpha=Math.max(0,1-dist/(180*DPR)); if(alpha<=0) continue; ctx.strokeStyle=isLight?('rgba(255,203,0,'+(0.16*alpha)+')'):'rgba(160,190,255,'+(0.12*alpha)+')'; ctx.lineWidth=1*DPR; ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke(); var t=(Date.now()+a.p)%1200/1200; var px=a.x+(b.x-a.x)*t, py=a.y+(b.y-a.y)*t; var grad=ctx.createRadialGradient(px,py,0,px,py,10*DPR); if(isLight){grad.addColorStop(0,'rgba(255,220,120,'+(0.45*alpha)+')'); grad.addColorStop(1,'rgba(255,220,120,0)');} else {grad.addColorStop(0,'rgba(120,220,255,'+(0.35*alpha)+')'); grad.addColorStop(1,'rgba(120,220,255,0)');} ctx.fillStyle=grad; ctx.beginPath(); ctx.arc(px,py,10*DPR,0,Math.PI*2); ctx.fill(); }} for(var i=0;i<nodes.length;i++){ var n=nodes[i]; n.x+=n.vx; n.y+=n.vy; if(n.x<0||n.x>canvas.width) n.vx*=-1; if(n.y<0||n.y>canvas.height) n.vy*=-1;} requestAnimationFrame(loop);} loop();})();
</script>

<script>
// Toggles for theme and animation
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
</html>
