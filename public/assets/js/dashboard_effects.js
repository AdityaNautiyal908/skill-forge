// --- Canvas Web Animation ---
(function(){
    var canvas = document.getElementById('webDash'); 
    if (!canvas) return; 
    var ctx = canvas.getContext('2d'); 
    var DPR = Math.max(1, window.devicePixelRatio||1);
    
    function resize(){ canvas.width=innerWidth*DPR; canvas.height=innerHeight*DPR; } 
    window.addEventListener('resize', resize); 
    resize();
    
    var nodes=[], NUM=50, K=4; 
    for(var i=0;i<NUM;i++){ 
        nodes.push({
            x:Math.random()*canvas.width,
            y:Math.random()*canvas.height,
            vx:(Math.random()-0.5)*0.15*DPR,
            vy:(Math.random()-0.5)*0.15*DPR,
            p:Math.random()*1e3
        }); 
    }
    
    function loop(){ 
        ctx.clearRect(0,0,canvas.width,canvas.height); 
        
        for(var i=0;i<nodes.length;i++){ 
            var a=nodes[i]; 
            // Draw nodes
            ctx.fillStyle='rgba(255,255,255,0.02)'; 
            ctx.beginPath(); ctx.arc(a.x,a.y,2*DPR,0,Math.PI*2); ctx.fill(); 
            
            // Find nearest neighbors
            var near=[]; 
            for(var j=0;j<nodes.length;j++) if(j!==i){var b=nodes[j],dx=a.x-b.x,dy=a.y-b.y,d=dx*dx+dy*dy; near.push({j:j,d:d});} 
            near.sort(function(p,q){return p.d-q.d;}); 
            
            // Draw connections
            for(var k=0;k<K;k++){ 
                var idx=near[k]&&near[k].j; 
                if(idx==null) continue; 
                var b=nodes[idx],dx=a.x-b.x,dy=a.y-b.y,dist=Math.sqrt(dx*dx+dy*dy),alpha=Math.max(0,1-dist/(180*DPR)); 
                if(alpha<=0) continue; 
                
                // Draw line
                ctx.strokeStyle='rgba(160,190,255,'+(0.12*alpha)+')'; 
                ctx.lineWidth=1*DPR; 
                ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke(); 
                
                // Draw travelling pulse
                var t=(Date.now()+a.p)%1200/1200; 
                var px=a.x+(b.x-a.x)*t, py=a.y+(b.y-a.y)*t; 
                var grad=ctx.createRadialGradient(px,py,0,px,py,10*DPR); 
                grad.addColorStop(0,'rgba(120,220,255,'+(0.35*alpha)+')'); 
                grad.addColorStop(1,'rgba(120,220,255,0)'); 
                ctx.fillStyle=grad; 
                ctx.beginPath(); ctx.arc(px,py,10*DPR,0,Math.PI*2); ctx.fill(); 
            }
        } 
        
        // Update node positions
        for(var i=0;i<nodes.length;i++){ 
            var n=nodes[i]; 
            n.x+=n.vx; n.y+=n.vy; 
            if(n.x<0||n.x>canvas.width) n.vx*=-1; 
            if(n.y<0||n.y>canvas.height) n.vy*=-1;
        } 
        requestAnimationFrame(loop);
    } 
    loop();
})();