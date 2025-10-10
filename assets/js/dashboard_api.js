// Function to check for new notifications
function checkNotifications() {
    // Use a direct file check approach
    fetch('check_notifications_simple.php')
        .then(response => response.json())
        .then(data => {
            console.log('Notification check:', data);
            const badge = document.getElementById('qa-notification');
            
            if (data.has_notifications) {
                const wasHidden = badge.style.display === 'none';
                badge.style.display = 'inline';
                badge.textContent = 'New';
                
                // Play sound and flash if notification wasn't showing before
                if (wasHidden) {
                    playNotificationSound();
                    flashNotification();
                }
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error checking notifications:', error);
        });
}

// Function to flash the notification badge
function flashNotification() {
    const badge = document.getElementById('qa-notification');
    let flashCount = 0;
    
    const flashInterval = setInterval(() => {
        // Toggle colors
        badge.style.backgroundColor = flashCount % 2 === 0 ? '#dc3545' : '#28a745';
        flashCount++;
        
        if (flashCount > 5) {
            clearInterval(flashInterval);
            badge.style.backgroundColor = '#dc3545'; // Reset to original color
        }
    }, 300);
}

// Play notification sound when new messages arrive
function playNotificationSound() {
    // Create audio element for notification sound
    const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
    audio.volume = 0.5;
    audio.play().catch(e => console.log('Audio play prevented by browser policy'));
}

// Check for notifications on page load
document.addEventListener('DOMContentLoaded', checkNotifications);

// Check for notifications every 5 seconds for real-time updates
setInterval(checkNotifications, 5000);