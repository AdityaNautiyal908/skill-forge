// --- Comments LTR carousel: continuous scrolling of full cards ---
(function(){
    var wrap = document.getElementById('commentsCarousel');
    var track = document.getElementById('commentsCarouselTrack');
    var source = document.getElementById('commentsSource');
    if (!wrap || !track || !source) return;

    // Gather cards from hidden source
    var cards = Array.prototype.slice.call(source.querySelectorAll('.comment-card'));
    if (!cards.length) return;
    var cardWidth = null; // computed later
    var html = cards.map(function(c){ return c.outerHTML; }).join('');
    track.innerHTML = html + html; // duplicate for seamless loop

    var totalWidth = 0;
    function recalc(){ 
        totalWidth = track.scrollWidth / 2; 
        var first = track.querySelector('.comment-card'); 
        // 18 is the gap defined in CSS
        cardWidth = first ? first.getBoundingClientRect().width + 18 : 420 + 18; 
    }
    recalc();
    window.addEventListener('resize', function(){ setTimeout(recalc, 100); });

    // Animate left-to-right
    var offset = -totalWidth;
    var speed = 40; // px/s
    var last = null;
    var paused = false;
    var isHovering = false;
    var rafId = null;

    function step(ts){
        if (paused || isHovering) { 
            rafId = requestAnimationFrame(step); 
            return; 
        }
        if (last == null) last = ts;
        var dt = (ts - last) / 1000; last = ts;
        offset += speed * dt;
        if (offset >= 0) offset = -totalWidth;
        track.style.transform = 'translateX(' + offset + 'px)';
        rafId = requestAnimationFrame(step);
    }
    rafId = requestAnimationFrame(step);

    // Controls
    var prevBtn = document.getElementById('ccPrev');
    var nextBtn = document.getElementById('ccNext');
    var playPauseBtn = document.getElementById('ccPlayPause');

    function jump(delta){
        // Stop momentarily to avoid fighting animation frame
        paused = true;
        
        // snap by one card width left (-) or right (+)
        // LTR, so positive delta moves content right, showing previous card
        offset += delta; 
        
        // wrap handling
        while (offset >= 0) offset -= totalWidth;
        while (offset < -totalWidth) offset += totalWidth;
        
        track.style.transform = 'translateX(' + offset + 'px)';
        
        // small delay then resume if play state is not paused by user
        setTimeout(function(){ 
            if (playPauseBtn && playPauseBtn.getAttribute('data-paused') !== 'true') { 
                paused = false; 
            } 
        }, 50);
    }

    if (prevBtn) prevBtn.addEventListener('click', function(){ jump(-cardWidth); });
    if (nextBtn) nextBtn.addEventListener('click', function(){ jump(cardWidth); });
    
    if (playPauseBtn) playPauseBtn.addEventListener('click', function(){
        var isPaused = this.getAttribute('data-paused') === 'true';
        if (isPaused) {
            this.setAttribute('data-paused', 'false');
            this.textContent = '❚❚';
            paused = false;
            last = null; // reset timing for smooth resume
        } else {
            this.setAttribute('data-paused', 'true');
            this.textContent = '►';
            paused = true;
        }
    });

    // Pause on card hover
    document.querySelectorAll('#commentsCarouselTrack .comment-card').forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            isHovering = true;
        });
        card.addEventListener('mouseleave', function() {
            isHovering = false;
        });
    });

})();