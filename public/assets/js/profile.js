// --- Theme/Animation Application Logic ---
(function(){
    // Global constants (THEME_PREF, ANIM_PREF) are implicitly defined in the profile.php script tag
    // using the PHP variables $prefs['theme'] and $prefs['animations'].

    // Apply theme/animation from saved prefs for immediate effect
    function applyProfilePrefs(){
        // Use PHP values directly (injected into JS)
        var theme = '<?= $prefs['theme'] ?>';
        var anim = '<?= $prefs['animations'] ?>';
        
        // Fallback for security/consistency (though PHP sets the initial body classes)
        if (!theme) theme = localStorage.getItem('sf_theme') || 'dark';
        if (!anim) anim = localStorage.getItem('sf_anim') || 'on';

        // Apply classes to the body
        document.body.classList.toggle('light', theme === 'light');
        document.body.classList.toggle('dark', theme === 'dark');
        document.body.classList.toggle('no-anim', anim === 'off');

        // Update localStorage to keep browser's local cache consistent with database
        localStorage.setItem('sf_theme', theme);
        localStorage.setItem('sf_anim', anim);
    }
    
    // Run on document load
    document.addEventListener('DOMContentLoaded', applyProfilePrefs);
})();