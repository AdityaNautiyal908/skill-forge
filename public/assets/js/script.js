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
        // NOTE: The inline style for animation-delay on spans is not necessary for the CSS electricPulse effect, 
        // as the entire H1 element has the animation. I'll revert to the original text for simplicity.
        // If you want a per-letter animation, you'll need to define a different CSS animation for the spans.
        // For now, I'm just leaving the hover effect and relying on the CSS for the main animation.
        // To maintain the current HTML structure without inline styles, the textContent is restored.
        // However, I will keep the original logic since it was in the source.
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