document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault(); // Stop the default form submission

            const form = this.closest('.delete-user-form');
            const username = this.getAttribute('data-username');
            const uid = this.getAttribute('data-uid');

            Swal.fire({
                title: 'Are you sure?',
                text: `Permanently delete user "${username}" (ID: ${uid})? WARNING: This action is irreversible.`,
                icon: 'warning',
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                
                // Styling to match your dark theme
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                background: '#20254a',
                color: '#fff', 
            }).then((result) => {
                if (result.isConfirmed) {
                    // If confirmed, submit the form to execute the PHP delete logic
                    form.submit();
                }
            });
        });
    });
});