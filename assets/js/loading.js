// The REDIRECT_DELAY_MS constant is defined globally in loading.php

document.addEventListener('DOMContentLoaded', function() {
    // Redirect logic using the delay passed from PHP
    if (typeof REDIRECT_DELAY_MS !== 'undefined') {
        setTimeout(function() {
            window.location.href = 'dashboard.php';
        }, REDIRECT_DELAY_MS);
    } else {
        // Fallback or immediate redirect if the PHP variable is missing
        window.location.href = 'dashboard.php';
    }
});