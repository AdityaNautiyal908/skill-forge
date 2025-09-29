<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Optional: Get current username for display
$currentUsername = $_SESSION['username'] ?? 'User';

require_once "config/db_mongo.php";
// No need to fetch data yet, fetching is done via JavaScript/AJAX
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillForge — Global Q&A Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Update last seen timestamp when page loads
        $(document).ready(function() {
            $.ajax({
                url: 'update_last_seen.php',
                type: 'POST',
                dataType: 'json',
                success: function(data) {
                    console.log('Last seen timestamp updated');
                },
                error: function(xhr, status, error) {
                    console.error('Error updating last seen timestamp:', error);
                }
            });
        });
    </script>
    <style>
        body { margin: 0; color: white; min-height: 100vh; background: linear-gradient(135deg, #171b30, #20254a 55%, #3c467b); }
        .navbar { background: rgba(0,0,0,0.35) !important; backdrop-filter: blur(10px); }
        /* CRITICAL CSS FIX: Standard chat layout (newest at bottom) */
        .chat-box { 
            background-color: #2c324c; 
            border-radius: 12px; 
            height: 70vh; 
            overflow-y: scroll; 
            padding: 15px; 
            display: flex; 
            flex-direction: column; /* Messages flow top-to-bottom */
            justify-content: flex-start; /* Messages start at the top */
        }
        .message { padding: 10px 15px; margin-bottom: 8px; border-radius: 15px; max-width: 80%; }
        .msg-self { background-color: #0d6efd; color: white; align-self: flex-end; margin-left: auto; }
        .msg-other { background-color: #4a5568; color: white; align-self: flex-start; }
        .username { font-weight: bold; font-size: 0.9em; margin-bottom: 2px; }
        .timestamp { font-size: 0.75em; color: #aaa; margin-top: 5px; text-align: right; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">SkillForge Q&A</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container my-4">
        <div class="card p-3" style="background: #1a202c; border: none;">
            <h3 class="text-white mb-3">Global Q&A Community</h3>
            
            <!-- Chat Box -->
            <div id="chatBox" class="chat-box mb-3">
                <!-- Messages load here -->
            </div>

            <!-- Input Form -->
            <div class="input-group">
                <input type="text" id="messageInput" class="form-control" placeholder="Ask a question or provide an answer..." style="background: #3c467b; border-color: #3c467b; color: white;">
                <button class="btn btn-primary" id="sendBtn">Send</button>
            </div>
        </div>
    </div>

    <script>
        // Update last seen timestamp when page loads
        $(document).ready(function() {
            $.ajax({
                url: 'update_last_seen.php',
                type: 'GET',
                dataType: 'json',
                error: function() {
                    console.error('Failed to update last seen timestamp');
                }
            });
        });
        
        const chatBox = document.getElementById('chatBox');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const currentUserId = <?= json_encode((int)($_SESSION['user_id'] ?? 0)); ?>;
        let isAutoScrolling = true;

        // Function to fetch messages
        async function fetchMessages() {
            try {
                const response = await fetch('fetch_chat.php');
                
                if (!response.ok) {
                    throw new Error('Failed to fetch messages. Status: ' + response.status);
                }
                
                const messages = await response.json();
                renderMessages(messages);
            } catch (error) {
                console.error('AJAX Fetch Error:', error);
            }
        }

        // Function to render messages
        function renderMessages(messages) {
            // Check if user is near the bottom before clearing, to handle auto-scroll
            const shouldScroll = (chatBox.scrollHeight - chatBox.scrollTop) <= chatBox.clientHeight + 1;
            
            chatBox.innerHTML = '';
            
            messages.forEach(msg => {
                const isSelf = parseInt(msg.user_id) === parseInt(currentUserId);
                const msgClass = isSelf ? 'msg-self' : 'msg-other';
                
                const messageEl = document.createElement('div');
                messageEl.className = `message ${msgClass}`;
                
                const date = new Date(msg.timestamp);
                const timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                messageEl.innerHTML = `
                    <div class="username">${msg.username}</div>
                    <div>${msg.message}</div>
                    <div class="timestamp">${timeStr}</div>
                `;
                chatBox.appendChild(messageEl);
            });

            // Auto-scroll logic: Scroll to the very bottom to see the newest message
            if (shouldScroll || isAutoScrolling) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        }
        
        // Auto-scroll logic for standard chat (scrolls to bottom if near bottom)
        chatBox.addEventListener('scroll', () => {
            const isNearBottom = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 50; 
            isAutoScrolling = isNearBottom;
        });


        // Function to send messages
        sendBtn.addEventListener('click', postMessage);
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                postMessage();
            }
        });

        async function postMessage() {
            const message = messageInput.value.trim();
            if (message === '') {
                alert("Message cannot be empty.");
                return;
            }

            try {
                const formData = new FormData();
                formData.append('message', message);

                const response = await fetch('post_chat.php', {
                    method: 'POST',
                    body: formData,
                });

                if (response.ok) {
                    messageInput.value = '';
                    // Force refresh to grab new message and auto-scroll
                    fetchMessages(); 
                } else {
                    const errorData = await response.json();
                    alert('Error: ' + (errorData.error || 'Failed to post message.'));
                }
            } catch (error) {
                console.error('Network Error:', error);
                alert('A network error occurred. Could not send message.');
            }
        }

        // Poll for new messages every 3 seconds (to simulate real-time)
        setInterval(fetchMessages, 3000);
        fetchMessages(); // Initial load
    </script>
</body>
</html>



