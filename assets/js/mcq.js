// CURRENT_THEME and CURRENT_ANIMATION_STATE are defined globally in mcq.php

// --- Theme Application Logic and Toggles ---
(function(){
    // Function to apply theme/animation settings
    function applyThemeAndAnimation() {
        const theme = localStorage.getItem('sf_theme') || CURRENT_THEME;
        const anim = localStorage.getItem('sf_anim') || CURRENT_ANIMATION_STATE;
        
        document.body.classList.toggle('light', theme === 'light');
        document.body.classList.toggle('no-anim', anim === 'off');
    }
    
    // Create box and buttons
    function createToggles() {
        const box=document.createElement('div'); 
        box.style.position='fixed'; box.style.right='14px'; box.style.bottom='14px'; 
        box.style.zIndex='9999'; box.style.display='flex'; box.style.gap='8px';
        
        function mk(label){ 
            var b=document.createElement('button'); b.textContent=label; 
            // NOTE: Using inline styles that match the CSS block for consistency
            b.style.cssText = "border:1px solid rgba(255,255,255,0.4); background:rgba(0,0,0,0.35); color:#fff; padding:8px 12px; border-radius:10px; backdrop-filter:blur(6px); cursor:pointer;";
            return b; 
        }
        
        const currentTheme = localStorage.getItem('sf_theme') || CURRENT_THEME;
        const currentAnim = localStorage.getItem('sf_anim') || CURRENT_ANIMATION_STATE;
        
        var tBtn=mk(currentTheme ==='light'?'Dark Mode':'Light Mode');
        var aBtn=mk(currentAnim ==='off'?'Enable Anim':'Disable Anim');
        
        tBtn.onclick=function(){ 
            var cur=localStorage.getItem('sf_theme')||'dark'; 
            var next=cur==='dark'?'light':'dark'; 
            localStorage.setItem('sf_theme',next); 
            tBtn.textContent=next==='light'?'Dark Mode':'Light Mode'; 
            applyThemeAndAnimation(); 
        };
        
        aBtn.onclick=function(){ 
            var cur=localStorage.getItem('sf_anim')||'on'; 
            var next=cur==='on'?'off':'on'; 
            localStorage.setItem('sf_anim',next); 
            aBtn.textContent=next==='off'?'Enable Anim':'Disable Anim'; 
            applyThemeAndAnimation(); 
        };
        
        document.body.appendChild(box); 
        box.appendChild(tBtn); 
        box.appendChild(aBtn);
    }

    applyThemeAndAnimation();
    document.addEventListener('DOMContentLoaded', createToggles);
})();

// --- Button ripple effect ---
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