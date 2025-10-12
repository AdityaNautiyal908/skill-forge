<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Optional: Get current username for display
$currentUsername = $_SESSION['username'] ?? 'User';

require_once "../config/db_mongo.php";
// No need to fetch data yet, fetching is done via JavaScript/AJAX
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillForge — Global Q&A Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/chat.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Update last seen timestamp and clear notifications when page loads
        $(document).ready(function() {
            // Update last seen timestamp (using POST or GET depending on update_last_seen.php)
            $.ajax({
                url: 'update_last_seen.php',
                type: 'POST', // Changed to POST for clarity, adjust if needed
                success: function(response) {
                    console.log('Last seen timestamp updated');
                },
                error: function(xhr, status, error) {
                    console.error('Error updating last seen timestamp:', error);
                }
            });
            
            // Clear notifications - using a simpler approach
            $.ajax({
                url: 'clear_notifications_simple.php',
                type: 'GET',
                success: function(response) {
                    console.log('Notifications cleared');
                },
                error: function(xhr, status, error) {
                    console.error('Error clearing notifications:', error);
                }
            });
        });
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">SkillForge Q&A</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="../core/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container my-4">
        <div class="card p-3" style="background: #1a202c; border: none;">
            <h3 class="text-white mb-3">Global Q&A Community</h3>
            
            <div id="chatBox" class="chat-box mb-3">
                </div>

            <div class="input-group">
                <input type="text" id="messageInput" class="form-control" placeholder="Ask a question or provide an answer..." style="background: #3c467b; border-color: #3c467b; color: white;">
                <button class="btn btn-primary" id="sendBtn">Send</button>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP variable to JavaScript
        const currentUserId = <?= json_encode((string)($_SESSION['user_id'] ?? 0)); ?>;
    </script>
    
    <script src="assets/js/chat.js"></script>
</body>
</html>