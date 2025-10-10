// --- SweetAlert for Reset Status ---
document.addEventListener('DOMContentLoaded', function() {
    // PHP_MESSAGE is passed as a global constant in reset_password.php
    
    if (typeof PHP_MESSAGE !== 'undefined' && PHP_MESSAGE) {
        
        // Check if the message contains the success string to trigger the timer/redirect logic
        const isSuccess = PHP_MESSAGE.includes('Success!');
        
        if (isSuccess) {
            let timerInterval;
            Swal.fire({
                icon: 'success',
                title: 'Password Reset!',
                // Use a standard message for the HTML content as the PHP message includes the redirection notice
                html: 'Your password has been changed successfully.<br>Redirecting you in <b></b> milliseconds.',
                timer: 3000, 
                timerProgressBar: true,
                background: '#3c467b',
                color: '#fff',
                confirmButtonColor: '#6d7cff',
                didOpen: () => {
                    Swal.showLoading();
                    const b = Swal.getHtmlContainer().querySelector('b');
                    timerInterval = setInterval(() => {
                        // Display timer left in milliseconds
                        b.textContent = Swal.getTimerLeft(); 
                    }, 100);
                },
                willClose: () => {
                    clearInterval(timerInterval);
                    // The PHP header already handles the redirection, but this ensures client-side continuity
                    // Note: The PHP header("refresh:5;url=login.php") handles the actual redirect delay.
                }
            });
        } else {
            // Display error/info for invalid token, mismatch, or server error
            Swal.fire({
                icon: 'error',
                title: 'Reset Failed',
                text: PHP_MESSAGE,
                background: '#3c467b',
                color: '#fff',
                confirmButtonColor: '#6d7cff'
            });
        }
    }
});


// --- Web animation (Copied from original) ---
(function(){
    var canvas = document.getElementById('webLogin'); if (!canvas) return; var ctx = canvas.getContext('2d'); var DPR = Math.max(1, window.devicePixelRatio||1);
    function resize(){ canvas.width=innerWidth*DPR; canvas.height=innerHeight*DPR; } window.addEventListener('resize', resize); resize();
    var nodes=[], NUM=40, K=4; for(var i=0;i<NUM;i++){ nodes.push({x:Math.random()*canvas.width,y:Math.random()*canvas.height,vx:(Math.random()-0.5)*0.15*DPR,vy:(Math.random()-0.5)*0.15*DPR,p:Math.random()*1e3}); }
    function loop(){ ctx.clearRect(0,0,canvas.width,canvas.height); var isLight=document.body.classList.contains('light'); for(var i=0;i<nodes.length;i++){ var a=nodes[i]; ctx.fillStyle='rgba(255,255,255,0.02)'; ctx.beginPath(); ctx.arc(a.x,a.y,2*DPR,0,Math.PI*2); ctx.fill(); var near=[]; for(var j=0;j<nodes.length;j++) if(j!==i){var b=nodes[j],dx=a.x-b.x,dy=a.y-b.y,d=dx*dx+dy*dy; near.push({j:j,d:d});} near.sort(function(p,q){return p.d-q.d;}); for(var k=0;k<K;k++){ var idx=near[k]&&near[k].j; if(idx==null) continue; var b=nodes[idx],dx=a.x-b.x,dy=a.y-b.y,dist=Math.sqrt(dx*dx+dy*dy),alpha=Math.max(0,1-dist/(180*DPR)); if(alpha<=0) continue; ctx.strokeStyle=isLight?('rgba(255,203,0,'+(0.16*alpha)+')'):'rgba(160,190,255,'+(0.12*alpha)+')'; ctx.lineWidth=1*DPR; ctx.beginPath(); ctx.moveTo(a.x,a.y); ctx.lineTo(b.x,b.y); ctx.stroke(); var t=(Date.now()+a.p)%1200/1200; var px=a.x+(b.x-a.x)*t, py=a.y+(b.y-a.y)*t; var grad=ctx.createRadialGradient(px,py,0,px,py,10*DPR); if(isLight){grad.addColorStop(0,'rgba(255,220,120,'+(0.45*alpha)+')'); grad.addColorStop(1,'rgba(255,220,120,0)');} else {grad.addColorStop(0,'rgba(120,220,255,'+(0.35*alpha)+')'); grad.addColorStop(1,'rgba(120,220,255,0)');} ctx.fillStyle=grad; ctx.beginPath(); ctx.arc(px,py,10*DPR,0,Math.PI*2); ctx.fill(); }} for(var i=0;i<nodes.length;i++){ var n=nodes[i]; n.x+=n.vx; n.y+=n.vy; if(n.x<0||n.x>canvas.width) n.vx*=-1; if(n.y<0||n.y>canvas.height) n.vy*=-1;} requestAnimationFrame(loop);} loop();})();

// --- Theme/Animation Toggles (Copied from original) ---
(function(){
    function apply(){ var theme=localStorage.getItem('sf_theme')||'dark'; var anim=localStorage.getItem('sf_anim')||'on'; document.body.classList.toggle('light', theme==='light'); document.body.classList.toggle('no-anim', anim==='off'); }
    apply();
    var box=document.createElement('div'); box.style.position='fixed'; box.style.right='14px'; box.style.bottom='14px'; box.style.zIndex='9999'; box.style.display='flex'; box.style.gap='8px';
    function mk(label){ 
        var b=document.createElement('button'); b.textContent=label; 
        b.style.border='1px solid rgba(255,255,255,0.4)'; b.style.background='rgba(0,0,0,0.35)'; b.style.color='#fff'; b.style.padding='8px 12px'; b.style.borderRadius='10px'; b.style.backdropFilter='blur(6px)'; b.style.cursor='pointer';
        return b; 
    }
    var tBtn=mk((localStorage.getItem('sf_theme')||'dark')==='light'?'Dark Mode':'Light Mode');
    var aBtn=mk((localStorage.getItem('sf_anim')||'on')==='off'?'Enable Anim':'Disable Anim');
    tBtn.onclick=function(){ var cur=localStorage.getItem('sf_theme')||'dark'; var next=cur==='dark'?'light':'dark'; localStorage.setItem('sf_theme',next); tBtn.textContent=next==='light'?'Dark Mode':'Light Mode'; apply(); };
    aBtn.onclick=function(){ var cur=localStorage.getItem('sf_anim')||'on'; var next=cur==='on'?'off':'on'; localStorage.setItem('sf_anim',next); aBtn.textContent=next==='off'?'Enable Anim':'Disable Anim'; apply(); };
    document.body.appendChild(box); box.appendChild(tBtn); box.appendChild(aBtn);
})();