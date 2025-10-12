// --- SweetAlert2 for Delete Confirmation ---
document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function (event) {
        event.preventDefault(); // Stop the default form submission

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545', // Danger red color
            cancelButtonColor: '#6c757d', // Secondary gray color
            confirmButtonText: 'Yes, delete it!',
            background: '#111437',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                // If confirmed, submit the form programmatically
                form.submit();
            }
        });
    });
});

// --- Copy Code Functionality (for modals) ---
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

// --- Toggles for theme and animation (Fix: Appending to body) ---
(function(){
    function apply(){ 
        var theme=localStorage.getItem('sf_theme')||'dark'; 
        var anim=localStorage.getItem('sf_anim')||'on'; 
        document.body.classList.toggle('light', theme==='light'); 
        document.body.classList.toggle('no-anim', anim==='off'); 
    }
    apply();
    
    // Create box and buttons
    var box=document.createElement('div'); 
    box.style.position='fixed'; box.style.right='14px'; box.style.bottom='14px'; 
    box.style.zIndex='9999'; box.style.display='flex'; box.style.gap='8px';
    
    function mk(label){ 
        var b=document.createElement('button'); 
        b.textContent=label; 
        // Inline styles copied from original CSS block
        b.style.border='1px solid rgba(255,255,255,0.4)'; 
        b.style.background='rgba(0,0,0,0.35)'; 
        b.style.color='#fff'; 
        b.style.padding='8px 12px'; 
        b.style.borderRadius='10px'; 
        b.style.backdropFilter='blur(6px)';
        b.style.cursor='pointer';
        return b; 
    }
    
    var tBtn=mk((localStorage.getItem('sf_theme')||'dark')==='light'?'Dark Mode':'Light Mode');
    var aBtn=mk((localStorage.getItem('sf_anim')||'on')==='off'?'Enable Anim':'Disable Anim');
    
    tBtn.onclick=function(){ 
        var cur=localStorage.getItem('sf_theme')||'dark'; 
        var next=cur==='dark'?'light':'dark'; 
        localStorage.setItem('sf_theme',next); 
        tBtn.textContent=next==='light'?'Dark Mode':'Light Mode'; 
        apply(); 
    };
    
    aBtn.onclick=function(){ 
        var cur=localStorage.getItem('sf_anim')||'on'; 
        var next=cur==='on'?'off':'on'; 
        localStorage.setItem('sf_anim',next); 
        aBtn.textContent=next==='off'?'Enable Anim':'Disable Anim'; 
        apply(); 
    };
    
    // Append to the body (to keep the floating toggle behavior)
    document.body.appendChild(box); 
    box.appendChild(tBtn); 
    box.appendChild(aBtn);
})();

// --- Web Animation (for the submissions page background) ---
(function(){
    var canvas = document.getElementById('webSub'); 
    if (!canvas) return; 
    var ctx = canvas.getContext('2d'); 
    var DPR = Math.max(1, window.devicePixelRatio||1);
    
    function resize(){ canvas.width=innerWidth*DPR; canvas.height=innerHeight*DPR; } 
    window.addEventListener('resize', resize); 
    resize();
    
    var nodes=[], NUM=40, K=4; 
    for(var i=0;i<NUM;i++){ nodes.push({x:Math.random()*canvas.width,y:Math.random()*canvas.height,vx:(Math.random()-0.5)*0.15*DPR,vy:(Math.random()-0.5)*0.15*DPR,p:Math.random()*1e3}); }
    
    function loop(){ 
        ctx.clearRect(0,0,canvas.width,canvas.height); 
        var isLight=document.body.classList.contains('light'); 
        
        for(var i=0;i<nodes.length;i++){ 
            var a=nodes[i]; 
            ctx.fillStyle='rgba(255,255,255,0.02)'; 
            ctx.beginPath(); ctx.arc(a.x,a.y,2*DPR,0,Math.PI*2); ctx.fill(); 
            
            var near=[]; 
            for(var j=0;j<nodes.length;j++) if(j!==i){var b=nodes[j],dx=a.x-b.x,dy=a.y-b.y,d=dx*dx+dy*dy; near.push({j:j,d:d});} 
            near.sort(function(p,q){return p.d-q.d;}); 
            
            for(var k=0;k<K;k++){ 
                var idx=near[k]&&near[k].j; 
                if(idx==null) continue; 
                var b=nodes[idx],dx=a.x-b.x,dy=a.y-b.y,dist=Math.sqrt(dx*dx+dy*dy),alpha=Math.max(0,1-dist/(180*DPR)); 
                if(alpha<=0) continue; 
                
                // Dynamic color based on theme
                ctx.strokeStyle=isLight?('rgba(255,203,0,'+(0.16*alpha)+')'):'rgba(160,190,255,'+(0.12*alpha)+')'; 
                ctx.lineWidth=1*DPR; 
                ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke(); 
                
                var t=(Date.now()+a.p)%1200/1200; 
                var px=a.x+(b.x-a.x)*t, py=a.y+(b.y-a.y)*t; 
                var grad=ctx.createRadialGradient(px,py,0,px,py,10*DPR); 
                
                // Dynamic pulse color based on theme
                if(isLight){grad.addColorStop(0,'rgba(255,220,120,'+(0.45*alpha)+')'); grad.addColorStop(1,'rgba(255,220,120,0)');} 
                else {grad.addColorStop(0,'rgba(120,220,255,'+(0.35*alpha)+')'); grad.addColorStop(1,'rgba(120,220,255,0)');} 
                
                ctx.fillStyle=grad; 
                ctx.beginPath(); ctx.arc(px,py,10*DPR,0,Math.PI*2); ctx.fill(); 
            }
        } 
        
        for(var i=0;i<nodes.length;i++){ 
            var n=nodes[i]; 
            n.x+=n.vx; n.y+=n.vy; 
            if(n.x<0||n.x>canvas.width) n.vx*=-1; 
            if(n.y<0||n.y>canvas.height) n.vy*=-1;
        } 
        requestAnimationFrame(loop);
    } 
    document.addEventListener('DOMContentLoaded', loop);
})();