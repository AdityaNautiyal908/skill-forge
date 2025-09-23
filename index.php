<?php
session_start();

// Redirect logged-in users to dashboard unless explicitly viewing public landing
if (isset($_SESSION['user_id']) && !isset($_GET['public'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CodeLearn - Learn & Practice Programming</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
html, body { height: 100%; }
body {
    margin: 0;
    color: white;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    overflow-x: hidden;
    overflow-y: hidden; /* lock vertical scroll for single-screen hero */
    background: radial-gradient(1200px 600px at 10% 10%, rgba(167,119,227,0.25), transparent 60%),
                radial-gradient(1000px 600px at 90% 30%, rgba(110,142,251,0.25), transparent 60%),
                linear-gradient(135deg, #0f1020, #111437 60%, #0a0d2a);
}

/* Light theme override */
body.light {
    color: #111;
    background: radial-gradient(1200px 600px at 10% 10%, rgba(0,0,0,0.05), transparent 60%),
                radial-gradient(1000px 600px at 90% 30%, rgba(0,0,0,0.04), transparent 60%),
                linear-gradient(135deg, #f7f8fc, #f0f3ff 60%, #e9ecff);
}

/* Floating orbs */
.orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(20px);
    opacity: 0.5;
    animation: float 12s ease-in-out infinite;
}
.orb.o1 { width: 220px; height: 220px; background: #6e8efb; top: 10%; left: -60px; animation-delay: 0s; }
.orb.o2 { width: 280px; height: 280px; background: #a777e3; bottom: -80px; right: 10%; animation-delay: 2s; }
.orb.o3 { width: 160px; height: 160px; background: #36d1dc; top: 30%; right: -60px; animation-delay: 4s; }

@keyframes float {
    0%, 100% { transform: translateY(0) translateX(0); }
    50% { transform: translateY(-20px) translateX(10px); }
}

/* Starfield */
.stars {
    position: fixed;
    inset: 0;
    background: radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,0.8), transparent 60%),
                radial-gradient(1px 1px at 40% 70%, rgba(255,255,255,0.6), transparent 60%),
                radial-gradient(1px 1px at 65% 25%, rgba(255,255,255,0.7), transparent 60%),
                radial-gradient(1px 1px at 80% 55%, rgba(255,255,255,0.5), transparent 60%);
    opacity: 0.6;
    pointer-events: none;
}

/* Electric web canvas overlay */
#web {
    position: fixed;
    inset: 0;
    z-index: 0; /* behind hero but above gradient background */
    pointer-events: none;
}

/* Disable animation helper */
body.no-anim .orb, body.no-anim .stars, body.no-anim #web { display: none !important; }

/* Toggles */
.controls { position: fixed; right: 14px; bottom: 14px; z-index: 9999; display: flex; gap: 8px; }
.toggle-btn { border: 1px solid rgba(255,255,255,0.4); background: rgba(0,0,0,0.35); color:#fff; padding:8px 12px; border-radius:10px; backdrop-filter: blur(6px); cursor:pointer; }
body.light .toggle-btn { border-color: rgba(0,0,0,0.2); background:#fff; color:#333; }

.hero {
    position: relative;
    z-index: 1;
    text-align: center;
    padding: 60px 28px;
    width: 100%;
    max-width: 1100px;
    min-height: 100vh; /* ensure hero fills viewport */
}
.logo {
    display: inline-block;
    padding: 10px 16px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02));
    border: 1px solid rgba(255,255,255,0.08);
    margin-bottom: 18px;
    font-weight: 600;
    letter-spacing: 0.6px;
    color: #fff;
    text-decoration: none;
    cursor: pointer;
}
.title {
    font-size: clamp(32px, 6vw, 60px);
    line-height: 1.08;
    font-weight: 800;
    margin-bottom: 16px;
    background: linear-gradient(90deg, #fff, #e6d6ff 30%, #b3c6ff 70%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}
.subtitle {
    max-width: 820px;
    margin: 0 auto 28px;
    color: rgba(255,255,255,0.82);
    font-size: clamp(14px, 2.6vw, 18px);
}
.cta-wrap {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}
.btn-primary-glow {
    background: linear-gradient(135deg, #6e8efb, #a777e3);
    border: none;
    color: white;
    padding: 12px 22px;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(110,142,251,0.35);
    transition: transform .2s ease, box-shadow .2s ease;
}
.btn-primary-glow:hover { transform: translateY(-2px); box-shadow: 0 12px 34px rgba(110,142,251,0.5); }

.btn-ghost {
    background: transparent;
    border: 1px solid rgba(255,255,255,0.25);
    color: white;
    padding: 12px 22px;
    border-radius: 12px;
    backdrop-filter: blur(6px);
}
.features {
    margin-top: 48px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}
.feature {
    background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px;
    padding: 18px;
    text-align: left;
}
.feature h5 { margin-bottom: 8px; }
.feature p { margin: 0; color: rgba(255,255,255,0.75); font-size: 14px; }
</style>
</head>
<body>
<div class="stars"></div>
<canvas id="web"></canvas>
<div class="orb o1"></div>
<div class="orb o2"></div>
<div class="orb o3"></div>

<section class="hero container">
    <a href="index.php" class="logo">SkillForge</a>
    <h1 class="title">Level up your coding skills with interactive challenges</h1>
    <p class="subtitle">Practice HTML, CSS, JavaScript and more with real problems, instant feedback, and a delightful editor. Learn by doing and build confidence one challenge at a time.</p>
    <div class="cta-wrap">
        <a href="login.php" class="btn btn-primary-glow">Start Solving</a>
        <a href="register.php" class="btn btn-ghost">Create Account</a>
    </div>

    <div class="features mt-4">
        <div class="feature">
            <h5>Live Code Editor</h5>
            <p>Write, run, and iterate with syntax highlighting and instant feedback.</p>
        </div>
        <div class="feature">
            <h5>Curated Tracks</h5>
            <p>Follow language tracks and progress through bite-sized challenges.</p>
        </div>
        <div class="feature">
            <h5>Progress Saving</h5>
            <p>Pick up where you left off. Your submissions are stored securely.</p>
        </div>
        <div class="feature">
            <h5>Beautiful UI</h5>
            <p>Clean, modern interface with subtle motion and pleasing colors.</p>
        </div>
    </div>
</section>

<script>
// Optional: small parallax for orbs
document.addEventListener('mousemove', function(e){
    var x = (e.clientX / window.innerWidth - 0.5) * 20;
    var y = (e.clientY / window.innerHeight - 0.5) * 20;
    document.querySelectorAll('.orb').forEach(function(el, i){
        el.style.transform = 'translate(' + (x*(i+1)) + 'px,' + (y*(i+1)) + 'px)';
    });
});

// Electric spider web animation (lightweight)
(function(){
  var canvas = document.getElementById('web');
  if (!canvas) return;
  var ctx = canvas.getContext('2d');
  var DPR = Math.max(1, window.devicePixelRatio || 1);
  var nodes = [];
  var NUM = 60; // number of points
  var connections = 4; // neighbors per node

  function resize(){
    canvas.width = innerWidth * DPR;
    canvas.height = innerHeight * DPR;
  }
  window.addEventListener('resize', resize);
  resize();

  function init(){
    nodes = [];
    for (var i=0;i<NUM;i++){
      nodes.push({
        x: Math.random()*canvas.width,
        y: Math.random()*canvas.height,
        vx: (Math.random()-0.5)*0.15*DPR,
        vy: (Math.random()-0.5)*0.15*DPR,
        pulse: Math.random()*1000
      });
    }
  }
  init();

  function draw(){
    ctx.clearRect(0,0,canvas.width,canvas.height);

    // softly glow backdrop
    ctx.fillStyle = 'rgba(255,255,255,0.02)';
    for (var i=0;i<nodes.length;i++){
      var n = nodes[i];
      ctx.beginPath();
      ctx.arc(n.x, n.y, 2*DPR, 0, Math.PI*2);
      ctx.fill();
    }

    // connect nearest neighbors
    for (var i=0;i<nodes.length;i++){
      var a = nodes[i];
      // find k nearest
      var nearest = [];
      for (var j=0;j<nodes.length;j++) if (j!==i){
        var b = nodes[j];
        var dx=a.x-b.x, dy=a.y-b.y;
        var d = dx*dx+dy*dy;
        nearest.push({j:j,d:d});
      }
      nearest.sort(function(p,q){return p.d-q.d;});
      for (var k=0;k<connections;k++){
        var idx = nearest[k] && nearest[k].j; if (idx==null) continue;
        var b = nodes[idx];
        var dx=a.x-b.x, dy=a.y-b.y;
        var dist = Math.sqrt(dx*dx+dy*dy);
        var alpha = Math.max(0, 1 - dist/(180*DPR));
        if (alpha<=0) continue;
        // base wire
        ctx.strokeStyle = 'rgba(160,190,255,'+ (0.12*alpha) +')';
        ctx.lineWidth = 1*DPR;
        ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke();

        // electric pulse traveling
        var t = (Date.now() + a.pulse) % 1200 / 1200; // 0..1
        var px = a.x + (b.x-a.x)*t;
        var py = a.y + (b.y-a.y)*t;
        var grad = ctx.createRadialGradient(px,py,0,px,py,10*DPR);
        grad.addColorStop(0,'rgba(120,220,255,'+(0.35*alpha)+')');
        grad.addColorStop(1,'rgba(120,220,255,0)');
        ctx.fillStyle = grad;
        ctx.beginPath(); ctx.arc(px,py,10*DPR,0,Math.PI*2); ctx.fill();
      }
    }

    // update node positions (soft drift)
    for (var i=0;i<nodes.length;i++){
      var n=nodes[i];
      n.x += n.vx; n.y += n.vy;
      if (n.x<0||n.x>canvas.width) n.vx*=-1;
      if (n.y<0||n.y>canvas.height) n.vy*=-1;
    }

    requestAnimationFrame(draw);
  }
  draw();
})();

// Global toggles (theme + animations)
(function(){
  function applyPrefs(){
    var theme = localStorage.getItem('sf_theme') || 'dark';
    var anim = localStorage.getItem('sf_anim') || 'on';
    document.body.classList.toggle('light', theme === 'light');
    document.body.classList.toggle('no-anim', anim === 'off');
  }
  applyPrefs();

  var box = document.createElement('div'); box.className='controls';
  var tBtn = document.createElement('button'); tBtn.className='toggle-btn'; tBtn.textContent = (localStorage.getItem('sf_theme')||'dark')==='light' ? 'Dark Mode' : 'Light Mode';
  var aBtn = document.createElement('button'); aBtn.className='toggle-btn'; aBtn.textContent = (localStorage.getItem('sf_anim')||'on')==='off' ? 'Enable Anim' : 'Disable Anim';
  tBtn.onclick = function(){ var cur = localStorage.getItem('sf_theme')||'dark'; var next = cur==='dark'?'light':'dark'; localStorage.setItem('sf_theme', next); tBtn.textContent = next==='light'?'Dark Mode':'Light Mode'; applyPrefs(); };
  aBtn.onclick = function(){ var cur = localStorage.getItem('sf_anim')||'on'; var next = cur==='on'?'off':'on'; localStorage.setItem('sf_anim', next); aBtn.textContent = next==='off'?'Enable Anim':'Disable Anim'; applyPrefs(); };
  box.appendChild(tBtn); box.appendChild(aBtn); document.body.appendChild(box);
})();
</script>
</body>
</html>
