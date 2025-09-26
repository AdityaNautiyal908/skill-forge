<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once "config/db_mongo.php";

$message = isset($_GET['message']) ? $_GET['message'] : "";
$error = isset($_GET['error']) ? $_GET['error'] : "";

// Handle delete action
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['comment_id'])) {
    try {
        $coll = getCollection('coding_platform', 'comments');
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update(
            ['_id' => new MongoDB\BSON\ObjectId($_POST['comment_id'])],
            ['$set' => [
                'deleted' => true,
                'deleted_at' => new MongoDB\BSON\UTCDateTime(),
                'deleted_by' => $_SESSION['user_id']
            ]],
            ['upsert' => false]
        );
        $coll['manager']->executeBulkWrite($coll['db'] . '.comments', $bulk);
        $message = "Comment deleted successfully.";
    } catch (Exception $e) {
        $error = "Failed to delete comment: " . $e->getMessage();
    }
}

// Fetch all comments (including deleted ones for admin view)
try {
    $coll = getCollection('coding_platform', 'comments');
    $query = new MongoDB\Driver\Query([], ['sort' => ['created_at' => -1]]);
    $comments = $coll['manager']->executeQuery($coll['db'] . '.comments', $query)->toArray();
} catch (Exception $e) {
    $error = "Failed to fetch comments: " . $e->getMessage();
    $comments = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SkillForge — Admin Feedback Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    margin: 0;
    color: white;
    min-height: 100vh;
    background: radial-gradient(1200px 600px at 10% 10%, rgba(76,91,155,0.35), transparent 60%),
                radial-gradient(1000px 600px at 90% 30%, rgba(60,70,123,0.35), transparent 60%),
                linear-gradient(135deg, #171b30, #20254a 55%, #3c467b);
    overflow-x: hidden;
}
.light { color: #2d3748 !important; background: radial-gradient(1200px 600px at 10% 10%, rgba(0,0,0,0.08), transparent 60%), radial-gradient(1000px 600px at 90% 30%, rgba(0,0,0,0.06), transparent 60%), linear-gradient(135deg, #e2e8f0, #cbd5e0 60%, #a0aec0) !important; }
.light .title, .light h1, .light h2, .light h3, .light h4, .light h5, .light h6 { color: #1a202c !important; }
.light .subtitle, .light p, .light .desc, .light .card-text { color: #4a5568 !important; }
.light .table { background: rgba(255,255,255,0.9); border: 1px solid rgba(0,0,0,0.1); }
.light .table th { background: rgba(0,0,0,0.05); color: #1a202c !important; }
.light .table td { color: #2d3748 !important; background: rgba(255,255,255,0.7); }
.light .table td strong { color: #1a202c !important; }
.light .table td small { color: #4a5568 !important; }
.light .table-striped tbody tr:nth-of-type(odd) { background: rgba(0,0,0,0.02); }
.stars { position: fixed; inset: 0; background: radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,0.7), transparent 60%), radial-gradient(1px 1px at 40% 70%, rgba(255,255,255,0.55), transparent 60%), radial-gradient(1px 1px at 65% 25%, rgba(255,255,255,0.6), transparent 60%), radial-gradient(1px 1px at 80% 55%, rgba(255,255,255,0.45), transparent 60%); opacity: .45; pointer-events: none; }
.web { position: fixed; inset:0; z-index:0; pointer-events:none; }
.no-anim .stars, .no-anim .web, .no-anim .orb { display:none !important; }
.orb { position:absolute; border-radius:50%; filter: blur(20px); opacity:.45; animation: float 12s ease-in-out infinite; }
.o1{ width: 200px; height: 200px; background:#6d7cff; top:-60px; left:-60px; }
.o2{ width: 260px; height: 260px; background:#7aa2ff; bottom:-80px; right:10%; animation-delay:2s; }
@keyframes float { 0%,100%{ transform:translateY(0)} 50%{ transform:translateY(-14px)} }

.navbar { background: rgba(10,12,28,0.45) !important; backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.12); }

.section { position: relative; z-index: 1; }

.comment-card { background: linear-gradient(180deg, rgba(60,70,123,0.42), rgba(60,70,123,0.18)); border: 1px solid rgba(255,255,255,0.14); border-radius: 16px; }
.btn-primary-glow { background: linear-gradient(135deg, #6d7cff, #7aa2ff); border:none; padding: 12px 24px; border-radius: 10px; box-shadow: 0 8px 30px rgba(109,124,255,0.35); font-weight: 600; }
.btn-secondary { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.18); color: white; padding: 12px 24px; border-radius: 10px; }
.btn-danger { background: linear-gradient(135deg, #dc3545, #c82333); border: none; padding: 8px 16px; border-radius: 8px; }
.btn-warning { background: linear-gradient(135deg, #ffc107, #e0a800); border: none; padding: 8px 16px; border-radius: 8px; color: #000; }
.alert-success { background: rgba(40, 167, 69, 0.2); color: #d4edda; border: 1px solid rgba(40, 167, 69, 0.3); }
.alert-danger { background: rgba(220, 53, 69, 0.2); color: #f8d7da; border: 1px solid rgba(220, 53, 69, 0.3); }

.brand { display:inline-block; padding:8px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.06); font-weight:600; margin-bottom:16px; }
.title { font-weight:800; margin:0 0 8px 0; }
.subtitle { color: rgba(255,255,255,0.9); margin-bottom: 24px; }

.table { background: rgba(0,0,0,0.4); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.2); }
.table th { background: rgba(0,0,0,0.6); color: #ffffff !important; border: none; font-weight: 600; }
.table td { color: #ffffff !important; border-color: rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); }
.table-striped tbody tr:nth-of-type(odd) { background: rgba(0,0,0,0.3); }
.table td strong { color: #ffffff !important; font-weight: 700; }
.table td small { color: #ffffff !important; }

.comment-deleted { opacity: 0.6; background: rgba(220, 53, 69, 0.1) !important; }
.comment-deleted td { text-decoration: line-through; }

.rating { color: #ffd700; }

/* Ensure text visibility in table cells */
.table td {
    text-shadow: 0 2px 4px rgba(0,0,0,0.5);
    font-weight: 500;
}

.table th {
    text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}

.light .table td,
.light .table th {
    text-shadow: none;
    font-weight: normal;
}

.alert {
    border-radius: 10px;
    border: none;
}
</style>
</head>
<body>
<div class="stars"></div>
<canvas id="webAdmin" class="web"></canvas>
<div class="orb o1"></div>
<div class="orb o2"></div>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">SkillForge</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="nav-link text-white">Hello, <?= $_SESSION['username'] ?> <span class="badge bg-warning text-dark">Admin</span></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="submissions.php">Submissions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users_admin.php">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="admin_feedback.php">Feedback</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5 section">
    <div class="comment-card p-4">
        <span class="brand">Admin Panel</span>
        <h2 class="title">Feedback Management</h2>
        <p class="subtitle">Manage user feedback and comments</p>
        
        <?php if($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>All Comments (<?= count($comments) ?>)</h4>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <?php if (empty($comments)): ?>
            <div class="text-center py-5">
                <p class="text-muted">No comments found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Comment</th>
                            <th>Rating</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $comment): ?>
                            <tr class="<?= isset($comment->deleted) && $comment->deleted ? 'comment-deleted' : '' ?>">
                                <td>
                                    <strong><?= htmlspecialchars($comment->username) ?></strong><br>
                                    <small class="text-muted">ID: <?= htmlspecialchars((string)$comment->user_id) ?></small>
                                </td>
                                <td>
                                    <div style="max-width: 300px; word-wrap: break-word;">
                                        <?= htmlspecialchars($comment->comment) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($comment->rating > 0): ?>
                                        <div class="rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span style="color: <?= $i <= $comment->rating ? '#ffd700' : '#666' ?>">★</span>
                                            <?php endfor; ?>
                                            <small class="text-muted">(<?= $comment->rating ?>/5)</small>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No rating</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= date('M j, Y g:i A', $comment->created_at->toDateTime()->getTimestamp()) ?>
                                </td>
                                <td>
                                    <?php if (isset($comment->deleted) && $comment->deleted): ?>
                                        <span class="badge bg-danger">Deleted</span>
                                        <?php if (isset($comment->deleted_at)): ?>
                                            <br><small class="text-muted"><?= date('M j, Y', $comment->deleted_at->toDateTime()->getTimestamp()) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <?php if (!isset($comment->deleted) || !$comment->deleted): ?>
                                            <button class="btn btn-warning btn-sm edit-comment-btn" 
                                                    data-comment-id="<?= htmlspecialchars((string)$comment->_id) ?>"
                                                    data-comment-text="<?= htmlspecialchars($comment->comment, ENT_QUOTES) ?>"
                                                    data-comment-rating="<?= $comment->rating ?>">
                                                Edit
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="comment_id" value="<?= (string)$comment->_id ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">No actions</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Comment Modal -->
<div class="modal fade" id="editCommentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background: rgba(60,70,123,0.95); border: 1px solid rgba(255,255,255,0.2);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.2);">
                <h5 class="modal-title" style="color: white;">Edit Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1);"></button>
            </div>
            <form method="POST" action="admin_edit_comment.php">
                <div class="modal-body">
                    <input type="hidden" name="comment_id" id="edit_comment_id">
                    <div class="mb-3">
                        <label class="form-label" style="color: white;">Comment</label>
                        <textarea name="comment" id="edit_comment_text" class="form-control" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white;" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color: white;">Rating</label>
                        <select name="rating" id="edit_rating" class="form-select" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white;">
                            <option value="0">No rating</option>
                            <option value="1">1 Star</option>
                            <option value="2">2 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="5">5 Stars</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.2);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-glow">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to all edit buttons
    document.querySelectorAll('.edit-comment-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            try {
                var commentId = this.getAttribute('data-comment-id');
                var commentText = this.getAttribute('data-comment-text');
                var commentRating = this.getAttribute('data-comment-rating');
                
                // Set form values
                document.getElementById('edit_comment_id').value = commentId;
                document.getElementById('edit_comment_text').value = commentText || '';
                document.getElementById('edit_rating').value = commentRating || 0;
                
                // Show modal
                var modal = new bootstrap.Modal(document.getElementById('editCommentModal'));
                modal.show();
            } catch (error) {
                console.error('Error opening edit modal:', error);
                alert('Error opening edit form. Please try again.');
            }
        });
    });
});
</script>

<script>
// Web animation
(function(){
  var canvas = document.getElementById('webAdmin'); if (!canvas) return; var ctx = canvas.getContext('2d'); var DPR = Math.max(1, window.devicePixelRatio||1);
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
