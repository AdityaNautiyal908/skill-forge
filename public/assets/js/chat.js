const chatBox = document.getElementById('chatBox');
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');
// currentUserId is defined globally in chat.php
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
        // NOTE: We rely on user_id being comparable. If using MongoDB ObjectId, you might need a different comparison.
        // Assuming user_id is a string or number that can be directly compared.
        const isSelf = String(msg.user_id) === String(currentUserId);
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
    // If the scroll position is within 50px of the bottom, keep auto-scrolling on updates
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
        Swal.fire({
            icon: 'warning',
            title: 'Oops...',
            text: 'Message cannot be empty.',
            background: '#111437',
            color: '#fff',
            confirmButtonColor: '#6d7cff'
        });
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
            isAutoScrolling = true;
            fetchMessages();
        } else {
            const errorData = await response.json();
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: errorData.error || 'Failed to post message.',
                background: '#111437',
                color: '#fff',
                confirmButtonColor: '#dc3545'
            });
        }
    } catch (error) {
        console.error('Network Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Network Error!',
            text: 'A network error occurred. Could not send message.',
            background: '#111437',
            color: '#fff',
            confirmButtonColor: '#dc3545'
        });
    }
}

// Poll for new messages every 3 seconds (to simulate real-time)
setInterval(fetchMessages, 3000);

// Initial load
document.addEventListener('DOMContentLoaded', fetchMessages);