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
<title>Skill Forge</title>
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
    overflow-y: hidden;
    background: radial-gradient(1200px 600px at 10% 10%, rgba(76,91,155,0.35), transparent 60%),
                radial-gradient(1000px 600px at 90% 30%, rgba(60,70,123,0.35), transparent 60%),
                linear-gradient(135deg, #171b30, #20254a 55%, #3c467b);
}

/* Light theme override */
body.light {
    color: #2d3748;
    background: radial-gradient(1200px 600px at 10% 10%, rgba(0,0,0,0.08), transparent 60%),
                radial-gradient(1000px 600px at 90% 30%, rgba(0,0,0,0.06), transparent 60%),
                linear-gradient(135deg, #e2e8f0, #cbd5e0 60%, #a0aec0);
}
body.light .title, body.light h1, body.light h2, body.light h3, body.light h4, body.light h5, body.light h6 { color: #1a202c !important; }
body.light .subtitle, body.light p, body.light .desc, body.light .card-text { color: #4a5568 !important; }

/* Floating orbs */
.orb { position: absolute; border-radius: 50%; filter: blur(20px); opacity: 0.45; animation: float 12s ease-in-out infinite; }
.orb.o1 { width: 220px; height: 220px; background: #6d7cff; top: 10%; left: -60px; animation-delay: 0s; }
.orb.o2 { width: 280px; height: 280px; background: #7aa2ff; bottom: -80px; right: 10%; animation-delay: 2s; }
.orb.o3 { width: 160px; height: 160px; background: #9fb0ff; top: 30%; right: -60px; animation-delay: 4s; }

/* Light theme orb colors: warm yellow glow */
body.light .orb.o1 { background: #ffd54f; }
body.light .orb.o2 { background: #ffb300; }
body.light .orb.o3 { background: #ffe082; }

@keyframes float { 0%, 100% { transform: translateY(0) translateX(0); } 50% { transform: translateY(-20px) translateX(10px); } }

/* Starfield */
.stars { position: fixed; inset: 0; background: radial-gradient(1px 1px at 20% 30%, rgba(255,255,255,0.7), transparent 60%), radial-gradient(1px 1px at 40% 70%, rgba(255,255,255,0.55), transparent 60%), radial-gradient(1px 1px at 65% 25%, rgba(255,255,255,0.6), transparent 60%), radial-gradient(1px 1px at 80% 55%, rgba(255,255,255,0.45), transparent 60%); opacity: 0.45; pointer-events: none; }

/* Electric web canvas overlay */
#web { position: fixed; inset: 0; z-index: 0; pointer-events: none; }

/* Disable animation helper */
body.no-anim .orb, body.no-anim .stars, body.no-anim #web { display: none !important; }
body.no-anim .title { animation: none !important; }

/* Buttons */
.btn-primary-glow { background: linear-gradient(135deg, #6d7cff, #7aa2ff); border: none; color: white; padding: 12px 22px; border-radius: 12px; box-shadow: 0 8px 30px rgba(109,124,255,0.35); transition: transform .2s ease, box-shadow .2s ease, background .25s ease, filter .2s ease; }
.btn-primary-glow:hover { transform: translateY(-2px); box-shadow: 0 12px 34px rgba(109,124,255,0.5); background: linear-gradient(135deg, #7b88ff, #8fb1ff); filter: brightness(1.05); }
.btn-primary-glow:active { transform: translateY(0); background: linear-gradient(135deg, #5a69f0, #6e90ff); filter: brightness(.98); }
.btn-ghost { background: transparent; border: 1px solid rgba(255,255,255,0.25); color: white; padding: 12px 22px; border-radius: 12px; backdrop-filter: blur(6px); }

/* Cards/panels */
.feature { background: linear-gradient(180deg, rgba(60,70,123,0.42), rgba(60,70,123,0.18)); border: 1px solid rgba(255,255,255,0.14); }
/* Respect global animation toggle for landing feature cards */
body.no-anim .feature::before,
body.no-anim .feature::after { display:none !important; animation:none !important; }

/* Toggles */
.controls { position: fixed; right: 14px; bottom: 14px; z-index: 9999; display: flex; gap: 8px; }
.toggle-btn { border: 1px solid rgba(255,255,255,0.4); background: rgba(0,0,0,0.35); color:#fff; padding:8px 12px; border-radius:10px; backdrop-filter: blur(6px); cursor:pointer; }
body.light .toggle-btn { border-color: rgba(0,0,0,0.3); background:rgba(255,255,255,0.9); color:#2d3748; }

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
    position: relative;
    animation: electricPulse 3s ease-in-out infinite;
}

@keyframes electricPulse {
    0%, 100% {
        text-shadow: 
            0 0 5px rgba(255, 255, 255, 0.3),
            0 0 10px rgba(110, 142, 251, 0.4),
            0 0 15px rgba(167, 119, 227, 0.3);
        filter: brightness(1);
    }
    25% {
        text-shadow: 
            0 0 8px rgba(255, 255, 255, 0.6),
            0 0 16px rgba(110, 142, 251, 0.8),
            0 0 24px rgba(167, 119, 227, 0.6),
            0 0 32px rgba(110, 142, 251, 0.4);
        filter: brightness(1.2);
    }
    50% {
        text-shadow: 
            0 0 12px rgba(255, 255, 255, 0.8),
            0 0 24px rgba(110, 142, 251, 1),
            0 0 36px rgba(167, 119, 227, 0.8),
            0 0 48px rgba(110, 142, 251, 0.6),
            0 0 60px rgba(167, 119, 227, 0.4);
        filter: brightness(1.4);
    }
    75% {
        text-shadow: 
            0 0 8px rgba(255, 255, 255, 0.6),
            0 0 16px rgba(110, 142, 251, 0.8),
            0 0 24px rgba(167, 119, 227, 0.6),
            0 0 32px rgba(110, 142, 251, 0.4);
        filter: brightness(1.2);
    }
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
.btn-primary-glow:hover { transform: translateY(-2px); box-shadow: 0 12px 34px rgba(110,142,251,0.5); background: linear-gradient(135deg, #7f9bff, #b48af3); filter: brightness(1.05); }
.btn-primary-glow:active { transform: translateY(0); background: linear-gradient(135deg, #5c78ef, #905fdc); filter: brightness(.98); }
.btn-animated { position: relative; overflow:hidden; cursor:pointer; }
.btn-animated .ripple { position:absolute; border-radius:50%; transform: scale(0); animation: ripple .6s linear; background: rgba(255,255,255,0.7); pointer-events:none; }
@keyframes ripple { to { transform: scale(10); opacity: 0; } }

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
    position: relative;
    overflow: hidden;
    transform-style: preserve-3d;
    transition: transform .25s ease, box-shadow .25s ease, background .25s ease, border-color .25s ease;
}
.feature h5 { margin-bottom: 8px; }
.feature p { margin: 0; color: rgba(255,255,255,0.75); font-size: 14px; }

/* Animated gradient border + glow using CSS variables per-card */
.feature::before {
    content: "";
    position: absolute;
    inset: -2px;
    border-radius: 16px;
    padding: 2px;
    background: conic-gradient(from 0deg, var(--c1), var(--c2), var(--c3), var(--c1));
    -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
    -webkit-mask-composite: xor; /* Safari */
    mask-composite: exclude;     /* Others */
    animation: spin 8s linear infinite;
    pointer-events: none;
}
.feature::after {
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
.feature:hover { transform: translateY(-4px) rotateX(2deg); box-shadow: 0 20px 50px rgba(0,0,0,.38); }

/* Hover background tint per palette */
.feature.f1:hover { background: linear-gradient(180deg, rgba(110,142,251,0.18), rgba(110,142,251,0.06)); border-color: rgba(110,142,251,0.35); }
.feature.f2:hover { background: linear-gradient(180deg, rgba(255,126,179,0.18), rgba(255,126,179,0.06)); border-color: rgba(255,126,179,0.35); }
.feature.f3:hover { background: linear-gradient(180deg, rgba(62,207,142,0.18), rgba(62,207,142,0.06)); border-color: rgba(62,207,142,0.35); }
.feature.f4:hover { background: linear-gradient(180deg, rgba(255,165,0,0.18), rgba(255,165,0,0.06)); border-color: rgba(255,165,0,0.35); }

/* Card palettes */
.feature.f1 { --c1:#6e8efb; --c2:#a777e3; --c3:#36d1dc; --glow: rgba(110,142,251,.45); }
.feature.f2 { --c1:#ff7eb3; --c2:#ff758c; --c3:#ffd86f; --glow: rgba(255,126,179,.45); }
.feature.f3 { --c1:#5efc8d; --c2:#3ecf8e; --c3:#9be15d; --glow: rgba(62,207,142,.45); }
.feature.f4 { --c1:#ffd54f; --c2:#ffa726; --c3:#ff6f61; --glow: rgba(255,165,0,.45); }

@keyframes spin { to { transform: rotate(360deg); } }
@keyframes pulse { 0%,100%{ opacity:.28; transform: translateY(0);} 50%{ opacity:.5; transform: translateY(-6px);} }
/* Splash screen */
.splash {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(1200px 600px at 10% 10%, rgba(167,119,227,0.35), transparent 60%),
                radial-gradient(1000px 600px at 90% 30%, rgba(110,142,251,0.35), transparent 60%),
                linear-gradient(135deg, #0f1020, #111437 60%, #0a0d2a);
    z-index: 10000;
}
.splash .brand {
    font-weight: 900;
    font-size: clamp(34px, 8vw, 72px);
    letter-spacing: 2px;
    background: linear-gradient(90deg, #fff, #e6d6ff 30%, #b3c6ff 70%);
    -webkit-background-clip: text; background-clip: text; color: transparent;
    filter: drop-shadow(0 0 20px rgba(110,142,251,.45));
    opacity: 0; transform: translateY(10px) scale(.96);
    animation: splashSequence 2.8s ease forwards, glowPulse 3s ease-in-out infinite 1.8s;
}
@keyframes splashSequence {
  0% { opacity:0; transform: translateY(10px) scale(.92); }
  20% { opacity:1; transform: translateY(0) scale(1.06); }
  45% { opacity:1; transform: translateY(0) scale(1.0); }
  70% { opacity:1; transform: translateY(-12vh) scale(.98); }
  100% { opacity:0; transform: translateY(-46vh) scale(.92); }
}
@keyframes glowPulse {
  0%,100% { filter: drop-shadow(0 0 12px rgba(110,142,251,.25)); }
  50% { filter: drop-shadow(0 0 26px rgba(167,119,227,.45)); }
}
</style>
</head>
<body>
<div class="stars"></div>
<canvas id="web"></canvas>
<div class="orb o1"></div>
<div class="orb o2"></div>
<div class="orb o3"></div>

<!-- Splash overlay (first visit) -->
<div id="splash" class="splash" style="display:none;">
  <div class="brand">SkillForge</div>
  
  <!-- Optional: small delay dots -->
  <div style="position:absolute; bottom: 16%; display:flex; gap:8px;">
    <span style="width:8px;height:8px;border-radius:50%;background:#a777e3;opacity:.6;animation: dots 1.2s infinite"></span>
    <span style="width:8px;height:8px;border-radius:50%;background:#6e8efb;opacity:.6;animation: dots 1.2s infinite .2s"></span>
    <span style="width:8px;height:8px;border-radius:50%;background:#36d1dc;opacity:.6;animation: dots 1.2s infinite .4s"></span>
  </div>
</div>


<section class="hero container">
    <a href="index.php" class="logo">SkillForge</a>
    <h1 class="title">Level up your coding skills with interactive challenges</h1>
    <p class="subtitle">Practice HTML, CSS, JavaScript and more with real problems, instant feedback, and a delightful editor. Learn by doing and build confidence one challenge at a time.</p>
    <div class="cta-wrap">
        <a href="login.php" class="btn btn-primary-glow btn-animated">Start Solving</a>
        <a href="register.php" class="btn btn-ghost">Create Account</a>
    </div>

    <div class="features mt-4">
        <div class="feature f1">
            <h5>Live Code Editor</h5>
            <p>Write, run, and iterate with syntax highlighting and instant feedback.</p>
        </div>
        <div class="feature f2">
            <h5>Curated Tracks</h5>
            <p>Follow language tracks and progress through bite-sized challenges.</p>
        </div>
        <div class="feature f3">
            <h5>Progress Saving</h5>
            <p>Pick up where you left off. Your submissions are stored securely.</p>
        </div>
        <div class="feature f4">
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
        // base wire with theme-aware color
        var isLight = document.body.classList.contains('light');
        var wireColor = isLight ? 'rgba(255,203,0,'+(0.16*alpha)+')' : 'rgba(160,190,255,'+(0.12*alpha)+')';
        ctx.strokeStyle = wireColor;
        ctx.lineWidth = 1*DPR;
        ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke();

        // electric pulse traveling
        var t = (Date.now() + a.pulse) % 1200 / 1200; // 0..1
        var px = a.x + (b.x-a.x)*t;
        var py = a.y + (b.y-a.y)*t;
        var grad = ctx.createRadialGradient(px,py,0,px,py,10*DPR);
        if (isLight) {
          grad.addColorStop(0,'rgba(255,220,120,'+(0.45*alpha)+')');
          grad.addColorStop(1,'rgba(255,220,120,0)');
        } else {
          grad.addColorStop(0,'rgba(120,220,255,'+(0.35*alpha)+')');
          grad.addColorStop(1,'rgba(120,220,255,0)');
        }
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

// Electric title effect
(function(){
  var title = document.querySelector('.title');
  if (!title) return;
  
  function createElectricEffect() {
    var letters = title.textContent.split('');
    title.innerHTML = letters.map(function(letter, index) {
      return '<span style="animation-delay: ' + (index * 0.1) + 's;">' + letter + '</span>';
    }).join('');
  }
  
  createElectricEffect();
  
  // Add hover effect for extra electric surge
  title.addEventListener('mouseenter', function() {
    this.style.animation = 'electricPulse 0.5s ease-in-out infinite';
  });
  
  title.addEventListener('mouseleave', function() {
    this.style.animation = 'electricPulse 3s ease-in-out infinite';
  });
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

// Button ripple effect for landing
document.querySelectorAll('.btn-animated').forEach(function(btn){
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

// Splash logic: show only on first visit for ~1.8s
(function(){
  var seen = sessionStorage.getItem('sf_seen_splash');
  var splash = document.getElementById('splash');
  if (!splash) return;
  if (!seen) {
    splash.style.display = 'flex';
    // Let CSS sequence play, then fully remove overlay to show page
    setTimeout(function(){
      splash.style.display = 'none';
      sessionStorage.setItem('sf_seen_splash','1');
    }, 3000);
  }
})();
</script>
</body>
</html>
