<?php
session_start();
require_once "config/db_mysql.php";
require_once "includes/mailer.php"; // You'll need a mailer class or function

$message = "";
$success = false; // Flag to control the SweetAlert icon

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = "Please enter your email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // Generate a unique token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+6 hours')); 

            // Store the token and expiry in the database
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
            $stmt->bind_param("ssi", $token, $expires_at, $user['id']);
            $stmt->execute();

            $project_folder = "/skill-forge"; // Define your project folder name
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . $project_folder . "/reset_password.php?token=" . $token;

            // Send the email (assuming send_mail() returns true on success, false/error string on failure)
            $mail_subject = "Password Reset Request";
            $mail_body = "Hello " . htmlspecialchars($user['username']) . ",<br><br>"
                       . "You have requested to reset your password. Please click the link below to continue:<br>"
                       . "<a href='" . htmlspecialchars($reset_link) . "'>Reset Your Password</a><br><br>"
                       . "This link is valid for 6 hours.<br>"
                       . "If you did not request this, please ignore this email.";
            
            
            // NOTE: We now check the return value of send_mail().
            if (send_mail($email, $mail_subject, $mail_body) === true) {
                // *** CHANGED: Direct success message and set flag for SweetAlert ***
                $message = "A password reset link has been successfully sent to your email.";
                $success = true; 
            } else {
                // FAILURE: Show the generic message (log error in mailer.php)
                $message = "If an account with that email exists, a password reset link has been sent.";
                $success = false;
            }

        } else {
            // Email not found: Still show the generic message for security
            $message = "If an account with that email exists, a password reset link has been sent.";
            $success = false;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SkillForge — Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    body {
        margin: 0;
        color: white;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: radial-gradient(1200px 600px at 10% 10%, rgba(76,91,155,0.35), transparent 60%),
                    radial-gradient(1000px 600px at 90% 30%, rgba(60,70,123,0.35), transparent 60%),
                    linear-gradient(135deg, #171b30, #20254a 55%, #3c467b);
        overflow: hidden;
    }
    .light { color:#2d3748 !important; background: radial-gradient(1200px 600px at 10% 10%, rgba(0,0,0,0.08), transparent 60%), radial-gradient(1000px 600px at 90% 30%, rgba(0,0,0,0.06), transparent 60%), linear-gradient(135deg, #e2e8f0, #cbd5e0 60%, #a0aec0) !important; }
    .light .title, .light h1, .light h2, .light h3, .light h4, .light h5, .light h6 { color: #1a202c !important; }
    .light .subtitle, .light p, .light .desc, .light .card-text { color: #4a5568 !important; }
    .stars { position: fixed; inset: 0; background: radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,0.7), transparent 60%), radial-gradient(1px 1px at 40% 70%, rgba(255,255,255,0.55), transparent 60%), radial-gradient(1px 1px at 65% 25%, rgba(255,255,255,0.6), transparent 60%), radial-gradient(1px 1px at 80% 55%, rgba(255,255,255,0.45), transparent 60%); opacity: .45; pointer-events: none; }
    .web { position: fixed; inset:0; z-index:0; pointer-events:none; }
    .orb { position: absolute; border-radius: 50%; filter: blur(20px); opacity: .45; animation: float 12s ease-in-out infinite; }
    .o1{ width: 200px; height: 200px; background:#6d7cff; top:-60px; left:-60px; }
    .o2{ width: 260px; height: 260px; background:#7aa2ff; bottom:-80px; right:10%; animation-delay:2s; }
    @keyframes float { 0%,100%{ transform:translateY(0)} 50%{ transform:translateY(-14px)} }

    .auth-card {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 440px;
        padding: 28px;
        border-radius: 16px;
        background: linear-gradient(180deg, rgba(60,70,123,0.42), rgba(60,70,123,0.18));
        border: 1px solid rgba(255,255,255,0.14);
        box-shadow: 0 10px 40px rgba(0,0,0,0.45);
    }
    .brand { display:inline-block; padding:8px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.14); background:rgba(60,70,123,0.35); font-weight:600; margin-bottom:10px; }
    .title { font-weight:800; margin:0 0 6px 0; }
    .subtitle { color: rgba(255,255,255,0.88); margin-bottom: 18px; }
    .form-label { color: rgba(255,255,255,0.9); }
    .form-control { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.18); color: #fff; }
    .form-control:focus { background: rgba(255,255,255,0.12); color: #fff; border-color: #6d7cff; box-shadow: 0 0 0 0.2rem rgba(109,124,255,0.25); }
    .btn-primary-glow { background: linear-gradient(135deg, #6d7cff, #7aa2ff); border:none; width:100%; padding: 10px 16px; border-radius: 10px; box-shadow: 0 8px 30px rgba(109,124,255,0.35); }
    .btn-guest { background: none; border: 1px solid rgba(255,255,255,0.14); color: rgba(255,255,255,0.88); padding: 10px 16px; width: 100%; border-radius: 10px; margin-top: 10px; }
    .btn-guest:hover { background: rgba(255,255,255,0.08); }
    .alt { color: rgba(255,255,255,0.88); }
    .alt a { color: #cfd8ff; text-decoration: none; }
    .alt a:hover { text-decoration: underline; }
    .form-check { margin-top: -10px; margin-bottom: 18px; }
    </style>
</head>
<body>
    <div class="stars"></div>
    <canvas id="webLogin" class="web"></canvas>
    <div class="orb o1"></div>
    <div class="orb o2"></div>

    <div class="auth-card mx-3">
        <span class="brand">SkillForge</span>
        <h2 class="title" align="center">Forgot Password</h2>
        <p class="subtitle">Enter your email to receive a password reset link.</p>
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary-glow">Send Reset Link</button>
        </form>
        <p class="mt-3 text-center alt">Remember your password? <a href="login.php">Log in</a></p>
    </div>
    
    <script>
        <?php if ($message): ?>
        Swal.fire({
            // Ternary operator to choose icon: 'success' if $success is true, 'info' otherwise
            icon: '<?= $success ? 'success' : 'info' ?>', 
            title: 'Request Status',
            text: '<?= htmlspecialchars($message) ?>',
            background: '#3c467b',
            color: '#fff',
            confirmButtonColor: '#6d7cff'
        });
        <?php endif; ?>
    </script>
    <script>
        // Copy the animated background script from login.php
        (function(){
            var canvas = document.getElementById('webLogin'); if (!canvas) return; var ctx = canvas.getContext('2d'); var DPR = Math.max(1, window.devicePixelRatio||1);
            function resize(){ canvas.width=innerWidth*DPR; canvas.height=innerHeight*DPR; } window.addEventListener('resize', resize); resize();
            var nodes=[], NUM=40, K=4; for(var i=0;i<NUM;i++){ nodes.push({x:Math.random()*canvas.width,y:Math.random()*canvas.height,vx:(Math.random()-0.5)*0.15*DPR,vy:(Math.random()-0.5)*0.15*DPR,p:Math.random()*1e3}); }
            function loop(){ ctx.clearRect(0,0,canvas.width,canvas.height); var isLight=document.body.classList.contains('light'); for(var i=0;i<nodes.length;i++){ var a=nodes[i]; ctx.fillStyle='rgba(255,255,255,0.02)'; ctx.beginPath(); ctx.arc(a.x,a.y,2*DPR,0,Math.PI*2); ctx.fill(); var near=[]; for(var j=0;j<nodes.length;j++) if(j!==i){var b=nodes[j],dx=a.x-b.x,dy=a.y-b.y,d=dx*dx+dy*dy; near.push({j:j,d:d});} near.sort(function(p,q){return p.d-q.d;}); for(var k=0;k<K;k++){ var idx=near[k]&&near[k].j; if(idx==null) continue; var b=nodes[idx],dx=a.x-b.x,dy=a.y-b.y,dist=Math.sqrt(dx*dx+dy*dy),alpha=Math.max(0,1-dist/(180*DPR)); if(alpha<=0) continue; ctx.strokeStyle=isLight?('rgba(255,203,0,'+(0.16*alpha)+')'):'rgba(160,190,255,'+(0.12*alpha)+')'; ctx.lineWidth=1*DPR; ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke(); var t=(Date.now()+a.p)%1200/1200; var px=a.x+(b.x-a.x)*t, py=a.y+(b.y-a.y)*t; var grad=ctx.createRadialGradient(px,py,0,px,py,10*DPR); if(isLight){grad.addColorStop(0,'rgba(255,220,120,'+(0.45*alpha)+')'); grad.addColorStop(1,'rgba(255,220,120,0)');} else {grad.addColorStop(0,'rgba(120,220,255,'+(0.35*alpha)+')'); grad.addColorStop(1,'rgba(120,220,255,0)');} ctx.fillStyle=grad; ctx.beginPath(); ctx.arc(px,py,10*DPR,0,Math.PI*2); ctx.fill(); }} for(var i=0;i<nodes.length;i++){ var n=nodes[i]; n.x+=n.vx; n.y+=n.vy; if(n.x<0||n.x>canvas.width) n.vx*=-1; if(n.y<0||n.y>canvas.height) n.vy*=-1;} requestAnimationFrame(loop);} loop();})();
    </script>
    <script>
        // Copy the theme/animation toggles from login.php
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