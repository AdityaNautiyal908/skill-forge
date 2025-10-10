// --- ACE Editor Initialization and Form Submission ---
var editor = ace.edit("editor");
editor.setTheme("ace/theme/monokai");
// Set mode dynamically based on problem language (PHP variable is HTML-escaped)
editor.session.setMode("ace/mode/<?= htmlspecialchars($problem->language) ?>");
editor.setOptions({ fontSize:"14pt", showPrintMargin:false });

var form = document.querySelector("form");
form.addEventListener("submit", function() {
    // Transfer code from Ace editor to the hidden textarea for submission
    document.querySelector("textarea[name='code']").value = editor.getValue();
    // Prevent leave warning on intentional submit
    dirty = false;
});


// --- Exit Guard Logic ---
var initialCode = editor.getValue();
var dirty = false;
editor.session.on('change', function(){
    // Set dirty flag whenever the code changes from the initial state
    dirty = editor.getValue() !== initialCode;
});

function guardExit(e){
    if (dirty) {
        // Use standard browser confirmation dialog
        if (!confirm('You have unsaved code. Are you sure you want to leave?')) {
            e.preventDefault();
            return false;
        }
    }
}

// Apply guard to navigation links and window closing
var exitBtn = document.getElementById('exitBtn');
if (exitBtn) exitBtn.addEventListener('click', guardExit);
document.querySelectorAll('.nav-problem').forEach(function(a){ a.addEventListener('click', guardExit); });

window.addEventListener('beforeunload', function (e) {
    if (dirty) {
        // Standard way to prompt user in modern browsers
        e.preventDefault();
        e.returnValue = '';
    }
});


// --- Web Animation (Background Effect) ---
(function(){
    var canvas = document.getElementById('webProb'); if (!canvas) return; var ctx = canvas.getContext('2d'); var DPR = Math.max(1, window.devicePixelRatio||1);
    function resize(){ canvas.width=innerWidth*DPR; canvas.height=innerHeight*DPR; } window.addEventListener('resize', resize); resize();
    var nodes=[], NUM=45, K=4; for(var i=0;i<NUM;i++){ nodes.push({x:Math.random()*canvas.width,y:Math.random()*canvas.height,vx:(Math.random()-0.5)*0.15*DPR,vy:(Math.random()-0.5)*0.15*DPR,p:Math.random()*1e3}); }
    function loop(){ ctx.clearRect(0,0,canvas.width,canvas.height); for(var i=0;i<nodes.length;i++){ var a=nodes[i]; ctx.fillStyle='rgba(255,255,255,0.02)'; ctx.beginPath(); ctx.arc(a.x,a.y,2*DPR,0,Math.PI*2); ctx.fill(); var near=[]; for(var j=0;j<nodes.length;j++) if(j!==i){var b=nodes[j],dx=a.x-b.x,dy=a.y-b.y,d=dx*dx+dy*dy; near.push({j:j,d:d});} near.sort(function(p,q){return p.d-q.d;}); for(var k=0;k<K;k++){ var idx=near[k]&&near[k].j; if(idx==null) continue; var b=nodes[idx],dx=a.x-b.x,dy=a.y-b.y,dist=Math.sqrt(dx*dx+dy*dy),alpha=Math.max(0,1-dist/(180*DPR)); if(alpha<=0) continue; var isLight=document.body.classList.contains('light'); ctx.strokeStyle=isLight?('rgba(255,203,0,'+(0.16*alpha)+')'):'rgba(160,190,255,'+(0.12*alpha)+')'; ctx.lineWidth=1*DPR; ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke(); var t=(Date.now()+a.p)%1200/1200; var px=a.x+(b.x-a.x)*t, py=a.y+(b.y-a.y)*t; var grad=ctx.createRadialGradient(px,py,0,px,py,10*DPR); if(isLight){ grad.addColorStop(0,'rgba(255,220,120,'+(0.45*alpha)+')'); grad.addColorStop(1,'rgba(255,220,120,0)'); } else { grad.addColorStop(0,'rgba(120,220,255,'+(0.35*alpha)+')'); grad.addColorStop(1,'rgba(120,220,255,0)'); } ctx.fillStyle=grad; ctx.beginPath(); ctx.arc(px,py,10*DPR,0,Math.PI*2); ctx.fill(); }} for(var i=0;i<nodes.length;i++){ var n=nodes[i]; n.x+=n.vx; n.y+=n.vy; if(n.x<0||n.x>canvas.width) n.vx*=-1; if(n.y<0||n.y>canvas.height) n.vy*=-1;} requestAnimationFrame(loop);} loop();})();


// --- Theme/Animation Toggles Logic ---
(function(){
    var themeBtn = document.getElementById('themeToggleBtn');
    var animBtn = document.getElementById('animToggleBtn');

    function applyToggles(){ 
        // Read theme/anim from localStorage
        var theme = localStorage.getItem('sf_theme') || 'dark'; 
        var anim = localStorage.getItem('sf_anim') || 'on'; 
        
        // Apply classes to body
        document.body.classList.toggle('light', theme === 'light'); 
        document.body.classList.toggle('no-anim', anim === 'off');
        
        // Update button visual state
        if (themeBtn) {
            themeBtn.classList.toggle('dark-active', theme === 'dark');
        }
        if (animBtn) {
            animBtn.classList.toggle('anim-off-active', anim === 'off');
        }
    }
    
    // Set initial state
    applyToggles();
    
    if (themeBtn) {
        themeBtn.onclick = function(){ 
            var cur = localStorage.getItem('sf_theme') || 'dark'; 
            var next = cur === 'dark' ? 'light' : 'dark'; 
            localStorage.setItem('sf_theme', next); 
            applyToggles(); 
        };
    }
    
    if (animBtn) {
        animBtn.onclick = function(){ 
            var cur = localStorage.getItem('sf_anim') || 'on'; 
            var next = cur === 'on' ? 'off' : 'on'; 
            localStorage.setItem('sf_anim', next); 
            applyToggles(); 
        };
    }
    
    // Button ripple for submit (copied into this file for simplicity)
    (document.querySelectorAll('.btn-animated')||[]).forEach(function(btn){
        btn.addEventListener('click', function(e){
            // Only run if animations are on
            if (document.body.classList.contains('no-anim')) return;

            var rect = this.getBoundingClientRect();
            var ripple = document.createElement('span');
            var size = Math.max(rect.width, rect.height);
            ripple.className = 'ripple';
            
            // Inline styles for ripple (must be here as it's dynamic)
            ripple.style.cssText = `
                position:absolute; 
                border-radius:50%; 
                transform: scale(0); 
                animation: ripple .6s linear; 
                background: rgba(255,255,255,0.7); 
                pointer-events:none;
                width: ${size}px;
                height: ${size}px;
                left: ${(e.clientX - rect.left - size/2)}px;
                top: ${(e.clientY - rect.top - size/2)}px;
            `;
            
            this.appendChild(ripple);
            setTimeout(function(){ ripple.remove(); }, 600);
        });
    });
})();