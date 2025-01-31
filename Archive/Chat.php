<?php
// Start the session and ensure the user is logged in
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: Login.html");
    exit();
}

// Check if item_id and conversation_id are provided
if (isset($_GET['conversation_id'], $_GET['item_id'])) {
    $conversation_id = intval($_GET['conversation_id']);
    $item_id = intval($_GET['item_id']);

    // Database connection
    $servername = "proj-mysql.uopnet.plymouth.ac.uk";
    $db_username = "comp2003_5";
    $db_password = "GcvN732+";
    $dbname = "comp2003_5";

    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get current user's ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $stmt->bind_result($current_user_id);
    $stmt->fetch();
    $stmt->close();

    // Check if the user is part of the conversation
    $stmt = $conn->prepare("SELECT * FROM messages WHERE conversation_id = ? AND (sender_id = ? OR receiver_id = ?)");
    $stmt->bind_param("iii", $conversation_id, $current_user_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("You do not have permission to view this conversation.");
    }
} else {
    header("Location: Inbox.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat - University of Plymouth Marketplace</title>

    <link rel="stylesheet" href="styles/ChatPage.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div id="chat-container">
        <div id="chat-header">
            Chat with Seller
        </div>
        <div id="messages"></div>
        <form id="message-form">
            <textarea id="message-input" rows="3" placeholder="Type your message here..." required></textarea>
            <button type="submit" aria-label="Send Message"><i class="fas fa-paper-plane"></i></button>
        </form>
    </div>

    <script>
        const conversationId = <?php echo json_encode($conversation_id); ?>;
        const itemId = <?php echo json_encode($item_id); ?>;
        const currentUserId = <?php echo json_encode($current_user_id); ?>;
        
        // Flag to check if it's the initial load
        let isInitialLoad = true;

        // Function to fetch messages
        async function fetchMessages() {
            try {
                const response = await fetch(`fetch_messages.php?conversation_id=${conversationId}`);
                const data = await response.json();
                const messagesContainer = document.getElementById('messages');
                messagesContainer.innerHTML = '';

                data.messages.forEach(message => {
                    const messageDiv = document.createElement('div');
                    messageDiv.classList.add('message');
                    messageDiv.classList.add(message.sender_id == currentUserId ? 'sent' : 'received');

                    messageDiv.innerHTML = `
                        <p>${sanitizeHTML(message.content)}</p>
                        <span class="timestamp">${formatTimestamp(message.sent_at)}</span>
                    `;
                    messagesContainer.appendChild(messageDiv);
                });

                if (isInitialLoad) {
                    // Scroll to the bottom on initial load
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    isInitialLoad = false; // Reset the flag after initial load
                } else {
                    // Optional: Auto-scroll to bottom only if user is near the bottom
                    const threshold = 100; // px from bottom
                    if (messagesContainer.scrollTop + messagesContainer.clientHeight >= messagesContainer.scrollHeight - threshold) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                }
            } catch (error) {
                console.error('Error fetching messages:', error);
            }
        }

        // Function to sanitize HTML to prevent XSS
        function sanitizeHTML(str) {
            const temp = document.createElement('div');
            temp.textContent = str;
            return temp.innerHTML;
        }

        // Function to format timestamp
        function formatTimestamp(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleString();
        }

        // Fetch messages every 3 seconds
        setInterval(fetchMessages, 3000);
        fetchMessages();

        // Handle form submission
        document.getElementById('message-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const messageInput = document.getElementById('message-input');
            const message = messageInput.value.trim();
            if (message.length > 0) {
                try {
                    const response = await fetch('send_message_ajax.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            conversation_id: conversationId,
                            item_id: itemId,
                            receiver_id: null, // Will be determined server-side
                            message: message
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        messageInput.value = '';
                        fetchMessages(); // Refresh messages
                    } else {
                        alert('Error sending message.');
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                }
            }
        });
    </script>
    <div class="backBTN">
        <a href="Inbox.php">Back</a>
    </div>
</body>
</html>
