// --- Recipient Toggling Logic ---
function toggleEmailInput(value) {
    const div = document.getElementById('singleEmailDiv');
    const input = document.getElementById('single_email');
    if (div && input) {
        div.style.display = (value === 'single') ? 'block' : 'none';
        input.required = (value === 'single');
    }
}

// Attach event listener for the select box
document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('recipient_type');
    if (select) {
        select.addEventListener('change', (e) => {
            toggleEmailInput(e.target.value);
        });
        // Initial call to set state correctly based on PHP (if set)
        toggleEmailInput(select.value);
    }
});


// --- SweetAlert for Server Messages ---
document.addEventListener('DOMContentLoaded', () => {
    // PHP_ERROR and PHP_MESSAGE are passed as global constants in admin_mail.php
    
    if (typeof PHP_ERROR !== 'undefined' && PHP_ERROR) {
        Swal.fire({
            icon: 'error',
            title: 'Mail Send Error',
            text: PHP_ERROR,
            background: '#3c467b',
            color: '#fff',
            confirmButtonColor: '#dc3545'
        });
    } else if (typeof PHP_MESSAGE !== 'undefined' && PHP_MESSAGE) {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: PHP_MESSAGE,
            background: '#3c467b',
            color: '#fff',
            confirmButtonColor: '#6d7cff'
        });
    }
});