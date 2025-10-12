// --- Theme Application Logic ---
(function(){
    // This is run once on load to ensure the body theme matches local storage/session state
    function apply(){ 
        var theme=localStorage.getItem('sf_theme')||'dark'; 
        var anim=localStorage.getItem('sf_anim')||'on'; 
        document.body.classList.toggle('light', theme==='light'); 
        document.body.classList.toggle('no-anim', anim==='off'); 
    }
    document.addEventListener('DOMContentLoaded', apply);
})();


// --- SweetAlert2 Confirmation Handler for Admin Delete Button ---
document.addEventListener('DOMContentLoaded', function() {
    // Check if Swal (SweetAlert2) is loaded
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded. Ensure the script tag is present.');
        return;
    }
    
    const cleanUpButtons = document.querySelectorAll('.clean-up-btn');

    cleanUpButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Stop the default link action

            const deleteUrl = this.getAttribute('data-delete-href');
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-username'); // Get the name from the data attribute
            
            Swal.fire({
                title: 'Are you sure?',
                text: `WARNING: You are about to delete ALL submissions for user "${userName}" (ID #${userId}). This action is irreversible!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                
                // Custom styling to match your dark theme
                confirmButtonColor: '#dc3545', // Red for delete
                cancelButtonColor: '#6c757d', // Secondary/Grey
                background: '#20254a', 
                color: '#fff', 
                customClass: {
                    container: 'leaderboard-swal-container' 
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // If confirmed, redirect to the PHP cleanup script
                    window.location.href = deleteUrl;
                }
            });
        });
    });
});