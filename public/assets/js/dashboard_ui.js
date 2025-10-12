// --- Theme and Animation Toggles ---
(function(){
    function apply(){
        // Apply theme/animation classes to the body
        const theme = localStorage.getItem('sf_theme') || 'dark';
        const anim = localStorage.getItem('sf_anim') || 'on';
        document.body.classList.toggle('light', theme==='light');
        document.body.classList.toggle('no-anim', anim==='off');
        
        // Update toggle button visuals
        const themeToggle = document.getElementById('themeToggle');
        const animToggle = document.getElementById('animToggle');
        if (themeToggle) themeToggle.classList.toggle('active', theme==='light');
        if (animToggle) animToggle.classList.toggle('active', anim==='on');
    }

    apply();

    // Event listeners for the nav bar toggles
    const themeToggle = document.getElementById('themeToggle');
    const animToggle = document.getElementById('animToggle');

    if (themeToggle) {
        themeToggle.addEventListener('click', function(){
            const cur = localStorage.getItem('sf_theme') || 'dark';
            const next = cur === 'dark' ? 'light' : 'dark';
            localStorage.setItem('sf_theme', next);
            apply();
        });
    }

    if (animToggle) {
        animToggle.addEventListener('click', function(){
            const cur = localStorage.getItem('sf_anim') || 'on';
            const next = cur === 'on' ? 'off' : 'on';
            localStorage.setItem('sf_anim', next);
            apply();
        });
    }
})();

// --- Button Ripple Effect ---
(document.querySelectorAll('.btn-animated')||[]).forEach(function(btn){
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


// --- Pointer-based 3D Tilt for Feature/Comment Cards ---
(function(){
    var cards = document.querySelectorAll('.feature');
    if (!cards || cards.length === 0) return;
    var maxTilt = 10; // degrees

    function setTransform(card, xRatio, yRatio){
        // xRatio and yRatio are in [-0.5, 0.5]
        var rotateX = (yRatio * -2) * maxTilt; // move up => tilt back
        var rotateY = (xRatio * 2) * maxTilt;  // move right => tilt right
        card.style.transform = 'perspective(800px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg)';
    }

    function handleMove(e){
        var card = e.currentTarget;
        var rect = card.getBoundingClientRect();
        var x = (e.clientX - rect.left) / Math.max(1, rect.width) - 0.5; // -0.5..0.5
        var y = (e.clientY - rect.top) / Math.max(1, rect.height) - 0.5;  // -0.5..0.5
        setTransform(card, x, y);
        // Spotlight gradient following cursor
        var px = (x + 0.5) * 100; // 0..100
        var py = (y + 0.5) * 100; // 0..100
        var glow = getComputedStyle(card).getPropertyValue('--glow') || 'rgba(110,142,251,.45)';
        var spot = 'radial-gradient(300px 200px at ' + px + '% ' + py + '%, ' + glow + ', rgba(0,0,0,0) 70%)';
        card.style.setProperty('--spotGradient', spot);
    }

    function reset(e){
        var card = e.currentTarget;
        card.style.transform = 'perspective(800px) rotateX(0deg) rotateY(0deg)';
        card.style.removeProperty('--spotGradient');
    }

    cards.forEach(function(card){
        card.addEventListener('mousemove', handleMove);
        card.addEventListener('mouseleave', reset);
        card.addEventListener('mouseenter', function(){
            card.style.transition = 'transform .08s ease';
        });
    });
})();